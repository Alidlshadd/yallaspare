{{--
    The head every catalogue landing page shares: the breadcrumb trail and the
    list of what is on the page, and the instruction to leave a page out of the
    index when it has nothing on it.

    An empty landing page is still worth serving — a visitor who followed a link
    should see the shop rather than a 404 — but it is not worth indexing, and a
    site full of thin pages costs the ones that do have something to say.
--}}

@unless ($isIndexable ?? true)
    <meta name="robots" content="noindex,follow">
@endunless

@include('partials.structured-data', [
    'schemas' => [
        \App\Support\Seo\StructuredData::breadcrumbs($breadcrumbs ?? []),
        $itemListSchema ?? null,
    ],
])
