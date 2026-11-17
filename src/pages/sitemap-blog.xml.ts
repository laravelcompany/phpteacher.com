// Blog post pages are served by the Worker from KV (see src/worker.js), so
// Cloudflare builds (SKIP_BLOG_POST_PAGES=1) never emit them and the
// @astrojs/sitemap integration cannot list their URLs. Emit them here as a
// dedicated sitemap; robots.txt points crawlers at it.
import type { APIRoute } from 'astro'
import { sortedBlogQa } from '@support/blog-qa'
import { sitemapLastmod } from '../support/blog-dates.mjs'
import { SITE } from '@/config'

export const GET: APIRoute = () => {
    const urls = sortedBlogQa()
        .map(p => {
            const modified = sitemapLastmod(p)
            return `  <url><loc>${SITE.url}/blog/${p.id}/</loc>${modified ? `<lastmod>${modified}</lastmod>` : ''}</url>`
        })
        .join('\n')
    const xml = `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>`
    return new Response(xml, {
        headers: {
            'Content-Type': 'application/xml; charset=utf-8',
        },
    })
}
