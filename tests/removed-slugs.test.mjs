// The laravelcompany.com duplicates removed from the blog in November 2026
// are recorded in src/blog-removed-slugs.json so the Worker can answer their
// old URLs (redirect to the identical laravelcompany.com article, or 410).
// This guards the map against drifting back into the corpus.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('../', import.meta.url).pathname;

test('removed-slugs map is well formed and disjoint from the corpus', () => {
    const removed = JSON.parse(
        readFileSync(join(root, 'src', 'blog-removed-slugs.json'), 'utf8'),
    );
    const slugs = Object.keys(removed);
    assert.ok(slugs.length > 5000, `expected the ~6k removed slugs, got ${slugs.length}`);
    for (const [slug, mark] of Object.entries(removed)) {
        assert.match(slug, /^[a-z0-9_-]+$/, `bad slug ${slug}`);
        assert.ok(mark === 'r' || mark === 'g', `bad mark ${mark} on ${slug}`);
    }
    const corpus = new Set(
        readdirSync(join(root, 'src', 'blog-qa'))
            .filter((f) => f.endsWith('.json'))
            .map((f) => f.replace(/\.json$/, '')),
    );
    const back = slugs.filter((s) => corpus.has(s));
    assert.deepEqual(back, [], `removed slugs back in src/blog-qa: ${back.slice(0, 3)}`);
});
