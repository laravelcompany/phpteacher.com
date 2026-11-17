// Assembles kv-bulk/chunk-*.json from per-slice harvests under kv-parts/.
// Used when the full build had to be rendered in BLOG_SLICE=i/N runs:
// each slice build's post HTML files were copied into kv-parts/<slug>.html.
// Produces the same output as scripts/extract-blog-posts.mjs, without
// needing a single monolithic build.
import {
  readdirSync,
  readFileSync,
  rmSync,
  mkdirSync,
  writeFileSync,
  existsSync,
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

const batches = new Map(); // batchIndex -> {slug: html}
for (const f of files) {
  const slug = f.replace(/\.html$/, "");
  const html = readFileSync(join(partsDir, f), "utf8");
  const n = blogBatchIndex(slug);
  if (!batches.has(n)) batches.set(n, {});
  batches.get(n)[slug] = html;
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });

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
const manifest = {
  posts: totalPosts,
  batches: elements.length,
  chunks: chunks.length,
};
writeFileSync(join(outDir, "manifest.json"), JSON.stringify(manifest, null, 2));
console.log(JSON.stringify(manifest));
