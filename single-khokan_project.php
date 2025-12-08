<?php
/**
 * Single Project template.
 */

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $default_cta = get_theme_mod('khokan_project_card_cta', 'View Project');
        $accent = get_post_meta(get_the_ID(), '_khokan_project_accent', true) ?: 'teal';
        if (!in_array($accent, ['teal', 'blue', 'indigo'], true)) {
            $accent = 'teal';
        }
        $cta = get_post_meta(get_the_ID(), '_khokan_project_cta', true) ?: $default_cta;
        $link = get_post_meta(get_the_ID(), '_khokan_project_link', true) ?: '#';
        $role = get_post_meta(get_the_ID(), '_khokan_project_role', true);
        $duration = get_post_meta(get_the_ID(), '_khokan_project_duration', true);
        $stack_raw = get_post_meta(get_the_ID(), '_khokan_project_stack', true);
        $stack = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $stack_raw)));
        $result = get_post_meta(get_the_ID(), '_khokan_project_result', true);
        $secondary_cta = get_post_meta(get_the_ID(), '_khokan_project_secondary_cta', true);
        $secondary_link = get_post_meta(get_the_ID(), '_khokan_project_secondary_link', true);
        $featured = (bool) get_post_meta(get_the_ID(), '_khokan_project_featured', true);
        $tags = wp_get_post_terms(get_the_ID(), 'khokan_project_tag', ['fields' => 'names']);
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        ?>
        <main class="section project-single">
            <div class="container project-single__hero">
                <div>
                    <?php if ($featured) : ?>
                        <span class="meta-chip">Featured</span>
                    <?php endif; ?>
                    <h1><?php the_title(); ?></h1>
                    <?php if ($role || $duration) : ?>
                        <p class="project-meta">
                            <?php if ($role) : ?><span class="meta-chip"><?php echo esc_html($role); ?></span><?php endif; ?>
                            <?php if ($duration) : ?><span class="meta-chip"><?php echo esc_html($duration); ?></span><?php endif; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($stack) : ?>
                        <div class="project-stack">
                            <?php foreach ($stack as $tech) : ?>
                                <span class="stack-chip"><?php echo esc_html($tech); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($result) : ?>
                        <p class="project-result"><?php echo esc_html($result); ?></p>
                    <?php endif; ?>
                    <div class="cta-row">
                        <a class="primary-btn" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener"><?php echo esc_html($cta); ?></a>
                        <?php if ($secondary_cta && $secondary_link) : ?>
                            <a class="ghost-btn" href="<?php echo esc_url($secondary_link); ?>" target="_blank" rel="noopener"><?php echo esc_html($secondary_cta); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($image) : ?>
                    <div class="project-single__image">
                        <img src="<?php echo esc_url($image); ?>" alt="<?php the_title_attribute(); ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div class="container project-single__content">
                <div class="project-content">
                    <?php the_content(); ?>
                    <?php if ($tags) : ?>
                        <p class="project-tags">
                            <?php foreach ($tags as $tag) : ?>
                                <span class="stack-chip"><?php echo esc_html($tag); ?></span>
                            <?php endforeach; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        <?php
    endwhile;
endif;

get_footer();
