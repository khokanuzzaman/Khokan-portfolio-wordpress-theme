-- Restore plugins after admin access is confirmed.
-- Run this only after ops/admin-permission-cache-recovery.sql and a successful admin login test.

START TRANSACTION;

UPDATE wp_options
SET option_value = (
    SELECT backup.option_value
    FROM (
        SELECT option_value
        FROM wp_options
        WHERE option_name = 'active_plugins_backup_khokan_recovery'
        LIMIT 1
    ) AS backup
)
WHERE option_name = 'active_plugins'
AND EXISTS (
    SELECT 1
    FROM (
        SELECT option_id
        FROM wp_options
        WHERE option_name = 'active_plugins_backup_khokan_recovery'
        LIMIT 1
    ) AS backup_exists
);

COMMIT;

-- If Settings/Plugins permission breaks again after this restore, disable plugins again
-- and reactivate them one by one. Start suspects from the dump:
-- wordfence, hostinger, hostinger-easy-onboarding, litespeed-cache, solutionhub-api, surerank.
