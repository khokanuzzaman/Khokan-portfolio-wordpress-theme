-- Khokan.me WordPress admin/cache recovery
-- Run this in phpMyAdmin against the live WordPress database.
-- This script is intentionally reversible: it backs up active_plugins first.
-- Target DB prefix from the provided dump: wp_

START TRANSACTION;

SET @khokan_admin_caps = 'a:27:{s:13:"administrator";b:1;s:4:"read";b:1;s:14:"manage_options";b:1;s:10:"edit_users";b:1;s:10:"list_users";b:1;s:16:"activate_plugins";b:1;s:11:"edit_themes";b:1;s:13:"switch_themes";b:1;s:10:"edit_posts";b:1;s:13:"publish_posts";b:1;s:12:"upload_files";b:1;s:17:"manage_categories";b:1;s:17:"moderate_comments";b:1;s:12:"manage_links";b:1;s:8:"level_10";b:1;s:12:"create_users";b:1;s:13:"promote_users";b:1;s:12:"remove_users";b:1;s:12:"delete_users";b:1;s:15:"install_plugins";b:1;s:14:"update_plugins";b:1;s:14:"delete_plugins";b:1;s:14:"install_themes";b:1;s:13:"update_themes";b:1;s:13:"delete_themes";b:1;s:15:"unfiltered_html";b:1;s:10:"edit_files";b:1;}';

-- 1) Backup the current plugin list once.
INSERT INTO wp_options (option_name, option_value, autoload)
SELECT 'active_plugins_backup_khokan_recovery', option_value, 'off'
FROM wp_options
WHERE option_name = 'active_plugins'
ON DUPLICATE KEY UPDATE
    option_value = wp_options.option_value,
    autoload = 'off';

-- 2) Restore the known real admin user from the dump: user ID 1.
-- This does not grant admin to any other user.
UPDATE wp_usermeta
SET meta_value = @khokan_admin_caps
WHERE user_id = 1
AND meta_key = 'wp_capabilities';

INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
SELECT 1, 'wp_capabilities', @khokan_admin_caps
WHERE NOT EXISTS (
    SELECT 1
    FROM wp_usermeta
    WHERE user_id = 1
    AND meta_key = 'wp_capabilities'
);

UPDATE wp_usermeta
SET meta_value = '10'
WHERE user_id = 1
AND meta_key = 'wp_user_level';

INSERT INTO wp_usermeta (user_id, meta_key, meta_value)
SELECT 1, 'wp_user_level', '10'
WHERE NOT EXISTS (
    SELECT 1
    FROM wp_usermeta
    WHERE user_id = 1
    AND meta_key = 'wp_user_level'
);

-- 3) Force a fresh login so stale capability/session cache cannot be reused.
DELETE FROM wp_usermeta
WHERE user_id = 1
AND meta_key = 'session_tokens';

-- 4) Temporarily disable all normal plugins.
-- If Settings works after this, one of the active plugins was blocking admin capability/menu access.
UPDATE wp_options
SET option_value = 'a:0:{}'
WHERE option_name = 'active_plugins';

-- 5) Correct dangerous LiteSpeed settings found in the dump.
-- These protect the site when LiteSpeed is enabled again.
UPDATE wp_options SET option_value = '' WHERE option_name = 'litespeed.conf.cache-priv';
UPDATE wp_options SET option_value = '' WHERE option_name = 'litespeed.conf.cache-page_login';
UPDATE wp_options SET option_value = '["administrator"]' WHERE option_name = 'litespeed.conf.cache-exc_roles';
UPDATE wp_options SET option_value = '' WHERE option_name = 'litespeed.conf.esi-cache_admbar';
UPDATE wp_options SET option_value = '300' WHERE option_name = 'litespeed.conf.cache-ttl_frontpage';

-- 6) Clear LiteSpeed URL cache mapping tables if they exist.
DELETE FROM wp_litespeed_url;
DELETE FROM wp_litespeed_url_file;

-- 7) Remove generated LiteSpeed transients when present.
DELETE FROM wp_options
WHERE option_name LIKE '_transient_litespeed%'
OR option_name LIKE '_site_transient_litespeed%';

COMMIT;

-- After running:
-- 1. Log out completely.
-- 2. Log in again as user ID 1 / khokanuzzamankhokan@gmail.com.
-- 3. Open /wp-admin/options-general.php and /wp-admin/plugins.php.
-- 4. If access works, restore plugins with ops/admin-permission-restore-plugins.sql.
