<?php

defined( 'ABSPATH' ) || die;
/**
 * All things search.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed class to WPHelpKit_Search.  Removed ability to set `HTTP Method` in search form (always use GET).
 */
class WPHelpKit_Search
{
    /**
     * Our static instance.
     *
     * @since 0.0.2
     *
     * @var WPHelpKit_Search
     */
    private static  $instance ;
    /**
     * Our ajax action.
     *
     * @since 0.0.2
     *
     * @var string
     */
    public static  $action = 'wphelpkit-autocomplete' ;
    /**
     * Our shortcode.
     *
     * @since 0.0.2
     *
     * @var string
     */
    public static  $shortcode = 'wphelpkit_search_form' ;
    /**
     * Text to use as the placeholder in our search form.
     *
     * @since 0.2.0
     *
     * @var string
     */
    protected  $placeholder_text ;
    /**
     * Text to use for searching articles by name in WHERE clause.
     *
     * @since 0.9.2
     *
     * @var string
     */
    protected  $search_string ;
    /**
     * Our block.
     *
     * @since 0.6.2
     *
     * @var string
     */
    public static  $block = 'wphelpkit/search' ;
    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.2
     *
     * @return WPHelpKit_Search
     */
    public static function get_instance()
    {
        if ( !self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor.
     *
     * Initialize our static instance and add hooks.
     *
     * @since 0.0.1
     *
     * @return WPHelpKit_Search
     */
    public function __construct()
    {
        if ( self::$instance ) {
            return self::$instance;
        }
        self::$instance = $this;
        /**
         * Filters the text used for the placeholder in the search form.
         *
         * @since 0.2.0
         *
         * @param string $placeholder Placeholder text.
         */
        $this->placeholder_text = apply_filters( 'wphelpkit-search-placeholder-text', esc_html__( 'Search Help Articles', 'wphelpkit' ) );
        $this->add_hooks();
    }
    
    /**
     * Add hooks.
     *
     * @since 0.0.2
     * @since 0.9.0 Added posts_search filter.
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_action( 'init', array( $this, 'add_shortcode' ) );
        add_action( 'init', array( $this, 'register_block' ) );
        add_filter(
            'posts_search',
            array( $this, 'search_articles_by_title_or_tags' ),
            500,
            2
        );
        return;
    }
    
    /**
     * Register our search block.
     *
     * @since 0.0.5
     *
     * @return void
     */
    public function register_block()
    {
        if ( !function_exists( 'register_block_type' ) ) {
            return;
        }
        register_block_type( self::$block, array(
            'attributes'      => array(
            'placeholder' => array(
            'selector'  => 'input[name="s"]',
            'attribute' => 'placeholder',
            'type'      => 'string',
            'default'   => $this->placeholder_text,
        ),
            'submit'      => array(
            'selector'  => 'input[type="submit"]',
            'attribute' => 'value',
            'type'      => 'string',
        ),
        ),
            'editor_script'   => 'wphelpkit-search-block-editor',
            'editor_style'    => 'wphelpkit-search-block-editor',
            'render_callback' => array( $this, 'render_search_block' ),
        ) );
        return;
    }
    
    /**
     * Render callback for our search block.
     *
     * @since 0.0.5
     * @since 0.9.0 Wrap block's content using 'wp-block-' class
     *
     * @param array $attributes
     * @return string
     */
    public function render_search_block( $attributes )
    {
        WPHelpKit_Templates::load_template_tags();
        $search_form = wphelpkit_get_search_form( $attributes );
        return '<div class="wp-block-helpkit-search">' . $search_form . '</div>';
    }
    
    /**
     * Sort the results of the ajax search.
     *
     * @since 0.0.2
     *
     * @param WP_Post $a
     * @param WP_Post $b
     * @return int An integer less than, equal to, or greater than zero
     *             if the first argument is considered to be respectively
     *             less than, equal to, or greater than the second.
     */
    protected function sort_ajax_results( $a, $b )
    {
        return strcasecmp( $a->post_title, $b->post_title );
    }
    
    /**
     * Add our search shortcode.
     *
     * @since 0.0.2
     *
     * @return void
     *
     * @action init
     */
    public function add_shortcode()
    {
        add_shortcode( self::$shortcode, array( $this, 'search_shortcode' ) );
        return;
    }
    
    /**
     * Generate the output for our search shortcode.
     *
     * @since 0.0.2
     * @since 0.1.1 Removed `$content` and `$shortcode_tag` params.
     * @since 0.2.3 Do not pre-populate the search box with any previous search
     *              terms, as they are now shown in the breadcrumbs on the
     *              search results page.
     *
     * @param array $attrs Keys are shortcode attribute names, values are
     *                     shortcode attribute values.
     * @return void Echo's it's output.
     */
    public function search_shortcode( $attrs )
    {
        global  $post ;
        $display = WPHelpKit_Settings::get_instance()->get_option( 'search' );
        
        if ( !is_customize_preview() ) {
            if ( !$display ) {
                return;
            }
            $display = '';
        } else {
            $display = ( $display ? '' : ' style="display: none"' );
        }
        
        $category_field = '';
        
        if ( 'yes' === WPHelpKit_Settings::get_instance()->get_option( 'search_in_category' ) ) {
            $taxonomy = WPHelpKit_Article_Category::$category;
            
            if ( is_tax( $taxonomy ) ) {
                $term = get_term_by( 'slug', get_query_var( 'term' ), $taxonomy );
                $category_slug = $term->slug;
                $category_field = '<input type="hidden" name="' . $taxonomy . '" id="' . $taxonomy . '" value="' . $category_slug . '" />';
            }
            
            
            if ( is_singular( WPHelpKit_Article::$post_type ) ) {
                $term = wp_get_post_terms( $post->ID, $taxonomy );
                if ( $term[0] ) {
                    $_term = $term[0];
                }
                while ( isset( $_term->parent ) && $_term->parent > 0 ) {
                    $_term = get_term( $_term->parent, $taxonomy );
                }
                $category_slug = $_term->slug;
                $category_field = '<input type="hidden" name="' . $taxonomy . '" id="' . $taxonomy . '" value="' . $category_slug . '" />';
            }
        
        }
        
        $default_attrs = array(
            'autocomplete' => true,
            'placeholder'  => $this->placeholder_text,
        );
        $attrs = shortcode_atts( $default_attrs, $attrs );
        $action = home_url();
        $post_type = WPHelpKit_Article::$post_type;
        $disable_submit = $button_classes = '';
        
        if ( WPHelpKit::is_gutenberg_preview() ) {
            $disable_submit = " onsubmit='return false;'";
            $button_classes = " class='wphelpkit-search components-button is-button is-default is-large'";
        }
        
        return '<form id="wphelpkit-search-form" method="GET" ' . $display . ' action="' . $action . '" ' . $disable_submit . '>' . '<input type="text" id="wphelpkit-search" name="s" placeholder="' . $attrs['placeholder'] . '" class="awesomplete" autocomplete="off" />' . $category_field . '<input type="hidden" name="post_type" value="' . $post_type . '" />' . '<button type="submit" ' . $button_classes . '>' . '<span class="label wphelpkiticons wphelpkiticons-search"></span>' . '</button>' . '</form>';
    }
    
    /**
     * Search articles only by title.
     *
     * @since 0.9.0
     *
     * @param string $search Search SQL for WHERE clause.
     * @param WP_Query $wp_query The current WP_Query object.
     *
     * @return string Modified search SQL for WHERE clause.
     */
    public function search_articles_by_title_or_tags( $search, $wp_query )
    {
        global  $wpdb ;
        // Skip processing - there is no keyword
        if ( empty($search) || is_admin() ) {
            return $search;
        }
        $q = $wp_query->query_vars;
        // Skip processing - not article post type
        if ( $q['post_type'] !== WPHelpKit_Article::$post_type ) {
            return $search;
        }
        $n = ( !empty($q['exact']) ? '' : '%' );
        $search = $searchand = '';
        $tag_taxonomy = WPHelpKit_Article_Tag::$tag;
        if ( !empty($q['search_terms']) ) {
            foreach ( (array) $q['search_terms'] as $term ) {
                $term = esc_sql( $wpdb->esc_like( $term ) );
                $search .= "{$searchand} (";
                // Search in title
                $search .= "({$wpdb->posts}.post_title LIKE '{$n}{$term}{$n}')";
                // Search in tags
                $search .= " OR EXISTS\n                     (\n                         SELECT * FROM {$wpdb->terms}\n                         INNER JOIN {$wpdb->term_taxonomy}\n                             ON {$wpdb->term_taxonomy}.term_id = {$wpdb->terms}.term_id\n                         INNER JOIN {$wpdb->term_relationships}\n                             ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id\n                         WHERE taxonomy = '{$tag_taxonomy}'\n                             AND object_id = {$wpdb->posts}.ID\n                             AND {$wpdb->terms}.name LIKE '%{$term}%'\n                     )";
                $search .= ")";
                $searchand = ' AND ';
            }
        }
        
        if ( !empty($search) ) {
            $search = " AND ({$search}) ";
            if ( !is_user_logged_in() ) {
                $search .= " AND ({$wpdb->posts}.post_password = '') ";
            }
        }
        
        return $search;
    }

}