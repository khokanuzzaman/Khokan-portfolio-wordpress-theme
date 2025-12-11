<?php
/**
 * Theme footer template.
 */

$footer_brand = function_exists('khokan_get_theme_text')
    ? khokan_get_theme_text('khokan_brand_title', get_bloginfo('name'))
    : get_bloginfo('name');
$footer_tagline = function_exists('khokan_get_theme_text')
    ? khokan_get_theme_text('khokan_hero_tagline', 'Senior Flutter & React Native Mobile Engineer', ['Senior Mobile App Developer'])
    : get_theme_mod('khokan_hero_tagline', 'Senior Flutter & React Native Mobile Engineer');
$footer_links = function_exists('khokan_get_landing_nav_items') ? khokan_get_landing_nav_items() : [];
?>
    <footer class="site-footer" aria-label="Site footer">
        <div class="container site-footer__inner">
            <div class="site-footer__meta">
                <p class="site-footer__title"><?php echo esc_html($footer_brand); ?></p>
                <p class="site-footer__tagline"><?php echo esc_html($footer_tagline); ?></p>
            </div>
            <?php if (!empty($footer_links)) : ?>
                <nav class="site-footer__nav-wrap" aria-label="Footer navigation">
                    <ul class="site-footer__nav">
                        <?php foreach ($footer_links as $item) : ?>
                            <li>
                                <a href="<?php echo esc_url($item['href']); ?>">
                                    <?php echo esc_html($item['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            <p class="site-footer__legal">
                &copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html($footer_brand); ?>. All rights reserved.
            </p>
        </div>
    </footer>
    </div>
<?php wp_footer(); ?>
</body>
</html>
