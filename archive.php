<?php
/**
 * Archive template for categories, tags, and taxonomies.
 */

get_header();

$topics = get_categories([
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 8,
]);

$archive_title = get_the_archive_title();
$eyebrow_label = 'Archive';
$archive_subtitle = wp_strip_all_tags(get_the_archive_description());

if (is_category()) {
    $archive_title = single_cat_title('', false);
    $eyebrow_label = 'Category';
    if (!$archive_subtitle) {
        $archive_subtitle = sprintf('Articles curated under %s.', $archive_title);
    }
} elseif (is_tag()) {
    $archive_title = single_tag_title('', false);
    $eyebrow_label = 'Tag';
    if (!$archive_subtitle) {
        $archive_subtitle = sprintf('Insights grouped by %s.', $archive_title);
    }
} elseif (is_tax()) {
    $term = get_queried_object();
    $taxonomy = get_taxonomy($term->taxonomy);
    $eyebrow_label = $taxonomy->labels->singular_name ?? 'Collection';
    $archive_title = $term->name ?? $archive_title;
    if (!$archive_subtitle) {
        $archive_subtitle = sprintf('Stories curated under %s.', $archive_title);
    }
} else {
    if (!$archive_subtitle) {
        $archive_subtitle = 'Latest articles and updates.';
    }
}

global $wp_query;
$post_count = (int) ($wp_query->found_posts ?? 0);
?>
<main>
    <section class="section blog-hero">
        <div class="container blog-hero__grid">
            <div>
                <p class="eyebrow"><?php echo esc_html($eyebrow_label); ?></p>
                <h1 class="blog-title"><?php echo esc_html($archive_title); ?></h1>
                <p class="section-subtitle"><?php echo esc_html($archive_subtitle); ?></p>
                <?php if (!empty($topics)) : ?>
                    <div class="blog-chips">
                        <?php foreach ($topics as $topic) : ?>
                            <?php
                            $is_current = is_category($topic->term_id);
                            $chip_classes = ['blog-chip'];
                            if ($is_current) {
                                $chip_classes[] = 'is-active';
                            }
                            ?>
                            <a class="<?php echo esc_attr(implode(' ', $chip_classes)); ?>" href="<?php echo esc_url(get_category_link($topic->term_id)); ?>">
                                <?php echo esc_html($topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="blog-hero__meta">
                <p class="muted">Published posts</p>
                <div class="hero-count"><?php echo esc_html($post_count); ?></div>
                <p class="muted">Curated for Flutter, React Native, and mobile teams.</p>
            </div>
        </div>
    </section>

    <section class="section blog-list">
        <div class="container blog-layout">
            <div class="blog-main">
                <?php if (have_posts()) : ?>
                    <?php
                    the_post();
                    $featured_id = get_the_ID();
                    $featured_thumb = get_the_post_thumbnail_url($featured_id, 'large');
                    $featured_cats = get_the_category();
                    ?>
                    <article <?php post_class('featured-card'); ?>>
                        <div class="featured-card__body">
                            <div class="post-single__cats">
                                <?php if (!empty($featured_cats)) : ?>
                                    <?php foreach ($featured_cats as $cat) : ?>
                                        <span class="meta-chip"><?php echo esc_html($cat->name); ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <h2 class="featured-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                            <p class="featured-card__meta">
                                <span><?php echo esc_html(get_the_date()); ?></span>
                                <span>·</span>
                                <span><?php echo esc_html(get_the_author()); ?></span>
                            </p>
                            <p class="featured-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 32, '…')); ?></p>
                            <a class="primary-btn" href="<?php the_permalink(); ?>">Read article</a>
                        </div>
                        <?php if ($featured_thumb) : ?>
                            <div class="featured-card__image">
                                <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php if (have_posts()) : ?>
                        <div class="post-grid post-grid--compact">
                            <?php
                            while (have_posts()) :
                                the_post();
                                $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
                                $categories = get_the_category();
                                $primary_category = !empty($categories) ? $categories[0] : null;
                                ?>
                                <article <?php post_class('post-card'); ?>>
                                    <div class="post-card__header">
                                        <div class="post-card__avatar">
                                            <?php echo get_avatar(get_the_author_meta('ID'), 44, '', get_the_author(), ['class' => 'post-card__avatar-img']); ?>
                                        </div>
                                        <div class="post-card__titles">
                                            <h2 class="post-card__title">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h2>
                                            <p class="post-card__subtitle">
                                                <?php echo esc_html(get_the_author()); ?> · <?php echo esc_html(get_the_date()); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <a class="post-card__media" href="<?php the_permalink(); ?>">
                                        <?php if (!empty($primary_category)) : ?>
                                            <span class="post-card__pill"><?php echo esc_html($primary_category->name); ?></span>
                                        <?php endif; ?>
                                        <?php if ($thumb) : ?>
                                            <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                        <?php else : ?>
                                            <span class="post-card__media-placeholder" aria-hidden="true">✦</span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="post-card__content">
                                        <p class="post-card__excerpt">
                                            <?php echo esc_html(wp_trim_words(get_the_excerpt(), 26, '…')); ?>
                                        </p>
                                    </div>
                                    <div class="post-card__actions">
                                        <div class="post-card__action-links">
                                            <a class="post-card__action-link" href="<?php the_permalink(); ?>">Read article</a>
                                            <?php if (!empty($primary_category)) : ?>
                                                <a class="post-card__action-link is-subtle" href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                                                    <?php echo esc_html($primary_category->name); ?>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        <div class="post-card__icon-buttons">
                                            <button class="post-card__icon-btn" type="button" aria-label="Save this post">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                    <path d="M12 20.5s-6.7-4.3-9.3-9.1C1.4 8.1 2.4 4.7 5.1 3.6c2-.8 4.2-.1 5.3 1.6 1.1-1.7 3.3-2.4 5.3-1.6 2.7 1.1 3.7 4.5 2.4 7.8-2.6 4.8-9.1 9.1-9.1 9.1z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                                                </svg>
                                            </button>
                                            <button class="post-card__icon-btn" type="button" aria-label="Share this post">
                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                                                    <circle cx="18" cy="5.5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
                                                    <circle cx="6" cy="12" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
                                                    <circle cx="18" cy="18.5" r="2.5" fill="none" stroke="currentColor" stroke-width="1.6"/>
                                                    <line x1="8.4" y1="13.2" x2="15.6" y2="16.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                    <line x1="15.6" y1="7.2" x2="8.4" y2="10.8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>

                    <div class="posts-pagination">
                        <?php the_posts_pagination([
                            'prev_text' => '← Newer',
                            'next_text' => 'Older →',
                        ]); ?>
                    </div>
                <?php else : ?>
                    <p class="empty-state">No posts found in this archive.</p>
                <?php endif; ?>
            </div>

            <aside class="blog-sidebar">
                <div class="widget widget-newsletter">
                    <h3 class="widget-title">Join My Newsletter</h3>
                    <p class="widget-subtext">Mobile dev tactics, no spam. One email when I publish.</p>
                    <form class="newsletter-form">
                        <input type="email" placeholder="Your email">
                        <button type="submit">Subscribe</button>
                    </form>
                </div>

                <?php if (!empty($topics)) : ?>
                    <div class="widget">
                        <h3 class="widget-title">Topics</h3>
                        <div class="widget-tags">
                            <?php foreach ($topics as $topic) : ?>
                                <?php
                                $is_current = is_category($topic->term_id);
                                $chip_classes = ['blog-chip'];
                                if ($is_current) {
                                    $chip_classes[] = 'is-active';
                                }
                                ?>
                                <a class="<?php echo esc_attr(implode(' ', $chip_classes)); ?>" href="<?php echo esc_url(get_category_link($topic->term_id)); ?>">
                                    <?php echo esc_html($topic->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php
                $popular = new WP_Query([
                    'post_type' => 'post',
                    'posts_per_page' => 4,
                    'post__not_in' => isset($featured_id) ? [$featured_id] : [],
                    'orderby' => 'comment_count',
                    'ignore_sticky_posts' => 1,
                ]);
                ?>
                <?php if ($popular->have_posts()) : ?>
                    <div class="widget widget-popular">
                        <h3 class="widget-title">Popular Articles</h3>
                        <ul class="popular-list">
                            <?php
                            while ($popular->have_posts()) :
                                $popular->the_post();
                                $p_thumb = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                                ?>
                                <li class="popular-item">
                                    <a href="<?php the_permalink(); ?>" class="popular-link">
                                        <?php if ($p_thumb) : ?>
                                            <span class="popular-thumb" style="background-image:url('<?php echo esc_url($p_thumb); ?>');"></span>
                                        <?php else : ?>
                                            <span class="popular-thumb placeholder">✦</span>
                                        <?php endif; ?>
                                        <span class="popular-meta">
                                            <span class="popular-title"><?php the_title(); ?></span>
                                            <span class="popular-date"><?php echo esc_html(get_the_date()); ?></span>
                                        </span>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                <?php wp_reset_postdata(); ?>
            </aside>
        </div>
    </section>
</main>
<?php
get_footer();
