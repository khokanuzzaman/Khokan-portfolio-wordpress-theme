<?php
/**
 * Template Name: Learning Path
 * Description: Display a category-based learning path as a course roadmap.
 */

get_header();

$subtitle = get_the_excerpt() ?: 'A curated series of lessons arranged as a guided learning path.';
$page_id = get_queried_object_id();
$page_slug = $page_id ? (string) get_post_field('post_name', $page_id) : '';
$meta_category = $page_id ? (string) get_post_meta($page_id, 'learning_path_category', true) : '';
$meta_category = trim($meta_category);
$category_slug = $meta_category !== '' ? sanitize_title($meta_category) : sanitize_title($page_slug);
if ($category_slug === '') {
    $category_slug = 'clean-architecture';
}
$category = get_category_by_slug($category_slug);
$category_link = $category ? get_category_link($category->term_id) : '';

$course_progress = [
    [
        'label' => 'Foundations & Goals',
        'status' => 'complete',
        'percent' => 100,
    ],
    [
        'label' => 'Entities & Boundaries',
        'status' => 'active',
        'percent' => 60,
    ],
    [
        'label' => 'Use Cases & Policies',
        'status' => 'upcoming',
        'percent' => 0,
    ],
    [
        'label' => 'Interfaces & Frameworks',
        'status' => 'upcoming',
        'percent' => 0,
    ],
];
$course_progress = apply_filters('khokan_learning_path_progress', $course_progress, $category_slug);

$path_query = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'date',
    'order' => 'ASC',
    'posts_per_page' => -1,
    'tax_query' => [
        [
            'taxonomy' => 'category',
            'field' => 'slug',
            'terms' => $category_slug,
        ],
    ],
]);

$lesson_count = (int) $path_query->post_count;
?>
<main class="learning-path">
    <section class="section learning-path__hero">
        <div class="container learning-path__intro">
            <div class="learning-path__intro-text">
                <p class="eyebrow">Learning Path</p>
                <h1 class="learning-path__title"><?php the_title(); ?></h1>
                <p class="section-subtitle"><?php echo esc_html($subtitle); ?></p>
            </div>
            <div class="learning-path__meta">
                <div class="learning-path__meta-card">
                    <p class="muted">Total lessons</p>
                    <div class="learning-path__count"><?php echo $lesson_count; ?></div>
                    <?php if ($category_link) : ?>
                        <a class="learning-path__link" href="<?php echo esc_url($category_link); ?>">Browse category</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="section learning-path__body">
        <div class="container learning-path__grid">
            <aside class="learning-path__sidebar">
                <div class="lp-panel">
                    <div class="lp-panel__sticky">
                        <h2 class="lp-panel__title">Course Roadmap</h2>
                        <p class="lp-panel__subtitle">Progress overview before you dive in.</p>
                        <ul class="lp-progress">
                            <?php foreach ($course_progress as $item) : ?>
                                <li class="lp-progress__item is-<?php echo esc_attr($item['status']); ?>">
                                    <span class="lp-progress__label"><?php echo esc_html($item['label']); ?></span>
                                    <span class="lp-progress__percent"><?php echo esc_html($item['percent']); ?>%</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="lp-panel__divider"></div>
                        <h3 class="lp-panel__title lp-panel__title--small">Lessons</h3>
                        <?php if ($path_query->have_posts()) : ?>
                            <ol class="lp-toc">
                                <?php $toc_index = 0; ?>
                                <?php while ($path_query->have_posts()) : ?>
                                    <?php $path_query->the_post(); ?>
                                    <?php $toc_index++; ?>
                                    <li class="lp-toc__item">
                                        <span class="lp-toc__marker"><?php echo esc_html(str_pad((string) $toc_index, 2, '0', STR_PAD_LEFT)); ?></span>
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </li>
                                <?php endwhile; ?>
                            </ol>
                        <?php else : ?>
                            <p class="empty-state">No lessons published yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </aside>

            <div class="learning-path__content">
                <?php
                wp_reset_postdata();
                $path_query->rewind_posts();
                ?>
                <?php if ($path_query->have_posts()) : ?>
                    <?php $index = 0; ?>
                    <?php while ($path_query->have_posts()) : ?>
                        <?php $path_query->the_post(); ?>
                        <?php $index++; ?>
                        <article <?php post_class('lp-card'); ?>>
                            <div class="lp-card__badge">
                                <span><?php echo esc_html(str_pad((string) $index, 2, '0', STR_PAD_LEFT)); ?></span>
                            </div>
                            <div class="lp-card__body">
                                <h3 class="lp-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                                <p class="lp-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28, '…')); ?></p>
                                <div class="lp-card__meta">
                                    <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                                    <span>·</span>
                                    <span><?php echo esc_html(get_the_author()); ?></span>
                                </div>
                            </div>
                            <div class="lp-card__action">
                                <a class="lp-card__button" href="<?php the_permalink(); ?>">Read now</a>
                            </div>
                        </article>
                    <?php endwhile; ?>
                <?php else : ?>
                    <p class="empty-state">No lessons found in this learning path yet.</p>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </div>
        </div>
    </section>
</main>
<?php get_footer(); ?>
