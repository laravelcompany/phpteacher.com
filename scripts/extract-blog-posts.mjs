// Runs after a full local build (no SKIP_BLOG_POST_PAGES) and the blog tests.
// Moves the ~16k blog post pages out of dist/ (they exceed the Pages
// per-deploy file limit) into batched KV bulk-upload JSON files under
// kv-bulk/. The Worker serves them from KV at request time.
import {
  readdirSync,
  readFileSync,
  rmSync,
  mkdirSync,
  writeFileSync,
  existsSync,
} from "node:fs";
import { join } from "node:path";
import { execSync } from "node:child_process";
import { BLOG_BATCHES, blogBatchIndex } from "../src/blog-kv-hash.js";

const root = new URL("../", import.meta.url).pathname;
const dist = join(root, "dist");
const blogDir = join(dist, "blog");
const outDir = join(root, "kv-bulk");
const MAX_CHUNK_BYTES = 64 * 1024 * 1024; // stay well under the 100MB bulk limit

if (!existsSync(blogDir)) {
  console.error("dist/blog missing - run a full npm run build first");
  process.exit(1);
}

// Post directories look like "1002-some-question-slug"; the pagination root
// is "page" (holding only numbered subdirectories) and stays in dist.
const isPostDir = (name) => {
  try {
    return (
      name !== "page" &&
      !/^\d+$/.test(name) &&
      readdirSync(join(blogDir, name)).includes("index.html")
    );
  } catch {
    return false; // not a directory
  }
};

const postDirs = readdirSync(blogDir).filter(isPostDir);
console.log(`found ${postDirs.length} post pages`);
if (postDirs.length < 15000) {
  console.error(`expected >15000 post pages, got ${postDirs.length}`);
  process.exit(1);
}

const batches = new Map(); // batchIndex -> {slug: html}
for (const slug of postDirs) {
  const html = readFileSync(join(blogDir, slug, "index.html"), "utf8");
  const n = blogBatchIndex(slug);
  if (!batches.has(n)) batches.set(n, {});
  batches.get(n)[slug] = html;
  rmSync(join(blogDir, slug), { recursive: true, force: true });
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });

// One bulk element per non-empty batch; chunk files by serialized size.
const elements = [...batches.entries()].map(([n, posts]) => ({
  key: `blog-batch-${String(n).padStart(3, "0")}`,
  value: JSON.stringify(posts),
}));

const chunks = [];
let current = [];
let currentBytes = 2; // []
for (const el of elements) {
  const size = Buffer.byteLength(el.value) + el.key.length + 32;
  if (currentBytes + size > MAX_CHUNK_BYTES && current.length) {
    chunks.push(current);
    current = [];
    currentBytes = 2;
  }
  current.push(el);
  currentBytes += size;
}
if (current.length) chunks.push(current);

chunks.forEach((chunk, i) =>
  writeFileSync(join(outDir, `chunk-${i}.json`), JSON.stringify(chunk))
);

const totalPosts = elements.reduce(
  (acc, el) => acc + Object.keys(JSON.parse(el.value)).length,
  0
);
const distFiles = execSync(`find "${dist}" -type f | wc -l`, {
  encoding: "utf8",
}).trim();
const manifest = {
  posts: totalPosts,
  batches: elements.length,
  chunks: chunks.length,
  distFiles: Number(distFiles),
};
writeFileSync(join(outDir, "manifest.json"), JSON.stringify(manifest, null, 2));
console.log(JSON.stringify(manifest));

if (Number(distFiles) >= 19500) {
  console.error(`dist still has ${distFiles} files (>= 19500) - too close to the Pages 20k limit`);
  process.exit(1);
}
