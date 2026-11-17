#!/usr/bin/env python3
"""
Generate blog posts from CSV questions using an Ollama‑compatible endpoint.
Uses SQLite for persistent storage and resume capability.
Enforces a minimum word count (500) and retries with backoff.
Includes parallelism, full debug logging of AI prompts & responses,
and graceful shutdown.
"""

import csv
import json
import logging
import os
import signal
import sqlite3
import sys
import threading
import time
from argparse import ArgumentParser, Namespace, RawDescriptionHelpFormatter
from concurrent.futures import ThreadPoolExecutor, as_completed
from datetime import datetime
from pathlib import Path
from typing import Optional, List, Dict, Any

import requests


# ============================================================================
# LOGGING SETUP
# ============================================================================
class ColorFormatter(logging.Formatter):
    """Add colors to console log output."""

    COLORS = {
        logging.DEBUG: "\033[36m",          # cyan
        logging.INFO: "\033[32m",           # green
        logging.WARNING: "\033[33m",        # yellow
        logging.ERROR: "\033[31m",          # red
        logging.CRITICAL: "\033[41m",       # red background
    }
    RESET = "\033[0m"

    def format(self, record: logging.LogRecord) -> str:
        color = self.COLORS.get(record.levelno, self.RESET)
        record.msg = f"{color}{record.msg}{self.RESET}"
        return super().format(record)


def setup_logging(verbose: bool = False, log_file: Optional[str] = None) -> None:
    """Configure logging with optional file output and verbosity."""
    level = logging.DEBUG if verbose else logging.INFO
    root = logging.getLogger()
    root.setLevel(logging.DEBUG)                 # capture everything, handlers filter

    # Console handler with color
    console = logging.StreamHandler(sys.stdout)
    console.setLevel(level)
    console.setFormatter(
        ColorFormatter("%(asctime)s  %(levelname)-8s  %(message)s", datefmt="%H:%M:%S")
    )
    root.addHandler(console)

    # Optional file handler (no colors, more detail)
    if log_file:
        fh = logging.FileHandler(log_file, encoding="utf-8")
        fh.setLevel(logging.DEBUG)
        fh.setFormatter(
            logging.Formatter("%(asctime)s  %(levelname)-8s  %(name)s  %(message)s")
        )
        root.addHandler(fh)
        logging.info("Logging to %s", log_file)


log = logging.getLogger("bloggen")

# ============================================================================
# CONFIGURATION
# ============================================================================
DEFAULT_CONFIG = {
    "api_endpoint": "https://ai.izdrail.com/v1/chat/completions",
    "model": "neural-chat:7b",
    "api_key": "",
    "request_timeout": 500,
    "max_retries": 3,
    "backoff_factor": 2,
    "min_words": 400,
    "request_delay": 2.0,          # seconds between sequential requests
    "concurrency": 1,
    "system_prompt": (
        "You are a senior developer and technical blogger. "
        "Your answers are accurate, practical, and well‑explained. "
        "Always include natural backlinks to https://laravelcompany.com where relevant. "
        "Your blog posts must be at least 500 words long."
    ),
    "user_prompt_template": (
        "Write a comprehensive blog post that answers the following question.\n\n"
        "Title: {title}\n"
        "Body: {body}\n\n"
        "The blog post should:\n"
        "- Provide a correct and thorough answer from a developer's perspective.\n"
        "- Include relevant code examples or best practices.\n"
        "- Naturally incorporate backlinks to https://laravelcompany.com in the content.\n"
        "- Be well‑structured with headings, paragraphs, and a clear conclusion.\n"
        "- Be at least 500 words long."
    ),
    "expand_prompt": (
        "\n\nThe previous response was too short. Please expand it to at least 500 words "
        "by adding more details, examples, and deeper explanations, while keeping the same "
        "high quality and structure."
    ),
    "db_file": "blog_posts.db",
    "export_dir": "exported_posts",
    "log_file": None,
    "debug_ai": True,              # whether to log full AI prompts & responses
}


def load_config(config_path: Optional[str]) -> dict:
    """Load config from JSON file, overlaying defaults with file values + env vars."""
    cfg = dict(DEFAULT_CONFIG)

    if config_path and os.path.exists(config_path):
        with open(config_path, "r", encoding="utf-8") as f:
            file_cfg = json.load(f)
        cfg.update(file_cfg)
        log.info("Loaded config from %s", config_path)

    # Environment variable overrides
    env_map = {
        "AI_ENDPOINT": "api_endpoint",
        "AI_MODEL": "model",
        "AI_API_KEY": "api_key",
        "REQUEST_TIMEOUT": "request_timeout",
        "DB_FILE": "db_file",
        "EXPORT_DIR": "export_dir",
        "DEBUG_AI": "debug_ai",
    }
    for env_key, cfg_key in env_map.items():
        val = os.environ.get(env_key)
        if val is not None:
            if cfg_key in ("request_timeout",):
                val = int(val)
            elif cfg_key == "debug_ai":
                val = val.lower() in ("1", "true", "yes")
            cfg[cfg_key] = val

    return cfg


# ============================================================================
# DATABASE HELPERS
# ============================================================================
DB_FILE = "blog_posts.db"


def get_db_connection(db_path: str):
    """Return a connection to the SQLite database with a busy timeout."""
    conn = sqlite3.connect(db_path, timeout=10)      # avoid "database is locked"
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    return conn


def init_db(db_path: str) -> None:
    """Create the table if it doesn't exist."""
    conn = get_db_connection(db_path)
    conn.execute("""
        CREATE TABLE IF NOT EXISTS blog_posts (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            body TEXT NOT NULL,
            blog_post TEXT,
            status TEXT DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            retries INTEGER DEFAULT 0,
            word_count INTEGER DEFAULT 0,
            error_message TEXT,
            UNIQUE(title)
        )
    """)
    conn.commit()

    _migrate_db(conn)

    conn.close()
    log.debug("Database initialized at %s", db_path)


def _migrate_db(conn: sqlite3.Connection) -> None:
    """Add columns that may be missing from older schema versions."""
    cursor = conn.execute("PRAGMA table_info(blog_posts)")
    existing = {row["name"] for row in cursor.fetchall()}

    migrations = {
        "word_count": "INTEGER DEFAULT 0",
        "error_message": "TEXT",
    }

    for col, col_def in migrations.items():
        if col not in existing:
            log.info("Adding missing column: %s", col)
            conn.execute(f"ALTER TABLE blog_posts ADD COLUMN {col} {col_def}")

    conn.commit()


def import_csv_to_db(csv_file: str, db_path: str) -> int:
    """Read CSV and insert new records into the database (skip duplicates by title)."""
    conn = get_db_connection(db_path)
    cursor = conn.cursor()
    inserted = 0

    with open(csv_file, "r", newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        if not {"Title", "Body"}.issubset(reader.fieldnames or []):
            raise ValueError("CSV must have 'Title' and 'Body' columns.")

        for row in reader:
            title = row["Title"].strip()
            body = row["Body"].strip()
            if not title or not body:
                log.warning("Skipping row with empty title or body")
                continue

            cursor.execute("SELECT id FROM blog_posts WHERE title = ?", (title,))
            if cursor.fetchone() is not None:
                log.debug("Duplicate title '%s' - skipping", title[:60])
                continue

            cursor.execute(
                "INSERT INTO blog_posts (title, body, status) VALUES (?, ?, ?)",
                (title, body, "pending"),
            )
            inserted += 1
            log.debug("Inserted record: %s", title[:60])

    conn.commit()
    conn.close()
    return inserted


def get_records(
    db_path: str,
    status_filter: Optional[str] = None,
    limit: Optional[int] = None,
    max_retries: int = 3,
) -> List[Dict[str, Any]]:
    """Fetch records by status. Includes failed records with retries < max_retries."""
    conn = get_db_connection(db_path)
    conditions = []
    params = []

    if status_filter == "pending":
        conditions.append("status IN ('pending', 'failed') AND retries < ?")
        params.append(max_retries)
    elif status_filter:
        conditions.append("status = ?")
        params.append(status_filter)

    where = ""
    if conditions:
        where = f"WHERE {' AND '.join(conditions)}"

    query = f"SELECT id, title, body, status, retries, word_count, error_message, blog_post FROM blog_posts {where} ORDER BY id"
    if limit is not None:
        query += f" LIMIT {limit}"

    cursor = conn.execute(query, params)
    rows = cursor.fetchall()
    conn.close()
    return [dict(row) for row in rows]


def update_record_status(
    db_path: str,
    record_id: int,
    status: str,
    blog_post: Optional[str] = None,
    retries: Optional[int] = None,
    word_count: Optional[int] = None,
    error_message: Optional[str] = None,
) -> None:
    """Update status and metadata for a record."""
    conn = get_db_connection(db_path)
    cursor = conn.cursor()
    updates = []
    params = []

    if status:
        updates.append("status = ?")
        params.append(status)
    if blog_post is not None:
        updates.append("blog_post = ?")
        params.append(blog_post)
    if retries is not None:
        updates.append("retries = ?")
        params.append(retries)
    if word_count is not None:
        updates.append("word_count = ?")
        params.append(word_count)
    if error_message is not None:
        updates.append("error_message = ?")
        params.append(error_message)

    updates.append("updated_at = ?")
    params.append(datetime.now().isoformat())
    params.append(record_id)

    sql = f"UPDATE blog_posts SET {', '.join(updates)} WHERE id = ?"
    cursor.execute(sql, params)
    conn.commit()
    conn.close()


def delete_records(db_path: str, status: Optional[str] = None) -> int:
    """Delete records, optionally by status. Returns count deleted."""
    conn = get_db_connection(db_path)
    if status:
        conn.execute("DELETE FROM blog_posts WHERE status = ?", (status,))
    else:
        conn.execute("DELETE FROM blog_posts")
    deleted = conn.total_changes
    conn.commit()
    conn.close()
    return deleted


# ============================================================================
# AI CALL WITH RETRY, BACKOFF, AND FULL DEBUG LOGGING
# ============================================================================

def mask_api_key(key: str) -> str:
    """Return a partially masked API key for logging."""
    if not key:
        return "<none>"
    return key[:4] + "***" + key[-4:] if len(key) > 8 else "***"


def _log_ai_request(payload: dict, cfg: dict) -> None:
    """Log the full AI request (with masked API key) when debug_ai is enabled."""
    if not cfg.get("debug_ai"):
        return
    safe_payload = {**payload}
    # mask the authorization header if present
    if cfg.get("api_key"):
        safe_payload["headers"] = {"Authorization": f"Bearer {mask_api_key(cfg['api_key'])}"}
    log.debug("AI REQUEST: %s", json.dumps(safe_payload, indent=2, ensure_ascii=False))


def _log_ai_response(response_data: dict, status_code: int, attempt: int) -> None:
    """Log the full AI response when debug_ai is enabled."""
    if not log.isEnabledFor(logging.DEBUG):
        return
    log.debug(
        "AI RESPONSE (attempt %d, HTTP %d): %s",
        attempt,
        status_code,
        json.dumps(response_data, indent=2, ensure_ascii=False)[:3000]  # limit length
    )


def call_ai(
    prompt: str,
    cfg: dict,
    expand: bool = False,
) -> Optional[str]:
    """
    Send a request to the AI endpoint.
    Retries with exponential backoff on timeouts or errors.
    Logs full request/response when --debug-ai is active.
    """
    headers = {"Content-Type": "application/json"}
    if cfg.get("api_key"):
        headers["Authorization"] = f"Bearer {cfg['api_key']}"

    system_msg = cfg["system_prompt"]
    if expand:
        system_msg += " The response must be at least 500 words. If you have already written a shorter version, expand it now."

    messages = [
        {"role": "system", "content": system_msg},
        {"role": "user", "content": prompt},
    ]

    payload = {
        "model": cfg["model"],
        "messages": messages,
        "temperature": 0.7,
        "max_tokens": 3000,
        "stream": False,
    }

    max_retries = cfg["max_retries"]
    backoff = cfg["backoff_factor"]
    timeout = cfg["request_timeout"]

    for attempt in range(1, max_retries + 1):
        try:
            _log_ai_request({**payload, "headers": headers}, cfg)
            log.debug(
                "API request (attempt %d/%d): model=%s, timeout=%d",
                attempt,
                max_retries,
                cfg["model"],
                timeout,
            )
            resp = requests.post(
                cfg["api_endpoint"],
                headers=headers,
                json=payload,
                timeout=timeout,
            )
            resp.raise_for_status()
            data = resp.json()
            _log_ai_response(data, resp.status_code, attempt)

            if "choices" in data:
                content = data["choices"][0]["message"]["content"].strip()
            elif "response" in data:
                content = data["response"].strip()
            else:
                raise ValueError(f"Unexpected response format: {list(data.keys())}")

            log.debug("API response received (%d chars)", len(content))
            return content

        except requests.exceptions.Timeout:
            wait = backoff ** attempt
            log.warning("Timeout (attempt %d). Retrying in %ds...", attempt, wait)
            time.sleep(wait)
        except requests.exceptions.ConnectionError as e:
            wait = backoff ** attempt
            log.error(
                "Connection error (attempt %d): %s. Retrying in %ds...",
                attempt,
                e,
                wait,
            )
            time.sleep(wait)
        except requests.exceptions.HTTPError as e:
            status_code = e.response.status_code if e.response is not None else "?"
            log.error("HTTP %s (attempt %d): %s", status_code, attempt, e)
            if e.response and e.response.text:
                log.debug("Response body: %s", e.response.text[:500])
                _log_ai_response({"error_body": e.response.text[:2000]}, status_code, attempt)
            if attempt == max_retries:
                return None
            wait = backoff ** attempt
            time.sleep(wait)
        except json.JSONDecodeError as e:
            log.error("Invalid JSON response (attempt %d): %s", attempt, e)
            return None
        except Exception as e:
            wait = backoff ** attempt
            log.warning(
                "API error (attempt %d): %s. Retrying in %ds...", attempt, e, wait
            )
            time.sleep(wait)

    return None


def generate_blog_post(title: str, body: str, cfg: dict) -> Optional[str]:
    """
    Generate a blog post with at least min_words words.
    Retries up to max_retries times, each time asking to expand if too short.
    """
    user_prompt = cfg["user_prompt_template"].format(title=title, body=body)
    current_prompt = user_prompt
    expand = False

    for attempt in range(1, cfg["max_retries"] + 1):
        log.info("Attempt %d/%d...", attempt, cfg["max_retries"])
        # log the prompt being sent
        if cfg.get("debug_ai"):
            log.debug("PROMPT (attempt %d): %s", attempt, current_prompt)

        content = call_ai(current_prompt, cfg, expand=expand)

        if content is None:
            log.warning("API call returned None")
            continue

        # log the raw response
        if cfg.get("debug_ai"):
            log.debug("RAW RESPONSE (attempt %d): %s", attempt, content[:2000])

        word_count = len(content.split())
        log.info("Generated %d words", word_count)

        if word_count >= cfg["min_words"]:
            return content

        log.info(
            "Too short (%d < %d). Asking for expansion.", word_count, cfg["min_words"]
        )
        current_prompt = user_prompt + cfg["expand_prompt"]
        expand = True

    log.warning(
        "Failed to produce %d words after %d attempts",
        cfg["min_words"],
        cfg["max_retries"],
    )
    return None


# ============================================================================
# EXPORT
# ============================================================================

def export_posts(db_path: str, export_dir: str, status: str = "completed") -> int:
    """Export blog posts as markdown files to a directory. Returns count exported."""
    export_path = Path(export_dir)
    export_path.mkdir(parents=True, exist_ok=True)
    records = get_records(db_path, status_filter=status)
    exported = 0

    for rec in records:
        if not rec["blog_post"]:
            continue
        title = rec["title"]
        body = rec["blog_post"]
        word_count = rec.get("word_count", len(body.split()))
        rec_id = rec["id"]

        safe_name = "".join(c if c.isalnum() or c in " _-" else "_" for c in title)
        safe_name = safe_name.strip().replace(" ", "_")[:80] or f"post_{rec_id}"
        filepath = export_path / f"{rec_id:04d}_{safe_name}.md"

        frontmatter = (
            f"---\n"
            f"id: {rec_id}\n"
            f'title: "{title}"\n'
            f"word_count: {word_count}\n"
            f"generated_at: {rec.get('updated_at', datetime.now().isoformat())}\n"
            f"---\n\n"
        )

        filepath.write_text(frontmatter + body, encoding="utf-8")
        exported += 1
        log.info("Exported: %s", filepath.name)

    return exported


# ============================================================================
# CONNECTION TEST
# ============================================================================

def test_connection(cfg: dict) -> bool:
    """Test connectivity to the AI endpoint."""
    headers = {"Content-Type": "application/json"}
    if cfg.get("api_key"):
        headers["Authorization"] = f"Bearer {cfg['api_key']}"

    payload = {
        "model": cfg["model"],
        "messages": [
            {"role": "user", "content": "Reply with exactly: OK"},
        ],
        "max_tokens": 10,
        "stream": False,
    }

    log.info("Testing connection to %s ...", cfg["api_endpoint"])
    log.info("Model: %s", cfg["model"])
    if cfg.get("debug_ai"):
        log.debug("Test payload: %s", json.dumps(payload, indent=2))

    try:
        resp = requests.post(
            cfg["api_endpoint"], headers=headers, json=payload, timeout=30
        )
        resp.raise_for_status()
        data = resp.json()

        if cfg.get("debug_ai"):
            _log_ai_response(data, resp.status_code, 1)

        if "choices" in data:
            log.info("Response: %s", data["choices"][0]["message"]["content"].strip())
        elif "response" in data:
            log.info("Response: %s", data["response"].strip())
        else:
            log.warning("Unexpected format, but connection succeeded.")
        log.info("Connection OK")
        return True

    except requests.exceptions.ConnectionError as e:
        log.error("Connection failed: %s", e)
    except requests.exceptions.Timeout:
        log.error("Connection timed out after 30s")
    except requests.exceptions.HTTPError as e:
        log.error("HTTP error: %s", e)
        if e.response and e.response.text:
            log.debug("Response: %s", e.response.text[:300])
    except Exception as e:
        log.error("Error: %s", e)

    return False


# ============================================================================
# STATISTICS
# ============================================================================

def show_stats(db_path: str) -> None:
    """Display detailed statistics about records."""
    conn = get_db_connection(db_path)

    cursor = conn.execute("""
        SELECT status, COUNT(*) as count FROM blog_posts GROUP BY status
    """)
    status_counts = {row["status"]: row["count"] for row in cursor.fetchall()}
    total = sum(status_counts.values())

    cursor = conn.execute("""
        SELECT
            COUNT(*) as total_completed,
            COALESCE(AVG(word_count), 0) as avg_words,
            COALESCE(MIN(word_count), 0) as min_words,
            COALESCE(MAX(word_count), 0) as max_words
        FROM blog_posts WHERE status = 'completed'
    """)
    stats = dict(cursor.fetchone())

    conn.close()

    log.info("Database statistics (%s)", DB_FILE)
    log.info("  Total records: %d", total)
    for s in ("pending", "processing", "completed", "failed"):
        log.info("  %s: %d", s.capitalize(), status_counts.get(s, 0))

    completed = status_counts.get("completed", 0)
    if completed:
        log.info("")
        log.info("Completed post stats:")
        log.info("  Average word count: %d", round(stats["avg_words"]))
        log.info("  Min word count: %d", stats["min_words"])
        log.info("  Max word count: %d", stats["max_words"])


# ============================================================================
# MAIN PROCESSING
# ============================================================================

_shutdown_requested = False


def _signal_handler(signum, frame) -> None:
    """Handle Ctrl+C gracefully."""
    global _shutdown_requested
    if _shutdown_requested:
        log.warning("Forced exit.")
        sys.exit(1)
    _shutdown_requested = True
    log.warning(
        "\nShutdown requested. Finishing current record... (Ctrl+C again to force)"
    )


class ProgressTracker:
    """Thread‑safe progress tracker for parallel execution."""
    def __init__(self, total: int):
        self.total = total
        self._lock = threading.Lock()
        self.completed: List[int] = []
        self.failed: List[int] = []
        self.running: Dict[int, str] = {}   # record_id -> title

    def start(self, record_id: int, title: str) -> None:
        with self._lock:
            self.running[record_id] = title

    def finish(self, record_id: int, success: bool) -> None:
        with self._lock:
            self.running.pop(record_id, None)
            (self.completed if success else self.failed).append(record_id)

    def summary(self) -> str:
        with self._lock:
            done = len(self.completed) + len(self.failed)
            return (
                f"[{done}/{self.total}] "
                f"completed={len(self.completed)} "
                f"failed={len(self.failed)} "
                f"running={len(self.running)}"
            )

    def log_line(self) -> str:
        with self._lock:
            running_titles = ", ".join(self.running.values())
            titles = f"  running: {running_titles}" if running_titles else ""
            return f"{self.summary()}{titles}"


def _worker(
    cfg: dict,
    record: dict,
    progress: ProgressTracker,
    idx: int,
    total: int,
) -> None:
    """Process a single record with its own DB connection. Called by workers."""
    rec_id = record["id"]
    title = record["title"]
    body = record["body"]
    retries = record["retries"]
    db_path = cfg["db_file"]
    new_retries = retries + 1

    progress.start(rec_id, title[:60])

    # Mark as processing (with its own connection)
    conn = get_db_connection(db_path)
    try:
        conn.execute(
            "UPDATE blog_posts SET status = 'processing', retries = ?, updated_at = ? WHERE id = ?",
            (new_retries, datetime.now().isoformat(), rec_id),
        )
        conn.commit()
    finally:
        conn.close()

    blog_post = generate_blog_post(title, body, cfg)
    success = blog_post is not None

    # Final status update
    conn = get_db_connection(db_path)
    try:
        if blog_post is None:
            err_msg = f"Failed after {new_retries} retries"
            conn.execute(
                "UPDATE blog_posts SET status = 'failed', error_message = ?, updated_at = ? WHERE id = ?",
                (err_msg, datetime.now().isoformat(), rec_id),
            )
            conn.commit()
        else:
            final_word_count = len(blog_post.split())
            conn.execute(
                "UPDATE blog_posts SET status = 'completed', blog_post = ?, word_count = ?, error_message = NULL, updated_at = ? WHERE id = ?",
                (blog_post, final_word_count, datetime.now().isoformat(), rec_id),
            )
            conn.commit()
    finally:
        conn.close()

    progress.finish(rec_id, success)

    word_info = f" ({len(blog_post.split())} words)" if blog_post else ""
    log.log(
        logging.WARNING if not success else logging.INFO,
        "%s  %s%s",
        progress.summary(),
        title[:60],
        word_info,
    )


def _run_sequential(cfg: dict, records: list, total: int) -> None:
    """Process records one at a time with rate limiting."""
    progress = ProgressTracker(total)
    for idx, rec in enumerate(records, start=1):
        if _shutdown_requested:
            log.warning("Shutdown requested. Stopping after %d/%d.", idx - 1, total)
            break
        _worker(cfg, rec, progress, idx, total)
        if _shutdown_requested:
            break
        if idx < total:
            log.debug("Rate‑limit sleep: %ds", cfg["request_delay"])
            time.sleep(cfg["request_delay"])


def _run_parallel(cfg: dict, records: list, total: int, concurrency: int) -> None:
    """Process records in parallel using a work‑stealing thread pool."""
    workers = min(concurrency, total)
    progress = ProgressTracker(total)

    log.info("Dispatching %d records across %d parallel workers", total, workers)

    with ThreadPoolExecutor(max_workers=workers) as pool:
        futmap = {}
        for idx, rec in enumerate(records, start=1):
            if _shutdown_requested:
                break
            f = pool.submit(_worker, cfg, rec, progress, idx, total)
            futmap[f] = idx

        for f in as_completed(futmap):
            idx = futmap[f]
            try:
                f.result()
            except Exception as e:
                rec = records[idx - 1]
                progress.finish(rec["id"], success=False)
                log.error("%s  Worker %d exception: %s", progress.summary(), idx, e)

    log.info(progress.summary())


def process_records(
    cfg: dict, limit: Optional[int] = None, force: bool = False
) -> None:
    """Fetch pending records, generate blog posts, update database."""
    global _shutdown_requested
    signal.signal(signal.SIGINT, _signal_handler)

    if force:
        conn = get_db_connection(cfg["db_file"])
        conn.execute(
            "UPDATE blog_posts SET status = 'pending', blog_post = NULL, retries = 0, word_count = 0, error_message = NULL, updated_at = CURRENT_TIMESTAMP"
        )
        conn.commit()
        conn.close()
        log.info("All records reset to pending.")

    records = get_records(
        cfg["db_file"],
        status_filter="pending",
        limit=limit,
        max_retries=cfg["max_retries"],
    )

    if not records:
        log.info("No pending records to process.")
        return

    total = len(records)
    concurrency = cfg.get("concurrency", 1)

    if concurrency <= 1:
        log.info("Processing %d records sequentially...", total)
        _run_sequential(cfg, records, total)
    else:
        _run_parallel(cfg, records, total, concurrency)

    log.info("Processing finished.")


# ============================================================================
# COMMAND-LINE INTERFACE
# ============================================================================

def parse_args() -> Namespace:
    parser = ArgumentParser(
        description="Generate blog posts from CSV questions using AI with database and resume support.",
        formatter_class=RawDescriptionHelpFormatter,
        epilog=(
            "Examples:\n"
            "  %(prog)s data.csv                          Import CSV and process\n"
            "  %(prog)s data.csv --limit 5                 Process only 5 records\n"
            "  %(prog)s --stats                            Show database statistics\n"
            "  %(prog)s --list completed                   List completed records\n"
            "  %(prog)s --show 1                           Show record ID 1\n"
            "  %(prog)s --export                           Export completed posts to markdown\n"
            "  %(prog)s --test                             Test AI endpoint connection\n"
            "  %(prog)s --dry-run data.csv                 Preview what would be imported/processed\n"
            "  %(prog)s --config config.json data.csv      Use config file\n"
            "  %(prog)s --retry-failed                     Reset failed records to pending\n"
            "  %(prog)s --delete completed                 Delete all completed records\n"
            "  %(prog)s --verbose                          Enable debug logging\n"
            "  %(prog)s -P 5 data.csv                      Process 5 records in parallel\n"
            "  %(prog)s --debug-ai data.csv                Show full AI prompts and responses\n"
        ),
    )
    parser.add_argument(
        "csv", nargs="?", help="CSV file to import (Title, Body columns)."
    )
    parser.add_argument(
        "--db", default=None, help="SQLite database file (default: blog_posts.db)"
    )
    parser.add_argument("--config", help="JSON configuration file path")
    parser.add_argument(
        "--limit", type=int, help="Maximum number of records to process"
    )
    parser.add_argument(
        "--force",
        action="store_true",
        help="Reset all records to pending and reprocess",
    )
    parser.add_argument("--stats", action="store_true", help="Show database statistics")
    parser.add_argument(
        "--list",
        nargs="?",
        const="pending",
        help="List records by status (pending/completed/failed/all)",
    )
    parser.add_argument("--show", type=int, help="Show a specific record by ID")
    parser.add_argument(
        "--export", action="store_true", help="Export completed posts to markdown files"
    )
    parser.add_argument("--export-dir", help="Directory for exported markdown files")
    parser.add_argument(
        "--test", action="store_true", help="Test AI endpoint connection"
    )
    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Show what would be processed without making API calls",
    )
    parser.add_argument(
        "--retry-failed",
        action="store_true",
        help="Reset failed records to pending for retry",
    )
    parser.add_argument(
        "--delete",
        nargs="?",
        const="all",
        help="Delete records by status (all/pending/completed/failed)",
    )
    parser.add_argument("--log-file", help="Path to log file")
    parser.add_argument(
        "--verbose", action="store_true", help="Enable debug‑level logging"
    )
    parser.add_argument(
        "-P",
        "--parallel",
        type=int,
        default=None,
        dest="concurrency",
        help="Number of records to process in parallel (default: 1, sequential)",
    )
    parser.add_argument(
        "--debug-ai",
        action="store_true",
        help="Log full AI request/response payloads (debug level must be active)",
    )
    return parser.parse_args()


def cmd_list(db_path: str, status: str) -> None:
    """List records by status."""
    status_filter = status if status != "all" else None
    records = get_records(db_path, status_filter=status_filter)
    if not records:
        log.info("No records found with status '%s'.", status)
        return

    log.info("Records (status=%s):", status)
    log.info("  %-4s  %-10s  %-5s  %s", "ID", "Status", "Words", "Title")
    log.info("  %s", "-" * 80)
    for rec in records:
        wc = rec.get("word_count", 0) or (
            len(rec.get("blog_post", "").split()) if rec.get("blog_post") else 0
        )
        log.info(
            "  %-4d  %-10s  %-5d  %s", rec["id"], rec["status"], wc, rec["title"][:70]
        )


def cmd_show(db_path: str, record_id: int) -> None:
    """Show a specific record's details."""
    conn = get_db_connection(db_path)
    cursor = conn.execute("SELECT * FROM blog_posts WHERE id = ?", (record_id,))
    row = cursor.fetchone()
    conn.close()

    if not row:
        log.error("Record %d not found.", record_id)
        return

    rec = dict(row)
    log.info("ID: %d", rec["id"])
    log.info("Title: %s", rec["title"])
    log.info("Status: %s", rec["status"])
    log.info("Retries: %d", rec["retries"])
    log.info("Word count: %d", rec.get("word_count", 0))
    log.info("Created: %s", rec["created_at"])
    log.info("Updated: %s", rec["updated_at"])
    if rec.get("error_message"):
        log.info("Error: %s", rec["error_message"])
    log.info("")
    log.info("--- Original Question Body (%d chars) ---", len(rec["body"]))
    log.info(rec["body"])
    if rec.get("blog_post"):
        log.info("")
        log.info("--- Generated Blog Post (%d chars) ---", len(rec["blog_post"]))
        log.info(rec["blog_post"])


def main() -> None:
    args = parse_args()

    # Load config first (to get defaults for db/log)
    cfg = load_config(args.config)

    # Apply CLI overrides to config
    if args.db:
        cfg["db_file"] = args.db
    if args.export_dir:
        cfg["export_dir"] = args.export_dir
    if args.log_file:
        cfg["log_file"] = args.log_file
    if args.concurrency is not None:
        cfg["concurrency"] = args.concurrency
    if args.debug_ai:
        cfg["debug_ai"] = True

    # Setup logging (debug level if verbose or debug_ai is on)
    setup_logging(
        verbose=args.verbose or cfg.get("debug_ai"),
        log_file=cfg.get("log_file")
    )

    DB_FILE = cfg["db_file"]

    # Initialize database
    init_db(DB_FILE)

    # --- Dispatch commands ---

    if args.test:
        test_connection(cfg)
        return

    if args.stats:
        show_stats(DB_FILE)
        return

    if args.list is not None:
        cmd_list(DB_FILE, args.list)
        return

    if args.show is not None:
        cmd_show(DB_FILE, args.show)
        return

    if args.export:
        exported = export_posts(DB_FILE, cfg["export_dir"])
        log.info("Exported %d posts to %s/", exported, cfg["export_dir"])
        return

    if args.delete is not None:
        status = None if args.delete == "all" else args.delete
        deleted = delete_records(DB_FILE, status)
        log.info(
            "Deleted %d records%s.",
            deleted,
            f" (status={args.delete})" if status else "",
        )
        return

    if args.retry_failed:
        conn = get_db_connection(DB_FILE)
        conn.execute(
            "UPDATE blog_posts SET status = 'pending', retries = 0, error_message = NULL WHERE status = 'failed'"
        )
        conn.commit()
        affected = conn.total_changes
        conn.close()
        log.info("Reset %d failed records to pending.", affected)
        return

    if args.dry_run:
        log.info("DRY RUN mode – no API calls will be made")
        if args.csv:
            try:
                with open(args.csv, "r", newline="", encoding="utf-8") as f:
                    reader = csv.DictReader(f)
                    rows = list(reader)
                log.info("CSV: %s", args.csv)
                log.info("  Rows: %d", len(rows))
                log.info("  Columns: %s", ", ".join(reader.fieldnames or []))
                if not {"Title", "Body"}.issubset(reader.fieldnames or []):
                    log.error("CSV must have 'Title' and 'Body' columns.")
                    return
            except Exception as e:
                log.error("Error reading CSV: %s", e)
                return

        if args.force:
            log.info("Would reset all records to pending (--force)")
        concurrency = cfg.get("concurrency", 1)
        log.info("Concurrency: %d", concurrency)
        if concurrency > 1:
            log.info("No rate‑limit delay between records in parallel mode")
        else:
            pending = get_records(
                DB_FILE, status_filter="pending", max_retries=cfg["max_retries"]
            )
            log.info("Pending records in DB: %d", len(pending))
            if args.limit:
                log.info("Limit: %d records", args.limit)
            for rec in pending[: args.limit or len(pending)]:
                log.info("  Would process ID %d: %s", rec["id"], rec["title"][:80])
        return

    # Import CSV (if provided)
    if args.csv:
        log.info("Importing %s into database...", args.csv)
        try:
            inserted = import_csv_to_db(args.csv, DB_FILE)
            log.info("Inserted %d new records.", inserted)
        except Exception as e:
            log.error("Import failed: %s", e)
            return

    # Process pending records
    process_records(cfg, limit=args.limit, force=args.force)

    # Show final stats
    show_stats(DB_FILE)


if __name__ == "__main__":
    main()
