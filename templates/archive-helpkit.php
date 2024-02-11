<?php
/**
 * The template for displaying WPHelpKit Article archive pages.
 *
 * Based on twentyseventeen's archive.php.
 *
 * @since 0.0.1
 * @since 0.0.2 Added prelimiary support for Customizer Live Preview.
 * @since 0.0.5 Rewrote in terms of the `wphelpkit_archive()` template tag.
 * @since 0.2.0 Added ability for themes to override various CSS classes
 *              and element @id's.
 * @since 0.9.0 Introduced 'helpkit_archive_content' action for Index page content.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$classes_and_ids = WPHelpKit_Templates::get_instance()->get_classes_and_ids();

get_header(); ?>

<div id='<?php echo esc_attr($classes_and_ids['ids']['wrap']); ?>' class='wphelpkit-archive-wrap <?php echo esc_attr($classes_and_ids['classes']['wrap']); ?>'>
    <?php if (have_posts()) : ?>
        <header class='<?php echo esc_attr($classes_and_ids['classes']['page_header']); ?>'>
            <?php the_archive_title("<h1 class='{$classes_and_ids['classes']['archive_title']}'>", '</h1>'); ?>
        </header><!-- .page-header -->
    <?php endif; ?>

    <div id='<?php echo esc_attr($classes_and_ids['ids']['primary']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['content_area']); ?>'>
        <main id='<?php echo esc_attr($classes_and_ids['ids']['main']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['site_main']); ?>' role='main'>
    <?php
        do_action('helpkit_archive_content');

    if (have_posts()) {
        wphelpkit_archive();
    } else {
        get_template_part('template-parts/post/content', 'none');
    }
    ?>
        </main><!-- #main -->
    </div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
