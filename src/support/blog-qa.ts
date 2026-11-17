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
