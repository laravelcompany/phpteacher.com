// Run after a full local build (no SKIP_BLOG_POST_PAGES):
// `npm run build && npm run test:blog`.
// The blog is live: /blog routes build, post pages build, nav and footer link to it.
// Cloudflare builds skip post pages (Worker + KV serves them), so this test
// only passes on a full local build.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync, existsSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('../', import.meta.url).pathname;
const out = join(root, 'dist');

const isPostDir = (name) => {
    try {
        return (
            name !== 'page' &&
            !/^\d+$/.test(name) &&
            statSync(join(out, 'blog', name)).isDirectory() &&
            readdirSync(join(out, 'blog', name)).includes('index.html')
        );
    } catch {
        return false;
    }
};

test('blog index page 1 exists and lists posts', () => {
    const indexPath = join(out, 'blog', 'index.html');
    assert.ok(existsSync(indexPath), 'dist/blog/index.html missing');
    const html = readFileSync(indexPath, 'utf8');
    assert.match(html, /Blog/, 'index has no Blog heading');
    assert.match(html, /href="\/blog\/[^/]+\/"/, 'index links no post pages');
});

test('blog pagination page 2 exists', () => {
    assert.ok(
        existsSync(join(out, 'blog', 'page', '2', 'index.html')),
        'dist/blog/page/2/index.html missing',
    );
});

test('post pages were generated', () => {
    const postDirs = readdirSync(join(out, 'blog')).filter(isPostDir);
    assert.ok(postDirs.length > 15000, `expected >15000 post pages, got ${postDirs.length}`);
    const html = readFileSync(join(out, 'blog', postDirs[0], 'index.html'), 'utf8');
    assert.match(html, /<article/, 'post page missing <article>');
});

test('the questions/json posts are included', () => {
    const html = readFileSync(
        join(out, 'blog', '1002-how-to-install-mysqli', 'index.html'),
        'utf8',
    );
    assert.match(html, /How to install MySQLi/, 'new questions/json post missing');
});

test('nav and footer link to the blog on the homepage', () => {
    const home = readFileSync(join(out, 'index.html'), 'utf8');
    const links = home.match(/href="\/blog\/"/g) ?? [];
    assert.ok(links.length >= 2, `expected nav+footer /blog links, got ${links.length}`);
});
