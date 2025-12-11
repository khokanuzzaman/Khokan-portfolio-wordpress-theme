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
        $platform = get_post_meta(get_the_ID(), '_khokan_project_platform', true);
        $duration = get_post_meta(get_the_ID(), '_khokan_project_duration', true);
        $stack_raw = get_post_meta(get_the_ID(), '_khokan_project_stack', true);
        $stack = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $stack_raw)));
        $result = get_post_meta(get_the_ID(), '_khokan_project_result', true);
        $responsibilities_raw = get_post_meta(get_the_ID(), '_khokan_project_responsibilities', true);
        $responsibilities = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,|;/', (string) $responsibilities_raw)));
        $secondary_cta = get_post_meta(get_the_ID(), '_khokan_project_secondary_cta', true);
        $secondary_link = get_post_meta(get_the_ID(), '_khokan_project_secondary_link', true);
        $featured = (bool) get_post_meta(get_the_ID(), '_khokan_project_featured', true);
        $tags = wp_get_post_terms(get_the_ID(), 'khokan_project_tag', ['fields' => 'names']);
        $image = get_the_post_thumbnail_url(get_the_ID(), 'large');
        $project = function_exists('khokan_enrich_project_data') ? khokan_enrich_project_data([
            'title' => get_the_title(),
            'description' => get_the_excerpt(),
            'link' => $link,
            'cta' => $cta,
            'platform' => $platform,
            'role' => $role,
            'duration' => $duration,
            'stack' => $stack,
            'responsibilities' => $responsibilities,
            'result' => $result,
            'secondary_cta' => $secondary_cta,
            'secondary_link' => $secondary_link,
            'featured' => $featured,
            'image' => $image,
        ]) : [];
        if ($project) {
            $link = $project['link'] ?? $link;
            $cta = $project['cta'] ?? $cta;
            $platform = $project['platform'] ?? $platform;
            $role = $project['role'] ?? $role;
            $stack = $project['stack'] ?? $stack;
            $responsibilities = $project['responsibilities'] ?? $responsibilities;
            $result = $project['result'] ?? $result;
            $secondary_cta = $project['secondary_cta'] ?? $secondary_cta;
            $secondary_link = $project['secondary_link'] ?? $secondary_link;
            $featured = !empty($project['featured']);
            $image = $project['image'] ?? $image;
        }
        ?>
        <main class="section project-single">
            <div class="container project-single__hero">
                <div>
                    <?php if ($featured) : ?>
                        <span class="meta-chip">Featured</span>
                    <?php endif; ?>
                    <h1><?php the_title(); ?></h1>
                    <?php if ($platform || $role || $duration) : ?>
                        <p class="project-meta">
                            <?php if ($platform) : ?><span class="meta-chip"><?php echo esc_html($platform); ?></span><?php endif; ?>
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
                        <a class="primary-btn" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($cta); ?></a>
                        <?php if ($secondary_cta && $secondary_link) : ?>
                            <a class="ghost-btn" href="<?php echo esc_url($secondary_link); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html($secondary_cta); ?></a>
                        <?php endif; ?>
                    </div>
                    <?php if ($responsibilities) : ?>
                        <div class="project-detail-block">
                            <h2>Key Responsibilities</h2>
                            <ul class="project-responsibilities">
                                <?php foreach ($responsibilities as $item) : ?>
                                    <li><?php echo esc_html($item); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
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
