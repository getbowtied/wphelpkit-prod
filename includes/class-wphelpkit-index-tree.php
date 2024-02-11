<?php

defined('ABSPATH') || die;

/**
 * Help index tree.
 *
 * @since 0.9.2
 */
class WPHelpKit_Index_Tree
{
    /**
     * Our static instance.
     *
     * @since 0.0.2
     *
     * @var WPHelpKit_Index_Tree
     */
    private static $instance;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.9.2
     *
     * @return WPHelpKit_Index_Tree
     */
    public static function get_instance()
    {
        if (! self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initialize our static instance and add hooks.
     *
     * @since 0.9.2
     *
     * @return WPHelpKit_Index_Tree
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        $this->add_hooks();
    }

    /**
     * Add hooks.
     *
     * @since 0.9.2
     *
     * @return void
     */
    public function add_hooks()
    {
        add_action('wphelpkit_index_tree', array( $this, 'wphelpkit_index_tree' ), 0);

        return;
    }

    /**
     * Output the help articles' index tree.
     *
     * @since 0.9.2
     *
     * @return string the index tree html.
     */
    public function wphelpkit_index_tree() {

        $current_id = null;

        if( !is_tax(WPHelpKit_Article_Category::$category) &&
            (!is_search() || WPHelpKit_Article::$post_type !== get_query_var('post_type')) &&
            !is_tax(WPHelpKit_Article_Tag::$tag) &&
            !is_singular(WPHelpKit_Article::$post_type) ) {
            return;
        }

        if( ( 'yes' === WPHelpKit_Settings::get_instance()->get_option('category_index_tree') ) &&
        ( is_tax(WPHelpKit_Article_Tag::$tag) || ( is_search() && WPHelpKit_Article::$post_type === get_query_var('post_type') ) ) ) {
            return;
        }

        if( is_singular(WPHelpKit_Article::$post_type) ) {
            global $post;
            $current_id = $post->ID;
        } else {
            $current_id = isset(get_queried_object()->term_id) ? get_queried_object()->term_id : 0;
        }

        $category = $this->get_parent_category( $current_id );
        $category_id = isset($category->term_id) ? $category->term_id : 0;

        $categories = $this->get_category_hierarchy( $category_id );

        if( ( 'yes' === WPHelpKit_Settings::get_instance()->get_option('category_index_tree') ) && isset($category->term_id) ) {
            $categories = array(
                array(
                    'id'        => $category->term_id,
                    'name'      => $category->name,
                    'children'  => $categories,
                    'expanded'  => true
                )
            );
        }

        $html = $this->get_index_tree_html( '', $categories, $current_id );

        echo

        '<div class="wphelpkit-index-tree">
            <div class="wphelpkit-index-tree-wrapper">
                <ul>
                    ' . wp_kses_post($html) . '
                </ul>
            </div>
        </div>';

        return;
    }

    /**
     * Get the parent category for the index tree, based on the specific category option.
     *
     * @since 0.9.2
     *
     * @param int $id current post's id.
     *
     * @return int category's id.
     */
    public function get_parent_category( $id ) {

        if( 'yes' !== WPHelpKit_Settings::get_instance()->get_option('category_index_tree') ) {
            return false;
        }

        $term = '';
        $taxonomy = '';
        // get the "current" term, depending on whether we're in a taxonomy
        // archive or a single post
        if (is_tax(WPHelpKit_Article_Category::$category)) {
            // save the category taxonomy, to ensure it gets used throughout
            $taxonomy = WPHelpKit_Article_Category::$category;
            $term = get_term_by('term_id', $id, $taxonomy);
        } elseif (is_singular(WPHelpKit_Article::$post_type)) {
            $taxonomy = WPHelpKit_Article_Category::$category;
            $term = wp_get_post_terms($id, $taxonomy);
            if ($term[0]) {
                $term = $term[0];
            }
        }

        if( $term && $taxonomy ) {
            while (isset($term->parent) && $term->parent > 0) {
                $term = get_term($term->parent, $taxonomy);
            }
        }

        return $term;
    }

    /**
     * Get the subcategories for the index tree.
     *
     * @since 0.9.2
     *
     * @param $parent the parent category's id.
     *
     * @return array list of category's children.
     */
    public function get_category_hierarchy( $parent = 0 ) {

        $args = array(
            'taxonomy' => WPHelpKit_Article_Category::$category,
            'parent' => $parent,
            'hide_empty' => true,
            'hierarchical' => true,
            'orderby' => WPHelpKit_Article::$display_order_meta_key,
            'pagination' => true
        );

    	$terms = get_terms( $args );

    	$children = array();
    	foreach ( $terms as $term ){
    		$term->children = $this->get_category_hierarchy( $term->term_id );
            $term_id = $term->term_id;
    		$children[$term_id] = array(
                'id'        => $term->term_id,
                'name'      => $term->name,
                'children'  => $term->children,
                'expanded'  => false
            );
    	}

    	return $children;
    }

    /**
     * Get the index tree html.
     *
     * @since 0.9.2
     *
     * @param string $html containing the html of children to be output in the index tree.
     * @param array $categories array of subcategories.
     *
     * @return string the index tree list html.
     */
    public function get_index_tree_html( $html = '', $categories = array(), $id = null ) {

        if( !is_array($categories) || empty($categories) ) {
            return '';
        }

        foreach($categories as $category) {

            $articles = get_posts(
                array(
                    'post_type' => WPHelpKit_Article::$post_type,
                    'post_status' => 'publish',
                    'posts_per_page' => -1,
                    'orderby' => 'meta_value_num title',
                    'order' => 'ASC',
                    'meta_key' => WPHelpKit_Article::get_instance()->display_order_meta_key($category['id']),
                    'tax_query' => array(
                        array(
                            'taxonomy' => WPHelpKit_Article_Category::$category,
                            'field'    => 'term_id',
                            'terms'    => $category['id'],
                            'include_children' => false,
                        )
                    )
                )
            );

            $parent_classes = $category['expanded'] ? ' parent-category expand' : '';
            $expand_arrow = $category['expanded'] ? '' : '<span class="wphelpkiticons wphelpkiticons-expand-arrow"></span>';
            $articles_html = '';
            $article_html = '';
            if( is_array($articles) && !empty($articles) ) {
                foreach( $articles as $article ) {
                    $classes = '';
                    if( $article->ID === $id ) {
                        $classes = ' expand';
                        $parent_classes = ' expand';
                    }
                    $article_html .= sprintf(
                        '<li class="wphelpkit-index-tree-article %s%s"><a href="%s">%s</a></li>',
                        'article-' . $article->ID,
                        $classes,
                        get_post_permalink($article->ID),
                        $article->post_title
                    );
                }
                $articles_html = sprintf(
                    '<ul class="wphelpkit-index-tree-articles-list">%s</ul>',
                    $article_html
                );
            }

            if( $category['id'] === $id ) {
                $classes = ' expand';
                $parent_classes .= ' expand active';
            }

            $children = $this->get_index_tree_html( '', $category['children'], $id );

            if( $children ) {
                $html .= sprintf(
                    '<li class="wphelpkit-index-tree-subcategory has-children%s"><span class="subcategory-title">%s<span>%s</span></span><ul class="wphelpkit-index-tree-subcategories-list">%s</ul>%s</li>',
                    $parent_classes,
                    $expand_arrow,
                    $category['name'],
                    $children,
                    $articles_html
                );
            } else {
                $html .= sprintf(
                    '<li class="wphelpkit-index-tree-subcategory has-children%s"><span class="subcategory-title">%s<span>%s</span></span>%s</li>',
                    $parent_classes,
                    $expand_arrow,
                    $category['name'],
                    $articles_html
                );
            }
        }

        return $html;
    }
}
