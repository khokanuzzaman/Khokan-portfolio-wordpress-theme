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
    register_nav_menus([
        'header-menu' => 'Header Menu',
    ]);
});

// Ensure Application Passwords stay available, even on non-HTTPS local/dev setups.
add_filter('wp_is_application_passwords_available', '__return_true');
add_filter('wp_is_application_passwords_available_for_user', '__return_true', 10, 2);
add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
    if (!$user instanceof WP_User) {
        return $allcaps;
    }

    $needs_app_password_cap = array_intersect(
        ['create_app_password', 'list_app_passwords', 'delete_app_passwords'],
        $caps
    );

    if (!$needs_app_password_cap) {
        return $allcaps;
    }

    $allowed_roles = ['administrator', 'editor'];
    if (array_intersect($allowed_roles, (array) $user->roles)) {
        $allcaps['create_app_password'] = true;
        $allcaps['list_app_passwords'] = true;
        $allcaps['delete_app_passwords'] = true;
    }

    return $allcaps;
}, 10, 4);

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
	
	if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
	}
});

/**
 * Theme Customizer: Job Ready Course settings.
 */
add_action('customize_register', function (WP_Customize_Manager $wp_customize) {
    $wp_customize->add_section('khokan_job_ready_course', [
        'title' => 'Job Ready Course',
        'priority' => 120,
    ]);

    $defaults = jrc_get_default_course_options();
    $options_snapshot = wp_parse_args(get_option('jrc_options', []), $defaults);
    $seat_limit_total = $options_snapshot['hero_card_seat_limit'];
    if (jrc_is_placeholder_value($seat_limit_total) || $seat_limit_total === '') {
        $seat_limit_total = $options_snapshot['details_batch'] ?? '';
    }
    $remaining_seats_snapshot = jrc_get_remaining_seats($seat_limit_total, $options_snapshot['booked_seats'] ?? 0);

    $fields = [
        'hero_title' => ['label' => 'Hero Title'],
        'hero_subtitle' => ['label' => 'Hero Subtitle', 'type' => 'textarea', 'rows' => 2],
        'hero_note' => ['label' => 'Hero Note (extra)', 'type' => 'textarea', 'rows' => 2],
        'cta_apply_label' => ['label' => 'Enroll Button Label'],
        'cta_apply_link' => ['label' => 'Enroll Button Link', 'type' => 'url'],
        'hero_card_start_date' => ['label' => 'Hero Card: Start Date'],
        'hero_card_seat_limit' => ['label' => 'Hero Card: Seat Limit'],
        'hero_card_fee_from' => ['label' => 'Hero Card: Fee From'],
        'hero_card_mode' => ['label' => 'Hero Card: Mode'],
        'audience_title' => ['label' => 'Audience Title'],
        'audience_items' => ['label' => 'Audience Items', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'learning_title' => ['label' => 'Learning Title'],
        'learning_subtitle' => ['label' => 'Learning Subtitle', 'type' => 'textarea', 'rows' => 2],
        'flutter_title' => ['label' => 'Flutter Title'],
        'flutter_items' => ['label' => 'Flutter Items', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'react_title' => ['label' => 'React Title'],
        'react_items' => ['label' => 'React Items', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'ai_track_title' => ['label' => 'AI Track Title'],
        'ai_track_items' => ['label' => 'AI Track Items', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'job_prep_title' => ['label' => 'Job Prep Title'],
        'job_prep_items' => ['label' => 'Job Prep Items', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'ai_section_title' => ['label' => 'AI Section Title'],
        'ai_rules' => ['label' => 'AI Rules', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'ai_good' => ['label' => 'AI Good Examples', 'type' => 'textarea', 'rows' => 4, 'description' => 'One item per line.'],
        'ai_bad' => ['label' => 'AI Bad Examples', 'type' => 'textarea', 'rows' => 3, 'description' => 'One item per line.'],
        'ai_cta_title' => ['label' => 'AI CTA Title'],
        'ai_cta_subtitle' => ['label' => 'AI CTA Subtitle', 'type' => 'textarea', 'rows' => 2],
        'details_title' => ['label' => 'Details Title'],
        'details_duration' => ['label' => 'Duration'],
        'details_days' => ['label' => 'Class Days/Week'],
        'details_mode' => ['label' => 'Mode'],
        'details_language' => ['label' => 'Language'],
        'details_batch' => ['label' => 'Batch Size'],
        'details_start_date' => ['label' => 'Start Date'],
        'details_class_time' => ['label' => 'Class Time'],
        'fee_title' => ['label' => 'Fee Title'],
        'fee_standard' => ['label' => 'Standard Fee'],
        'fee_early' => ['label' => 'Early Bird Fee'],
        'fee_installment' => ['label' => 'Installment Line'],
        'fee_note' => ['label' => 'Fee Note (extra)', 'type' => 'textarea', 'rows' => 2],
        'booked_seats' => ['label' => 'Reserved Seats (Pre-booking)'],
        'remaining_seats' => [
            'label' => 'Remaining Seats (auto)',
            'input_attrs' => ['readonly' => 'readonly'],
            'description' => 'Auto-calculated: Seat Limit - Booked Seats.',
            'default' => (string) $remaining_seats_snapshot,
        ],
        'early_bird_deadline' => ['label' => 'Early Bird Deadline'],
        'laptop_requirement' => ['label' => 'Laptop Requirement'],
        'ai_tools_list' => ['label' => 'AI Tools List'],
        'refund_policy' => ['label' => 'Refund Policy'],
        'foundation_weeks' => ['label' => 'Foundation Weeks'],
        'track_weeks' => ['label' => 'Primary Track Weeks'],
        'interview_weeks' => ['label' => 'Interview Weeks'],
        'project_1' => ['label' => 'Project 1'],
        'project_2' => ['label' => 'Project 2'],
        'project_3' => ['label' => 'Project 3'],
        'project_4' => ['label' => 'Project 4'],
        'project_5' => ['label' => 'Project 5'],
        'mentor_title' => ['label' => 'Mentor Title'],
        'mentor_bio' => ['label' => 'Mentor Bio', 'type' => 'textarea', 'rows' => 3],
        'faq_title' => ['label' => 'FAQ Title'],
        'faq_items' => ['label' => 'FAQ Items', 'type' => 'textarea', 'rows' => 6, 'description' => 'One per line. Format: Question | Answer'],
        'final_title' => ['label' => 'Final CTA Title'],
        'final_subtitle' => ['label' => 'Final CTA Subtitle', 'type' => 'textarea', 'rows' => 2],
    ];

    $priority = 10;
    foreach ($fields as $key => $field) {
        $setting_id = 'jrc_options[' . $key . ']';
        $is_link = str_contains($key, '_link');
        $wp_customize->add_setting($setting_id, [
            'type' => 'option',
            'default' => $field['default'] ?? ($defaults[$key] ?? ''),
            'sanitize_callback' => $is_link ? 'jrc_sanitize_course_link' : 'sanitize_textarea_field',
        ]);

        $control_args = [
            'label' => $field['label'],
            'section' => 'khokan_job_ready_course',
            'settings' => $setting_id,
            'type' => $field['type'] ?? 'text',
            'priority' => $priority,
        ];

        if (!empty($field['description'])) {
            $control_args['description'] = $field['description'];
        }

        $input_attrs = $field['input_attrs'] ?? [];
        if (!empty($field['rows'])) {
            $input_attrs['rows'] = (int) $field['rows'];
        }
        if (!empty($input_attrs)) {
            $control_args['input_attrs'] = $input_attrs;
        }

        $wp_customize->add_control($setting_id, $control_args);
        $priority += 5;
    }
});

add_action('customize_controls_enqueue_scripts', function () {
    wp_enqueue_script(
        'jrc-customizer',
        get_template_directory_uri() . '/assets/js/jrc-customizer.js',
        ['customize-controls', 'jquery'],
        '1.0.0',
        true
    );
});

/**
 * Job Ready Course Options Page.
 */
function jrc_get_default_course_data()
{
    return require __DIR__ . '/data/job-ready-course-content.php';
}

function jrc_get_default_course_options()
{
    $defaults = jrc_get_default_course_data();

    $options = [
        'hero_title' => $defaults['hero']['title'],
        'hero_subtitle' => $defaults['hero']['subtitle'],
        'hero_note' => $defaults['hero']['note'] ?? '',
        'cta_apply_label' => $defaults['hero']['ctas'][0]['label'],
        'cta_apply_link' => $defaults['hero']['ctas'][0]['link'],
        'hero_card_start_date' => $defaults['hero']['card']['Start Date'],
        'hero_card_seat_limit' => $defaults['hero']['card']['Batch Size'],
        'hero_card_fee_from' => $defaults['hero']['card']['Fee From'],
        'hero_card_mode' => $defaults['hero']['card']['Mode'],
        'audience_title' => $defaults['audience']['title'],
        'audience_items' => implode("\n", $defaults['audience']['items']),
        'learning_title' => $defaults['learning']['title'],
        'learning_subtitle' => $defaults['learning']['subtitle'],
        'flutter_title' => $defaults['learning']['tracks'][0]['title'],
        'flutter_items' => implode("\n", $defaults['learning']['tracks'][0]['items']),
        'react_title' => $defaults['learning']['tracks'][1]['title'],
        'react_items' => implode("\n", $defaults['learning']['tracks'][1]['items']),
        'ai_track_title' => $defaults['learning']['tracks'][2]['title'],
        'ai_track_items' => implode("\n", $defaults['learning']['tracks'][2]['items']),
        'job_prep_title' => $defaults['learning']['tracks'][3]['title'],
        'job_prep_items' => implode("\n", $defaults['learning']['tracks'][3]['items']),
        'ai_section_title' => $defaults['ai_usage']['title'],
        'ai_rules' => implode("\n", $defaults['ai_usage']['rules']),
        'ai_good' => implode("\n", $defaults['ai_usage']['good']),
        'ai_bad' => implode("\n", $defaults['ai_usage']['bad']),
        'ai_cta_title' => $defaults['ai_usage']['cta']['title'],
        'ai_cta_subtitle' => $defaults['ai_usage']['cta']['subtitle'],
        'details_title' => $defaults['details']['title'],
        'details_duration' => $defaults['details']['items']['Duration'],
        'details_days' => $defaults['details']['items']['Class Days/Week'],
        'details_mode' => $defaults['details']['items']['Mode'],
        'details_language' => $defaults['details']['items']['Language'],
        'details_batch' => $defaults['details']['items']['Batch Size'],
        'details_start_date' => $defaults['details']['items']['Start Date'],
        'details_class_time' => $defaults['details']['items']['Class Time'],
        'fee_title' => $defaults['fee']['title'],
        'fee_standard' => $defaults['fee']['standard'],
        'fee_early' => $defaults['fee']['early'],
        'fee_installment' => $defaults['fee']['installment'],
        'fee_note' => $defaults['fee']['note'] ?? '',
        'booked_seats' => '0',
        'remaining_seats' => '',
        'early_bird_deadline' => 'Until Ramadan',
        'laptop_requirement' => '',
        'ai_tools_list' => '',
        'refund_policy' => '',
        'foundation_weeks' => '2',
        'track_weeks' => '6',
        'interview_weeks' => '2',
        'project_1' => 'Portfolio mobile app (Flutter)',
        'project_2' => 'Responsive web app (React)',
        'project_3' => 'API integration + auth project',
        'project_4' => 'Deployment + live demo project',
        'project_5' => 'Capstone project with README',
        'mentor_title' => $defaults['mentor']['title'],
        'mentor_bio' => $defaults['mentor']['bio'],
        'faq_title' => $defaults['faq_title'] ?? 'FAQ',
        'faq_items' => implode("\n", array_map(function ($item) {
            return $item['q'] . ' | ' . $item['a'];
        }, $defaults['faq'])),
        'final_title' => $defaults['final_cta']['title'],
        'final_subtitle' => $defaults['final_cta']['subtitle'],
    ];

    if (jrc_is_placeholder_value($options['hero_card_seat_limit'])) {
        $options['hero_card_seat_limit'] = '30';
    }
    if (jrc_is_placeholder_value($options['details_batch'])) {
        $options['details_batch'] = $options['hero_card_seat_limit'];
    }
    if (jrc_is_placeholder_value($options['fee_installment'])) {
        $options['fee_installment'] = 'Installment available';
    }
    $seat_limit_total = $options['hero_card_seat_limit'];
    if (jrc_is_placeholder_value($seat_limit_total) || $seat_limit_total === '') {
        $seat_limit_total = $options['details_batch'];
    }
    $options['remaining_seats'] = (string) jrc_get_remaining_seats($seat_limit_total, $options['booked_seats']);

    $replacements = [
        '{{START_DATE}}' => $options['hero_card_start_date'],
        '{{SEAT_LIMIT}}' => $options['remaining_seats'],
        '{{SEAT_LIMIT_TOTAL}}' => $options['hero_card_seat_limit'],
        '{{SEAT_REMAINING}}' => $options['remaining_seats'],
        '{{EARLY_BIRD_FEE}}' => $options['fee_early'],
        '{{REGULAR_FEE}}' => $options['fee_standard'],
        '{{DELIVERY_MODE}}' => $options['details_mode'],
        '{{CLASS_SCHEDULE}}' => $options['details_days'],
        '{{BATCH_DURATION}}' => $options['details_duration'],
        '{{CLASS_TIME_OPTIONS}}' => $options['details_class_time'],
        '{{EARLY_BIRD_DEADLINE}}' => $options['early_bird_deadline'],
        '{{INSTALLMENT_OPTION}}' => $options['fee_installment'],
        '{{LAPTOP_REQUIREMENT}}' => $options['laptop_requirement'],
        '{{AI_TOOLS_LIST}}' => $options['ai_tools_list'],
        '{{REFUND_POLICY}}' => $options['refund_policy'],
    ];

    $options['faq_items'] = strtr($options['faq_items'], array_filter($replacements, function ($value) {
        return $value !== '' && !jrc_is_placeholder_value($value);
    }));

    return $options;
}

function jrc_lines_to_array($text)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $text);
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $items[] = $line;
    }
    return $items;
}

function jrc_is_placeholder_value($value)
{
    if ($value === null) {
        return false;
    }
    $value = trim((string) $value);
    if ($value === '') {
        return false;
    }
    if (preg_match('/^\{\{[A-Z0-9_]+\}\}$/', $value)) {
        return true;
    }
    if (preg_match('/^\[[A-Z0-9_]+\]$/', $value)) {
        return true;
    }
    if (preg_match('/^\{[A-Z0-9_]+\}$/', $value)) {
        return true;
    }
    return false;
}

function jrc_is_google_form_link($value)
{
    if ($value === null) {
        return false;
    }
    $value = trim((string) $value);
    if ($value === '') {
        return false;
    }
    return (bool) preg_match('/(docs\\.google\\.com\\/forms|forms\\.gle)/i', $value);
}

function jrc_get_remaining_seats($total, $booked)
{
    $total = max(0, (int) $total);
    $booked = max(0, (int) $booked);
    return max(0, $total - $booked);
}

function jrc_sanitize_course_link($value)
{
    $value = esc_url_raw((string) $value);
    if (jrc_is_google_form_link($value)) {
        return '';
    }
    return $value;
}

function jrc_replace_placeholders_recursive($data, array $replacements)
{
    if (empty($replacements)) {
        return $data;
    }

    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = jrc_replace_placeholders_recursive($value, $replacements);
        }
        return $data;
    }

    if (is_string($data)) {
        return strtr($data, $replacements);
    }

    return $data;
}

function jrc_faq_lines_to_array($text, $fallback)
{
    $lines = preg_split('/\r\n|\r|\n/', (string) $text);
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        if (count($parts) < 2) {
            $parts = array_map('trim', explode(' - ', $line, 2));
        }
        if (count($parts) < 2) {
            continue;
        }
        $items[] = ['q' => $parts[0], 'a' => $parts[1]];
    }

    return $items ?: $fallback;
}

function jrc_get_course_options()
{
    $stored = (array) get_option('jrc_options', []);

    $default_options = jrc_get_default_course_options();

    $options = array_merge($default_options, $stored);

    foreach ($default_options as $key => $default_value) {
        if (!array_key_exists($key, $options)) {
            continue;
        }
        if (jrc_is_placeholder_value($options[$key]) && !jrc_is_placeholder_value($default_value)) {
            $options[$key] = $default_value;
        }
    }

    if (jrc_is_google_form_link($options['cta_apply_link'] ?? '')) {
        $options['cta_apply_link'] = $default_options['cta_apply_link'];
    }

    return $options;
}

function jrc_get_course_data()
{
    $defaults = jrc_get_default_course_data();
    $options = jrc_get_course_options();

    $course = $defaults;

    $course['hero'] = [
        'title' => $options['hero_title'],
        'subtitle' => $options['hero_subtitle'],
        'note' => $options['hero_note'],
        'ctas' => [
            [
                'label' => $options['cta_apply_label'],
                'link' => $options['cta_apply_link'],
                'class' => 'primary-btn',
            ],
        ],
        'card' => [
            'Start Date' => $options['hero_card_start_date'],
            'Batch Size' => $options['hero_card_seat_limit'],
            'Fee From' => $options['hero_card_fee_from'],
            'Mode' => $options['hero_card_mode'],
        ],
    ];

    $course['audience'] = [
        'title' => $options['audience_title'],
        'items' => jrc_lines_to_array($options['audience_items']) ?: $defaults['audience']['items'],
    ];

    $course['learning'] = [
        'title' => $options['learning_title'],
        'subtitle' => $options['learning_subtitle'],
        'tracks' => [
            [
                'title' => $options['flutter_title'],
                'items' => jrc_lines_to_array($options['flutter_items']) ?: $defaults['learning']['tracks'][0]['items'],
            ],
            [
                'title' => $options['react_title'],
                'items' => jrc_lines_to_array($options['react_items']) ?: $defaults['learning']['tracks'][1]['items'],
            ],
            [
                'title' => $options['ai_track_title'],
                'items' => jrc_lines_to_array($options['ai_track_items']) ?: $defaults['learning']['tracks'][2]['items'],
            ],
            [
                'title' => $options['job_prep_title'],
                'items' => jrc_lines_to_array($options['job_prep_items']) ?: $defaults['learning']['tracks'][3]['items'],
            ],
        ],
    ];

    $course['ai_usage'] = [
        'title' => $options['ai_section_title'],
        'rules' => jrc_lines_to_array($options['ai_rules']) ?: $defaults['ai_usage']['rules'],
        'good' => jrc_lines_to_array($options['ai_good']) ?: $defaults['ai_usage']['good'],
        'bad' => jrc_lines_to_array($options['ai_bad']) ?: $defaults['ai_usage']['bad'],
        'cta' => [
            'title' => $options['ai_cta_title'],
            'subtitle' => $options['ai_cta_subtitle'],
        ],
    ];

    $course['details'] = [
        'title' => $options['details_title'],
        'items' => [
            'Duration' => $options['details_duration'],
            'Class Days/Week' => $options['details_days'],
            'Mode' => $options['details_mode'],
            'Language' => $options['details_language'],
            'Batch Size' => $options['details_batch'],
            'Start Date' => $options['details_start_date'],
            'Class Time' => $options['details_class_time'],
        ],
    ];

    $course['fee'] = [
        'title' => $options['fee_title'],
        'standard' => $options['fee_standard'],
        'early' => $options['fee_early'],
        'installment' => $options['fee_installment'],
        'note' => $options['fee_note'],
    ];

    $course['mentor'] = [
        'title' => $options['mentor_title'],
        'bio' => $options['mentor_bio'],
    ];

    $course['faq'] = jrc_faq_lines_to_array($options['faq_items'], $defaults['faq']);
    $course['faq_title'] = $options['faq_title'];
    $course['final_cta'] = [
        'title' => $options['final_title'],
        'subtitle' => $options['final_subtitle'],
    ];

    if (empty($course['enrollment']['website']['link'])) {
        $course['enrollment']['website']['link'] = $options['cta_apply_link'];
    }

    $start_date = $options['details_start_date'];
    if (jrc_is_placeholder_value($start_date)) {
        $start_date = $options['hero_card_start_date'];
    }

    $seat_limit_total = $options['hero_card_seat_limit'];
    if (jrc_is_placeholder_value($seat_limit_total) || $seat_limit_total === '') {
        $seat_limit_total = $options['details_batch'];
    }
    $booked_seats = $options['booked_seats'] ?? 0;
    $remaining_seats = jrc_get_remaining_seats($seat_limit_total, $booked_seats);
    $course['hero']['booked_seats'] = (int) $booked_seats;
    $course['hero']['remaining_seats'] = $remaining_seats;

    $replacements = [
        '{{WEBSITE_ENROLL_LINK}}' => $options['cta_apply_link'],
        '{{START_DATE}}' => $start_date,
        '{{SEAT_LIMIT}}' => $remaining_seats,
        '{{SEAT_LIMIT_TOTAL}}' => $seat_limit_total,
        '{{SEAT_REMAINING}}' => $remaining_seats,
        '{{EARLY_BIRD_FEE}}' => $options['fee_early'],
        '{{REGULAR_FEE}}' => $options['fee_standard'],
        '{{DELIVERY_MODE}}' => $options['details_mode'],
        '{{CLASS_SCHEDULE}}' => $options['details_days'],
        '{{BATCH_DURATION}}' => $options['details_duration'],
        '{{CLASS_TIME_OPTIONS}}' => $options['details_class_time'],
        '{{EARLY_BIRD_DEADLINE}}' => $options['early_bird_deadline'] ?? '',
        '{{INSTALLMENT_OPTION}}' => $options['fee_installment'],
        '{{LAPTOP_REQUIREMENT}}' => $options['laptop_requirement'] ?? '',
        '{{AI_TOOLS_LIST}}' => $options['ai_tools_list'] ?? '',
        '{{REFUND_POLICY}}' => $options['refund_policy'] ?? '',
        '{{FOUNDATION_WEEKS}}' => $options['foundation_weeks'] ?? '',
        '{{TRACK_WEEKS}}' => $options['track_weeks'] ?? '',
        '{{INTERVIEW_WEEKS}}' => $options['interview_weeks'] ?? '',
        '{{PROJECT_1}}' => $options['project_1'] ?? '',
        '{{PROJECT_2}}' => $options['project_2'] ?? '',
        '{{PROJECT_3}}' => $options['project_3'] ?? '',
        '{{PROJECT_4}}' => $options['project_4'] ?? '',
        '{{PROJECT_5}}' => $options['project_5'] ?? '',
    ];

    $replacements = array_filter($replacements, function ($value) {
        return $value !== '' && !jrc_is_placeholder_value($value);
    });

    return jrc_replace_placeholders_recursive($course, $replacements);
}

function jrc_sanitize_options($input)
{
    $sanitized = [];
    foreach ((array) $input as $key => $value) {
        if (str_contains($key, '_link')) {
            $sanitized[$key] = jrc_sanitize_course_link($value);
            continue;
        }
        $sanitized[$key] = sanitize_textarea_field($value);
    }
    return $sanitized;
}

add_action('admin_menu', function () {
    add_menu_page(
        'Job Ready Course',
        'Job Ready Course',
        'manage_options',
        'jrc-options',
        'jrc_render_options_page',
        'dashicons-welcome-learn-more',
        62
    );
});

add_action('admin_init', function () {
    register_setting('jrc_options_group', 'jrc_options', [
        'sanitize_callback' => 'jrc_sanitize_options',
    ]);
});

function jrc_render_options_page()
{
    if (!current_user_can('manage_options')) {
        return;
    }
    $options = jrc_get_course_options();
    $seat_limit_total = $options['hero_card_seat_limit'] ?? '';
    if (jrc_is_placeholder_value($seat_limit_total) || $seat_limit_total === '') {
        $seat_limit_total = $options['details_batch'] ?? 0;
    }
    $remaining_seats = jrc_get_remaining_seats($seat_limit_total, $options['booked_seats'] ?? 0);
    ?>
    <div class="wrap">
        <h1>Job Ready Course Options</h1>
        <form method="post" action="options.php">
            <?php settings_fields('jrc_options_group'); ?>
            <table class="form-table" role="presentation">
                <tr><th scope="row">Hero Title</th><td><input class="regular-text" type="text" name="jrc_options[hero_title]" value="<?php echo esc_attr($options['hero_title']); ?>"></td></tr>
                <tr><th scope="row">Hero Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[hero_subtitle]"><?php echo esc_textarea($options['hero_subtitle']); ?></textarea></td></tr>
                <tr><th scope="row">Hero Note (extra)</th><td><textarea class="large-text" rows="2" name="jrc_options[hero_note]"><?php echo esc_textarea($options['hero_note']); ?></textarea></td></tr>
                <tr><th scope="row">Enroll Button Label</th><td><input class="regular-text" type="text" name="jrc_options[cta_apply_label]" value="<?php echo esc_attr($options['cta_apply_label']); ?>"></td></tr>
                <tr><th scope="row">Enroll Button Link</th><td><input class="regular-text" type="url" name="jrc_options[cta_apply_link]" value="<?php echo esc_url($options['cta_apply_link']); ?>"></td></tr>
                <tr><th scope="row">Hero Card: Start Date</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_start_date]" value="<?php echo esc_attr($options['hero_card_start_date']); ?>"></td></tr>
                <tr><th scope="row">Hero Card: Seat Limit</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_seat_limit]" value="<?php echo esc_attr($options['hero_card_seat_limit']); ?>"></td></tr>
                <tr><th scope="row">Reserved Seats (Pre-booking)</th><td><input class="regular-text" type="text" name="jrc_options[booked_seats]" value="<?php echo esc_attr($options['booked_seats']); ?>"></td></tr>
                <tr><th scope="row">Remaining Seats</th><td><input class="regular-text" type="text" value="<?php echo esc_attr($remaining_seats); ?>" readonly></td></tr>
                <tr><th scope="row">Hero Card: Fee From</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_fee_from]" value="<?php echo esc_attr($options['hero_card_fee_from']); ?>"></td></tr>
                <tr><th scope="row">Hero Card: Mode</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_mode]" value="<?php echo esc_attr($options['hero_card_mode']); ?>"></td></tr>

                <tr><th scope="row">Audience Title</th><td><input class="regular-text" type="text" name="jrc_options[audience_title]" value="<?php echo esc_attr($options['audience_title']); ?>"></td></tr>
                <tr><th scope="row">Audience Items</th><td><textarea class="large-text" rows="4" name="jrc_options[audience_items]"><?php echo esc_textarea($options['audience_items']); ?></textarea><p class="description">One item per line.</p></td></tr>

                <tr><th scope="row">Learning Title</th><td><input class="regular-text" type="text" name="jrc_options[learning_title]" value="<?php echo esc_attr($options['learning_title']); ?>"></td></tr>
                <tr><th scope="row">Learning Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[learning_subtitle]"><?php echo esc_textarea($options['learning_subtitle']); ?></textarea></td></tr>
                <tr><th scope="row">Flutter Title</th><td><input class="regular-text" type="text" name="jrc_options[flutter_title]" value="<?php echo esc_attr($options['flutter_title']); ?>"></td></tr>
                <tr><th scope="row">Flutter Items</th><td><textarea class="large-text" rows="4" name="jrc_options[flutter_items]"><?php echo esc_textarea($options['flutter_items']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">React Title</th><td><input class="regular-text" type="text" name="jrc_options[react_title]" value="<?php echo esc_attr($options['react_title']); ?>"></td></tr>
                <tr><th scope="row">React Items</th><td><textarea class="large-text" rows="4" name="jrc_options[react_items]"><?php echo esc_textarea($options['react_items']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">AI Track Title</th><td><input class="regular-text" type="text" name="jrc_options[ai_track_title]" value="<?php echo esc_attr($options['ai_track_title']); ?>"></td></tr>
                <tr><th scope="row">AI Track Items</th><td><textarea class="large-text" rows="4" name="jrc_options[ai_track_items]"><?php echo esc_textarea($options['ai_track_items']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">Job Prep Title</th><td><input class="regular-text" type="text" name="jrc_options[job_prep_title]" value="<?php echo esc_attr($options['job_prep_title']); ?>"></td></tr>
                <tr><th scope="row">Job Prep Items</th><td><textarea class="large-text" rows="4" name="jrc_options[job_prep_items]"><?php echo esc_textarea($options['job_prep_items']); ?></textarea><p class="description">One item per line.</p></td></tr>

                <tr><th scope="row">AI Section Title</th><td><input class="regular-text" type="text" name="jrc_options[ai_section_title]" value="<?php echo esc_attr($options['ai_section_title']); ?>"></td></tr>
                <tr><th scope="row">AI Rules</th><td><textarea class="large-text" rows="4" name="jrc_options[ai_rules]"><?php echo esc_textarea($options['ai_rules']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">AI Good Examples</th><td><textarea class="large-text" rows="4" name="jrc_options[ai_good]"><?php echo esc_textarea($options['ai_good']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">AI Bad Examples</th><td><textarea class="large-text" rows="3" name="jrc_options[ai_bad]"><?php echo esc_textarea($options['ai_bad']); ?></textarea><p class="description">One item per line.</p></td></tr>
                <tr><th scope="row">AI CTA Title</th><td><input class="regular-text" type="text" name="jrc_options[ai_cta_title]" value="<?php echo esc_attr($options['ai_cta_title']); ?>"></td></tr>
                <tr><th scope="row">AI CTA Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[ai_cta_subtitle]"><?php echo esc_textarea($options['ai_cta_subtitle']); ?></textarea></td></tr>

                <tr><th scope="row">Details Title</th><td><input class="regular-text" type="text" name="jrc_options[details_title]" value="<?php echo esc_attr($options['details_title']); ?>"></td></tr>
                <tr><th scope="row">Duration</th><td><input class="regular-text" type="text" name="jrc_options[details_duration]" value="<?php echo esc_attr($options['details_duration']); ?>"></td></tr>
                <tr><th scope="row">Class Days/Week</th><td><input class="regular-text" type="text" name="jrc_options[details_days]" value="<?php echo esc_attr($options['details_days']); ?>"></td></tr>
                <tr><th scope="row">Mode</th><td><input class="regular-text" type="text" name="jrc_options[details_mode]" value="<?php echo esc_attr($options['details_mode']); ?>"></td></tr>
                <tr><th scope="row">Language</th><td><input class="regular-text" type="text" name="jrc_options[details_language]" value="<?php echo esc_attr($options['details_language']); ?>"></td></tr>
                <tr><th scope="row">Batch Size</th><td><input class="regular-text" type="text" name="jrc_options[details_batch]" value="<?php echo esc_attr($options['details_batch']); ?>"></td></tr>
                <tr><th scope="row">Start Date</th><td><input class="regular-text" type="text" name="jrc_options[details_start_date]" value="<?php echo esc_attr($options['details_start_date']); ?>"></td></tr>
                <tr><th scope="row">Class Time</th><td><input class="regular-text" type="text" name="jrc_options[details_class_time]" value="<?php echo esc_attr($options['details_class_time']); ?>"></td></tr>

                <tr><th scope="row">Fee Title</th><td><input class="regular-text" type="text" name="jrc_options[fee_title]" value="<?php echo esc_attr($options['fee_title']); ?>"></td></tr>
                <tr><th scope="row">Standard Fee</th><td><input class="regular-text" type="text" name="jrc_options[fee_standard]" value="<?php echo esc_attr($options['fee_standard']); ?>"></td></tr>
                <tr><th scope="row">Early Bird Fee</th><td><input class="regular-text" type="text" name="jrc_options[fee_early]" value="<?php echo esc_attr($options['fee_early']); ?>"></td></tr>
                <tr><th scope="row">Installment Line</th><td><input class="regular-text" type="text" name="jrc_options[fee_installment]" value="<?php echo esc_attr($options['fee_installment']); ?>"></td></tr>
                <tr><th scope="row">Fee Note (extra)</th><td><textarea class="large-text" rows="2" name="jrc_options[fee_note]"><?php echo esc_textarea($options['fee_note']); ?></textarea></td></tr>
                <tr><th scope="row">Early Bird Deadline</th><td><input class="regular-text" type="text" name="jrc_options[early_bird_deadline]" value="<?php echo esc_attr($options['early_bird_deadline']); ?>"></td></tr>
                <tr><th scope="row">Laptop Requirement</th><td><input class="regular-text" type="text" name="jrc_options[laptop_requirement]" value="<?php echo esc_attr($options['laptop_requirement']); ?>"></td></tr>
                <tr><th scope="row">AI Tools List</th><td><input class="regular-text" type="text" name="jrc_options[ai_tools_list]" value="<?php echo esc_attr($options['ai_tools_list']); ?>"></td></tr>
                <tr><th scope="row">Refund Policy</th><td><input class="regular-text" type="text" name="jrc_options[refund_policy]" value="<?php echo esc_attr($options['refund_policy']); ?>"></td></tr>
                <tr><th scope="row">Foundation Weeks</th><td><input class="regular-text" type="text" name="jrc_options[foundation_weeks]" value="<?php echo esc_attr($options['foundation_weeks']); ?>"></td></tr>
                <tr><th scope="row">Primary Track Weeks</th><td><input class="regular-text" type="text" name="jrc_options[track_weeks]" value="<?php echo esc_attr($options['track_weeks']); ?>"></td></tr>
                <tr><th scope="row">Interview Weeks</th><td><input class="regular-text" type="text" name="jrc_options[interview_weeks]" value="<?php echo esc_attr($options['interview_weeks']); ?>"></td></tr>
                <tr><th scope="row">Project 1</th><td><input class="regular-text" type="text" name="jrc_options[project_1]" value="<?php echo esc_attr($options['project_1']); ?>"></td></tr>
                <tr><th scope="row">Project 2</th><td><input class="regular-text" type="text" name="jrc_options[project_2]" value="<?php echo esc_attr($options['project_2']); ?>"></td></tr>
                <tr><th scope="row">Project 3</th><td><input class="regular-text" type="text" name="jrc_options[project_3]" value="<?php echo esc_attr($options['project_3']); ?>"></td></tr>
                <tr><th scope="row">Project 4</th><td><input class="regular-text" type="text" name="jrc_options[project_4]" value="<?php echo esc_attr($options['project_4']); ?>"></td></tr>
                <tr><th scope="row">Project 5</th><td><input class="regular-text" type="text" name="jrc_options[project_5]" value="<?php echo esc_attr($options['project_5']); ?>"></td></tr>

                <tr><th scope="row">Mentor Title</th><td><input class="regular-text" type="text" name="jrc_options[mentor_title]" value="<?php echo esc_attr($options['mentor_title']); ?>"></td></tr>
                <tr><th scope="row">Mentor Bio</th><td><textarea class="large-text" rows="3" name="jrc_options[mentor_bio]"><?php echo esc_textarea($options['mentor_bio']); ?></textarea></td></tr>

                <tr><th scope="row">FAQ Title</th><td><input class="regular-text" type="text" name="jrc_options[faq_title]" value="<?php echo esc_attr($options['faq_title']); ?>"></td></tr>
                <tr><th scope="row">FAQ Items</th><td><textarea class="large-text" rows="6" name="jrc_options[faq_items]"><?php echo esc_textarea($options['faq_items']); ?></textarea><p class="description">One per line. Format: Question | Answer</p></td></tr>

                <tr><th scope="row">Final CTA Title</th><td><input class="regular-text" type="text" name="jrc_options[final_title]" value="<?php echo esc_attr($options['final_title']); ?>"></td></tr>
                <tr><th scope="row">Final CTA Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[final_subtitle]"><?php echo esc_textarea($options['final_subtitle']); ?></textarea></td></tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

/**
 * Block pattern: Job Ready Course page content.
 */
add_action('init', function () {
    if (!function_exists('register_block_pattern')) {
        return;
    }

    if (function_exists('register_block_pattern_category')) {
        register_block_pattern_category('khokan-pages', ['label' => 'Khokan Pages']);
    }

    $pattern = <<<'HTML'
<!-- wp:group {"className":"section course-hero"} -->
<div class="wp-block-group section course-hero"><!-- wp:group {"className":"container course-hero__grid"} -->
<div class="wp-block-group container course-hero__grid"><!-- wp:group {"className":"course-hero__content"} -->
<div class="wp-block-group course-hero__content"><!-- wp:heading {"level":1,"className":"course-hero__title"} -->
<h1 class="course-hero__title">Job-Ready Course for Last Semester Students &amp; Fresh Graduates</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"course-hero__subtitle"} -->
<p class="course-hero__subtitle">Flutter + React + practical AI use. Beginner-friendly, Bangladesh-focused, budget-friendly.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"course-hero__actions"} -->
<div class="wp-block-buttons course-hero__actions"><!-- wp:button {"className":"primary-btn","linkClassName":"primary-btn"} -->
<div class="wp-block-button primary-btn"><a class="wp-block-button__link primary-btn" href="[WEBSITE_ENROLL_LINK]">Enroll Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-hero__card"} -->
<div class="wp-block-group course-hero__card"><!-- wp:heading {"level":2,"className":"course-hero__card-title"} -->
<h2 class="course-hero__card-title">Quick Info</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"course-hero__list"} -->
<ul class="course-hero__list"><li>Start Date: [START_DATE]</li><li>Batch Size: [SEAT_LIMIT]</li><li>Fee From: ৳ 6,999 (Early Bird)</li><li>Mode: Online Live</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>Who This Course Is For</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>Last semester students (any dept, but interested in dev)</li><li>Fresh graduates (0–1 year)</li><li>Beginners who want a clear roadmap</li><li>যারা confident না, but start করতে চায় 🙂</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>What You Will Learn</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-subtitle"} -->
<p class="section-subtitle">Four tracks, structured and beginner-first.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"course-grid"} -->
<div class="wp-block-group course-grid"><!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>Flutter (Mobile)</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>Dart basics to real app flow</li><li>UI building with widgets</li><li>State management (simple and practical)</li><li>API connect, auth, and local storage</li><li>Build 2–3 small apps + 1 portfolio app</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>React (Web)</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>React basics + component thinking</li><li>Hooks, state, and routing</li><li>API integration, forms, validations</li><li>Build a responsive web project</li><li>Simple deployment for portfolio</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>AI (Practical &amp; Ethical)</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>ChatGPT/Codex/Cursor/Gemini/Kiro workflow</li><li>Prompting for explanations, not copy-paste</li><li>Debugging help and refactor suggestions</li><li>Learn to ask better questions</li><li>AI as learning partner, not cheating</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>Job Prep</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>GitHub profile setup</li><li>CV + portfolio guidance</li><li>Interview practice (basic)</li><li>LinkedIn tips and project showcase</li><li>How to talk about your projects</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>How We Use AI (Ethical + Practical)</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"course-grid course-grid--two"} -->
<div class="wp-block-group course-grid course-grid--two"><!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>Ethical Rules</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>AI is a learning partner, not a copy-paste machine</li><li>Always understand before using</li><li>No fake projects, no copied code without learning</li><li>We compare AI output with docs and best practices</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-card"} -->
<div class="wp-block-group course-card"><!-- wp:heading {"level":3} -->
<h3>Good Use Examples</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>Ask AI to explain error messages</li><li>Ask for 2–3 solution options, then choose</li><li>Use AI to generate test data or dummy content</li><li>Ask for a code review checklist</li></ul>
<!-- /wp:list -->

<!-- wp:separator {"className":"course-divider"} -->
<hr class="wp-block-separator has-alpha-channel-opacity course-divider"/>
<!-- /wp:separator -->

<!-- wp:heading {"level":3} -->
<h3>Bad Use (We Don’t Do This)</h3>
<!-- /wp:heading -->

<!-- wp:list {"className":"list"} -->
<ul class="list"><li>Copy full project code without understanding</li><li>Submit AI-made code as your own without learning</li></ul>
<!-- /wp:list --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-cta"} -->
<div class="wp-block-group course-cta"><!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading {"level":3} -->
<h3>Want the AI workflow guide?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-subtitle"} -->
<p class="section-subtitle">Start with a free guideline call.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"course-cta__actions"} -->
<div class="wp-block-buttons course-cta__actions"><!-- wp:button {"className":"primary-btn","linkClassName":"primary-btn"} -->
<div class="wp-block-button primary-btn"><a class="wp-block-button__link primary-btn" href="[WEBSITE_ENROLL_LINK]">Enroll Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>Course Details</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"course-table"} -->
<div class="wp-block-group course-table"><!-- wp:paragraph -->
<p>Duration: 10–12 weeks</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Class Days/Week: 2–3 days</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Mode: Online (Live + Practice)</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Language: Bangla + English mix</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Batch Size: [SEAT_LIMIT]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Start Date: [START_DATE]</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Class Time: [CLASS_TIME_OPTIONS]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>Fee (Student-Friendly)</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"course-price"} -->
<div class="wp-block-group course-price"><!-- wp:paragraph -->
<p>Standard Fee: ৳ 9,999</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Early Bird: ৳ 6,999 (limited seats)</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"section-subtitle"} -->
<p class="section-subtitle">Installment available: 2–3 steps, friendly plan</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>Mentor</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"course-mentor"} -->
<p class="course-mentor">আমি একজন full-stack developer ও mentor. I keep things simple and realistic. Goal: help you build confidence, projects, and a real learning habit. No fake promises, just consistent growth. 🙌</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section"} -->
<div class="wp-block-group section course-section"><!-- wp:group {"className":"container"} -->
<div class="wp-block-group container"><!-- wp:heading -->
<h2>FAQ</h2>
<!-- /wp:heading -->

<!-- wp:group {"className":"course-faq"} -->
<div class="wp-block-group course-faq"><!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>আমি একদম beginner. পারবো তো?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Yes. Step-by-step, zero assumed background.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Laptop minimum spec কী লাগবে?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Any decent laptop that can run VS Code + browser.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Flutter + React একসাথে শিখতে পারবো?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Yes, but we keep it structured and paced.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>AI tools use করলে কি cheating হবে?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>No, if used ethically. We focus on learning, not copying.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Class miss করলে কী হবে?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>We share notes and practice tasks. You can catch up.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Job guarantee আছে?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>No guarantee. But you’ll have real projects and guidance.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Installment possible?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Yes, 2–3 steps.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"course-faq__item"} -->
<div class="wp-block-group course-faq__item"><!-- wp:heading {"level":3} -->
<h3>Certificate পাবো?</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Yes, upon completion of projects and attendance.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"section course-section course-final"} -->
<div class="wp-block-group section course-section course-final"><!-- wp:group {"className":"container course-final__inner"} -->
<div class="wp-block-group container course-final__inner"><!-- wp:group -->
<div class="wp-block-group"><!-- wp:heading -->
<h2>Ready to start your job-ready journey?</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"section-subtitle"} -->
<p class="section-subtitle">ছোট ছোট steps নিয়ে শুরু করি—skill তৈরি হবে। 🚀</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons {"className":"course-cta__actions"} -->
<div class="wp-block-buttons course-cta__actions"><!-- wp:button {"className":"primary-btn","linkClassName":"primary-btn"} -->
<div class="wp-block-button primary-btn"><a class="wp-block-button__link primary-btn" href="[WEBSITE_ENROLL_LINK]">Enroll Now</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
HTML;

    register_block_pattern(
        'khokan/job-ready-course',
        [
            'title' => 'Job Ready Course Page',
            'description' => 'Landing page layout for the Job-Ready Course.',
            'categories' => ['khokan-pages'],
            'content' => $pattern,
        ]
    );
});

/**
 * Create a draft Learning Path page on theme activation (if missing).
 */
function khokan_maybe_create_learning_path_page()
{
    $template = 'page-learning-path.php';
    $legacy_template = 'page-clean-architecture.php';
    $title = 'Learning Path';
    $slug = 'learning-path';

    $existing_template = get_posts([
        'post_type' => 'page',
        'post_status' => ['publish', 'draft', 'pending', 'private'],
        'meta_key' => '_wp_page_template',
        'meta_value' => [$template, $legacy_template],
        'meta_compare' => 'IN',
        'fields' => 'ids',
        'numberposts' => -1,
    ]);

    if ($existing_template) {
        foreach ($existing_template as $page_id) {
            if (get_post_meta($page_id, '_wp_page_template', true) === $legacy_template) {
                update_post_meta($page_id, '_wp_page_template', $template);
            }
        }
        return;
    }

    $page = get_page_by_path($slug);
    if (!$page) {
        $page = get_page_by_title($title);
    }

    if ($page instanceof WP_Post) {
        update_post_meta($page->ID, '_wp_page_template', $template);
        return;
    }

    $page_id = wp_insert_post([
        'post_type' => 'page',
        'post_title' => $title,
        'post_name' => $slug,
        'post_status' => 'draft',
    ]);

    if (!is_wp_error($page_id) && $page_id) {
        update_post_meta($page_id, '_wp_page_template', $template);
    }
}
add_action('after_switch_theme', 'khokan_maybe_create_learning_path_page');

/**
 * Learning Path template meta box (choose category).
 */
function khokan_is_learning_path_template($post_id)
{
    $template = get_page_template_slug($post_id);
    if (!$template) {
        $template = get_post_meta($post_id, '_wp_page_template', true);
    }
    return in_array($template, ['page-learning-path.php', 'page-clean-architecture.php'], true);
}

function khokan_add_learning_path_meta_box()
{
    add_meta_box(
        'khokan_learning_path_meta',
        'Learning Path Settings',
        'khokan_learning_path_meta_box_html',
        'page',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'khokan_add_learning_path_meta_box');

function khokan_learning_path_meta_box_html($post)
{
    if (!$post instanceof WP_Post) {
        return;
    }

    $is_learning_path = khokan_is_learning_path_template($post->ID);
    wp_nonce_field('khokan_learning_path_meta_nonce', 'khokan_learning_path_meta_nonce');
    $selected_slug = (string) get_post_meta($post->ID, 'learning_path_category', true);
    $categories = get_categories([
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);
    ?>
    <p>
        <label for="learning-path-category"><strong>Series Category</strong></label>
        <select id="learning-path-category" name="learning_path_category" style="width:100%;">
            <option value="">Use page slug</option>
            <?php foreach ($categories as $category) : ?>
                <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($selected_slug, $category->slug); ?>>
                    <?php echo esc_html($category->name); ?> (<?php echo (int) $category->count; ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <p class="description">
        Select a category to power this learning path. Leave blank to use the page slug.
        <?php if (!$is_learning_path) : ?>
            <br>Note: This setting is used when the Learning Path template is selected.
        <?php endif; ?>
    </p>
    <?php
}

function khokan_save_learning_path_meta_box($post_id)
{
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['khokan_learning_path_meta_nonce'])) {
        return;
    }

    if (!wp_verify_nonce($_POST['khokan_learning_path_meta_nonce'], 'khokan_learning_path_meta_nonce')) {
        return;
    }

    if (!current_user_can('edit_page', $post_id)) {
        return;
    }

    if (isset($_POST['learning_path_category'])) {
        $slug = sanitize_title(wp_unslash($_POST['learning_path_category']));
        if ($slug === '') {
            delete_post_meta($post_id, 'learning_path_category');
        } else {
            update_post_meta($post_id, 'learning_path_category', $slug);
        }
    }
}
add_action('save_post_page', 'khokan_save_learning_path_meta_box');

/**
 * Register Learning Path meta for REST/block editor compatibility.
 */
function khokan_register_learning_path_meta()
{
    register_post_meta('page', 'learning_path_category', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => function () {
            return current_user_can('edit_pages');
        },
    ]);
}
add_action('init', 'khokan_register_learning_path_meta');

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

    register_taxonomy('khokan_project_tag', 'khokan_project', [
        'label' => 'Project Tags',
        'public' => true,
        'show_in_rest' => true,
        'hierarchical' => false,
        'rewrite' => ['slug' => 'project-tag'],
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

    register_post_meta('khokan_project', '_khokan_project_role', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('khokan_project', '_khokan_project_duration', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('khokan_project', '_khokan_project_stack', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);

    register_post_meta('khokan_project', '_khokan_project_result', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);

    register_post_meta('khokan_project', '_khokan_project_secondary_cta', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'sanitize_text_field',
    ]);

    register_post_meta('khokan_project', '_khokan_project_secondary_link', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'sanitize_callback' => 'esc_url_raw',
    ]);

    register_post_meta('khokan_project', '_khokan_project_featured', [
        'show_in_rest' => true,
        'single' => true,
        'type' => 'boolean',
        'sanitize_callback' => 'rest_sanitize_boolean',
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
    $role = get_post_meta($post->ID, '_khokan_project_role', true);
    $duration = get_post_meta($post->ID, '_khokan_project_duration', true);
    $stack = get_post_meta($post->ID, '_khokan_project_stack', true);
    $result = get_post_meta($post->ID, '_khokan_project_result', true);
    $secondary_cta = get_post_meta($post->ID, '_khokan_project_secondary_cta', true);
    $secondary_link = get_post_meta($post->ID, '_khokan_project_secondary_link', true);
    $featured = (bool) get_post_meta($post->ID, '_khokan_project_featured', true);
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
    <p>
        <label for="khokan_project_role"><strong>Role</strong></label><br>
        <input type="text" id="khokan_project_role" name="khokan_project_role" class="widefat"
               value="<?php echo esc_attr($role); ?>" placeholder="Lead Mobile Engineer">
    </p>
    <p>
        <label for="khokan_project_duration"><strong>Duration</strong></label><br>
        <input type="text" id="khokan_project_duration" name="khokan_project_duration" class="widefat"
               value="<?php echo esc_attr($duration); ?>" placeholder="6 months (2023)">
    </p>
    <p>
        <label for="khokan_project_stack"><strong>Tech Stack</strong></label><br>
        <textarea id="khokan_project_stack" name="khokan_project_stack" class="widefat" rows="3" placeholder="Flutter, Firebase, Riverpod"><?php echo esc_textarea($stack); ?></textarea>
    </p>
    <p>
        <label for="khokan_project_result"><strong>Result / Impact</strong></label><br>
        <textarea id="khokan_project_result" name="khokan_project_result" class="widefat" rows="3" placeholder="Increased retention by 22%, 50k+ users."><?php echo esc_textarea($result); ?></textarea>
    </p>
    <p>
        <label for="khokan_project_secondary_cta"><strong>Secondary CTA Text</strong></label><br>
        <input type="text" id="khokan_project_secondary_cta" name="khokan_project_secondary_cta" class="widefat"
               value="<?php echo esc_attr($secondary_cta); ?>" placeholder="Case Study">
    </p>
    <p>
        <label for="khokan_project_secondary_link"><strong>Secondary CTA Link</strong></label><br>
        <input type="url" id="khokan_project_secondary_link" name="khokan_project_secondary_link" class="widefat"
               value="<?php echo esc_attr($secondary_link); ?>" placeholder="https://example.com/case-study">
    </p>
    <p>
        <label><input type="checkbox" name="khokan_project_featured" value="1" <?php checked($featured); ?>> Mark as Featured</label>
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

    if (isset($_POST['khokan_project_role'])) {
        update_post_meta($post_id, '_khokan_project_role', sanitize_text_field(wp_unslash($_POST['khokan_project_role'])));
    }

    if (isset($_POST['khokan_project_duration'])) {
        update_post_meta($post_id, '_khokan_project_duration', sanitize_text_field(wp_unslash($_POST['khokan_project_duration'])));
    }

    if (isset($_POST['khokan_project_stack'])) {
        update_post_meta($post_id, '_khokan_project_stack', khokan_sanitize_textarea(wp_unslash($_POST['khokan_project_stack'])));
    }

    if (isset($_POST['khokan_project_result'])) {
        update_post_meta($post_id, '_khokan_project_result', khokan_sanitize_textarea(wp_unslash($_POST['khokan_project_result'])));
    }

    if (isset($_POST['khokan_project_secondary_cta'])) {
        update_post_meta($post_id, '_khokan_project_secondary_cta', sanitize_text_field(wp_unslash($_POST['khokan_project_secondary_cta'])));
    }

    if (isset($_POST['khokan_project_secondary_link'])) {
        update_post_meta($post_id, '_khokan_project_secondary_link', esc_url_raw(wp_unslash($_POST['khokan_project_secondary_link'])));
    }

    update_post_meta($post_id, '_khokan_project_featured', isset($_POST['khokan_project_featured']) ? 1 : 0);
}
add_action('save_post_khokan_project', 'khokan_save_project_meta');

/**
 * Theme Customizer settings for editable content.
 */
function khokan_sanitize_textarea($value)
{
    return sanitize_textarea_field($value);
}

/**
 * Sanitize multi-line skill list input.
 */
function khokan_sanitize_skills_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $label = sanitize_text_field($parts[0]);
        $icon = isset($parts[1]) ? esc_url_raw($parts[1]) : '';
        $size = '';
        if (isset($parts[2])) {
            $maybe_size = (int) $parts[2];
            if ($maybe_size > 0) {
                // clamp to keep visuals sensible
                $size = max(24, min(160, $maybe_size));
            }
        }

        if ($icon && $size) {
            $clean[] = "{$label}|{$icon}|{$size}";
        } elseif ($icon) {
            $clean[] = "{$label}|{$icon}";
        } else {
            $clean[] = $label;
        }
    }

    return implode("\n", $clean);
}

function khokan_sanitize_font_px($value)
{
    $size = absint($value);
    if (!$size) {
        return '';
    }
    return max(10, min(120, $size));
}

function khokan_sanitize_checkbox($value)
{
    return $value ? 1 : 0;
}

function khokan_sanitize_optional_px_range($value)
{
    $size = absint($value);
    if (!$size) {
        return '';
    }
    return max(10, min(1600, $size));
}

function khokan_sanitize_optional_float($value)
{
    if ($value === '' || $value === null) {
        return '';
    }
    $num = floatval($value);
    if (abs($num) < 0.0001) {
        return '';
    }
    return max(-2000, min(2000, $num));
}

function khokan_sanitize_optional_font_px($value)
{
    $size = absint($value);
    if (!$size) {
        return '';
    }
    return max(10, min(200, $size));
}

function khokan_sanitize_grid_int($value)
{
    $val = absint($value);
    if ($val < 1) {
        return 1;
    }
    if ($val > 6) {
        return 6;
    }
    return $val;
}

function khokan_sanitize_radius_px($value)
{
    $size = absint($value);
    if (!$size) {
        return 0;
    }
    return max(0, min(40, $size));
}

function khokan_sanitize_card_shadow($value)
{
    $val = strtolower(sanitize_text_field($value));
    return in_array($val, ['none', 'soft', 'medium', 'strong'], true) ? $val : 'medium';
}

function khokan_sanitize_projects_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $title = sanitize_text_field($parts[0]);
        $desc = isset($parts[1]) ? sanitize_text_field($parts[1]) : '';
        $link = isset($parts[2]) ? esc_url_raw($parts[2]) : '';
        $accent = isset($parts[3]) ? sanitize_text_field($parts[3]) : '';
        $image = isset($parts[4]) ? esc_url_raw($parts[4]) : '';
        $cta = isset($parts[5]) ? sanitize_text_field($parts[5]) : '';

        $encoded = [
            $title,
            $desc,
            $link,
            $accent,
            $image,
            $cta,
        ];

        $clean[] = implode('|', $encoded);
    }

    return implode("\n", $clean);
}

function khokan_sanitize_expertise_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }

        $label = sanitize_text_field($parts[0]);
        $style = 'default';
        if (!empty($parts[1])) {
            $maybe_style = strtolower(sanitize_text_field($parts[1]));
            if (in_array($maybe_style, ['accent', 'default'], true)) {
                $style = $maybe_style;
            }
        }

        $clean[] = $style === 'accent' ? "{$label}|accent" : $label;
    }

    return implode("\n", $clean);
}

function khokan_sanitize_services_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }

        $title = sanitize_text_field($parts[0]);
        $desc = isset($parts[1]) ? sanitize_text_field($parts[1]) : '';
        $icon = isset($parts[2]) ? esc_url_raw($parts[2]) : '';

        $encoded = [$title, $desc];
        if ($icon) {
            $encoded[] = $icon;
        }

        $clean[] = implode('|', $encoded);
    }

    return implode("\n", $clean);
}

function khokan_sanitize_hobbies_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }

        $title = sanitize_text_field($parts[0]);
        $desc = isset($parts[1]) ? sanitize_text_field($parts[1]) : '';
        $tags = [];
        if (!empty($parts[2])) {
            $tags = array_filter(array_map('trim', preg_split('/[,;]+/', $parts[2])));
            $tags = array_slice($tags, 0, 8);
            $tags = array_map('sanitize_text_field', $tags);
        }

        $clean[] = implode('|', [
            $title,
            $desc,
            $tags ? implode(',', $tags) : '',
        ]);
    }

    return implode("\n", $clean);
}

function khokan_sanitize_hobby_projects_list($value)
{
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value)));
    $clean = [];

    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }

        $title = sanitize_text_field($parts[0]);
        $desc = isset($parts[1]) ? sanitize_text_field($parts[1]) : '';
        $link = '';
        if (!empty($parts[2]) && preg_match('#^https?://#i', $parts[2])) {
            $link = esc_url_raw($parts[2]);
        }

        $clean[] = implode('|', [$title, $desc, $link]);
    }

    return implode("\n", $clean);
}

function khokan_sanitize_float($value)
{
    $num = floatval($value);
    if ($num <= 0) {
        return 1.0;
    }
    return max(1.0, min(2.4, $num));
}

function khokan_sanitize_center_size($value)
{
    $size = absint($value);
    if (!$size) {
        return 78;
    }

    return max(30, min(120, $size));
}

/**
 * Default configuration for orbiting skill badges.
 */
function khokan_get_skill_defaults()
{
    return [
        1 => [
            'label' => 'Flutter',
            'class' => 'flutter',
            'orbit' => 240,
            'duration' => 8.5,
            'size' => 62,
            'icon' => get_template_directory_uri() . '/assets/img/flutter-logo.png',
        ],
        2 => [
            'label' => 'React Native',
            'class' => 'react',
            'orbit' => 300,
            'duration' => 9.5,
            'size' => 58,
            'icon' => get_template_directory_uri() . '/assets/img/react.png',
        ],
        3 => [
            'label' => 'Android',
            'class' => 'android',
            'orbit' => 360,
            'duration' => 7.5,
            'size' => 64,
            'icon' => get_template_directory_uri() . '/assets/img/android.png',
        ],
        4 => [
            'label' => 'iOS',
            'class' => 'ios',
            'orbit' => 430,
            'duration' => 10,
            'size' => 56,
            'icon' => get_template_directory_uri() . '/assets/img/apple.png',
        ],
    ];
}

function khokan_skill_defaults_as_lines()
{
    $defaults = khokan_get_skill_defaults();
    $lines = [];
    foreach ($defaults as $skill) {
        $lines[] = $skill['label'] . '|' . $skill['icon'] . '|' . $skill['size'];
    }
    return implode("\n", $lines);
}

function khokan_customize_register($wp_customize)
{
    $wp_customize->add_panel('khokan_theme_panel', [
        'title' => 'Khokan Theme Options',
        'description' => 'Tweak branding, visuals, and each homepage section in one place.',
        'priority' => 20,
    ]);

    $wp_customize->add_section('khokan_colors', [
        'title' => 'Colors',
        'priority' => 6,
        'description' => 'Set background, accent, and card colors used across the site.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_typography', [
        'title' => 'Typography',
        'priority' => 7,
        'description' => 'Control base font sizes and heading scales.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_buttons', [
        'title' => 'Buttons & Links',
        'priority' => 8,
        'description' => 'Radius and colors for primary/secondary buttons and links.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_header_hero', [
        'title' => 'Brand & Hero',
        'priority' => 5,
        'description' => 'Logo/brand text, hero headline, background, and CTA links.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_about', [
        'title' => 'About & Tech',
        'priority' => 20,
        'description' => 'Control About copy, tech list, and section visibility.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_expertise', [
        'title' => 'Expertise',
        'priority' => 22,
        'description' => 'Highlight expertise pills and toggle the section.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_skills', [
        'title' => 'Bubble Skills',
        'priority' => 26,
        'description' => 'Orbiting skill bubbles around the hero image.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_projects', [
        'title' => 'Projects',
        'priority' => 30,
        'description' => 'Manage projects grid, pagination, and visibility.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_hobbies', [
        'title' => 'Web Hobbies & Side Projects',
        'priority' => 32,
        'description' => 'Control the My Hobbies & Web Craft section and featured web links.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_contact', [
        'title' => 'Contact & Footer',
        'priority' => 34,
        'description' => 'Contact form copy, direct contact chips, and visibility.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_social', [
        'title' => 'Social Links',
        'priority' => 36,
        'description' => 'Add your social profiles and direct chat links.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_section('khokan_seo', [
        'title' => 'SEO & Sharing',
        'priority' => 40,
        'description' => 'Meta title/description and social share image.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_setting('khokan_brand_title', [
        'default' => 'Khokan Dev Studio',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_brand_title', [
        'label' => 'Brand Title',
        'section' => 'khokan_header_hero',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_brand_tagline', [
        'default' => 'Mobile • Flutter • React Native',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_brand_tagline', [
        'label' => 'Brand Tagline',
        'section' => 'khokan_header_hero',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_tagline', [
        'default' => 'Senior Mobile App Developer | Flutter & React Native Expert',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hero_tagline', [
        'label' => 'Hero Tagline',
        'section' => 'khokan_header_hero',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_subline', [
        'default' => 'Building Scalable iOS & Android Apps for 6+ Years',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_hero_subline', [
        'label' => 'Hero Subline',
        'section' => 'khokan_header_hero',
        'type' => 'textarea',
    ]);

    $wp_customize->add_setting('khokan_hero_cta_text', [
        'default' => 'Get in Touch / Hire Me',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hero_cta_text', [
        'label' => 'Hero Button Text',
        'section' => 'khokan_header_hero',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_project_card_cta', [
        'default' => 'View Project',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_project_card_cta', [
        'label' => 'Project Card Button Text',
        'section' => 'khokan_projects',
        'type' => 'text',
        'description' => 'Default CTA text for project cards when a project-specific CTA is not set.',
    ]);

    $wp_customize->add_setting('khokan_projects_footer_cta', [
        'default' => 'See More Projects',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_projects_footer_cta', [
        'label' => 'Projects Footer Button Text',
        'section' => 'khokan_projects',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_hero_cta_link', [
        'default' => 'mailto:avkhokanuzzaman@gmail.com',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('khokan_hero_cta_link', [
        'label' => 'Hero Button Link',
        'section' => 'khokan_header_hero',
        'type' => 'url',
    ]);

    $wp_customize->add_setting('khokan_seo_title', [
        'default' => get_bloginfo('name'),
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_seo_title', [
        'label' => 'SEO Title',
        'section' => 'khokan_seo',
        'type' => 'text',
        'description' => 'Used for Open Graph & Twitter preview. Defaults to Site Title.',
    ]);

    $wp_customize->add_setting('khokan_seo_description', [
        'default' => get_bloginfo('description'),
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_seo_description', [
        'label' => 'SEO Description',
        'section' => 'khokan_seo',
        'type' => 'textarea',
        'description' => 'Shown in meta description and social shares. Defaults to Site Tagline.',
    ]);

    $wp_customize->add_setting('khokan_seo_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_seo_image', [
        'label' => 'SEO / Social Share Image',
        'section' => 'khokan_seo',
        'mime_type' => 'image',
        'description' => 'Recommended 1200x630. Falls back to hero image.',
    ]));

    $wp_customize->add_setting('khokan_cv_text', [
        'default' => 'Download CV',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_cv_text', [
        'label' => 'CV Button Text',
        'section' => 'khokan_header_hero',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_cv_link', [
        'default' => get_template_directory_uri() . '/assets/cv/Resume_khokan.pdf',
        'sanitize_callback' => 'esc_url_raw',
    ]);
    $wp_customize->add_control('khokan_cv_link', [
        'label' => 'CV Download Link (PDF or Drive URL)',
        'section' => 'khokan_header_hero',
        'type' => 'url',
        'description' => 'Paste the URL to your CV/resume file. The button will use the download attribute.',
    ]);

    $wp_customize->add_setting('khokan_hero_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_hero_image', [
        'label' => 'Hero Image',
        'section' => 'khokan_header_hero',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('khokan_about_text', [
        'default' => 'I\'m Md Khokanuzzanierkan, a software engineer specializing in Flutter, React Native, and cross-platform mobile apps. Over 6+ years, I\'ve built & shipped healthcare and commerce apps used by',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_about_text', [
        'label' => 'About Text',
        'section' => 'khokan_about',
        'type' => 'textarea',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_about_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_about_enabled', [
        'label' => 'Show About Section',
        'section' => 'khokan_about',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_tech_list', [
        'default' => "Flutter, React Native, Android, iOS\nState management: Riverpod, Redux, Bloc\nBackend: Node.js, NestJS, Firebase\nDevOps: CI/CD, Play Console, iOS release pipeline",
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_tech_list', [
        'label' => 'Tech List (one per line)',
        'section' => 'khokan_about',
        'type' => 'textarea',
        'priority' => 10,
    ]);

    $default_expertise_lines = implode("\n", [
        'Mobile App Development|accent',
        'Backend Development',
        'Cloud Solutions (AWS, Firebase)|accent',
        'DevOps & CI/CD',
        'Cross-Platform Solutions',
        'State Management|accent',
        'API Integration',
        'Code Optimization|accent',
    ]);

    $wp_customize->add_setting('khokan_expertise_title', [
        'default' => 'My Areas of Expertise',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_expertise_title', [
        'label' => 'Expertise Section Title',
        'section' => 'khokan_expertise',
        'type' => 'text',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_expertise_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_expertise_enabled', [
        'label' => 'Show Expertise Section',
        'section' => 'khokan_expertise',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_expertise_description', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_expertise_description', [
        'label' => 'Expertise Subtext (optional)',
        'section' => 'khokan_expertise',
        'type' => 'textarea',
        'priority' => 10,
    ]);

    $wp_customize->add_setting('khokan_expertise_items', [
        'default' => $default_expertise_lines,
        'sanitize_callback' => 'khokan_sanitize_expertise_list',
    ]);
    $wp_customize->add_control('khokan_expertise_items', [
        'label' => 'Expertise Items (one per line)',
        'section' => 'khokan_expertise',
        'type' => 'textarea',
        'description' => "Format: Label | style(accent|default). Use 'accent' for highlighted cards.",
        'priority' => 15,
    ]);

    $default_hobbies_lines = implode("\n", [
        'React Development|I like shaping clean, scalable web apps - dashboards, admin panels, and internal tools where speed and clarity matter.|React,APIs,Performance-first UI',
        'WordPress Custom Craft|Custom themes and performance tuning for portfolio sites and content-driven platforms built to be clean, SEO-ready, and actually used.|Custom themes,Performance,SEO-ready',
        'Web + Mobile Ecosystem|Web experience feeds my mobile architecture thinking: admin dashboards, landing pages, and backend integration that line up with app flows.|System thinking,Admin dashboards,Backend integration',
    ]);

    $default_hobby_projects_lines = implode("\n", [
        'khokan.me|Personal portfolio & blog (Custom WordPress).|https://www.khokan.me',
        'SolutionHub (Beta)|Web tools platform.|https://solutionhub.khokan.me/',
        'Aleef Mart|Ecommerce contributions and performance tuning.|https://aleefmart.com/',
    ]);

    $wp_customize->add_setting('khokan_hobbies_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_hobbies_enabled', [
        'label' => 'Show Hobbies/Web Craft Section',
        'section' => 'khokan_hobbies',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_hobbies_title', [
        'default' => 'My Hobbies & Web Craft',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hobbies_title', [
        'label' => 'Section Title',
        'section' => 'khokan_hobbies',
        'type' => 'text',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_hobbies_subtitle', [
        'default' => 'I lead with mobile (Flutter & React Native), but I genuinely enjoy building for the web. What started as weekend tinkering grew into production dashboards and sites, and it now helps me design stronger mobile systems, APIs, dashboards, and admin panels.',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_hobbies_subtitle', [
        'label' => 'Section Subtext',
        'section' => 'khokan_hobbies',
        'type' => 'textarea',
        'priority' => 10,
    ]);

    $wp_customize->add_setting('khokan_hobbies_items', [
        'default' => $default_hobbies_lines,
        'sanitize_callback' => 'khokan_sanitize_hobbies_list',
    ]);
    $wp_customize->add_control('khokan_hobbies_items', [
        'label' => 'Hobby Cards (one per line)',
        'section' => 'khokan_hobbies',
        'type' => 'textarea',
        'priority' => 12,
        'description' => "Format: Title | Description | Tags (comma separated).",
    ]);

    $wp_customize->add_setting('khokan_hobby_projects_title', [
        'default' => 'Web Projects Built from Passion',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_hobby_projects_title', [
        'label' => 'Web Projects Heading',
        'section' => 'khokan_hobbies',
        'type' => 'text',
        'priority' => 14,
    ]);

    $wp_customize->add_setting('khokan_hobby_projects', [
        'default' => $default_hobby_projects_lines,
        'sanitize_callback' => 'khokan_sanitize_hobby_projects_list',
    ]);
    $wp_customize->add_control('khokan_hobby_projects', [
        'label' => 'Web Projects (one per line)',
        'section' => 'khokan_hobbies',
        'type' => 'textarea',
        'priority' => 16,
        'description' => "Format: Name | Description | Link (optional).",
    ]);

    $wp_customize->add_setting('khokan_hobbies_outro', [
        'default' => 'Sometimes hobbies turn into strengths - web development is one of mine.',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_hobbies_outro', [
        'label' => 'Outro Line',
        'section' => 'khokan_hobbies',
        'type' => 'text',
        'priority' => 18,
    ]);

    $default_services_lines = implode("\n", [
        'Mobile App Development (iOS & Android)|Native and cross-platform builds for iOS & Android with App Store/Play Store launch support.|' . get_template_directory_uri() . '/assets/img/android.png',
        'Backend & API Development|Robust, secure APIs and realtime backends with Node.js, NestJS, and Firebase.|' . get_template_directory_uri() . '/assets/img/react.png',
        'Software Consulting & Architecture|Technical consulting, solution design, and release planning for mobile products.|' . get_template_directory_uri() . '/assets/img/seo-share.png',
    ]);

    $wp_customize->add_section('khokan_services', [
        'title' => 'Services',
        'priority' => 24,
        'description' => 'Configure services cards, icons, and columns, and toggle visibility.',
        'panel' => 'khokan_theme_panel',
    ]);

    $wp_customize->add_setting('khokan_services_title', [
        'default' => 'My Services',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_services_title', [
        'label' => 'Services Section Title',
        'section' => 'khokan_services',
        'type' => 'text',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_services_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_services_enabled', [
        'label' => 'Show Services Section',
        'section' => 'khokan_services',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_services_description', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_services_description', [
        'label' => 'Services Subtext (optional)',
        'section' => 'khokan_services',
        'type' => 'textarea',
        'priority' => 10,
    ]);

    $wp_customize->add_setting('khokan_services_items', [
        'default' => $default_services_lines,
        'sanitize_callback' => 'khokan_sanitize_services_list',
    ]);
    $wp_customize->add_control('khokan_services_items', [
        'label' => 'Services (one per line)',
        'section' => 'khokan_services',
        'type' => 'textarea',
        'description' => "Format: Title | Description | Icon URL (optional).",
        'priority' => 15,
    ]);

    $wp_customize->add_setting('khokan_services_columns_desktop', [
        'default' => 3,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_services_columns_desktop', [
        'label' => 'Services Columns (Desktop)',
        'section' => 'khokan_services',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 4,
            'step' => 1,
        ],
        'priority' => 20,
    ]);

    $wp_customize->add_setting('khokan_services_columns_tablet', [
        'default' => 2,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_services_columns_tablet', [
        'label' => 'Services Columns (Tablet)',
        'section' => 'khokan_services',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 3,
            'step' => 1,
        ],
        'priority' => 21,
    ]);

    $wp_customize->add_setting('khokan_services_columns_mobile', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_services_columns_mobile', [
        'label' => 'Services Columns (Mobile)',
        'section' => 'khokan_services',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 2,
            'step' => 1,
        ],
        'priority' => 22,
    ]);

    $wp_customize->add_setting('khokan_services_card_radius', [
        'default' => 16,
        'sanitize_callback' => 'khokan_sanitize_radius_px',
    ]);
    $wp_customize->add_control('khokan_services_card_radius', [
        'label' => 'Services Card Radius (px)',
        'section' => 'khokan_services',
        'type' => 'number',
        'input_attrs' => [
            'min' => 0,
            'max' => 40,
            'step' => 1,
        ],
        'priority' => 30,
    ]);

    $wp_customize->add_setting('khokan_services_card_shadow', [
        'default' => 'medium',
        'sanitize_callback' => 'khokan_sanitize_card_shadow',
    ]);
    $wp_customize->add_control('khokan_services_card_shadow', [
        'label' => 'Services Card Shadow',
        'section' => 'khokan_services',
        'type' => 'select',
        'choices' => [
            'none' => 'None',
            'soft' => 'Soft',
            'medium' => 'Medium',
            'strong' => 'Strong',
        ],
        'priority' => 32,
    ]);

    $wp_customize->add_setting('khokan_contact_title', [
        'default' => 'Contact & Lead Generation',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_contact_title', [
        'label' => 'Contact Section Title',
        'section' => 'khokan_contact',
        'type' => 'text',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_contact_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_contact_enabled', [
        'label' => 'Show Contact Section',
        'section' => 'khokan_contact',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_contact_button_text', [
        'default' => 'Send Message',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_contact_button_text', [
        'label' => 'Contact Button Text',
        'section' => 'khokan_contact',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_contact_email', [
        'default' => get_option('admin_email'),
        'sanitize_callback' => 'sanitize_email',
    ]);
    $wp_customize->add_control('khokan_contact_email', [
        'label' => 'Contact Email (form recipient)',
        'section' => 'khokan_contact',
        'type' => 'email',
    ]);

    $wp_customize->add_setting('khokan_projects_title', [
        'default' => 'Projects',
        'sanitize_callback' => 'sanitize_text_field',
    ]);
    $wp_customize->add_control('khokan_projects_title', [
        'label' => 'Projects Section Title',
        'section' => 'khokan_projects',
        'type' => 'text',
        'priority' => 5,
    ]);

    $wp_customize->add_setting('khokan_projects_enabled', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_projects_enabled', [
        'label' => 'Show Projects Section',
        'section' => 'khokan_projects',
        'type' => 'checkbox',
        'priority' => 1,
    ]);

    $wp_customize->add_setting('khokan_projects_intro', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_textarea',
    ]);
    $wp_customize->add_control('khokan_projects_intro', [
        'label' => 'Projects Intro (optional)',
        'section' => 'khokan_projects',
        'type' => 'textarea',
        'priority' => 10,
    ]);

    $default_projects_lines = implode("\n", [
        'JOTNO – For Patient|Patient-facing telemedicine app in React Native with instant doctor chat, prescriptions, and follow-ups.|https://play.google.com/store/apps/details?id=sqh.jotno.patient&hl=en|teal|' . get_template_directory_uri() . '/assets/img/jotno-logo.png|View Project',
        'Jotno – Telemedicine Platform|Built the doctor-side Flutter app for instant patient communication and digital prescriptions.|https://play.google.com/store/apps/details?id=sqh.jotno.doctor&hl=en|teal|' . get_template_directory_uri() . '/assets/img/jotno-doctor.png|View Project',
        'Confidence Reseller|Flutter app for sales agent distributors with 500+ downloads.|https://play.google.com/store/apps/details?id=com.confidenceresellerbd.app&hl=en|blue|' . get_template_directory_uri() . '/assets/img/confidence-reseller.png|View Project',
    ]);

    $wp_customize->add_setting('khokan_projects_custom_enabled', [
        'default' => 0,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_projects_custom_enabled', [
        'label' => 'Use Custom Projects List',
        'section' => 'khokan_projects',
        'type' => 'checkbox',
        'description' => 'Manually define project cards here instead of pulling from Project posts.',
    ]);

    $wp_customize->add_setting('khokan_projects_custom_list', [
        'default' => $default_projects_lines,
        'sanitize_callback' => 'khokan_sanitize_projects_list',
    ]);
    $wp_customize->add_control('khokan_projects_custom_list', [
        'label' => 'Projects (one per line)',
        'section' => 'khokan_projects',
        'type' => 'textarea',
        'description' => "Format: Title | Description | Link | Accent(teal|blue|indigo) | Image URL | CTA text (optional)\nLeave link/image/CTA empty to fall back to defaults.",
    ]);

    $wp_customize->add_setting('khokan_projects_show_filters', [
        'default' => 0,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_projects_show_filters', [
        'label' => 'Show Tag Filters',
        'section' => 'khokan_projects',
        'type' => 'checkbox',
        'description' => 'Display chips for Project Tags above the grid (CPT only).',
    ]);

    $wp_customize->add_setting('khokan_projects_per_page', [
        'default' => 6,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control('khokan_projects_per_page', [
        'label' => 'Projects Per Page (for paginate/load more)',
        'section' => 'khokan_projects',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 24,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_projects_pagination', [
        'default' => 'all',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['all', 'paginate', 'load_more'], true) ? $value : 'all';
        },
    ]);
    $wp_customize->add_control('khokan_projects_pagination', [
        'label' => 'Projects Display',
        'section' => 'khokan_projects',
        'type' => 'select',
        'choices' => [
            'all' => 'Show All',
            'paginate' => 'Paginate',
            'load_more' => 'Load More',
        ],
    ]);

    $wp_customize->add_setting('khokan_projects_card_radius', [
        'default' => 14,
        'sanitize_callback' => 'khokan_sanitize_optional_font_px',
    ]);
    $wp_customize->add_control('khokan_projects_card_radius', [
        'label' => 'Project Card Radius (px)',
        'section' => 'khokan_projects',
        'type' => 'number',
        'input_attrs' => [
            'min' => 0,
            'max' => 40,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_projects_card_shadow', [
        'default' => 'medium',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['none', 'soft', 'medium', 'strong'], true) ? $value : 'medium';
        },
    ]);
    $wp_customize->add_control('khokan_projects_card_shadow', [
        'label' => 'Project Card Shadow',
        'section' => 'khokan_projects',
        'type' => 'select',
        'choices' => [
            'none' => 'None',
            'soft' => 'Soft',
            'medium' => 'Medium',
            'strong' => 'Strong',
        ],
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
            'section' => 'khokan_social',
            'type' => 'url',
        ]);
    }
    $wp_customize->add_setting('khokan_skills_list', [
        'default' => khokan_skill_defaults_as_lines(),
        'sanitize_callback' => 'khokan_sanitize_skills_list',
    ]);
    // Keep storage control but hide it visually; JS manages the list.
    $wp_customize->add_control('khokan_skills_list', [
        'section' => 'khokan_skills',
        'type' => 'textarea',
        'label' => '',
        'input_attrs' => [
            'style' => 'display:none;',
            'aria-hidden' => 'true',
        ],
    ]);

    $wp_customize->add_setting('khokan_skills_center_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_skills_center_image', [
        'label' => 'Center Image (Orbit)',
        'section' => 'khokan_skills',
        'mime_type' => 'image',
        'description' => 'Overrides the hero image used in the orbit center. Leave empty to use the Hero Image.',
    ]));

    $wp_customize->add_setting('khokan_skills_center_size', [
        'default' => 78,
        'sanitize_callback' => 'khokan_sanitize_center_size',
    ]);
    $wp_customize->add_control('khokan_skills_center_size', [
        'label' => 'Center Image Size (%)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 30,
            'max' => 120,
            'step' => 1,
        ],
        'description' => 'Adjust the size of the center image relative to the orbit container.',
    ]);

    $wp_customize->add_setting('khokan_skills_orbit_size', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_optional_px_range',
    ]);
    $wp_customize->add_control('khokan_skills_orbit_size', [
        'label' => 'Override Orbit Size (px)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 120,
            'max' => 1200,
            'step' => 10,
        ],
        'description' => 'Advanced: applies to all skill orbits. Example: 440.',
        'priority' => 42,
    ]);

    $wp_customize->add_setting('khokan_skills_orbit_duration', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_optional_float',
    ]);
    $wp_customize->add_control('khokan_skills_orbit_duration', [
        'label' => 'Override Orbit Duration (s)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 600,
            'step' => 0.1,
        ],
        'description' => 'Advanced: applies to all skill orbits. Example: 158.3',
        'priority' => 43,
    ]);

    $wp_customize->add_setting('khokan_skills_planet_size', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_optional_px_range',
    ]);
    $wp_customize->add_control('khokan_skills_planet_size', [
        'label' => 'Override Planet Size (px)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 24,
            'max' => 240,
            'step' => 2,
        ],
        'description' => 'Advanced: applies to all planets. Example: 64.',
        'priority' => 44,
    ]);

    $wp_customize->add_setting('khokan_skills_orbit_delay', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_optional_float',
    ]);
    $wp_customize->add_control('khokan_skills_orbit_delay', [
        'label' => 'Override Orbit Delay (s)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => -300,
            'max' => 300,
            'step' => 0.1,
        ],
        'description' => 'Advanced: applies to all planets. Example: 13.6 (can be negative).',
        'priority' => 45,
    ]);

    $wp_customize->add_setting('khokan_skills_use_projects', [
        'default' => 0,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_skills_use_projects', [
        'label' => 'Append Projects to Skills Orbit',
        'section' => 'khokan_skills',
        'type' => 'checkbox',
        'description' => 'Use published Projects as additional skill bubbles (title + featured image).',
    ]);

    $wp_customize->add_setting('khokan_skills_project_limit', [
        'default' => 4,
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control('khokan_skills_project_limit', [
        'label' => 'Max Project Bubbles',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 20,
            'step' => 1,
        ],
    ]);

    // Quick-add skill inputs (assistive UI that appends to the list).
    $wp_customize->add_setting('khokan_skill_new_label', [
        'default' => '',
        'sanitize_callback' => 'sanitize_text_field',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('khokan_skill_new_label', [
        'label' => 'New Skill Label',
        'section' => 'khokan_skills',
        'type' => 'text',
    ]);

    $wp_customize->add_setting('khokan_skill_new_icon', [
        'sanitize_callback' => 'absint',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_skill_new_icon', [
        'label' => 'New Skill Icon',
        'section' => 'khokan_skills',
        'mime_type' => 'image',
    ]));

    $wp_customize->add_setting('khokan_skill_new_size', [
        'default' => '',
        'sanitize_callback' => 'khokan_sanitize_optional_font_px',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control('khokan_skill_new_size', [
        'label' => 'New Skill Size (px)',
        'section' => 'khokan_skills',
        'type' => 'number',
        'input_attrs' => [
            'min' => 24,
            'max' => 160,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_skill_add_button', [
        'sanitize_callback' => 'khokan_sanitize_checkbox',
        'transport' => 'postMessage',
    ]);
    $wp_customize->add_control(new WP_Customize_Control($wp_customize, 'khokan_skill_add_button', [
        'section' => 'khokan_skills',
        'type' => 'custom',
        'settings' => 'khokan_skill_add_button',
        'description' => '<button type="button" class="button button-primary" id="khokan-skill-add-btn">Add Skill</button><p>Fill Label/Icon/Size above, then click to append to the Skills list.</p>',
    ]));

    $wp_customize->add_setting('khokan_background_color', [
        'default' => '#040b24',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_background_color', [
        'label' => 'Background Color',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_accent_color', [
        'default' => '#7cd25e',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_accent_color', [
        'label' => 'Accent Color',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_card_color', [
        'default' => '#0b1744',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_card_color', [
        'label' => 'Card Background',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_card_dark_color', [
        'default' => '#081030',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_card_dark_color', [
        'label' => 'Card Dark Background',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_text_color', [
        'default' => '#e8edff',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_text_color', [
        'label' => 'Primary Text Color',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_muted_color', [
        'default' => '#c5cce5',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_muted_color', [
        'label' => 'Muted Text Color',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_border_color', [
        'default' => '#101a3a',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_border_color', [
        'label' => 'Border Color',
        'section' => 'khokan_colors',
    ]));

    $wp_customize->add_setting('khokan_hero_tagline_color', [
        'default' => '#e8edff',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_hero_tagline_color', [
        'label' => 'Hero Tagline Color',
        'section' => 'khokan_typography',
    ]));

    $wp_customize->add_setting('khokan_hero_tagline_size', [
        'default' => 34,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_hero_tagline_size', [
        'label' => 'Hero Tagline Font Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 16,
            'max' => 72,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_hero_subline_color', [
        'default' => '#c5cce5',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_hero_subline_color', [
        'label' => 'Hero Subline Color',
        'section' => 'khokan_typography',
    ]));

    $wp_customize->add_setting('khokan_hero_subline_size', [
        'default' => 18,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_hero_subline_size', [
        'label' => 'Hero Subline Font Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 12,
            'max' => 48,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_body_font_size', [
        'default' => 16,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_body_font_size', [
        'label' => 'Base Font Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 12,
            'max' => 24,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_body_line_height', [
        'default' => 1.6,
        'sanitize_callback' => 'khokan_sanitize_float',
    ]);
    $wp_customize->add_control('khokan_body_line_height', [
        'label' => 'Base Line Height',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1.0,
            'max' => 2.4,
            'step' => 0.05,
        ],
    ]);

    $wp_customize->add_setting('khokan_h1_size', [
        'default' => 40,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_h1_size', [
        'label' => 'H1 Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 28,
            'max' => 72,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_h2_size', [
        'default' => 32,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_h2_size', [
        'label' => 'H2 Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 22,
            'max' => 56,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_h3_size', [
        'default' => 26,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_h3_size', [
        'label' => 'H3 Size (px)',
        'section' => 'khokan_typography',
        'type' => 'number',
        'input_attrs' => [
            'min' => 18,
            'max' => 42,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_button_radius', [
        'default' => 10,
        'sanitize_callback' => 'khokan_sanitize_font_px',
    ]);
    $wp_customize->add_control('khokan_button_radius', [
        'label' => 'Button Radius (px)',
        'section' => 'khokan_buttons',
        'type' => 'number',
        'input_attrs' => [
            'min' => 0,
            'max' => 32,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_button_primary_bg', [
        'default' => '#7cd25e',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_button_primary_bg', [
        'label' => 'Primary Button Background',
        'section' => 'khokan_buttons',
    ]));

    $wp_customize->add_setting('khokan_button_primary_text', [
        'default' => '#0a1a35',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_button_primary_text', [
        'label' => 'Primary Button Text',
        'section' => 'khokan_buttons',
    ]));

    $wp_customize->add_setting('khokan_button_primary_hover', [
        'default' => '#92e37b',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_button_primary_hover', [
        'label' => 'Primary Button Hover Background',
        'section' => 'khokan_buttons',
    ]));

    $wp_customize->add_setting('khokan_button_secondary_hover', [
        'default' => '#18264d',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_button_secondary_hover', [
        'label' => 'Secondary/Ghost Hover Background',
        'section' => 'khokan_buttons',
    ]));

    $wp_customize->add_setting('khokan_link_hover_color', [
        'default' => '#7cd25e',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_link_hover_color', [
        'label' => 'Link Hover Color',
        'section' => 'khokan_buttons',
    ]));

    $wp_customize->add_setting('khokan_hero_alignment', [
        'default' => 'left',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['left', 'center'], true) ? $value : 'left';
        },
    ]);
    $wp_customize->add_control('khokan_hero_alignment', [
        'label' => 'Hero Alignment',
        'section' => 'khokan_header_hero',
        'type' => 'radio',
        'choices' => [
            'left' => 'Left',
            'center' => 'Center',
        ],
    ]);

    $wp_customize->add_setting('khokan_hero_bg_type', [
        'default' => 'default',
        'sanitize_callback' => function ($value) {
            return in_array($value, ['default', 'solid', 'gradient', 'image'], true) ? $value : 'default';
        },
    ]);
    $wp_customize->add_control('khokan_hero_bg_type', [
        'label' => 'Hero Background',
        'section' => 'khokan_header_hero',
        'type' => 'select',
        'choices' => [
            'default' => 'Theme Default',
            'solid' => 'Solid Color',
            'gradient' => 'Custom Gradient',
            'image' => 'Background Image',
        ],
    ]);

    $wp_customize->add_setting('khokan_hero_bg_color', [
        'default' => '#050d2c',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_hero_bg_color', [
        'label' => 'Hero Background Color',
        'section' => 'khokan_header_hero',
    ]));

    $wp_customize->add_setting('khokan_hero_bg_color2', [
        'default' => '#0b1240',
        'sanitize_callback' => 'sanitize_hex_color',
    ]);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'khokan_hero_bg_color2', [
        'label' => 'Hero Background Color 2 (for gradient)',
        'section' => 'khokan_header_hero',
    ]));

    $wp_customize->add_setting('khokan_hero_bg_image', [
        'sanitize_callback' => 'absint',
    ]);
    $wp_customize->add_control(new WP_Customize_Media_Control($wp_customize, 'khokan_hero_bg_image', [
        'label' => 'Hero Background Image',
        'section' => 'khokan_header_hero',
        'mime_type' => 'image',
        'description' => 'Used when Hero Background is set to Image.',
    ]));

    $wp_customize->add_setting('khokan_reduce_motion', [
        'default' => 0,
        'sanitize_callback' => 'khokan_sanitize_checkbox',
    ]);
    $wp_customize->add_control('khokan_reduce_motion', [
        'label' => 'Reduce Motion (disable orbit animations)',
        'section' => 'khokan_header_hero',
        'type' => 'checkbox',
    ]);

    $wp_customize->add_setting('khokan_projects_columns_desktop', [
        'default' => 3,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_projects_columns_desktop', [
        'label' => 'Projects Columns (Desktop)',
        'section' => 'khokan_projects',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 4,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_projects_columns_tablet', [
        'default' => 2,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_projects_columns_tablet', [
        'label' => 'Projects Columns (Tablet)',
        'section' => 'khokan_projects',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 3,
            'step' => 1,
        ],
    ]);

    $wp_customize->add_setting('khokan_projects_columns_mobile', [
        'default' => 1,
        'sanitize_callback' => 'khokan_sanitize_grid_int',
    ]);
    $wp_customize->add_control('khokan_projects_columns_mobile', [
        'label' => 'Projects Columns (Mobile)',
        'section' => 'khokan_projects',
        'type' => 'number',
        'input_attrs' => [
            'min' => 1,
            'max' => 2,
            'step' => 1,
        ],
    ]);
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

function khokan_get_services_items()
{
    $defaults = [
        [
            'title' => 'Mobile App Development (iOS & Android)',
            'description' => 'Native and cross-platform builds for iOS & Android with App Store/Play Store launch support.',
            'icon' => get_template_directory_uri() . '/assets/img/android.png',
        ],
        [
            'title' => 'Backend & API Development',
            'description' => 'Robust, secure APIs and realtime backends with Node.js, NestJS, and Firebase.',
            'icon' => get_template_directory_uri() . '/assets/img/react.png',
        ],
        [
            'title' => 'Software Consulting & Architecture',
            'description' => 'Technical consulting, solution design, and release planning for mobile products.',
            'icon' => get_template_directory_uri() . '/assets/img/seo-share.png',
        ],
    ];

    $raw = get_theme_mod('khokan_services_items', '');
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));

    $items = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $items[] = [
            'title' => sanitize_text_field($parts[0]),
            'description' => isset($parts[1]) ? sanitize_text_field($parts[1]) : '',
            'icon' => isset($parts[2]) ? esc_url($parts[2]) : '',
        ];
    }

    if (!$items) {
        return $defaults;
    }

    return $items;
}

function khokan_get_expertise_items()
{
    $defaults = [
        'Mobile App Development|accent',
        'Backend Development',
        'Cloud Solutions (AWS, Firebase)|accent',
        'DevOps & CI/CD',
        'Cross-Platform Solutions',
        'State Management|accent',
        'API Integration',
        'Code Optimization|accent',
    ];

    $raw = get_theme_mod('khokan_expertise_items', implode("\n", $defaults));
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));

    $items = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $label = sanitize_text_field($parts[0]);
        $style = (!empty($parts[1]) && strtolower($parts[1]) === 'accent') ? 'accent' : 'default';

        $items[] = [
            'label' => $label,
            'style' => $style,
        ];
    }

    if (!$items) {
        $items = [];
        foreach ($defaults as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (empty($parts[0])) {
                continue;
            }
            $items[] = [
                'label' => $parts[0],
                'style' => (!empty($parts[1]) && strtolower($parts[1]) === 'accent') ? 'accent' : 'default',
            ];
        }
    }

    return $items;
}

function khokan_get_hobbies_items()
{
    $default_lines = [
        'React Development|I like shaping clean, scalable web apps - dashboards, admin panels, and internal tools where speed and clarity matter.|React,APIs,Performance-first UI',
        'WordPress Custom Craft|Custom themes and performance tuning for portfolio sites and content-driven platforms built to be clean, SEO-ready, and actually used.|Custom themes,Performance,SEO-ready',
        'Web + Mobile Ecosystem|Web experience feeds my mobile architecture thinking: admin dashboards, landing pages, and backend integration that line up with app flows.|System thinking,Admin dashboards,Backend integration',
    ];

    $raw = get_theme_mod('khokan_hobbies_items', implode("\n", $default_lines));
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));

    $items = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $tags = [];
        if (!empty($parts[2])) {
            $tags = array_filter(array_map('trim', preg_split('/[,;]+/', $parts[2])));
            $tags = array_slice($tags, 0, 8);
            $tags = array_map('sanitize_text_field', $tags);
        }
        $items[] = [
            'label' => sanitize_text_field($parts[0]),
            'description' => isset($parts[1]) ? sanitize_text_field($parts[1]) : '',
            'tags' => $tags,
        ];
    }

    if (!$items) {
        foreach ($default_lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (empty($parts[0])) {
                continue;
            }
            $tags = [];
            if (!empty($parts[2])) {
                $tags = array_filter(array_map('trim', preg_split('/[,;]+/', $parts[2])));
            }
            $items[] = [
                'label' => $parts[0],
                'description' => $parts[1] ?? '',
                'tags' => $tags,
            ];
        }
    }

    return $items;
}

function khokan_get_hobby_projects()
{
    $default_lines = [
        'khokan.me|Personal portfolio & blog (Custom WordPress).|https://www.khokan.me',
        'SolutionHub (Beta)|Web tools platform.|https://solutionhub.khokan.me/',
        'Aleef Mart|Ecommerce contributions and performance tuning.|https://aleefmart.com/',
    ];

    $raw = get_theme_mod('khokan_hobby_projects', implode("\n", $default_lines));
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));

    $projects = [];
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }

        $link = '';
        if (!empty($parts[2]) && preg_match('#^https?://#i', $parts[2])) {
            $link = esc_url($parts[2]);
        }

        $projects[] = [
            'name' => sanitize_text_field($parts[0]),
            'description' => isset($parts[1]) ? sanitize_text_field($parts[1]) : '',
            'link' => $link,
        ];
    }

    if (!$projects) {
        foreach ($default_lines as $line) {
            $parts = array_map('trim', explode('|', $line));
            if (empty($parts[0])) {
                continue;
            }
            $projects[] = [
                'name' => $parts[0],
                'description' => $parts[1] ?? '',
                'link' => $parts[2] ?? '',
            ];
        }
    }

    return $projects;
}

function khokan_get_projects()
{
    $default_cta = get_theme_mod('khokan_project_card_cta', 'View Project');
    $projects = [];
    $use_custom = get_theme_mod('khokan_projects_custom_enabled', 0);
    $custom_lines = get_theme_mod('khokan_projects_custom_list', '');
    $show_filters = get_theme_mod('khokan_projects_show_filters', 0);
    $pagination_mode = get_theme_mod('khokan_projects_pagination', 'all');
    $per_page = absint(get_theme_mod('khokan_projects_per_page', 6));
    if ($per_page < 1) {
        $per_page = 6;
    }

    $active_tag = '';
    if ($show_filters && !$use_custom && isset($_GET['project_tag'])) {
        $active_tag = sanitize_title(wp_unslash($_GET['project_tag']));
    }

    $current_page = isset($_GET['proj_page']) ? max(1, absint($_GET['proj_page'])) : 1;

    if ($use_custom && $custom_lines) {
        $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $custom_lines)));
        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', explode('|', $line));
            if (empty($parts[0])) {
                continue;
            }
            $accent = isset($parts[3]) ? strtolower($parts[3]) : 'teal';
            if (!in_array($accent, ['teal', 'blue', 'indigo'], true)) {
                $accent = 'teal';
            }
            $projects[] = [
                'title' => $parts[0],
                'description' => $parts[1] ?? '',
                'cta' => !empty($parts[5]) ? $parts[5] : $default_cta,
                'link' => !empty($parts[2]) ? $parts[2] : '#',
                'accent' => $accent,
                'image' => !empty($parts[4]) ? $parts[4] : '',
                'role' => '',
                'duration' => '',
                'stack' => [],
                'result' => '',
                'secondary_cta' => '',
                'secondary_link' => '',
                'featured' => false,
                'tags' => [],
            ];
        }

        if ($pagination_mode !== 'all' && $projects) {
            $total_pages = (int) ceil(count($projects) / $per_page);
            $slice = array_slice($projects, ($current_page - 1) * $per_page, $per_page);
            return [
                'items' => $slice,
                'pagination' => [
                    'mode' => $pagination_mode,
                    'current' => $current_page,
                    'total' => max(1, $total_pages),
                    'has_more' => $current_page < $total_pages,
                ],
                'filters' => [],
                'active_tag' => '',
                'use_custom' => true,
            ];
        }

        return [
            'items' => $projects,
            'pagination' => [
                'mode' => 'all',
                'current' => 1,
                'total' => 1,
                'has_more' => false,
            ],
            'filters' => [],
            'active_tag' => '',
            'use_custom' => true,
        ];
    }

    $query_args = [
        'post_type' => 'khokan_project',
        'posts_per_page' => $pagination_mode === 'all' ? -1 : $per_page,
        'orderby' => [
            'meta_value_num' => 'DESC',
            'menu_order' => 'ASC',
            'date' => 'DESC',
        ],
        'meta_key' => '_khokan_project_featured',
        'post_status' => 'publish',
        'paged' => $pagination_mode === 'all' ? 1 : $current_page,
    ];

    if ($active_tag) {
        $query_args['tax_query'] = [
            [
                'taxonomy' => 'khokan_project_tag',
                'field' => 'slug',
                'terms' => $active_tag,
            ],
        ];
    }

    $query = new WP_Query($query_args);

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $accent = get_post_meta(get_the_ID(), '_khokan_project_accent', true) ?: 'teal';
            if (!in_array($accent, ['teal', 'blue', 'indigo'], true)) {
                $accent = 'teal';
            }

            $image = get_the_post_thumbnail_url(get_the_ID(), 'medium') ?: '';
            $project_cta = get_post_meta(get_the_ID(), '_khokan_project_cta', true) ?: $default_cta;
            $stack_raw = get_post_meta(get_the_ID(), '_khokan_project_stack', true);
            $stack = array_filter(array_map('trim', preg_split('/\r\n|\r|\n|,/', (string) $stack_raw)));
            $result = get_post_meta(get_the_ID(), '_khokan_project_result', true);
            $role = get_post_meta(get_the_ID(), '_khokan_project_role', true);
            $duration = get_post_meta(get_the_ID(), '_khokan_project_duration', true);
            $secondary_cta = get_post_meta(get_the_ID(), '_khokan_project_secondary_cta', true);
            $secondary_link = get_post_meta(get_the_ID(), '_khokan_project_secondary_link', true);
            $featured = (bool) get_post_meta(get_the_ID(), '_khokan_project_featured', true);
            $tags = wp_get_post_terms(get_the_ID(), 'khokan_project_tag', ['fields' => 'names']);

            $projects[] = [
                'title' => get_the_title(),
                'description' => get_the_excerpt() ?: wp_trim_words(get_the_content(), 30, '...'),
                'cta' => $project_cta,
                'link' => get_post_meta(get_the_ID(), '_khokan_project_link', true) ?: '#',
                'accent' => $accent,
                'image' => $image,
                'role' => $role,
                'duration' => $duration,
                'stack' => $stack,
                'result' => $result,
                'secondary_cta' => $secondary_cta,
                'secondary_link' => $secondary_link,
                'featured' => $featured,
                'tags' => $tags,
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
                'role' => '',
                'duration' => '',
                'stack' => [],
                'result' => '',
                'secondary_cta' => '',
                'secondary_link' => '',
                'featured' => false,
                'tags' => [],
            ],
            [
                'title' => 'Jotno – Telemedicine Platform',
                'description' => 'Built the doctor-side Flutter app for instant patient communication and digital prescriptions.',
                'cta' => $default_cta,
                'link' => 'https://play.google.com/store/apps/details?id=sqh.jotno.doctor&hl=en',
                'accent' => 'teal',
                'image' => get_template_directory_uri() . '/assets/img/jotno-doctor.png',
                'role' => '',
                'duration' => '',
                'stack' => [],
                'result' => '',
                'secondary_cta' => '',
                'secondary_link' => '',
                'featured' => false,
                'tags' => [],
            ],
            [
                'title' => 'Confidence Reseller',
                'description' => 'Flutter app for sales agent distributors with 500+ downloads.',
                'cta' => $default_cta,
                'link' => 'https://play.google.com/store/apps/details?id=com.confidenceresellerbd.app&hl=en',
                'accent' => 'blue',
                'image' => get_template_directory_uri() . '/assets/img/confidence-reseller.png',
                'role' => '',
                'duration' => '',
                'stack' => [],
                'result' => '',
                'secondary_cta' => '',
                'secondary_link' => '',
                'featured' => false,
                'tags' => [],
            ],
        ];
    }

    $filters = [];
    if ($show_filters && !$use_custom) {
        $terms = get_terms([
            'taxonomy' => 'khokan_project_tag',
            'hide_empty' => true,
        ]);
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                $filters[] = [
                    'name' => $term->name,
                    'slug' => $term->slug,
                    'count' => $term->count,
                ];
            }
        }
    }

    $total_pages = ($pagination_mode === 'all') ? 1 : max(1, (int) ($query->max_num_pages ?: 1));

    return [
        'items' => $projects,
        'pagination' => [
            'mode' => $pagination_mode,
            'current' => $current_page,
            'total' => $total_pages,
            'has_more' => $current_page < $total_pages,
        ],
        'filters' => $filters,
        'active_tag' => $active_tag,
        'use_custom' => $use_custom,
    ];
}

$GLOBALS['khokan_skill_items'] = null;

function khokan_get_skill_items()
{
    if (isset($GLOBALS['khokan_skill_items']) && is_array($GLOBALS['khokan_skill_items'])) {
        return $GLOBALS['khokan_skill_items'];
    }

    $defaults = array_values(khokan_get_skill_defaults());
    if (!$defaults) {
        return [];
    }
    $default_lines = khokan_skill_defaults_as_lines();
    $raw = get_theme_mod('khokan_skills_list', $default_lines);
    $lines = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $raw)));

    $override_orbit_size = khokan_sanitize_optional_px_range(get_theme_mod('khokan_skills_orbit_size', ''));
    $override_orbit_duration = khokan_sanitize_optional_float(get_theme_mod('khokan_skills_orbit_duration', ''));
    $override_planet_size = khokan_sanitize_optional_px_range(get_theme_mod('khokan_skills_planet_size', ''));
    $override_orbit_delay = khokan_sanitize_optional_float(get_theme_mod('khokan_skills_orbit_delay', ''));

    $items = [];
    $count = 0;
    foreach ($lines as $line) {
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        if (empty($parts[0])) {
            continue;
        }
        $defaults_index = $count % count($defaults);
        $fallback = $defaults[$defaults_index];

        $size = $override_planet_size ?: $fallback['size'];
        if (!$override_planet_size && !empty($parts[2])) {
            $size = (int) $parts[2];
            $size = max(24, min(160, $size));
        }

        $orbit = $override_orbit_size ?: ($fallback['orbit'] + ($count * 40));
        $duration = ($override_orbit_duration !== '') ? (float) $override_orbit_duration : ($fallback['duration'] + ($count * 0.4));
        $delay = ($override_orbit_delay !== '') ? (float) $override_orbit_delay : -1 * ($count * 0.7);
        $items[] = [
            'name' => $parts[0],
            'class' => $fallback['class'],
            'orbit' => $orbit,
            'duration' => $duration,
            'size' => $size,
            'icon' => !empty($parts[1]) ? $parts[1] : $fallback['icon'],
            'delay' => $delay,
        ];
        $count++;
        if ($count > 50) {
            break;
        }
    }

    if (!$items) {
        $items = array_map(function ($skill) {
            return [
                'name' => $skill['label'],
                'class' => $skill['class'],
                'orbit' => $skill['orbit'],
                'duration' => $skill['duration'],
                'size' => $skill['size'],
                'icon' => $skill['icon'],
            ];
        }, $defaults);
    }

    $use_projects = get_theme_mod('khokan_skills_use_projects', 0);
    $project_limit = absint(get_theme_mod('khokan_skills_project_limit', 4));
    if ($project_limit < 1) {
        $project_limit = 4;
    } elseif ($project_limit > 20) {
        $project_limit = 20;
    }

    if ($use_projects) {
        $project_query = new WP_Query([
            'post_type' => 'khokan_project',
            'post_status' => 'publish',
            'posts_per_page' => $project_limit,
            'orderby' => [
                'menu_order' => 'ASC',
                'date' => 'DESC',
            ],
        ]);

        if ($project_query->have_posts()) {
            while ($project_query->have_posts()) {
                $project_query->the_post();
                $defaults_index = $count % count($defaults);
                $fallback = $defaults[$defaults_index];

                $orbit = $fallback['orbit'] + ($count * 40);
                $duration = $fallback['duration'] + ($count * 0.4);
                $items[] = [
                    'name' => get_the_title(),
                    'class' => $fallback['class'],
                    'orbit' => $orbit,
                    'duration' => $duration,
                    'size' => $fallback['size'],
                    'icon' => get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: $fallback['icon'],
                    'delay' => -1 * ($count * 0.7),
                ];
                $count++;
            }
            wp_reset_postdata();
        }
    }

    $GLOBALS['khokan_skill_items'] = $items;
    return $items;
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

    if (!$name) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Please add your name before sending.',
        ];
        return;
    }

    if (!$email || !is_email($email)) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Please enter a valid email address.',
        ];
        return;
    }

    if (!$message) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Please add a message before sending.',
        ];
        return;
    }

    $admin_email = sanitize_email(get_option('admin_email'));
    $custom_email = sanitize_email(get_theme_mod('khokan_contact_email', ''));
    $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
    $fallback_email = $site_host ? 'noreply@' . $site_host : 'wordpress@localhost';

    if (!$admin_email) {
        $admin_email = $fallback_email;
    }

    // Always send to admin; include custom email if provided.
    $recipients = array_filter(array_unique(array_merge(
        $admin_email ? [$admin_email] : [],
        $custom_email ? [$custom_email] : []
    )));

    if (!$recipients) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'No valid recipient email is configured.',
        ];
        return;
    }

    $subject = sprintf('[Khokan Portfolio] Message from %s', $name ?: 'Website visitor');
    $headers = [];
    if ($admin_email) {
        $headers[] = 'From: ' . get_bloginfo('name') . ' <' . $admin_email . '>';
    }
    if ($email) {
        $headers[] = 'Reply-To: ' . $email;
    }
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';

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
	
	    $post_id = get_queried_object_id();
    if (is_singular() && $post_id) {
        $url = get_permalink($post_id) ?: $url;

        $singular_title = get_the_title($post_id);
        if (!empty($singular_title)) {
            $title = $singular_title;
        }

        $singular_desc = get_the_excerpt($post_id);
        if (!$singular_desc) {
            $content = get_post_field('post_content', $post_id);
            if (!empty($content)) {
                $singular_desc = wp_trim_words(wp_strip_all_tags($content), 30, '…');
            }
        }
        if (!empty($singular_desc)) {
            $description = $singular_desc;
        }

        $thumb_id = get_post_thumbnail_id($post_id);
        if ($thumb_id) {
            $image = wp_get_attachment_image_url($thumb_id, 'large') ?: $image;
            $image_meta = wp_get_attachment_metadata($thumb_id);
            if (!empty($image_meta['width'])) {
                $image_width = (int) $image_meta['width'];
            }
            if (!empty($image_meta['height'])) {
                $image_height = (int) $image_meta['height'];
            }
            $mime = get_post_mime_type($thumb_id);
            if (!empty($mime)) {
                $image_type = $mime;
            }
        }
    }

    if ($description) {
        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }
    echo '<link rel="canonical" href="' . esc_url($url) . '">' . "\n";
    echo '<meta property="og:locale" content="' . esc_attr($locale) . '">' . "\n";
    echo '<meta property="og:type" content="' . esc_attr(is_singular() ? 'article' : 'website') . '">' . "\n";
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

/**
 * Output dynamic background color.
 */
function khokan_output_custom_background_color()
{
    if (is_admin()) {
        return;
    }

    $bg = sanitize_hex_color(get_theme_mod('khokan_background_color', '#040b24')) ?: '#040b24';
    $accent = sanitize_hex_color(get_theme_mod('khokan_accent_color', '#7cd25e')) ?: '#7cd25e';
    $card = sanitize_hex_color(get_theme_mod('khokan_card_color', '#0b1744')) ?: '#0b1744';
    $card_dark = sanitize_hex_color(get_theme_mod('khokan_card_dark_color', '#081030')) ?: '#081030';
    $text = sanitize_hex_color(get_theme_mod('khokan_text_color', '#e8edff')) ?: '#e8edff';
    $muted = sanitize_hex_color(get_theme_mod('khokan_muted_color', '#c5cce5')) ?: '#c5cce5';
    $border = sanitize_hex_color(get_theme_mod('khokan_border_color', '#101a3a')) ?: '#101a3a';

    $primary_btn_bg = sanitize_hex_color(get_theme_mod('khokan_button_primary_bg', $accent)) ?: $accent;
    $primary_btn_text = sanitize_hex_color(get_theme_mod('khokan_button_primary_text', '#0a1a35')) ?: '#0a1a35';
    $primary_btn_hover = sanitize_hex_color(get_theme_mod('khokan_button_primary_hover', '#92e37b')) ?: '#92e37b';
    $secondary_btn_hover = sanitize_hex_color(get_theme_mod('khokan_button_secondary_hover', '#18264d')) ?: '#18264d';
    $link_hover = sanitize_hex_color(get_theme_mod('khokan_link_hover_color', $accent)) ?: $accent;
    $btn_radius = (int) get_theme_mod('khokan_button_radius', 10);
    $btn_radius = max(0, min(32, $btn_radius));
    $sun_core_size = (int) get_theme_mod('khokan_skills_center_size', 78);
    $sun_core_size = max(30, min(120, $sun_core_size));

    $card_radius = (int) get_theme_mod('khokan_projects_card_radius', 14);
    $card_radius = max(0, min(40, $card_radius));
    $card_shadow = get_theme_mod('khokan_projects_card_shadow', 'medium');
    $shadow_value = '0 18px 28px rgba(0,0,0,0.35)';
    if ($card_shadow === 'none') {
        $shadow_value = 'none';
    } elseif ($card_shadow === 'soft') {
        $shadow_value = '0 10px 20px rgba(0,0,0,0.28)';
    } elseif ($card_shadow === 'strong') {
        $shadow_value = '0 24px 36px rgba(0,0,0,0.45)';
    }

    $services_radius = (int) get_theme_mod('khokan_services_card_radius', 16);
    $services_radius = max(0, min(40, $services_radius));
    $services_shadow = get_theme_mod('khokan_services_card_shadow', 'medium');
    $services_shadow_value = '0 18px 32px rgba(0, 0, 0, 0.32)';
    if ($services_shadow === 'none') {
        $services_shadow_value = 'none';
    } elseif ($services_shadow === 'soft') {
        $services_shadow_value = '0 12px 24px rgba(0,0,0,0.26)';
    } elseif ($services_shadow === 'strong') {
        $services_shadow_value = '0 22px 36px rgba(0,0,0,0.42)';
    }

    echo '<style id="khokan-custom-bg">:root{--bg:' . esc_html($bg) . ';--accent:' . esc_html($accent) . ';--card:' . esc_html($card) . ';--card-dark:' . esc_html($card_dark) . ';--text:' . esc_html($text) . ';--muted:' . esc_html($muted) . ';--border:' . esc_html($border) . ';--btn-radius:' . esc_attr($btn_radius) . 'px;--btn-primary-bg:' . esc_html($primary_btn_bg) . ';--btn-primary-text:' . esc_html($primary_btn_text) . ';--btn-primary-hover:' . esc_html($primary_btn_hover) . ';--btn-secondary-hover:' . esc_html($secondary_btn_hover) . ';--link-hover:' . esc_html($link_hover) . ';--project-radius:' . esc_attr($card_radius) . 'px;--project-shadow:' . esc_html($shadow_value) . ';--services-radius:' . esc_attr($services_radius) . 'px;--services-shadow:' . esc_html($services_shadow_value) . ';--sun-core-size:' . esc_attr($sun_core_size) . '%;}body{background:' . esc_html($bg) . ';color:' . esc_html($text) . ';}.page{background-color:' . esc_html($bg) . ';}a{color:' . esc_html($text) . ';}.hero .subline{color:' . esc_html($muted) . ';}.primary-btn{background:' . esc_html($primary_btn_bg) . ';color:' . esc_html($primary_btn_text) . ';border-radius:var(--btn-radius);} .secondary-btn,.ghost-btn{border-radius:var(--btn-radius);} .project-card,.contact-form,.social-btn{border-color:' . esc_html($border) . ';}</style>' . "\n";
}
add_action('wp_head', 'khokan_output_custom_background_color', 5);

/**
 * Output dynamic typography for hero text.
 */
function khokan_output_typography_styles()
{
    if (is_admin()) {
        return;
    }

    $tag_color = sanitize_hex_color(get_theme_mod('khokan_hero_tagline_color', '#e8edff')) ?: '#e8edff';
    $sub_color = sanitize_hex_color(get_theme_mod('khokan_hero_subline_color', '#c5cce5')) ?: '#c5cce5';

    $tag_size = (int) get_theme_mod('khokan_hero_tagline_size', 34);
    $tag_size = max(16, min(72, $tag_size));

    $sub_size = (int) get_theme_mod('khokan_hero_subline_size', 18);
    $sub_size = max(12, min(48, $sub_size));

    $body_size = (int) get_theme_mod('khokan_body_font_size', 16);
    $body_size = max(12, min(24, $body_size));
    $body_line = khokan_sanitize_float(get_theme_mod('khokan_body_line_height', 1.6));

    $h1 = (int) get_theme_mod('khokan_h1_size', 40);
    $h2 = (int) get_theme_mod('khokan_h2_size', 32);
    $h3 = (int) get_theme_mod('khokan_h3_size', 26);
    $h1 = max(28, min(72, $h1));
    $h2 = max(22, min(56, $h2));
    $h3 = max(18, min(42, $h3));

    $cols_desktop = khokan_sanitize_grid_int(get_theme_mod('khokan_projects_columns_desktop', 3));
    $cols_tablet = khokan_sanitize_grid_int(get_theme_mod('khokan_projects_columns_tablet', 2));
    $cols_mobile = khokan_sanitize_grid_int(get_theme_mod('khokan_projects_columns_mobile', 1));

    $services_cols_desktop = khokan_sanitize_grid_int(get_theme_mod('khokan_services_columns_desktop', 3));
    $services_cols_tablet = khokan_sanitize_grid_int(get_theme_mod('khokan_services_columns_tablet', 2));
    $services_cols_mobile = khokan_sanitize_grid_int(get_theme_mod('khokan_services_columns_mobile', 1));

    $reduce_motion = get_theme_mod('khokan_reduce_motion', 0) ? '1' : '0';

    $projects_var_css = '.projects-grid{--projects-cols-desktop:' . esc_attr($cols_desktop) . ';--projects-cols-tablet:' . esc_attr($cols_tablet) . ';--projects-cols-mobile:' . esc_attr($cols_mobile) . ';}';
    $services_var_css = '.services-grid{--services-cols-desktop:' . esc_attr($services_cols_desktop) . ';--services-cols-tablet:' . esc_attr($services_cols_tablet) . ';--services-cols-mobile:' . esc_attr($services_cols_mobile) . ';}';

    echo '<style id="khokan-typography">body{font-size:' . esc_attr($body_size) . 'px;line-height:' . esc_attr($body_line) . ';}.headline .tagline{color:' . esc_html($tag_color) . ';font-size:' . esc_attr($tag_size) . 'px;}.headline .subline{color:' . esc_html($sub_color) . ';font-size:' . esc_attr($sub_size) . 'px;}h1{font-size:' . esc_attr($h1) . 'px;}h2{font-size:' . esc_attr($h2) . 'px;}h3{font-size:' . esc_attr($h3) . 'px;}' .
        $projects_var_css .
        $services_var_css .
        ($reduce_motion === '1' ? '.orbit,.sun,.planet,.page-glow{animation:none !important;transition:none !important;} .orbit-path{animation:none !important;} .primary-btn,.secondary-btn,.ghost-btn{transition:none !important;}' : '') .
        '</style>' . "\n";
}
add_action('wp_head', 'khokan_output_typography_styles', 6);

/**
 * Output project schema JSON-LD.
 */
function khokan_output_project_schema()
{
    if (is_admin()) {
        return;
    }

    $projects_data = khokan_get_projects();
    $items = array_slice($projects_data['items'] ?? [], 0, 10);
    if (!$items) {
        return;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'itemListElement' => [],
    ];

    foreach ($items as $index => $project) {
        $schema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'CreativeWork',
                'name' => $project['title'],
                'description' => $project['description'],
                'url' => $project['link'],
                'image' => $project['image'],
            ],
        ];
    }

    echo '<script type="application/ld+json">' . wp_json_encode($schema) . '</script>' . "\n";
}
add_action('wp_head', 'khokan_output_project_schema', 3);

/**
 * Customizer helper script for adding skills via button.
 */
function khokan_customize_controls_script()
{
    $script = <<<'JS'
(function ($, api) {
    api.bind('ready', function () {
        var listSetting = api('khokan_skills_list');
        var labelSetting = api('khokan_skill_new_label');
        var iconSetting = api('khokan_skill_new_icon');
        var sizeSetting = api('khokan_skill_new_size');

        var button = $('#khokan-skill-add-btn');
        var skillsListContainer = $('<div class="khokan-skills-live-list"></div>');
        var skillsSection = $('#customize-control-khokan_skill_new_label').closest('li.customize-control');

        if (!button.length || !listSetting || !labelSetting) {
            return;
        }

        // Build live list UI
        skillsSection.after(skillsListContainer);

        function parseList(val) {
            var lines = (val || '').split(/\r?\n/).map(function (line) { return line.trim(); }).filter(Boolean);
            return lines.map(function (line) {
                var parts = line.split('|').map(function (p) { return p.trim(); });
                return { line: line, label: parts[0] || '', icon: parts[1] || '', size: parts[2] || '' };
            });
        }

        function renderList() {
            var items = parseList(listSetting());
            if (!items.length) {
                skillsListContainer.html('<p class="description">No skills added yet.</p>');
                return;
            }
            var html = '<ul class="khokan-skills-list">';
            items.forEach(function (item, idx) {
                html += '<li data-index="' + idx + '">' +
                    '<span class="skill-label">' + _.escape(item.label || '(no label)') + '</span>' +
                    (item.size ? ' <span class="skill-size">(' + _.escape(item.size) + 'px)</span>' : '') +
                    '<button type="button" class="button-link delete-skill" data-index="' + idx + '">Delete</button>' +
                '</li>';
            });
            html += '</ul>';
            skillsListContainer.html(html);
        }

        skillsListContainer.on('click', '.delete-skill', function () {
            var idx = parseInt($(this).data('index'), 10);
            var items = parseList(listSetting());
            if (idx >= 0 && idx < items.length) {
                items.splice(idx, 1);
                var newVal = items.map(function (i) {
                    var parts = [i.label];
                    if (i.icon) { parts.push(i.icon); }
                    if (i.size) { parts.push(i.size); }
                    return parts.join('|');
                }).join("\n");
                listSetting.set(newVal);
                renderList();
            }
        });

        listSetting.bind(renderList);
        renderList();

        button.on('click', function (e) {
            e.preventDefault();
            var label = (labelSetting() || '').trim();
            var iconId = iconSetting();
            var size = (sizeSetting() || '').toString().trim();
            var iconUrl = '';

            if (!label) {
                alert('Please add a label before adding a skill.');
                return;
            }

            if (iconId) {
                var attachment = wp.media.attachment(iconId);
                if (attachment && attachment.get('url')) {
                    iconUrl = attachment.get('url');
                }
            }

            var lineParts = [label];
            if (iconUrl) {
                lineParts.push(iconUrl);
            }
            if (size) {
                lineParts.push(size);
            }

            var current = listSetting() || '';
            var newLine = lineParts.join('|');
            var newValue = current ? current + "\n" + newLine : newLine;

            listSetting.set(newValue);
            labelSetting.set('');
            iconSetting.set('');
            sizeSetting.set('');
            renderList();
        });
    });
})(jQuery, wp.customize);
JS;
    wp_add_inline_script('customize-controls', $script);
}
add_action('customize_controls_enqueue_scripts', 'khokan_customize_controls_script');
