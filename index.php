<?php
/**
 * Main template for the Khokan Portfolio theme.
 */

function khokan_random_duration($min, $max)
{
    return number_format(mt_rand($min * 10, $max * 10) / 10, 1, '.', '');
}

function khokan_random_distance($min, $max)
{
    return mt_rand($min, $max);
}

$animation = [
    'float_duration' => khokan_random_duration(7, 11),
    'glow_duration' => khokan_random_duration(5, 9),
    'ring_duration' => khokan_random_duration(12, 18),
    'shadow_duration' => khokan_random_duration(6, 10),
    'float_x' => khokan_random_distance(8, 16),
    'float_y' => khokan_random_distance(10, 18),
];

$projects_data = khokan_get_projects();
$projects = $projects_data['items'] ?? [];
$projects_pagination = $projects_data['pagination'] ?? ['mode' => 'all', 'current' => 1, 'total' => 1, 'has_more' => false];
$project_filters = $projects_data['filters'] ?? [];
$active_project_tag = $projects_data['active_tag'] ?? '';
$tech_list = khokan_get_tech_list();
$contact_feedback = khokan_get_contact_feedback();
$hobby_cards = khokan_get_hobbies_items();
$hobby_projects = khokan_get_hobby_projects();

$brand_title = get_theme_mod('khokan_brand_title', 'Khokan Dev Studio');
$hero_tagline = get_theme_mod('khokan_hero_tagline', 'Senior Mobile App Developer | Flutter & React Native Expert');
$hero_subline = get_theme_mod('khokan_hero_subline', 'Building Scalable iOS & Android Apps for 6+ Years');
$hero_cta_text = get_theme_mod('khokan_hero_cta_text', 'Get in Touch / Hire Me');
$cv_text = get_theme_mod('khokan_cv_text', 'Download CV');
$projects_footer_cta = get_theme_mod('khokan_projects_footer_cta', 'See More Projects');
$hero_cta_link = get_theme_mod('khokan_hero_cta_link', 'mailto:khokanuzzamankhokan@gmail.com');
$cv_link = get_theme_mod('khokan_cv_link', get_template_directory_uri() . '/assets/cv/Resume_khokan.pdf');
$whatsapp_link = get_theme_mod('khokan_social_whatsapp', '');
$telegram_link = get_theme_mod('khokan_social_telegram', '');
$hero_image_id = get_theme_mod('khokan_hero_image');
$hero_img = $hero_image_id ? wp_get_attachment_image_url($hero_image_id, 'large') : get_template_directory_uri() . '/assets/img/user-img.png';
$skills_center_image_id = get_theme_mod('khokan_skills_center_image');
$skills_center_img = $skills_center_image_id ? wp_get_attachment_image_url($skills_center_image_id, 'large') : $hero_img;
$skills_center_size = (int) get_theme_mod('khokan_skills_center_size', 78);
$skills_center_size = max(30, min(120, $skills_center_size));
$hero_alignment = get_theme_mod('khokan_hero_alignment', 'left');
$hero_bg_type = get_theme_mod('khokan_hero_bg_type', 'default');
$hero_bg_color = sanitize_hex_color(get_theme_mod('khokan_hero_bg_color', '#050d2c')) ?: '#050d2c';
$hero_bg_color2 = sanitize_hex_color(get_theme_mod('khokan_hero_bg_color2', '#0b1240')) ?: '#0b1240';
$hero_bg_image_id = get_theme_mod('khokan_hero_bg_image');
$hero_bg_image = $hero_bg_image_id ? wp_get_attachment_image_url($hero_bg_image_id, 'large') : '';

$skill_planets = function_exists('khokan_get_skill_items') ? khokan_get_skill_items() : [];

$about_enabled = get_theme_mod('khokan_about_enabled', 1);
$about_text = get_theme_mod(
    'khokan_about_text',
    'I\'m Md Khokanuzzaman, a software engineer specializing in Flutter, React Native, and cross-platform mobile apps. Over 6+ years, I\'ve built & shipped healthcare and commerce apps used by'
);

$contact_title = get_theme_mod('khokan_contact_title', 'Contact & Lead Generation');
$contact_button_text = get_theme_mod('khokan_contact_button_text', 'Send Message');
$contact_email = get_theme_mod('khokan_contact_email', get_option('admin_email'));
$contact_enabled = get_theme_mod('khokan_contact_enabled', 1);

$services_title = get_theme_mod('khokan_services_title', 'My Services');
$services_description = get_theme_mod('khokan_services_description', '');
$services_items = function_exists('khokan_get_services_items') ? khokan_get_services_items() : [];
$services_enabled = get_theme_mod('khokan_services_enabled', 1);

$expertise_title = get_theme_mod('khokan_expertise_title', 'My Areas of Expertise');
$expertise_description = get_theme_mod('khokan_expertise_description', '');
$expertise_items = function_exists('khokan_get_expertise_items') ? khokan_get_expertise_items() : [];
$expertise_enabled = get_theme_mod('khokan_expertise_enabled', 1);

$projects_title = get_theme_mod('khokan_projects_title', 'Projects');
$projects_intro = get_theme_mod('khokan_projects_intro', '');
$projects_enabled = get_theme_mod('khokan_projects_enabled', 1);
$hobbies_enabled = get_theme_mod('khokan_hobbies_enabled', 1);
$hobbies_title = get_theme_mod('khokan_hobbies_title', 'My Hobbies & Web Craft');
$hobbies_subtitle = get_theme_mod('khokan_hobbies_subtitle', 'I lead with mobile (Flutter & React Native), but I genuinely enjoy building for the web. What started as weekend tinkering grew into production dashboards and sites, and it now helps me design stronger mobile systems, APIs, dashboards, and admin panels.');
$hobbies_outro = get_theme_mod('khokan_hobbies_outro', 'Sometimes hobbies turn into strengths - web development is one of mine.');
$hobby_projects_title = get_theme_mod('khokan_hobby_projects_title', 'Web Projects Built from Passion');

$social_icons = [
    [
        'name' => 'Facebook',
        'href' => get_theme_mod('khokan_social_facebook', ''),
        'path' => 'M17 3h4V0h-4c-2.8 0-5 2.2-5 5v3H8v4h4v8h4v-8h3l1-4h-4V5c0-.6.4-1 1-1z',
    ],
    [
        'name' => 'Twitter',
        'href' => get_theme_mod('khokan_social_twitter', ''),
        'path' => 'M24 4.6c-.9.4-1.8.6-2.8.8A4.9 4.9 0 0 0 23.3 3a9.7 9.7 0 0 1-3.1 1.2 4.8 4.8 0 0 0-8.2 4.4 13.7 13.7 0 0 1-10-5.1A4.8 4.8 0 0 0 3 9.8a4.9 4.9 0 0 1-2.2-.6v.1a4.8 4.8 0 0 0 3.9 4.7 4.9 4.9 0 0 1-2.1.1 4.8 4.8 0 0 0 4.5 3.4A9.7 9.7 0 0 1 0 19.5 13.7 13.7 0 0 0 7.4 21c9 0 13.9-7.4 13.9-13.9v-.6A9.6 9.6 0 0 0 24 4.6z',
    ],
    [
        'name' => 'LinkedIn',
        'href' => get_theme_mod('khokan_social_linkedin', ''),
        'path' => 'M5.4 8.5h3.6V21H5.4zM7.2 3a2.1 2.1 0 1 1 0 4.1 2.1 2.1 0 0 1 0-4.1zM13 8.5h3.4v1.7h.1a3.7 3.7 0 0 1 3.3-1.8c3.5 0 4.2 2.3 4.2 5.2V21H20V14c0-1.7 0-3.8-2.3-3.8-2.4 0-2.7 1.8-2.7 3.7V21h-4V8.5z',
    ],
    [
        'name' => 'Instagram',
        'href' => get_theme_mod('khokan_social_instagram', ''),
        'path' => 'M7 0h10a7 7 0 0 1 7 7v10a7 7 0 0 1-7 7H7a7 7 0 0 1-7-7V7a7 7 0 0 1 7-7zm0 2C4.2 2 2 4.2 2 7v10c0 2.8 2.2 5 5 5h10c2.8 0 5-2.2 5-5V7c0-2.8-2.2-5-5-5H7zm12.8 3.2a1.4 1.4 0 1 1-2.8 0 1.4 1.4 0 0 1 2.8 0zM12 7a5 5 0 1 1 0 10 5 5 0 0 1 0-10zm0 2.1a2.9 2.9 0 1 0 0 5.8 2.9 2.9 0 0 0 0-5.8z',
    ],
    [
        'name' => 'YouTube',
        'href' => get_theme_mod('khokan_social_youtube', ''),
        'path' => 'M23.5 6.2a2.9 2.9 0 0 0-2-2c-1.7-.5-8.5-.5-8.5-.5s-6.8 0-8.5.5a2.9 2.9 0 0 0-2 2C2 8 2 12 2 12s0 4 .5 5.8a2.9 2.9 0 0 0 2 2c1.7.5 8.5.5 8.5.5s6.8 0 8.5-.5a2.9 2.9 0 0 0 2-2c.5-1.8.5-5.8.5-5.8s0-4-.5-5.8zM10 15.5v-7L16 12z',
    ],
    [
        'name' => 'WhatsApp',
        'href' => get_theme_mod('khokan_social_whatsapp', ''),
        'path' => 'M17.5 6.5a5.5 5.5 0 0 0-9.6 3.5 5.4 5.4 0 0 0 .8 3l.1.2-.6 2.2 2.3-.6.2.1a5.5 5.5 0 0 0 7.9-4.9 5.5 5.5 0 0 0-1.1-3.5zm-5.5-4.5a8 8 0 0 1 6.9 12.1l1 3.7-3.8-1A8 8 0 0 1 8 16.8L4 18l1.1-4A8 8 0 0 1 12 2zM9.2 8.4c.1-.2.3-.2.4-.2h.3c.1 0 .2 0 .3.2l.5 1c.1.1.1.2 0 .3l-.2.3-.2.2c-.1 0-.1.1 0 .2.1.1.5.8 1.2 1.3.9.7 1.5.9 1.6.8l.3-.3.2-.2c.1-.1.2-.1.3 0l1.1.5c.1 0 .2.1.2.2l.2.8c0 .1 0 .2-.1.3l-.3.3c-.3.3-.7.3-1.2.2-.4-.1-1-.3-1.8-.8a8.9 8.9 0 0 1-2.5-2.2c-.1-.2-.5-.7-.6-1.3-.1-.6.1-1 .2-1.1z',
    ],
    [
        'name' => 'Telegram',
        'href' => get_theme_mod('khokan_social_telegram', ''),
        'path' => 'M9.95 14.56 9.7 18.62c.4 0 .57-.17.78-.37l1.87-1.8 3.88 2.84c.71.4 1.22.19 1.41-.66l2.55-11.97c.23-.98-.36-1.37-1.04-1.13L2.43 10.6c-1 .39-.98.95-.17 1.2l4.6 1.44 10.66-6.7c.5-.33.95-.15.58.18z',
    ],
];

$social_icons = array_values(array_filter($social_icons, function ($icon) {
    return !empty($icon['href']) && $icon['href'] !== '#';
}));

get_header();

$hero_classes = ['section', 'hero'];
if ($hero_alignment === 'center') {
    $hero_classes[] = 'hero-align-center';
}

$hero_style = '';
if ($hero_bg_type === 'solid') {
    $hero_style = 'background:' . $hero_bg_color . ';';
} elseif ($hero_bg_type === 'gradient') {
    $hero_style = 'background:linear-gradient(135deg, ' . $hero_bg_color . ' 0%, ' . $hero_bg_color2 . ' 100%);';
} elseif ($hero_bg_type === 'image' && $hero_bg_image) {
    $hero_style = 'background:linear-gradient(135deg, rgba(5,13,44,0.8) 0%, rgba(11,18,64,0.8) 45%, rgba(5,10,36,0.8) 100%), url(' . esc_url($hero_bg_image) . ') center/cover no-repeat;';
}
?>
<section id="top" class="<?php echo esc_attr(implode(' ', $hero_classes)); ?>" <?php echo $hero_style ? 'style="' . esc_attr($hero_style) . '"' : ''; ?>>
    <div class="container hero-grid">
            <div class="orbit-wrap">
                <div
                    class="orbit-stage"
                    style="<?php echo esc_attr(sprintf(
                        '--float-duration:%ss; --glow-duration:%ss; --ring-duration:%ss; --shadow-duration:%ss; --float-x:%spx; --float-y:%spx;',
                        $animation['float_duration'],
                        $animation['glow_duration'],
                        $animation['ring_duration'],
                        $animation['shadow_duration'],
                        $animation['float_x'],
                        $animation['float_y']
                    )); ?>"
                >
                    <div class="starfield layer-1"></div>
                    <div class="starfield layer-2"></div>

                    <div class="sun">
                        <div class="sun-core">
                            <img src="<?php echo esc_url($skills_center_img); ?>" alt="<?php echo esc_attr($brand_title); ?>">
                        </div>
                    </div>

                    <?php foreach ($skill_planets as $planet) : ?>
                        <div
                            class="orbit orbit-<?php echo esc_attr($planet['class']); ?>"
                            style="<?php echo esc_attr(sprintf(
                                '--orbit-size:%dpx; --orbit-duration:%ss; --planet-size:%dpx; --orbit-delay:%ss;',
                                $planet['orbit'],
                                $planet['duration'],
                                $planet['size'],
                                isset($planet['delay']) ? $planet['delay'] : 0
                            )); ?>"
                        >
                            <div class="orbit-path"></div>
                            <div class="planet <?php echo esc_attr($planet['class']); ?>">
                                <span class="planet-icon">
                                    <img src="<?php echo esc_url($planet['icon']); ?>" alt="<?php echo esc_attr($planet['name']); ?>">
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="headline hero-copy">
                <p class="tagline"><?php echo esc_html($hero_tagline); ?></p>
                <p class="subline"><?php echo esc_html($hero_subline); ?></p>
                <div class="cta-row">
                    <a class="primary-btn" href="<?php echo esc_url($hero_cta_link ?: '#'); ?>">
                        <?php echo esc_html($hero_cta_text); ?>
                    </a>
                    <a class="secondary-btn" href="<?php echo esc_url($cv_link ?: '#'); ?>" download>
                        <?php echo esc_html($cv_text); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <main>
        <?php if ($about_enabled) : ?>
            <section id="about" class="section about">
                <div class="container">
                    <h2>About</h2>
                    <p class="about-text">
                        <?php echo esc_html($about_text); ?>
                    </p>
                    <ul class="list">
                        <?php foreach ($tech_list as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($expertise_items) && $expertise_enabled) : ?>
            <section id="expertise" class="section expertise">
                <div class="container">
                    <div class="expertise-panel">
                        <div class="section-heading">
                            <h2><?php echo esc_html($expertise_title); ?></h2>
                            <?php if (!empty($expertise_description)) : ?>
                                <p class="section-subtitle"><?php echo esc_html($expertise_description); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="expertise-grid">
                            <?php foreach ($expertise_items as $item) : ?>
                                <?php
                                $tone = isset($item['style']) ? $item['style'] : 'default';
                                $classes = ['expertise-card'];
                                if ($tone === 'accent') {
                                    $classes[] = 'is-accent';
                                } else {
                                    $classes[] = 'is-muted';
                                }
                                ?>
                                <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
                                    <span class="expertise-label"><?php echo esc_html($item['label']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($services_items) && $services_enabled) : ?>
            <section id="services" class="section services">
                <div class="container">
                    <div class="section-heading">
                        <h2><?php echo esc_html($services_title); ?></h2>
                        <?php if (!empty($services_description)) : ?>
                            <p class="section-subtitle"><?php echo esc_html($services_description); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="services-grid">
                        <?php foreach ($services_items as $service) : ?>
                            <div class="service-card">
                                <?php if (!empty($service['icon'])) : ?>
                                    <div class="service-icon">
                                        <img src="<?php echo esc_url($service['icon']); ?>" alt="<?php echo esc_attr($service['title']); ?>">
                                    </div>
                                <?php endif; ?>
                                <h3><?php echo esc_html($service['title']); ?></h3>
                                <?php if (!empty($service['description'])) : ?>
                                    <p class="service-copy"><?php echo esc_html($service['description']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php
        $articles_query = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 4,
            'orderby' => 'comment_count',
            'order' => 'DESC',
            'ignore_sticky_posts' => 1,
        ]);
        $blog_page_id = get_option('page_for_posts');
        $blog_page_link = $blog_page_id ? get_permalink($blog_page_id) : home_url('/blog/');
        ?>
        <?php if ($articles_query->have_posts()) : ?>
            <section id="articles" class="section articles">
                <div class="container">
                    <div class="section-heading">
                        <h2>Popular Tech Articles</h2>
                        <p class="section-subtitle">Highlights from the blog: mobile, Flutter, React Native, and more.</p>
                    </div>
                    <div class="articles-grid">
                        <?php
                        while ($articles_query->have_posts()) :
                            $articles_query->the_post();
                            $article_thumb = get_the_post_thumbnail_url(get_the_ID(), 'medium');
                            $article_cats = get_the_category();
                            ?>
                            <article class="article-card">
                                <a class="article-thumb" href="<?php the_permalink(); ?>">
                                    <?php if ($article_thumb) : ?>
                                        <span class="article-img" style="background-image:url('<?php echo esc_url($article_thumb); ?>');"></span>
                                    <?php else : ?>
                                        <span class="article-img placeholder">✦</span>
                                    <?php endif; ?>
                                </a>
                                <div class="article-meta">
                                    <span class="article-date"><?php echo esc_html(get_the_date()); ?></span>
                                    <?php if (!empty($article_cats)) : ?>
                                        <span class="article-sep">·</span>
                                        <span class="article-cat"><?php echo esc_html($article_cats[0]->name); ?></span>
                                    <?php endif; ?>
                                </div>
                                <h3 class="article-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p class="article-excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18, '…')); ?></p>
                            </article>
                        <?php endwhile; ?>
                        <?php wp_reset_postdata(); ?>
                    </div>
                    <div class="articles-footer">
                        <a class="ghost-btn" href="<?php echo esc_url($blog_page_link); ?>">View All Articles</a>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($projects_enabled) : ?>
            <section id="projects" class="section projects">
                <div class="container">
                    <h2><?php echo esc_html($projects_title); ?></h2>
                    <?php if (!empty($projects_intro)) : ?>
                        <p class="projects-intro"><?php echo esc_html($projects_intro); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($project_filters)) : ?>
                        <div class="project-filters">
                            <a class="filter-chip <?php echo $active_project_tag ? '' : 'active'; ?>" href="<?php echo esc_url(remove_query_arg('project_tag')); ?>">All</a>
                            <?php foreach ($project_filters as $filter) : ?>
                                <a class="filter-chip <?php echo ($active_project_tag === $filter['slug']) ? 'active' : ''; ?>"
                                   href="<?php echo esc_url(add_query_arg('project_tag', $filter['slug'])); ?>">
                                    <?php echo esc_html($filter['name']); ?>
                                    <span class="filter-count"><?php echo (int) $filter['count']; ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <div class="projects-grid">
                        <?php foreach ($projects as $project) : ?>
                            <div class="project-card">
                                <div class="project-icon <?php echo esc_attr($project['accent']); ?>">
                                    <?php if (!empty($project['image'])) : ?>
                                        <img src="<?php echo esc_url($project['image']); ?>" alt="<?php echo esc_attr($project['title']); ?>" class="project-logo">
                                    <?php else : ?>
                                        <span>★</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo esc_html($project['title']); ?></h3>
                                <p><?php echo esc_html($project['description']); ?></p>
                                <?php if (!empty($project['role']) || !empty($project['duration'])) : ?>
                                    <p class="project-meta">
                                        <?php if (!empty($project['role'])) : ?>
                                            <span class="meta-chip"><?php echo esc_html($project['role']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($project['duration'])) : ?>
                                            <span class="meta-chip"><?php echo esc_html($project['duration']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($project['stack'])) : ?>
                                    <div class="project-stack">
                                        <?php foreach ($project['stack'] as $tech) : ?>
                                            <span class="stack-chip"><?php echo esc_html($tech); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($project['result'])) : ?>
                                    <p class="project-result"><?php echo esc_html($project['result']); ?></p>
                                <?php endif; ?>
                                <a class="ghost-btn" href="<?php echo esc_url($project['link'] ?? '#'); ?>" target="_blank" rel="noopener">
                                    <?php echo esc_html($project['cta']); ?>
                                </a>
                                <?php if (!empty($project['secondary_cta']) && !empty($project['secondary_link'])) : ?>
                                    <a class="ghost-btn secondary-cta" href="<?php echo esc_url($project['secondary_link']); ?>" target="_blank" rel="noopener">
                                        <?php echo esc_html($project['secondary_cta']); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (!empty($projects_footer_cta)) : ?>
                        <div class="projects-footer">
                            <?php if ($projects_pagination['mode'] === 'load_more' && $projects_pagination['has_more']) : ?>
                                <a class="primary-btn" href="<?php echo esc_url(add_query_arg('proj_page', $projects_pagination['current'] + 1)); ?>">
                                    Load More
                                </a>
                            <?php elseif ($projects_pagination['mode'] === 'paginate' && $projects_pagination['total'] > 1) : ?>
                                <div class="pagination">
                                    <?php for ($p = 1; $p <= (int) $projects_pagination['total']; $p++) : ?>
                                        <a class="page-btn <?php echo $p === (int) $projects_pagination['current'] ? 'active' : ''; ?>"
                                           href="<?php echo esc_url(add_query_arg('proj_page', $p)); ?>"><?php echo (int) $p; ?></a>
                                    <?php endfor; ?>
                                </div>
                            <?php else : ?>
                                <a class="primary-btn" href="<?php echo esc_url(home_url('/projects/')); ?>">
                                    <?php echo esc_html($projects_footer_cta); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($hobbies_enabled && (!empty($hobby_cards) || !empty($hobby_projects))) : ?>
            <section id="web-hobbies" class="section web-hobbies">
                <div class="container">
                    <div class="hobbies-panel">
                        <div class="section-heading">
                            <h2><?php echo esc_html($hobbies_title); ?></h2>
                            <?php if (!empty($hobbies_subtitle)) : ?>
                                <p class="section-subtitle"><?php echo esc_html($hobbies_subtitle); ?></p>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($hobby_cards)) : ?>
                            <div class="hobby-grid">
                                <?php foreach ($hobby_cards as $card) : ?>
                                    <div class="hobby-card">
                                        <div class="hobby-card__top">
                                            <?php if (!empty($card['label'])) : ?>
                                                <span class="hobby-label"><?php echo esc_html($card['label']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($card['description'])) : ?>
                                            <p class="hobby-card__body">
                                                <?php echo esc_html($card['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($card['tags'])) : ?>
                                            <div class="hobby-tags">
                                                <?php foreach ($card['tags'] as $tag) : ?>
                                                    <span class="hobby-tag"><?php echo esc_html($tag); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($hobby_projects)) : ?>
                            <div class="hobby-projects">
                                <?php if (!empty($hobby_projects_title)) : ?>
                                    <div class="hobby-projects__heading">
                                        <h3><?php echo esc_html($hobby_projects_title); ?></h3>
                                    </div>
                                <?php endif; ?>
                                <div class="hobby-project-grid">
                                    <?php foreach ($hobby_projects as $project) : ?>
                                        <div class="hobby-project-card">
                                            <?php if (!empty($project['link'])) : ?>
                                                <a class="hobby-project-name" href="<?php echo esc_url($project['link']); ?>" target="_blank" rel="noopener">
                                                    <?php echo esc_html($project['name']); ?>
                                                </a>
                                            <?php else : ?>
                                                <div class="hobby-project-name"><?php echo esc_html($project['name']); ?></div>
                                            <?php endif; ?>
                                            <?php if (!empty($project['description'])) : ?>
                                                <p class="hobby-project-desc"><?php echo esc_html($project['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($hobbies_outro)) : ?>
                            <p class="hobby-outro"><?php echo esc_html($hobbies_outro); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <?php if ($contact_enabled) : ?>
            <section id="contact" class="section contact">
                <div class="container contact-grid">
                    <div>
                        <h2><?php echo esc_html($contact_title); ?></h2>
                        <div class="contact-shortcode">
                            <?php echo do_shortcode('[sureforms id="2687"]'); ?>
                        </div>
                    </div>

                    <div class="social-block">
                        <h3>Social Media</h3>
                        <div class="social-icons">
                            <?php foreach ($social_icons as $icon) : ?>
                                <a class="social-btn" href="<?php echo esc_url($icon['href']); ?>" aria-label="<?php echo esc_attr($icon['name']); ?>">
                                    <svg viewBox="0 0 24 24" aria-hidden="true" role="img">
                                        <path d="<?php echo esc_attr($icon['path']); ?>"/>
                                    </svg>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <p class="direct-email">
                            Direct email:<br>
                            <a href="mailto:<?php echo esc_attr($contact_email); ?>"><?php echo esc_html($contact_email); ?></a>
                        </p>
                        <?php if ($whatsapp_link || $telegram_link) : ?>
                            <div class="direct-contact">
                                <h4>Direct Contact</h4>
                                <div class="contact-chips">
                                    <?php if ($whatsapp_link) : ?>
                                        <a class="contact-chip whatsapp" href="<?php echo esc_url($whatsapp_link); ?>" target="_blank" rel="noopener">
                                            <span class="chip-icon">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" role="img"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.198.297-.767.967-.94 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.654-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.074-.148-.669-1.611-.916-2.206-.242-.58-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.262.489 1.694.626.712.227 1.36.195 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"></path><path d="M20.52 3.48C18.24 1.2 15.24 0 12 0 5.37 0 0 5.37 0 12c0 2.115.55 4.177 1.6 6l-1.05 3.84 3.93-1.03C6.29 22.424 9.09 23 12 23c6.63 0 12-5.37 12-12 0-3.24-1.2-6.24-3.48-8.52zM12 21c-2.592 0-5.046-.85-7.093-2.46l-.507-.38-2.33.61.623-2.28-.38-.58C1.434 14.927 1 13.48 1 12 1 6.486 5.486 2 11 2c2.89 0 5.603 1.127 7.64 3.164C20.673 7.2 21.8 9.91 21.8 12.8c0 5.514-4.486 10-9.8 10z"></path></svg>
                                            </span>
                                            <span class="chip-label">WhatsApp</span>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($telegram_link) : ?>
                                        <a class="contact-chip telegram" href="<?php echo esc_url($telegram_link); ?>" target="_blank" rel="noopener">
                                            <span class="chip-icon">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" role="img"><path d="M9.95 14.56 9.7 18.62c.4 0 .57-.17.78-.37l1.87-1.8 3.88 2.84c.71.4 1.22.19 1.41-.66l2.55-11.97c.23-.98-.36-1.37-1.04-1.13L2.43 10.6c-1 .39-.98.95-.17 1.2l4.6 1.44 10.66-6.7c.5-.33.95-.15.58.18z"></path></svg>
                                            </span>
                                            <span class="chip-label">Telegram</span>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        <?php endif; ?>
    </main>
<?php get_footer(); ?>
