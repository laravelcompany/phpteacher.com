// Builds the blog search index consumed by the /api/search Worker route.
// Run with: node --experimental-strip-types scripts/build-search-index.mjs
// Writes public/search-index.json (gitignored, copied into dist by astro
// build; the Worker fetches it once per isolate through the STATIC assets
// binding). Replaces the Algolia "godruoyi" demo index with phpteacher's
// own corpus.
import { mkdirSync, writeFileSync } from "node:fs";
import { dirname, isAbsolute, join } from "node:path";
import {
    loadBlogQa,
    BLOG_CATEGORIES,
    matchesCategory,
} from "../src/support/blog-qa.ts";

const root = new URL("../", import.meta.url).pathname;
const outArg = process.argv.find((a) => a.startsWith("--out="));
const out = outArg
    ? outArg.slice("--out=".length)
    : join(root, "public", "search-index.json");
const outPath = isAbsolute(out) ? out : join(process.cwd(), out);

const EXCERPT_MAX = 180;

// Stored excerpts often keep their markdown (a leading "# Title" line,
// emphasis, links); the popup shows the excerpt as plain text.
function plain(text) {
    return (text || "")
        .replace(/<[^>]+>/g, " ")
        .replace(/!\[([^\]]*)\]\([^)]*\)/g, "$1")
        .replace(/\[([^\]]*)\]\([^)]*\)/g, "$1")
        .replace(/^\s*#+\s*/gm, "")
        .replace(/[*_`>]/g, "")
        .replace(/\s+/g, " ")
        .trim();
}

function excerptOf(post) {
    let text = plain(post.excerpt || post.description || "");
    if (!text) {
        text = plain(post.content || "");
    }
    if (text.length <= EXCERPT_MAX) return text;
    const cut = text.slice(0, EXCERPT_MAX);
    return cut.slice(0, cut.lastIndexOf(" ")).trimEnd() + "…";
}

function categoryTitleOf(post) {
    if (post.category) {
        const cat = BLOG_CATEGORIES.find((c) => c.slug === post.category);
        if (cat) return cat.title;
    }
    const bucket = BLOG_CATEGORIES.find(
        (c) => !c.editorialOnly && matchesCategory(post, c),
    );
    return bucket ? bucket.title : "PHP";
}

const posts = loadBlogQa();
const index = posts.map((post) => ({
    s: post.id,
    t: post.title,
    e: excerptOf(post),
    c: categoryTitleOf(post),
    g: Array.isArray(post.tags) ? post.tags : [],
    d: post.date || "",
}));

const json = JSON.stringify(index);
mkdirSync(dirname(outPath), { recursive: true });
writeFileSync(outPath, json);
console.log(
    `search index: ${index.length} posts, ${(json.length / 1024 / 1024).toFixed(2)} MB -> ${outPath}`,
);
