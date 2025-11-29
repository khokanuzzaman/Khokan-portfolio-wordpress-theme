<?php
/**
 * Khokan Portfolio Theme setup.
 */

add_action('after_setup_theme', function () {
    load_theme_textdomain('wp-theme-khokan', get_template_directory() . '/languages');
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo');
    add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('automatic-feed-links');
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style(
        'khokan-fonts',
        'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'khokan-style',
        get_stylesheet_uri(),
        ['khokan-fonts'],
        filemtime(get_template_directory() . '/style.css')
    );
});

/**
 * Register a Project custom post type so projects can be added from WP admin.
 */
function khokan_register_projects_cpt()
{
    register_post_type('khokan_project', [
        'labels' => [
            'name' => 'Projects',
            'singular_name' => 'Project',
            'add_new_item' => 'Add New Project',
            'edit_item' => 'Edit Project',
        ],
        'public' => true,
        'menu_icon' => 'dashicons-portfolio',
        'supports' => ['title', 'editor', 'thumbnail'],
        'show_in_rest' => true,
        'has_archive' => true,
        'rewrite' => ['slug' => 'projects'],
    ]);
}
add_action('init', 'khokan_register_projects_cpt');

/**
 * Register meta for projects (CTA text, CTA URL, accent color).
 */
function khokan_register_project_meta()
{
    register_post_meta('khokan_project', '_khokan_project_cta', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('khokan_project', '_khokan_project_link', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    register_post_meta('khokan_project', '_khokan_project_accent', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
}
add_action('init', 'khokan_register_project_meta');

/**
 * Project meta box UI.
 */
function khokan_add_project_meta_boxes()
{
    add_meta_box(
        'khokan_project_meta',
        'Project Details',
        'khokan_project_meta_box_html',
        'khokan_project',
        'normal',
        'default'
    );
}
add_action('add_meta_boxes', 'khokan_add_project_meta_boxes');

function khokan_project_meta_box_html($post)
{
    wp_nonce_field('khokan_project_meta_nonce', 'khokan_project_meta_nonce');
    $cta = get_post_meta($post->ID, '_khokan_project_cta', true);
    $link = get_post_meta($post->ID, '_khokan_project_link', true);
    $accent = get_post_meta($post->ID, '_khokan_project_accent', true);
    $accent_options = [
        'teal' => 'Teal',
        'blue' => 'Blue',
        'indigo' => 'Indigo',
    ];
    ?>
    <p>
        <label for="khokan_project_cta"><strong>CTA Text</strong></label><br>
        <input type="text" id="khokan_project_cta" name="khokan_project_cta" class="widefat"
               value="<?php echo esc_attr($cta); ?>" placeholder="View Project">
    </p>
    <p>
        <label for="khokan_project_link"><strong>CTA Link</strong></label><br>
        <input type="url" id="khokan_project_link" name="khokan_project_link" class="widefat"
               value="<?php echo esc_attr($link); ?>" placeholder="https://example.com">
    </p>
    <p>
        <label for="khokan_project_accent"><strong>Accent</strong></label><br>
        <select id="khokan_project_accent" name="khokan_project_accent" class="widefat">
            <?php foreach ($accent_options as $value => $label) : ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($accent ?: 'teal', $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function khokan_save_project_meta($post_id)
{
    if (!isset($_POST['khokan_project_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['khokan_project_meta_nonce'])), 'khokan_project_meta_nonce')) {
        return;
    }

    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['khokan_project_cta'])) {
        update_post_meta($post_id, '_khokan_project_cta', sanitize_text_field(wp_unslash($_POST['khokan_project_cta'])));
    }

    if (isset($_POST['khokan_project_link'])) {
        update_post_meta($post_id, '_khokan_project_link', esc_url_raw(wp_unslash($_POST['khokan_project_link'])));
    }

    if (isset($_POST['khokan_project_accent'])) {
        update_post_meta($post_id, '_khokan_project_accent', sanitize_text_field(wp_unslash($_POST['khokan_project_accent'])));
    }
}
add_action('save_post_khokan_project', 'khokan_save_project_meta');

/**
 * Theme Customizer settings for editable content.
 */
function khokan_sanitize_textarea($value)
{
    return sanitize_textarea_field($value);
}

function khokan_customize_register($wp_customize)
{
    $wp_customize->add_section('khokan_content', [
        'title' => 'Khokan Theme Content',
        'priority' => 30,
    ]);

    $wp_customize->add_setting('khokan_brand_title', [
        'default' => 'Khokan Dev Studio',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_brand_title', [
        'label' => 'Brand Title',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_brand_tagline', [
        'default' => 'Mobile • Flutter • React Native',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_brand_tagline', [
        'label' => 'Brand Tagline',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_tagline', [
        'default' => 'Senior Mobile App Developer | Flutter & React Native Expert',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hero_tagline', [
        'label' => 'Hero Tagline',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_subline', [
        'default' => 'Building Scalable iOS & Android Apps for 6+ Years',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_hero_subline', [
        'label' => 'Hero Subline',
        'section' => 'khokan_content',
        'type' => 'textarea',
    ]);

    $wp_customize->add_setting('khokan_hero_cta_text', [
        'default' => 'Get in Touch / Hire Me',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hero_cta_text', [
        'label' => 'Hero Button Text',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_project_card_cta', [
        'default' => 'View Project',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_project_card_cta', [
        'label' => 'Project Card Button Text',
        'section' => 'khokan_content',
        'type' => 'text',
        'description' => 'Default CTA text for project cards when a project-specific CTA is not set.',
    ]);

    $wp_customize->add_setting('khokan_projects_footer_cta', [
        'default' => 'See More Projects',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_projects_footer_cta', [
        'label' => 'Projects Footer Button Text',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_cta_link', [
        'default' => 'mailto:avkhokanuzzaman@gmail.com',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('khokan_hero_cta_link', [
        'label' => 'Hero Button Link',
        'section' => 'khokan_content',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('khokan_seo_title', [
        'default' => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_seo_title', [
        'label' => 'SEO Title',
        'section' => 'khokan_content',
        'type' => 'text',
        'description' => 'Used for Open Graph & Twitter preview. Defaults to Site Title.',
    ]);

    $wp_customize->add_setting('khokan_seo_description', [
        'default' => get_bloginfo('description'),
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_seo_description', [
        'label' => 'SEO Description',
        'section' => 'khokan_content',
        'type' => 'textarea',
        'description' => 'Shown in meta description and social shares. Defaults to Site Tagline.',
    ]);

    $wp_customize->add_setting('khokan_seo_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_seo_image', [
        'label' => 'SEO / Social Share Image',
        'section' => 'khokan_content',
        'mime_type' => 'image',
        'description' => 'Recommended 1200x630. Falls back to hero image.',
    ]));

    $wp_customize->add_setting('khokan_cv_text', [
        'default' => 'Download CV',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_cv_text', [
        'label' => 'CV Button Text',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_cv_link', [
        'default' => get_template_directory_uri() . '/assets/cv/Resume_khokan.pdf',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('khokan_cv_link', [
        'label' => 'CV Download Link (PDF or Drive URL)',
        'section' => 'khokan_content',
        'type' => 'url',
        'description' => 'Paste the URL to your CV/resume file. The button will use the download attribute.',
    ]);

    $wp_customize->add_setting('khokan_hero_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_hero_image', [
        'label' => 'Hero Image',
        'section' => 'khokan_content',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('khokan_about_text', [
        'default' => 'I\'m Md Khokanuzzanierkan, a software engineer specializing in Flutter, React Native, and cross-platform mobile apps. Over 6+ years, I\'ve built & shipped healthcare and commerce apps used by',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_about_text', [
        'label' => 'About Text',
        'section' => 'khokan_content',
        'type' => 'textarea',
    ]);

    $wp_customize->add_setting('khokan_tech_list', [
        'default' => "Flutter, React Native, Android, iOS\nState management: Riverpod, Redux, Bloc\nBackend: Node.js, NestJS, Firebase\nDevOps: CI/CD, Play Console, iOS release pipeline",
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_tech_list', [
        'label' => 'Tech List (one per line)',
        'section' => 'khokan_content',
        'type' => 'textarea',
    ]);

    $wp_customize->add_setting('khokan_contact_title', [
        'default' => 'Contact & Lead Generation',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_contact_title', [
        'label' => 'Contact Section Title',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_contact_button_text', [
        'default' => 'Send Message',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_contact_button_text', [
        'label' => 'Contact Button Text',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_contact_email', [
        'default' => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ]);
    $wp_customize->add_control('khokan_contact_email', [
        'label' => 'Contact Email (form recipient)',
        'section' => 'khokan_content',
        'type' => 'email',
    ]);

    $wp_customize->add_setting('khokan_projects_title', [
        'default' => 'Projects',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_projects_title', [
        'label' => 'Projects Section Title',
        'section' => 'khokan_content',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_projects_intro', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_projects_intro', [
        'label' => 'Projects Intro (optional)',
        'section' => 'khokan_content',
        'type' => 'textarea',
    ]);

    $social_controls = [
        'khokan_social_facebook' => 'Facebook URL',
        'khokan_social_twitter' => 'Twitter/X URL',
        'khokan_social_linkedin' => 'LinkedIn URL',
        'khokan_social_instagram' => 'Instagram URL',
        'khokan_social_youtube' => 'YouTube URL',
        'khokan_social_whatsapp' => 'WhatsApp URL (e.g., https://wa.me/123456789)',
        'khokan_social_telegram' => 'Telegram URL (e.g., https://t.me/username)',
    ];

    foreach ($social_controls as $setting => $label) {
        $wp_customize->add_setting($setting, [
            'default' => '',
            'sanitize_callback' => 'esc_url_raw',
        ]);
        $wp_customize->add_control($setting, [
            'label' => $label,
            'section' => 'khokan_content',
            'type' => 'url',
        ]);
    }
}
add_action('customize_register', 'khokan_customize_register');

/**
 * Helpers used in templates.
 */
function khokan_get_tech_list()
{
    $raw = get_theme_mod('khokan_tech_list', '');
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $raw)));
    return $lines ?: [
        'Flutter, React Native, Android, iOS',
        'State management: Riverpod, Redux, Bloc',
        'Backend: Node.js, NestJS, Firebase',
        'DevOps: CI/CD, Play Console, iOS release pipeline',
    ];
}

function khokan_get_projects()
{
    $default_cta = get_theme_mod('khokan_project_card_cta', 'View Project');

    $query = new WP_Query([
        'post_type' => 'khokan_project',
        'posts_per_page' => -1,
        'orderby' => [
            'menu_order' => 'ASC',
            'date' => 'DESC',
        ],
        'post_status' => 'publish',
    ]);

    $projects = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $accent = get_post_meta(get_the_ID(), '_khokan_project_accent', true) ?: 'teal';
            if (!in_array($accent, ['teal', 'blue', 'indigo'], true)) {
                $accent = 'teal';
            }

            $image = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '';
            $project_cta = get_post_meta(get_the_ID(), '_khokan_project_cta', true) ?: $default_cta;

            $projects[] = [
                'title' => get_the_title(),
                'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30, '...'),
                'cta' => $project_cta,
                'link' => get_post_meta(get_the_ID(), '_khokan_project_link', true) ?: '#',
                'accent' => $accent,
                'image' => $image,
            ];
        }
        wp_reset_postdata();
    }

    if (!$projects) {
        $projects = [
            [
                'title' => 'JOTNO – For Patient',
                'description' => 'Patient-facing telemedicine app in React Native with instant doctor chat, prescriptions, and follow-ups.',
                'cta' => $default_cta,
                'link' => 'https://play.google.com/store/apps/details?id=sqh.jotno.patient&hl=en',
                'accent' => 'teal',
                'image' => get_template_directory_uri() . '/assets/img/jotno-logo.png',
            ],
            [
                'title' => 'Jotno – Telemedicine Platform',
                'description' => 'Built the doctor-side Flutter app for instant patient communication and digital prescriptions.',
                'cta' => $default_cta,
                'link' => 'https://play.google.com/store/apps/details?id=sqh.jotno.doctor&hl=en',
                'accent' => 'teal',
                'image' => get_template_directory_uri() . '/assets/img/jotno-doctor.png',
            ],
            [
                'title' => 'Confidence Reseller',
                'description' => 'Flutter app for sales agent distributors with 500+ downloads.',
                'cta' => $default_cta,
                'link' => 'https://play.google.com/store/apps/details?id=com.confidenceresellerbd.app&hl=en',
                'accent' => 'blue',
                'image' => get_template_directory_uri() . '/assets/img/confidence-reseller.png',
            ],
        ];
    }

    return $projects;
}

$GLOBALS['khokan_contact_feedback'] = null;

function khokan_handle_contact_form()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['khokan_contact_submit'])) {
        return;
    }

    if (
        !isset($_POST['khokan_contact_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['khokan_contact_nonce'])), 'khokan_contact_nonce')
    ) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Security check failed. Please try again.',
        ];
        return;
    }

    $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
    $email = isset($_POST['email']) ? sanitize_email(wp_unslash($_POST['email'])) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field(wp_unslash($_POST['message'])) : '';

    if (!$message) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Please add a message before sending.',
        ];
        return;
    }

    $recipient = get_theme_mod('khokan_contact_email', get_option('admin_email'));
    if (!$recipient) {
        $recipient = get_option('admin_email');
    }

    $subject = sprintf('[Khokan Portfolio] Message from %s', $name ?: 'Website visitor');
    $headers = [];
    if ($email) {
        $headers[] = 'Reply-To: ' . $email;
    }

    $body_lines = [
        'Name: ' . ($name ?: 'N/A'),
        'Email: ' . ($email ?: 'N/A'),
        '',
        'Message:',
        $message,
    ];

    $sent = wp_mail($recipient, $subject, implode("\n", $body_lines), $headers);

    $GLOBALS['khokan_contact_feedback'] = [
        'status' => $sent ? 'success' : 'error',
        'message' => $sent ? 'Thanks, your message was sent.' : 'Sorry, your message could not be sent right now.',
    ];
}
add_action('init', 'khokan_handle_contact_form');

function khokan_get_contact_feedback()
{
    return $GLOBALS['khokan_contact_feedback'] ?? null;
}

/**
 * Output SEO meta tags and social previews.
 */
function khokan_output_meta_tags()
{
    if (is_admin()) {
        return;
    }

    $title = get_theme_mod('khokan_seo_title', get_bloginfo('name'));
    $description = get_theme_mod('khokan_seo_description', get_bloginfo('description'));

    if (!$description) {
        $about_text = get_theme_mod('khokan_about_text', '');
        if ($about_text) {
            $description = wp_strip_all_tags($about_text);
        } else {
            $description = 'Portfolio of Md Khokanuzzaman – Senior Mobile App Developer (Flutter & React Native) building scalable iOS and Android apps.';
        }
    }

    $fallback_image = get_template_directory_uri() . '/screenshot.png';

    $seo_image_id = get_theme_mod('khokan_seo_image');
    $image = $seo_image_id ? wp_get_attachment_image_url($seo_image_id, 'large') : $fallback_image;
    $image_width = 1200;
    $image_height = 630;
    $image_type = 'image/png';

    if ($seo_image_id) {
        $image_meta = wp_get_attachment_metadata($seo_image_id);
        if (!empty($image_meta['width'])) {
            $image_width = (int) $image_meta['width'];
        }
        if (!empty($image_meta['height'])) {
            $image_height = (int) $image_meta['height'];
        }
        $mime = get_post_mime_type($seo_image_id);
        if (!empty($mime)) {
            $image_type = $mime;
        }
    }

    $url = home_url('/');
    $locale = str_replace('_', '-', get_locale());

    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description) {
        echo '<meta property="og:description" content="' . esc_attr($description) . '">' . "\n";
    }
    if ($image) {
        echo '<meta property="og:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:image:secure_url" content="' . esc_url($image) . '">' . "\n";
        echo '<meta property="og:image:width" content="' . esc_attr($image_width) . '">' . "\n";
        echo '<meta property="og:image:height" content="' . esc_attr($image_height) . '">' . "\n";
        echo '<meta property="og:image:type" content="' . esc_attr($image_type) . '">' . "\n";
        echo '<meta property="og:image:alt" content="' . esc_attr($title) . '">' . "\n";
    }
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($title) . '">' . "\n";
    if ($description) {
        echo '<meta name="twitter:description" content="' . esc_attr($description) . '">' . "\n";
    }
    if ($image) {
        echo '<meta name="twitter:image" content="' . esc_url($image) . '">' . "\n";
        echo '<meta name="twitter:image:alt" content="' . esc_attr($title) . '">' . "\n";
    }
    echo '<meta name="theme-color" content="#040b24">' . "\n";
}
add_action('wp_head', 'khokan_output_meta_tags', 1);
