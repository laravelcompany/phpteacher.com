# PHP Teacher

Astro site for https://phpteacher.com. The shared theme follows LaravelCompany's Nunito typography, dark violet surfaces, purple/pink accents, glass cards and responsive navigation. Existing posts, magazines, books and the bundled PHP manual keep their routes.

## Development and checks

```sh
npm ci
npm run dev
npm test
npm run build
```

The production build runs the regression tests first, builds the complete site, then verifies the main resource routes, shared layout, AdSense loader, ads.txt and the bundled manual. Original manual and magazine files are copied without re-minification. This avoids processing thousands of unchanged upstream HTML files on every build.

## Deployment and rollback

Cloudflare Pages project `new-phpteacher-com` builds `main` with `npm run build`, publishing `dist`. The production domain is `phpteacher.com`.

Traffic is fronted by the `phpteacher-com` Worker (`src/worker.js`, zone routes `phpteacher.com/*` and `www.phpteacher.com/*`), which serves `/blog/<slug>` post pages from Workers KV and everything else from the static assets. A production deploy is:

```sh
npm run build && npm run test:blog        # full local build with post pages
node scripts/extract-blog-posts.mjs       # dist/blog/* -> kv-bulk/, trims dist under the 20k cap
npm run test:kv                           # validates the KV payload
wrangler deploy --config wrangler.worker.jsonc   # substitute the real BLOG_KV namespace id first
wrangler kv bulk put ...                   # upload kv-bulk/chunk-*.json to the BLOG_KV namespace
```

Posts removed from the corpus (the laravelcompany.com duplicates, November 2026) stay answerable: `src/blog-removed-slugs.json` ships to KV as `blog-removed-slugs`, and the Worker 308-redirects those old `/blog/<slug>` URLs to the identical article on laravelcompany.com (410 when no equivalent exists).

Before the November 2026 restyle, production main was `834e5ce582c689a61abd5dc35733ca75160bd596`. A git bundle of that revision was verified before publication. For an urgent rollback, choose the preceding successful production deployment in Cloudflare Pages. To undo the change in source, revert the restyle commit on main and let Pages publish the revert.

## Advertising and shared components

Keep `public/ads.txt` and the single AdSense loader in `BaseLayout.astro`. The house layout also retains existing analytics, the partner network strip and the LeadsCaptain promotion. Article content comes from the existing `posts` collection; the restyle does not add a new publishing system.
