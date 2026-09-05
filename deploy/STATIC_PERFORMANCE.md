# Storefront load performance

Measured against the live site on 2026-09-05, after a report that the storefront
felt sluggish across the board. The numbers below are what the browser actually
had to fetch for one visit to the home page.

## What the measurement found

| Asset | Size | Cache-Control | Compressed |
| --- | --- | --- | --- |
| Hero video (`/storage/home/hero/*.mp4`) | 31.2 MB | none | n/a |
| 14 product photos (`/storage/products/*.png`) | 1.3–2.1 MB each, ~24 MB | none | n/a |
| Popup image (`/storage/popups/*.png`) | 1.7 MB | none | n/a |
| CSS bundle | 290 KB | none | no |
| JS bundles | 161 KB + 23 KB | none | no |

Roughly **57 MB for one home page view**, none of it cacheable, with the text
assets uncompressed. Server render time itself was fine (TTFB 0.5–1.2s); the
weight was the problem, and because nothing carried a `Cache-Control` header,
every page view paid it again.

## Fixed in the application

- The hero video no longer downloads on page load. It was marked
  `preload="auto"` and started fetching all 31 MB immediately, starving every
  other request on the page. It now loads only once the hero scrolls into view,
  and not at all on a save-data or 2G/3G connection — the poster frame, which
  was already there, carries the hero in that case.
- The hero video no longer restarts on `suspend`, `stalled` and `waiting`. Those
  fire constantly while a large file buffers, and restarting on them is what
  made the hero blink between poster and video.
- The hero video no longer re-primes itself on every `scroll`, `touchstart`,
  `pointerdown`, `pointerup` and `click`. Rewriting a `<video>` element's
  attributes on every scroll tick is what made the page stutter under the
  finger. One user gesture is still enough to lift a mobile autoplay block; the
  listener now retires itself after that.
- The site-wide popup image is lazy-loaded. It sits in a `display:none` dialog
  on every storefront page and was being downloaded in full regardless.
- Product, category, cart, brand and account images are lazy-loaded and decode
  off the main thread. The two images that are the largest paint on their page —
  the home hero poster and the product detail photo — are marked
  `fetchpriority="high"` instead, so they are not delayed.

## Still to do on the server

Include `deploy/nginx/yallaspare-static-performance.conf.example` in the HTTPS
server block, then:

```bash
sudo nginx -t
sudo systemctl reload nginx
```

Verify with:

```bash
curl -sI -H 'Accept-Encoding: gzip' https://yallaspare.com/build/assets/<hashed>.css \
  | grep -iE 'cache-control|content-encoding'
```

Expect `Cache-Control: public, max-age=31536000, immutable` and
`Content-Encoding: gzip`. Note that `yallaspare-upload-hardening.conf.example`
already carried a `Cache-Control` block for `/storage/`, and the live headers
show it is not in effect — so check that the include is actually reached and not
shadowed by an earlier `location` match.

## Still to do in the application

The product photos are served at their original upload resolution — 1.3–2.1 MB
PNGs rendered into roughly 300px cards. Generating a resized derivative on
upload (plus a backfill command for existing files) would take that ~24 MB down
to well under 1 MB. That needs an image library, which the project does not
currently depend on, so it is scoped separately.
