import test from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
const read = (path) =>
    readFileSync(new URL("../" + path, import.meta.url), "utf8");

test("shared layout keeps one AdSense loader and the publisher identity", () => {
    const layout = read("src/layouts/BaseLayout.astro");
    assert.equal(
        (layout.match(/pagead\/js\/adsbygoogle\.js/g) || []).length,
        1,
    );
    assert.match(layout, /client=ca-pub-6836700493760647/);
    assert.equal(
        read("public/ads.txt").trim(),
        "google.com, pub-6836700493760647, DIRECT, f08c47fec0942fa0",
    );
    assert.match(layout, /NetworkExtras/);
});
test("site-wide theme is imported and content can be reached with a skip link", () => {
    const layout = read("src/layouts/BaseLayout.astro");
    assert.match(layout, /network-theme\.css/);
    assert.match(layout, /href="#main-content"/);
    assert.match(layout, /<main id="main-content"/);
    assert.doesNotMatch(layout, /new Lenis/);
});
test("mobile navigation is accessible, dismissible and height constrained", () => {
    const header = read("src/components/Header.astro");
    assert.match(header, /aria-controls="mobile-menu"/);
    assert.match(header, /aria-expanded/);
    assert.match(header, /event.key === 'Escape'/);
    assert.match(header, /max-height: calc\(100dvh - 80px\)/);
    assert.match(header, /overflow-y: auto/);
});
test("homepage keeps the existing resource routes with articles merged into /blog", () => {
    const home = read("src/pages/index.astro");
    for (const route of [
        "/blog/",
        "/manual/",
        "/categories/",
        "/magazines/",
        "/books-laravel/",
        "/partners/",
    ])
        assert.ok(home.includes(route), route);
    // The Posts section is merged into the one blog: no /posts links left.
    assert.doesNotMatch(home, /\/posts\//);
    assert.match(home, /getCollection\('posts'\)/);
    assert.doesNotMatch(home, /Weekly Updates|50k|testimonials|href="#"/);
});
test("search popup matches the dark violet theme and keeps its footer tidy", () => {
    const css = read("src/components/support/search/AlgoliaDocSearch.css").replace(/\s+/g, "");
    for (const token of [
        "--docsearch-modal-background:#130f2e",
        "--docsearch-searchbox-background:#0a0818",
        "--docsearch-footer-background:#0d0a22",
        "--docsearch-primary-color:#a78bfa",
        "--docsearch-searchbox-shadow:0",
        ".DocSearch-Commands{",
        "white-space:nowrap",
        "color-scheme:dark",
    ])
        assert.ok(css.includes(token), token);
});
test("house theme includes responsive layouts and reduced-motion support", () => {
    const css = read("src/styles/network-theme.css").replace(/\s+/g, "");
    for (const token of [
        "#0a0818",
        "#7c3aed",
        "#e879f9",
        "max-width:767px",
        "prefers-reduced-motion:reduce",
        "grid-template-columns:1fr",
    ])
        assert.ok(css.includes(token), token);
});
