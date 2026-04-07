<?php
/**
 * Template Name: Blog Posts
 * Description: Use this template to show the blog feed on a dedicated page.
 */

get_header();

$subtitle = get_the_excerpt() ?: 'Mobile engineering, product lessons, and shipping better apps.';
$paged = max(1, get_query_var('paged'), get_query_var('page'));
$total_posts = (int) wp_count_posts()->publish;

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
                                <span class="blog-chip__count"><?php echo (int) $topic->count; ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="blog-hero__meta">
                <p class="muted">Published posts</p>
                <div class="hero-count"><?php echo $total_posts; ?>+</div>
                <p class="muted">Curated for Flutter, React Native, and mobile teams.</p>
                <form class="blog-search" role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
                    <label class="screen-reader-text" for="blog-search-input">Search articles</label>
                    <input id="blog-search-input" class="blog-search__input" type="search" name="s" placeholder="Search articles" value="<?php echo esc_attr(get_search_query()); ?>" autocomplete="off">
                    <button class="blog-search__btn" type="submit">Search</button>
                </form>
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
                                        <a class="meta-chip" href="<?php echo esc_url(get_category_link($cat->term_id)); ?>">
                                            <?php echo esc_html($cat->name); ?>
                                            <span class="meta-chip__count"><?php echo (int) $cat->count; ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <h2 class="featured-card__title"><a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a></h2>
                            <p class="featured-card__meta">
                                <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                                <span>·</span>
                                <span><?php echo esc_html(get_the_author()); ?></span>
                            </p>
                            <p class="featured-card__excerpt"><?php echo esc_html(wp_trim_words(get_the_excerpt(), 32, '…')); ?></p>
                            <a class="primary-btn" href="<?php the_permalink(); ?>">Read full article</a>
                        </div>
                        <?php if ($featured_thumb) : ?>
                            <div class="featured-card__image">
                                <img src="<?php echo esc_url($featured_thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                            </div>
                        <?php endif; ?>
                    </article>

                    <?php if ($posts_query->have_posts()) : ?>
                        <?php
                        $posts_page_id = (int) get_option('page_for_posts');
                        $posts_page_link = $posts_page_id ? get_permalink($posts_page_id) : home_url('/');
                        ?>
                        <div class="blog-toolbar">
                            <div>
                                <h2 class="blog-toolbar__title">Latest articles</h2>
                                <p class="blog-toolbar__subtitle">Straight to the point insights with zero fluff.</p>
                            </div>
                            <a class="blog-toolbar__link" href="<?php echo esc_url($posts_page_link); ?>">Browse archive</a>
                        </div>
                        <div class="post-grid post-grid--compact">
                            <?php
                            while ($posts_query->have_posts()) :
                                $posts_query->the_post();
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
                                                <a href="<?php the_permalink(); ?>" rel="bookmark"><?php the_title(); ?></a>
                                            </h2>
                                            <p class="post-card__subtitle">
                                                <?php echo esc_html(get_the_author()); ?> · <time datetime="<?php echo esc_attr(get_the_date('c')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="post-card__media">
                                        <?php if (!empty($primary_category)) : ?>
                                            <a class="post-card__pill" href="<?php echo esc_url(get_category_link($primary_category->term_id)); ?>">
                                                <?php echo esc_html($primary_category->name); ?>
                                                <span class="post-card__pill-count"><?php echo (int) $primary_category->count; ?></span>
                                            </a>
                                        <?php endif; ?>
                                        <a class="post-card__media-link" href="<?php the_permalink(); ?>">
                                            <?php if ($thumb) : ?>
                                                <img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
                                            <?php else : ?>
                                                <span class="post-card__media-placeholder" aria-hidden="true">✦</span>
                                            <?php endif; ?>
                                        </a>
                                    </div>
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
                        <label class="screen-reader-text" for="newsletter-email">Email address</label>
                        <input id="newsletter-email" type="email" name="email" placeholder="Your email" autocomplete="email">
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
                                    <span class="blog-chip__count"><?php echo (int) $topic->count; ?></span>
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
