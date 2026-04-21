# BioNames 2

New version of [BioNames](https://bionames.org).

## Features

### PDF thumbnails in article lists
Journal article lists (e.g. `/issn/0081-0282`) display a thumbnail of the first page for articles that have archived PDF content (`content_sha1`). Articles without a PDF but with a DOI show a DOI icon. Articles with neither show an empty placeholder box. Thumbnails are only shown for reference types (Book, Chapter, CreativeWork, ScholarlyArticle), not for name listings.

The same thumbnail/DOI-glyph pattern is used in the "Based on" section when viewing a single taxonomic name.

### Lazy loading
Thumbnails are lazy-loaded using an IntersectionObserver with a 200ms dwell timer and 200px root margin. Images only load when they enter the viewport and remain visible briefly, so rapid scrolling doesn't trigger unnecessary requests.

### JSON-LD data viewer
When `$config['show_data']` is enabled, each entity page shows a collapsible "Data" section containing the underlying JSON-LD, with lightweight syntax highlighting (keys, strings, numbers, booleans, null) themed for both light and dark mode. No external dependencies.

## Architecture notes

### Key files
- **index.php** — all display logic: `display_datafeed()` (article lists), `display_entity_details()` (single entity view), HTML shell, navbar
- **objects.php** — data access: `get_entity()`, `get_container()`, `get_container_works_list()`, `search_names()`, `db_row_to_reference()`
- **body.css.inc.php** — layout and component styles (thumbnails, reference rows, debug viewer, JSON highlight colours)
- **root.css.inc.php** — CSS custom properties for light/dark theming
- **lists.css.inc.php** — legacy list marker styles (currently unused by `display_datafeed`)
- **display.js.inc.php** — client-side JS: PDF viewer, citation rendering, JSON syntax highlighter, lazy-load observer
- **assets/** — UI assets (e.g. `doi.svg`); separate from `images/` which holds PhyloPic silhouettes

### Known limitations
- `search_names()` only returns name fields (`id`, `cluster_id`, `nameComplete`, `taxonAuthor`) — no reference/publication data, so search results cannot display thumbnails or DOI glyphs.
- `lists.css.inc.php` still contains `li.open`/`li.closed` rules that are no longer used by `display_datafeed` and could be removed.



## Credits

### Images

Images of animals come from [PhyloPic](https://www.phylopic.org). DOI logo is from [shadcn.io
](https://www.shadcn.io/icon/academicons-doi) under the [Open Font License](https://scripts.sil.org/cms/scripts/page.php?site_id=nrsi&id=OFL).




