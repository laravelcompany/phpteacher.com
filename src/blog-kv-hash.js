// Shared by the build-time extraction script and the Worker - must stay
// byte-identical in behavior so a post lands in the batch the Worker reads.
export const BLOG_BATCHES = 192;

export function blogBatchIndex(slug) {
  // djb2
  let h = 5381;
  for (let i = 0; i < slug.length; i++) {
    h = ((h << 5) + h + slug.charCodeAt(i)) >>> 0;
  }
  return h % BLOG_BATCHES;
}

export function blogBatchKey(slug) {
  return `blog-batch-${String(blogBatchIndex(slug)).padStart(3, "0")}`;
}
