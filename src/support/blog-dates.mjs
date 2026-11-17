// Database row creation is not proof of publication or substantive modification.
// Keep this timestamp for provenance, never turn it into public freshness.
export function normalizeBlogDates(post, isQuestionImport = false) {
    if (!isQuestionImport) return post;
    return { ...post, sourceCreatedAt: post.sourceCreatedAt || post.date, date: '' };
}

export function sitemapLastmod(post) {
    // Publication dates are not last-modification dates. No build-time fallback.
    const value = post.modifiedAt;
    return typeof value === 'string' && /^\d{4}-\d{2}-\d{2}(?:T.*)?$/.test(value)
        && Number.isFinite(Date.parse(value)) ? value : undefined;
}
