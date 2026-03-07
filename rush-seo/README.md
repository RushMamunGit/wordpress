# Rush SEO for Shopify (WordPress plugin)

Production-ready MVP scaffold for an embedded Shopify SEO assistant.

## Requirements
- PHP 8.0+
- WordPress 6.0+
- MySQL 5.7+/MariaDB 10+
- Cron configured to run WordPress events (system cron recommended)

## Installation
1. Place the `rush-seo` folder in `wp-content/plugins/` and activate the plugin.
2. In `wp-config.php`, define your Shopify and app keys:

```php
define('RUSH_SEO_SHOPIFY_API_KEY',    'your-app-api-key');
define('RUSH_SEO_SHOPIFY_API_SECRET', 'your-app-api-secret');
define('RUSH_SEO_CRYPTO_KEY',         'random-long-secret');
define('RUSH_SEO_GEMINI_API_KEY',     'your-gemini-key');
```

3. Ensure cron runs (recommended):
```bash
*/5 * * * * wp cron event run --due-now >/dev/null 2>&1
```

## REST Endpoints
- POST `/wp-json/rush-seo/v1/oauth/install` -> `{ shop }` returns `redirectUrl`
- GET  `/wp-json/rush-seo/v1/oauth/callback` -> Shopify redirects here; exchanges token and starts 14-day trial
- POST `/wp-json/rush-seo/v1/billing/activate` -> `{ shop }` marks plan active
- POST `/wp-json/rush-seo/v1/scan` -> `{ shop, target_type, target_ref }` enqueue scan
- POST `/wp-json/rush-seo/v1/webhooks` -> Shopify webhooks (HMAC verified)
- POST `/wp-json/rush-seo/v1/ai/suggest` -> `{ type: title|meta|alt, context, constraints? }`

## Billing
- Monthly subscription: $20/month
- Free trial: 14 days (set on OAuth callback). Create the subscription via Shopify GraphQL and return to `/billing/activate`.

## Background Jobs
- DB-backed job table `wp_rush_seo_jobs` (created on activation)
- Worker runs every 5 minutes to process up to 10 jobs

## Email
- Uses `wp_mail` for weekly reports (stub in this MVP)

## Notes
- Theme App Extension and embedded admin UI are out-of-scope for this scaffold and will be added next.
- Add rate limiting and further validation before production.

