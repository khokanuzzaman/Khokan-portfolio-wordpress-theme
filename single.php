<?php
/**
 * Single post template.
 */

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
        $categories = get_the_category();
        $tags = get_the_tags();
        $share_url = urlencode(get_permalink());
        $share_title = urlencode(get_the_title());
        $popular = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post__not_in' => [get_the_ID()],
            'ignore_sticky_posts' => 1,
        ]);
        ?>
        <main class="section post-layout">
            <div class="container">
                <div class="post-grid">
                    <div class="post-main">
                        <div class="post-hero-card" <?php echo $thumb ? 'style="--post-hero:url(' . esc_url($thumb) . ');"' : ''; ?>>
                            <div class="post-hero__top">
                                <?php if (!empty($categories)) : ?>
                                    <div class="post-single__cats">
                                        <?php foreach ($categories as $cat) : ?>
                                            <span class="meta-chip"><?php echo esc_html($cat->name); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                                <h1 class="post-single__title"><?php the_title(); ?></h1>
                                <p class="post-single__meta">
                                    <span><?php echo esc_html(get_the_date()); ?></span>
                                    <span>·</span>
                                    <span><?php echo esc_html(get_the_author()); ?></span>
                                </p>
                            </div>
                            <?php if ($thumb) : ?>
                                <div class="post-single__image">
                                    <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="post-body">
                            <?php the_content(); ?>
                        </div>

                        <div class="post-share">
                            <span>Share:</span>
                            <div class="share-buttons">
                                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $share_url; ?>" target="_blank" rel="noopener">Facebook</a>
                                <a href="https://twitter.com/intent/tweet?url=<?php echo $share_url; ?>&text=<?php echo $share_title; ?>" target="_blank" rel="noopener">X / Twitter</a>
                                <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo $share_url; ?>" target="_blank" rel="noopener">LinkedIn</a>
                            </div>
                        </div>

                        <?php if (!empty($tags)) : ?>
                            <div class="post-tags">
                                <?php foreach ($tags as $tag) : ?>
                                    <span class="stack-chip"><?php echo esc_html($tag->name); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="post-nav">
                            <div class="post-nav__prev"><?php previous_post_link('%link', '← %title'); ?></div>
                            <div class="post-nav__next"><?php next_post_link('%link', '%title →'); ?></div>
                        </div>
                    </div>

                    <aside class="post-sidebar">
                        <div class="ad-card">
                            <p class="ad-label">Sponsored</p>
                            <div class="ad-placeholder">728 x 90</div>
                        </div>

                        <div class="widget widget-social">
                            <h3 class="widget-title">Follow</h3>
                            <div class="widget-social__links">
                                <a href="https://facebook.com" target="_blank" rel="noopener">Facebook</a>
                                <a href="https://twitter.com" target="_blank" rel="noopener">Twitter</a>
                                <a href="https://www.linkedin.com" target="_blank" rel="noopener">LinkedIn</a>
                                <a href="<?php echo esc_url(home_url('/feed/')); ?>" target="_blank" rel="noopener">RSS</a>
                            </div>
                        </div>

                        <div class="widget widget-newsletter">
                            <h3 class="widget-title">Join My Newsletter</h3>
                            <p class="widget-subtext">Sign up for a round-up of mobile dev tips.</p>
                            <form class="newsletter-form">
                                <input type="email" placeholder="Your email">
                                <button type="submit">Subscribe</button>
                            </form>
                        </div>

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
            </div>
        </main>
        <?php
    endwhile;
endif;

get_footer();
