import test from "node:test";
import assert from "node:assert/strict";
import { execFileSync } from "node:child_process";
import { mkdtempSync, readFileSync, readdirSync } from "node:fs";
import { tmpdir } from "node:os";
import { join } from "node:path";
import { prepareIndex, searchIndex } from "../src/search/rank.mjs";

const read = (path) =>
    readFileSync(new URL("../" + path, import.meta.url), "utf8");

test("ranker: title beats tags beats excerpt, all terms must match", () => {
    const prepared = prepareIndex([
        { s: "a", t: "How to install Composer", e: "Dependencies in PHP.", c: "Composer", g: ["composer"], d: "2024-01-01" },
        { s: "b", t: "PHP arrays", e: "Composer mentioned in passing.", c: "PHP", g: [], d: "2025-01-01" },
        { s: "c", t: "Unrelated post", e: "Nothing here.", c: "PHP", g: [], d: "2026-01-01" },
    ]);
    const hits = searchIndex(prepared, "composer");
    assert.deepEqual(hits.map((h) => h.s), ["a", "b"]);
    assert.equal(searchIndex(prepared, "composer arrays").length, 1); // AND semantics
    assert.deepEqual(searchIndex(prepared, ""), []);
    // date breaks score ties: newest first
    const tie = searchIndex(
        prepareIndex([
            { s: "old", t: "laravel old", e: "", c: "Laravel", g: [], d: "2020-01-01" },
            { s: "new", t: "laravel new", e: "", c: "Laravel", g: [], d: "2021-01-01" },
        ]),
        "laravel",
    );
    assert.deepEqual(tie.map((h) => h.s), ["new", "old"]);
    // rank output carries only the display fields
    assert.deepEqual(Object.keys(tie[0]).sort(), ["c", "e", "s", "t"]);
});

test("builder indexes every blog post and no removed slug", () => {
    const dir = mkdtempSync(join(tmpdir(), "search-index-"));
    const out = join(dir, "index.json");
    execFileSync(
        process.execPath,
        ["--experimental-strip-types", "scripts/build-search-index.mjs", `--out=${out}`],
        { cwd: new URL("..", import.meta.url).pathname, stdio: "pipe" },
    );
    const index = JSON.parse(readFileSync(out, "utf8"));
    const corpus = readdirSync(
        new URL("../src/blog-qa", import.meta.url),
    ).filter((f) => f.endsWith(".json"));
    assert.equal(index.length, corpus.length);
    const removed = JSON.parse(read("src/blog-removed-slugs.json"));
    const slugs = new Set(index.map((p) => p.s));
    for (const slug of Object.keys(removed)) {
        assert.ok(!slugs.has(slug), `removed slug ${slug} must not be indexed`);
    }
    for (const p of index) {
        assert.ok(p.t && p.e && p.c, `entry ${p.s} has title, excerpt, category`);
    }
    // and the prepared ranker can actually find a known post
    const hits = searchIndex(prepareIndex(index), "laravel", 5);
    assert.ok(hits.length > 0, "a laravel query returns hits");
});

test("search popup queries the own /api/search route, not the Algolia demo index", () => {
    const tsx = read("src/components/support/search/AlgoliaDocSearch.tsx");
    assert.doesNotMatch(tsx, /godruoyi/i);
    assert.doesNotMatch(tsx, /461ZQ3AX3S/);
    assert.doesNotMatch(tsx, /c928a62c38a34b2cbfdb8323de9c9cc9/);
    assert.match(tsx, /transformSearchClient/);
    const client = read("src/components/support/search/localSearchClient.ts");
    assert.match(client, /\/api\/search\?q=/);
    // DocSearch v3.9 calls search({ requests: [...] }) (Algolia v5 shape),
    // not search(requests): a bare-array client throws "map is not a
    // function" and the popup silently shows "No results".
    assert.match(client, /input\.requests/);
    assert.match(client, /request\.query/);
    const worker = read("src/worker.js");
    assert.match(worker, /\/api\/search/);
    assert.match(worker, /search-index\.json/);
});

test("popup footer hides the Algolia attribution (own backend, not Algolia)", () => {
    const css = read("src/components/support/search/AlgoliaDocSearch.css");
    assert.match(css, /\.DocSearch-Logo\s*\{\s*display:\s*none/);
});
