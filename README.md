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

Before the November 2026 restyle, production main was `834e5ce582c689a61abd5dc35733ca75160bd596`. A git bundle of that revision was verified before publication. For an urgent rollback, choose the preceding successful production deployment in Cloudflare Pages. To undo the change in source, revert the restyle commit on main and let Pages publish the revert.

## Advertising and shared components

Keep `public/ads.txt` and the single AdSense loader in `BaseLayout.astro`. The house layout also retains existing analytics, the partner network strip and the LeadsCaptain promotion. Article content comes from the existing `posts` collection; the restyle does not add a new publishing system.
