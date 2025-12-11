<?php
/**
 * Template Name: Job Ready Course
 * Description: Landing page for the Job-Ready Course.
 */

get_header();

$defaults = require __DIR__ . '/data/job-ready-course-content.php';
$course = function_exists('jrc_get_course_data')
    ? array_replace_recursive($defaults, jrc_get_course_data())
    : $defaults;
$ctas = array_values(array_filter($course['hero']['ctas'], function ($cta) {
    return !empty($cta['label']) && !empty($cta['link']);
}));
$primary_cta = $ctas[0] ?? null;
$quiz_cta = $course['quiz_cta'] ?? [];
$quiz_cta_label = trim((string) ($quiz_cta['label'] ?? ''));
$quiz_cta_link = trim((string) ($quiz_cta['link'] ?? ''));
$quiz_gate = $quiz_cta_link !== '';
if ($quiz_gate) {
    $ctas = array_map(function ($cta) use ($quiz_cta_link) {
        $cta['link'] = $quiz_cta_link;
        return $cta;
    }, $ctas);
    $primary_cta = $ctas[0] ?? null;
}
$show_quiz_cta = !$quiz_gate && $quiz_cta_label !== '' && $quiz_cta_link !== '';
$enroll_link = $quiz_gate ? $quiz_cta_link : ($course['enrollment']['website']['link'] ?? '');
$whatsapp = $course['whatsapp'] ?? [];
$whatsapp_group = $whatsapp['group_link'] ?? '';
$whatsapp_number = $whatsapp['number'] ?? '';
$whatsapp_note = $whatsapp['note'] ?? '';
$whatsapp_group_label = $whatsapp['group_label'] ?? 'Join WhatsApp Group';
$whatsapp_contact_label = $whatsapp['contact_label'] ?? 'WhatsApp Number';
$whatsapp_number_digits = preg_replace('/\D+/', '', $whatsapp_number);
$whatsapp_chat_link = $whatsapp_number_digits ? 'https://wa.me/' . $whatsapp_number_digits : '';
$has_whatsapp = $whatsapp_group || $whatsapp_number;
$whatsapp_gate = isset($_GET['seat']) && $_GET['seat'] === '1';
$show_whatsapp = $has_whatsapp;
$whatsapp_initially_visible = $has_whatsapp && $whatsapp_gate;
$seat_success_message = 'Thank you! Your enrollment request has been received. We will contact you soon.';
?>
<main class="course-page">
    <?php the_content(); ?>
    <section class="section course-hero">
        <div class="container course-hero__grid">
            <div class="course-hero__content">
                <?php if (!empty($course['hero']['sticker'])) : ?>
                    <?php $countdown_target = $course['hero']['countdown'] ?? ''; ?>
                    <div class="course-hero__sticker" <?php echo $countdown_target ? 'data-countdown-target="' . esc_attr($countdown_target) . '"' : ''; ?>>
                        <span class="course-hero__sticker-label"><?php echo esc_html($course['hero']['sticker']); ?></span>
                        <?php if ($countdown_target) : ?>
                            <span class="course-hero__countdown" aria-live="polite"></span>
                        <?php endif; ?>
                        <?php
                        $remaining_seats = $course['hero']['remaining_seats'] ?? '';
                        if ($remaining_seats === '' && !empty($course['hero']['card']['Batch Size'])) {
                            $remaining_seats = $course['hero']['card']['Batch Size'];
                        }
                        if ($remaining_seats !== '') :
                        ?>
                            <span class="course-hero__seats">Seats left: <?php echo esc_html($remaining_seats); ?></span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $hero_title = function_exists('jrc_format_hero_title') ? jrc_format_hero_title($course['hero']['title']) : $course['hero']['title']; ?>
                <h1 class="course-hero__title"><?php echo wp_kses_post($hero_title); ?></h1>
                <p class="course-hero__subtitle"><?php echo esc_html($course['hero']['subtitle']); ?></p>
                <?php if (!empty($course['hero']['note'])) : ?>
                    <p class="course-hero__note"><?php echo esc_html($course['hero']['note']); ?></p>
                <?php endif; ?>
                <?php if (!empty($course['hero']['microcopy'])) : ?>
                    <p class="course-hero__microcopy"><?php echo esc_html($course['hero']['microcopy']); ?></p>
                <?php endif; ?>
                <?php if ($quiz_gate) : ?>
                    <div class="course-highlight course-highlight--quiz">
                        <span class="course-highlight__label">Basic Test Required</span>
                        <p class="course-highlight__text">Enrollment করার আগে Basic Test দিতে হবে। 60%+ হলে coupon auto-apply হবে।</p>
                    </div>
                <?php endif; ?>
                <div class="course-hero__actions">
                    <?php foreach ($ctas as $cta) : ?>
                        <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endforeach; ?>
                    <?php if ($show_quiz_cta) : ?>
                        <a class="secondary-btn" href="<?php echo esc_attr($quiz_cta_link); ?>">
                            <?php echo esc_html($quiz_cta_label); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="course-hero__card">
                <h2 class="course-hero__card-title">Quick Info</h2>
                <ul class="course-hero__list">
                    <?php
                    $hero_card = $course['hero']['card_display'] ?? $course['hero']['card'];
                    ?>
                    <?php foreach ($hero_card as $label => $value) : ?>
                        <li>
                            <span><?php echo esc_html($label); ?></span>
                            <strong><?php echo esc_html($value); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="section course-section course-why">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['why']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['why']['subtitle']); ?></p>
            </div>
            <p class="section-subtitle"><?php echo esc_html($course['why']['content']); ?></p>
            <ul class="course-why__cards">
                <?php foreach ($course['why']['bullets'] as $index => $item) : ?>
                    <?php
                    $parts = array_map('trim', explode(':', $item, 2));
                    $label = $parts[0] ?? '';
                    $text = $parts[1] ?? '';
                    if ($text === '') {
                        $text = $item;
                    }
                    $card_class = 'course-why__card';
                    if ($index === 0) {
                        $card_class .= ' is-problem';
                    } elseif ($index === 1) {
                        $card_class .= ' is-solution';
                    } elseif ($index === 2) {
                        $card_class .= ' is-goal';
                    }
                    ?>
                    <li class="<?php echo esc_attr($card_class); ?>">
                        <?php if ($label !== '') : ?>
                            <span class="course-why__label">
                                <?php if ($index === 0) : ?>
                                    <span class="course-why__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M12 3l9 16H3L12 3z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                            <path d="M12 9v5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M12 17.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                                        </svg>
                                    </span>
                                <?php elseif ($index === 1) : ?>
                                    <span class="course-why__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <path d="M9 18h6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M10 22h4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M12 2a7 7 0 0 0-4 12.74V17a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-2.26A7 7 0 0 0 12 2z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"></path>
                                        </svg>
                                    </span>
                                <?php elseif ($index === 2) : ?>
                                    <span class="course-why__icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                            <circle cx="12" cy="12" r="8" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                                            <circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.8"></circle>
                                            <path d="M12 8v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M12 20v-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M8 12H4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                            <path d="M20 12h-4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"></path>
                                        </svg>
                                    </span>
                                <?php endif; ?>
                                <?php echo esc_html($label); ?>
                            </span>
                        <?php endif; ?>
                        <span class="course-why__text"><?php echo esc_html($text); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="course-cta">
                <div>
                    <p class="section-subtitle"><?php echo esc_html($course['why']['microcopy']); ?></p>
                </div>
                <?php if ($primary_cta) : ?>
                    <div class="course-cta__actions">
                        <a class="primary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                            <?php echo esc_html($course['why']['cta']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['before_after']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['before_after']['subtitle']); ?></p>
            </div>
            <div class="course-table">
                <?php foreach ($course['before_after']['rows'] as $row) : ?>
                    <div class="course-table__row">
                        <span><?php echo esc_html($row['before']); ?></span>
                        <strong><?php echo esc_html($row['after']); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-eligibility">
        <div class="container course-eligibility__grid">
            <div class="course-eligibility__col is-yes">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['audience']['title']); ?></h2>
                </div>
                <ul class="course-eligibility__list">
                    <?php foreach ($course['audience']['items'] as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="course-eligibility__col is-no">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['not_for']['title']); ?></h2>
                </div>
                <ul class="course-eligibility__list">
                    <?php foreach ($course['not_for']['items'] as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </section>

    <section class="section course-section course-structure">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['structure']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['structure']['subtitle']); ?></p>
            </div>
            <div class="course-grid">
                <?php foreach ($course['structure']['phases'] as $phase) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($phase['title']); ?></h3>
                        <?php if (!empty($phase['weeks'])) : ?>
                            <p class="section-subtitle"><?php echo esc_html($phase['weeks']); ?></p>
                        <?php endif; ?>
                        <ul class="list">
                            <?php foreach ($phase['items'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-tracks">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['tracks']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['tracks']['subtitle']); ?></p>
            </div>
            <div class="course-grid course-grid--two">
                <?php foreach ($course['tracks']['items'] as $track) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($track['title']); ?></h3>
                        <ul class="list">
                            <?php foreach ($track['items'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <?php if (!empty($track['cta']) && $primary_cta) : ?>
                            <div class="course-cta__actions">
                                <a class="secondary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                                    <?php echo esc_html($track['cta']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-learning">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['learning']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['learning']['subtitle']); ?></p>
            </div>
            <div class="course-grid">
                <?php foreach ($course['learning']['tracks'] as $track) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($track['title']); ?></h3>
                        <ul class="list">
                            <?php foreach ($track['items'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-ai">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['ai_usage']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['ai_usage']['subtitle']); ?></p>
            </div>
            <div class="course-grid course-grid--two">
                <div class="course-card">
                    <h3>Ethical Rules</h3>
                    <ul class="list">
                        <?php foreach ($course['ai_usage']['rules'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="course-card">
                    <h3>Good Use Examples</h3>
                    <ul class="list">
                        <?php foreach ($course['ai_usage']['good'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="course-divider"></div>
                    <h3>Bad Use (We Don’t Do This)</h3>
                    <ul class="list">
                        <?php foreach ($course['ai_usage']['bad'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="course-cta">
                <div>
                    <h3><?php echo esc_html($course['ai_usage']['cta']['title']); ?></h3>
                    <p class="section-subtitle"><?php echo esc_html($course['ai_usage']['cta']['subtitle']); ?></p>
                </div>
                <div class="course-cta__actions">
                    <?php foreach ($ctas as $cta) : ?>
                        <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section course-section course-projects">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['projects']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['projects']['subtitle']); ?></p>
            </div>
            <ul class="list">
                <?php foreach ($course['projects']['items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section course-section course-portfolio">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['portfolio_support']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['portfolio_support']['subtitle']); ?></p>
            </div>
            <?php if (!empty($course['portfolio_support']['highlight'])) : ?>
                <div class="course-highlight">
                    <span class="course-highlight__label">Key Outcome</span>
                    <p class="course-highlight__text">
                        <?php
                        echo wp_kses($course['portfolio_support']['highlight'], [
                            'span' => ['class' => true],
                        ]);
                        ?>
                    </p>
                </div>
            <?php endif; ?>
            <div class="course-card">
                <h3><?php echo esc_html($course['portfolio_support']['launch_title']); ?></h3>
                <ul class="list">
                    <?php foreach ($course['portfolio_support']['launch_items'] as $item) : ?>
                        <li><?php echo esc_html($item); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="course-grid course-grid--two">
                <div class="course-card">
                    <h3><?php echo esc_html($course['portfolio_support']['tech_title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['portfolio_support']['tech_items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="course-card">
                    <h3><?php echo esc_html($course['portfolio_support']['domain_title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['portfolio_support']['domain_items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="course-card">
                    <h3><?php echo esc_html($course['portfolio_support']['payment_title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['portfolio_support']['payment_items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="course-card">
                    <h3><?php echo esc_html($course['portfolio_support']['tools_title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['portfolio_support']['tools_items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <div class="course-cta">
                <div>
                    <h3><?php echo esc_html($course['portfolio_support']['interview_title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['portfolio_support']['interview_items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="section-subtitle"><?php echo esc_html($course['portfolio_support']['dates_line']); ?></p>
                </div>
                <?php if ($primary_cta) : ?>
                    <div class="course-cta__actions">
                        <a class="primary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                            <?php echo esc_html($course['portfolio_support']['cta_text']); ?>
                        </a>
                        <p class="section-subtitle"><?php echo esc_html($course['portfolio_support']['microcopy']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-interview">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['interview']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['interview']['subtitle']); ?></p>
            </div>
            <ul class="list">
                <?php foreach ($course['interview']['items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
            <?php if ($primary_cta) : ?>
                <div class="course-cta__actions">
                    <a class="secondary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                        <?php echo esc_html($course['interview']['cta']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php
    $mentors_enabled = !isset($course['mentors']['enabled']) || $course['mentors']['enabled'];
    $mentor_items = $course['mentors']['items'] ?? [];
    $mentor_items = array_values(array_filter($mentor_items, function ($mentor) {
        return !isset($mentor['enabled']) || $mentor['enabled'];
    }));
    $mentor_card = $course['mentor'] ?? [];
    $has_mentor_card = !empty($mentor_card) && (trim($mentor_card['title'] ?? '') !== '' || trim($mentor_card['bio'] ?? '') !== '');
    $has_mentor_items = !empty($mentor_items);
    ?>
    <?php if ($mentors_enabled && ($has_mentor_card || $has_mentor_items)) : ?>
        <section class="section course-section course-mentors">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['mentors']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['mentors']['subtitle']); ?></p>
                </div>
                <?php if ($has_mentor_card) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($mentor_card['title'] ?? ''); ?></h3>
                        <p class="section-subtitle"><?php echo esc_html($mentor_card['bio'] ?? ''); ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($has_mentor_items) : ?>
                    <div class="course-grid">
                        <?php foreach ($mentor_items as $mentor) : ?>
                    <?php
                    $mentor_name = $mentor['name'] ?? '';
                    $initial = '';
                    if ($mentor_name !== '') {
                        if (function_exists('mb_substr')) {
                            $initial = mb_substr($mentor_name, 0, 1);
                        } else {
                            $initial = substr($mentor_name, 0, 1);
                        }
                    }
                    $linkedin = $mentor['linkedin'] ?? '';
                    $github = $mentor['github'] ?? '';
                    if ($linkedin && !preg_match('~^https?://~', $linkedin)) {
                        $linkedin = 'https://' . $linkedin;
                    }
                    if ($github && !preg_match('~^https?://~', $github)) {
                        $github = 'https://' . $github;
                    }
                    ?>
                        <div class="course-card">
                            <div class="course-mentor__header">
                                <?php if ($initial !== '') : ?>
                                    <span class="course-mentor__avatar" aria-hidden="true"><?php echo esc_html($initial); ?></span>
                                <?php endif; ?>
                                <div>
                                    <h3 class="course-mentor__name"><?php echo esc_html($mentor['name']); ?></h3>
                                    <p class="course-mentor__role"><?php echo esc_html($mentor['role']); ?></p>
                                </div>
                            </div>
                            <ul class="course-mentor__list">
                                <?php
                                $mentor_fields = [
                                    'Experience' => $mentor['experience'],
                                    'Specialization' => $mentor['specialization'],
                                    'Real Projects' => $mentor['projects'],
                                ];
                                foreach ($mentor_fields as $label => $value) :
                                    if (empty($value)) {
                                        continue;
                                    }
                                    ?>
                                    <li>
                                        <span class="course-mentor__label"><?php echo esc_html($label); ?></span>
                                        <span class="course-mentor__value"><?php echo esc_html($value); ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                            <p class="course-mentor__message"><?php echo esc_html($mentor['message']); ?></p>
                            <?php if ($linkedin || $github) : ?>
                                <div class="course-mentor__links">
                                    <?php if ($linkedin) : ?>
                                        <a class="course-mentor__link" href="<?php echo esc_url($linkedin); ?>">
                                            <span class="course-mentor__link-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                                    <path d="M4.9 8.2h3.3v10.6H4.9zM6.5 4.5a1.9 1.9 0 1 1 0 3.8 1.9 1.9 0 0 1 0-3.8zM9.9 8.2h3.1v1.5h.1c.4-.8 1.5-1.7 3.1-1.7 3.3 0 3.9 2.2 3.9 5v5.8h-3.3v-5.1c0-1.2 0-2.8-1.7-2.8-1.7 0-2 1.3-2 2.7v5.2H9.9z" fill="currentColor"></path>
                                                </svg>
                                            </span>
                                            LinkedIn
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($github) : ?>
                                        <a class="course-mentor__link" href="<?php echo esc_url($github); ?>">
                                            <span class="course-mentor__link-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                                    <path d="M12 2.2a9.8 9.8 0 0 0-3.1 19.1c.5.1.7-.2.7-.5v-1.9c-2.8.6-3.4-1.2-3.4-1.2-.5-1.1-1.2-1.4-1.2-1.4-1-.7.1-.7.1-.7 1.1.1 1.7 1.2 1.7 1.2 1 .1.8 2.1 2.9 1.5.1-.7.4-1.2.7-1.5-2.2-.3-4.6-1.1-4.6-4.9 0-1.1.4-2 1.1-2.7-.1-.3-.5-1.3.1-2.7 0 0 .9-.3 2.8 1.1a9.5 9.5 0 0 1 5.1 0c1.9-1.4 2.8-1.1 2.8-1.1.6 1.4.2 2.4.1 2.7.7.7 1.1 1.6 1.1 2.7 0 3.8-2.4 4.6-4.6 4.9.4.3.8 1 .8 2.1v3.1c0 .3.2.6.7.5A9.8 9.8 0 0 0 12 2.2z" fill="currentColor"></path>
                                                </svg>
                                            </span>
                                            GitHub
                                        </a>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($primary_cta) : ?>
                    <div class="course-cta__actions">
                        <a class="secondary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                            <?php echo esc_html($course['mentors']['cta']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="section course-section course-timeline">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['timeline']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['timeline']['subtitle']); ?></p>
            </div>
            <ul class="list">
                <?php foreach ($course['timeline']['weeks'] as $week) : ?>
                    <li><?php echo esc_html($week); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section course-section course-details">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['details']['title']); ?></h2>
            </div>
            <ul class="list">
                <?php foreach ($course['details']['items'] as $label => $value) : ?>
                    <li><?php echo esc_html($label); ?>: <?php echo esc_html($value); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section course-section course-pricing">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['pricing']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['pricing']['subtitle']); ?></p>
            </div>
            <?php
            $pricing = $course['pricing'];
            if (!empty($course['fee'])) {
                $pricing['title'] = $course['fee']['title'] ?? $pricing['title'];
                $pricing['standard'] = $course['fee']['standard'] ?? $pricing['standard'];
                $pricing['early'] = $course['fee']['early'] ?? $pricing['early'];
                $pricing['installment'] = $course['fee']['installment'] ?? $pricing['installment'];
                $pricing['note'] = $course['fee']['note'] ?? $pricing['note'];
            }
            ?>
            <div class="course-price">
                <div class="course-price__item">
                    <span>Regular Fee</span>
                    <strong><?php echo esc_html($pricing['standard']); ?></strong>
                </div>
                <div class="course-price__item">
                    <span>Early Bird</span>
                    <strong><?php echo esc_html($pricing['early']); ?></strong>
                </div>
                <p class="section-subtitle"><?php echo esc_html($pricing['installment']); ?></p>
                <?php if (!empty($pricing['note'])) : ?>
                    <p class="section-subtitle"><?php echo esc_html($pricing['note']); ?></p>
                <?php endif; ?>
                <?php if ($primary_cta) : ?>
                    <div class="course-cta__actions">
                        <a class="primary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                            <?php echo esc_html($course['pricing']['cta']); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-enroll">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['enrollment']['title']); ?></h2>
                <p class="section-subtitle" id="jrc-enroll-subtitle" data-success="<?php echo esc_attr($seat_success_message); ?>">
                    <?php echo esc_html($course['enrollment']['subtitle']); ?>
                </p>
            </div>
            <div class="course-grid">
                <?php if ($show_whatsapp) : ?>
                    <div class="course-card" id="jrc-whatsapp-card" data-show="<?php echo $whatsapp_initially_visible ? '1' : '0'; ?>" style="<?php echo $whatsapp_initially_visible ? '' : 'display:none;'; ?>">
                        <div class="course-highlight course-highlight--seat">
                            <span class="course-highlight__label">Seat Request Received</span>
                            <p class="course-highlight__text"><?php echo esc_html($seat_success_message); ?></p>
                        </div>
                        <h3>WhatsApp Seat Booking</h3>
                        <?php if ($whatsapp_note) : ?>
                            <p class="section-subtitle"><?php echo esc_html($whatsapp_note); ?></p>
                        <?php endif; ?>
                        <ul class="list">
                            <?php if ($whatsapp_group) : ?>
                                <li><?php echo esc_html($whatsapp_group_label); ?></li>
                            <?php endif; ?>
                            <?php if ($whatsapp_number) : ?>
                                <li><?php echo esc_html($whatsapp_contact_label); ?>: <?php echo esc_html($whatsapp_number); ?></li>
                            <?php endif; ?>
                        </ul>
                        <div class="course-cta__actions">
                            <?php if ($whatsapp_group) : ?>
                                <a class="primary-btn" href="<?php echo esc_url($whatsapp_group); ?>">
                                    <?php echo esc_html($whatsapp_group_label); ?>
                                </a>
                            <?php endif; ?>
                            <?php if ($whatsapp_chat_link) : ?>
                                <a class="secondary-btn" href="<?php echo esc_url($whatsapp_chat_link); ?>">
                                    <?php echo esc_html($whatsapp_contact_label); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <div class="course-card">
                    <h3><?php echo esc_html($course['enrollment']['website']['title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['enrollment']['website']['fields'] as $field) : ?>
                            <li><?php echo esc_html($field); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment']['website']['success']); ?></p>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment']['website']['privacy']); ?></p>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment']['website']['urgency']); ?></p>
                    <?php if (!empty($enroll_link)) : ?>
                        <div class="course-cta__actions">
                            <a class="secondary-btn" href="<?php echo esc_attr($enroll_link); ?>">
                                <?php echo esc_html($course['enrollment']['website']['button']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section course-section course-faqs">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['faq_title']); ?></h2>
            </div>
            <div class="course-faq">
                <?php foreach ($course['faq'] as $item) : ?>
                    <div class="course-faq__item">
                        <h3><?php echo esc_html($item['q']); ?></h3>
                        <p><?php echo esc_html($item['a']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="section course-section course-final">
        <div class="container course-final__inner">
            <div>
                <h2><?php echo esc_html($course['final_cta']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['final_cta']['subtitle']); ?></p>
                <?php if (!empty($course['final_cta']['microcopy'])) : ?>
                    <p class="section-subtitle"><?php echo esc_html($course['final_cta']['microcopy']); ?></p>
                <?php endif; ?>
            </div>
            <div class="course-cta__actions">
                <?php foreach ($ctas as $cta) : ?>
                    <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                        <?php echo esc_html($cta['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>
<script>
    (function () {
        var stickers = document.querySelectorAll('.course-hero__sticker[data-countdown-target]');
        if (!stickers.length) {
            return;
        }

        function formatCountdown(diffMs) {
            if (diffMs <= 0) {
                return '0d 0h 0m 0s';
            }
            var totalSeconds = Math.floor(diffMs / 1000);
            var days = Math.floor(totalSeconds / 86400);
            var hours = Math.floor((totalSeconds % 86400) / 3600);
            var mins = Math.floor((totalSeconds % 3600) / 60);
            var secs = totalSeconds % 60;
            return days + 'd ' + hours + 'h ' + mins + 'm ' + secs + 's';
        }

        stickers.forEach(function (sticker) {
            var targetStr = sticker.getAttribute('data-countdown-target') || '';
            var target = targetStr ? new Date(targetStr) : null;
            var output = sticker.querySelector('.course-hero__countdown');
            if (!target || isNaN(target.getTime()) || !output) {
                return;
            }

            function tick() {
                var now = new Date();
                var diff = target.getTime() - now.getTime();
                if (diff <= 0) {
                    diff = 0;
                    sticker.classList.add('is-ended');
                }
                output.textContent = formatCountdown(diff);
            }

            tick();
            var timer = setInterval(function () {
                tick();
                if (sticker.classList.contains('is-ended')) {
                    clearInterval(timer);
                }
            }, 1000);
        });
    })();
</script>
<script>
    (function () {
        var card = document.getElementById('jrc-whatsapp-card');
        var subtitle = document.getElementById('jrc-enroll-subtitle');
        if (!card) {
            return;
        }

        function applySuccessMessage() {
            if (!subtitle) {
                return;
            }
            var message = subtitle.getAttribute('data-success');
            if (message) {
                subtitle.textContent = message;
            }
        }

        function showCard() {
            card.style.display = '';
            card.hidden = false;
            applySuccessMessage();
        }

        function hasWpformsConfirmation() {
            return document.querySelector('.course-page .wpforms-confirmation-container, .course-page .wpforms-confirmation') !== null;
        }

        function handleConfirmation() {
            if (hasWpformsConfirmation()) {
                if (window.sessionStorage) {
                    sessionStorage.setItem('jrcSeatBooked', '1');
                }
                showCard();
                return true;
            }
            return false;
        }

        if (card.getAttribute('data-show') === '1') {
            showCard();
        } else if (window.sessionStorage && sessionStorage.getItem('jrcSeatBooked') === '1') {
            showCard();
        } else {
            handleConfirmation();
        }

        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || !form.closest) {
                return;
            }
            if (!form.closest('.course-page')) {
                return;
            }
            if (window.sessionStorage) {
                sessionStorage.setItem('jrcSeatBooked', '1');
            }
            showCard();
        }, true);

        if (window.MutationObserver) {
            var observer = new MutationObserver(function () {
                handleConfirmation();
            });
            observer.observe(document.body, { childList: true, subtree: true });
        }
    })();
</script>
<?php get_footer(); ?>
