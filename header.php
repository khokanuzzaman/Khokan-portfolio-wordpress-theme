<?php
/**
 * Theme header template.
 */

$brand_title = function_exists('khokan_get_theme_text')
    ? khokan_get_theme_text('khokan_brand_title', 'Md Khokanuzzaman', ['Khokan Dev Studio'])
    : get_theme_mod('khokan_brand_title', 'Md Khokanuzzaman');
$brand_tagline = function_exists('khokan_get_theme_text')
    ? khokan_get_theme_text('khokan_brand_tagline', 'Senior Mobile App Developer', ['Mobile • Flutter • React Native'])
    : get_theme_mod('khokan_brand_tagline', 'Senior Mobile App Developer');
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

$header_nav_items = function_exists('khokan_get_landing_nav_items') ? khokan_get_landing_nav_items() : [];
$header_cv_text = function_exists('khokan_get_theme_text')
    ? khokan_get_theme_text('khokan_cv_text', 'Download CV')
    : get_theme_mod('khokan_cv_text', 'Download CV');
$header_cv_link = get_theme_mod('khokan_cv_link', get_template_directory_uri() . '/assets/cv/Resume_khokan.pdf');
$header_menu = wp_nav_menu([
    'theme_location' => 'header-menu',
    'container' => false,
    'menu_class' => 'hero-menu',
    'fallback_cb' => false,
    'echo' => false,
    'depth' => 2,
]);

if (!$header_menu && $header_nav_items) {
    $fallback_menu_items = '';
    foreach ($header_nav_items as $item) {
        $fallback_menu_items .= sprintf(
            '<li class="%s"><a href="%s"%s>%s</a></li>',
            !empty($item['is_current']) ? 'current-menu-item' : '',
            esc_url($item['href']),
            !empty($item['is_current']) ? ' aria-current="page"' : '',
            esc_html($item['label'])
        );
    }
    $header_menu = '<ul class="hero-menu">' . $fallback_menu_items . '</ul>';
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
            <div class="hero-top__actions">
                <nav class="hero-nav" aria-label="Primary menu">
                    <?php echo $header_menu; ?>
                </nav>
                <?php if (!empty($header_cv_link)) : ?>
                    <a class="ghost-btn hero-header-cta" href="<?php echo esc_url($header_cv_link); ?>" target="_blank" rel="noopener noreferrer">
                        <?php echo esc_html($header_cv_text); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>
