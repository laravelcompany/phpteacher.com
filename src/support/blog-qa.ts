import { readdirSync, readFileSync } from 'node:fs'
import { join } from 'node:path'

// The ~6k Q&A posts reused from laravelcompany.com live in src/blog-qa/ as
// plain JSON files, read with node:fs at build time. They deliberately do NOT
// go through an Astro content collection: a glob-loaded data collection turns
// every JSON file into a vite module, which exhausts the build heap at this
// volume.

export interface BlogQaPost {
    id: string
    title: string
    date: string
    author: string
    authorRole: string
    authorAvatar: string
    excerpt: string
    content: string
    image?: string | null
    tags: string[]
    // Present on the long-form articles imported from the old Posts section.
    category?: string
    description?: string
    banner?: string | null
}

const DIR = 'src/blog-qa'

export function loadBlogQa(): BlogQaPost[] {
    return readdirSync(DIR)
        .filter(f => f.endsWith('.json'))
        .map(f => {
            const j = JSON.parse(readFileSync(join(DIR, f), 'utf8'))
            // The original harvest stored missing images as the string 'None'.
            if (j.image === 'None' || j.image === 'null') j.image = null
            if (j.banner === 'None' || j.banner === 'null') j.banner = null
            return { id: f.replace(/\.json$/, ''), ...j } as BlogQaPost
        })
}

export function sortedBlogQa(): BlogQaPost[] {
    return loadBlogQa().sort((a, b) => {
        const ta = new Date(a.date).valueOf() || 0
        const tb = new Date(b.date).valueOf() || 0
        return tb - ta || a.id.localeCompare(b.id)
    })
}

// Real categories of the merged blog. The long-form articles carry a
// category field; the Q&A posts are bucketed by strong keywords in their
// title/slug. A post can belong to several buckets. Slugs keep the old
// /categories/<slug>/ URLs alive.
export interface BlogCategory {
    slug: string
    title: string
    description: string
    keywords?: string[]
    editorialOnly?: boolean
}

export const BLOG_CATEGORIES: BlogCategory[] = [
    { slug: 'laravel', title: 'Laravel', description: 'Laravel, Eloquent and Artisan questions, answers and articles.', keywords: ['laravel', 'eloquent', 'artisan'] },
    { slug: 'php', title: 'PHP', description: 'Core PHP articles, language features and day-to-day development.', editorialOnly: true },
    { slug: 'mysql', title: 'MySQL & SQL', description: 'MySQL, SQL and database questions: queries, connections, errors and performance.', keywords: ['mysql', 'sql'] },
    { slug: 'javascript', title: 'JavaScript', description: 'JavaScript, Vue and React questions that come up in PHP projects.', keywords: ['javascript', 'vue', 'react'] },
    { slug: 'composer', title: 'Composer', description: 'Composer dependency management: installs, autoloading and version conflicts.', keywords: ['composer'] },
    { slug: 'codeigniter', title: 'CodeIgniter', description: 'CodeIgniter framework questions and answers.', keywords: ['codeigniter'] },
    { slug: 'wordpress', title: 'WordPress', description: 'WordPress questions: plugins, themes, hooks and errors.', keywords: ['wordpress'] },
    { slug: 'symfony', title: 'Symfony', description: 'Symfony framework questions and answers.', keywords: ['symfony'] },
    { slug: 'docker', title: 'Docker & DevOps', description: 'Docker, containers and deployment questions for PHP apps.', keywords: ['docker'] },
    { slug: 'servers', title: 'Web Servers', description: 'Apache and Nginx configuration, rewrites and errors.', keywords: ['apache', 'nginx'] },
    { slug: 'testing', title: 'Testing', description: 'PHPUnit and testing questions and articles.', keywords: ['phpunit', 'testing'] },
    { slug: 'git', title: 'Git', description: 'Git questions that come up in everyday PHP work.', keywords: ['git'] },
    { slug: 'security', title: 'Security', description: 'Application security: vulnerabilities, hardening and best practices.', editorialOnly: true },
    { slug: 'ddd', title: 'DDD', description: 'Domain-Driven Design in PHP applications.', editorialOnly: true },
    { slug: 'books', title: 'Books', description: 'Book notes and reading for PHP developers.', editorialOnly: true },
    { slug: 'news', title: 'News', description: 'News from the PHP and Laravel ecosystem.', editorialOnly: true },
    { slug: 'career', title: 'Career', description: 'Career notes for developers.', editorialOnly: true },
    { slug: 'tool', title: 'Tools', description: 'Developer tools around the PHP ecosystem.', editorialOnly: true },
    { slug: 'seo', title: 'SEO', description: 'SEO notes for developer-run sites.', editorialOnly: true },
    { slug: 'kubernetes', title: 'Kubernetes', description: 'Kubernetes notes for PHP deployments.', editorialOnly: true },
    { slug: 'drupal', title: 'Drupal', description: 'Drupal notes and questions.', keywords: ['drupal'] },
]

export function matchesCategory(post: BlogQaPost, cat: BlogCategory): boolean {
    const c = (post.category || '').toLowerCase()
    if (c && c === cat.slug) return true
    if (cat.editorialOnly) return false
    const hay = `${post.title} ${post.id}`.toLowerCase()
    return (cat.keywords || []).some(k => hay.includes(k))
}

export function postsInCategory(posts: BlogQaPost[], cat: BlogCategory): BlogQaPost[] {
    return posts.filter(p => matchesCategory(p, cat))
}
