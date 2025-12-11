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
        </div>
    </header>
