<?php
/**
 * The template for displaying WPHelpKit category archive pages.
 *
 * Based on twentyseventeen's archive.php.
 *
 * @since 0.0.1
 * @since 0.0.2 Added prelimiary support for Customizer Live Preview.
 * @since 0.0.3 Ensure a category with children but no articles displays the children.
 * @since 0.2.0 Added ability for themes to override various CSS classes
 *              and element @id's.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$args = array(
    'child_of' => get_queried_object_id(),
);
$children = WPHelpKit_Article_Category::get_instance()->get_categories($args);

$classes_and_ids = WPHelpKit_Templates::get_instance()->get_classes_and_ids();

get_header(); ?>

<div id='<?php echo esc_attr($classes_and_ids['ids']['wrap']); ?>' class='wphelpkit-category-archive <?php echo esc_attr($classes_and_ids['classes']['wrap']); ?>'>

    <?php if (have_posts() || ! empty($children)) : ?>
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
        if (have_posts() || ! empty($children)) {
            $args = array(
                'category'        => get_queried_object_id(),
                'header'          => false,
                'display_posts'   => true,
                'pagination'      => true,
            );
            wphelpkit_category_archive($args);
        } else {
            require('template-parts/article/content-none.php');
        }
        ?>

        </main><!-- #main -->
    </div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
