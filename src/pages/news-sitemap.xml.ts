// Google News sitemap: articles published in the last 48 hours.
// https://developers.google.com/search/docs/crawling-indexing/sitemaps/news-sitemap
// Blog post pages are served by the Worker from KV (see sitemap-blog.xml.ts);
// this static route only lists the recent ones for Google News discovery.
import type { APIRoute } from 'astro'
import { sortedBlogQa } from '@support/blog-qa'
import { SITE } from '@/config'

const PUBLICATION_NAME = 'PHP Teacher'
const PUBLICATION_LANGUAGE = 'en'
const WINDOW_MS = 48 * 60 * 60 * 1000

const escapeXml = (value: string) =>
    value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')

export const GET: APIRoute = () => {
    const cutoff = Date.now() - WINDOW_MS
    const urls = sortedBlogQa()
        .map((p) => ({ p, time: new Date(p.date || p.sourceCreatedAt || '').valueOf() }))
        .filter(({ time }) => !Number.isNaN(time) && time >= cutoff)
        .map(
            ({ p, time }) => `  <url>
    <loc>${SITE.url}/blog/${p.id}/</loc>
    <news:news>
      <news:publication>
        <news:name>${escapeXml(PUBLICATION_NAME)}</news:name>
        <news:language>${PUBLICATION_LANGUAGE}</news:language>
      </news:publication>
      <news:publication_date>${new Date(time).toISOString()}</news:publication_date>
      <news:title>${escapeXml(p.title)}</news:title>
    </news:news>
  </url>`,
        )
        .join('\n')

    const xml = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
${urls}
</urlset>`
    return new Response(xml, {
        headers: {
            'Content-Type': 'application/xml; charset=utf-8',
        },
    })
}
