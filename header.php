<?php
/**
 * Theme header template.
 */

$brand_title = get_theme_mod('khokan_brand_title', 'Khokan Dev Studio');
$brand_tagline = get_theme_mod('khokan_brand_tagline', 'Mobile • Flutter • React Native');
$brand_logo = '';
$brand_initial = strtoupper(substr($brand_title, 0, 1));

if (is_page_template('page-job-ready-course.php') && function_exists('jrc_get_course_options')) {
    $jrc_options = jrc_get_course_options();
    if (array_key_exists('header_brand_title', $jrc_options)) {
        $brand_title = (string) $jrc_options['header_brand_title'];
    }
    if (array_key_exists('header_brand_tagline', $jrc_options)) {
        $brand_tagline = (string) $jrc_options['header_brand_tagline'];
    }
    if (array_key_exists('header_brand_logo', $jrc_options)) {
        $brand_logo = (string) $jrc_options['header_brand_logo'];
    }
    $brand_initial = strtoupper(substr($brand_title, 0, 1));
}

$header_menu = wp_nav_menu([
    'theme_location' => 'header-menu',
    'container' => false,
    'menu_class' => 'hero-menu',
    'fallback_cb' => false,
    'echo' => false,
    'depth' => 2,
]);

if (!$header_menu) {
    $fallback_links = [
        ['href' => home_url('/#top'), 'label' => 'Home'],
        ['href' => home_url('/#about'), 'label' => 'About'],
        ['href' => home_url('/#expertise'), 'label' => 'Expertise'],
        ['href' => home_url('/#services'), 'label' => 'Services'],
        ['href' => home_url('/#projects'), 'label' => 'Projects'],
        ['href' => home_url('/#contact'), 'label' => 'Contact'],
    ];

    $menu_items = '';
    foreach ($fallback_links as $link) {
        $menu_items .= sprintf(
            '<li><a href="%s">%s</a></li>',
            esc_url($link['href']),
            esc_html($link['label'])
        );
    }

    $header_menu = '<ul class="hero-menu">' . $menu_items . '</ul>';
}

$auth_primary = null;
$auth_secondary = null;
if (function_exists('jrc_get_auth_page_url')) {
    $selected_course_for_auth = function_exists('jrc_get_requested_course_key') ? jrc_get_requested_course_key() : '';
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $requires_verification = function_exists('jrc_user_requires_email_verification') && jrc_user_requires_email_verification($current_user);
        $can_manage_course = function_exists('jrc_current_user_can_manage_course') && jrc_current_user_can_manage_course();

        if ($requires_verification) {
            $auth_primary = [
                'label' => 'Verify Email',
                'url' => jrc_get_auth_page_url('verify', $selected_course_for_auth),
                'class' => 'primary-btn',
            ];
        } elseif ($can_manage_course) {
            $auth_primary = [
                'label' => 'WP Admin',
                'url' => admin_url('/'),
                'class' => 'primary-btn',
            ];
        } else {
            $auth_primary = [
                'label' => 'My Dashboard',
                'url' => function_exists('jrc_get_student_dashboard_url') ? jrc_get_student_dashboard_url() : home_url('/'),
                'class' => 'primary-btn',
            ];
        }

        $auth_secondary = [
            'label' => 'Logout',
            'url' => function_exists('jrc_get_logout_redirect_url') ? jrc_get_logout_redirect_url($selected_course_for_auth) : home_url('/'),
            'class' => 'secondary-btn',
            'type' => 'logout',
        ];
    } else {
        $auth_primary = [
            'label' => 'Sign Up',
            'url' => jrc_get_auth_page_url('signup', $selected_course_for_auth),
            'class' => 'primary-btn',
        ];
        $auth_secondary = [
            'label' => 'Login',
            'url' => jrc_get_auth_page_url('login', $selected_course_for_auth),
            'class' => 'secondary-btn',
        ];
    }
}
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="page">
    <div class="page-glow"></div>
    <header class="site-header">
        <div class="container hero-top">
            <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
                <span class="brand-emblem" aria-hidden="true">
                    <span class="brand-glow"></span>
                    <?php if ($brand_logo !== '') : ?>
                        <img class="brand-logo" src="<?php echo esc_url($brand_logo); ?>" alt="">
                    <?php else : ?>
                        <span class="brand-letter"><?php echo esc_html($brand_initial); ?></span>
                    <?php endif; ?>
                </span>
                <span class="brand-meta">
                    <span class="brand-name"><?php echo esc_html($brand_title); ?></span>
                    <span class="brand-tagline"><?php echo esc_html($brand_tagline); ?></span>
                </span>
            </a>
            <nav class="hero-nav" aria-label="Primary menu">
                <?php echo $header_menu; ?>
            </nav>
            <?php if ($auth_primary || $auth_secondary) : ?>
                <div class="hero-auth">
                    <?php if ($auth_primary) : ?>
                        <a class="<?php echo esc_attr($auth_primary['class']); ?>" href="<?php echo esc_url($auth_primary['url']); ?>">
                            <?php echo esc_html($auth_primary['label']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if ($auth_secondary) : ?>
                        <?php if (($auth_secondary['type'] ?? '') === 'logout' && function_exists('jrc_render_logout_form')) : ?>
                            <?php echo jrc_render_logout_form($auth_secondary['url'], $auth_secondary['label'], $auth_secondary['class'], 'hero-auth__form'); ?>
                        <?php else : ?>
                            <a class="<?php echo esc_attr($auth_secondary['class']); ?>" href="<?php echo esc_url($auth_secondary['url']); ?>">
                                <?php echo esc_html($auth_secondary['label']); ?>
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </header>
