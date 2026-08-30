{{--
    One script tag for everything a page has to say about itself.

    Pass $schemas as an array of nodes built by App\Support\Seo\StructuredData;
    nulls are dropped, and more than one node is folded into a single @graph.
    Nothing is emitted when there is nothing to say.
--}}
@php
    $structuredDataJson = \App\Support\Seo\StructuredData::encode($schemas ?? []);
@endphp

@if ($structuredDataJson !== '')
    <script nonce="{{ $cspNonce }}" type="application/ld+json">{!! $structuredDataJson !!}</script>
@endif
