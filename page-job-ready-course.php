<?php
/**
 * Template Name: Job Ready Course
 * Description: Landing page for the Job-Ready Course.
 */

get_header();

$course = function_exists('jrc_get_course_data')
    ? jrc_get_course_data()
    : require __DIR__ . '/data/job-ready-course-content.php';
if (empty($course['why'])) {
    $course = require __DIR__ . '/data/job-ready-course-content.php';
}
$ctas = $course['hero']['ctas'];
?>
<main class="course-page">
    <?php the_content(); ?>
    <section class="section course-hero">
        <div class="container course-hero__grid">
            <div class="course-hero__content">
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
                    $hero_card = !empty($course['hero']['card_display']) ? $course['hero']['card_display'] : $course['hero']['card'];
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
                <div class="course-cta__actions">
                    <a class="primary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
                        <?php echo esc_html($course['why']['cta']); ?>
                    </a>
                </div>
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
                        <?php if (!empty($track['cta'])) : ?>
                            <div class="course-cta__actions">
                                <a class="secondary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
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
                <div class="course-cta__actions">
                    <a class="primary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
                        <?php echo esc_html($course['portfolio_support']['cta_text']); ?>
                    </a>
                    <p class="section-subtitle"><?php echo esc_html($course['portfolio_support']['microcopy']); ?></p>
                </div>
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
            <div class="course-cta__actions">
                <a class="secondary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
                    <?php echo esc_html($course['interview']['cta']); ?>
                </a>
            </div>
        </div>
    </section>

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['mentors']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['mentors']['subtitle']); ?></p>
            </div>
            <div class="course-grid">
                <?php foreach ($course['mentors']['items'] as $mentor) : ?>
                    <div class="course-card">
                        <h3><?php echo esc_html($mentor['name']); ?></h3>
                        <p class="section-subtitle"><?php echo esc_html($mentor['role']); ?></p>
                        <ul class="list">
                            <li>Experience: <?php echo esc_html($mentor['experience']); ?></li>
                            <li>Specialization: <?php echo esc_html($mentor['specialization']); ?></li>
                            <li>Real Projects: <?php echo esc_html($mentor['projects']); ?></li>
                            <li>LinkedIn: <?php echo esc_html($mentor['linkedin']); ?></li>
                            <li>GitHub: <?php echo esc_html($mentor['github']); ?></li>
                        </ul>
                        <p class="section-subtitle"><?php echo esc_html($mentor['message']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="course-cta__actions">
                <a class="secondary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
                    <?php echo esc_html($course['mentors']['cta']); ?>
                </a>
            </div>
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
                <h2><?php echo esc_html($course['pricing']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['pricing']['subtitle']); ?></p>
            </div>
            <div class="course-price">
                <div class="course-price__item">
                    <span>Regular Fee</span>
                    <strong><?php echo esc_html($course['pricing']['standard']); ?></strong>
                </div>
                <div class="course-price__item">
                    <span>Early Bird</span>
                    <strong><?php echo esc_html($course['pricing']['early']); ?></strong>
                </div>
                <p class="section-subtitle"><?php echo esc_html($course['pricing']['installment']); ?></p>
                <?php if (!empty($course['pricing']['note'])) : ?>
                    <p class="section-subtitle"><?php echo esc_html($course['pricing']['note']); ?></p>
                <?php endif; ?>
                <div class="course-cta__actions">
                    <a class="primary-btn" href="<?php echo esc_attr($ctas[0]['link']); ?>">
                        <?php echo esc_html($course['pricing']['cta']); ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="section course-section">
        <div class="container">
            <div class="section-heading">
                <h2><?php echo esc_html($course['enrollment']['title']); ?></h2>
                <p class="section-subtitle"><?php echo esc_html($course['enrollment']['subtitle']); ?></p>
            </div>
            <div class="course-grid course-grid--two">
                <div class="course-card">
                    <h3><?php echo esc_html($course['enrollment']['google_form']['title']); ?></h3>
                    <ul class="list">
                        <?php foreach ($course['enrollment']['google_form']['lines'] as $line) : ?>
                            <li><?php echo esc_html($line); ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment']['google_form']['trust']); ?></p>
                    <p class="section-subtitle"><?php echo esc_html($course['enrollment']['google_form']['after_submit']); ?></p>
                    <div class="course-cta__actions">
                        <a class="primary-btn" href="<?php echo esc_attr($course['enrollment']['google_form']['link']); ?>">
                            <?php echo esc_html($course['enrollment']['google_form']['button']); ?>
                        </a>
                    </div>
                </div>
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
                    <div class="course-cta__actions">
                        <a class="secondary-btn" href="<?php echo esc_attr($course['enrollment']['website']['link']); ?>">
                            <?php echo esc_html($course['enrollment']['website']['button']); ?>
                        </a>
                    </div>
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
<?php get_footer(); ?>
