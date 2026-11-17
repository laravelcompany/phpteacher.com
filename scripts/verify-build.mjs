import assert from "node:assert/strict";
import { readFileSync, existsSync } from "node:fs";
const routes = [
    "",
    "blog/",
    "categories/",
    "manual/",
    "magazines/",
    "partners/",
    "books-laravel/",
];
for (const route of routes) {
    const html = readFileSync(`dist/${route}index.html`, "utf8");
    assert.equal(
        (html.match(/pagead\/js\/adsbygoogle\.js/g) || []).length,
        1,
        `${route}: one AdSense loader`,
    );
    assert.ok(
        html.includes("ca-pub-6836700493760647"),
        `${route}: publisher identity`,
    );
    assert.ok(html.includes("main-content"), `${route}: shared layout`);
}
assert.equal(
    readFileSync("dist/ads.txt", "utf8"),
    readFileSync("public/ads.txt", "utf8"),
);
assert.ok(
    existsSync("dist/manual/index.html"),
    "PHP manual index remains available",
);
const searchIndex = JSON.parse(readFileSync("dist/search-index.json", "utf8"));
assert.ok(
    searchIndex.length > 9000,
    `blog search index covers the corpus (${searchIndex.length} posts)`,
);
assert.ok(
    existsSync("dist/manual/function.strlen.html"),
    "bundled PHP manual remains available",
);
console.log(
    "Production output verified: seven resource routes, shared layout, one AdSense loader per page, ads.txt, PHP manual, blog search index.",
);
