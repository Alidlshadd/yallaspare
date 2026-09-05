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

## Resized images, without touching the upload path

Uploads are stored at whatever resolution they arrived in — 1.3–2.1 MB PNGs
rendered into roughly 300px cards. Rather than change how uploads are handled,
`php artisan images:variants` walks the stored files and writes resized WebP
copies under `storage/app/public/variants/<width>/…`, and the views ask
`App\Support\ImageVariants` for one. Measured on the local image set, a 555 KB
PNG became a 9 KB WebP at card width.

Three widths are generated: 400 (cards, category tiles, thumbnails), 800 (popup,
vehicle art) and 1400 (the product detail photo). Anything already narrower than
a given width is skipped, since re-encoding it would only make it heavier.

Nothing about this is load-bearing. A path with no variant yet, a remote URL, or
a machine without the `gd` extension all fall back to the original file, so a
missing variant is never a broken image. The product page's `og:image` and
`twitter:image` deliberately keep the full-size original, because other sites
render those.

The command is scheduled hourly, which is what keeps the upload path untouched:
a newly uploaded photo serves its original for at most an hour, then its
variant. After deploying, run it once to backfill:

```bash
php artisan images:variants
```

GD is required (`php8.3-gd`), and it must be built with WebP support — check
with `php -r "print_r(gd_info());"`. Without it the command says so and exits
cleanly rather than failing the deploy.
