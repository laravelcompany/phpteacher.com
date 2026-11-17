import { DocSearch } from '@docsearch/react'
import { phpTeacherSearchClient } from './localSearchClient'

import '@docsearch/css'
import './AlgoliaDocSearch.css'

// The popup keeps the DocSearch UI (and its theme overrides), but searches
// phpteacher's own corpus via the /api/search Worker route instead of the
// theme author's Algolia demo index. appId/apiKey are placeholders: the
// Algolia client is constructed but replaced by our client before any query.
function AlgoliaDocSearch() {
    return (
        <DocSearch
            appId="phpteacher"
            indexName="phpteacher-blog"
            apiKey="local-search"
            transformSearchClient={() => phpTeacherSearchClient as never}
        />
    )
}

export default AlgoliaDocSearch
