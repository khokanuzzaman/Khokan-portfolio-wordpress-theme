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
$apply_ctas = array_values(array_filter($course['hero']['ctas'], function ($cta) {
    return !empty($cta['label']) && !empty($cta['link']);
}));
$primary_cta = $apply_ctas[0] ?? null;
$quiz_cta = $course['quiz_cta'] ?? [];
$quiz_cta_label = trim((string) ($quiz_cta['label'] ?? ''));
$quiz_cta_link = trim((string) ($quiz_cta['link'] ?? ''));
$quiz_available = $quiz_cta_label !== '' && $quiz_cta_link !== '';
$enroll_link = $course['enrollment']['website']['link'] ?? '';
if ($enroll_link === '' && $primary_cta && !empty($primary_cta['link'])) {
    $enroll_link = $primary_cta['link'];
}
$hero_ctas = [];
if ($quiz_available) {
    $hero_ctas[] = [
        'label' => $quiz_cta_label,
        'link' => $quiz_cta_link,
        'class' => 'primary-btn',
    ];
} elseif ($primary_cta) {
    $hero_ctas[] = $primary_cta;
}
$hero_secondary_cta = $course['hero']['secondary_cta'] ?? [];
if (!empty($hero_secondary_cta['label']) && !empty($hero_secondary_cta['link'])) {
    $hero_ctas[] = [
        'label' => $hero_secondary_cta['label'],
        'link' => $hero_secondary_cta['link'],
        'class' => $hero_secondary_cta['class'] ?? 'secondary-btn',
    ];
}
$discount_cta_label = trim((string) ($course['discount_hook']['cta_label'] ?? ''));
if ($discount_cta_label === '') {
    $discount_cta_label = $quiz_cta_label;
}
$final_cta = $course['final_cta'] ?? [];
$final_test_label = trim((string) ($final_cta['test_label'] ?? $quiz_cta_label));
$final_apply_label = trim((string) ($final_cta['apply_label'] ?? ($primary_cta['label'] ?? 'Apply Now')));
$final_ctas = [];
if ($quiz_available && $quiz_cta_link !== '') {
    $final_ctas[] = [
        'label' => $final_test_label !== '' ? $final_test_label : $quiz_cta_label,
        'link' => $quiz_cta_link,
        'class' => 'primary-btn',
    ];
}
if ($primary_cta && !empty($primary_cta['link'])) {
    $final_ctas[] = [
        'label' => $final_apply_label,
        'link' => $primary_cta['link'],
        'class' => 'secondary-btn',
    ];
}
if (empty($final_ctas) && $primary_cta && !empty($primary_cta['link'])) {
    $final_ctas[] = $primary_cta;
}
$cta_basic = $quiz_available ? [
    'label' => $quiz_cta_label,
    'link' => $quiz_cta_link,
    'class' => 'primary-btn',
] : null;
$cta_apply = $primary_cta ? [
    'label' => $primary_cta['label'],
    'link' => $primary_cta['link'],
    'class' => 'secondary-btn',
] : null;
$cta_curriculum = [
    'label' => $hero_secondary_cta['label'] ?? 'View Curriculum',
    'link' => $hero_secondary_cta['link'] ?? '#curriculum',
    'class' => 'secondary-btn',
];
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
$mentors_enabled = !isset($course['mentors']['enabled']) || $course['mentors']['enabled'];
$mentor_items = $course['mentors']['items'] ?? [];
$mentor_items = array_values(array_filter($mentor_items, function ($mentor) {
    return !isset($mentor['enabled']) || $mentor['enabled'];
}));
$mentor_card = $course['mentor'] ?? [];
$has_mentor_card = !empty($mentor_card) && (trim($mentor_card['title'] ?? '') !== '' || trim($mentor_card['bio'] ?? '') !== '');
$has_mentor_items = !empty($mentor_items);
$has_mentors_section = $mentors_enabled && ($has_mentor_card || $has_mentor_items);
$section_nav = [];
$add_nav = function ($id, $label, $condition = true) use (&$section_nav) {
    if ($condition && $label !== '') {
        $section_nav[] = ['id' => $id, 'label' => $label];
    }
};
$add_nav('hero', 'Overview');
$add_nav('course-options', 'Tracks', !empty($course['course_paths']));
$add_nav('curriculum', 'Curriculum', !empty($course['curriculum']));
$add_nav('build', 'Projects', !empty($course['build']) || !empty($course['projects']));
$add_nav('pricing', 'Pricing', !empty($course['pricing']));
$add_nav('course-application', 'Apply', !empty($course['course_paths']));

if (!function_exists('jrc_render_cta_block')) {
    function jrc_render_cta_block($title, $subtitle, $primary, $secondary)
    {
        ?>
        <div class="course-cta-block">
            <div>
                <?php if ($title) : ?>
                    <h3><?php echo esc_html($title); ?></h3>
                <?php endif; ?>
                <?php if ($subtitle) : ?>
                    <p class="section-subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php endif; ?>
            </div>
            <div class="course-cta__actions">
                <?php if ($primary && !empty($primary['link'])) : ?>
                    <a class="<?php echo esc_attr($primary['class'] ?? 'primary-btn'); ?>" href="<?php echo esc_attr($primary['link']); ?>">
                        <?php echo esc_html($primary['label']); ?>
                    </a>
                <?php endif; ?>
                <?php if ($secondary && !empty($secondary['link'])) : ?>
                    <a class="<?php echo esc_attr($secondary['class'] ?? 'secondary-btn'); ?>" href="<?php echo esc_attr($secondary['link']); ?>">
                        <?php echo esc_html($secondary['label']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
?>
<main class="course-page">
    <?php the_content(); ?>
    <section class="section course-hero" id="hero">
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
                <?php if (!empty($course['hero']['logo']) || !empty($course['hero']['organization'])) : ?>
                    <div class="course-hero__brand">
                        <?php if (!empty($course['hero']['logo'])) : ?>
                            <img class="course-hero__logo" src="<?php echo esc_url($course['hero']['logo']); ?>" alt="<?php echo esc_attr($course['hero']['organization'] ?? ''); ?>">
                        <?php endif; ?>
                        <?php if (!empty($course['hero']['organization'])) : ?>
                            <p class="course-hero__org"><?php echo esc_html($course['hero']['organization']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php $hero_title = function_exists('jrc_format_hero_title') ? jrc_format_hero_title($course['hero']['title']) : $course['hero']['title']; ?>
                <h1 class="course-hero__title"><?php echo wp_kses_post($hero_title); ?></h1>
                <p class="course-hero__subtitle"><?php echo esc_html($course['hero']['subtitle']); ?></p>
                <?php if (!empty($course['hero']['note'])) : ?>
                    <p class="course-hero__note"><?php echo esc_html($course['hero']['note']); ?></p>
                <?php endif; ?>
                <?php if (!empty($course['hero']['highlights']) && is_array($course['hero']['highlights'])) : ?>
                    <ul class="course-hero__highlights">
                        <?php foreach ($course['hero']['highlights'] as $highlight) : ?>
                            <?php if (trim((string) $highlight) !== '') : ?>
                                <li><?php echo esc_html($highlight); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php if (!empty($course['hero']['microcopy'])) : ?>
                    <p class="course-hero__microcopy"><?php echo esc_html($course['hero']['microcopy']); ?></p>
                <?php endif; ?>
                <?php if ($quiz_available) : ?>
                    <div class="course-highlight course-highlight--quiz">
                        <span class="course-highlight__label"><?php echo esc_html($course['hero']['quiz_label'] ?? 'Basic Test'); ?></span>
                        <p class="course-highlight__text"><?php echo esc_html($course['hero']['quiz_note'] ?? 'Basic test pass করলে special discount unlock হবে।'); ?></p>
                    </div>
                <?php endif; ?>
                <div class="course-hero__actions">
                    <?php foreach ($hero_ctas as $cta) : ?>
                        <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endforeach; ?>
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

    <?php if (!empty($section_nav)) : ?>
        <nav class="course-nav" aria-label="Course sections">
            <div class="container">
                <ul class="course-nav__list">
                    <?php foreach ($section_nav as $item) : ?>
                        <li>
                            <a class="course-nav__link" href="#<?php echo esc_attr($item['id']); ?>">
                                <?php echo esc_html($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <?php if (!empty($course['problem_solution'])) : ?>
        <?php
        $problem_primary = $cta_basic ?: $cta_apply;
        if ($problem_primary && !empty($course['problem_solution']['cta_label'])) {
            $problem_primary['label'] = $course['problem_solution']['cta_label'];
        }
        $problem_secondary = $cta_apply && $problem_primary !== $cta_apply ? $cta_apply : null;
        ?>
        <section class="section course-section course-problem" id="problem-solution">
            <div class="container course-problem__grid">
                <div>
                    <div class="section-heading">
                        <h2><?php echo esc_html($course['problem_solution']['title']); ?></h2>
                        <p class="section-subtitle"><?php echo esc_html($course['problem_solution']['subtitle']); ?></p>
                    </div>
                    <?php if (!empty($course['problem_solution']['problems'])) : ?>
                        <ul class="course-problem__list">
                            <?php foreach ($course['problem_solution']['problems'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="course-problem__card">
                    <h3><?php echo esc_html($course['problem_solution']['solution_title']); ?></h3>
                    <p class="section-subtitle"><?php echo esc_html($course['problem_solution']['solution_text']); ?></p>
                </div>
            </div>
            <?php
            jrc_render_cta_block(
                $course['problem_solution']['cta_title'] ?? '',
                $course['problem_solution']['cta_subtitle'] ?? '',
                $problem_primary,
                $problem_secondary
            );
            ?>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['transformation'])) : ?>
        <?php
        $transformation_primary = $cta_basic ?: $cta_apply;
        if ($transformation_primary && !empty($course['transformation']['cta_label'])) {
            $transformation_primary['label'] = $course['transformation']['cta_label'];
        }
        $transformation_secondary = $cta_apply && $transformation_primary !== $cta_apply ? $cta_apply : null;
        ?>
        <section class="section course-section course-transformation" id="transformation">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['transformation']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['transformation']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['transformation']['steps'])) : ?>
                    <div class="course-transformation__grid">
                        <?php foreach ($course['transformation']['steps'] as $step) : ?>
                            <div class="course-transformation__card">
                                <h3><?php echo esc_html($step['title'] ?? ''); ?></h3>
                                <?php if (!empty($step['text'])) : ?>
                                    <p class="section-subtitle"><?php echo esc_html($step['text']); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['transformation']['cta_title'] ?? '',
                    $course['transformation']['cta_subtitle'] ?? '',
                    $transformation_primary,
                    $transformation_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['before_after'])) : ?>
        <section class="section course-section course-before-after" id="before-after">
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
    <?php endif; ?>

    <?php if (!empty($course['why'])) : ?>
        <section class="section course-section course-why" id="why-program">
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
    <?php endif; ?>

    <?php if (!empty($course['trust'])) : ?>
        <section class="section course-section course-trust" id="trust">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['trust']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['trust']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['trust']['items'])) : ?>
                    <div class="course-trust__grid">
                        <?php foreach ($course['trust']['items'] as $item) : ?>
                            <div class="course-trust__card">
                                <span class="course-trust__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M12 2l7 3v6c0 5.2-3.5 9.5-7 11-3.5-1.5-7-5.8-7-11V5l7-3z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path>
                                        <path d="M8.5 12.2l2.2 2.2 4.8-4.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h3><?php echo esc_html($item['title'] ?? $item); ?></h3>
                                    <?php if (!empty($item['text'])) : ?>
                                        <p class="section-subtitle"><?php echo esc_html($item['text']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                $trust_primary = $cta_basic ?: $cta_apply;
                $trust_secondary = $cta_apply && $trust_primary !== $cta_apply ? $cta_apply : null;
                jrc_render_cta_block(
                    'Build trust with real projects',
                    'Start with the basic test and secure your seat.',
                    $trust_primary,
                    $trust_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['who_for'])) : ?>
        <?php
        $who_for_primary = $cta_apply ?: $cta_basic;
        if ($who_for_primary && !empty($course['who_for']['cta_label'])) {
            $who_for_primary['label'] = $course['who_for']['cta_label'];
        }
        $who_for_secondary = $cta_basic && $who_for_primary !== $cta_basic ? $cta_basic : null;
        ?>
        <section class="section course-section course-who" id="who-for">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['who_for']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['who_for']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['who_for']['items'])) : ?>
                    <ul class="course-who__list">
                        <?php foreach ($course['who_for']['items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['who_for']['cta_title'] ?? '',
                    $course['who_for']['cta_subtitle'] ?? '',
                    $who_for_primary,
                    $who_for_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['who_not_for'])) : ?>
        <?php
        $who_not_primary = $cta_basic ?: $cta_apply;
        if ($who_not_primary && !empty($course['who_not_for']['cta_label'])) {
            $who_not_primary['label'] = $course['who_not_for']['cta_label'];
        }
        $who_not_secondary = $cta_apply && $who_not_primary !== $cta_apply ? $cta_apply : null;
        ?>
        <section class="section course-section course-who-not" id="who-not-for">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['who_not_for']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['who_not_for']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['who_not_for']['items'])) : ?>
                    <ul class="course-who__list is-muted">
                        <?php foreach ($course['who_not_for']['items'] as $item) : ?>
                            <li><?php echo esc_html($item); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['who_not_for']['cta_title'] ?? '',
                    $course['who_not_for']['cta_subtitle'] ?? '',
                    $who_not_primary,
                    $who_not_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['audience']) || !empty($course['not_for'])) : ?>
        <section class="section course-section course-eligibility" id="eligibility">
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
    <?php endif; ?>

    <?php if (!empty($course['learning'])) : ?>
        <section class="section course-section course-learning" id="learning">
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
    <?php endif; ?>

    <?php if (!empty($course['tracks'])) : ?>
        <section class="section course-section course-tracks" id="tracks">
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
    <?php endif; ?>

    <?php if (!empty($course['course_paths'])) : ?>
        <section class="section course-section course-paths" id="course-options">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['course_paths']['title'] ?? 'Choose Your Course'); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['course_paths']['subtitle'] ?? 'Pick the path that fits you.'); ?></p>
                </div>
                <div class="course-grid course-grid--two">
                    <?php foreach ($course['course_paths']['items'] as $item) : ?>
                        <?php
                        $key = $item['key'] ?? '';
                        $stats = function_exists('jrc_get_course_seat_stats') && $key ? jrc_get_course_seat_stats($key) : null;
                        $seat_total = $stats ? $stats['total'] : (int) ($item['seat_total'] ?? 30);
                        $seat_remaining = $stats ? $stats['remaining'] : $seat_total;
                        ?>
                        <div class="course-card jrc-course-card" data-course="<?php echo esc_attr($key); ?>">
                            <h3><?php echo esc_html($item['title'] ?? 'Course'); ?></h3>
                            <?php if (!empty($item['overview'])) : ?>
                                <p class="section-subtitle"><?php echo esc_html($item['overview']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['outcome'])) : ?>
                                <p class="section-subtitle"><?php echo esc_html($item['outcome']); ?></p>
                            <?php endif; ?>
                            <p class="course-seat">Seats remaining: <?php echo esc_html($seat_remaining); ?> / <?php echo esc_html($seat_total); ?></p>
                            <?php if (!empty($item['apply_note'])) : ?>
                                <p class="section-subtitle"><?php echo esc_html($item['apply_note']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['cta_link']) && !empty($item['cta_label'])) : ?>
                                <div class="course-cta__actions">
                                    <a class="secondary-btn" href="<?php echo esc_attr($item['cta_link']); ?>">
                                        <?php echo esc_html($item['cta_label']); ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($key)) : ?>
                                <div class="course-cta__actions">
                                    <button type="button" class="primary-btn jrc-course-select" data-course="<?php echo esc_attr($key); ?>">Select This Path</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['curriculum'])) : ?>
        <section class="section course-section course-curriculum" id="curriculum">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['curriculum']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['curriculum']['subtitle']); ?></p>
                </div>
                <div class="course-grid course-grid--two curriculum-grid">
                    <?php foreach ($course['curriculum']['paths'] as $path) : ?>
                        <?php
                        $path_key = $path['key'] ?? '';
                        $sections = $path['sections'] ?? [];
                        ?>
                        <div class="curriculum-card">
                            <div class="curriculum-card__header">
                                <span class="curriculum-icon <?php echo $path_key ? 'is-' . esc_attr($path_key) : ''; ?>" aria-hidden="true">
                                    <?php if ($path_key === 'react') : ?>
                                        <svg viewBox="0 0 80 80" focusable="false" aria-hidden="true">
                                            <circle cx="40" cy="40" r="6" fill="currentColor"></circle>
                                            <ellipse cx="40" cy="40" rx="28" ry="12" fill="none" stroke="currentColor" stroke-width="4"></ellipse>
                                            <ellipse cx="40" cy="40" rx="12" ry="28" fill="none" stroke="currentColor" stroke-width="4" transform="rotate(60 40 40)"></ellipse>
                                            <ellipse cx="40" cy="40" rx="12" ry="28" fill="none" stroke="currentColor" stroke-width="4" transform="rotate(-60 40 40)"></ellipse>
                                        </svg>
                                    <?php elseif ($path_key === 'flutter') : ?>
                                        <svg viewBox="0 0 64 64" focusable="false" aria-hidden="true">
                                            <path d="M14 40l22-22h10L24 40H14z" fill="currentColor"></path>
                                            <path d="M24 50l12-12h10L34 50H24z" fill="currentColor" opacity="0.7"></path>
                                            <path d="M24 14L14 24h10l10-10H24z" fill="currentColor" opacity="0.55"></path>
                                        </svg>
                                    <?php else : ?>
                                        <svg viewBox="0 0 48 48" focusable="false" aria-hidden="true">
                                            <circle cx="24" cy="24" r="18" fill="none" stroke="currentColor" stroke-width="3"></circle>
                                            <path d="M16 24h16" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                                        </svg>
                                    <?php endif; ?>
                                </span>
                                <div>
                                    <h3><?php echo esc_html($path['title'] ?? ''); ?></h3>
                                    <?php if (!empty($path['tagline'])) : ?>
                                        <span class="curriculum-badge"><?php echo esc_html($path['tagline']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($path['intro'])) : ?>
                                <p class="curriculum-intro"><?php echo esc_html($path['intro']); ?></p>
                            <?php endif; ?>
                            <?php if (!empty($sections)) : ?>
                                <div class="curriculum-accordions">
                                    <?php foreach ($sections as $section_index => $section) : ?>
                                        <details class="curriculum-accordion"<?php echo $section_index === 0 ? ' open' : ''; ?>>
                                            <summary><?php echo esc_html($section['title'] ?? ''); ?></summary>
                                            <ul class="list">
                                                <?php foreach (($section['items'] ?? []) as $item) : ?>
                                                    <li><?php echo esc_html($item); ?></li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </details>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <div class="curriculum-meta">
                                <div>
                                    <h4><?php echo esc_html($path['skills_title'] ?? 'Skills Gained'); ?></h4>
                                    <ul class="list">
                                        <?php foreach (($path['skills'] ?? []) as $item) : ?>
                                            <li><?php echo esc_html($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <div>
                                    <h4><?php echo esc_html($path['career_title'] ?? 'Career Focus'); ?></h4>
                                    <ul class="list">
                                        <?php foreach (($path['career'] ?? []) as $item) : ?>
                                            <li><?php echo esc_html($item); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                            <?php if (!empty($path['cta_label']) && !empty($path_key)) : ?>
                                <div class="course-cta__actions">
                                    <button type="button" class="primary-btn jrc-course-select" data-course="<?php echo esc_attr($path_key); ?>">
                                        <?php echo esc_html($path['cta_label']); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['structure'])) : ?>
        <section class="section course-section course-structure" id="program-structure">
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
    <?php endif; ?>

    <?php if (!empty($course['timeline'])) : ?>
        <section class="section course-section course-timeline" id="weekly-timeline">
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
    <?php endif; ?>

    <?php if (!empty($course['roadmap'])) : ?>
        <?php
        $roadmap_primary = $cta_basic ?: $cta_apply;
        if ($roadmap_primary && !empty($course['roadmap']['cta_label'])) {
            $roadmap_primary['label'] = $course['roadmap']['cta_label'];
        }
        $roadmap_secondary = $cta_apply && $roadmap_primary !== $cta_apply ? $cta_apply : null;
        ?>
        <section class="section course-section course-roadmap" id="roadmap">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['roadmap']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['roadmap']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['roadmap']['steps'])) : ?>
                    <ol class="course-roadmap__timeline">
                        <?php foreach ($course['roadmap']['steps'] as $step) : ?>
                            <li><?php echo esc_html($step); ?></li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['roadmap']['cta_title'] ?? '',
                    $course['roadmap']['cta_subtitle'] ?? '',
                    $roadmap_primary,
                    $roadmap_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['build'])) : ?>
        <?php
        $build_primary = [
            'label' => $course['build']['cta_label'] ?? $cta_curriculum['label'],
            'link' => $cta_curriculum['link'],
            'class' => 'primary-btn',
        ];
        $build_secondary = $cta_basic ?: $cta_apply;
        if ($build_secondary && $build_secondary['class'] === 'primary-btn') {
            $build_secondary['class'] = 'secondary-btn';
        }
        ?>
        <section class="section course-section course-build" id="build">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['build']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['build']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['build']['items'])) : ?>
                    <div class="course-build__grid">
                        <?php foreach ($course['build']['items'] as $item) : ?>
                            <div class="course-build__card">
                                <span class="course-build__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M4 7.5h16v10H4z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path>
                                        <path d="M8 6.5h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                                        <path d="M8 12h4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"></path>
                                    </svg>
                                </span>
                                <div>
                                    <h3><?php echo esc_html($item['title'] ?? $item); ?></h3>
                                    <?php if (!empty($item['text'])) : ?>
                                        <p class="section-subtitle"><?php echo esc_html($item['text']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['build']['cta_title'] ?? '',
                    $course['build']['cta_subtitle'] ?? '',
                    $build_primary,
                    $build_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['projects'])) : ?>
        <section class="section course-section course-projects" id="projects">
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
    <?php endif; ?>

    <?php if (!empty($course['portfolio_support'])) : ?>
        <section class="section course-section course-portfolio" id="portfolio-support">
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
    <?php endif; ?>

    <?php if (!empty($course['ai_usage'])) : ?>
        <section class="section course-section course-ai" id="ai-workflow">
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
                        <?php foreach ($apply_ctas as $cta) : ?>
                            <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                                <?php echo esc_html($cta['label']); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['interview'])) : ?>
        <section class="section course-section course-interview" id="interview">
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
    <?php endif; ?>

    <?php if ($has_mentors_section) : ?>
        <section class="section course-section course-mentors" id="mentors">
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

    <?php if (!empty($course['social_proof'])) : ?>
        <?php
        $social_primary = $cta_apply ?: $cta_basic;
        if ($social_primary && !empty($course['social_proof']['cta_label'])) {
            $social_primary['label'] = $course['social_proof']['cta_label'];
        }
        $social_secondary = $cta_basic && $social_primary !== $cta_basic ? $cta_basic : null;
        ?>
        <section class="section course-section course-social" id="social-proof">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['social_proof']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['social_proof']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['social_proof']['items'])) : ?>
                    <div class="course-social__grid">
                        <?php foreach ($course['social_proof']['items'] as $item) : ?>
                            <div class="course-social__card">
                                <span class="course-social__icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M12 3l7 4v5c0 4.2-3 7.8-7 9-4-1.2-7-4.8-7-9V7l7-4z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path>
                                        <path d="M9.2 12.2l2 2 3.8-3.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </span>
                                <p><?php echo esc_html($item); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php
                jrc_render_cta_block(
                    $course['social_proof']['cta_title'] ?? '',
                    $course['social_proof']['cta_subtitle'] ?? '',
                    $social_primary,
                    $social_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['details'])) : ?>
        <section class="section course-section course-details" id="course-details">
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
    <?php endif; ?>

    <?php if (!empty($course['pricing'])) : ?>
        <section class="section course-section course-pricing" id="pricing">
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
                        <span><?php echo esc_html($pricing['standard_label'] ?? 'Original Course Fee'); ?></span>
                        <strong><?php echo esc_html($pricing['standard']); ?></strong>
                    </div>
                    <div class="course-price__item">
                        <span><?php echo esc_html($pricing['discount_label'] ?? 'Discounted Price (After Test)'); ?></span>
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
    <?php endif; ?>

    <?php if (!empty($course['batch_info'])) : ?>
        <section class="section course-section course-batch" id="batch-info">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['batch_info']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['batch_info']['subtitle']); ?></p>
                </div>
                <div class="course-batch__grid">
                    <div class="course-batch__card">
                        <?php if (!empty($course['batch_info']['items'])) : ?>
                            <ul class="course-batch__list">
                                <?php foreach ($course['batch_info']['items'] as $item) : ?>
                                    <li>
                                        <span><?php echo esc_html($item['label'] ?? ''); ?></span>
                                        <strong><?php echo esc_html($item['value'] ?? ''); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($course['batch_info']['note'])) : ?>
                            <p class="course-batch__note"><?php echo esc_html($course['batch_info']['note']); ?></p>
                        <?php endif; ?>
                        <?php if ($primary_cta) : ?>
                            <div class="course-cta__actions">
                                <a class="primary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                                    <?php echo esc_html($course['batch_info']['cta'] ?? $primary_cta['label']); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['discount_hook'])) : ?>
        <section class="section course-section course-discount" id="basic-test">
            <div class="container course-discount__grid">
                <div>
                    <div class="section-heading">
                        <h2><?php echo esc_html($course['discount_hook']['title']); ?></h2>
                        <p class="section-subtitle"><?php echo esc_html($course['discount_hook']['subtitle']); ?></p>
                    </div>
                    <?php if (!empty($course['discount_hook']['items'])) : ?>
                        <ul class="course-discount__list">
                            <?php foreach ($course['discount_hook']['items'] as $item) : ?>
                                <li><?php echo esc_html($item); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <div class="course-discount__card">
                    <div class="course-highlight">
                        <span class="course-highlight__label"><?php echo esc_html($course['discount_hook']['badge'] ?? 'Basic Test'); ?></span>
                        <p class="course-highlight__text"><?php echo esc_html($course['discount_hook']['cta_text'] ?? 'Pass the test to unlock discount.'); ?></p>
                    </div>
                    <?php if ($quiz_available) : ?>
                        <div class="course-cta__actions">
                            <a class="primary-btn" href="<?php echo esc_attr($quiz_cta_link); ?>">
                                <?php echo esc_html($discount_cta_label); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['enrollment_process'])) : ?>
        <section class="section course-section course-process" id="enrollment-process">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['enrollment_process']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment_process']['subtitle']); ?></p>
                </div>
                <?php if (!empty($course['enrollment_process']['steps'])) : ?>
                    <ol class="course-steps">
                        <?php foreach ($course['enrollment_process']['steps'] as $step) : ?>
                            <li class="course-step">
                                <h3><?php echo esc_html($step['title'] ?? ''); ?></h3>
                                <?php if (!empty($step['text'])) : ?>
                                    <p class="section-subtitle"><?php echo esc_html($step['text']); ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
                <?php if (!empty($course['enrollment_process']['note'])) : ?>
                    <div class="course-highlight course-highlight--process">
                        <span class="course-highlight__label"><?php echo esc_html($course['enrollment_process']['note_label'] ?? 'Discount Unlock'); ?></span>
                        <p class="course-highlight__text"><?php echo esc_html($course['enrollment_process']['note']); ?></p>
                    </div>
                <?php endif; ?>
                <?php
                $process_primary = $cta_basic ?: $cta_apply;
                $process_secondary = $cta_apply && $process_primary !== $cta_apply ? $cta_apply : null;
                jrc_render_cta_block(
                    'Ready to start?',
                    'Complete the basic test to unlock your discount.',
                    $process_primary,
                    $process_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['payment'])) : ?>
        <section class="section course-section course-payment" id="payment-options">
            <div class="container">
                <div class="section-heading">
                    <h2><?php echo esc_html($course['payment']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['payment']['subtitle']); ?></p>
                </div>
                <div class="course-payment__grid">
                    <div class="course-payment__card">
                        <?php if (!empty($course['payment']['methods'])) : ?>
                            <ul class="course-payment__list">
                                <?php foreach ($course['payment']['methods'] as $method) : ?>
                                    <li><?php echo esc_html($method); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if (!empty($course['payment']['note'])) : ?>
                            <p class="section-subtitle"><?php echo esc_html($course['payment']['note']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['course_paths'])) : ?>
        <section class="section course-section course-application" id="course-application">
            <div class="container">
                <div class="section-heading">
                    <?php if (!empty($course['hero']['logo'])) : ?>
                        <img class="jrc-form-logo" src="<?php echo esc_url($course['hero']['logo']); ?>" alt="<?php echo esc_attr($course['hero']['organization'] ?? ''); ?>">
                    <?php endif; ?>
                    <h2><?php echo esc_html($course['enrollment']['website']['title'] ?? 'Apply Now'); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['course_paths']['selection_note'] ?? 'Select your course and submit the form.'); ?></p>
                </div>
                <div class="course-card">
                    <?php echo do_shortcode('[jrc_application_form]'); ?>
                </div>
                <?php if (!empty($whatsapp_chat_link)) : ?>
                    <a class="jrc-help-fab" href="<?php echo esc_url($whatsapp_chat_link); ?>">
                        Have Questions? WhatsApp Us
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['faq'])) : ?>
        <section class="section course-section course-faqs" id="faq">
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
                <?php
                $faq_primary = $cta_basic ?: $cta_apply;
                $faq_secondary = $cta_apply && $faq_primary !== $cta_apply ? $cta_apply : null;
                jrc_render_cta_block(
                    'Have more questions?',
                    'Take the basic test or apply to reserve your seat.',
                    $faq_primary,
                    $faq_secondary
                );
                ?>
            </div>
        </section>
    <?php endif; ?>

    <?php if (!empty($course['final_cta'])) : ?>
        <section class="section course-section course-final" id="final-cta">
            <div class="container course-final__inner">
                <div>
                    <h2><?php echo esc_html($course['final_cta']['title']); ?></h2>
                    <p class="section-subtitle"><?php echo esc_html($course['final_cta']['subtitle']); ?></p>
                    <?php if (!empty($course['final_cta']['microcopy'])) : ?>
                        <p class="section-subtitle"><?php echo esc_html($course['final_cta']['microcopy']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="course-cta__actions">
                    <?php foreach ($final_ctas as $cta) : ?>
                        <a class="<?php echo esc_attr($cta['class']); ?>" href="<?php echo esc_attr($cta['link']); ?>">
                            <?php echo esc_html($cta['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <button class="course-back-to-top" type="button" aria-label="Back to top">
        ↑
    </button>

    <?php
    $whatsapp_support_link = $whatsapp_chat_link ?: $whatsapp_group;
    ?>
    <?php if ($show_whatsapp && $whatsapp_support_link) : ?>
        <a class="course-whatsapp-support" href="<?php echo esc_url($whatsapp_support_link); ?>">
            <span class="course-whatsapp-support__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                    <path d="M12 2.4a9.6 9.6 0 0 0-8.3 14.4L2.5 22l5.4-1.4A9.6 9.6 0 1 0 12 2.4z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"></path>
                    <path d="M8.4 7.8c-.3.7-.2 1.6.2 2.6.6 1.4 2 2.9 3.6 3.7 1 .5 1.8.6 2.5.3.5-.2 1.1-.8 1.2-1.3.1-.4-.1-.7-.4-.9l-1.5-.7c-.3-.2-.7-.1-.9.2l-.4.6c-.2.3-.5.4-.8.2-.6-.3-1.3-.9-1.8-1.5-.2-.3-.2-.7.1-1l.5-.5c.2-.3.2-.7 0-1l-.8-1.4c-.2-.3-.5-.4-.8-.3-.5.2-1 .6-1.2 1z" fill="currentColor"></path>
                </svg>
            </span>
            <span class="course-whatsapp-support__text">
                <strong><?php echo esc_html($course['support']['title'] ?? 'Have Questions?'); ?></strong>
                <span><?php echo esc_html($course['support']['subtitle'] ?? 'Chat with us on WhatsApp'); ?></span>
            </span>
        </a>
    <?php endif; ?>
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
<script>
    (function () {
        var forms = document.querySelectorAll('.jrc-application-form');
        forms.forEach(function (form) {
            var progress = form.querySelector('.jrc-progress');
            var progressBar = form.querySelector('.jrc-progress__bar');
            var progressLabel = form.querySelector('.jrc-progress__label');

            function getRequiredNames() {
                var required = form.querySelectorAll('[required]');
                var names = {};
                required.forEach(function (field) {
                    if (field.name) {
                        names[field.name] = true;
                    }
                });
                return Object.keys(names);
            }

            function isFieldComplete(name) {
                var fields = form.querySelectorAll('[name="' + name + '"]');
                if (!fields.length) {
                    return false;
                }
                var field = fields[0];
                if (field.type === 'radio' || field.type === 'checkbox') {
                    return Array.prototype.some.call(fields, function (item) { return item.checked; });
                }
                if (field.tagName === 'SELECT') {
                    return field.value && field.value.trim() !== '';
                }
                return field.value && field.value.trim() !== '';
            }

            function updateProgress() {
                if (!progress || !progressBar || !progressLabel) {
                    return;
                }
                var names = getRequiredNames();
                if (!names.length) {
                    return;
                }
                var completed = names.filter(isFieldComplete).length;
                var percent = Math.round((completed / names.length) * 100);
                progressBar.style.width = percent + '%';
                progressLabel.textContent = percent + '% complete';
                progress.setAttribute('aria-valuenow', percent);
            }

            function getErrorElement(field) {
                if (!field || !field.name) {
                    return null;
                }
                return form.querySelector('.jrc-error[data-error-for="' + field.name + '"]');
            }

            function setError(field, message) {
                var error = getErrorElement(field);
                if (error) {
                    error.textContent = message;
                    error.classList.add('is-visible');
                }
                var wrapper = field.closest('.jrc-form-field');
                if (wrapper) {
                    wrapper.classList.add('has-error');
                }
            }

            function clearError(field) {
                var error = getErrorElement(field);
                if (error) {
                    error.textContent = '';
                    error.classList.remove('is-visible');
                }
                var wrapper = field.closest('.jrc-form-field');
                if (wrapper) {
                    wrapper.classList.remove('has-error');
                }
            }

            function validateField(field) {
                if (!field) {
                    return;
                }
                if (field.checkValidity()) {
                    clearError(field);
                    return;
                }
                var label = field.getAttribute('data-label') || 'This field';
                var message = 'Please enter a valid value.';
                if (field.validity && field.validity.valueMissing) {
                    message = label + ' is required.';
                }
                setError(field, message);
            }

            form.addEventListener('invalid', function (event) {
                event.preventDefault();
                validateField(event.target);
            }, true);

            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    var firstInvalid = form.querySelector(':invalid');
                    if (firstInvalid) {
                        validateField(firstInvalid);
                        firstInvalid.focus({ preventScroll: false });
                    }
                }
            });

            form.addEventListener('input', function (event) {
                if (event.target && event.target.name) {
                    clearError(event.target);
                    updateProgress();
                }
            });

            form.addEventListener('change', function (event) {
                if (event.target && event.target.name) {
                    clearError(event.target);
                    updateProgress();
                }
            });

            var wordFields = form.querySelectorAll('textarea[data-max-words]');
            wordFields.forEach(function (field) {
                var max = parseInt(field.getAttribute('data-max-words'), 10);
                var counter = field.parentElement ? field.parentElement.querySelector('.jrc-word-count') : null;
                function updateCount() {
                    var words = field.value.trim() ? field.value.trim().split(/\s+/).length : 0;
                    if (counter) {
                        counter.textContent = words + ' / ' + max + ' words';
                    }
                    if (words > max) {
                        field.setCustomValidity('Please keep within ' + max + ' words.');
                    } else {
                        field.setCustomValidity('');
                    }
                }
                field.addEventListener('input', updateCount);
                updateCount();
            });

            var referral = form.querySelector('select[name="student_referral"]');
            var otherField = form.querySelector('[data-toggle="jrc-referral-other"]');
            function toggleOther() {
                if (!otherField) {
                    return;
                }
                var show = referral && referral.value === 'Other';
                otherField.style.display = show ? '' : 'none';
            }
            if (otherField) {
                toggleOther();
                if (referral) {
                    referral.addEventListener('change', toggleOther);
                }
            }

            var courseRadios = form.querySelectorAll('input[name="student_course"]');
            var courseNotes = form.querySelectorAll('.jrc-course-note');
            var courseOptionLabels = form.querySelectorAll('.jrc-course-options label');
            function updateCourseLabels() {
                if (!courseOptionLabels.length) {
                    return;
                }
                courseOptionLabels.forEach(function (label) {
                    var input = label.querySelector('input[type="radio"]');
                    label.classList.toggle('is-selected', !!(input && input.checked));
                });
            }
            function updateCourseNotes() {
                if (!courseNotes.length || !courseRadios.length) {
                    return;
                }
                var selected = form.querySelector('input[name="student_course"]:checked');
                var selectedValue = selected ? selected.value : '';
                courseNotes.forEach(function (note) {
                    var matches = note.getAttribute('data-course') === selectedValue;
                    note.classList.toggle('is-active', matches);
                });
                if (typeof courseCards !== 'undefined' && courseCards.length) {
                    courseCards.forEach(function (card) {
                        card.classList.toggle('is-selected', card.getAttribute('data-course') === selectedValue);
                    });
                }
                updateCourseLabels();
            }
            if (courseRadios.length && courseNotes.length) {
                courseRadios.forEach(function (radio) {
                    radio.addEventListener('change', updateCourseNotes);
                });
                updateCourseNotes();
            }

            updateProgress();
        });

        var applicationSection = document.getElementById('course-application');
        var courseCards = document.querySelectorAll('.jrc-course-card');
        function setCourseSelection(value, shouldScroll) {
            if (!value) {
                return;
            }
            var form = document.querySelector('.jrc-application-form');
            if (form) {
                var radio = form.querySelector('input[name="student_course"][value="' + value + '"]');
                if (radio) {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }
            courseCards.forEach(function (card) {
                card.classList.toggle('is-selected', card.getAttribute('data-course') === value);
            });
            if (shouldScroll !== false && applicationSection && applicationSection.scrollIntoView) {
                applicationSection.scrollIntoView({ behavior: 'smooth' });
            }
        }

        document.querySelectorAll('.jrc-course-select').forEach(function (button) {
            button.addEventListener('click', function () {
                setCourseSelection(button.getAttribute('data-course'));
            });
        });

        courseCards.forEach(function (card) {
            card.addEventListener('click', function (event) {
                if (event.target.closest('a') || event.target.closest('button') || event.target.closest('input')) {
                    return;
                }
                setCourseSelection(card.getAttribute('data-course'));
            });
        });

        var initialSelection = document.querySelector('.jrc-application-form input[name="student_course"]:checked');
        if (initialSelection) {
            setCourseSelection(initialSelection.value, false);
        }

    })();
</script>
<script>
    (function () {
        var page = document.querySelector('.course-page');
        var nav = document.querySelector('.course-nav');
        if (!page || !nav) {
            return;
        }

        var navOffset = 0;

        function updateNavHeight() {
            var height = nav.offsetHeight || 0;
            page.style.setProperty('--course-nav-height', height + 'px');
        }

        function updateNavOffset() {
            navOffset = nav.getBoundingClientRect().top + window.scrollY;
        }

        function setFixedState(shouldFix) {
            page.classList.toggle('is-nav-fixed', shouldFix);
            updateNavHeight();
        }

        function toggleFixed() {
            var shouldFix = window.scrollY > navOffset;
            var isFixed = page.classList.contains('is-nav-fixed');
            if (shouldFix !== isFixed) {
                setFixedState(shouldFix);
            }
        }

        updateNavHeight();
        updateNavOffset();
        toggleFixed();

        window.addEventListener('scroll', toggleFixed, { passive: true });
        window.addEventListener('resize', function () {
            updateNavHeight();
            updateNavOffset();
            toggleFixed();
        }, { passive: true });
    })();
</script>
<script>
    (function () {
        var button = document.querySelector('.course-back-to-top');
        if (!button) {
            return;
        }

        var prefersReduced = false;
        if (window.matchMedia) {
            prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        }

        function toggleVisibility() {
            if (window.scrollY > 420) {
                button.classList.add('is-visible');
            } else {
                button.classList.remove('is-visible');
            }
        }

        button.addEventListener('click', function () {
            window.scrollTo({
                top: 0,
                behavior: prefersReduced ? 'auto' : 'smooth',
            });
        });

        toggleVisibility();
        window.addEventListener('scroll', toggleVisibility, { passive: true });
    })();
</script>
<?php get_footer(); ?>
