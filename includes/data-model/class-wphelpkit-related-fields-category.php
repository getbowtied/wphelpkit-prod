<?php

defined('ABSPATH') || die;

/**
 * Manage related articles by showing up to 5 random articles in the same category.
 *
 * @since 0.0.5
 * @since 0.1.1 Renamed class to WPHelpKit_Related_Articles_Category.
 */
class WPHelpKit_Related_Articles_Category extends WPHelpKit_Related_Articles
{
    /**
     * Retrieve list of related articles.
     *
     * Picks a random category assigned to `$post`.  Then retrieves up to 5
     * articles in that category (not including `$post`) and displays them
     * in a random order.
     *
     * @since 0.0.5
     *
     * @param WP_Post|int $post Optional. Post ID or WP_Post object. Default is global $post.
     * @return array array of article post IDs.
     */
    public function get_related_articles($post = null)
    {
        $post = get_post($post);
        if (get_post_type($post) !== WPHelpKit_Article::$post_type) {
            // not an article, so no related articles.
            return array();
        }

        // pick a random catgeory from those assigned to `$post`.
        $the_categories = get_the_terms($post, WPHelpKit_Article_Category::$category);
        if (empty($the_categories) || is_wp_error($the_categories)) {
            // no categories assigned.  Shouldn't happen but just in case.
            return array();
        }
        $category = $the_categories[ array_rand($the_categories, 1) ];

        $args = array(
            'tax_query' => array(
                array(
                    'taxonomy'         => WPHelpKit_Article_Category::$category,
                    'field'            => 'id',
                    'terms'            => $category->term_id,
                    'include_children' => false,
                ),
            ),
            'posts_per_page'           => 5,
            'post__not_in'             => array( $post->ID ),
            'fields'                   => 'ids',
            'orderby'                  => 'rand',
        );

        $related_articles = WPHelpKit_Article::get_instance()->get_posts($args);

        if (! empty($related_articles) &&
                ( $related_articles[0] instanceof WP_Post || is_array($related_articles[0]) ) ) {
            $related_articles = wp_list_pluck($related_articles, 'ID');
        }

        return $related_articles;
    }
}
