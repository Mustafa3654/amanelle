# Amanelle Beauty

E-commerce storefront and admin for [Amanelle](https://amanelle.store) — authentic Gulf fragrance and cosmetics, sold in Lebanon.

Bilingual Arabic/English with full RTL, light and dark themes, multi-currency (USD/LBP) with admin-editable rates, and an inventory model built so the shop cannot oversell.

## Stack

| | |
|---|---|
| PHP | 8.3+ |
| Framework | Laravel 13 |
| Admin | Filament 5 |
| Frontend | Livewire 4, Alpine, Tailwind CSS v4 |
| Database | MySQL 8 / MariaDB 10.4+ |
| Images | Intervention Image (GD) |

## Getting started

```bash
git clone https://github.com/Mustafa3654/amanelle.git
cd amanelle
composer install
npm install
```

```bash
cp .env.example .env && php artisan key:generate
```

Create the database (`utf8mb4_unicode_ci` matters — Arabic product names sort wrong without it):

```bash
mysql -u root -e "CREATE DATABASE amanelle_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
php artisan migrate --seed
```

```bash
php artisan storage:link
```

```bash
php artisan make:filament-user
```

```bash
composer dev
```

The storefront is at `/`, the admin at `/admin`.

## How stock works

The part most worth understanding. Two numbers, never one:

| Column | Meaning | Changes when |
|---|---|---|
| `quantity` | Units physically on the shelf | **Only on delivery** |
| `reserved` | Units promised to open orders | Order placed / cancelled |
| `available` | `quantity - reserved` — what may be sold | derived, never stored |

Placing an order reserves stock without touching the shelf count. Marking it delivered is what finally decrements `quantity`. So with one unit in stock and one open order, the next customer sees *out of stock* rather than being allowed to buy it too.

Oversell is prevented by the write itself, not a prior read:

```sql
UPDATE inventories SET reserved = reserved + n
WHERE product_variant_id = ? AND quantity - reserved >= n
```

Two checkouts in the same millisecond both read "1 available"; only one can satisfy the predicate at write time. The other gets zero affected rows and an `InsufficientStockException`, and its order rolls back whole.

Every transition is guarded by its own timestamp rather than the status, so marking an order delivered twice deducts once, and cancelling an already-delivered order cannot invent stock. Pending orders release their reservation after 48 hours via `stock:release-expired`, so an abandoned checkout doesn't hold a unit hostage forever.

`stock_movements` is an append-only log with signed deltas — replaying it from zero reproduces the current row.

## Domain notes

Modelled on what the business actually sells rather than a generic cosmetics shape.

- **Longevity and projection** (*الثبات* / *الفوحان*) are rated, filterable columns. That is what this audience compares; the note pyramid is secondary.
- **`fragrance_references`** maps a product to the designer scent it is an alternative to. Search covers it, so someone typing "Kayali" finds the cheaper version — that journey is the whole pitch.
- **One variant table, three axes.** `products.type` selects which apply: volume and concentration for fragrance, shade and hex for makeup, volume alone for skincare.
- **Prices are stored in USD** and converted at display time from an admin-editable rate, so changing the LBP rate reprices the catalogue without touching a product row. Orders snapshot the currency and rate the customer saw.

## Features

**Storefront** — search, filtering and sorting; slide-in cart; cash-on-delivery checkout with per-area delivery fees and promo codes; order tracking by number plus phone; curated Instagram rail; Open Graph tags so shared links preview as cards.

**Admin** — products with per-locale tabs and a shade colour picker; one-click order fulfilment that drives stock; dashboard with revenue, low-stock alerts and charts; promo codes; delivery areas; currencies and rates; enquiries; read-only stock history; CSV export.

**Images** — every upload is converted to WebP, EXIF-oriented, capped at 1600px, and stripped of metadata.

## Testing

```bash
php artisan test
```

65 feature tests covering stock reservation and the oversell race, checkout, promo codes, delivery fees, browsing, image conversion, and every admin page rendering.

## Environment notes

Things that cost time on this build, recorded so they don't again:

- **`bootstrap/app.php` runs before `.env` is loaded**, so `env()` there is always `null`. Trusted proxies must be a literal. Without them, a TLS-terminating tunnel makes Laravel emit `http://` asset URLs on an `https://` page and the browser blocks the CSS as mixed content.
- **Blade reads `'@context'` as a directive.** Build schema.org arrays in a `@php` block.
- **XAMPP ships MariaDB, not MySQL.** `php artisan db:show` fails against it (it queries a `performance_schema` table MariaDB lacks) — cosmetic only. SQLite is used for tests, so the fulltext index is added conditionally.
- **PowerShell 5.1 strips `^`** from composer constraints, silently pinning exact versions. Use `5.*` style. Commit messages containing Arabic need `git commit -F <file>`.

## Licence

MIT.
