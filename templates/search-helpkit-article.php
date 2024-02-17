<?php
/**
 * The template for displaying search results pages
 *
 * Based on twentyseventeen's search.php.
 *
 * @since 0.0.2
 * @since 0.2.0 Added ability for themes to override various CSS classes
 *              and element @id's.
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$classes_and_ids = WPHelpKit_Templates::get_instance()->get_classes_and_ids();

get_header(); ?>

<div id='<?php echo esc_attr($classes_and_ids['ids']['wrap']); ?>' class='wphelpkit-search-archive <?php echo esc_attr($classes_and_ids['classes']['wrap']); ?>'>
    <header class='<?php echo esc_attr($classes_and_ids['classes']['page_header']); ?>'>
        <div class='wphelpkit-prelude'>
<?php
            wphelpkit_breadcrumbs();
            wphelpkit_search_form();
?>
        </div>
        <?php if (have_posts()) : ?>
            <h1 class='page-title'><?php printf(esc_html__('Search Results for &ldquo;%s&rdquo;', 'wphelpkit'), '<span>' . esc_attr( get_search_query() ) . '</span>'); ?></h1>
        <?php else : ?>
            <h1 class='page-title'><?php esc_html_e('Nothing Found', 'wphelpkit') ?></h1>
        <?php endif; ?>
    </header><!-- .page-header -->

    <div id='<?php echo esc_attr($classes_and_ids['ids']['primary']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['content_area']); ?>'>
        <main id='<?php echo esc_attr($classes_and_ids['ids']['main']); ?>' class='<?php echo esc_attr($classes_and_ids['classes']['site_main']); ?>' role='main'>

<?php
if (have_posts()) :
    /* Start the Loop */

    $class = apply_filters('wphelpkit-search-results-class', array( 'entry-content' ));
    $class = array_merge(array( 'wphelpkit-search-results' ), $class);
    $class = implode(' ', $class);
    ?>
            <ul class='<?php echo esc_attr($class); ?>'>
    <?php
    while (have_posts()) :
        the_post();
        ?>
                <li class='wphelpkit-article'>
                    <span class='wphelpkiticons wphelpkiticons-article'></span>
                    <a href='<?php echo esc_url(get_the_permalink()); ?>' class="wphelpkit-article-title"><?php echo wp_kses_post(get_the_title()); ?></a>
                    <?php
                    $categories = get_the_terms($post, WPHelpKit_Article_Category::$category);
                    if (is_array($categories)) {
                        $categories_list = '';
                        foreach ($categories as $category) {
                            $categories_list .= sprintf('<span>%s</span>', $category->name);
                            if( next( $categories ) ) {
                                $categories_list .= ', ';
                            }
                        }
                        echo sprintf('<span class="wphelpkit-categories">%s %s</span>', esc_html__('in', 'wphelpkit'), wp_kses_post($categories_list));
                    }
                    if( !empty($article_excerpt=get_the_excerpt(get_the_ID())) ) {
                        echo sprintf('<p class="wphelpkit-article-excerpt">%s</p>', wp_trim_words(wp_kses_post($article_excerpt), 20));
                    }
                    ?>
                </li>
        <?php
    endwhile; // End of the loop.
    ?>
            </ul>
    <?php
    echo apply_filters( 'wphelpkit-search-pagination', wp_kses_post( get_the_posts_pagination() ) );
else : ?>
            <p><?php esc_html_e('Sorry, but nothing matched your search terms. Please try again with some different keywords.', 'wphelpkit'); ?></p>
            <?php
endif;
?>

        </main><!-- #main -->
    </div><!-- #primary -->
</div><!-- .wrap -->

<?php get_footer();
