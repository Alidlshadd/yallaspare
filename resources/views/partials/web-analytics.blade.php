@php
    $webAnalytics = app(\App\Services\Analytics\ClientAnalytics::class);
    $ga4MeasurementId = $webAnalytics->ga4Enabled() ? $webAnalytics->ga4MeasurementId() : null;
    $metaPixelId = $webAnalytics->metaEnabled() ? $webAnalytics->metaPixelId() : null;
    $webAnalyticsEvents = $webAnalytics->enabled() ? $webAnalytics->flushForBrowser() : [];
@endphp

{{-- Nothing is emitted until an id is configured, and SecurityHeaders leaves
     the content security policy alone in that case too. Only the storefront
     layout includes this, which is what keeps the admin panel out.

     No subresource integrity on the two tag scripts, deliberately: Google and
     Meta ship a changing file from a fixed URL, so a pinned hash stops the
     measurement working within days. The nonce is what authorises them. --}}

@if ($ga4MeasurementId)
    <script nonce="{{ $cspNonce }}" async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4MeasurementId }}"></script>
    <script nonce="{{ $cspNonce }}">
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @json($ga4MeasurementId));
    </script>
@endif

@if ($metaPixelId)
    <script nonce="{{ $cspNonce }}">
        {{-- Meta's own loader, kept as published. It injects the pixel script,
             which 'strict-dynamic' trusts because this block carries a nonce. --}}
        !function (f, b, e, v, n, t, s) {
            if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) };
            if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
            t = b.createElement(e); t.async = !0; t.src = v; t.nonce = @json($cspNonce);
            s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
        fbq('init', @json($metaPixelId));
        fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none" alt="" src="https://www.facebook.com/tr?id={{ urlencode($metaPixelId) }}&ev=PageView&noscript=1"></noscript>
@endif

@if ($webAnalyticsEvents !== [])
    <script nonce="{{ $cspNonce }}">
        (() => {
            {{-- Shop-side events collected during the request. They ride the
                 session, so an action that redirects — adding to the cart —
                 still reports on the page the visitor lands on. --}}
            const events = @json($webAnalyticsEvents);

            for (const event of events) {
                if (typeof window.gtag === 'function' && event.ga4) {
                    gtag('event', event.ga4.name, event.ga4.params || {});
                }

                if (typeof window.fbq === 'function' && event.meta) {
                    const options = event.meta.event_id ? { eventID: event.meta.event_id } : undefined;
                    fbq('track', event.meta.name, event.meta.params || {}, options);
                }
            }
        })();
    </script>
@endif
