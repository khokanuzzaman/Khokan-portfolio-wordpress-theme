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
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    register_nav_menus([
        'header-menu' => 'Header Menu',
    ]);
});

add_filter('the_generator', '__return_empty_string');

function khokan_is_local_environment()
{
    if (function_exists('wp_get_environment_type')) {
        $environment = wp_get_environment_type();
        if (in_array($environment, ['local', 'development'], true)) {
            return true;
        }
    }

    $host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
    if ($host === '') {
        return false;
    }

    if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    return (bool) preg_match('/(\.test|\.local)$/i', $host);
}

function khokan_get_request_ip()
{
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string) $_SERVER['REMOTE_ADDR'] : '';
    $ip = preg_replace('/[^0-9a-fA-F:\.,]/', '', $ip);
    return $ip ?: 'unknown';
}

function khokan_rate_limit_request($action, $limit, $window_seconds)
{
    $fingerprint = $action . '|' . khokan_get_request_ip();
    $key = 'khokan_rl_' . md5($fingerprint);
    $attempts = (int) get_transient($key);

    if ($attempts >= $limit) {
        return new WP_Error(
            'rate_limited',
            sprintf('Too many attempts. Please wait %d minute(s) and try again.', max(1, (int) ceil($window_seconds / MINUTE_IN_SECONDS)))
        );
    }

    set_transient($key, $attempts + 1, $window_seconds);
    return true;
}

function khokan_submission_tripped_honeypot($field_name)
{
    if (!isset($_POST[$field_name])) {
        return false;
    }

    $value = sanitize_text_field(wp_unslash($_POST[$field_name]));
    return $value !== '';
}

function khokan_normalize_phone($value)
{
    $value = sanitize_text_field(wp_unslash((string) $value));
    $value = preg_replace('/[^0-9+]/', '', $value);

    return $value ?: '';
}

function khokan_phone_has_min_digits($value, $minimum = 7)
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    return strlen($digits) >= $minimum;
}

function jrc_get_management_capability()
{
    return 'jrc_manage_course';
}

function jrc_get_required_administrator_caps()
{
    return [
        'read',
        'edit_posts',
        'delete_posts',
        'publish_posts',
        'upload_files',
        'list_users',
        'create_users',
        'edit_users',
        'delete_users',
        'promote_users',
        'manage_options',
        jrc_get_management_capability(),
    ];
}

function jrc_user_has_course_management_access($user)
{
    if (is_numeric($user)) {
        $user = get_userdata((int) $user);
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    if (function_exists('is_super_admin') && is_super_admin($user->ID)) {
        return true;
    }

    if (in_array('administrator', (array) $user->roles, true)) {
        return true;
    }

    $allcaps = is_array($user->allcaps ?? null) ? $user->allcaps : [];
    $caps = is_array($user->caps ?? null) ? $user->caps : [];

    return !empty($allcaps['manage_options'])
        || !empty($caps['manage_options'])
        || !empty($allcaps[jrc_get_management_capability()])
        || !empty($caps[jrc_get_management_capability()]);
}

function jrc_current_user_can_manage_course()
{
    if (jrc_user_has_course_management_access(wp_get_current_user())) {
        return true;
    }

    return current_user_can('manage_options') || current_user_can(jrc_get_management_capability());
}

function jrc_user_can_manage_course($user)
{
    if (jrc_user_has_course_management_access($user)) {
        return true;
    }

    if (is_numeric($user)) {
        $user = get_userdata((int) $user);
    }

    return $user instanceof WP_User && (user_can($user, 'manage_options') || user_can($user, jrc_get_management_capability()));
}

add_filter('map_meta_cap', function ($caps, $cap, $user_id) {
    if ($cap !== jrc_get_management_capability()) {
        return $caps;
    }

    return jrc_user_has_course_management_access($user_id) ? ['read'] : ['do_not_allow'];
}, 10, 4);

add_filter('user_has_cap', function ($allcaps, $caps, $args, $user) {
    if (!$user instanceof WP_User) {
        return $allcaps;
    }

    if (!in_array('administrator', (array) $user->roles, true)) {
        return $allcaps;
    }

    foreach (jrc_get_required_administrator_caps() as $cap) {
        $allcaps[$cap] = true;
    }

    if (
        !in_array(jrc_get_management_capability(), (array) $caps, true) &&
        !in_array('manage_options', (array) $caps, true)
    ) {
        return $allcaps;
    }

    return $allcaps;
}, 999, 4);

add_filter('option_page_capability_jrc_options_group', function () {
    return jrc_get_management_capability();
});

function jrc_get_frontend_route_slugs()
{
    return [
        'login' => 'login',
        'signup' => 'signup',
        'dashboard' => 'dashboard',
        'verify' => 'verify-email',
    ];
}

function jrc_get_frontend_route_url($route, array $args = [], $fragment = '')
{
    $slugs = jrc_get_frontend_route_slugs();
    $slug = trim((string) ($slugs[$route] ?? ''), '/');

    if ($slug === '') {
        $url = home_url('/');
    } else {
        $url = home_url('/' . $slug . '/');
    }

    if (!empty($args)) {
        $url = add_query_arg($args, $url);
    }

    if ($fragment !== '') {
        $url .= '#' . ltrim((string) $fragment, '#');
    }

    return $url;
}

function jrc_get_auth_page_url($screen, $course_key = '', array $args = [])
{
    $course_key = jrc_get_requested_course_key($course_key);
    if ($course_key !== '') {
        $args['jrc_course'] = $course_key;
    }

    return jrc_get_frontend_route_url($screen, $args);
}

function jrc_get_current_request_path()
{
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    if ($request_path === '') {
        $request_path = '/';
    }

    $home_path = trim((string) wp_parse_url(home_url('/'), PHP_URL_PATH), '/');
    if ($home_path !== '') {
        $prefix = '/' . $home_path;
        if (strpos($request_path, $prefix) === 0) {
            $request_path = substr($request_path, strlen($prefix));
            if ($request_path === '') {
                $request_path = '/';
            }
        }
    }

    return trim($request_path, '/');
}

function jrc_get_current_frontend_route()
{
    $path = jrc_get_current_request_path();
    foreach (jrc_get_frontend_route_slugs() as $route => $slug) {
        if ($path === trim($slug, '/')) {
            return $route;
        }
    }

    return '';
}

function jrc_get_student_dashboard_url()
{
    return jrc_get_frontend_route_url('dashboard');
}

function jrc_get_course_application_page_url($course_key = '', array $args = [])
{
    $job_ready_pages = get_pages([
        'meta_key' => '_wp_page_template',
        'meta_value' => 'page-job-ready-course.php',
        'number' => 1,
        'post_status' => ['publish', 'private'],
    ]);

    $base_url = !empty($job_ready_pages) ? get_permalink($job_ready_pages[0]) : home_url('/');
    if (!$base_url) {
        $base_url = home_url('/');
    }

    $course_key = jrc_get_requested_course_key($course_key);
    if ($course_key !== '') {
        $args['jrc_course'] = $course_key;
    }

    if (!empty($args)) {
        $base_url = add_query_arg($args, $base_url);
    }

    return $base_url . '#course-application';
}

function jrc_get_logout_redirect_url($course_key = '')
{
    if (in_array(jrc_get_current_frontend_route(), ['login', 'signup', 'dashboard', 'verify'], true)) {
        return jrc_get_course_application_page_url($course_key);
    }

    if (function_exists('is_page_template') && is_page_template('page-job-ready-course.php')) {
        return jrc_get_course_application_page_url($course_key);
    }

    return home_url('/');
}

function jrc_render_logout_form($redirect_url = '', $label = 'Logout', $button_class = 'secondary-btn', $form_class = 'jrc-logout-form')
{
    $redirect_url = $redirect_url ? esc_url_raw($redirect_url) : home_url('/');
    if (!wp_validate_redirect($redirect_url, false)) {
        $redirect_url = home_url('/');
    }

    ob_start();
    ?>
    <form class="<?php echo esc_attr($form_class); ?>" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="jrc_logout">
        <input type="hidden" name="nonce" value="<?php echo esc_attr(wp_create_nonce('jrc_logout')); ?>">
        <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>">
        <button type="submit" class="<?php echo esc_attr($button_class); ?>"><?php echo esc_html($label); ?></button>
    </form>
    <?php

    return ob_get_clean();
}

add_action('admin_post_jrc_logout', 'jrc_handle_logout');
add_action('admin_post_nopriv_jrc_logout', 'jrc_handle_logout');
function jrc_handle_logout()
{
    $redirect = isset($_REQUEST['redirect_to']) ? esc_url_raw(wp_unslash($_REQUEST['redirect_to'])) : home_url('/');
    if (!wp_validate_redirect($redirect, false)) {
        $redirect = home_url('/');
    }

    if (!is_user_logged_in()) {
        wp_safe_redirect($redirect);
        exit;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if ($nonce === '' || !wp_verify_nonce($nonce, 'jrc_logout')) {
        wp_safe_redirect(add_query_arg('jrc_auth_error', 'invalid_request', $redirect));
        exit;
    }

    wp_logout();
    wp_safe_redirect($redirect);
    exit;
}

function jrc_get_application_course_notes()
{
    $notes = [];
    if (!function_exists('jrc_get_course_data')) {
        return $notes;
    }

    $course = jrc_get_course_data();
    $items = $course['course_paths']['items'] ?? [];
    foreach ($items as $item) {
        $key = sanitize_key((string) ($item['key'] ?? ''));
        $note = trim((string) ($item['apply_note'] ?? ''));
        if ($key !== '' && $note !== '') {
            $notes[$key] = $note;
        }
    }

    return $notes;
}

function jrc_is_user_email_verified($user)
{
    if (is_numeric($user)) {
        $user = get_userdata((int) $user);
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    if (jrc_user_can_manage_course($user)) {
        return true;
    }

    $status = (string) get_user_meta($user->ID, 'jrc_email_verified', true);

    return $status === '' || $status === 'yes' || $status === 'verified';
}

function jrc_user_requires_email_verification($user)
{
    if (is_numeric($user)) {
        $user = get_userdata((int) $user);
    }

    if (!$user instanceof WP_User || !$user->exists()) {
        return false;
    }

    if (jrc_user_can_manage_course($user)) {
        return false;
    }

    return !jrc_is_user_email_verified($user);
}

function jrc_send_email_verification($user_id, $course_key = '')
{
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User || !$user->exists()) {
        return new WP_Error('invalid_user', 'Invalid user.');
    }

    if (jrc_user_can_manage_course($user)) {
        return true;
    }

    if (!is_email($user->user_email)) {
        return new WP_Error('invalid_email', 'Missing email address.');
    }

    $token = wp_generate_password(32, false, false);
    update_user_meta($user->ID, 'jrc_email_verified', 'pending');
    update_user_meta($user->ID, 'jrc_email_verification_token', $token);
    update_user_meta($user->ID, 'jrc_email_verification_created_at', time());
    update_user_meta($user->ID, 'jrc_email_verification_sent_at', time());

    $verify_url = jrc_get_auth_page_url('verify', $course_key, [
        'uid' => $user->ID,
        'token' => $token,
    ]);

    $name = $user->display_name ?: $user->user_login;
    $subject = 'Verify your email address';
    $body = "Hi {$name},\n\nPlease verify your email address to continue your course application.\n\nVerification link:\n{$verify_url}\n\nThis link will expire in 48 hours.\n\nIf you did not request this, you can ignore this email.";
    $sent = wp_mail($user->user_email, $subject, $body);

    if (!$sent) {
        return new WP_Error('mail_failed', 'Could not send the verification email.');
    }

    return true;
}

function jrc_maybe_send_email_verification($user_id, $course_key = '', $force = false)
{
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User || !$user->exists() || jrc_is_user_email_verified($user)) {
        return true;
    }

    $last_sent = (int) get_user_meta($user->ID, 'jrc_email_verification_sent_at', true);
    if (!$force && $last_sent > 0 && (time() - $last_sent) < (15 * MINUTE_IN_SECONDS)) {
        return true;
    }

    return jrc_send_email_verification($user->ID, $course_key);
}

function jrc_verify_email_token($user_id, $token)
{
    $user = get_userdata((int) $user_id);
    if (!$user instanceof WP_User || !$user->exists()) {
        return new WP_Error('invalid_user', 'Invalid verification user.');
    }

    if (jrc_user_can_manage_course($user)) {
        return true;
    }

    $stored_token = (string) get_user_meta($user->ID, 'jrc_email_verification_token', true);
    $created_at = (int) get_user_meta($user->ID, 'jrc_email_verification_created_at', true);
    if ($stored_token === '' || $token === '' || !hash_equals($stored_token, (string) $token)) {
        return new WP_Error('invalid_token', 'Invalid verification link.');
    }

    if ($created_at > 0 && (time() - $created_at) > (2 * DAY_IN_SECONDS)) {
        return new WP_Error('expired_token', 'Verification link expired.');
    }

    update_user_meta($user->ID, 'jrc_email_verified', 'yes');
    delete_user_meta($user->ID, 'jrc_email_verification_token');
    delete_user_meta($user->ID, 'jrc_email_verification_created_at');

    return true;
}

function jrc_render_verification_resend_form($course_key = '')
{
    if (!is_user_logged_in()) {
        return '';
    }

    $nonce = wp_create_nonce('jrc_resend_verification');

    ob_start();
    ?>
    <form class="jrc-inline-action" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="jrc_resend_verification">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="jrc_course" value="<?php echo esc_attr(jrc_get_requested_course_key($course_key)); ?>">
        <button type="submit" class="secondary-btn">Resend Verification Email</button>
    </form>
    <?php

    return ob_get_clean();
}

function jrc_render_email_verification_gate($course_key = '')
{
    $user = wp_get_current_user();
    $email = $user instanceof WP_User ? $user->user_email : '';
    $verify_url = jrc_get_auth_page_url('verify', $course_key);

    ob_start();
    ?>
    <div class="jrc-auth-shell">
        <div class="jrc-auth-intro">
            <span class="jrc-auth-intro__eyebrow">Email Verification</span>
            <h3>Verify your email before applying</h3>
            <p class="section-subtitle">We sent a verification link to <strong><?php echo esc_html($email); ?></strong>. Verify the email first, then come back to complete your application.</p>
        </div>
        <div class="jrc-auth-selected">
            <span class="jrc-auth-selected__label">Next Step</span>
            <strong class="jrc-auth-selected__value">Open your inbox and confirm your email address</strong>
            <div class="jrc-inline-actions">
                <a class="primary-btn" href="<?php echo esc_url($verify_url); ?>">Open Verification Page</a>
                <?php echo jrc_render_verification_resend_form($course_key); ?>
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_filter('show_admin_bar', function ($show) {
    if (!is_user_logged_in()) {
        return $show;
    }

    return jrc_current_user_can_manage_course() ? $show : false;
});

add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    if (!$user instanceof WP_User || !$user->exists()) {
        return $redirect_to;
    }

    if (jrc_user_requires_email_verification($user)) {
        return jrc_get_auth_page_url('verify');
    }

    return jrc_user_can_manage_course($user) ? $redirect_to : jrc_get_student_dashboard_url();
}, 10, 3);

add_action('admin_init', function () {
    if (!is_user_logged_in() || jrc_current_user_can_manage_course() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    global $pagenow;
    if (in_array($pagenow, ['admin-post.php', 'async-upload.php'], true)) {
        return;
    }

    if (function_exists('wp_is_json_request') && wp_is_json_request()) {
        return;
    }

    $current_user = wp_get_current_user();
    $redirect_url = ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user))
        ? jrc_get_auth_page_url('verify')
        : jrc_get_student_dashboard_url();

    wp_safe_redirect($redirect_url);
    exit;
}, 1);

function jrc_get_quiz_language_map(array $questions)
{
    $language_map = [];

    foreach ($questions as $question) {
        $label = trim((string) ($question['language'] ?? ''));
        if ($label === '') {
            continue;
        }

        $key = sanitize_title($label);
        if ($key === '' || isset($language_map[$key])) {
            continue;
        }

        $language_map[$key] = $label;
    }

    return $language_map;
}

function jrc_normalize_quiz_answer($value)
{
    $value = wp_strip_all_tags((string) $value);
    $value = preg_replace('/\s+/u', ' ', trim($value));

    if (function_exists('mb_strtolower')) {
        return mb_strtolower($value, 'UTF-8');
    }

    return strtolower($value);
}

function jrc_score_quiz_submission(array $questions, $language_key, array $answers, $pass_percent)
{
    $score = 0;
    $total = 0;
    $language_key = sanitize_title((string) $language_key);

    foreach ($questions as $index => $question) {
        $question_language = sanitize_title(trim((string) ($question['language'] ?? '')));
        if ($language_key !== '' && $question_language !== $language_key) {
            continue;
        }

        $points = max(1, (int) ($question['points'] ?? 1));
        $expected_answers = array_filter(array_map('jrc_normalize_quiz_answer', explode('|', (string) ($question['answer'] ?? ''))));
        $submitted_answer = jrc_normalize_quiz_answer($answers['q' . $index] ?? '');

        $total += $points;

        if (!empty($expected_answers) && in_array($submitted_answer, $expected_answers, true)) {
            $score += $points;
        }
    }

    $percent = $total > 0 ? (int) round(($score / $total) * 100) : 0;

    return [
        'score' => $score,
        'total' => $total,
        'percent' => $percent,
        'passed' => $percent >= max(0, min(100, (int) $pass_percent)),
    ];
}

function jrc_build_quiz_redirect_url($base_url, $coupon_code = '', $coupon_param = 'coupon')
{
    $base_url = esc_url_raw((string) $base_url);
    if ($base_url === '') {
        return '';
    }

    $coupon_param = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $coupon_param);
    if ($coupon_code !== '' && $coupon_param !== '') {
        return esc_url_raw(add_query_arg($coupon_param, $coupon_code, $base_url));
    }

    return $base_url;
}

function jrc_can_link_application_user(WP_User $user)
{
    return !user_can($user, 'edit_posts') && !jrc_user_can_manage_course($user);
}

if (khokan_is_local_environment()) {
    // Preserve local/dev API testing without widening production auth surface.
    add_filter('wp_is_application_passwords_available', '__return_true');
    add_filter('wp_is_application_passwords_available_for_user', '__return_true', 10, 2);
} else {
    // Disable rarely needed remote auth surfaces on production.
    add_filter('xmlrpc_enabled', '__return_false');
    add_filter('wp_is_application_passwords_available', '__return_false');
    add_filter('wp_is_application_passwords_available_for_user', '__return_false', 10, 2);
}

add_filter('xmlrpc_methods', function ($methods) {
    unset($methods['pingback.ping'], $methods['pingback.extensions.getPingbacks']);
    return $methods;
});

add_action('send_headers', function () {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), geolocation=(), microphone=()');
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
	
	if (is_singular() && comments_open() && get_option('thread_comments')) {
        wp_enqueue_script('comment-reply');
	}
});

add_action('init', function () {
    $admin_role = get_role('administrator');
    if ($admin_role) {
        foreach (jrc_get_required_administrator_caps() as $cap) {
            if (!$admin_role->has_cap($cap)) {
                $admin_role->add_cap($cap);
            }
        }
    }

    register_post_type('jrc_quiz_attempt', [
        'labels' => [
            'name' => 'Basic Test Submissions',
            'singular_name' => 'Basic Test Submission',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'menu_icon' => 'dashicons-clipboard',
        'supports' => ['title'],
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ]);
});

add_filter('manage_jrc_quiz_attempt_posts_columns', function ($columns) {
    return [
        'cb' => $columns['cb'],
        'title' => 'Student',
        'quiz_phone' => 'Phone',
        'quiz_email' => 'Email',
        'quiz_score' => 'Score',
        'quiz_passed' => 'Passed',
        'quiz_language' => 'Language',
        'date' => $columns['date'],
    ];
});

add_action('manage_jrc_quiz_attempt_posts_custom_column', function ($column, $post_id) {
    if ($column === 'quiz_phone') {
        echo esc_html(get_post_meta($post_id, 'student_phone', true));
        return;
    }
    if ($column === 'quiz_email') {
        echo esc_html(get_post_meta($post_id, 'student_email', true));
        return;
    }
    if ($column === 'quiz_score') {
        $score = get_post_meta($post_id, 'quiz_score', true);
        $total = get_post_meta($post_id, 'quiz_total', true);
        $percent = get_post_meta($post_id, 'quiz_percent', true);
        $label = $score !== '' ? $score . '/' . $total : '';
        if ($percent !== '') {
            $label .= ' (' . $percent . '%)';
        }
        echo esc_html(trim($label));
        return;
    }
    if ($column === 'quiz_passed') {
        $passed = get_post_meta($post_id, 'quiz_passed', true);
        echo esc_html($passed === 'yes' ? 'Yes' : 'No');
        return;
    }
    if ($column === 'quiz_language') {
        echo esc_html(get_post_meta($post_id, 'quiz_language', true));
        return;
    }
}, 10, 2);

add_action('wp_ajax_jrc_quiz_submit', 'jrc_handle_quiz_submit');
add_action('wp_ajax_nopriv_jrc_quiz_submit', 'jrc_handle_quiz_submit');

function jrc_handle_quiz_submit()
{
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_quiz_submit')) {
        wp_send_json_error(['message' => 'Invalid nonce'], 403);
    }

    if (khokan_submission_tripped_honeypot('quiz_website')) {
        wp_send_json_error(['message' => 'Request blocked'], 400);
    }

    $rate_limit = khokan_rate_limit_request('jrc_quiz_submit', 8, 10 * MINUTE_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        wp_send_json_error(['message' => $rate_limit->get_error_message()], 429);
    }

    $name = isset($_POST['student_name']) ? sanitize_text_field(wp_unslash($_POST['student_name'])) : '';
    $email = isset($_POST['student_email']) ? sanitize_email(wp_unslash($_POST['student_email'])) : '';
    $phone = khokan_normalize_phone($_POST['student_phone'] ?? '');
    $language = isset($_POST['language']) ? sanitize_title(wp_unslash($_POST['language'])) : '';
    $raw_answers = (isset($_POST['answers']) && is_array($_POST['answers'])) ? wp_unslash($_POST['answers']) : [];
    $answers = [];

    foreach ($raw_answers as $key => $value) {
        $question_key = sanitize_key((string) $key);
        if ($question_key === '') {
            continue;
        }

        $answers[$question_key] = sanitize_textarea_field((string) $value);
    }

    if ($name === '' || $phone === '') {
        wp_send_json_error(['message' => 'Full name and phone are required.'], 422);
    }

    if (!khokan_phone_has_min_digits($phone)) {
        wp_send_json_error(['message' => 'Please enter a valid phone number.'], 422);
    }

    if ($email !== '' && !is_email($email)) {
        wp_send_json_error(['message' => 'Please enter a valid email address.'], 422);
    }

    $quiz = jrc_get_quiz_data();
    $questions = $quiz['questions'] ?? [];
    $language_map = jrc_get_quiz_language_map($questions);
    if ($language !== '' && !isset($language_map[$language])) {
        wp_send_json_error(['message' => 'Invalid quiz language selected.'], 422);
    }

    $result = jrc_score_quiz_submission($questions, $language, $answers, $quiz['pass_percent'] ?? 60);
    if ($result['total'] <= 0) {
        wp_send_json_error(['message' => 'Could not score this submission.'], 500);
    }

    $coupon = '';
    $redirect = esc_url_raw((string) ($quiz['redirect_link'] ?? ''));

    $title = $name !== '' ? $name : 'Quiz Attempt';
    $title .= ' - ' . current_time('Y-m-d H:i');

    $post_id = wp_insert_post([
        'post_type' => 'jrc_quiz_attempt',
        'post_status' => 'private',
        'post_title' => $title,
    ], true);

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Could not save attempt'], 500);
    }

    update_post_meta($post_id, 'student_name', $name);
    update_post_meta($post_id, 'student_email', $email);
    update_post_meta($post_id, 'student_phone', $phone);
    update_post_meta($post_id, 'quiz_language', $language ? ($language_map[$language] ?? $language) : '');
    update_post_meta($post_id, 'quiz_score', $result['score']);
    update_post_meta($post_id, 'quiz_total', $result['total']);
    update_post_meta($post_id, 'quiz_percent', $result['percent']);
    update_post_meta($post_id, 'quiz_passed', $result['passed'] ? 'yes' : 'no');
    update_post_meta($post_id, 'quiz_coupon', $coupon);
    update_post_meta($post_id, 'quiz_redirect', $redirect);

    wp_send_json_success([
        'id' => $post_id,
        'score' => $result['score'],
        'total' => $result['total'],
        'percent' => $result['percent'],
        'passed' => $result['passed'],
        'redirect' => $redirect,
    ]);
}

/**
 * Job Ready Course: Application system (form + dashboard).
 */
add_action('init', function () {
    if (!get_role('jrc_student')) {
        add_role('jrc_student', 'Student', ['read' => true]);
    }

    $management_cap = jrc_get_management_capability();

    register_post_type('jrc_application', [
        'labels' => [
            'name' => 'Applications',
            'singular_name' => 'Application',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => 'jrc-options',
        'menu_icon' => 'dashicons-forms',
        'supports' => ['title'],
        'capability_type' => 'jrc_application',
        'map_meta_cap' => true,
        'capabilities' => [
            'edit_post' => $management_cap,
            'read_post' => $management_cap,
            'delete_post' => $management_cap,
            'edit_posts' => $management_cap,
            'edit_others_posts' => $management_cap,
            'delete_posts' => $management_cap,
            'publish_posts' => $management_cap,
            'read_private_posts' => $management_cap,
        ],
    ]);
});

function jrc_get_application_statuses()
{
    return [
        'applied' => 'Applied',
        'test_taken' => 'Test Taken',
        'payment_pending' => 'Payment Pending',
        'enrolled' => 'Enrolled',
    ];
}

function jrc_get_application_payment_plan_labels()
{
    return [
        'full' => 'Full ৳9,000',
        'installment' => '3 Installments',
        'monthly' => 'Monthly ৳1,500/mo',
    ];
}

function jrc_get_application_course_options()
{
    if (function_exists('jrc_get_course_data')) {
        $course = jrc_get_course_data();
        $items = $course['course_paths']['items'] ?? [];
        $options = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $key = sanitize_key((string) ($item['key'] ?? ''));
            $label = trim((string) ($item['application_label'] ?? $item['title'] ?? ''));
            if ($key === '' || $label === '') {
                continue;
            }

            $options[$key] = $label;
        }

        if (!empty($options)) {
            return $options;
        }
    }

    return [
        'react' => 'React Course - Web Development',
        'flutter' => 'Flutter + AI Integration',
        'cursor_mastery' => 'Cursor Mastery',
    ];
}

function jrc_get_course_seat_total($course_key)
{
    if (!function_exists('jrc_get_course_data')) {
        return 30;
    }
    $course = jrc_get_course_data();
    $items = $course['course_paths']['items'] ?? [];
    foreach ($items as $item) {
        if (!is_array($item) || ($item['key'] ?? '') !== $course_key) {
            continue;
        }
        $total = (int) ($item['seat_total'] ?? 30);
        return $total > 0 ? $total : 30;
    }
    return 30;
}

function jrc_get_course_seat_stats($course_key)
{
    $total = jrc_get_course_seat_total($course_key);
    $base_args = [
        'post_type' => 'jrc_application',
        'post_status' => 'private',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_query' => [
            [
                'key' => 'student_course',
                'value' => $course_key,
            ],
        ],
    ];
    $applied = count(get_posts($base_args));
    $occupied_args = $base_args;
    $occupied_args['meta_query'][] = [
        'key' => 'application_status',
        'value' => ['payment_pending', 'enrolled'],
        'compare' => 'IN',
    ];
    $occupied = count(get_posts($occupied_args));
    $remaining = max(0, $total - $occupied);

    return [
        'total' => $total,
        'applied' => $applied,
        'occupied' => $occupied,
        'remaining' => $remaining,
    ];
}

add_filter('manage_jrc_application_posts_columns', function ($columns) {
    return [
        'cb' => $columns['cb'],
        'student_name' => 'Full Name',
        'student_email' => 'Email',
        'student_course' => 'Course',
        'student_payment_plan' => 'Payment Plan',
        'student_skill' => 'Skill Level',
        'application_status' => 'Status',
        'date' => $columns['date'],
    ];
});

add_action('manage_jrc_application_posts_custom_column', function ($column, $post_id) {
    if ($column === 'student_name') {
        echo esc_html(get_post_meta($post_id, 'student_name', true));
        return;
    }
    if ($column === 'student_email') {
        echo esc_html(get_post_meta($post_id, 'student_email', true));
        return;
    }
    if ($column === 'student_course') {
        $course = get_post_meta($post_id, 'student_course', true);
        $labels = jrc_get_application_course_options();
        echo esc_html($labels[$course] ?? '-');
        return;
    }
    if ($column === 'student_payment_plan') {
        $payment_plan = get_post_meta($post_id, 'student_payment_plan', true);
        $labels = jrc_get_application_payment_plan_labels();
        echo esc_html($labels[$payment_plan] ?? 'Not specified');
        return;
    }
    if ($column === 'student_skill') {
        echo esc_html(get_post_meta($post_id, 'student_experience', true));
        return;
    }
    if ($column === 'application_status') {
        $status = get_post_meta($post_id, 'application_status', true);
        $statuses = jrc_get_application_statuses();
        echo esc_html($statuses[$status] ?? 'Applied');
        return;
    }
}, 10, 2);

add_filter('manage_edit-jrc_application_sortable_columns', function ($columns) {
    $columns['student_payment_plan'] = 'student_payment_plan';
    $columns['student_skill'] = 'student_skill';
    $columns['application_status'] = 'application_status';
    return $columns;
});

add_action('pre_get_posts', function (WP_Query $query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    $post_type = $query->get('post_type');
    if ($post_type !== 'jrc_application') {
        return;
    }

    $meta_query = [];
    $status = sanitize_text_field($_GET['jrc_status'] ?? '');
    if ($status !== '') {
        $meta_query[] = [
            'key' => 'application_status',
            'value' => $status,
        ];
    }
    $skill = sanitize_text_field($_GET['jrc_skill'] ?? '');
    if ($skill !== '') {
        $meta_query[] = [
            'key' => 'student_experience',
            'value' => $skill,
        ];
    }
    $course = sanitize_text_field($_GET['jrc_course'] ?? '');
    if ($course !== '') {
        $meta_query[] = [
            'key' => 'student_course',
            'value' => $course,
        ];
    }
    $payment_plan = sanitize_text_field($_GET['jrc_payment_plan'] ?? '');
    if ($payment_plan !== '') {
        $meta_query[] = [
            'key' => 'student_payment_plan',
            'value' => $payment_plan,
        ];
    }
    if (!empty($meta_query)) {
        $query->set('meta_query', $meta_query);
    }

    $orderby = $query->get('orderby');
    if ($orderby === 'student_payment_plan') {
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = [];
        }
        $meta_query[] = [
            'relation' => 'OR',
            [
                'key' => 'student_payment_plan',
                'compare' => 'EXISTS',
            ],
            [
                'key' => 'student_payment_plan',
                'compare' => 'NOT EXISTS',
            ],
        ];
        $query->set('meta_query', $meta_query);
        $query->set('meta_key', 'student_payment_plan');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'student_skill') {
        $query->set('meta_key', 'student_experience');
        $query->set('orderby', 'meta_value');
    }
    if ($orderby === 'application_status') {
        $query->set('meta_key', 'application_status');
        $query->set('orderby', 'meta_value');
    }
});

add_action('restrict_manage_posts', function () {
    global $typenow;
    if ($typenow !== 'jrc_application') {
        return;
    }
    $statuses = jrc_get_application_statuses();
    $skills = ['Beginner', 'Intermediate', 'Advanced'];
    $courses = jrc_get_application_course_options();
    $payment_plans = jrc_get_application_payment_plan_labels();
    $current_status = sanitize_text_field($_GET['jrc_status'] ?? '');
    $current_skill = sanitize_text_field($_GET['jrc_skill'] ?? '');
    $current_course = sanitize_text_field($_GET['jrc_course'] ?? '');
    $current_payment_plan = sanitize_text_field($_GET['jrc_payment_plan'] ?? '');

    echo '<select name="jrc_status">';
    echo '<option value="">All Statuses</option>';
    foreach ($statuses as $key => $label) {
        printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($current_status, $key, false), esc_html($label));
    }
    echo '</select>';

    echo '<select name="jrc_skill">';
    echo '<option value="">All Skill Levels</option>';
    foreach ($skills as $skill) {
        printf('<option value="%s"%s>%s</option>', esc_attr($skill), selected($current_skill, $skill, false), esc_html($skill));
    }
    echo '</select>';

    echo '<select name="jrc_course">';
    echo '<option value="">All Courses</option>';
    foreach ($courses as $key => $label) {
        printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($current_course, $key, false), esc_html($label));
    }
    echo '</select>';

    echo '<select name="jrc_payment_plan">';
    echo '<option value="">All Payment Plans</option>';
    foreach ($payment_plans as $key => $label) {
        printf('<option value="%s"%s>%s</option>', esc_attr($key), selected($current_payment_plan, $key, false), esc_html($label));
    }
    echo '</select>';
});

add_action('admin_notices', function () {
    global $typenow;
    if ($typenow !== 'jrc_application') {
        return;
    }

    $parts = [];
    foreach (jrc_get_application_course_options() as $course_key => $course_label) {
        $stats = jrc_get_course_seat_stats($course_key);
        $parts[] = $course_label . ': ' . $stats['remaining'] . '/' . $stats['total'] . ' seats remaining';
    }

    if (empty($parts)) {
        return;
    }

    echo '<div class="notice notice-info"><p>';
    echo esc_html(implode(' | ', $parts)) . '.';
    echo '</p></div>';
});

add_filter('post_row_actions', function ($actions, WP_Post $post) {
    if ($post->post_type !== 'jrc_application') {
        return $actions;
    }

    $status = get_post_meta($post->ID, 'application_status', true) ?: 'applied';
    $actions['jrc_mark_applied'] = jrc_application_status_action_link($post->ID, 'applied', $status);
    $actions['jrc_mark_test'] = jrc_application_status_action_link($post->ID, 'test_taken', $status);
    $actions['jrc_mark_payment'] = jrc_application_status_action_link($post->ID, 'payment_pending', $status);
    $actions['jrc_mark_enrolled'] = jrc_application_status_action_link($post->ID, 'enrolled', $status);

    $phone = preg_replace('/\D+/', '', get_post_meta($post->ID, 'student_phone', true));
    $course_key = get_post_meta($post->ID, 'student_course', true);
    $course_labels = jrc_get_application_course_options();
    $course_label = $course_labels[$course_key] ?? 'Job Ready Course';
    if ($phone !== '') {
        $message = rawurlencode('Thanks for applying to ' . $course_label . '. We will share next steps soon.');
        $actions['jrc_whatsapp'] = '<a href="https://wa.me/' . esc_attr($phone) . '?text=' . $message . '" target="_blank" rel="noopener">WhatsApp</a>';
    }
    $email = get_post_meta($post->ID, 'student_email', true);
    if ($email) {
        $subject = rawurlencode($course_label . ' - Next Steps');
        $body = rawurlencode('Thanks for applying to ' . $course_label . '. Please complete the basic test so we can review your current level.');
        $actions['jrc_email'] = '<a href="mailto:' . esc_attr($email) . '?subject=' . $subject . '&body=' . $body . '">Email</a>';
    }

    return $actions;
}, 10, 2);

function jrc_application_status_action_link($post_id, $target_status, $current_status)
{
    $labels = jrc_get_application_statuses();
    $label = $labels[$target_status] ?? ucfirst(str_replace('_', ' ', $target_status));
    if ($target_status === $current_status) {
        return '<span style="color:#8a98b2;">' . esc_html($label) . '</span>';
    }
    $url = wp_nonce_url(
        admin_url('admin-post.php?action=jrc_update_application_status&post_id=' . $post_id . '&status=' . $target_status),
        'jrc_update_application_status_' . $post_id
    );
    return '<a href="' . esc_url($url) . '">' . esc_html('Mark ' . $label) . '</a>';
}

add_action('admin_post_jrc_update_application_status', 'jrc_handle_application_status_update');
function jrc_handle_application_status_update()
{
    $post_id = isset($_GET['post_id']) ? (int) $_GET['post_id'] : 0;
    $status = sanitize_text_field($_GET['status'] ?? '');
    if (!$post_id || !jrc_current_user_can_manage_course()) {
        wp_die('Unauthorized', 403);
    }
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'jrc_update_application_status_' . $post_id)) {
        wp_die('Invalid nonce', 403);
    }
    $statuses = jrc_get_application_statuses();
    if (!isset($statuses[$status])) {
        wp_die('Invalid status', 400);
    }
    update_post_meta($post_id, 'application_status', $status);
    update_post_meta($post_id, 'application_status_updated_at', current_time('mysql'));
    update_post_meta($post_id, 'application_status_updated_by', get_current_user_id());
    jrc_notify_application_event($post_id, $status);

    $redirect = wp_get_referer() ?: admin_url('edit.php?post_type=jrc_application');
    wp_safe_redirect($redirect);
    exit;
}

add_action('admin_post_jrc_export_applications', 'jrc_handle_application_export');
function jrc_handle_application_export()
{
    if (!jrc_current_user_can_manage_course()) {
        wp_die('Unauthorized', 403);
    }
    if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'jrc_export_applications')) {
        wp_die('Invalid nonce', 403);
    }

    $args = [
        'post_type' => 'jrc_application',
        'post_status' => 'private',
        'posts_per_page' => -1,
    ];
    $meta_query = [];
    $status = sanitize_text_field($_GET['jrc_status'] ?? '');
    if ($status !== '') {
        $meta_query[] = ['key' => 'application_status', 'value' => $status];
    }
    $skill = sanitize_text_field($_GET['jrc_skill'] ?? '');
    if ($skill !== '') {
        $meta_query[] = ['key' => 'student_experience', 'value' => $skill];
    }
    $course = sanitize_text_field($_GET['jrc_course'] ?? '');
    if ($course !== '') {
        $meta_query[] = ['key' => 'student_course', 'value' => $course];
    }
    $payment_plan = sanitize_text_field($_GET['jrc_payment_plan'] ?? '');
    if ($payment_plan !== '') {
        $meta_query[] = ['key' => 'student_payment_plan', 'value' => $payment_plan];
    }
    if (!empty($meta_query)) {
        $args['meta_query'] = $meta_query;
    }
    $items = get_posts($args);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=job-ready-applications.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Full Name',
        'Email',
        'Phone',
        'City',
        'University',
        'Department',
        'Semester/Status',
        'Course',
        'Currently Learning',
        'Experience Level',
        'Why Join',
        'Goal (3 months)',
        'Available Time',
        'Referral',
        'Status',
        'Applied Date',
    ]);
    foreach ($items as $item) {
        $course_key = get_post_meta($item->ID, 'student_course', true);
        $course_labels = jrc_get_application_course_options();
        $course_label = $course_labels[$course_key] ?? '';
        $learning = (array) get_post_meta($item->ID, 'student_learning', true);
        $referral = get_post_meta($item->ID, 'student_referral', true);
        $referral_other = get_post_meta($item->ID, 'student_referral_other', true);
        if ($referral === 'Other' && $referral_other) {
            $referral = 'Other: ' . $referral_other;
        }
        fputcsv($output, [
            get_post_meta($item->ID, 'student_name', true),
            get_post_meta($item->ID, 'student_email', true),
            get_post_meta($item->ID, 'student_phone', true),
            get_post_meta($item->ID, 'student_city', true),
            get_post_meta($item->ID, 'student_university', true),
            get_post_meta($item->ID, 'student_department', true),
            get_post_meta($item->ID, 'student_semester', true),
            $course_label,
            implode(', ', array_filter($learning)),
            get_post_meta($item->ID, 'student_experience', true),
            get_post_meta($item->ID, 'student_reason', true),
            get_post_meta($item->ID, 'student_goal', true),
            get_post_meta($item->ID, 'student_time', true),
            $referral,
            get_post_meta($item->ID, 'application_status', true),
            get_post_time('Y-m-d H:i', false, $item->ID),
        ]);
    }
    fclose($output);
    exit;
}

add_action('manage_posts_extra_tablenav', function ($which) {
    global $typenow;
    if ($typenow !== 'jrc_application' || $which !== 'top') {
        return;
    }
    $url = wp_nonce_url(
        add_query_arg([
            'action' => 'jrc_export_applications',
            'jrc_status' => sanitize_text_field($_GET['jrc_status'] ?? ''),
            'jrc_skill' => sanitize_text_field($_GET['jrc_skill'] ?? ''),
            'jrc_course' => sanitize_text_field($_GET['jrc_course'] ?? ''),
            'jrc_payment_plan' => sanitize_text_field($_GET['jrc_payment_plan'] ?? ''),
        ], admin_url('admin-post.php')),
        'jrc_export_applications'
    );
    echo '<div class="alignleft actions"><a class="button" href="' . esc_url($url) . '">Export CSV</a></div>';
});

add_action('add_meta_boxes', function () {
    add_meta_box(
        'jrc_application_details',
        'Application Details',
        'jrc_render_application_details_meta_box',
        'jrc_application',
        'side',
        'default'
    );
});

function jrc_render_application_details_meta_box(WP_Post $post)
{
    $fields = [
        'Phone' => get_post_meta($post->ID, 'student_phone', true),
        'Course' => (jrc_get_application_course_options()[get_post_meta($post->ID, 'student_course', true)] ?? ''),
        'City' => get_post_meta($post->ID, 'student_city', true),
        'University' => get_post_meta($post->ID, 'student_university', true),
        'Semester' => get_post_meta($post->ID, 'student_semester', true),
        'Status' => get_post_meta($post->ID, 'application_status', true),
    ];
    echo '<ul style="margin:0;padding-left:16px;">';
    foreach ($fields as $label => $value) {
        if ($value === '') {
            continue;
        }
        echo '<li><strong>' . esc_html($label) . ':</strong> ' . esc_html($value) . '</li>';
    }
    echo '</ul>';

    $payment_txn = get_post_meta($post->ID, 'payment_txn_id', true);
    $payment_proof_id = (int) get_post_meta($post->ID, 'payment_proof_id', true);
    if ($payment_txn || $payment_proof_id) {
        echo '<p style="margin-top:12px;"><strong>Payment Proof</strong></p>';
        if ($payment_txn) {
            echo '<p style="margin:0;">Txn ID: ' . esc_html($payment_txn) . '</p>';
        }
        if ($payment_proof_id) {
            $url = wp_get_attachment_url($payment_proof_id);
            if ($url) {
                echo '<p style="margin:0;"><a href="' . esc_url($url) . '" target="_blank" rel="noopener">View upload</a></p>';
            }
        }
    }
}

add_action('admin_post_jrc_application_submit', 'jrc_handle_application_submit');
add_action('admin_post_nopriv_jrc_application_submit', 'jrc_handle_application_submit');

function jrc_handle_application_submit()
{
    $course = jrc_get_requested_course_key($_POST['student_course'] ?? '');
    $redirect = jrc_get_application_redirect_url($course);

    if (!is_user_logged_in()) {
        wp_safe_redirect(jrc_get_application_redirect_url($course, ['jrc_auth_error' => 'auth_required']));
        exit;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_application_submit')) {
        $redirect = add_query_arg('app_error', 'invalid_request', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    if (khokan_submission_tripped_honeypot('jrc_website')) {
        $redirect = add_query_arg('app_error', 'invalid_request', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $rate_limit = khokan_rate_limit_request('jrc_application_submit', 5, HOUR_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        $redirect = add_query_arg('app_error', 'rate_limit', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $current_user = wp_get_current_user();
    if (!$current_user instanceof WP_User || !$current_user->exists()) {
        wp_safe_redirect(jrc_get_application_redirect_url($course, ['jrc_auth_error' => 'auth_required']));
        exit;
    }

    if (jrc_user_requires_email_verification($current_user)) {
        wp_safe_redirect(jrc_get_auth_page_url('verify', $course, ['jrc_auth_error' => 'verify_required']));
        exit;
    }

    $user_id = (int) $current_user->ID;
    $name = isset($_POST['student_name']) ? sanitize_text_field(wp_unslash($_POST['student_name'])) : '';
    $email = sanitize_email($current_user->user_email);
    $phone = khokan_normalize_phone($_POST['student_phone'] ?? '');
    $city = isset($_POST['student_city']) ? sanitize_text_field(wp_unslash($_POST['student_city'])) : '';
    $university = isset($_POST['student_university']) ? sanitize_text_field(wp_unslash($_POST['student_university'])) : '';
    $department = isset($_POST['student_department']) ? sanitize_text_field(wp_unslash($_POST['student_department'])) : '';
    $semester = isset($_POST['student_semester']) ? sanitize_text_field(wp_unslash($_POST['student_semester'])) : '';
    $raw_learning = (isset($_POST['student_learning']) && is_array($_POST['student_learning'])) ? wp_unslash($_POST['student_learning']) : [];
    $learning = array_map('sanitize_text_field', $raw_learning);
    $experience = isset($_POST['student_experience']) ? sanitize_text_field(wp_unslash($_POST['student_experience'])) : '';
    $reason = isset($_POST['student_reason']) ? sanitize_textarea_field(wp_unslash($_POST['student_reason'])) : '';
    $goal = isset($_POST['student_goal']) ? sanitize_textarea_field(wp_unslash($_POST['student_goal'])) : '';
    $time = isset($_POST['student_time']) ? sanitize_text_field(wp_unslash($_POST['student_time'])) : '';
    $payment_plan = isset($_POST['student_payment_plan']) ? sanitize_text_field(wp_unslash($_POST['student_payment_plan'])) : '';
    $referral = isset($_POST['student_referral']) ? sanitize_text_field(wp_unslash($_POST['student_referral'])) : '';
    $referral_other = isset($_POST['student_referral_other']) ? sanitize_text_field(wp_unslash($_POST['student_referral_other'])) : '';
    if ($referral !== 'Other') {
        $referral_other = '';
    }
    $consent = !empty($_POST['student_consent']);

    $errors = [];
    $course_options = jrc_get_application_course_options();
    if ($name === '' || $email === '' || $phone === '' || $city === '' || $university === '' || $semester === '' || $course === '' || !isset($course_options[$course]) || $experience === '' || $reason === '' || $goal === '' || $time === '' || !$consent) {
        $errors[] = 'Please fill all required fields.';
    }
    if (!khokan_phone_has_min_digits($phone)) {
        $errors[] = 'Please provide a valid phone number.';
    }
    if ($email === '' || !is_email($email)) {
        $errors[] = 'Your account must have a valid email address.';
    }
    if ($reason !== '') {
        $word_count = str_word_count(wp_strip_all_tags($reason));
        if ($word_count > 100) {
            $errors[] = 'Why Join must be within 100 words.';
        }
    }

    if (!empty($errors)) {
        $redirect = add_query_arg('app_error', 'validation', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $course_labels = jrc_get_application_course_options();
    $course_label = $course_labels[$course] ?? '';
    $title = trim($name . ' | ' . $phone . ' | ' . $email . ($course_label ? ' | ' . $course_label : ''));
    $post_id = jrc_get_user_application_id($user_id);
    if ($post_id > 0) {
        wp_update_post([
            'ID' => $post_id,
            'post_title' => $title,
        ]);
    } else {
        $post_id = wp_insert_post([
            'post_type' => 'jrc_application',
            'post_status' => 'private',
            'post_title' => $title,
        ], true);
    }

    if (is_wp_error($post_id)) {
        $redirect = add_query_arg('app_error', 'server', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    update_post_meta($post_id, 'student_name', $name);
    update_post_meta($post_id, 'student_email', $email);
    update_post_meta($post_id, 'student_phone', $phone);
    update_post_meta($post_id, 'student_city', $city);
    update_post_meta($post_id, 'student_university', $university);
    update_post_meta($post_id, 'student_department', $department);
    update_post_meta($post_id, 'student_semester', $semester);
    update_post_meta($post_id, 'student_learning', array_values(array_filter($learning)));
    update_post_meta($post_id, 'student_course', $course);
    update_post_meta($post_id, 'student_experience', $experience);
    update_post_meta($post_id, 'student_reason', $reason);
    update_post_meta($post_id, 'student_goal', $goal);
    update_post_meta($post_id, 'student_time', $time);
    update_post_meta($post_id, 'student_payment_plan', $payment_plan);
    update_post_meta($post_id, 'student_referral', $referral);
    update_post_meta($post_id, 'student_referral_other', $referral_other);
    update_post_meta($post_id, 'student_consent', $consent ? 'yes' : 'no');

    if (!get_post_meta($post_id, 'application_status', true)) {
        update_post_meta($post_id, 'application_status', 'applied');
    }
    update_post_meta($post_id, 'application_updated_at', current_time('mysql'));

    if ($name !== '' && !jrc_user_can_manage_course($current_user)) {
        $user_updates = ['ID' => $user_id];
        $current_first_name = (string) get_user_meta($user_id, 'first_name', true);

        if ((string) $current_user->display_name !== $name) {
            $user_updates['display_name'] = $name;
            $user_updates['nickname'] = $name;
        }

        if ($current_first_name !== $name) {
            $user_updates['first_name'] = $name;
        }

        if (count($user_updates) > 1) {
            wp_update_user($user_updates);
        }
    }

    update_post_meta($post_id, 'application_user_id', $user_id);
    update_user_meta($user_id, 'jrc_application_id', $post_id);

    jrc_notify_application_event($post_id, 'applied');

    $redirect = add_query_arg('seat', '1', $redirect);
    wp_safe_redirect($redirect);
    exit;
}

function jrc_notify_application_event($post_id, $event)
{
    $email = get_post_meta($post_id, 'student_email', true);
    $name = get_post_meta($post_id, 'student_name', true);
    $admin_email = get_option('admin_email');
    $course_key = get_post_meta($post_id, 'student_course', true);
    $course_labels = jrc_get_application_course_options();
    $course_label = $course_labels[$course_key] ?? 'Selected course';

    $course = function_exists('jrc_get_course_data') ? jrc_get_course_data() : [];
    $quiz_link = $course['quiz_cta']['link'] ?? '';
    $whatsapp_group = $course['whatsapp']['group_link'] ?? '';
    $discounted_fee = $course['fee']['early'] ?? '';

    $student_subjects = [
        'applied' => 'We received your application',
        'test_taken' => 'Test completed - next step',
        'payment_pending' => 'Payment received - pending verification',
        'enrolled' => 'You are enrolled',
    ];
    $student_bodies = [
        'applied' => "Hi {$name},\n\nThanks for applying to {$course_label}. Please take the basic test so we can review your current level.\n\n" . ($discounted_fee ? "Current discounted price: {$discounted_fee}\n\n" : '') . ($quiz_link ? "Basic Test: {$quiz_link}\n\n" : '') . ($whatsapp_group ? "Join WhatsApp group: {$whatsapp_group}\n\n" : '') . "We will contact you with next steps.",
        'test_taken' => "Hi {$name},\n\nCongratulations on completing the test for {$course_label}. Please complete the payment to secure your spot. We will verify and confirm your enrollment.",
        'payment_pending' => "Hi {$name},\n\nWe received your payment information. Our team will verify and confirm shortly.",
        'enrolled' => "Hi {$name},\n\nYou are officially enrolled in {$course_label}. Welcome to the program!",
    ];
    $admin_subjects = [
        'applied' => 'New application received',
        'test_taken' => 'Student completed test',
        'payment_pending' => 'Payment proof submitted',
        'enrolled' => 'Student enrolled',
    ];
    $admin_bodies = [
        'applied' => "{$name} has submitted the application for {$course_label}.",
        'test_taken' => "{$name} moved to Test Taken for {$course_label}.",
        'payment_pending' => "{$name} submitted payment proof for {$course_label}.",
        'enrolled' => "{$name} is enrolled in {$course_label}.",
    ];

    $student_subject = $student_subjects[$event] ?? 'Job Ready Course Update';
    $student_body = $student_bodies[$event] ?? '';
    $admin_subject = $admin_subjects[$event] ?? 'Job Ready Course Update';
    $admin_body = $admin_bodies[$event] ?? '';

    if ($email && $student_body) {
        wp_mail($email, $student_subject, $student_body);
    }
    if ($admin_email && $admin_body) {
        wp_mail($admin_email, $admin_subject, $admin_body);
    }

    jrc_trigger_application_webhook($event, $post_id);
}

function jrc_trigger_application_webhook($event, $post_id)
{
    $url = apply_filters('jrc_application_webhook_url', '');
    if (!$url) {
        return;
    }

    $payload = [
        'event' => $event,
        'application_id' => $post_id,
        'name' => get_post_meta($post_id, 'student_name', true),
        'email' => get_post_meta($post_id, 'student_email', true),
        'phone' => get_post_meta($post_id, 'student_phone', true),
        'course' => get_post_meta($post_id, 'student_course', true),
        'status' => get_post_meta($post_id, 'application_status', true),
        'created_at' => get_post_time('c', false, $post_id),
    ];

    wp_remote_post($url, [
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'body' => wp_json_encode($payload),
        'timeout' => 10,
    ]);
}

function jrc_get_requested_course_key($value = null)
{
    if ($value === null) {
        $value = $_REQUEST['jrc_course'] ?? '';
    }

    $course_key = sanitize_key(wp_unslash((string) $value));
    $courses = jrc_get_application_course_options();

    return isset($courses[$course_key]) ? $course_key : '';
}

function jrc_get_application_redirect_url($course_key = '', array $args = [])
{
    if (in_array(jrc_get_current_frontend_route(), ['login', 'signup', 'dashboard', 'verify'], true)) {
        return jrc_get_course_application_page_url($course_key, $args);
    }

    $base_url = '';
    if (!is_admin() && !empty($_SERVER['REQUEST_URI'])) {
        $base_url = home_url(wp_unslash($_SERVER['REQUEST_URI']));
    }

    if ($base_url === '') {
        $base_url = wp_get_referer() ?: home_url('/');
    }

    $base_url = esc_url_raw(strtok((string) $base_url, '#'));
    if ($base_url === '') {
        $base_url = home_url('/');
    }

    $base_url = remove_query_arg(['jrc_auth', 'jrc_auth_error', 'app_error', 'seat', 'payment_error', 'payment_success'], $base_url);

    if ($course_key !== '') {
        $args['jrc_course'] = $course_key;
    }

    if (!empty($args)) {
        $base_url = add_query_arg($args, $base_url);
    }

    return $base_url . '#course-application';
}

function jrc_get_auth_feedback_message()
{
    $success = sanitize_key($_GET['jrc_auth'] ?? '');
    $error = sanitize_key($_GET['jrc_auth_error'] ?? '');

    if ($error !== '') {
        $messages = [
            'auth_required' => 'Apply করার আগে login বা sign up করতে হবে.',
            'invalid_request' => 'Invalid request detected. Please try again.',
            'rate_limit' => 'Too many attempts detected. Please wait a little and try again.',
            'register_invalid' => 'Please complete all sign up fields correctly.',
            'account_exists' => 'This email is already registered. Please log in instead.',
            'register_failed' => 'Could not create your account right now. Please try again.',
            'login_failed' => 'Login failed. Please check your email and password.',
            'verify_required' => 'Verify your email address first, then continue with the application.',
            'verify_invalid' => 'The verification link is invalid.',
            'verify_expired' => 'The verification link expired. Request a new one.',
            'verify_send_failed' => 'Could not send the verification email right now. Please try again.',
        ];

        return [
            'type' => 'error',
            'message' => $messages[$error] ?? 'Could not continue right now. Please try again.',
        ];
    }

    if ($success !== '') {
        $messages = [
            'registered' => 'Account created successfully.',
            'logged_in' => 'Login successful. You can now complete the application form.',
            'verify_sent' => 'Verification email sent. Please check your inbox.',
            'verified' => 'Email verified successfully. You can now continue.',
        ];

        if (isset($messages[$success])) {
            return [
                'type' => 'success',
                'message' => $messages[$success],
            ];
        }
    }

    return null;
}

function jrc_get_user_application_id($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return 0;
    }

    $application_id = (int) get_user_meta($user_id, 'jrc_application_id', true);
    if ($application_id > 0 && get_post_type($application_id) === 'jrc_application') {
        return $application_id;
    }

    $user = get_userdata($user_id);
    if (!$user instanceof WP_User) {
        return 0;
    }

    $matches = get_posts([
        'post_type' => 'jrc_application',
        'post_status' => 'private',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => [
            'relation' => 'OR',
            [
                'key' => 'application_user_id',
                'value' => $user_id,
            ],
            [
                'key' => 'student_email',
                'value' => $user->user_email,
            ],
        ],
    ]);

    if (!empty($matches)) {
        $application_id = (int) $matches[0];
        update_user_meta($user_id, 'jrc_application_id', $application_id);
        return $application_id;
    }

    return 0;
}

function jrc_get_application_prefill_data($course_key = '')
{
    $values = [];
    $resolved_course_key = jrc_get_requested_course_key($course_key);
    $application_id = 0;

    if (!is_user_logged_in()) {
        return [
            'application_id' => 0,
            'values' => $values,
            'course_key' => $resolved_course_key,
        ];
    }

    $user = wp_get_current_user();
    if ($user instanceof WP_User) {
        $values['student_name'] = get_user_meta($user->ID, 'first_name', true) ?: ($user->display_name ?: $user->user_login);
        $values['student_email'] = $user->user_email;
    }

    $application_id = jrc_get_user_application_id(get_current_user_id());
    if ($application_id > 0) {
        $meta_keys = [
            'student_name',
            'student_phone',
            'student_email',
            'student_city',
            'student_university',
            'student_department',
            'student_semester',
            'student_course',
            'student_experience',
            'student_reason',
            'student_goal',
            'student_time',
            'student_referral',
            'student_referral_other',
            'student_consent',
        ];

        foreach ($meta_keys as $meta_key) {
            $values[$meta_key] = get_post_meta($application_id, $meta_key, true);
        }

        $values['student_learning'] = (array) get_post_meta($application_id, 'student_learning', true);
        if ($resolved_course_key === '') {
            $resolved_course_key = jrc_get_requested_course_key($values['student_course'] ?? '');
        }
    }

    if ($user instanceof WP_User) {
        $values['student_email'] = $user->user_email;
    }

    return [
        'application_id' => $application_id,
        'values' => $values,
        'course_key' => $resolved_course_key,
    ];
}

function jrc_render_application_auth_gate($course_key = '', array $course_notes = [], $preferred_mode = '')
{
    $course_options = jrc_get_application_course_options();
    $selected_course = jrc_get_requested_course_key($course_key);
    $selected_label = $selected_course && isset($course_options[$selected_course])
        ? $course_options[$selected_course]
        : 'Select a course above to continue';
    $preferred_mode = in_array($preferred_mode, ['signup', 'login'], true) ? $preferred_mode : '';
    $feedback = jrc_get_auth_feedback_message();
    $login_nonce = wp_create_nonce('jrc_auth_login');
    $register_nonce = wp_create_nonce('jrc_auth_register');
    $signup_url = jrc_get_auth_page_url('signup', $selected_course);
    $login_url = jrc_get_auth_page_url('login', $selected_course);
    $shell_classes = 'jrc-auth-shell' . ($preferred_mode ? ' jrc-auth-shell--' . $preferred_mode : '');
    $signup_panel_classes = 'jrc-auth-panel jrc-auth-panel--signup' . ($preferred_mode === 'signup' ? ' is-preferred' : '');
    $login_panel_classes = 'jrc-auth-panel jrc-auth-panel--login' . ($preferred_mode === 'login' ? ' is-preferred' : '');

    ob_start();
    ?>
    <?php if ($feedback) : ?>
        <p class="section-subtitle jrc-auth-message <?php echo $feedback['type'] === 'success' ? 'jrc-success-message' : 'jrc-error-message'; ?>">
            <?php echo esc_html($feedback['message']); ?>
        </p>
    <?php endif; ?>
    <div class="<?php echo esc_attr($shell_classes); ?>">
        <div class="jrc-auth-intro">
            <span class="jrc-auth-intro__eyebrow">Account Required</span>
            <h3>Sign up or log in to apply</h3>
            <p class="section-subtitle">Application dashboard, payment proof, and course status সবকিছু আপনার account-এর সাথে linked থাকবে.</p>
            <div class="jrc-auth-quick-actions">
                <a class="primary-btn" href="<?php echo esc_url($signup_url); ?>">Open Sign Up Page</a>
                <a class="secondary-btn" href="<?php echo esc_url($login_url); ?>">Open Login Page</a>
            </div>
        </div>
        <div class="jrc-auth-selected">
            <span class="jrc-auth-selected__label">Selected Course</span>
            <strong class="jrc-auth-selected__value" data-jrc-auth-course-label><?php echo esc_html($selected_label); ?></strong>
            <?php if (!empty($course_notes)) : ?>
                <div class="jrc-course-notes jrc-auth-course-notes">
                    <?php foreach ($course_notes as $key => $note) : ?>
                        <p class="jrc-course-note<?php echo $selected_course === $key ? ' is-active' : ''; ?>" data-course="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($note); ?>
                        </p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="jrc-auth-grid">
            <div class="<?php echo esc_attr($signup_panel_classes); ?>">
                <div class="jrc-auth-panel__header">
                    <span class="jrc-auth-panel__eyebrow">New Student</span>
                    <h4 class="jrc-auth-panel__title">Sign Up</h4>
                    <p class="section-subtitle">Create your account first, verify your email, then continue with the application.</p>
                </div>
                <form class="jrc-auth-form jrc-auth-form--signup" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="jrc_auth_register">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($register_nonce); ?>">
                    <input type="hidden" class="jrc-auth-course-input" name="jrc_course" value="<?php echo esc_attr($selected_course); ?>">
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <label for="jrc-register-website">Website</label>
                        <input type="text" id="jrc-register-website" name="jrc_register_website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="jrc-form-group">
                        <div class="jrc-form-group__fields">
                            <div class="jrc-form-field">
                                <label for="jrc-register-name">Full Name *</label>
                                <input type="text" id="jrc-register-name" name="jrc_register_name" required>
                            </div>
                            <div class="jrc-form-field">
                                <label for="jrc-register-email">Email *</label>
                                <input type="email" id="jrc-register-email" name="jrc_register_email" required>
                            </div>
                            <div class="jrc-form-field">
                                <label for="jrc-register-password">Password *</label>
                                <input type="password" id="jrc-register-password" name="jrc_register_password" minlength="8" required>
                            </div>
                            <div class="jrc-form-field">
                                <label for="jrc-register-confirm-password">Confirm Password *</label>
                                <input type="password" id="jrc-register-confirm-password" name="jrc_register_password_confirm" minlength="8" required>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="primary-btn">Sign Up And Continue</button>
                </form>
            </div>
            <div class="<?php echo esc_attr($login_panel_classes); ?>">
                <div class="jrc-auth-panel__header">
                    <span class="jrc-auth-panel__eyebrow">Returning Student</span>
                    <h4 class="jrc-auth-panel__title">Login</h4>
                    <p class="section-subtitle">Already created an account? Log in and continue from where you left off.</p>
                </div>
                <form class="jrc-auth-form jrc-auth-form--login" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="jrc_auth_login">
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($login_nonce); ?>">
                    <input type="hidden" class="jrc-auth-course-input" name="jrc_course" value="<?php echo esc_attr($selected_course); ?>">
                    <div style="position:absolute;left:-9999px;" aria-hidden="true">
                        <label for="jrc-login-website">Website</label>
                        <input type="text" id="jrc-login-website" name="jrc_login_website" tabindex="-1" autocomplete="off">
                    </div>
                    <div class="jrc-form-group">
                        <div class="jrc-form-group__fields">
                            <div class="jrc-form-field">
                                <label for="jrc-login-email">Email Or Username *</label>
                                <input type="text" id="jrc-login-email" name="jrc_login_identifier" required>
                            </div>
                            <div class="jrc-form-field">
                                <label for="jrc-login-password">Password *</label>
                                <input type="password" id="jrc-login-password" name="jrc_login_password" required>
                            </div>
                            <label class="jrc-form-checkbox" for="jrc-login-remember">
                                <input type="checkbox" id="jrc-login-remember" name="jrc_login_remember" value="1">
                                Remember me
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="secondary-btn">Log In And Continue</button>
                    <a class="jrc-auth-helper" href="<?php echo esc_url(wp_lostpassword_url(jrc_get_application_redirect_url($selected_course))); ?>">Forgot password?</a>
                </form>
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

add_action('admin_post_jrc_auth_register', 'jrc_handle_auth_register');
add_action('admin_post_nopriv_jrc_auth_register', 'jrc_handle_auth_register');
function jrc_handle_auth_register()
{
    $course_key = jrc_get_requested_course_key($_POST['jrc_course'] ?? '');

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user)) {
            wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_required']));
            exit;
        }

        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'logged_in']));
        exit;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_auth_register')) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'invalid_request']));
        exit;
    }

    if (khokan_submission_tripped_honeypot('jrc_register_website')) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'invalid_request']));
        exit;
    }

    $rate_limit = khokan_rate_limit_request('jrc_auth_register', 4, HOUR_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'rate_limit']));
        exit;
    }

    $name = isset($_POST['jrc_register_name']) ? sanitize_text_field(wp_unslash($_POST['jrc_register_name'])) : '';
    $email = isset($_POST['jrc_register_email']) ? sanitize_email(wp_unslash($_POST['jrc_register_email'])) : '';
    $password = isset($_POST['jrc_register_password']) ? (string) wp_unslash($_POST['jrc_register_password']) : '';
    $confirm_password = isset($_POST['jrc_register_password_confirm']) ? (string) wp_unslash($_POST['jrc_register_password_confirm']) : '';

    if ($name === '' || $email === '' || !is_email($email) || strlen($password) < 8 || $password !== $confirm_password) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'register_invalid']));
        exit;
    }

    if (email_exists($email)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'account_exists']));
        exit;
    }

    $user_id = wp_create_user($email, $password, $email);
    if (is_wp_error($user_id)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'register_failed']));
        exit;
    }

    wp_update_user([
        'ID' => $user_id,
        'display_name' => $name,
        'first_name' => $name,
        'nickname' => $name,
    ]);

    $user = get_user_by('id', $user_id);
    if ($user instanceof WP_User && !jrc_user_can_manage_course($user)) {
        $user->set_role('jrc_student');
    }

    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id, true, is_ssl());
    do_action('wp_login', $user ? $user->user_login : $email, $user);

    $verification = jrc_send_email_verification($user_id, $course_key);
    if (is_wp_error($verification)) {
        wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_send_failed']));
        exit;
    }

    wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth' => 'verify_sent']));
    exit;
}

add_action('admin_post_jrc_auth_login', 'jrc_handle_auth_login');
add_action('admin_post_nopriv_jrc_auth_login', 'jrc_handle_auth_login');
function jrc_handle_auth_login()
{
    $course_key = jrc_get_requested_course_key($_POST['jrc_course'] ?? '');

    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user)) {
            wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_required']));
            exit;
        }

        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'logged_in']));
        exit;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_auth_login')) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'invalid_request']));
        exit;
    }

    if (khokan_submission_tripped_honeypot('jrc_login_website')) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'invalid_request']));
        exit;
    }

    $rate_limit = khokan_rate_limit_request('jrc_auth_login', 10, 30 * MINUTE_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'rate_limit']));
        exit;
    }

    $identifier = isset($_POST['jrc_login_identifier']) ? sanitize_text_field(wp_unslash($_POST['jrc_login_identifier'])) : '';
    $password = isset($_POST['jrc_login_password']) ? (string) wp_unslash($_POST['jrc_login_password']) : '';
    $remember = !empty($_POST['jrc_login_remember']);

    if ($identifier === '' || $password === '') {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'login_failed']));
        exit;
    }

    $user_login = $identifier;
    if (is_email($identifier)) {
        $user = get_user_by('email', $identifier);
        if ($user instanceof WP_User) {
            $user_login = $user->user_login;
        }
    }

    $signed_in_user = wp_signon([
        'user_login' => $user_login,
        'user_password' => $password,
        'remember' => $remember,
    ], is_ssl());

    if (is_wp_error($signed_in_user)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth_error' => 'login_failed']));
        exit;
    }

    if (jrc_user_requires_email_verification($signed_in_user)) {
        $verification = jrc_maybe_send_email_verification($signed_in_user->ID, $course_key);
        if (is_wp_error($verification)) {
            wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_send_failed']));
            exit;
        }

        wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth' => 'verify_sent']));
        exit;
    }

    wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'logged_in']));
    exit;
}

add_action('admin_post_jrc_resend_verification', 'jrc_handle_resend_verification');
add_action('admin_post_nopriv_jrc_resend_verification', 'jrc_handle_resend_verification');
function jrc_handle_resend_verification()
{
    $course_key = jrc_get_requested_course_key($_POST['jrc_course'] ?? '');
    $redirect = jrc_get_auth_page_url('verify', $course_key);

    if (!is_user_logged_in()) {
        wp_safe_redirect(jrc_get_auth_page_url('login', $course_key, ['jrc_auth_error' => 'auth_required']));
        exit;
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_resend_verification')) {
        wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'invalid_request']));
        exit;
    }

    $rate_limit = khokan_rate_limit_request('jrc_resend_verification', 4, HOUR_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'rate_limit']));
        exit;
    }

    $user = wp_get_current_user();
    if (!$user instanceof WP_User || !$user->exists()) {
        wp_safe_redirect(jrc_get_auth_page_url('login', $course_key, ['jrc_auth_error' => 'auth_required']));
        exit;
    }

    if (!jrc_user_requires_email_verification($user)) {
        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'verified']));
        exit;
    }

    $verification = jrc_maybe_send_email_verification($user->ID, $course_key, true);
    if (is_wp_error($verification)) {
        wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_send_failed']));
        exit;
    }

    wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth' => 'verify_sent']));
    exit;
}

function jrc_render_frontend_route_page($route)
{
    $course_key = jrc_get_requested_course_key();
    $course_options = jrc_get_application_course_options();
    $course_notes = jrc_get_application_course_notes();
    $selected_label = $course_key && isset($course_options[$course_key])
        ? $course_options[$course_key]
        : 'Choose your course and continue';

    status_header(200);
    nocache_headers();
    global $wp_query;
    if (isset($wp_query) && $wp_query instanceof WP_Query) {
        $wp_query->is_404 = false;
    }

    get_header();
    ?>
    <main class="section jrc-auth-page">
        <div class="container jrc-auth-page__inner">
            <?php if ($route === 'dashboard') : ?>
                <div class="jrc-auth-page__hero">
                    <span class="jrc-auth-intro__eyebrow">Student Dashboard</span>
                    <h1>Track your application and payment status</h1>
                    <p class="section-subtitle">Everything related to your course application stays under one account.</p>
                </div>
                <div class="jrc-auth-page__shell">
                    <?php echo do_shortcode('[jrc_student_dashboard]'); ?>
                </div>
            <?php elseif ($route === 'verify') : ?>
                <?php $feedback = jrc_get_auth_feedback_message(); ?>
                <div class="jrc-auth-page__hero">
                    <span class="jrc-auth-intro__eyebrow">Verify Email</span>
                    <h1>Confirm your email to continue</h1>
                    <p class="section-subtitle">Selected course: <strong><?php echo esc_html($selected_label); ?></strong></p>
                </div>
                <div class="jrc-auth-page__shell">
                    <?php if ($feedback) : ?>
                        <p class="section-subtitle jrc-auth-message <?php echo $feedback['type'] === 'success' ? 'jrc-success-message' : 'jrc-error-message'; ?>">
                            <?php echo esc_html($feedback['message']); ?>
                        </p>
                    <?php endif; ?>
                    <?php if (!is_user_logged_in()) : ?>
                        <div class="jrc-auth-shell">
                            <div class="jrc-auth-intro">
                                <h3>Log in to manage verification</h3>
                                <p class="section-subtitle">Use your account to resend the verification email or go back to your application.</p>
                            </div>
                            <div class="jrc-inline-actions">
                                <a class="primary-btn" href="<?php echo esc_url(jrc_get_auth_page_url('login', $course_key)); ?>">Log In</a>
                                <a class="secondary-btn" href="<?php echo esc_url(jrc_get_auth_page_url('signup', $course_key)); ?>">Create Account</a>
                            </div>
                        </div>
                    <?php else : ?>
                        <?php
                        $current_user = wp_get_current_user();
                        if ($current_user instanceof WP_User && jrc_is_user_email_verified($current_user)) :
                            ?>
                            <div class="jrc-auth-shell">
                                <div class="jrc-auth-intro">
                                    <h3>Email already verified</h3>
                                    <p class="section-subtitle">Your account is verified. Continue to the application or open your dashboard.</p>
                                </div>
                                <div class="jrc-inline-actions">
                                    <a class="primary-btn" href="<?php echo esc_url(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'verified'])); ?>">Continue Application</a>
                                    <a class="secondary-btn" href="<?php echo esc_url(jrc_get_student_dashboard_url()); ?>">Open Dashboard</a>
                                </div>
                            </div>
                        <?php else : ?>
                            <?php echo jrc_render_email_verification_gate($course_key); ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php else : ?>
                <?php
                $auth_shell_class = 'jrc-auth-page__shell';
                if (in_array($route, ['signup', 'login'], true)) {
                    $auth_shell_class .= ' jrc-auth-page__shell--' . $route;
                }
                ?>
                <div class="jrc-auth-page__hero">
                    <span class="jrc-auth-intro__eyebrow"><?php echo $route === 'signup' ? 'Create Account' : 'Log In'; ?></span>
                    <h1><?php echo $route === 'signup' ? 'Create your account to apply' : 'Log in to continue your application'; ?></h1>
                    <p class="section-subtitle">Selected course: <strong><?php echo esc_html($selected_label); ?></strong></p>
                </div>
                <div class="<?php echo esc_attr($auth_shell_class); ?>">
                    <?php echo jrc_render_application_auth_gate($course_key, $course_notes, $route); ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    <?php
    get_footer();
    exit;
}

add_action('template_redirect', function () {
    $route = jrc_get_current_frontend_route();
    if ($route === '') {
        return;
    }

    $course_key = jrc_get_requested_course_key();

    if ($route === 'verify' && isset($_GET['uid'], $_GET['token'])) {
        $user_id = (int) $_GET['uid'];
        $verification = jrc_verify_email_token($user_id, sanitize_text_field(wp_unslash($_GET['token'])));
        if (is_wp_error($verification)) {
            $error_key = $verification->get_error_code() === 'expired_token' ? 'verify_expired' : 'verify_invalid';
            wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => $error_key]));
            exit;
        }

        if (!is_user_logged_in()) {
            $verified_user = get_userdata($user_id);
            if ($verified_user instanceof WP_User && $verified_user->exists()) {
                wp_set_current_user($verified_user->ID);
                wp_set_auth_cookie($verified_user->ID, true, is_ssl());
                do_action('wp_login', $verified_user->user_login, $verified_user);
            }
        }

        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'verified']));
        exit;
    }

    if (in_array($route, ['login', 'signup'], true) && is_user_logged_in()) {
        $current_user = wp_get_current_user();
        if ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user)) {
            wp_safe_redirect(jrc_get_auth_page_url('verify', $course_key, ['jrc_auth_error' => 'verify_required']));
            exit;
        }

        wp_safe_redirect(jrc_get_application_redirect_url($course_key, ['jrc_auth' => 'logged_in']));
        exit;
    }

    if ($route === 'dashboard' && !is_user_logged_in()) {
        wp_safe_redirect(jrc_get_auth_page_url('login', $course_key, ['jrc_auth_error' => 'auth_required']));
        exit;
    }

    jrc_render_frontend_route_page($route);
}, 1);

function jrc_render_application_form_html(array $fields, $button_label, $course_key = '', array $course_notes = [], $success_message = '', $lock_course = false)
{
    if (!is_user_logged_in()) {
        return jrc_render_application_auth_gate($course_key, $course_notes);
    }

    $current_user = wp_get_current_user();
    if ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user)) {
        return jrc_render_email_verification_gate($course_key);
    }

    $action_url = admin_url('admin-post.php');
    $nonce = wp_create_nonce('jrc_application_submit');
    $course_options = jrc_get_application_course_options();
    $prefill = jrc_get_application_prefill_data($course_key);
    $values = is_array($prefill['values'] ?? null) ? $prefill['values'] : [];
    $course_key = jrc_get_requested_course_key($prefill['course_key'] ?? $course_key);
    $application_id = (int) ($prefill['application_id'] ?? 0);
    $seat_param = sanitize_text_field($_GET['seat'] ?? '');
    $show_success = in_array($seat_param, ['1', 'true'], true);
    $app_error = sanitize_key($_GET['app_error'] ?? '');
    $show_error = $app_error !== '';
    $auth_feedback = jrc_get_auth_feedback_message();
    $error_message = 'Please complete all required fields (and keep the answer within 100 words).';
    if ($app_error === 'rate_limit') {
        $error_message = 'Too many attempts detected. Please wait a little and submit again.';
    } elseif ($app_error === 'invalid_request') {
        $error_message = 'Invalid submission detected. Please refresh the page and try again.';
    } elseif ($app_error === 'server') {
        $error_message = 'Could not save your application right now. Please try again shortly.';
    }
    $account_email = $current_user instanceof WP_User ? $current_user->user_email : '';
    $grouped = [];
    foreach ($fields as $field) {
        if (!is_array($field)) {
            continue;
        }
        $group = $field['group'] ?? 'Other Information';
        if (!isset($grouped[$group])) {
            $grouped[$group] = [];
        }
        $grouped[$group][] = $field;
    }
    $group_order = [
        'Course Selection',
        'Personal Information',
        'Course Information',
        'Learning Status',
        'Motivation',
        'Other Information',
    ];

    ob_start();
    ?>
    <?php if ($auth_feedback) : ?>
        <p class="section-subtitle jrc-auth-message <?php echo $auth_feedback['type'] === 'success' ? 'jrc-success-message' : 'jrc-error-message'; ?>">
            <?php echo esc_html($auth_feedback['message']); ?>
        </p>
    <?php endif; ?>
    <?php if ($show_success) : ?>
        <?php if ($success_message) : ?>
            <p class="section-subtitle jrc-success-message"><?php echo esc_html($success_message); ?></p>
        <?php endif; ?>
    <?php endif; ?>
    <?php if ($show_error) : ?>
        <p class="section-subtitle jrc-auth-message jrc-error-message"><?php echo esc_html($error_message); ?></p>
    <?php endif; ?>
    <div class="jrc-account-notice">
        <div>
            <span class="jrc-account-notice__eyebrow">Account Linked</span>
            <p class="jrc-account-notice__copy">
                Signed in as <strong><?php echo esc_html($account_email); ?></strong>.
                <?php echo $application_id > 0 ? esc_html__('Your previous application details are loaded below. Update anything needed and submit again.', 'khokan') : esc_html__('Your application will be saved under this account.', 'khokan'); ?>
            </p>
        </div>
        <?php echo jrc_render_logout_form(jrc_get_application_redirect_url($course_key), 'Use another account', 'jrc-auth-helper-button', 'jrc-logout-form jrc-logout-form--helper'); ?>
    </div>
    <form class="jrc-application-form" method="post" action="<?php echo esc_url($action_url); ?>" novalidate>
        <input type="hidden" name="action" value="jrc_application_submit">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
            <label for="jrc-website">Website</label>
            <input type="text" id="jrc-website" name="jrc_website" tabindex="-1" autocomplete="off">
        </div>
        <div class="jrc-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
            <div class="jrc-progress__bar"></div>
            <span class="jrc-progress__label">0% complete</span>
        </div>

        <?php
        $course_selection_html = '';
        if ($lock_course && $course_key && isset($course_options[$course_key])) {
            $course_label = $course_options[$course_key];
            $course_selection_html .= '<div class="jrc-form-field jrc-form-field--course">';
            $course_selection_html .= '<p class="jrc-course-selected">Selected Course: <strong data-jrc-selected-course-label>' . esc_html($course_label) . '</strong></p>';
            $course_selection_html .= '<input type="hidden" name="student_course" value="' . esc_attr($course_key) . '">';
            if (!empty($course_notes[$course_key])) {
                $course_selection_html .= '<p class="jrc-course-note is-active" data-course="' . esc_attr($course_key) . '">' . esc_html($course_notes[$course_key]) . '</p>';
            }
            $course_selection_html .= '</div>';
        } else {
            $course_selection_html .= '<div class="jrc-form-field jrc-form-field--course">';
            $course_selection_html .= '<fieldset>';
            $course_selection_html .= '<legend>Which course would you like to enroll in? *</legend>';
            $course_selection_html .= '<div class="jrc-form-options jrc-course-options">';
            $course_index = 0;
            foreach ($course_options as $key => $label) {
                $input_id = 'jrc-course-' . $course_index;
                $is_selected = $course_key === $key;
                $course_selection_html .= '<label for="' . esc_attr($input_id) . '">';
                $course_selection_html .= '<input type="radio" id="' . esc_attr($input_id) . '" name="student_course" value="' . esc_attr($key) . '" data-label="Course Selection" ' . ($course_index === 0 ? 'required' : '') . ($is_selected ? ' checked' : '') . '>';
                $course_selection_html .= esc_html($label);
                $course_selection_html .= '</label>';
                $course_index++;
            }
            $course_selection_html .= '</div></fieldset>';
            $course_selection_html .= '<span class="jrc-error" data-error-for="student_course"></span>';
            if (!empty($course_notes)) {
                $course_selection_html .= '<div class="jrc-course-notes">';
                foreach ($course_notes as $key => $note) {
                    $course_selection_html .= '<p class="jrc-course-note' . ($course_key === $key ? ' is-active' : '') . '" data-course="' . esc_attr($key) . '">' . esc_html($note) . '</p>';
                }
                $course_selection_html .= '</div>';
            }
            $course_selection_html .= '</div>';
        }
        ?>

        <?php foreach ($group_order as $group_title) : ?>
            <?php if ($group_title === 'Course Selection') : ?>
                <div class="jrc-form-group">
                    <h4 class="jrc-form-group__title"><?php echo esc_html($group_title); ?></h4>
                    <?php echo $course_selection_html; ?>
                </div>
                <?php continue; ?>
            <?php endif; ?>
            <?php if (empty($grouped[$group_title])) : ?>
                <?php continue; ?>
            <?php endif; ?>
            <div class="jrc-form-group">
                <h4 class="jrc-form-group__title"><?php echo esc_html($group_title); ?></h4>
                <div class="jrc-form-group__fields">
                    <?php foreach ($grouped[$group_title] as $field) : ?>
                        <?php
                        $label = $field['label'] ?? '';
                        $type = $field['type'] ?? 'text';
                        $name = $field['name'] ?? '';
                        if ($label === '' || $name === '') {
                            continue;
                        }
                        $required = !empty($field['required']);
                        $options = $field['options'] ?? [];
                        $max_words = $field['max_words'] ?? null;
                        $field_id = 'jrc-' . sanitize_title($name);
                        $wrapper_attrs = '';
                        if ($name === 'student_referral_other') {
                            $wrapper_attrs = ' data-toggle="jrc-referral-other"';
                        }
                        $placeholder = $field['placeholder'] ?? $label;
                        $error_id = 'jrc-error-' . sanitize_title($name);
                        $field_value = $values[$name] ?? '';
                        $field_values = is_array($field_value) ? array_map('strval', $field_value) : [];
                        $field_value = is_scalar($field_value) ? (string) $field_value : '';
                        $is_account_email = $name === 'student_email' && $account_email !== '';
                        $readonly_attr = $is_account_email ? ' readonly' : '';
                        ?>
                        <div class="jrc-form-field"<?php echo $wrapper_attrs; ?>>
                            <?php if ($type === 'checkboxes' || $type === 'radio') : ?>
                                <fieldset>
                                    <legend><?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?></legend>
                                    <div class="jrc-form-options">
                                        <?php foreach ((array) $options as $index => $option) : ?>
                                            <?php
                                            $input_name = $type === 'checkboxes' ? $name . '[]' : $name;
                                            $input_id = $field_id . '-' . $index;
                                            $needs_required = $required && $type === 'radio' && $index === 0;
                                            ?>
                                            <label for="<?php echo esc_attr($input_id); ?>">
                                                <input type="<?php echo esc_attr($type === 'checkboxes' ? 'checkbox' : 'radio'); ?>"
                                                       id="<?php echo esc_attr($input_id); ?>"
                                                       name="<?php echo esc_attr($input_name); ?>"
                                                       value="<?php echo esc_attr($option); ?>"
                                                       data-label="<?php echo esc_attr($label); ?>"
                                                       aria-describedby="<?php echo esc_attr($error_id); ?>"
                                                       <?php echo $needs_required ? 'required' : ''; ?>
                                                       <?php echo $type === 'checkboxes' ? (in_array((string) $option, $field_values, true) ? 'checked' : '') : ($field_value === (string) $option ? 'checked' : ''); ?>>
                                                <?php echo esc_html($option); ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </fieldset>
                                <span class="jrc-error" id="<?php echo esc_attr($error_id); ?>" data-error-for="<?php echo esc_attr($name); ?>"></span>
                            <?php elseif ($type === 'select') : ?>
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?>
                                </label>
                                <select id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>" data-label="<?php echo esc_attr($label); ?>" aria-describedby="<?php echo esc_attr($error_id); ?>" <?php echo $required ? 'required' : ''; ?>>
                                    <option value=""><?php echo esc_html($field['placeholder'] ?? 'Select an option'); ?></option>
                                    <?php foreach ((array) $options as $option) : ?>
                                        <option value="<?php echo esc_attr($option); ?>" <?php selected($field_value, (string) $option); ?>><?php echo esc_html($option); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="jrc-error" id="<?php echo esc_attr($error_id); ?>" data-error-for="<?php echo esc_attr($name); ?>"></span>
                            <?php elseif ($type === 'textarea') : ?>
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?>
                                </label>
                                <textarea id="<?php echo esc_attr($field_id); ?>"
                                          name="<?php echo esc_attr($name); ?>"
                                          rows="3"
                                          placeholder="<?php echo esc_attr($placeholder); ?>"
                                          data-label="<?php echo esc_attr($label); ?>"
                                          aria-describedby="<?php echo esc_attr($error_id); ?>"
                                          <?php echo $required ? 'required' : ''; ?>
                                          <?php echo $max_words ? 'data-max-words="' . esc_attr($max_words) . '"' : ''; ?>><?php echo esc_textarea($field_value); ?></textarea>
                                <?php if ($max_words) : ?>
                                    <small class="jrc-word-count" data-max="<?php echo esc_attr($max_words); ?>">0 / <?php echo esc_html($max_words); ?> words</small>
                                <?php endif; ?>
                                <span class="jrc-error" id="<?php echo esc_attr($error_id); ?>" data-error-for="<?php echo esc_attr($name); ?>"></span>
                            <?php elseif ($type === 'checkbox') : ?>
                                <label class="jrc-form-checkbox" for="<?php echo esc_attr($field_id); ?>">
                                    <input type="checkbox" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>" value="1" data-label="<?php echo esc_attr($label); ?>" aria-describedby="<?php echo esc_attr($error_id); ?>" <?php echo $required ? 'required' : ''; ?> <?php checked(in_array(strtolower($field_value), ['1', 'yes', 'on', 'true'], true)); ?>>
                                    <?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?>
                                </label>
                                <span class="jrc-error" id="<?php echo esc_attr($error_id); ?>" data-error-for="<?php echo esc_attr($name); ?>"></span>
                            <?php else : ?>
                                <label for="<?php echo esc_attr($field_id); ?>">
                                    <?php echo esc_html($label); ?><?php echo $required ? ' *' : ''; ?>
                                </label>
                                <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($field_id); ?>" name="<?php echo esc_attr($name); ?>" placeholder="<?php echo esc_attr($placeholder); ?>" data-label="<?php echo esc_attr($label); ?>" aria-describedby="<?php echo esc_attr($error_id); ?>" value="<?php echo esc_attr($field_value); ?>" <?php echo $required ? 'required' : ''; ?><?php echo $readonly_attr; ?>>
                                <?php if ($is_account_email) : ?>
                                    <small class="jrc-field-helper">This email comes from your account and will be used for your application.</small>
                                <?php endif; ?>
                                <span class="jrc-error" id="<?php echo esc_attr($error_id); ?>" data-error-for="<?php echo esc_attr($name); ?>"></span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <button type="submit" class="primary-btn"><?php echo esc_html($button_label ?: 'Submit'); ?></button>
    </form>
    <?php
    return ob_get_clean();
}

add_shortcode('jrc_application_form', function ($atts) {
    $course = function_exists('jrc_get_course_data') ? jrc_get_course_data() : [];
    $fields = $course['enrollment']['website']['fields'] ?? [];
    $button_label = $course['enrollment']['website']['button'] ?? 'Submit';
    $success_message = $course['enrollment']['website']['success'] ?? '';
    $atts = shortcode_atts(['course' => ''], $atts, 'jrc_application_form');
    $locked_course_key = jrc_get_requested_course_key($atts['course']);
    $course_key = $locked_course_key;
    if ($course_key === '') {
        $course_key = jrc_get_requested_course_key();
    }
    $notes = [];
    $items = $course['course_paths']['items'] ?? [];
    foreach ($items as $item) {
        $key = $item['key'] ?? '';
        $note = $item['apply_note'] ?? '';
        if ($key && $note) {
            $notes[$key] = $note;
        }
    }

    if (!is_user_logged_in()) {
        return jrc_render_application_auth_gate($course_key, $notes);
    }

    return jrc_render_application_form_html($fields, $button_label, $course_key, $notes, $success_message, $locked_course_key !== '');
});

add_shortcode('jrc_student_dashboard', function () {
    if (!is_user_logged_in()) {
        return '<div class="jrc-auth-shell"><div class="jrc-auth-intro"><h3>Please log in to view your dashboard</h3><p class="section-subtitle">Use your account to track application and payment status.</p></div><div class="jrc-inline-actions"><a class="primary-btn" href="' . esc_url(jrc_get_auth_page_url('login')) . '">Log In</a><a class="secondary-btn" href="' . esc_url(jrc_get_auth_page_url('signup')) . '">Create Account</a></div></div>';
    }

    $current_user = wp_get_current_user();
    if ($current_user instanceof WP_User && jrc_user_requires_email_verification($current_user)) {
        return jrc_render_email_verification_gate(jrc_get_requested_course_key());
    }

    $user_id = get_current_user_id();
    $application_id = (int) get_user_meta($user_id, 'jrc_application_id', true);
    if (!$application_id) {
        $user = wp_get_current_user();
        $matches = get_posts([
            'post_type' => 'jrc_application',
            'post_status' => 'private',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => 'student_email',
                    'value' => $user->user_email,
                ],
            ],
        ]);
        if (!empty($matches)) {
            $application_id = $matches[0]->ID;
            update_user_meta($user_id, 'jrc_application_id', $application_id);
        }
    }
    if (!$application_id) {
        return '<div class="jrc-auth-shell"><div class="jrc-auth-intro"><h3>No application found yet</h3><p class="section-subtitle">Start from the Job Ready Course page and submit your application with this account.</p></div><div class="jrc-inline-actions"><a class="primary-btn" href="' . esc_url(jrc_get_course_application_page_url()) . '">Open Application Form</a></div></div>';
    }

    $status = get_post_meta($application_id, 'application_status', true) ?: 'applied';
    $statuses = jrc_get_application_statuses();
    $status_label = $statuses[$status] ?? 'Applied';
    $course_key = get_post_meta($application_id, 'student_course', true);
    $course_labels = jrc_get_application_course_options();
    $course_label = $course_labels[$course_key] ?? 'Selected course';
    $nonce = wp_create_nonce('jrc_payment_submit');
    $payment_error = sanitize_key($_GET['payment_error'] ?? '');
    $payment_success = sanitize_key($_GET['payment_success'] ?? '');

    ob_start();
    ?>
    <div class="jrc-student-dashboard">
        <h3>Course: <?php echo esc_html($course_label); ?></h3>
        <p>Application Status: <?php echo esc_html($status_label); ?></p>
        <p>We will contact you as you move through the steps.</p>
        <?php if ($payment_success === '1') : ?>
            <p class="section-subtitle jrc-success-message"><?php echo esc_html('Payment proof submitted successfully.'); ?></p>
        <?php elseif ($payment_error === 'missing') : ?>
            <p class="section-subtitle"><?php echo esc_html('Please add a transaction ID or upload a payment proof file.'); ?></p>
        <?php elseif ($payment_error === 'file') : ?>
            <p class="section-subtitle"><?php echo esc_html('Upload only JPG, PNG, or PDF files up to 5 MB.'); ?></p>
        <?php elseif ($payment_error === 'rate_limit') : ?>
            <p class="section-subtitle"><?php echo esc_html('Too many attempts detected. Please wait and try again.'); ?></p>
        <?php endif; ?>
        <form class="jrc-payment-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="jrc_payment_submit">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
            <label>
                Payment Transaction ID (add this or upload proof)
                <input type="text" name="payment_txn_id">
            </label>
            <label>
                Upload Payment Proof (JPG, PNG, or PDF)
                <input type="file" name="payment_proof" accept=".jpg,.jpeg,.png,.pdf">
            </label>
            <button type="submit" class="primary-btn">Submit Payment Proof</button>
        </form>
    </div>
    <?php
    return ob_get_clean();
});

add_action('admin_post_jrc_payment_submit', 'jrc_handle_payment_submit');
function jrc_handle_payment_submit()
{
    if (!is_user_logged_in()) {
        wp_die('Unauthorized', 403);
    }
    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'jrc_payment_submit')) {
        wp_die('Invalid nonce', 403);
    }

    $rate_limit = khokan_rate_limit_request('jrc_payment_submit', 6, HOUR_IN_SECONDS);
    $redirect = wp_get_referer() ?: home_url('/');
    if (is_wp_error($rate_limit)) {
        $redirect = add_query_arg('payment_error', 'rate_limit', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    $user_id = get_current_user_id();
    $application_id = (int) get_user_meta($user_id, 'jrc_application_id', true);
    if (!$application_id) {
        wp_die('No application found', 404);
    }

    $linked_user_id = (int) get_post_meta($application_id, 'application_user_id', true);
    if ($linked_user_id && $linked_user_id !== $user_id && !jrc_current_user_can_manage_course()) {
        wp_die('Unauthorized', 403);
    }

    $txn_id = isset($_POST['payment_txn_id']) ? sanitize_text_field(wp_unslash($_POST['payment_txn_id'])) : '';
    $has_file = !empty($_FILES['payment_proof']['name']);
    if ($txn_id === '' && !$has_file) {
        $redirect = add_query_arg('payment_error', 'missing', $redirect);
        wp_safe_redirect($redirect);
        exit;
    }

    if ($txn_id !== '') {
        update_post_meta($application_id, 'payment_txn_id', $txn_id);
    }

    if ($has_file) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $allowed_mimes = [
            'jpg|jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'pdf' => 'application/pdf',
        ];
        $file_size = isset($_FILES['payment_proof']['size']) ? (int) $_FILES['payment_proof']['size'] : 0;
        $checked_file = wp_check_filetype_and_ext(
            $_FILES['payment_proof']['tmp_name'],
            $_FILES['payment_proof']['name'],
            $allowed_mimes
        );

        if ($file_size <= 0 || $file_size > 5 * MB_IN_BYTES || empty($checked_file['ext']) || empty($checked_file['type'])) {
            $redirect = add_query_arg('payment_error', 'file', $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $uploaded = wp_handle_upload($_FILES['payment_proof'], [
            'test_form' => false,
            'mimes' => $allowed_mimes,
        ]);

        if (isset($uploaded['error'])) {
            $redirect = add_query_arg('payment_error', 'file', $redirect);
            wp_safe_redirect($redirect);
            exit;
        }

        $attachment_id = wp_insert_attachment([
            'post_mime_type' => $uploaded['type'],
            'post_title' => sanitize_file_name(basename($uploaded['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $application_id,
        ], $uploaded['file']);

        if (!is_wp_error($attachment_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata($attachment_id, wp_generate_attachment_metadata($attachment_id, $uploaded['file']));
            update_post_meta($application_id, 'payment_proof_id', $attachment_id);
        }
    }

    update_post_meta($application_id, 'application_status', 'payment_pending');
    update_post_meta($application_id, 'application_status_updated_at', current_time('mysql'));
    jrc_notify_application_event($application_id, 'payment_pending');

    $redirect = add_query_arg('payment_success', '1', $redirect);
    wp_safe_redirect($redirect);
    exit;
}

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
        'hero_organization' => ['label' => 'Hero Organization'],
        'hero_logo' => ['label' => 'Hero Logo', 'type' => 'image', 'description' => 'Upload the Job Ready Course hero logo.'],
        'header_brand_title' => ['label' => 'Header Brand Title (Job Ready)'],
        'header_brand_tagline' => ['label' => 'Header Brand Tagline (Job Ready)'],
        'header_brand_logo' => ['label' => 'Header Brand Logo (Job Ready)', 'type' => 'image', 'description' => 'Upload the Job Ready Course header logo.'],
        'cta_apply_label' => ['label' => 'Enroll Button Label'],
        'cta_apply_link' => ['label' => 'Enroll Button Link', 'type' => 'url'],
        'quiz_cta_label' => ['label' => 'Basic Test Button Label'],
        'quiz_cta_link' => ['label' => 'Basic Test Button Link', 'type' => 'url'],
        'quiz_pass_redirect_link' => ['label' => 'After Test Redirect Link', 'type' => 'url'],
        'whatsapp_group_link' => ['label' => 'WhatsApp Group Link', 'type' => 'url'],
        'whatsapp_number' => ['label' => 'WhatsApp Number'],
        'whatsapp_note' => ['label' => 'WhatsApp Note', 'type' => 'textarea', 'rows' => 2],
        'whatsapp_group_label' => ['label' => 'WhatsApp Group Label'],
        'whatsapp_contact_label' => ['label' => 'WhatsApp Contact Label'],
        'hero_card_title' => ['label' => 'Quick Info Title'],
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
        'fee_standard' => ['label' => 'Original Course Fee'],
        'fee_early' => ['label' => 'Discounted Price'],
        'fee_installment' => ['label' => 'Installment Line'],
        'fee_note' => ['label' => 'Fee Note (extra)', 'type' => 'textarea', 'rows' => 2],
        'booked_seats' => ['label' => 'Reserved Seats (Pre-booking)'],
        'remaining_seats' => [
            'label' => 'Remaining Seats (auto)',
            'input_attrs' => ['readonly' => 'readonly'],
            'description' => 'Auto-calculated: Seat Limit - Booked Seats.',
            'default' => (string) $remaining_seats_snapshot,
        ],
        'early_bird_deadline' => ['label' => 'Discount Note'],
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
        'mentors_enabled' => ['label' => 'Show Mentor Panel', 'type' => 'checkbox'],
        'mentors_title' => ['label' => 'Mentor Panel Title'],
        'mentors_subtitle' => ['label' => 'Mentor Panel Subtitle', 'type' => 'textarea', 'rows' => 2],
        'mentor_items' => [
            'label' => 'Mentor Items',
            'type' => 'textarea',
            'rows' => 6,
            'description' => 'One per line. Format: Name | Role | Experience | Specialization | Real Projects | LinkedIn | GitHub | Message | Enabled (1/0)',
        ],
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
        $sanitize_callback = $is_link ? 'jrc_sanitize_course_link' : 'sanitize_textarea_field';
        if ($key === 'hero_title') {
            $sanitize_callback = 'jrc_sanitize_hero_title';
        }
        if (($field['type'] ?? '') === 'image') {
            $sanitize_callback = 'esc_url_raw';
        }
        $wp_customize->add_setting($setting_id, [
            'type' => 'option',
            'default' => $field['default'] ?? ($defaults[$key] ?? ''),
            'sanitize_callback' => $sanitize_callback,
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

        if (($field['type'] ?? '') === 'image') {
            $wp_customize->add_control(new WP_Customize_Image_Control(
                $wp_customize,
                $setting_id,
                $control_args
            ));
        } else {
            $wp_customize->add_control($setting_id, $control_args);
        }
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

function jrc_get_default_quiz_questions()
{
    return [
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'int x = 5; x += 2; এখন x এর মান কত?',
            'options' => ['5', '7', '8', '2'],
            'answer' => '7',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'C তে float data type এর উদাহরণ কোনটি?',
            'options' => ['3', '3.14', '\'a\'', 'true'],
            'answer' => '3.14',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'short',
            'question' => 'C তে function কোন keyword দিয়ে কিছু return না করলে ব্যবহার হয়?',
            'options' => [],
            'answer' => 'void',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'short',
            'question' => 'C তে array index শুরু হয় কত থেকে?',
            'options' => [],
            'answer' => '0',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'output',
            'question' => 'Output কী হবে?\nint x = 3; while (x < 6) { x++; } printf(\"%d\", x);',
            'options' => [],
            'answer' => '6',
            'points' => 2,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'for loop এ initialization অংশ কোথায় থাকে?',
            'options' => ['প্রথম অংশ', 'দ্বিতীয় অংশ', 'তৃতীয় অংশ', 'কোনোটাই না'],
            'answer' => 'প্রথম অংশ',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'printf ব্যবহার করতে কোন header file লাগে?',
            'options' => ['stdio.h', 'stdlib.h', 'string.h', 'math.h'],
            'answer' => 'stdio.h',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'short',
            'question' => 'C তে logical AND operator কী?',
            'options' => [],
            'answer' => '&&',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'sizeof operator কী return করে?',
            'options' => ['bytes', 'bits', 'value', 'address'],
            'answer' => 'bytes',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'output',
            'question' => 'Output কী হবে?\nint x = 1; x = x * 2 + 3; printf(\"%d\", x);',
            'options' => [],
            'answer' => '5',
            'points' => 2,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'পয়েন্টার ডিক্লেয়ার করার সঠিক syntax কোনটি?',
            'options' => ['int *p;', 'int p*;', 'int &p;', 'pointer int p;'],
            'answer' => 'int *p;',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'short',
            'question' => 'C string এর শেষ character কী?',
            'options' => [],
            'answer' => '\\0',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'output',
            'question' => 'Output কী হবে?\nint i = 0; do { i++; } while (i < 3); printf(\"%d\", i);',
            'options' => [],
            'answer' => '3',
            'points' => 2,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'switch statement ব্যবহার করতে কোন keyword লাগে?',
            'options' => ['switch', 'case', 'select', 'match'],
            'answer' => 'switch',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'short',
            'question' => 'C তে multi-line comment শুরু হয় কী দিয়ে?',
            'options' => [],
            'answer' => '/*|/* */',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'printf এ float এর format specifier কোনটি?',
            'options' => ['%f', '%d', '%c', '%s'],
            'answer' => '%f',
            'points' => 1,
        ],
        [
            'language' => 'C',
            'type' => 'output',
            'question' => 'Output কী হবে?\nint a = 10; if (a > 5) { a -= 3; } printf(\"%d\", a);',
            'options' => [],
            'answer' => '7',
            'points' => 2,
        ],
        [
            'language' => 'C',
            'type' => 'mcq',
            'question' => 'কোন loop কমপক্ষে একবার চলে?',
            'options' => ['for', 'while', 'do-while', 'goto'],
            'answer' => 'do-while',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'JavaScript এ const দিয়ে variable ঘোষণা করলে কী হয়?',
            'options' => ['মান পরিবর্তন করা যায়', 'পুনরায় ঘোষণা করা যায়', 'মান পরিবর্তন করা যায় না', 'শুধু number রাখা যায়'],
            'answer' => 'মান পরিবর্তন করা যায় না',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'JavaScript এ Array এর length কীভাবে পাওয়া যায়?',
            'options' => ['arr.size', 'arr.length', 'arr.count', 'arr.total'],
            'answer' => 'arr.length',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'short',
            'question' => 'JavaScript এ strict equality operator কী?',
            'options' => [],
            'answer' => '===',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'short',
            'question' => 'JavaScript এ single line comment কীভাবে লেখা হয়?',
            'options' => [],
            'answer' => '//',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'output',
            'question' => 'Output কী হবে?\nlet x = 2; for (let i = 0; i < 3; i++) { x++; } console.log(x);',
            'options' => [],
            'answer' => '5',
            'points' => 2,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'Function call করার জন্য কোন syntax সঠিক?',
            'options' => ['myFunc[]', 'myFunc()', 'myFunc{}', 'myFunc<>'],
            'answer' => 'myFunc()',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'typeof [] এর ফলাফল কী?',
            'options' => ['array', 'object', 'list', 'undefined'],
            'answer' => 'object',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'short',
            'question' => 'Template literal কোন quote দিয়ে লেখা হয়?',
            'options' => [],
            'answer' => '`',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'String কে number এ convert করতে কোনটা ব্যবহার হয়?',
            'options' => ['Number()', 'String()', 'toString()', 'Boolean()'],
            'answer' => 'Number()',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'output',
            'question' => 'Output কী হবে?\nlet x = 1; x += 2; console.log(x);',
            'options' => [],
            'answer' => '3',
            'points' => 2,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'নিচের কোনটি JavaScript primitive নয়?',
            'options' => ['string', 'number', 'boolean', 'array'],
            'answer' => 'array',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'short',
            'question' => 'Array এর শেষে element যোগ করতে কোন method ব্যবহার হয়?',
            'options' => [],
            'answer' => 'push',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'Block scope variable ঘোষণা করতে কোন keyword ব্যবহার হয়?',
            'options' => ['var', 'let', 'const', 'static'],
            'answer' => 'let',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'output',
            'question' => 'Output কী হবে?\nlet a = [1, 2]; a.push(3); console.log(a.length);',
            'options' => [],
            'answer' => '3',
            'points' => 2,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'short',
            'question' => 'JSON string parse করার method কী?',
            'options' => [],
            'answer' => 'JSON.parse',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'Strict not equal operator কোনটা?',
            'options' => ['!=', '!==', '<>', '=!'],
            'answer' => '!==',
            'points' => 1,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'output',
            'question' => 'Output কী হবে?\nconst x = \"5\"; console.log(typeof x);',
            'options' => [],
            'answer' => 'string',
            'points' => 2,
        ],
        [
            'language' => 'JavaScript',
            'type' => 'mcq',
            'question' => 'Arrow function এর সঠিক syntax কোনটি?',
            'options' => ['function => () {}', '() => {}', '() -> {}', '=> () {}'],
            'answer' => '() => {}',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Dart এ list তৈরি করার সঠিক উদাহরণ কোনটি?',
            'options' => ['List a = (1,2,3)', 'List<int> a = [1,2,3]', 'List<int> a = {1,2,3}', 'List<int> a = 1,2,3'],
            'answer' => 'List<int> a = [1,2,3]',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Dart এ constant variable ঘোষণা করতে কোন keyword ব্যবহার হয়?',
            'options' => ['let', 'var', 'const', 'static'],
            'answer' => 'const',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'short',
            'question' => 'Dart এ main function এর সঠিক signature কী?',
            'options' => [],
            'answer' => 'void main()',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'short',
            'question' => 'Dart এ string interpolation ব্যবহার করতে কোন symbol লাগে?',
            'options' => [],
            'answer' => '$',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'output',
            'question' => 'Output কী হবে?\nvar x = 1; while (x < 4) { x++; } print(x);',
            'options' => [],
            'answer' => '4',
            'points' => 2,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Dart এ for loop এর condition অংশ কোথায় থাকে?',
            'options' => ['প্রথম অংশ', 'দ্বিতীয় অংশ', 'তৃতীয় অংশ', 'লুপের বাইরে'],
            'answer' => 'দ্বিতীয় অংশ',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Dart এ final keyword এর মানে কী?',
            'options' => ['একবার সেট হয়', 'বারবার পরিবর্তন করা যায়', 'শুধু class এ ব্যবহার হয়', 'শুধু function এ'],
            'answer' => 'একবার সেট হয়',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'short',
            'question' => 'Dart list এর length property কী?',
            'options' => [],
            'answer' => 'length',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Key-value collection কোনটি?',
            'options' => ['List', 'Set', 'Map', 'Queue'],
            'answer' => 'Map',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'output',
            'question' => 'Output কী হবে?\nvar x = 2; x *= 3; print(x);',
            'options' => [],
            'answer' => '6',
            'points' => 2,
        ],
        [
            'language' => 'Dart',
            'type' => 'short',
            'question' => 'Dart এ null-coalescing operator কী?',
            'options' => [],
            'answer' => '??',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'async function লেখার জন্য কোন keyword লাগে?',
            'options' => ['async', 'await', 'future', 'stream'],
            'answer' => 'async',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'output',
            'question' => 'Output কী হবে?\nvar sum = 0; for (var i = 1; i <= 3; i++) { sum += i; } print(sum);',
            'options' => [],
            'answer' => '6',
            'points' => 2,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'true/false এর type কোনটি?',
            'options' => ['bool', 'Boolean', 'int', 'string'],
            'answer' => 'bool',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'short',
            'question' => 'Dart এ single line comment কীভাবে লেখা হয়?',
            'options' => [],
            'answer' => '//',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'String থেকে int এ convert করার সঠিক পদ্ধতি কোনটি?',
            'options' => ['int.parse', 'toInt', 'parseInt', 'int()'],
            'answer' => 'int.parse',
            'points' => 1,
        ],
        [
            'language' => 'Dart',
            'type' => 'output',
            'question' => 'Output কী হবে?\nvar list = [1, 2, 3]; list.removeAt(1); print(list.length);',
            'options' => [],
            'answer' => '2',
            'points' => 2,
        ],
        [
            'language' => 'Dart',
            'type' => 'mcq',
            'question' => 'Class declare করতে কোন keyword ব্যবহার হয়?',
            'options' => ['class', 'struct', 'object', 'module'],
            'answer' => 'class',
            'points' => 1,
        ],
    ];
}

function jrc_get_default_course_options()
{
    $defaults = jrc_get_default_course_data();
    $default_quiz_questions = jrc_get_default_quiz_questions();
    $mentor_lines = array_map(function ($mentor) {
        $fields = [
            $mentor['name'] ?? '',
            $mentor['role'] ?? '',
            $mentor['experience'] ?? '',
            $mentor['specialization'] ?? '',
            $mentor['projects'] ?? '',
            $mentor['linkedin'] ?? '',
            $mentor['github'] ?? '',
            $mentor['message'] ?? '',
        ];
        return implode(' | ', $fields);
    }, $defaults['mentors']['items'] ?? []);
    $mentor_items_default = implode("\n", $mentor_lines);

    $options = [
        'hero_title' => $defaults['hero']['title'],
        'hero_subtitle' => $defaults['hero']['subtitle'],
        'hero_note' => $defaults['hero']['note'] ?? '',
        'hero_organization' => $defaults['hero']['organization'] ?? '',
        'hero_logo' => $defaults['hero']['logo'] ?? '',
        'header_brand_title' => 'Khokan dev studio',
        'header_brand_tagline' => 'Job Ready Course',
        'header_brand_logo' => '',
        'cta_apply_label' => $defaults['hero']['ctas'][0]['label'],
        'cta_apply_link' => $defaults['hero']['ctas'][0]['link'],
        'quiz_cta_label' => 'Take Basic Test',
        'quiz_cta_link' => '',
        'quiz_pass_redirect_link' => '',
        'quiz_coupon_code' => '',
        'quiz_coupon_param' => 'coupon',
        'whatsapp_group_link' => $defaults['whatsapp']['group_link'] ?? '',
        'whatsapp_number' => $defaults['whatsapp']['number'] ?? '',
        'whatsapp_note' => $defaults['whatsapp']['note'] ?? '',
        'whatsapp_group_label' => $defaults['whatsapp']['group_label'] ?? 'Join WhatsApp Group',
        'whatsapp_contact_label' => $defaults['whatsapp']['contact_label'] ?? 'WhatsApp Number',
        'hero_card_title' => 'Quick Info',
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
        'quiz_title' => 'Basic Programming Test',
        'quiz_subtitle' => 'C / JavaScript / Dart basic screening. 20-25 minutes.',
        'quiz_discount_note' => 'Basic test result admin review-এর জন্য save হবে।',
        'quiz_time_limit' => '25',
        'quiz_pass_percent' => '60',
        'quiz_questions' => wp_json_encode($default_quiz_questions),
        'mentors_enabled' => '1',
        'mentors_title' => $defaults['mentors']['title'],
        'mentors_subtitle' => $defaults['mentors']['subtitle'],
        'mentor_items' => $mentor_items_default,
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

function jrc_parse_mentor_items($text, array $fallback)
{
    $lines = jrc_lines_to_array($text);
    if (!$lines) {
        return $fallback;
    }

    $mentors = [];
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', $line));
        $enabled = true;
        if (count($parts) > 8) {
            $last = strtolower(trim((string) end($parts)));
            $map = [
                '1' => true,
                'true' => true,
                'yes' => true,
                'on' => true,
                'enabled' => true,
                '0' => false,
                'false' => false,
                'no' => false,
                'off' => false,
                'disabled' => false,
            ];
            if (array_key_exists($last, $map)) {
                $enabled = $map[$last];
                array_pop($parts);
            }
        }

        $message = '';
        if (isset($parts[7])) {
            $message = implode(' | ', array_slice($parts, 7));
        }
        $mentor = [
            'name' => $parts[0] ?? '',
            'role' => $parts[1] ?? '',
            'experience' => $parts[2] ?? '',
            'specialization' => $parts[3] ?? '',
            'projects' => $parts[4] ?? '',
            'linkedin' => $parts[5] ?? '',
            'github' => $parts[6] ?? '',
            'message' => $message,
            'enabled' => $enabled,
        ];

        if ($mentor['name'] === '' && $mentor['role'] === '' && $mentor['message'] === '') {
            continue;
        }

        $mentors[] = $mentor;
    }

    return $mentors ?: $fallback;
}

function jrc_parse_quiz_questions($text, array $fallback)
{
    $decoded = json_decode((string) $text, true);
    if (!is_array($decoded)) {
        return $fallback;
    }

    $questions = [];
    foreach ($decoded as $item) {
        if (!is_array($item)) {
            continue;
        }

        $question = [
            'language' => $item['language'] ?? '',
            'type' => $item['type'] ?? 'mcq',
            'question' => $item['question'] ?? '',
            'options' => is_array($item['options'] ?? null) ? array_values($item['options']) : [],
            'answer' => $item['answer'] ?? '',
            'points' => isset($item['points']) ? (int) $item['points'] : 1,
        ];

        if (trim($question['question']) === '') {
            continue;
        }

        $questions[] = $question;
    }

    return $questions ?: $fallback;
}

function jrc_get_quiz_data()
{
    $options = jrc_get_course_options();
    $fallback_questions = jrc_get_default_quiz_questions();
    $questions = jrc_parse_quiz_questions($options['quiz_questions'] ?? '', $fallback_questions);

    return [
        'title' => $options['quiz_title'] ?? 'Basic Programming Test',
        'subtitle' => $options['quiz_subtitle'] ?? '',
        'discount_note' => $options['quiz_discount_note'] ?? '',
        'time_limit' => isset($options['quiz_time_limit']) ? (int) $options['quiz_time_limit'] : 0,
        'pass_percent' => isset($options['quiz_pass_percent']) ? (int) $options['quiz_pass_percent'] : 60,
        'redirect_link' => $options['quiz_pass_redirect_link'] ?? '',
        'questions' => $questions,
    ];
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

function jrc_format_hero_title($title)
{
    $title = (string) $title;
    if (stripos($title, 'course-hero__title-accent') !== false) {
        return $title;
    }
    if (stripos($title, 'Job Transformation') === false) {
        return $title;
    }
    return preg_replace(
        '/(Job Transformation)/i',
        '<span class="course-hero__title-accent">$1</span>',
        $title,
        1
    );
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

    $course['hero'] = array_replace_recursive($defaults['hero'], [
        'organization' => $options['hero_organization'],
        'logo' => $options['hero_logo'],
        'title' => $options['hero_title'],
        'subtitle' => $options['hero_subtitle'],
        'note' => $options['hero_note'],
        'card_title' => $options['hero_card_title'] ?: 'Quick Info',
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
    ]);

    $course['quiz_cta'] = [
        'label' => $options['quiz_cta_label'],
        'link' => $options['quiz_cta_link'],
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

    $course['mentors'] = array_replace_recursive($defaults['mentors'], [
        'enabled' => !empty($options['mentors_enabled']),
        'title' => $options['mentors_title'],
        'subtitle' => $options['mentors_subtitle'],
        'items' => jrc_parse_mentor_items($options['mentor_items'], $defaults['mentors']['items']),
    ]);

    $course['faq'] = jrc_faq_lines_to_array($options['faq_items'], $defaults['faq']);
    $course['faq_title'] = $options['faq_title'];
    $course['final_cta'] = [
        'title' => $options['final_title'],
        'subtitle' => $options['final_subtitle'],
    ];

    $course['whatsapp'] = [
        'group_link' => $options['whatsapp_group_link'],
        'number' => $options['whatsapp_number'],
        'note' => $options['whatsapp_note'],
        'group_label' => $options['whatsapp_group_label'],
        'contact_label' => $options['whatsapp_contact_label'],
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
    $course['hero']['card_display'] = array_filter([
        'শুরু' => $start_date,
        'ক্লাস শিডিউল' => $options['details_days'],
        'ডিউরেশন' => $options['details_duration'],
        'সিট' => $seat_limit_total,
        'রেগুলার ফি' => $options['fee_standard'],
        'Discounted Price' => $options['fee_early'],
        'Mode' => $options['hero_card_mode'],
    ], function ($value) {
        return trim((string) $value) !== '';
    });

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
    $sanitized = jrc_get_course_options();
    foreach ((array) $input as $key => $value) {
        if (str_contains($key, '_link')) {
            $sanitized[$key] = jrc_sanitize_course_link($value);
            continue;
        }
        if ($key === 'hero_title') {
            $sanitized[$key] = jrc_sanitize_hero_title($value);
            continue;
        }
        if ($key === 'hero_logo' || $key === 'header_brand_logo') {
            $sanitized[$key] = esc_url_raw($value);
            continue;
        }
        $sanitized[$key] = sanitize_textarea_field($value);
    }

    $seat_limit_total = $sanitized['hero_card_seat_limit'] ?? '';
    if (jrc_is_placeholder_value($seat_limit_total) || $seat_limit_total === '') {
        $seat_limit_total = $sanitized['details_batch'] ?? '';
    }
    $sanitized['remaining_seats'] = (string) jrc_get_remaining_seats($seat_limit_total, $sanitized['booked_seats'] ?? 0);

    return $sanitized;
}

function jrc_sanitize_hero_title($value)
{
    $allowed = [
        'span' => [
            'class' => true,
        ],
        'strong' => [],
        'em' => [],
        'br' => [],
    ];
    return wp_kses($value, $allowed);
}

add_action('admin_menu', function () {
    if (!jrc_current_user_can_manage_course()) {
        return;
    }

    $menu_cap = jrc_get_management_capability();

    add_menu_page(
        'Job Ready Course',
        'Job Ready Course',
        $menu_cap,
        'jrc-options',
        'jrc_render_options_page',
        'dashicons-welcome-learn-more',
        62
    );
    add_submenu_page(
        'jrc-options',
        'Job Ready Course Options',
        'Options',
        $menu_cap,
        'jrc-options',
        'jrc_render_options_page'
    );
    add_submenu_page(
        'jrc-options',
        'Quick Info',
        'Quick Info',
        $menu_cap,
        'jrc-quick-info',
        'jrc_render_quick_info_page'
    );
    add_submenu_page(
        'jrc-options',
        'Basic Test Submissions',
        'Basic Test Submissions',
        $menu_cap,
        'edit.php?post_type=jrc_quiz_attempt'
    );

    add_submenu_page(
        'index.php',
        'Job Ready Course',
        'Job Ready Course',
        $menu_cap,
        'jrc-dashboard',
        'jrc_render_dashboard_page'
    );
    add_submenu_page(
        'index.php',
        'Job Ready Course Options',
        'Job Ready Options',
        $menu_cap,
        'jrc-dashboard-options',
        'jrc_render_dashboard_options_page'
    );
});

add_action('admin_init', function () {
    register_setting('jrc_options_group', 'jrc_options', [
        'sanitize_callback' => 'jrc_sanitize_options',
    ]);
});

add_action('wp_dashboard_setup', function () {
    if (!jrc_current_user_can_manage_course()) {
        return;
    }
    wp_add_dashboard_widget(
        'jrc_dashboard_widget',
        'Job Ready Course',
        function () {
            $options_url = admin_url('index.php?page=jrc-dashboard-options');
            $quick_info_url = admin_url('admin.php?page=jrc-quick-info');
            $applications_url = admin_url('edit.php?post_type=jrc_application');
            $quiz_url = admin_url('edit.php?post_type=jrc_quiz_attempt');
            ?>
            <div style="display:grid; gap:10px;">
                <p><strong>Quick Links</strong></p>
                <a href="<?php echo esc_url($options_url); ?>">Open Job Ready Options</a>
                <a href="<?php echo esc_url($quick_info_url); ?>">Edit Quick Info Panel</a>
                <a href="<?php echo esc_url($applications_url); ?>">View Applications</a>
                <a href="<?php echo esc_url($quiz_url); ?>">View Basic Test Submissions</a>
            </div>
            <?php
        },
        null,
        null,
        'normal',
        'high'
    );
}, 999);

add_filter('get_user_option_metaboxhidden_dashboard', function ($hidden) {
    if (!is_array($hidden)) {
        return $hidden;
    }
    return array_values(array_diff($hidden, ['jrc_dashboard_widget']));
});

function jrc_render_options_page()
{
    if (!jrc_current_user_can_manage_course()) {
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
                <tr><th scope="row">Hero Organization</th><td><input class="regular-text" type="text" name="jrc_options[hero_organization]" value="<?php echo esc_attr($options['hero_organization']); ?>"></td></tr>
                <tr><th scope="row">Hero Logo URL</th><td><input class="regular-text" type="url" name="jrc_options[hero_logo]" value="<?php echo esc_url($options['hero_logo']); ?>"><p class="description">Upload via Customizer for best results.</p></td></tr>
                <tr><th scope="row">Header Brand Title (Job Ready)</th><td><input class="regular-text" type="text" name="jrc_options[header_brand_title]" value="<?php echo esc_attr($options['header_brand_title']); ?>"></td></tr>
                <tr><th scope="row">Header Brand Tagline (Job Ready)</th><td><input class="regular-text" type="text" name="jrc_options[header_brand_tagline]" value="<?php echo esc_attr($options['header_brand_tagline']); ?>"></td></tr>
                <tr><th scope="row">Header Brand Logo URL (Job Ready)</th><td><input class="regular-text" type="url" name="jrc_options[header_brand_logo]" value="<?php echo esc_url($options['header_brand_logo']); ?>"><p class="description">Upload via Customizer for best results.</p></td></tr>
                <tr><th scope="row">Enroll Button Label</th><td><input class="regular-text" type="text" name="jrc_options[cta_apply_label]" value="<?php echo esc_attr($options['cta_apply_label']); ?>"></td></tr>
                <tr><th scope="row">Enroll Button Link</th><td><input class="regular-text" type="url" name="jrc_options[cta_apply_link]" value="<?php echo esc_url($options['cta_apply_link']); ?>"></td></tr>
                <tr><th scope="row">Basic Test Button Label</th><td><input class="regular-text" type="text" name="jrc_options[quiz_cta_label]" value="<?php echo esc_attr($options['quiz_cta_label']); ?>"></td></tr>
                <tr><th scope="row">Basic Test Button Link</th><td><input class="regular-text" type="url" name="jrc_options[quiz_cta_link]" value="<?php echo esc_url($options['quiz_cta_link']); ?>"></td></tr>
                <?php jrc_render_quick_info_option_rows($options, $remaining_seats); ?>

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
                <tr><th scope="row">Original Course Fee</th><td><input class="regular-text" type="text" name="jrc_options[fee_standard]" value="<?php echo esc_attr($options['fee_standard']); ?>"></td></tr>
                <tr><th scope="row">Discounted Price</th><td><input class="regular-text" type="text" name="jrc_options[fee_early]" value="<?php echo esc_attr($options['fee_early']); ?>"></td></tr>
                <tr><th scope="row">Installment Line</th><td><input class="regular-text" type="text" name="jrc_options[fee_installment]" value="<?php echo esc_attr($options['fee_installment']); ?>"></td></tr>
                <tr><th scope="row">Fee Note (extra)</th><td><textarea class="large-text" rows="2" name="jrc_options[fee_note]"><?php echo esc_textarea($options['fee_note']); ?></textarea></td></tr>
                <tr><th scope="row">Discount Note</th><td><input class="regular-text" type="text" name="jrc_options[early_bird_deadline]" value="<?php echo esc_attr($options['early_bird_deadline']); ?>"></td></tr>
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

                <tr><th scope="row" colspan="2"><h2>WhatsApp</h2></th></tr>
                <tr><th scope="row">WhatsApp Group Link</th><td><input class="regular-text" type="url" name="jrc_options[whatsapp_group_link]" value="<?php echo esc_url($options['whatsapp_group_link']); ?>"></td></tr>
                <tr><th scope="row">WhatsApp Number</th><td><input class="regular-text" type="text" name="jrc_options[whatsapp_number]" value="<?php echo esc_attr($options['whatsapp_number']); ?>"></td></tr>
                <tr><th scope="row">WhatsApp Note</th><td><textarea class="large-text" rows="2" name="jrc_options[whatsapp_note]"><?php echo esc_textarea($options['whatsapp_note']); ?></textarea></td></tr>
                <tr><th scope="row">WhatsApp Group Label</th><td><input class="regular-text" type="text" name="jrc_options[whatsapp_group_label]" value="<?php echo esc_attr($options['whatsapp_group_label']); ?>"></td></tr>
                <tr><th scope="row">WhatsApp Contact Label</th><td><input class="regular-text" type="text" name="jrc_options[whatsapp_contact_label]" value="<?php echo esc_attr($options['whatsapp_contact_label']); ?>"></td></tr>

                <tr><th scope="row" colspan="2"><h2>Basic Test</h2></th></tr>
                <tr><th scope="row">Quiz Title</th><td><input class="regular-text" type="text" name="jrc_options[quiz_title]" value="<?php echo esc_attr($options['quiz_title']); ?>"></td></tr>
                <tr><th scope="row">Quiz Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[quiz_subtitle]"><?php echo esc_textarea($options['quiz_subtitle']); ?></textarea></td></tr>
                <tr><th scope="row">Test Note</th><td><input class="regular-text" type="text" name="jrc_options[quiz_discount_note]" value="<?php echo esc_attr($options['quiz_discount_note']); ?>"></td></tr>
                <tr><th scope="row">Time Limit (minutes)</th><td><input class="small-text" type="number" min="0" name="jrc_options[quiz_time_limit]" value="<?php echo esc_attr($options['quiz_time_limit']); ?>"></td></tr>
                <tr><th scope="row">Pass Percent</th><td><input class="small-text" type="number" min="0" max="100" name="jrc_options[quiz_pass_percent]" value="<?php echo esc_attr($options['quiz_pass_percent']); ?>"></td></tr>
                <tr><th scope="row">After Test Redirect Link</th><td><input class="regular-text" type="url" name="jrc_options[quiz_pass_redirect_link]" value="<?php echo esc_url($options['quiz_pass_redirect_link']); ?>"></td></tr>
                <tr><th scope="row">Quiz Questions</th>
                    <td>
                        <?php
                        $quiz_questions = jrc_parse_quiz_questions($options['quiz_questions'] ?? '', jrc_get_default_quiz_questions());
                        $quiz_questions_json = wp_json_encode($quiz_questions);
                        $quiz_default_json = wp_json_encode(jrc_get_default_quiz_questions());
                        ?>
                        <div id="jrc-quiz-builder" style="display:grid; gap:16px;"></div>
                        <p>
                            <button type="button" class="button" id="jrc-quiz-add">Add Question</button>
                            <button type="button" class="button" id="jrc-quiz-reset">Reset to Default</button>
                        </p>
                        <textarea id="jrc-quiz-questions" name="jrc_options[quiz_questions]" class="large-text code" rows="6" style="display:none;"><?php echo esc_textarea($quiz_questions_json); ?></textarea>
                        <script>
                            (function () {
                                var builder = document.getElementById('jrc-quiz-builder');
                                var textarea = document.getElementById('jrc-quiz-questions');
                                var addBtn = document.getElementById('jrc-quiz-add');
                                var resetBtn = document.getElementById('jrc-quiz-reset');
                                if (!builder || !textarea || !addBtn) {
                                    return;
                                }

                                var defaults = <?php echo $quiz_default_json; ?>;
                                var questions = [];
                                try {
                                    questions = JSON.parse(textarea.value || '[]');
                                } catch (e) {
                                    questions = [];
                                }
                                if (!Array.isArray(questions) || !questions.length) {
                                    questions = defaults.slice();
                                }

                                var languages = ['C', 'JavaScript', 'Dart'];
                                var types = [
                                    { value: 'mcq', label: 'MCQ' },
                                    { value: 'short', label: 'Short' },
                                    { value: 'output', label: 'Output' }
                                ];

                                function serialize() {
                                    textarea.value = JSON.stringify(questions);
                                }

                                function render() {
                                    builder.innerHTML = '';
                                    questions.forEach(function (q, index) {
                                        var row = document.createElement('div');
                                        row.className = 'jrc-quiz-row';
                                        row.style.border = '1px solid #ccd0d4';
                                        row.style.borderRadius = '8px';
                                        row.style.padding = '12px';
                                        row.style.background = '#fff';

                                        var optionsValue = Array.isArray(q.options) ? q.options.join(' | ') : '';

                                        row.innerHTML =
                                            '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">' +
                                            '<strong>Question ' + (index + 1) + '</strong>' +
                                            '<button type="button" class="button-link-delete" data-action="remove" data-index="' + index + '">Remove</button>' +
                                            '</div>' +
                                            '<div style="display:grid;gap:10px;margin-top:10px;">' +
                                            '<label>Language ' + buildSelect('language', languages, q.language || 'C', index) + '</label>' +
                                            '<label>Type ' + buildSelect('type', types, q.type || 'mcq', index) + '</label>' +
                                            '<label>Points <input type="number" min="1" value="' + (q.points || 1) + '" data-field="points" data-index="' + index + '"></label>' +
                                            '<label>Question<textarea rows="2" data-field="question" data-index="' + index + '" style="width:100%;">' + escapeHtml(q.question || '') + '</textarea></label>' +
                                            '<label>Options (for MCQ, use | )<input type="text" data-field="options" data-index="' + index + '" value="' + escapeAttr(optionsValue) + '"></label>' +
                                            '<label>Answer (use | for multiple)<input type="text" data-field="answer" data-index="' + index + '" value="' + escapeAttr(q.answer || '') + '"></label>' +
                                            '</div>';

                                        builder.appendChild(row);
                                    });
                                }

                                function buildSelect(field, items, selected, index) {
                                    var html = '<select data-field="' + field + '" data-index="' + index + '">';
                                    items.forEach(function (item) {
                                        var value = typeof item === 'string' ? item : item.value;
                                        var label = typeof item === 'string' ? item : item.label;
                                        var isSelected = value === selected ? ' selected' : '';
                                        html += '<option value="' + escapeAttr(value) + '"' + isSelected + '>' + escapeHtml(label) + '</option>';
                                    });
                                    html += '</select>';
                                    return html;
                                }

                                function escapeHtml(text) {
                                    return String(text || '').replace(/[&<>"]/g, function (char) {
                                        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char];
                                    });
                                }

                                function escapeAttr(text) {
                                    return String(text || '').replace(/"/g, '&quot;');
                                }

                                builder.addEventListener('input', function (event) {
                                    var target = event.target;
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    var field = target.getAttribute('data-field');
                                    if (Number.isNaN(index) || !field || !questions[index]) {
                                        return;
                                    }

                                    if (field === 'options') {
                                        questions[index].options = target.value
                                            .split('|')
                                            .map(function (item) { return item.trim(); })
                                            .filter(Boolean);
                                    } else if (field === 'points') {
                                        questions[index].points = parseInt(target.value, 10) || 1;
                                    } else {
                                        questions[index][field] = target.value;
                                    }

                                    serialize();
                                });

                                builder.addEventListener('change', function (event) {
                                    var target = event.target;
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    var field = target.getAttribute('data-field');
                                    if (Number.isNaN(index) || !field || !questions[index]) {
                                        return;
                                    }
                                    questions[index][field] = target.value;
                                    serialize();
                                });

                                builder.addEventListener('click', function (event) {
                                    var target = event.target;
                                    if (target.getAttribute('data-action') !== 'remove') {
                                        return;
                                    }
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    if (!Number.isNaN(index)) {
                                        questions.splice(index, 1);
                                        render();
                                        serialize();
                                    }
                                });

                                addBtn.addEventListener('click', function () {
                                    questions.push({
                                        language: 'C',
                                        type: 'mcq',
                                        question: '',
                                        options: [],
                                        answer: '',
                                        points: 1
                                    });
                                    render();
                                    serialize();
                                });

                                if (resetBtn) {
                                    resetBtn.addEventListener('click', function () {
                                        questions = defaults.slice();
                                        render();
                                        serialize();
                                    });
                                }

                                render();
                                serialize();
                            })();
                        </script>
                    </td>
                </tr>

                <tr>
                    <th scope="row">Show Mentor Panel</th>
                    <td>
                        <input type="hidden" name="jrc_options[mentors_enabled]" value="0">
                        <label>
                            <input type="checkbox" name="jrc_options[mentors_enabled]" value="1" <?php checked(!empty($options['mentors_enabled'])); ?>>
                            Enable mentors section
                        </label>
                    </td>
                </tr>
                <tr><th scope="row">Mentor Panel Title</th><td><input class="regular-text" type="text" name="jrc_options[mentors_title]" value="<?php echo esc_attr($options['mentors_title']); ?>"></td></tr>
                <tr><th scope="row">Mentor Panel Subtitle</th><td><textarea class="large-text" rows="2" name="jrc_options[mentors_subtitle]"><?php echo esc_textarea($options['mentors_subtitle']); ?></textarea></td></tr>
                <tr>
                    <th scope="row">Mentor Items</th>
                    <td>
                        <div id="jrc-mentor-builder" style="display:grid; gap:16px;"></div>
                        <p>
                            <button type="button" class="button" id="jrc-mentor-add">Add Mentor</button>
                        </p>
                        <textarea id="jrc-mentor-items" name="jrc_options[mentor_items]" class="large-text code" rows="6" style="display:none;"><?php echo esc_textarea($options['mentor_items']); ?></textarea>
                        <p class="description">Stored as: Name | Role | Experience | Specialization | Real Projects | LinkedIn | GitHub | Message | Enabled (1/0)</p>
                        <script>
                            (function () {
                                var builder = document.getElementById('jrc-mentor-builder');
                                var textarea = document.getElementById('jrc-mentor-items');
                                var addBtn = document.getElementById('jrc-mentor-add');
                                if (!builder || !textarea || !addBtn) {
                                    return;
                                }

                                function normalize(value) {
                                    return String(value || '').replace(/\r?\n/g, ' ').trim();
                                }

                                function parseEnabled(value) {
                                    var key = String(value || '').trim().toLowerCase();
                                    var map = {
                                        '1': true,
                                        'true': true,
                                        'yes': true,
                                        'on': true,
                                        'enabled': true,
                                        '0': false,
                                        'false': false,
                                        'no': false,
                                        'off': false,
                                        'disabled': false
                                    };
                                    return Object.prototype.hasOwnProperty.call(map, key) ? map[key] : null;
                                }

                                function parseLine(line) {
                                    var parts = String(line || '')
                                        .split('|')
                                        .map(function (item) { return item.trim(); })
                                        .filter(function (item, index, arr) { return !(item === '' && index === arr.length - 1); });

                                    var enabled = true;
                                    if (parts.length > 8) {
                                        var parsed = parseEnabled(parts[parts.length - 1]);
                                        if (parsed !== null) {
                                            enabled = parsed;
                                            parts.pop();
                                        }
                                    }

                                    var message = '';
                                    if (parts.length > 7) {
                                        message = parts.slice(7).join(' | ');
                                    }

                                    return {
                                        enabled: enabled,
                                        name: parts[0] || '',
                                        role: parts[1] || '',
                                        experience: parts[2] || '',
                                        specialization: parts[3] || '',
                                        projects: parts[4] || '',
                                        linkedin: parts[5] || '',
                                        github: parts[6] || '',
                                        message: message
                                    };
                                }

                                function parseLines(text) {
                                    return String(text || '')
                                        .split(/\r?\n/)
                                        .map(function (line) { return line.trim(); })
                                        .filter(Boolean)
                                        .map(parseLine);
                                }

                                var mentors = parseLines(textarea.value);

                                function isEmpty(mentor) {
                                    return !normalize(mentor.name)
                                        && !normalize(mentor.role)
                                        && !normalize(mentor.experience)
                                        && !normalize(mentor.specialization)
                                        && !normalize(mentor.projects)
                                        && !normalize(mentor.linkedin)
                                        && !normalize(mentor.github)
                                        && !normalize(mentor.message);
                                }

                                function serialize() {
                                    var lines = mentors
                                        .filter(function (mentor) { return !isEmpty(mentor); })
                                        .map(function (mentor) {
                                            var parts = [
                                                normalize(mentor.name),
                                                normalize(mentor.role),
                                                normalize(mentor.experience),
                                                normalize(mentor.specialization),
                                                normalize(mentor.projects),
                                                normalize(mentor.linkedin),
                                                normalize(mentor.github),
                                                normalize(mentor.message)
                                            ];
                                            return parts.join(' | ') + ' | ' + (mentor.enabled ? '1' : '0');
                                        });
                                    textarea.value = lines.join('\n');
                                }

                                function escapeHtml(text) {
                                    return String(text || '').replace(/[&<>"]/g, function (char) {
                                        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[char];
                                    });
                                }

                                function escapeAttr(text) {
                                    return String(text || '').replace(/"/g, '&quot;');
                                }

                                function render() {
                                    builder.innerHTML = '';
                                    mentors.forEach(function (mentor, index) {
                                        var row = document.createElement('div');
                                        row.style.border = '1px solid #ccd0d4';
                                        row.style.borderRadius = '8px';
                                        row.style.padding = '12px';
                                        row.style.background = '#fff';

                                        row.innerHTML =
                                            '<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">' +
                                            '<strong>Mentor ' + (index + 1) + '</strong>' +
                                            '<div style="display:flex;align-items:center;gap:12px;">' +
                                            '<label style="display:flex;align-items:center;gap:6px;">' +
                                            '<input type="checkbox" data-field="enabled" data-index="' + index + '"' + (mentor.enabled ? ' checked' : '') + '>' +
                                            'Enabled</label>' +
                                            '<button type="button" class="button-link-delete" data-action="remove" data-index="' + index + '">Remove</button>' +
                                            '</div>' +
                                            '</div>' +
                                            '<div style="display:grid;gap:10px;margin-top:10px;">' +
                                            '<label>Name <input type="text" data-field="name" data-index="' + index + '" value="' + escapeAttr(mentor.name) + '"></label>' +
                                            '<label>Role <input type="text" data-field="role" data-index="' + index + '" value="' + escapeAttr(mentor.role) + '"></label>' +
                                            '<label>Experience <input type="text" data-field="experience" data-index="' + index + '" value="' + escapeAttr(mentor.experience) + '"></label>' +
                                            '<label>Specialization <input type="text" data-field="specialization" data-index="' + index + '" value="' + escapeAttr(mentor.specialization) + '"></label>' +
                                            '<label>Real Projects <input type="text" data-field="projects" data-index="' + index + '" value="' + escapeAttr(mentor.projects) + '"></label>' +
                                            '<label>LinkedIn <input type="text" data-field="linkedin" data-index="' + index + '" value="' + escapeAttr(mentor.linkedin) + '"></label>' +
                                            '<label>GitHub <input type="text" data-field="github" data-index="' + index + '" value="' + escapeAttr(mentor.github) + '"></label>' +
                                            '<label>Message<textarea rows="2" data-field="message" data-index="' + index + '" style="width:100%;">' + escapeHtml(mentor.message) + '</textarea></label>' +
                                            '</div>';

                                        builder.appendChild(row);
                                    });
                                }

                                builder.addEventListener('input', function (event) {
                                    var target = event.target;
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    var field = target.getAttribute('data-field');
                                    if (Number.isNaN(index) || !field || !mentors[index]) {
                                        return;
                                    }
                                    if (field === 'enabled') {
                                        mentors[index].enabled = target.checked;
                                    } else {
                                        mentors[index][field] = target.value;
                                    }
                                    serialize();
                                });

                                builder.addEventListener('change', function (event) {
                                    var target = event.target;
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    var field = target.getAttribute('data-field');
                                    if (Number.isNaN(index) || !field || !mentors[index]) {
                                        return;
                                    }
                                    if (field === 'enabled') {
                                        mentors[index].enabled = target.checked;
                                        serialize();
                                    }
                                });

                                builder.addEventListener('click', function (event) {
                                    var target = event.target;
                                    if (target.getAttribute('data-action') !== 'remove') {
                                        return;
                                    }
                                    var index = parseInt(target.getAttribute('data-index'), 10);
                                    if (!Number.isNaN(index)) {
                                        mentors.splice(index, 1);
                                        render();
                                        serialize();
                                    }
                                });

                                addBtn.addEventListener('click', function () {
                                    mentors.push({
                                        enabled: true,
                                        name: '',
                                        role: '',
                                        experience: '',
                                        specialization: '',
                                        projects: '',
                                        linkedin: '',
                                        github: '',
                                        message: ''
                                    });
                                    render();
                                    serialize();
                                });

                                render();
                                serialize();
                            })();
                        </script>
                    </td>
                </tr>
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

function jrc_render_quick_info_option_rows(array $options, $remaining_seats)
{
    ?>
    <tr><th scope="row">Quick Info Title</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_title]" value="<?php echo esc_attr($options['hero_card_title'] ?? 'Quick Info'); ?>"></td></tr>
    <tr><th scope="row">Hero Card: Start Date</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_start_date]" value="<?php echo esc_attr($options['hero_card_start_date']); ?>"></td></tr>
    <tr><th scope="row">Hero Card: Seat Limit</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_seat_limit]" value="<?php echo esc_attr($options['hero_card_seat_limit']); ?>"></td></tr>
    <tr><th scope="row">Reserved Seats (Pre-booking)</th><td><input class="regular-text" type="text" name="jrc_options[booked_seats]" value="<?php echo esc_attr($options['booked_seats']); ?>"><p class="description">Remaining seats auto-calculate from seat limit minus reserved seats.</p></td></tr>
    <tr><th scope="row">Remaining Seats</th><td><input class="regular-text" type="text" value="<?php echo esc_attr($remaining_seats); ?>" readonly></td></tr>
    <tr><th scope="row">Hero Card: Fee From</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_fee_from]" value="<?php echo esc_attr($options['hero_card_fee_from']); ?>"></td></tr>
    <tr><th scope="row">Hero Card: Mode</th><td><input class="regular-text" type="text" name="jrc_options[hero_card_mode]" value="<?php echo esc_attr($options['hero_card_mode']); ?>"></td></tr>
    <tr><th scope="row">Original Course Fee</th><td><input class="regular-text" type="text" name="jrc_options[fee_standard]" value="<?php echo esc_attr($options['fee_standard']); ?>"></td></tr>
    <tr><th scope="row">Discounted Price</th><td><input class="regular-text" type="text" name="jrc_options[fee_early]" value="<?php echo esc_attr($options['fee_early']); ?>"><p class="description">This value is controlled by admin and also appears in the Quick Info card and pricing section.</p></td></tr>
    <?php
}

function jrc_render_quick_info_page()
{
    if (!jrc_current_user_can_manage_course()) {
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
        <h1>Quick Info Panel</h1>
        <p>Edit the hero card content and pricing shown on the Job Ready Course page.</p>
        <form method="post" action="options.php">
            <?php settings_fields('jrc_options_group'); ?>
            <table class="form-table" role="presentation">
                <?php jrc_render_quick_info_option_rows($options, $remaining_seats); ?>
            </table>
            <?php submit_button('Save Quick Info'); ?>
        </form>
    </div>
    <?php
}

function jrc_render_dashboard_options_page()
{
    jrc_render_options_page();
}

function jrc_render_dashboard_page()
{
    if (!jrc_current_user_can_manage_course()) {
        wp_die('Unauthorized', 403);
    }
    $options_url = admin_url('index.php?page=jrc-dashboard-options');
    $quick_info_url = admin_url('admin.php?page=jrc-quick-info');
    $applications_url = admin_url('edit.php?post_type=jrc_application');
    $quiz_url = admin_url('edit.php?post_type=jrc_quiz_attempt');
    ?>
    <div class="wrap">
        <h1>Job Ready Course</h1>
        <p>Quick links and status shortcuts for the course management.</p>
        <div style="display:grid; gap:12px; max-width:520px;">
            <?php if (jrc_current_user_can_manage_course()) : ?>
                <a class="button button-primary" href="<?php echo esc_url($options_url); ?>">Open Job Ready Options</a>
                <a class="button" href="<?php echo esc_url($quick_info_url); ?>">Edit Quick Info Panel</a>
                <a class="button" href="<?php echo esc_url($applications_url); ?>">View Applications</a>
                <a class="button" href="<?php echo esc_url($quiz_url); ?>">View Basic Test Submissions</a>
            <?php else : ?>
                <p>You do not have permission to manage course options.</p>
            <?php endif; ?>
        </div>
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
<ul class="course-hero__list"><li>Start Date: [START_DATE]</li><li>Batch Size: [SEAT_LIMIT]</li><li>Discounted Price: ৳ 12,000</li><li>Mode: Online Live</li></ul>
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
<p>Original Course Fee: ৳ 15,000</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Discounted Price: ৳ 12,000</p>
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
<p>সার্টিফিকেট দেয় না চাকরি, দেয় স্কিল আর প্রজেক্ট। আমরা সার্টিফিকেট দিই না, কারণ বিশ্বাস করি ফলাফলই আপনার সবচেয়ে বড় পরিচয়।</p>
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

    if (khokan_submission_tripped_honeypot('khokan_contact_website')) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => 'Invalid submission detected.',
        ];
        return;
    }

    $rate_limit = khokan_rate_limit_request('khokan_contact_submit', 5, HOUR_IN_SECONDS);
    if (is_wp_error($rate_limit)) {
        $GLOBALS['khokan_contact_feedback'] = [
            'status' => 'error',
            'message' => $rate_limit->get_error_message(),
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

    $sent = wp_mail($recipients, $subject, implode("\n", $body_lines), $headers);

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
