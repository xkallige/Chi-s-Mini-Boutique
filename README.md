# Chi's Mini Boutique

Static HTML + Bootstrap 5.3 frontend and PHP 8 JSON API for XAMPP. The supplied attachment contained the build brief but no banner image, so the shop uses a light-weight navy/light-blue editorial hero made in CSS. Replace the hero artwork in index.html when you have the brand banner.

## XAMPP setup

1. Copy this project folder into `C:\xampp\htdocs\Chi's Mini Boutique` (the folder used for the local URL).
2. Start Apache and MySQL from the XAMPP Control Panel.
3. Open phpMyAdmin at `http://localhost/phpmyadmin`, select Import, and import `database/schema.sql`. It creates `cmb_boutique` and seeds six products plus state delivery fees.
4. Open `api/config.php`. Default local DB settings are MySQL at 127.0.0.1, database `cmb_boutique`, user `root`, blank password. Update those fields if your XAMPP MySQL account differs.
5. Initial local admin password: `CMB-Local-Admin-Setup-2026!`. Sign in at `http://localhost/Chi's%20Mini%20Boutique/admin/login.html`, then change the hash in config before sharing or deploying. Generate a hash with XAMPP PHP:
   `C:\xampp\php\php.exe -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"`
   Paste the output into `admin_password_hash`.
6. Add test public/secret keys for each provider you want to offer in api/config.php. Flutterwave also needs its webhook verification hash. Checkout lists each fully configured provider; gateway sets the default selection. Never commit or publish live keys.
7. The gateway callback URL uses the `site_url` in config. For local testing set it to `http://localhost/Chi%27s%20Mini%20Boutique`. For production set the HTTPS origin and folder path, if any.
8. In the gateway dashboard, set webhook URL to `https://your-domain.example/api/webhook.php`. For local webhook testing, run ngrok against port 80 (`ngrok http 80`) and use its HTTPS forwarding URL plus `/api/webhook.php` in the test dashboard.
9. Open `http://localhost/Chi's%20Mini%20Boutique/index.html`. The HTML files and local assets use relative URLs so they work from the htdocs subfolder.

## Payments

- Checkout offers every fully configured provider, and records the selected provider on each order. Paystack checkout uses Inline v2 and passes a server-created reference and amount in kobo.
- Checkout explicitly offers card payment through the selected provider's hosted payment UI; card details are entered on the provider's secure checkout.
- Payment is not trusted from the browser callback. success.html calls verify-payment.php; the API verifies status, NGN currency and exact order amount with the provider saved on that order. Each provider's signed webhook repeats the same idempotent fulfillment.
- Local payment gateways need a public HTTPS callback/webhook tunnel such as ngrok. If a tunnel is used, set site_url to the tunnel URL while testing callbacks.

## Pre-launch checklist

- [ ] Replace the initial admin password hash and confirm sign-in/session expiry.
- [ ] Add production database credentials, HTTPS site URL, business email, social links and gateway live keys.
- [ ] Replace sample product copy and upload real product photos; confirm categories, sizes and stock.
- [ ] Test cart persistence after closing and reopening the browser; test storage-disabled notice.
- [ ] Test unavailable/deleted products, stock cap, quantity changes, all Nigerian states and state-specific delivery.
- [ ] Complete a test payment in the selected gateway; verify the paid order appears in admin and email reaches both customer/admin.
- [ ] Test a webhook through ngrok. Confirm repeat webhook/verify calls do not deduct stock twice.
- [ ] Switch an order between Pending Delivery and Shipped; test order filters/search and WhatsApp link.
- [ ] Upload a large JPG/PNG/WebP; confirm 1200px max resize, WebP/JPG output and old-image cleanup.
- [ ] Confirm HTTPS, backups, private config, upload script blocking and PHP error logging on production hosting.
- [ ] Test pages on mobile and with keyboard/focus/reduced-motion settings.

PHP requires PDO MySQL, cURL, fileinfo and GD. For image WebP conversion GD WebP is preferred; without it uploads fall back to JPG. Bootstrap and Google Fonts load from CDN, so an internet connection is needed for those assets (the custom stylesheet still provides the layout if CDNs are unavailable).
