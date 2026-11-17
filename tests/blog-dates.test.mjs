import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, mkdtempSync, mkdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { execFileSync } from 'node:child_process';
import { normalizeBlogDates, sitemapLastmod } from '../src/support/blog-dates.mjs';

test('database created_at never becomes a publication date', () => {
    const input = { title: 'Question', date: '2026-11-15', content: 'Unchanged' };
    const result = normalizeBlogDates(input, true);
    assert.equal(result.date, '');
    assert.equal(result.sourceCreatedAt, input.date);
    assert.equal(result.content, input.content);
    assert.equal(input.date, '2026-11-15');
    assert.equal(normalizeBlogDates({ ...input, date: '2026-12-01' }, true).date, '');
});
test('recoverable editorial publication dates are retained, without re-dating', () => {
    const original = { date: '2023-05-20', content: 'Editorial' };
    assert.deepEqual(normalizeBlogDates(original), original);
});
test('lastmod requires an explicit valid modification record, never publication or now', () => {
    assert.equal(sitemapLastmod({ date: '2023-05-20' }), undefined);
    assert.equal(sitemapLastmod({ modifiedAt: 'invalid' }), undefined);
    assert.equal(sitemapLastmod({ modifiedAt: '2026-11-16' }), '2026-11-16');
});
test('finalizing the sitemap index adds the KV sitemap once and preserves static sitemap', () => {
    const dir = mkdtempSync(join(tmpdir(), 'sitemap-'));
    mkdirSync(join(dir, 'dist'));
    const file = join(dir, 'dist/sitemap-index.xml');
    writeFileSync(file, '<sitemapindex><sitemap><loc>https://phpteacher.com/sitemap-0.xml</loc></sitemap></sitemapindex>');
    const script = new URL('../scripts/finalize-sitemaps.mjs', import.meta.url);
    execFileSync(process.execPath, [script.pathname], { cwd: dir });
    execFileSync(process.execPath, [script.pathname], { cwd: dir });
    const xml = readFileSync(file, 'utf8');
    assert.equal((xml.match(/<loc>https:\/\/phpteacher.com\/sitemap-blog.xml<\/loc>/g) || []).length, 1);
    assert.ok(xml.includes('sitemap-0.xml'));
});
test('article and card templates suppress missing dates; sitemap does not use publication as lastmod', () => {
    const read = p => readFileSync(new URL('../' + p, import.meta.url), 'utf8');
    assert.match(read('src/components/blog/BlogQaCard.astro'), /date && <time/);
    assert.match(read('src/pages/blog/[slug].astro'), /if \(!data.date\) return undefined/);
    assert.match(read('src/pages/blog/[slug].astro'), /data.date && <li/);
    assert.match(read('src/pages/sitemap-blog.xml.ts'), /sitemapLastmod\(p\)/);
    assert.doesNotMatch(read('src/pages/sitemap-blog.xml.ts'), /<lastmod>\$\{p.date\}/);
});
