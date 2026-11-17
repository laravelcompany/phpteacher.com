import { blogBatchKey } from "./blog-kv-hash.js";

// phpteacher.com front door.
// - /blog/<slug> post pages are served from KV (batched, written at deploy
//   time by scripts/extract-blog-posts.mjs) because the ~16k post files
//   exceed the Pages per-deploy file limit.
// - Everything else (including /blog and /blog/page/<n> pagination) falls
//   through to the static assets binding (the Astro build output).
export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    const m = url.pathname.match(/^\/blog\/([^\/.]+)\/?(?:index\.html)?$/);
    if (m && !/^\d+$/.test(m[1]) && m[1] !== "page") {
      const slug = m[1];
      try {
        const batch = await env.BLOG_KV.get(blogBatchKey(slug), "json");
        const html = batch && batch[slug];
        if (html) {
          return new Response(html, {
            status: 200,
            headers: {
              "content-type": "text/html; charset=utf-8",
              "cache-control": "public, max-age=3600",
            },
          });
        }
        // Unknown slug: fall through to the static 404 page.
      } catch (err) {
        return new Response("Blog temporarily unavailable", { status: 502 });
      }
    }
    return env.STATIC.fetch(request);
  },
};
