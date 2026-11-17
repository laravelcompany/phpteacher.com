import { blogBatchKey } from "./blog-kv-hash.js";

// phpteacher.com front door.
// - /blog/<slug> post pages are served from KV (batched, written at deploy
//   time by scripts/extract-blog-posts.mjs or scripts/assemble-kv-bulk.mjs)
//   because the ~16k post files exceed the Pages per-deploy file limit.
//   Each post is stored gzip-compressed and base64-encoded: the raw harvest
//   is ~700MB, which would not fit the 1GB free-tier KV storage next to
//   ecudocs; gzipped it is ~100MB.
// - Everything else (including /blog and /blog/page/<n> pagination) falls
//   through to the static assets binding (the Astro build output).
function b64ToBytes(b64) {
  const bin = atob(b64);
  const bytes = new Uint8Array(bin.length);
  for (let i = 0; i < bin.length; i++) bytes[i] = bin.charCodeAt(i);
  return bytes;
}

export default {
  async fetch(request, env) {
    const url = new URL(request.url);
    // One canonical host: fold www into the apex.
    if (url.hostname === "www.phpteacher.com") {
      url.hostname = "phpteacher.com";
      return Response.redirect(url.toString(), 308);
    }
    const m = url.pathname.match(/^\/blog\/([^\/.]+)\/?(?:index\.html)?$/);
    if (m && !/^\d+$/.test(m[1]) && m[1] !== "page") {
      const slug = m[1];
      try {
        const batch = await env.BLOG_KV.get(blogBatchKey(slug), "json");
        const packed = batch && batch[slug];
        if (packed) {
          // Inflate in the worker and hand the edge plain HTML: the edge
          // then negotiates br/zstd/gzip with the client itself, exactly
          // like it does for the static assets. Setting content-encoding
          // here made the edge compress the payload a second time.
          const gz = b64ToBytes(packed);
          const stream = new Blob([gz])
            .stream()
            .pipeThrough(new DecompressionStream("gzip"));
          return new Response(stream, {
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
