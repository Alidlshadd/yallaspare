{{--
    The head every catalogue landing page shares: the breadcrumb trail as
    structured data, and the instruction to leave a page out of the index when
    it has nothing on it.

    An empty landing page is still worth serving — a visitor who followed a link
    should see the shop rather than a 404 — but it is not worth indexing, and a
    site full of thin pages costs the ones that do have something to say.
--}}
@php
    $breadcrumbSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($breadcrumbs ?? [])->values()->map(fn (array $crumb, int $index): array => [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'name' => $crumb['label'],
            'item' => $crumb['url'],
        ])->all(),
    ];
@endphp

@unless ($isIndexable ?? true)
    <meta name="robots" content="noindex,follow">
@endunless

<script nonce="{{ $cspNonce }}" type="application/ld+json">
    {!! json_encode($breadcrumbSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
</script>

@isset ($itemListSchema)
    <script nonce="{{ $cspNonce }}" type="application/ld+json">
        {!! json_encode($itemListSchema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
@endisset
