# Recipe: search from Twig

This searches the `news` index from [Recipe: index entries](./entry-index.md). It uses a `q` query parameter, filters to one category, and renders pagination.

```twig
{% set query = craft.app.request.getParam('q', '') %}
{% set search = craft.meilisearch.search('news', query, {
    hitsPerPage: 12,
    filter: 'category = "Press"',
    sort: ['postDate:desc'],
}) %}

{% if search.error is defined %}
  <p>Search is unavailable. Try again shortly.</p>
{% else %}
  <p>{{ search.pagination.total }} results</p>

  {% for result in search.results %}
    <article>
      <h2><a href="{{ result.url }}">{{ result.title }}</a></h2>
      <p>{{ result.summary }}</p>
    </article>
  {% endfor %}

  {% if search.pagination.prevUrl %}
    <a href="{{ search.pagination.prevUrl }}">Previous</a>
  {% endif %}

  {% if search.pagination.nextUrl %}
    <a href="{{ search.pagination.nextUrl }}">Next</a>
  {% endif %}
{% endif %}
```

The Twig variable adds the current Craft page number to the Meilisearch request. It returns:

- `results`: Meilisearch hits.
- `facetDistribution`: facet counts.
- `processingTimeMs`: request time reported by Meilisearch.
- `pagination`: Craft `Paginate` object.
- `error`: present only when Meilisearch returns an API error.

`search()` takes four arguments: the index handle, query, search parameters, and options.

The third argument contains Meilisearch search parameters, such as `filter`, `sort`, and `hitsPerPage`. The fourth argument contains options passed to `meilisearch-php`.
