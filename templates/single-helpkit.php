<?php
/**
 * The template for displaying WPHelpKit single article pages.
 *
 * Based on twentyseventeen's single.php.
 *
 * @since 0.0.2
 * @since 0.0.3 Added support for article voting, view counts and commenting.
 * @since 0.2.0 Added ability for themes to override various CSS classes
 *              and element @id's.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$classes_and_ids = WPHelpKit_Templates::get_instance()->get_classes_and_ids();

get_header(); ?>

<div id='<?php echo esc_attr($classes_and_ids['ids']['wrap']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['wrap']); ?>'>
    <div id='<?php echo esc_attr($classes_and_ids['ids']['primary']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['content_area']); ?>'>
        <main id='<?php echo esc_attr($classes_and_ids['ids']['main']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['site_main']); ?>' role='main'>
            <?php
            /* Start the Loop */
            while (have_posts()) :
                the_post();

                /**
                 * Filters the WPHelpKit template part used.
                 *
                 * @since 0.6.4
                 *
                 * @param string $template_part.
                 */
                $template_part = apply_filters('wphelpkit-template-part', 'template-parts/article/content.php');
                require $template_part;

                // If comments are open or we have at least one comment, load up the comment template.
                if (( comments_open() || get_comments_number() ) || is_customize_preview()) {
                    comments_template();
                }
            endwhile; // End of the loop.
            ?>

        </main><!-- #main -->
    </div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
