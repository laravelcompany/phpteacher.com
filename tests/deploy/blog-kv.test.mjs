// Run after scripts/extract-blog-posts.mjs: `npm run test:kv`.
// Verifies the KV bulk payload and that post pages left dist.
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync, readdirSync, existsSync, statSync } from "node:fs";
import { join } from "node:path";
import { execSync } from "node:child_process";
import { gunzipSync } from "node:zlib";
import { blogBatchKey } from "../../src/blog-kv-hash.js";

const root = new URL("../../", import.meta.url).pathname;
const out = join(root, "dist");
const kvDir = join(root, "kv-bulk");

const chunks = existsSync(kvDir)
  ? readdirSync(kvDir).filter((f) => /^chunk-\d+\.json$/.test(f))
  : [];

test("kv bulk chunks exist and parse", () => {
  assert.ok(chunks.length > 0, "no kv-bulk chunk files");
  for (const f of chunks) {
    const arr = JSON.parse(readFileSync(join(kvDir, f), "utf8"));
    assert.ok(Array.isArray(arr) && arr.length > 0, `${f} empty`);
    for (const el of arr) {
      if (el.key === "blog-removed-slugs") {
        assert.deepEqual(JSON.parse(el.value), JSON.parse(readFileSync(join(root, "src/blog-removed-slugs.json"), "utf8")));
        continue;
      }
      assert.match(el.key, /^blog-batch-\d{3}$/, `bad key ${el.key}`);
      const posts = JSON.parse(el.value);
      const slugs = Object.keys(posts);
      assert.ok(slugs.length > 0, `${el.key} holds no posts`);
      const html = gunzipSync(Buffer.from(posts[slugs[0]], "base64")).toString("utf8");
      assert.match(html, /<article/, `${slugs[0]} missing <article>`);
    }
  }
});

test("every post is in the batch its slug hashes to", () => {
  let checked = 0;
  for (const f of chunks) {
    const arr = JSON.parse(readFileSync(join(kvDir, f), "utf8"));
    for (const el of arr) {
      if (el.key === "blog-removed-slugs") continue;
      const posts = JSON.parse(el.value);
      for (const slug of Object.keys(posts)) {
        assert.equal(blogBatchKey(slug), el.key, `${slug} in wrong batch`);
        checked++;
      }
    }
  }
  assert.ok(checked > 9000, `expected >9000 posts, got ${checked}`);
});

test("a new questions/json post is in the KV payload", () => {
  const key = blogBatchKey("1002-how-to-install-mysqli");
  let found = "";
  for (const f of chunks) {
    const arr = JSON.parse(readFileSync(join(kvDir, f), "utf8"));
    const el = arr.find((e) => e.key === key);
    if (el) {
      const packed = JSON.parse(el.value)["1002-how-to-install-mysqli"] ?? "";
      found = packed
        ? gunzipSync(Buffer.from(packed, "base64")).toString("utf8")
        : "";
      break;
    }
  }
  assert.match(
    found,
    /How to install MySQLi/,
    "new questions/json post missing from KV payload",
  );
});

test("removed duplicates are out of the payload; the removed-slugs key ships", () => {
  const removed = JSON.parse(
    readFileSync(join(root, "src", "blog-removed-slugs.json"), "utf8")
  );
  const inPayload = new Set();
  let removedKeyCount = 0;
  for (const f of chunks) {
    const arr = JSON.parse(readFileSync(join(kvDir, f), "utf8"));
    for (const el of arr) {
      if (el.key === "blog-removed-slugs") {
        removedKeyCount++;
        assert.deepEqual(
          JSON.parse(el.value),
          removed,
          "blog-removed-slugs payload differs from src/blog-removed-slugs.json"
        );
        continue;
      }
      for (const slug of Object.keys(JSON.parse(el.value))) inPayload.add(slug);
    }
  }
  assert.equal(removedKeyCount, 1, "blog-removed-slugs key missing from chunks");
  const leaked = Object.keys(removed).filter((s) => inPayload.has(s));
  assert.deepEqual(leaked, [], `removed slugs still in KV payload: ${leaked.slice(0, 3)}`);
});

test("post pages are out of dist, index and pagination stay", () => {
  const leftovers = readdirSync(join(out, "blog")).filter(
    (name) =>
      name !== "page" &&
      !/^\d+$/.test(name) &&
      statSync(join(out, "blog", name)).isDirectory()
  );
  assert.deepEqual(leftovers, [], `post dirs left in dist: ${leftovers.slice(0, 3)}`);
  assert.ok(existsSync(join(out, "blog", "index.html")));
  assert.ok(existsSync(join(out, "blog", "page", "2", "index.html")));
});

test("dist is comfortably under the Pages file limit", () => {
  const distFiles = Number(
    execSync(`find "${out}" -type f | wc -l`, { encoding: "utf8" }).trim()
  );
  assert.ok(
    distFiles < 19500,
    `dist has ${distFiles} files, too close to the 20k Pages limit`,
  );
});
