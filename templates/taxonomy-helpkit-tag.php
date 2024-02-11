<?php
/**
 * The template for displaying WPHelpKit tag archive pages.
 *
 * Based on twentyseventeen's archive.php.
 *
 * @since 0.0.3
 * @since 0.2.0 Added ability for themes to override various CSS classes
 *              and element @id's.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$classes_and_ids = WPHelpKit_Templates::get_instance()->get_classes_and_ids();

get_header(); ?>

<div id='<?php echo esc_attr($classes_and_ids['ids']['wrap']); ?>' class='wphelpkit-tag-archive <?php echo esc_attr($classes_and_ids['classes']['wrap']); ?>'>

    <?php if (have_posts()) : ?>
        <header class='<?php echo esc_attr($classes_and_ids['classes']['page_header']); ?>'>
            <div class='wphelpkit-prelude'>
                <?php
                    wphelpkit_breadcrumbs();
                    wphelpkit_search_form();
                ?>
            </div>

            <?php
                the_archive_title("<h1 class='{$classes_and_ids['classes']['archive_title']}'>", '</h1>');
                the_archive_description("<div class='taxonomy-description'>", '</div>');
            ?>
        </header><!-- .page-header -->
    <?php endif; ?>

    <div id='<?php echo esc_attr($classes_and_ids['ids']['primary']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['content_area']); ?>'>
        <main id='<?php echo esc_attr($classes_and_ids['ids']['main']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['site_main']); ?>' role='main'>

        <?php
        if (have_posts()) {
            $args = array(
                'tag' => get_queried_object_id(),
            );
            wphelpkit_tag_archive($args);
        } else {
            require('template-parts/article/content-none.php');
        }
        ?>

        </main><!-- #main -->
    </div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
