<?php
/**
 * Default page template.
 */

get_header();

if (have_posts()) :
    while (have_posts()) :
        the_post();
        $thumb = get_the_post_thumbnail_url(get_the_ID(), 'large');
        ?>
        <main class="section page-default">
            <div class="container page-hero">
                <h1 class="page-title"><?php the_title(); ?></h1>
                <?php if ($thumb) : ?>
                    <div class="page-hero__image">
                        <img src="<?php echo esc_url($thumb); ?>" alt="<?php the_title_attribute(); ?>">
                    </div>
                <?php endif; ?>
            </div>
            <div class="container page-content">
                <div class="page-body">
                    <?php the_content(); ?>
                </div>
            </div>
        </main>
        <?php
    endwhile;
endif;

get_footer();
