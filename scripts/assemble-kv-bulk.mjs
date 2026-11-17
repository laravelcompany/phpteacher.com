// Assembles kv-bulk/chunk-*.json from per-slice harvests under kv-parts/.
// Used when the full build had to be rendered in BLOG_SLICE=i/N runs:
// each slice build's post HTML files were copied into kv-parts/<slug>.html.
// Produces the same output as scripts/extract-blog-posts.mjs, without
// needing a single monolithic build. Streams batch by batch: the full
// harvest is ~700MB and cannot be held in one heap.
import {
  readdirSync,
  readFileSync,
  rmSync,
  mkdirSync,
  writeFileSync,
  existsSync,
  openSync,
  writeSync,
  closeSync,
  statSync,
} from "node:fs";
import { join } from "node:path";
import { blogBatchIndex } from "../src/blog-kv-hash.js";

const root = new URL("../", import.meta.url).pathname;
const partsDir = join(root, "kv-parts");
const outDir = join(root, "kv-bulk");
const MAX_CHUNK_BYTES = 64 * 1024 * 1024; // stay well under the 100MB bulk limit

if (!existsSync(partsDir)) {
  console.error("kv-parts missing - harvest slice builds first");
  process.exit(1);
}

const files = readdirSync(partsDir).filter((f) => f.endsWith(".html"));
console.log(`found ${files.length} harvested post pages`);
if (files.length < 15000) {
  console.error(`expected >15000 post pages, got ${files.length}`);
  process.exit(1);
}

// Group slugs by batch first (cheap), then read HTML one batch at a time.
const byBatch = new Map(); // batchIndex -> [slug]
for (const f of files) {
  const slug = f.replace(/\.html$/, "");
  const n = blogBatchIndex(slug);
  if (!byBatch.has(n)) byBatch.set(n, []);
  byBatch.get(n).push(slug);
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });

let chunkIdx = -1;
let fd = -1;
let currentBytes = 2; // []
let first = true;
const openChunk = () => {
  chunkIdx++;
  fd = openSync(join(outDir, `chunk-${chunkIdx}.json`), "w");
  writeSync(fd, "[");
  currentBytes = 2;
  first = true;
};
openChunk();

for (const [n, slugs] of [...byBatch.entries()].sort((a, b) => a[0] - b[0])) {
  const key = `blog-batch-${String(n).padStart(3, "0")}`;
  const posts = {};
  for (const slug of slugs) {
    posts[slug] = readFileSync(join(partsDir, `${slug}.html`), "utf8");
  }
  const el = JSON.stringify({ key, value: JSON.stringify(posts) });
  if (currentBytes + el.length > MAX_CHUNK_BYTES && !first) {
    writeSync(fd, "]");
    closeSync(fd);
    openChunk();
  }
  if (!first) writeSync(fd, ",");
  writeSync(fd, el);
  currentBytes += el.length;
  first = false;
}
writeSync(fd, "]");
closeSync(fd);

const manifest = {
  posts: files.length,
  batches: byBatch.size,
  chunks: chunkIdx + 1,
};
writeFileSync(join(outDir, "manifest.json"), JSON.stringify(manifest, null, 2));
console.log(JSON.stringify(manifest));
