#!/usr/bin/env python3
"""
Generate blog posts from CSV questions using AI with database, resume support,
fallback, and a flicker-free live Rich TUI showing streaming responses,
per-worker progress, TTFT, and Ollama-reported tokens/sec.

Features:
* Flicker-free Rich TUI with multiple active jobs.
* Ollama native and OpenAI-compatible API support.
* Streaming generation and per-request metrics.
* SQLite persistence and resume support.
* CSV import with duplicate protection.
* Application-level retries and cooperative cancellation.
* Markdown and JSON exports.
* Configurable editorial prompts.
* Expansion requests use the previous draft instead of starting again.
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

from argparse import ArgumentParser
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime
from pathlib import Path
from typing import Callable, Optional

import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

from rich.console import Console, Group
from rich.layout import Layout
from rich.live import Live
from rich.panel import Panel
from rich.progress import (
    BarColumn,
    Progress,
    TextColumn,
    TimeElapsedColumn,
    TimeRemainingColumn,
)
from rich.table import Table
from rich.text import Text
from rich import box


# ============================================================================
# LOGGING
# ============================================================================

class ColorFormatter(logging.Formatter):
    COLORS = {
        logging.DEBUG: "\033[36m",
        logging.INFO: "\033[32m",
        logging.WARNING: "\033[33m",
        logging.ERROR: "\033[31m",
        logging.CRITICAL: "\033[41m",
    }

    RESET = "\033[0m"

    def format(self, record):
        color = self.COLORS.get(record.levelno, self.RESET)
        record.msg = f"{color}{record.msg}{self.RESET}"
        return super().format(record)


def setup_logging(verbose=False, log_file=None):
    level = logging.DEBUG if verbose else logging.INFO

    root = logging.getLogger()
    root.setLevel(logging.DEBUG)
    root.handlers.clear()

    console = logging.StreamHandler(sys.stdout)
    console.setLevel(level)
    console.setFormatter(
        ColorFormatter(
            "%(asctime)s %(levelname)-8s %(message)s",
            datefmt="%H:%M:%S",
        )
    )

    root.addHandler(console)

    if log_file:
        fh = logging.FileHandler(log_file, encoding="utf-8")
        fh.setLevel(logging.DEBUG)
        fh.setFormatter(
            logging.Formatter(
                "%(asctime)s %(levelname)-8s %(name)s %(message)s"
            )
        )
        root.addHandler(fh)
        logging.info("Logging to %s", log_file)


log = logging.getLogger("bloggen")


# ============================================================================
# CONFIGURATION
# ============================================================================

DEFAULT_CONFIG = {
    "api_endpoint": "http://localhost:11434/api/chat",
    "model": "hf.co/unsloth/Qwen3.5-9B-GGUF:UD-Q4_K_XL",
    "api_key": "",
    "request_timeout": 1200,
    "max_retries": 3,
    "backoff_factor": 2,
    "min_words": 350,
    "concurrency": 1,

    "system_prompt": (
        "You are an experienced software engineer and technical writer. "
        "You write useful, original technical articles for developers. "
        "Your writing should sound natural, confident, knowledgeable, "
        "and human rather than formulaic or machine-generated. "
        "\n\n"
        "EDITORIAL STYLE:\n"
        "- Write as an experienced engineer explaining something to another developer.\n"
        "- Prefer clear, direct language over academic or corporate language.\n"
        "- Vary sentence length and paragraph structure naturally.\n"
        "- Use specific technical details instead of vague statements.\n"
        "- Explain why a solution works, not just what to do.\n"
        "- Include practical trade-offs, limitations, and common mistakes where relevant.\n"
        "- Avoid unnecessary repetition and filler.\n"
        "- Avoid generic opening sentences and predictable transitions.\n"
        "- Do not repeatedly summarise what has already been explained.\n"
        "- Do not invent personal experiences, projects, benchmarks, or facts.\n"
        "\n"
        "ARTICLE STRUCTURE:\n"
        "- Use headings when they genuinely improve readability.\n"
        "- Choose headings that describe the actual subject of each section.\n"
        "- Do not force a fixed article structure.\n"
        "- Do not use headings named Introduction, Conclusion, Final Thoughts, "
        "Key Takeaways, or Summary.\n"
        "- Do not create a dedicated conclusion section.\n"
        "- Do not add a generic closing paragraph that repeats the article.\n"
        "- Finish naturally after the final useful explanation or recommendation.\n"
        "\n"
        "TECHNICAL QUALITY:\n"
        "- Keep code examples correct, relevant, and consistent with the explanation.\n"
        "- Explain important configuration and implementation details.\n"
        "- Do not fabricate APIs, package methods, commands, or configuration options.\n"
        "- Mention version-specific behaviour when it materially affects the answer.\n"
        "\n"
        "LINKING:\n"
        "- Reference https://laravelcompany.com only when it is genuinely relevant.\n"
        "- Integrate links naturally into the surrounding explanation.\n"
        "- Never force a backlink into an unrelated paragraph.\n"
        "- Never repeat the same promotional link unnecessarily.\n"
        "\n"
        "Return the article in clean Markdown. "
        "Do not include commentary about the writing process."
    ),

    "user_prompt_template": (
        "Write an original, technically accurate blog article based on the "
        "following subject and source material.\n"
        "\n"
        "Title: {title}\n"
        "\n"
        "Source material:\n"
        "{body}\n"
        "\n"
        "WRITING REQUIREMENTS:\n"
        "1. Address the subject directly. Avoid generic introductions.\n"
        "2. Explain the technical details in a way developers can apply.\n"
        "3. Include relevant implementation examples, commands, or code where useful.\n"
        "4. Explain important decisions, trade-offs, and potential pitfalls.\n"
        "5. Use natural paragraphs and meaningful headings where appropriate.\n"
        "6. Keep the article focused on the actual subject.\n"
        "7. Do not pad the article with repetitive explanations.\n"
        "8. Do not invent technical details or claim that something was tested "
        "unless the source material establishes that.\n"
        "9. Include a LaravelCompany link only if it is relevant to the subject.\n"
        "\n"
        "IMPORTANT FORMATTING RULES:\n"
        "- Do not use an Introduction heading.\n"
        "- Do not use a Conclusion heading.\n"
        "- Do not use Final Thoughts, Key Takeaways, or Summary headings.\n"
        "- Do not create a separate conclusion section.\n"
        "- Do not finish with a generic summary of the article.\n"
        "- End naturally when the technical explanation is complete.\n"
        "\n"
        "Return only the finished Markdown article."
    ),

    "expand_prompt": (
        "Improve and expand the existing article rather than starting again.\n"
        "\n"
        "The previous draft did not reach the required length. "
        "Develop the existing content with useful technical depth.\n"
        "\n"
        "Expansion guidelines:\n"
        "- Preserve the strongest parts of the existing article.\n"
        "- Expand explanations that are currently too brief.\n"
        "- Add relevant implementation details and practical examples.\n"
        "- Explain important trade-offs and common mistakes.\n"
        "- Add technical context where it improves the reader's understanding.\n"
        "- Avoid repeating existing paragraphs in different words.\n"
        "- Do not add unrelated sections just to increase word count.\n"
        "- Keep the original subject and technical accuracy.\n"
        "- Do not introduce generic introductions or conclusions.\n"
        "- Do not add Introduction, Conclusion, Final Thoughts, Key Takeaways, "
        "or Summary headings.\n"
        "- Do not finish with a generic closing paragraph.\n"
        "\n"
        "Return the complete improved article in Markdown, not an explanation "
        "of the changes."
    ),

    "db_file": "blog_posts.db",
    "export_dir": "exported_posts",
    "export_format": "md",
    "log_file": None,
    "debug_ai": False,
    "stream": True,
    "think": False,
    "num_ctx": 4096,
    "temperature": 0.7,
    "max_tokens": 4096,
    "no_tui": False,
    "stream_to_file": None,
    "stream_lines": 180,
    "quiet": False,
    "tui_refresh_per_second": 10,
}

CONFIG_INT_KEYS = {
    "request_timeout",
    "max_tokens",
    "num_ctx",
    "stream_lines",
    "concurrency",
    "max_retries",
    "backoff_factor",
    "tui_refresh_per_second",
    "min_words",
}

CONFIG_BOOL_KEYS = {
    "debug_ai",
    "stream",
    "think",
    "no_tui",
    "quiet",
}

CONFIG_FLOAT_KEYS = {
    "request_delay",
}


def load_config(config_path=None):
    cfg = dict(DEFAULT_CONFIG)

    if config_path and os.path.exists(config_path):
        with open(config_path, "r", encoding="utf-8") as f:
            file_cfg = json.load(f)

        cfg.update(file_cfg)
        log.info("Loaded config from %s", config_path)

    env_map = {
        "AI_ENDPOINT": "api_endpoint",
        "AI_MODEL": "model",
        "AI_API_KEY": "api_key",
        "REQUEST_TIMEOUT": "request_timeout",
        "DB_FILE": "db_file",
        "EXPORT_DIR": "export_dir",
        "EXPORT_FORMAT": "export_format",
        "DEBUG_AI": "debug_ai",
        "STREAM": "stream",
        "MAX_TOKENS": "max_tokens",
        "NO_TUI": "no_tui",
        "STREAM_LINES": "stream_lines",
        "NUM_CTX": "num_ctx",
        "THINK": "think",
    }

    for env_key, cfg_key in env_map.items():
        val = os.environ.get(env_key)

        if val is not None:
            if cfg_key in CONFIG_INT_KEYS:
                val = int(val)
            elif cfg_key in CONFIG_BOOL_KEYS:
                val = val.lower() in ("1", "true", "yes")

            cfg[cfg_key] = val

    validate_config(cfg)

    return cfg


def validate_config(cfg):
    errors = []

    if not cfg.get("api_endpoint"):
        errors.append("api_endpoint is required")

    if not cfg.get("model"):
        errors.append("model is required")

    for key in CONFIG_INT_KEYS:
        try:
            cfg[key] = int(cfg[key])
        except (TypeError, ValueError, KeyError):
            errors.append(f"{key} must be an integer, got {cfg.get(key)!r}")

    if not errors:
        if cfg.get("num_ctx", 4096) < 1:
            errors.append("num_ctx must be >= 1")

        if cfg.get("concurrency", 1) < 1:
            errors.append("concurrency must be >= 1")

        if cfg.get("min_words", 350) < 1:
            errors.append("min_words must be >= 1")

        if cfg.get("max_retries", 3) < 1:
            errors.append("max_retries must be >= 1")

    if cfg.get("export_format") not in ("md", "json"):
        errors.append("export_format must be 'md' or 'json'")

    if errors:
        for error in errors:
            log.error("Config error: %s", error)

        raise SystemExit(
            "Invalid configuration. Fix the errors above and try again."
        )


def write_starter_config(path):
    starter = dict(DEFAULT_CONFIG)

    with open(path, "w", encoding="utf-8") as f:
        json.dump(starter, f, indent=2, ensure_ascii=False)

    log.info("Wrote starter config to %s", path)


# ============================================================================
# DATABASE
# ============================================================================

def get_db_connection(db_path):
    conn = sqlite3.connect(db_path, timeout=10)
    conn.row_factory = sqlite3.Row

    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA synchronous=NORMAL")

    return conn


def init_db(db_path):
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

    cursor = conn.execute("PRAGMA table_info(blog_posts)")
    existing = {row["name"] for row in cursor.fetchall()}

    for col, col_def in [
        ("word_count", "INTEGER DEFAULT 0"),
        ("error_message", "TEXT"),
    ]:
        if col not in existing:
            conn.execute(
                f"ALTER TABLE blog_posts ADD COLUMN {col} {col_def}"
            )

    conn.execute("""
        CREATE INDEX IF NOT EXISTS idx_blog_posts_status
        ON blog_posts(status)
    """)

    conn.commit()
    conn.close()


def import_csv_to_db(csv_file, db_path):
    conn = get_db_connection(db_path)
    cursor = conn.cursor()

    try:
        existing_titles = {
            row[0]
            for row in cursor.execute("SELECT title FROM blog_posts")
        }

        to_insert = []

        with open(csv_file, "r", newline="", encoding="utf-8-sig") as f:
            reader = csv.DictReader(f)

            if not {"Title", "Body"}.issubset(reader.fieldnames or []):
                raise ValueError(
                    "CSV must have 'Title' and 'Body' columns."
                )

            for row in reader:
                title = (row.get("Title") or "").strip()
                body = (row.get("Body") or "").strip()

                if not title or not body:
                    continue

                if title in existing_titles:
                    continue

                existing_titles.add(title)

                to_insert.append((title, body, "pending"))

        if to_insert:
            cursor.executemany(
                """
                INSERT INTO blog_posts (title, body, status)
                VALUES (?, ?, ?)
                """,
                to_insert,
            )

            conn.commit()

        return len(to_insert)

    finally:
        conn.close()


def get_records(
    db_path,
    status_filter=None,
    limit=None,
    max_retries=3,
):
    conn = get_db_connection(db_path)

    conditions = []
    params = []

    if status_filter == "pending":
        conditions.append(
            "status IN ('pending', 'failed') AND retries < ?"
        )
        params.append(max_retries)

    elif status_filter:
        conditions.append("status = ?")
        params.append(status_filter)

    where = (
        f"WHERE {' AND '.join(conditions)}"
        if conditions
        else ""
    )

    query = (
        "SELECT id, title, body, status, retries, word_count, "
        "error_message, blog_post, updated_at "
        f"FROM blog_posts {where} ORDER BY id"
    )

    if limit is not None:
        query += " LIMIT ?"
        params.append(limit)

    try:
        rows = conn.execute(query, params).fetchall()
        return [dict(row) for row in rows]

    finally:
        conn.close()


def update_record_status(
    db_path,
    record_id,
    status,
    blog_post=None,
    retries=None,
    word_count=None,
    error_message=None,
):
    conn = get_db_connection(db_path)

    try:
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

        sql = (
            f"UPDATE blog_posts SET {', '.join(updates)} "
            "WHERE id = ?"
        )

        conn.execute(sql, params)
        conn.commit()

    finally:
        conn.close()


def delete_records(db_path, status=None):
    conn = get_db_connection(db_path)

    try:
        if status:
            conn.execute(
                "DELETE FROM blog_posts WHERE status = ?",
                (status,),
            )
        else:
            conn.execute("DELETE FROM blog_posts")

        deleted = conn.total_changes
        conn.commit()

        return deleted

    finally:
        conn.close()


# ============================================================================
# HTTP SESSION
# ============================================================================

def build_session(cfg):
    session = requests.Session()

    retry = Retry(
        total=0,
        connect=0,
        read=0,
        backoff_factor=0,
    )

    adapter = HTTPAdapter(
        max_retries=retry,
        pool_connections=max(10, cfg.get("concurrency", 1)),
        pool_maxsize=max(10, cfg.get("concurrency", 1)),
    )

    session.mount("http://", adapter)
    session.mount("https://", adapter)

    return session


# ============================================================================
# AI CALL
# ============================================================================

def mask_api_key(key):
    if not key:
        return "<none>"

    return key[:4] + "***" + key[-4:] if len(key) > 8 else "***"


def _log_ai_request(payload, cfg):
    if not cfg.get("debug_ai"):
        return

    safe = {**payload}

    if cfg.get("api_key"):
        safe["headers"] = {
            "Authorization": f"Bearer {mask_api_key(cfg['api_key'])}"
        }

    log.debug(
        "AI REQUEST: %s",
        json.dumps(safe, indent=2, ensure_ascii=False),
    )


def _merge_ollama_stats(stats, chunk):
    for key in (
        "total_duration",
        "load_duration",
        "prompt_eval_count",
        "prompt_eval_duration",
        "eval_count",
        "eval_duration",
    ):
        if key in chunk and chunk[key] is not None:
            stats[key] = chunk[key]


def _stream_response(
    response,
    debug_ai,
    on_chunk=None,
    cancel_event: Optional[threading.Event] = None,
    metrics=None,
    on_metrics: Optional[Callable[[dict], None]] = None,
):
    """Parse Ollama NDJSON and OpenAI-compatible SSE streams."""

    metrics = metrics if metrics is not None else {}

    def emit(content):
        if not content:
            return None

        if metrics.get("first_token_at") is None:
            metrics["first_token_at"] = time.perf_counter()

            started = metrics.get("request_started_at")

            if started is not None:
                metrics["ttft_ms"] = (
                    metrics["first_token_at"] - started
                ) * 1000

                if on_metrics:
                    on_metrics(dict(metrics))

        if on_chunk:
            on_chunk(content)

        return content

    try:
        content_type = response.headers.get(
            "content-type",
            "",
        ).lower()

        if (
            "application/json" in content_type
            and response.headers.get("transfer-encoding") != "chunked"
        ):
            data = response.json()

            _merge_ollama_stats(metrics, data)

            if "choices" in data:
                choices = data.get("choices") or []
                content = (
                    choices[0].get("message", {}).get("content", "")
                    if choices
                    else ""
                )
            else:
                content = (
                    data.get("message", {}).get("content", "") or ""
                )

            emitted = emit(content)

            if emitted:
                yield emitted

            return

        for raw_line in response.iter_lines(decode_unicode=True):
            if cancel_event is not None and cancel_event.is_set():
                log.debug("Streaming cancelled by user request.")
                response.close()
                return

            if not raw_line:
                continue

            line = (
                raw_line.decode("utf-8", errors="replace")
                if isinstance(raw_line, bytes)
                else raw_line
            )

            line = line.strip()

            if not line:
                continue

            if debug_ai:
                log.debug("RAW LINE: %s", line[:300])

            # OpenAI-compatible SSE.
            if line.startswith("data:"):
                data = line[5:].strip()

                if data == "[DONE]":
                    break

                try:
                    chunk = json.loads(data)
                except json.JSONDecodeError:
                    continue

                usage = chunk.get("usage")

                if isinstance(usage, dict):
                    metrics["prompt_eval_count"] = usage.get(
                        "prompt_tokens"
                    )
                    metrics["eval_count"] = usage.get(
                        "completion_tokens"
                    )

                choices = chunk.get("choices") or []
                delta = (
                    choices[0].get("delta", {})
                    if choices
                    else {}
                )

                content = delta.get("content", "") or ""
                emitted = emit(content)

                if emitted:
                    yield emitted

                continue

            # Ollama native NDJSON.
            try:
                chunk = json.loads(line)
            except json.JSONDecodeError:
                continue

            if not isinstance(chunk, dict):
                continue

            _merge_ollama_stats(metrics, chunk)

            message = chunk.get("message") or {}
            content = message.get("content", "") or ""

            emitted = emit(content)

            if emitted:
                yield emitted

    except Exception as exc:
        log.warning("Streaming interrupted: %s", exc)

        if debug_ai:
            log.exception("Streaming error details")


def _sleep_cancellable(seconds, cancel_event):
    if cancel_event is None:
        time.sleep(seconds)
        return

    end = time.time() + seconds

    while time.time() < end:
        if cancel_event.is_set():
            return

        time.sleep(min(0.1, end - time.time()))


def call_ai(
    prompt,
    cfg,
    session,
    expand=False,
    on_chunk: Optional[Callable[[str], None]] = None,
    cancel_event: Optional[threading.Event] = None,
    on_metrics: Optional[Callable[[dict], None]] = None,
):
    """Call Ollama native /api/chat or an OpenAI-compatible endpoint."""

    headers = {
        "Content-Type": "application/json",
    }

    if cfg.get("api_key"):
        headers["Authorization"] = f"Bearer {cfg['api_key']}"

    system_msg = cfg["system_prompt"]

    if expand:
        system_msg += (
            "\n\nYou are revising an existing article. Preserve its "
            "accuracy and useful material. Improve depth without adding "
            "repetition, generic introductions, or forced conclusions."
        )

    messages = [
        {
            "role": "system",
            "content": system_msg,
        },
        {
            "role": "user",
            "content": prompt,
        },
    ]

    endpoint = cfg["api_endpoint"].lower()

    use_ollama = (
        "/api/chat" in endpoint
        and "/v1/" not in endpoint
    )

    stream_enabled = bool(
        cfg.get("stream", True)
        and on_chunk is not None
    )

    if use_ollama:
        payload = {
            "model": cfg["model"],
            "messages": messages,
            "stream": stream_enabled,
            "think": bool(cfg.get("think", False)),
            "options": {
                "temperature": cfg.get("temperature", 0.7),
                "num_ctx": cfg.get("num_ctx", 4096),
                "num_predict": cfg.get("max_tokens", 4096),
            },
        }

    else:
        payload = {
            "model": cfg["model"],
            "messages": messages,
            "temperature": cfg.get("temperature", 0.7),
            "max_tokens": cfg.get("max_tokens", 4096),
            "stream": stream_enabled,
            "stop": None,
        }

    max_retries = cfg["max_retries"]
    backoff = cfg["backoff_factor"]
    timeout = cfg["request_timeout"]

    for attempt in range(1, max_retries + 1):
        if cancel_event is not None and cancel_event.is_set():
            return None

        metrics = {
            "request_started_at": time.perf_counter(),
            "ttft_ms": None,
        }

        log.debug(
            "API attempt %d/%d",
            attempt,
            max_retries,
        )

        resp = None

        try:
            _log_ai_request(
                {
                    **payload,
                    "headers": headers,
                },
                cfg,
            )

            resp = session.post(
                cfg["api_endpoint"],
                headers=headers,
                json=payload,
                timeout=timeout,
                stream=stream_enabled,
            )

            resp.raise_for_status()

            if stream_enabled:
                content_parts = []

                for chunk in _stream_response(
                    resp,
                    cfg.get("debug_ai", False),
                    on_chunk=on_chunk,
                    cancel_event=cancel_event,
                    metrics=metrics,
                    on_metrics=on_metrics,
                ):
                    content_parts.append(chunk)

                if (
                    cancel_event is not None
                    and cancel_event.is_set()
                ):
                    return None

                full_content = "".join(content_parts).strip()

            else:
                data = resp.json()

                if "error" in data:
                    raise ValueError(
                        f"API error: {data['error']}"
                    )

                if use_ollama:
                    full_content = (
                        data.get("message", {})
                        .get("content", "")
                        .strip()
                    )

                    _merge_ollama_stats(metrics, data)

                else:
                    choices = data.get("choices", [])

                    if not choices:
                        raise ValueError("Empty choices")

                    full_content = (
                        choices[0]
                        .get("message", {})
                        .get("content", "")
                        .strip()
                    )

                    usage = data.get("usage") or {}

                    metrics["prompt_eval_count"] = usage.get(
                        "prompt_tokens"
                    )

                    metrics["eval_count"] = usage.get(
                        "completion_tokens"
                    )

            metrics["request_duration_ms"] = (
                time.perf_counter()
                - metrics["request_started_at"]
            ) * 1000

            if (
                metrics.get("ttft_ms") is None
                and full_content
            ):
                metrics["ttft_ms"] = metrics[
                    "request_duration_ms"
                ]

            eval_count = metrics.get("eval_count")
            eval_duration = metrics.get("eval_duration")

            if eval_count is not None and eval_duration:
                metrics["tokens_per_second"] = (
                    eval_count
                    / (eval_duration / 1_000_000_000)
                )

            elif (
                eval_count is not None
                and metrics.get("request_duration_ms", 0) > 0
            ):
                metrics["tokens_per_second"] = (
                    eval_count
                    / (
                        metrics["request_duration_ms"]
                        / 1000
                    )
                )

            if on_metrics:
                on_metrics(metrics)

            if not full_content:
                raise ValueError("Empty response from API")

            log.debug(
                "Generated response: %s tokens, TTFT=%s, generation=%s tok/s",
                eval_count if eval_count is not None else "unknown",
                (
                    f"{metrics['ttft_ms']:.0f}ms"
                    if metrics.get("ttft_ms") is not None
                    else "unknown"
                ),
                (
                    f"{metrics['tokens_per_second']:.2f}"
                    if metrics.get("tokens_per_second") is not None
                    else "unknown"
                ),
            )

            if cfg.get("debug_ai"):
                log.debug(
                    "FULL RESPONSE (truncated): %s...",
                    full_content[:1500],
                )

            return full_content

        except requests.exceptions.Timeout:
            wait = backoff ** attempt

            log.warning(
                "Timeout (attempt %d). Retry in %ds",
                attempt,
                wait,
            )

            _sleep_cancellable(wait, cancel_event)

        except requests.exceptions.ConnectionError as exc:
            wait = backoff ** attempt

            log.error(
                "Connection error (attempt %d): %s. Retry in %ds",
                attempt,
                exc,
                wait,
            )

            _sleep_cancellable(wait, cancel_event)

        except requests.exceptions.HTTPError as exc:
            status_code = (
                exc.response.status_code
                if exc.response
                else "?"
            )

            log.error(
                "HTTP %s (attempt %d): %s",
                status_code,
                attempt,
                exc,
            )

            if exc.response is not None and exc.response.text:
                log.debug(
                    "Response body: %s",
                    exc.response.text[:800],
                )

            if attempt == max_retries:
                return None

            _sleep_cancellable(
                backoff ** attempt,
                cancel_event,
            )

        except (
            json.JSONDecodeError,
            ValueError,
            KeyError,
        ) as exc:
            log.error(
                "Invalid response (attempt %d): %s",
                attempt,
                exc,
            )

            if attempt == max_retries:
                return None

            _sleep_cancellable(
                backoff ** attempt,
                cancel_event,
            )

        except Exception as exc:
            log.warning(
                "API error (attempt %d): %s",
                attempt,
                exc,
            )

            if log.isEnabledFor(logging.DEBUG):
                log.exception("Exception details")

            if attempt == max_retries:
                return None

            _sleep_cancellable(
                backoff ** attempt,
                cancel_event,
            )

        finally:
            if resp is not None:
                resp.close()

    return None


# ============================================================================
# BLOG GENERATION
# ============================================================================

def build_expansion_prompt(
    original_prompt,
    previous_draft,
    min_words,
    expansion_instructions,
):
    """
    Build an expansion request using the previous generated draft.

    The previous draft is deliberately included in the user message.
    This avoids asking the model to regenerate the article from scratch.
    """

    return (
        f"{expansion_instructions.strip()}\n\n"
        f"REQUIRED MINIMUM LENGTH: {min_words} words.\n\n"
        "ORIGINAL ARTICLE REQUEST:\n"
        f"{original_prompt}\n\n"
        "EXISTING ARTICLE DRAFT:\n"
        "The following is your previous draft. Improve and expand it. "
        "Preserve useful content and do not discard relevant technical details.\n\n"
        f"{previous_draft}\n\n"
        "FINAL INSTRUCTIONS:\n"
        f"- The complete article must contain at least {min_words} words.\n"
        "- Return the complete revised article, not only the new paragraphs.\n"
        "- Do not explain what you changed.\n"
        "- Do not add an Introduction or Conclusion heading.\n"
        "- Do not add Final Thoughts, Key Takeaways, or Summary headings.\n"
        "- Do not append a generic conclusion paragraph.\n"
        "- End naturally after the final useful technical explanation.\n"
        "- Return only the complete Markdown article."
    )


def generate_blog_post(
    title,
    body,
    cfg,
    session,
    on_chunk: Optional[Callable[[str], None]] = None,
    cancel_event: Optional[threading.Event] = None,
    on_metrics: Optional[Callable[[dict], None]] = None,
):
    """
    Generate an article and expand the previous draft when it is too short.

    Returns the best available content, even if it remains below min_words.
    """

    min_words = int(cfg.get("min_words", 350))

    user_prompt = cfg["user_prompt_template"].format(
        title=title,
        body=body,
    )

    user_prompt += (
        f"\n\nThe finished article must contain at least "
        f"{min_words} words. Do not pad the article with repetition."
    )

    current_prompt = user_prompt
    expand = False

    best_content = None
    best_word_count = 0

    for attempt in range(1, cfg["max_retries"] + 1):
        if (
            cancel_event is not None
            and cancel_event.is_set()
        ):
            break

        log.info(
            "Attempt %d/%d for '%s'...",
            attempt,
            cfg["max_retries"],
            title[:50],
        )

        if cfg.get("debug_ai"):
            log.debug(
                "PROMPT (attempt %d): %s",
                attempt,
                current_prompt[:1000],
            )

        content = call_ai(
            current_prompt,
            cfg,
            session,
            expand=expand,
            on_chunk=on_chunk,
            cancel_event=cancel_event,
            on_metrics=on_metrics,
        )

        if content is None:
            continue

        wc = len(content.split())

        log.info("Generated %d words", wc)

        if wc > best_word_count:
            best_content = content
            best_word_count = wc

        if wc >= min_words:
            log.info(
                "Article meets minimum length: %d/%d words.",
                wc,
                min_words,
            )
            return content

        if attempt == cfg["max_retries"]:
            break

        log.info(
            "Too short (%d < %d). Expanding existing draft...",
            wc,
            min_words,
        )

        # IMPORTANT:
        # Pass the best existing draft to the model instead of sending
        # the original article request again.
        current_prompt = build_expansion_prompt(
            original_prompt=user_prompt,
            previous_draft=best_content,
            min_words=min_words,
            expansion_instructions=cfg["expand_prompt"],
        )

        expand = True

    if best_content:
        log.warning(
            "Best effort: %d words (minimum %d). Saving partial.",
            best_word_count,
            min_words,
        )

        return best_content

    return None


# ============================================================================
# RICH TUI
# ============================================================================

SPARK_CHARS = " ▁▂▃▄▅▆▇█"


def _sparkline(values, width=24):
    if not values:
        return " " * width

    values = values[-width:]

    lo = min(values)
    hi = max(values)
    span = (hi - lo) or 1.0

    chars = []

    for value in values:
        idx = int(
            (value - lo)
            / span
            * (len(SPARK_CHARS) - 1)
        )

        chars.append(SPARK_CHARS[idx])

    return "".join(chars).rjust(width)


class RichTUI:
    """
    Flicker-free TUI.

    The layout is created once. Only its existing sections are updated.
    Rich Live owns the refresh thread.
    """

    def __init__(
        self,
        total,
        stream_lines=180,
        refresh_per_second=10,
    ):
        self.total = total
        self.completed = 0
        self.failed = 0

        self.running = {}
        self.recent = []

        self.lock = threading.RLock()
        self.console = Console()

        self._stop = False
        self.overall_start = time.time()
        self.stream_lines = stream_lines

        self.current_record_id = None

        self.layout = self._build_layout()

        self.live = Live(
            self.layout,
            console=self.console,
            refresh_per_second=refresh_per_second,
            screen=True,
            transient=False,
            auto_refresh=True,
            vertical_overflow="crop",
        )

        self.refresh()

    def _build_layout(self):
        layout = Layout()

        layout.split(
            Layout(name="header", size=3),
            Layout(name="progress", size=8),
            Layout(name="main"),
        )

        layout["main"].split_row(
            Layout(name="jobs", ratio=1),
            Layout(name="content", ratio=2),
        )

        return layout

    def start(self):
        self.live.start()

    def stop(self):
        self._stop = True
        self.live.stop()

    def start_record(self, record_id, title):
        with self.lock:
            self.running[record_id] = {
                "title": title,
                "start_time": time.time(),
                "word_count": 0,
                "token_count": None,
                "tokens_per_second": None,
                "ttft_ms": None,
                "content": "",
                "status": "starting",
                "history": [],
            }

            if self.current_record_id is None:
                self.current_record_id = record_id

        self.refresh()

    def update_stream(self, record_id, chunk):
        with self.lock:
            rec = self.running.get(record_id)

            if not rec:
                return

            rec["content"] += chunk
            rec["word_count"] = len(rec["content"].split())
            rec["status"] = "streaming"

            if (
                self.current_record_id not in self.running
            ):
                self.current_record_id = record_id

        self.refresh()

    def set_content(self, record_id, content):
        with self.lock:
            rec = self.running.get(record_id)

            if not rec:
                return

            rec["content"] = content
            rec["word_count"] = len(content.split())

            rec["status"] = (
                "completed"
                if rec["word_count"] > 0
                else "failed"
            )

        self.refresh()

    def update_metrics(self, record_id, metrics):
        with self.lock:
            rec = self.running.get(record_id)

            if not rec:
                return

            if metrics.get("eval_count") is not None:
                rec["token_count"] = metrics["eval_count"]

            if metrics.get("tokens_per_second") is not None:
                speed = metrics["tokens_per_second"]

                rec["tokens_per_second"] = speed
                rec["history"].append(speed)

            if metrics.get("ttft_ms") is not None:
                rec["ttft_ms"] = metrics["ttft_ms"]

        self.refresh()

    def set_error(self, record_id, error_msg):
        with self.lock:
            rec = self.running.get(record_id)

            if not rec:
                return

            rec["status"] = f"error: {error_msg[:60]}"

        self.refresh()

    def finish_record(self, record_id, success):
        with self.lock:
            rec = self.running.pop(record_id, None)

            if rec is not None:
                snapshot = dict(rec)

                snapshot["status"] = (
                    "completed"
                    if success
                    else "failed"
                )

                self.recent.append(snapshot)
                self.recent = self.recent[-5:]

            if success:
                self.completed += 1
            else:
                self.failed += 1

            if self.current_record_id == record_id:
                self.current_record_id = next(
                    iter(self.running.keys()),
                    None,
                )

        self.refresh()

    def focus(self, record_id):
        with self.lock:
            if record_id in self.running:
                self.current_record_id = record_id

        self.refresh()

    def refresh(self):
        with self.lock:
            self._render_header()
            self._render_progress()
            self._render_jobs()
            self._render_content()

    def _render_header(self):
        header_text = Text(
            "Blog Post Generator • Live Streaming",
            style="bold blue",
            justify="center",
        )

        self.layout["header"].update(
            Panel(
                header_text,
                style="white",
                box=box.ROUNDED,
            )
        )

    def _render_progress(self):
        done = self.completed + self.failed

        pct = (
            done / self.total * 100
            if self.total
            else 0
        )

        elapsed = time.time() - self.overall_start
        speed = done / max(elapsed, 1) * 60

        eta = (
            (self.total - done) / max(speed / 60, 0.001)
            if done > 0
            else 0
        )

        prog_table = Table(
            show_header=False,
            box=None,
            padding=(0, 1),
            expand=True,
        )

        prog_table.add_column(justify="left", width=14)
        prog_table.add_column(justify="right")
        prog_table.add_column(justify="left", width=14)
        prog_table.add_column(justify="right")

        prog_table.add_row(
            "Total",
            str(self.total),
            "Active",
            str(len(self.running)),
        )

        prog_table.add_row(
            "Completed",
            f"[green]{self.completed}[/green]",
            "Failed",
            f"[red]{self.failed}[/red]",
        )

        prog_table.add_row(
            "Elapsed",
            f"{elapsed:.0f}s",
            "Speed",
            f"{speed:.2f} posts/min",
        )

        prog_table.add_row(
            "Progress",
            f"{pct:.1f}%",
            "ETA",
            f"{eta / 60:.1f} min" if eta > 60 else f"{eta:.0f}s",
        )

        bar = Progress(
            TextColumn("[progress.description]{task.description}"),
            BarColumn(),
            TextColumn("[progress.percentage]{task.percentage:>3.0f}%"),
            TimeElapsedColumn(),
            TimeRemainingColumn(),
            expand=True,
        )

        bar.add_task(
            "Overall",
            total=self.total,
            completed=done,
        )

        group = Group(prog_table, bar)

        self.layout["progress"].update(
            Panel(
                group,
                title="Progress",
                border_style="green",
                box=box.ROUNDED,
            )
        )

    def _render_jobs(self):
        table = Table(
            box=box.SIMPLE,
            expand=True,
            show_lines=False,
        )

        table.add_column("Job", ratio=3, overflow="ellipsis")
        table.add_column("Status", ratio=2)
        table.add_column("Words", ratio=1, justify="right")
        table.add_column("TTFT", ratio=1, justify="right")
        table.add_column("Tok/s", ratio=2)

        rows = list(self.running.items())

        rows.extend(
            (None, rec)
            for rec in reversed(self.recent[-5:])
        )

        if not rows:
            table.add_row(
                "[dim]No active generation[/dim]",
                "",
                "",
                "",
                "",
            )

        else:
            for rid, rec in rows:
                status = rec["status"]

                style = {
                    "streaming": "cyan",
                    "starting": "yellow",
                    "completed": "green",
                    "failed": "red",
                }.get(
                    status,
                    "red" if status.startswith("error") else "white",
                )

                marker = (
                    "➤ "
                    if (
                        rid is not None
                        and rid == self.current_record_id
                    )
                    else "  "
                )

                spark = _sparkline(
                    rec["history"],
                    width=12,
                )

                if rec.get("tokens_per_second") is not None:
                    speed = (
                        f"{rec['tokens_per_second']:.1f} tok/s "
                        f"{spark}"
                    )

                elif status in ("starting", "streaming"):
                    speed = "measuring…"

                else:
                    speed = "unavailable"

                table.add_row(
                    f"{marker}{rec['title'][:40]}",
                    f"[{style}]{status}[/{style}]",
                    str(rec["word_count"]),
                    (
                        f"{rec['ttft_ms']:.0f} ms"
                        if rec.get("ttft_ms") is not None
                        else "—"
                    ),
                    speed,
                )

        self.layout["jobs"].update(
            Panel(
                table,
                title="Active Jobs",
                border_style="cyan",
                box=box.ROUNDED,
            )
        )

    def _render_content(self):
        rid = self.current_record_id

        rec = (
            self.running.get(rid)
            if rid is not None
            else None
        )

        if not rec:
            content = "Waiting for the next job..."

        else:
            content = (
                rec["content"]
                or "Waiting for first tokens..."
            )

            if rec["status"].startswith("error"):
                content = (
                    f"Error: {rec['status']}\n\n"
                    f"{content}"
                )

        lines = content.splitlines() or [content]

        if len(lines) > self.stream_lines:
            content = (
                f"... [truncated to last {self.stream_lines} lines]\n\n"
                + "\n".join(lines[-self.stream_lines:])
            )

        title = (
            f"Live Output — {rec['title'][:50]}"
            if rec
            else "Live Output"
        )

        self.layout["content"].update(
            Panel(
                Text(content, style="white"),
                title=title,
                border_style="yellow",
                box=box.ROUNDED,
                padding=(1, 2),
            )
        )


# ============================================================================
# WORKER
# ============================================================================

_shutdown_event = threading.Event()


def _signal_handler(signum, frame):
    if _shutdown_event.is_set():
        log.warning("Forced exit.")
        sys.exit(1)

    _shutdown_event.set()

    log.warning(
        "Shutdown requested. Finishing current record(s)... "
        "(Ctrl+C again to force)"
    )


def _worker(
    cfg,
    record,
    tui,
    session,
    stream_file=None,
    quiet=False,
):
    rec_id = record["id"]
    title = record["title"]
    body = record["body"]
    retries = record["retries"] + 1
    db_path = cfg["db_file"]

    if tui:
        tui.start_record(rec_id, title)

    elif not quiet:
        log.info("Processing: %s", title[:60])

    log.debug(
        "Worker: starting record %d",
        rec_id,
    )

    conn = get_db_connection(db_path)

    try:
        conn.execute(
            """
            UPDATE blog_posts
            SET status='processing', retries=?, updated_at=?
            WHERE id=?
            """,
            (
                retries,
                datetime.now().isoformat(),
                rec_id,
            ),
        )

        conn.commit()

    finally:
        conn.close()

    stream_output = None

    try:
        if stream_file:
            mode = (
                "a"
                if os.path.exists(stream_file)
                else "w"
            )

            stream_output = open(
                stream_file,
                mode,
                encoding="utf-8",
            )

        def on_chunk(chunk):
            if tui:
                tui.update_stream(rec_id, chunk)

            elif not quiet:
                sys.stdout.write(chunk)
                sys.stdout.flush()

            if stream_output:
                stream_output.write(chunk)
                stream_output.flush()

        def on_metrics(metrics):
            if tui:
                tui.update_metrics(rec_id, metrics)

        blog_post = generate_blog_post(
            title,
            body,
            cfg,
            session,
            on_chunk=(
                on_chunk
                if cfg.get("stream")
                else None
            ),
            cancel_event=_shutdown_event,
            on_metrics=on_metrics,
        )

    finally:
        if stream_output:
            stream_output.close()

    success = blog_post is not None

    if success:
        wc = len(blog_post.split())

        update_record_status(
            db_path,
            rec_id,
            "completed",
            blog_post=blog_post,
            word_count=wc,
            error_message=None,
        )

        if tui:
            tui.set_content(rec_id, blog_post)

            log.info(
                "Completed %s (%d words)",
                title[:60],
                wc,
            )

        else:
            if not quiet:
                log.info(
                    "Completed %s (%d words)",
                    title[:60],
                    wc,
                )

            if not cfg.get("stream") and not quiet:
                print("\n--- FINAL POST ---\n")
                print(blog_post)
                print("\n---\n")

    else:
        error_msg = (
            "Cancelled"
            if _shutdown_event.is_set()
            else "Failed after retries"
        )

        update_record_status(
            db_path,
            rec_id,
            "failed",
            error_message=error_msg,
        )

        if tui:
            tui.set_error(rec_id, error_msg)

            log.error(
                "Failed %s (%s)",
                title[:60],
                error_msg,
            )

        elif not quiet:
            log.error(
                "Failed %s (%s)",
                title[:60],
                error_msg,
            )

    if tui:
        tui.finish_record(rec_id, success)

    return success


def process_records(cfg, limit=None, force=False):
    signal.signal(signal.SIGINT, _signal_handler)

    _shutdown_event.clear()

    if force:
        conn = get_db_connection(cfg["db_file"])

        conn.execute(
            """
            UPDATE blog_posts
            SET status='pending',
                blog_post=NULL,
                retries=0,
                word_count=0,
                error_message=NULL
            """
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
        log.info("No pending records.")
        return

    total = len(records)

    log.info("Processing %d records...", total)

    log.info(
        "AI endpoint=%s | model=%s | streaming=%s | thinking=%s | context=%s",
        cfg["api_endpoint"],
        cfg["model"],
        cfg.get("stream", True),
        cfg.get("think", False),
        cfg.get("num_ctx", 4096),
    )

    concurrency = cfg.get("concurrency", 1)

    if cfg.get("stream") and concurrency > 1:
        log.info(
            "Streaming enabled with concurrency=%d.",
            concurrency,
        )

    use_tui = not cfg.get("no_tui", False)

    stream_lines = cfg.get("stream_lines", 180)
    stream_file = cfg.get("stream_to_file")
    quiet = cfg.get("quiet", False)

    session = build_session(cfg)

    tui = None

    if use_tui:
        try:
            tui = RichTUI(
                total,
                stream_lines=stream_lines,
                refresh_per_second=cfg.get(
                    "tui_refresh_per_second",
                    10,
                ),
            )

            tui.start()

        except Exception as exc:
            log.warning(
                "Failed to start TUI, falling back to console mode: %s",
                exc,
            )

            use_tui = False
            tui = None

    try:
        with ThreadPoolExecutor(
            max_workers=concurrency
        ) as pool:

            futures = []

            for rec in records:
                if _shutdown_event.is_set():
                    break

                future = pool.submit(
                    _worker,
                    cfg,
                    rec,
                    tui,
                    session,
                    stream_file,
                    quiet,
                )

                futures.append((future, rec["id"]))

            for future, record_id in futures:
                try:
                    future.result()

                except Exception as exc:
                    log.error(
                        "Worker for record %d crashed: %s",
                        record_id,
                        exc,
                    )

                    if log.isEnabledFor(logging.DEBUG):
                        log.exception("Worker details:")

                    update_record_status(
                        cfg["db_file"],
                        record_id,
                        "failed",
                        error_message=str(exc),
                    )

                    if tui:
                        tui.set_error(record_id, str(exc))
                        tui.finish_record(
                            record_id,
                            success=False,
                        )

    finally:
        if tui:
            tui.stop()

        session.close()

    log.info("Processing finished.")


# ============================================================================
# EXPORT
# ============================================================================

def export_posts(
    db_path,
    export_dir,
    status="completed",
    fmt="md",
):
    export_path = Path(export_dir)
    export_path.mkdir(
        parents=True,
        exist_ok=True,
    )

    records = get_records(
        db_path,
        status_filter=status,
    )

    exported = 0

    for rec in records:
        if not rec["blog_post"]:
            continue

        title = rec["title"]
        body = rec["blog_post"]
        wc = rec.get("word_count", len(body.split()))
        rec_id = rec["id"]

        safe_name = "".join(
            c if c.isalnum() or c in " _-" else "_"
            for c in title
        )

        safe_name = (
            safe_name.strip().replace(" ", "_")[:80]
            or f"post_{rec_id}"
        )

        if fmt == "json":
            filepath = (
                export_path
                / f"{rec_id:04d}_{safe_name}.json"
            )

            payload = {
                "id": rec_id,
                "title": title,
                "word_count": wc,
                "generated_at": rec.get(
                    "updated_at",
                    datetime.now().isoformat(),
                ),
                "blog_post": body,
            }

            filepath.write_text(
                json.dumps(
                    payload,
                    indent=2,
                    ensure_ascii=False,
                ),
                encoding="utf-8",
            )

        else:
            filepath = (
                export_path
                / f"{rec_id:04d}_{safe_name}.md"
            )

            # JSON encoding safely escapes quotes and special characters
            # in the YAML title.
            yaml_title = json.dumps(
                title,
                ensure_ascii=False,
            )

            frontmatter = (
                f"---\n"
                f"id: {rec_id}\n"
                f"title: {yaml_title}\n"
                f"word_count: {wc}\n"
                f"generated_at: {rec.get('updated_at', datetime.now().isoformat())}\n"
                f"---\n\n"
            )

            filepath.write_text(
                frontmatter + body,
                encoding="utf-8",
            )

        exported += 1

        log.info(
            "Exported: %s",
            filepath.name,
        )

    return exported


# ============================================================================
# STATS AND DATABASE COMMANDS
# ============================================================================

def show_stats(db_path):
    conn = get_db_connection(db_path)

    try:
        cursor = conn.execute(
            """
            SELECT status, COUNT(*)
            FROM blog_posts
            GROUP BY status
            """
        )

        status_counts = {
            row[0]: row[1]
            for row in cursor.fetchall()
        }

        total = sum(status_counts.values())

        cursor = conn.execute(
            """
            SELECT
                COUNT(*),
                COALESCE(AVG(word_count), 0),
                COALESCE(MIN(word_count), 0),
                COALESCE(MAX(word_count), 0)
            FROM blog_posts
            WHERE status='completed'
            """
        )

        completed, avg, mn, mx = cursor.fetchone()

    finally:
        conn.close()

    log.info("Database Statistics")
    log.info(" Total: %d", total)

    for status in (
        "pending",
        "processing",
        "completed",
        "failed",
    ):
        log.info(
            " %s: %d",
            status.capitalize(),
            status_counts.get(status, 0),
        )

    if completed:
        log.info(
            " Completed posts: avg=%.0f, min=%d, max=%d",
            avg,
            mn,
            mx,
        )


def cmd_list(db_path, status):
    records = get_records(
        db_path,
        status_filter=(
            status
            if status != "all"
            else None
        ),
    )

    if not records:
        log.info(
            "No records found with status '%s'.",
            status,
        )
        return

    log.info(
        "Records (status=%s):",
        status,
    )

    log.info(
        " %-4s %-10s %-5s %s",
        "ID",
        "Status",
        "Words",
        "Title",
    )

    log.info(" %s", "-" * 80)

    for rec in records:
        wc = (
            rec.get("word_count", 0)
            or (
                len(rec.get("blog_post", "").split())
                if rec.get("blog_post")
                else 0
            )
        )

        log.info(
            " %-4d %-10s %-5d %s",
            rec["id"],
            rec["status"],
            wc,
            rec["title"][:70],
        )


def cmd_show(db_path, record_id):
    conn = get_db_connection(db_path)

    try:
        row = conn.execute(
            "SELECT * FROM blog_posts WHERE id=?",
            (record_id,),
        ).fetchone()

    finally:
        conn.close()

    if not row:
        log.error(
            "Record %d not found.",
            record_id,
        )
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
        log.info(
            "Error: %s",
            rec["error_message"],
        )

    log.info(
        "\n--- Original Body ---\n%s",
        rec["body"],
    )

    if rec.get("blog_post"):
        log.info(
            "\n--- Generated Blog Post ---\n%s",
            rec["blog_post"],
        )


# ============================================================================
# CONNECTION TEST
# ============================================================================

def test_connection(cfg):
    session = build_session(cfg)

    headers = {
        "Content-Type": "application/json",
    }

    if cfg.get("api_key"):
        headers["Authorization"] = (
            f"Bearer {cfg['api_key']}"
        )

    endpoint = cfg["api_endpoint"].lower()

    if (
        "/api/chat" in endpoint
        and "/v1/" not in endpoint
    ):
        payload = {
            "model": cfg["model"],
            "messages": [
                {
                    "role": "user",
                    "content": "Reply with OK",
                }
            ],
            "stream": False,
            "think": bool(cfg.get("think", False)),
            "options": {
                "num_ctx": cfg.get("num_ctx", 4096),
                "num_predict": 10,
            },
        }

    else:
        payload = {
            "model": cfg["model"],
            "messages": [
                {
                    "role": "user",
                    "content": "Reply with OK",
                }
            ],
            "max_tokens": 10,
            "stream": False,
        }

    try:
        resp = session.post(
            cfg["api_endpoint"],
            headers=headers,
            json=payload,
            timeout=30,
        )

        resp.raise_for_status()

        log.info("Connection successful.")

        return True

    except Exception as exc:
        log.error(
            "Connection failed: %s",
            exc,
        )

        return False

    finally:
        session.close()


# ============================================================================
# ARGUMENTS
# ============================================================================

def parse_args():
    parser = ArgumentParser(
        description="Blog post generator with AI and TUI."
    )

    parser.add_argument(
        "csv",
        nargs="?",
        help="CSV file to import",
    )

    parser.add_argument(
        "--db",
        help="SQLite database file",
    )

    parser.add_argument(
        "--config",
        help="JSON config file",
    )

    parser.add_argument(
        "--init-config",
        metavar="FILE",
        help="Write a starter JSON config file and exit",
    )

    parser.add_argument(
        "--limit",
        type=int,
        help="Max records to process",
    )

    parser.add_argument(
        "--force",
        action="store_true",
        help="Reset all to pending",
    )

    parser.add_argument(
        "--stats",
        action="store_true",
        help="Show statistics",
    )

    parser.add_argument(
        "--list",
        nargs="?",
        const="pending",
        help="List records by status",
    )

    parser.add_argument(
        "--show",
        type=int,
        help="Show record by ID",
    )

    parser.add_argument(
        "--export",
        action="store_true",
        help="Export completed posts",
    )

    parser.add_argument(
        "--export-dir",
        help="Export directory",
    )

    parser.add_argument(
        "--export-format",
        choices=["md", "json"],
        help="Export format",
    )

    parser.add_argument(
        "--test",
        action="store_true",
        help="Test AI connection",
    )

    parser.add_argument(
        "--dry-run",
        action="store_true",
        help="Preview what would be processed",
    )

    parser.add_argument(
        "--retry-failed",
        action="store_true",
        help="Reset failed to pending",
    )

    parser.add_argument(
        "--delete",
        nargs="?",
        const="all",
        help="Delete records by status",
    )

    parser.add_argument(
        "--log-file",
        help="Log file path",
    )

    parser.add_argument(
        "--verbose",
        action="store_true",
        help="Enable debug logging",
    )

    parser.add_argument(
        "-P",
        "--parallel",
        type=int,
        dest="concurrency",
        help="Number of parallel workers",
    )

    parser.add_argument(
        "--debug-ai",
        action="store_true",
        help="Log AI payloads and raw responses",
    )

    parser.add_argument(
        "--timeout",
        type=int,
        help="Request timeout in seconds",
    )

    parser.add_argument(
        "--pending",
        action="store_true",
        help="Show pending count",
    )

    parser.add_argument(
        "--stream",
        dest="stream",
        action="store_true",
        help="Enable streaming",
    )

    parser.add_argument(
        "--no-stream",
        dest="stream",
        action="store_false",
        help="Disable streaming",
    )

    parser.set_defaults(stream=None)

    parser.add_argument(
        "--no-tui",
        action="store_true",
        help="Disable TUI and print streaming content to stdout",
    )

    parser.add_argument(
        "--stream-to-file",
        metavar="FILE",
        help="Write streaming chunks to this file in real time",
    )

    parser.add_argument(
        "--stream-lines",
        type=int,
        help="Number of lines to show in TUI streaming panel",
    )

    parser.add_argument(
        "--tui-fps",
        type=int,
        dest="tui_refresh_per_second",
        help="TUI redraw rate",
    )

    parser.add_argument(
        "--quiet",
        action="store_true",
        help="Suppress progress output when --no-tui is used",
    )

    return parser.parse_args()


# ============================================================================
# MAIN
# ============================================================================

def main():
    args = parse_args()

    if args.init_config:
        setup_logging(verbose=False)
        write_starter_config(args.init_config)
        return

    cfg = load_config(args.config)

    if args.db:
        cfg["db_file"] = args.db

    if args.export_dir:
        cfg["export_dir"] = args.export_dir

    if args.export_format:
        cfg["export_format"] = args.export_format

    if args.log_file:
        cfg["log_file"] = args.log_file

    if args.concurrency is not None:
        cfg["concurrency"] = args.concurrency

    if args.debug_ai:
        cfg["debug_ai"] = True

    if args.timeout is not None:
        cfg["request_timeout"] = args.timeout

    if args.stream is not None:
        cfg["stream"] = args.stream

    if args.no_tui:
        cfg["no_tui"] = True

    if args.stream_to_file:
        cfg["stream_to_file"] = args.stream_to_file

    if args.stream_lines:
        cfg["stream_lines"] = args.stream_lines

    if args.tui_refresh_per_second:
        cfg["tui_refresh_per_second"] = (
            args.tui_refresh_per_second
        )

    if args.quiet:
        cfg["quiet"] = True

    validate_config(cfg)

    setup_logging(
        verbose=args.verbose or cfg.get("debug_ai"),
        log_file=cfg.get("log_file"),
    )

    db_path = cfg["db_file"]

    init_db(db_path)

    if args.test:
        test_connection(cfg)
        return

    if args.stats:
        show_stats(db_path)
        return

    if args.list is not None:
        cmd_list(db_path, args.list)
        return

    if args.show is not None:
        cmd_show(db_path, args.show)
        return

    if args.export:
        exported = export_posts(
            db_path,
            cfg["export_dir"],
            fmt=cfg.get("export_format", "md"),
        )

        log.info(
            "Exported %d posts to %s/",
            exported,
            cfg["export_dir"],
        )

        return

    if args.delete is not None:
        status = (
            None
            if args.delete == "all"
            else args.delete
        )

        deleted = delete_records(
            db_path,
            status,
        )

        log.info(
            "Deleted %d records%s.",
            deleted,
            (
                f" (status={args.delete})"
                if status
                else ""
            ),
        )

        return

    if args.retry_failed:
        conn = get_db_connection(db_path)

        try:
            conn.execute(
                """
                UPDATE blog_posts
                SET status='pending',
                    retries=0,
                    error_message=NULL
                WHERE status='failed'
                """
            )

            affected = conn.total_changes
            conn.commit()

        finally:
            conn.close()

        log.info(
            "Reset %d failed records to pending.",
            affected,
        )

        return

    if args.pending:
        conn = get_db_connection(db_path)

        try:
            count = conn.execute(
                """
                SELECT COUNT(*)
                FROM blog_posts
                WHERE status IN ('pending', 'failed')
                AND retries < ?
                """,
                (cfg["max_retries"],),
            ).fetchone()[0]

        finally:
            conn.close()

        log.info(
            "Records pending/failed (retryable): %d",
            count,
        )

        return

    if args.dry_run:
        log.info("DRY RUN mode")

        if args.csv:
            try:
                with open(
                    args.csv,
                    "r",
                    encoding="utf-8-sig",
                ) as f:
                    reader = csv.DictReader(f)
                    rows = list(reader)

                log.info(
                    "CSV: %s (%d rows)",
                    args.csv,
                    len(rows),
                )

            except Exception as exc:
                log.error(
                    "Error reading CSV: %s",
                    exc,
                )
                return

        pending = get_records(
            db_path,
            status_filter="pending",
            max_retries=cfg["max_retries"],
        )

        log.info(
            "Would process %d records.",
            len(pending),
        )

        return

    if args.csv:
        try:
            inserted = import_csv_to_db(
                args.csv,
                db_path,
            )

            log.info(
                "Inserted %d new records.",
                inserted,
            )

        except Exception as exc:
            log.error(
                "Import failed: %s",
                exc,
            )
            return

    process_records(
        cfg,
        limit=args.limit,
        force=args.force,
    )

    show_stats(db_path)


if __name__ == "__main__":
    main()