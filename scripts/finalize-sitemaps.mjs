// The blog lives in KV, outside Astro's static page discovery.
import { readFileSync, writeFileSync } from 'node:fs';
const file = 'dist/sitemap-index.xml';
const blogUrl = 'https://phpteacher.com/sitemap-blog.xml';
let xml = readFileSync(file, 'utf8');
if (!xml.includes(`<loc>${blogUrl}</loc>`)) {
    xml = xml.replace('</sitemapindex>', `<sitemap><loc>${blogUrl}</loc></sitemap></sitemapindex>`);
}
if (!xml.includes(`<loc>${blogUrl}</loc>`)) throw new Error('Sitemap index insertion failed');
writeFileSync(file, xml);
console.log('Sitemap index includes blog sitemap.');
