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
        $share_url = rawurlencode(get_permalink());
        $share_title = rawurlencode(html_entity_decode(get_the_title(), ENT_QUOTES, 'UTF-8'));
        $share_media = $thumb ? rawurlencode($thumb) : '';
        $popular = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post__not_in' => [get_the_ID()],
            'ignore_sticky_posts' => 1,
        ]);

        $share_links = [
            [
                'label' => 'Facebook',
                'href' => "https://www.facebook.com/sharer/sharer.php?u={$share_url}",
            ],
            [
                'label' => 'X / Twitter',
                'href' => "https://twitter.com/intent/tweet?url={$share_url}&text={$share_title}",
            ],
            [
                'label' => 'LinkedIn',
                'href' => "https://www.linkedin.com/sharing/share-offsite/?url={$share_url}",
            ],
            [
                'label' => 'WhatsApp',
                'href' => "https://api.whatsapp.com/send?text={$share_title}%20{$share_url}",
            ],
            [
                'label' => 'Telegram',
                'href' => "https://t.me/share/url?url={$share_url}&text={$share_title}",
            ],
            [
                'label' => 'Reddit',
                'href' => "https://www.reddit.com/submit?url={$share_url}&title={$share_title}",
            ],
            [
                'label' => 'Pinterest',
                'href' => "https://pinterest.com/pin/create/button/?url={$share_url}&media={$share_media}&description={$share_title}",
            ],
        ];
        $share_links = array_filter($share_links, function ($item) {
            return !empty($item['href']);
        });

        $social_profiles = [
            ['label' => 'Facebook', 'href' => get_theme_mod('khokan_social_facebook', '')],
            ['label' => 'Twitter / X', 'href' => get_theme_mod('khokan_social_twitter', '')],
            ['label' => 'LinkedIn', 'href' => get_theme_mod('khokan_social_linkedin', '')],
            ['label' => 'Instagram', 'href' => get_theme_mod('khokan_social_instagram', '')],
            ['label' => 'YouTube', 'href' => get_theme_mod('khokan_social_youtube', '')],
            ['label' => 'Telegram', 'href' => get_theme_mod('khokan_social_telegram', '')],
            ['label' => 'WhatsApp', 'href' => get_theme_mod('khokan_social_whatsapp', '')],
        ];
        $social_profiles = array_values(array_filter($social_profiles, function ($item) {
            return !empty($item['href']) && $item['href'] !== '#';
        }));
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
                                <?php foreach ($share_links as $share) : ?>
                                    <a href="<?php echo esc_url($share['href']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($share['label']); ?>
                                    </a>
                                <?php endforeach; ?>
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
                            <div class="post-nav__prev"><?php previous_post_link('%link', '← %title'); ?>
							</div>
                            <div class="post-nav__next"><?php next_post_link('%link', '%title →'); ?></div>
                        </div>
						<?php if (comments_open() || get_comments_number()) : ?>
                            <div class="post-comments">
                                <?php comments_template(); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <aside class="post-sidebar">
                        <div class="ad-card">
                            <p class="ad-label">Sponsored</p>
                            <div class="ad-placeholder">728 x 90</div>
                        </div>

                        <div class="widget widget-social">
                            <h3 class="widget-title">Follow</h3>
                            <div class="widget-social__links">
                                <?php foreach ($social_profiles as $profile) : ?>
                                    <a href="<?php echo esc_url($profile['href']); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php echo esc_html($profile['label']); ?>
                                    </a>
                                <?php endforeach; ?>
                                <a href="<?php echo esc_url(home_url('/feed/')); ?>" target="_blank" rel="noopener noreferrer">RSS</a>
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
