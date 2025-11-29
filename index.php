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

$projects = khokan_get_projects();
$tech_list = khokan_get_tech_list();
$contact_feedback = khokan_get_contact_feedback();

$brand_title = get_theme_mod('khokan_brand_title', 'Khokan Dev Studio');
$brand_tagline = get_theme_mod('khokan_brand_tagline', 'Mobile • Flutter • React Native');
$brand_initial = strtoupper(substr($brand_title, 0, 1));
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

$skill_planets = [
    [
        'name' => 'Flutter',
        'class' => 'flutter',
        'orbit' => 240,
        'duration' => 8.5,
        'size' => 62,
        'icon' => get_template_directory_uri() . '/assets/img/flutter-logo.png',
    ],
    [
        'name' => 'React Native',
        'class' => 'react',
        'orbit' => 300,
        'duration' => 9.5,
        'size' => 58,
        'icon' => get_template_directory_uri() . '/assets/img/react.png',
    ],
    [
        'name' => 'Android',
        'class' => 'android',
        'orbit' => 360,
        'duration' => 7.5,
        'size' => 64,
        'icon' => get_template_directory_uri() . '/assets/img/android.png',
    ],
    [
        'name' => 'iOS',
        'class' => 'ios',
        'orbit' => 430,
        'duration' => 10,
        'size' => 56,
        'icon' => get_template_directory_uri() . '/assets/img/apple.png',
    ],
];

$about_text = get_theme_mod(
    'khokan_about_text',
    'I\'m Md Khokanuzzaman, a software engineer specializing in Flutter, React Native, and cross-platform mobile apps. Over 6+ years, I\'ve built & shipped healthcare and commerce apps used by'
);

$contact_title = get_theme_mod('khokan_contact_title', 'Contact & Lead Generation');
$contact_button_text = get_theme_mod('khokan_contact_button_text', 'Send Message');
$contact_email = get_theme_mod('khokan_contact_email', get_option('admin_email'));

$projects_title = get_theme_mod('khokan_projects_title', 'Projects');
$projects_intro = get_theme_mod('khokan_projects_intro', '');

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
    <header class="section hero">
        <div class="container hero-top">
            <a class="brand" href="<?php echo esc_url(home_url('/')); ?>">
                <span class="brand-emblem" aria-hidden="true">
                    <span class="brand-glow"></span>
                    <span class="brand-letter"><?php echo esc_html($brand_initial); ?></span>
                </span>
                <span class="brand-meta">
                    <span class="brand-name"><?php echo esc_html($brand_title); ?></span>
                    <span class="brand-tagline"><?php echo esc_html($brand_tagline); ?></span>
                </span>
            </a>
        </div>
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
                            <img src="<?php echo esc_url($hero_img); ?>" alt="Portrait of Khokanuzzanierkan">
                        </div>
                    </div>

                    <?php foreach ($skill_planets as $planet) : ?>
                        <div
                            class="orbit orbit-<?php echo esc_attr($planet['class']); ?>"
                            style="<?php echo esc_attr(sprintf(
                                '--orbit-size:%dpx; --orbit-duration:%ss; --planet-size:%dpx;',
                                $planet['orbit'],
                                $planet['duration'],
                                $planet['size']
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
    </header>

    <main>
        <section class="section about">
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

        <section class="section projects">
            <div class="container">
                <h2><?php echo esc_html($projects_title); ?></h2>
                <?php if (!empty($projects_intro)) : ?>
                    <p class="projects-intro"><?php echo esc_html($projects_intro); ?></p>
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
                            <a class="ghost-btn" href="<?php echo esc_url($project['link'] ?? '#'); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html($project['cta']); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="projects-footer">
                    <a class="primary-btn" href="<?php echo esc_url(home_url('/projects/')); ?>">
                        <?php echo esc_html($projects_footer_cta); ?>
                    </a>
                </div>
            </div>
        </section>

        <section class="section contact">
            <div class="container contact-grid">
                <div>
                    <h2><?php echo esc_html($contact_title); ?></h2>
                    <form class="contact-form" method="post">
                        <?php if ($contact_feedback) : ?>
                            <div class="form-feedback <?php echo esc_attr($contact_feedback['status']); ?>">
                                <?php echo esc_html($contact_feedback['message']); ?>
                            </div>
                        <?php endif; ?>
                        <?php wp_nonce_field('khokan_contact_nonce', 'khokan_contact_nonce'); ?>
                        <input type="hidden" name="khokan_contact_submit" value="1">
                        <div class="input-row">
                            <input type="text" placeholder="Name" name="name" value="<?php echo isset($_POST['name']) ? esc_attr(wp_unslash($_POST['name'])) : ''; ?>">
                            <input type="email" placeholder="Email" name="email" value="<?php echo isset($_POST['email']) ? esc_attr(wp_unslash($_POST['email'])) : ''; ?>">
                        </div>
                        <textarea rows="3" placeholder="Project Details" name="message"><?php echo isset($_POST['message']) ? esc_textarea(wp_unslash($_POST['message'])) : ''; ?></textarea>
                        <button type="submit" class="primary-btn"><?php echo esc_html($contact_button_text); ?></button>
                    </form>
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
    </main>
</div>
<?php wp_footer(); ?>
</body>
</html>
