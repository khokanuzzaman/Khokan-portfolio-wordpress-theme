<?php
/**
 * Template Name: Blog Posts
 * Description: Use this template to show the blog feed on a dedicated page.
 */

get_header();

$subtitle = get_the_excerpt() ?: 'Mobile engineering, product lessons, and shipping better apps.';
$paged = max(1, get_query_var('paged'), get_query_var('page'));

$posts_query = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'paged' => $paged,
]);
$topics = get_categories([
    'orderby' => 'count',
    'order' => 'DESC',
    'number' => 8,
]);
?>
<main>
    <section class="section blog-hero">
        <div class="container blog-hero__grid">
            <div>
                <p class="eyebrow">Insights & Updates</p>
                <h1 class="blog-title"><?php the_title(); ?></h1>
                <p class="section-subtitle"><?php echo esc_html($subtitle); ?></p>
                <?php if (!empty($topics)) : ?>
                    <div class="blog-chips">
                        <?php foreach ($topics as $topic) : ?>
                            <a class="blog-chip" href="<?php echo esc_url(get_category_link($topic->term_id)); ?>">
                                <?php echo esc_html($topic->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="blog-hero__meta">
                <p class="muted">Published posts</p>
                <div class="hero-count"><?php echo (int) wp_count_posts()->publish; ?>+</div>
                <p class="muted">Curated for Flutter, React Native, and mobile teams.</p>
            </div>
        </div>
    </section>

    <section class="section blog-list">
        <div class="container blog-layout">
            <div class="blog-main">
                <?php if ($posts_query->have_posts()) : ?>
                    <?php
                    $posts_query->the_post();
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
                                <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php the_title_attribute(); ?>">
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php if ($posts_query->have_posts()) : ?>
                        <div class="post-grid post-grid--compact">
                            <?php
                            while ($posts_query->have_posts()) :
                                $posts_query->the_post();
                                $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
                                $categories = get_the_category();
                                ?>
                                <article <?php post_class('post-card post-card--compact'); ?>>
                                    <a class="post-card__image" href="<?php the_permalink(); ?>">
                                        <?php if ($thumb) : ?>
                                            <span class="post-card__thumb" style="background-image:url('<?php echo esc_url($thumb); ?>');"></span>
                                        <?php else : ?>
                                            <span class="post-card__placeholder">✦</span>
                                        <?php endif; ?>
                                    </a>
                                    <div class="post-card__body">
                                        <?php if (!empty($categories)) : ?>
                                            <div class="post-card__cats">
                                                <?php foreach ($categories as $cat) : ?>
                                                    <span class="meta-chip"><?php echo esc_html($cat->name); ?></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        <h2 class="post-card__title">
                                            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                        </h2>
                                        <p class="post-card__excerpt">
                                            <?php echo esc_html(wp_trim_words(get_the_excerpt(), 22, '…')); ?>
                                        </p>
                                        <div class="post-card__meta">
                                            <span><?php echo esc_html(get_the_date()); ?></span>
                                            <a class="read-link" href="<?php the_permalink(); ?>">Read →</a>
                                        </div>
                                    </div>
                                </article>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>

                    <?php
                    $pagination = paginate_links([
                        'total' => $posts_query->max_num_pages,
                        'current' => $paged,
                        'mid_size' => 2,
                        'prev_text' => '← Newer',
                        'next_text' => 'Older →',
                    ]);
                    if ($pagination) :
                        ?>
                        <div class="posts-pagination">
                            <?php echo $pagination; ?>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <p class="empty-state">No posts published yet.</p>
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
                                <a class="blog-chip" href="<?php echo esc_url(get_category_link($topic->term_id)); ?>">
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
wp_reset_postdata();
get_footer();
