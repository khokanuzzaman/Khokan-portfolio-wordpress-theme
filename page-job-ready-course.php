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
                <h1 class="course-hero__title"><?php echo esc_html($course['hero']['title']); ?></h1>
                <p class="course-hero__subtitle"><?php echo esc_html($course['hero']['subtitle']); ?></p>
                <?php if (!empty($course['hero']['note'])) : ?>
                    <p class="course-hero__note"><?php echo esc_html($course['hero']['note']); ?></p>
                <?php endif; ?>
                <?php if (!empty($course['hero']['microcopy'])) : ?>
                    <p class="course-hero__note"><?php echo esc_html($course['hero']['microcopy']); ?></p>
                <?php endif; ?>
                <div class="course-hero__actions">
                    <?php foreach ($ctas as $cta) : ?>
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

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['why']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['why']['subtitle']); ?></p>
            </div>
            <p class="section-subtitle"><?php echo esc_html($course['why']['content']); ?></p>
            <ul class="list">
                <?php foreach ($course['why']['bullets'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
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

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['audience']['title']); ?></h2>
            </div>
            <ul class="list">
                <?php foreach ($course['audience']['items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['not_for']['title']); ?></h2>
            </div>
            <ul class="list">
                <?php foreach ($course['not_for']['items'] as $item) : ?>
                    <li><?php echo esc_html($item); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </section>

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['mentors']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['mentors']['subtitle']); ?></p>
            </div>
            <?php if (!empty($course['mentor'])) : ?>
                <div class="course-card">
                    <h3><?php echo esc_html($course['mentor']['title']); ?></h3>
                    <p class="section-subtitle"><?php echo esc_html($course['mentor']['bio']); ?></p>
                </div>
            <?php endif; ?>
            <div class="course-grid">
                <?php foreach ($course['mentors']['items'] as $mentor) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($mentor['name']); ?></h3>
                        <p class="section-subtitle"><?php echo esc_html($mentor['role']); ?></p>
                        <ul class="list">
                            <?php
                            $mentor_fields = [
                                'Experience' => $mentor['experience'],
                                'Specialization' => $mentor['specialization'],
                                'Real Projects' => $mentor['projects'],
                                'LinkedIn' => $mentor['linkedin'],
                                'GitHub' => $mentor['github'],
                            ];
                            foreach ($mentor_fields as $label => $value) :
                                if (empty($value)) {
                                    continue;
                                }
                                ?>
                                <li><?php echo esc_html($label); ?>: <?php echo esc_html($value); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="section-subtitle"><?php echo esc_html($mentor['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($primary_cta) : ?>
                <div class="course-cta__actions">
                    <a class="secondary-btn" href="<?php echo esc_attr($primary_cta['link']); ?>">
                        <?php echo esc_html($course['mentors']['cta']); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
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

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['enrollment']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['enrollment']['subtitle']); ?></p>
            </div>
            <div class="course-grid">
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
                    <?php if (!empty($course['enrollment']['website']['link'])) : ?>
                        <div class="course-cta__actions">
                            <a class="secondary-btn" href="<?php echo esc_attr($course['enrollment']['website']['link']); ?>">
                                <?php echo esc_html($course['enrollment']['website']['button']); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section course-section">
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
<?php get_footer(); ?>
