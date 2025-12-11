# Khokan.me Admin and Cache Recovery Checklist

## What the dump shows

- User ID `1` is the real Administrator account.
- `wp_user_roles` already includes `manage_options`, `activate_plugins`, and normal Administrator capabilities.
- The permission problem is therefore likely caused at runtime by a plugin, object/cache layer, `wp-config.php`, or modified core behavior.
- LiteSpeed settings in the dump are unsafe for this site:
  - `litespeed.conf.cache-priv = 1`
  - `litespeed.conf.cache-page_login = 1`
  - `litespeed.conf.cache-exc_roles = []`
  - `litespeed.conf.esi-cache_admbar = 1`

## Recovery Steps

1. In phpMyAdmin, select the live database.
2. Run `ops/admin-permission-cache-recovery.sql`.
3. Log out completely and log in again as user ID `1`.
4. Test `/wp-admin/options-general.php`.
5. Test `/wp-admin/plugins.php`.
6. If admin access works, run `ops/admin-permission-restore-plugins.sql`.
7. If access breaks again after restoring plugins, run the recovery SQL again and reactivate plugins one by one.

## First Suspect Plugins

- `wordfence`
- `hostinger`
- `hostinger-easy-onboarding`
- `litespeed-cache`
- `solutionhub-api`
- `surerank`

## Cloudflare / Hostinger Cache

- Purge Cloudflare after origin cache is fixed.
- Disable any Cache Everything rule for logged-in/admin sessions.
- Bypass cache for `/wp-admin/*`, `/wp-login.php`, `/wp-json/*`, preview URLs, and requests with WordPress logged-in cookies.
- Clear Hostinger cache and LiteSpeed cache from hPanel if available.

## Public Verification

Run:

```bash
curl -s https://khokan.me/ | grep -Ei "wpadminbar|admin-bar|khokanuzzamankhokan@gmail.com|Sign Up|Login"
curl -sI https://khokan.me/
```

Expected:

- No admin bar markup.
- No account email.
- No `Sign Up` / `Login` old-header buttons.
- Cache headers should not keep stale HTML after the new homepage deploy.
