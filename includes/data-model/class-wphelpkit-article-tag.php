<?php

defined( 'ABSPATH' ) || die;
/**
 * This class represents the WPHelpKit Article Tag.
 *
 * @since 0.9.1
 */
class WPHelpKit_Article_Tag
{
    /**
     * Our static instance.
     *
     * @since 0.9.1
     *
     * @var WPHelpKit_Article_Tag
     */
    private static  $instance ;
    /**
     * Our "Tags" taxonomy.
     *
     * @since 0.0.1
     * @since 0.9.0 'wphelpkit-tag' turned into 'helpkit-tag'.
     *
     * @var string
     */
    public static  $tag = 'helpkit-tag' ;
    /**
     * Our tag archive shortcode.
     *
     * @since 0.9.0
     *
     * @var string
     */
    public static  $tag_archive_shortcode = 'wphelpkit_tag' ;
    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.9.1
     *
     * @return WPHelpKit_Article_Tag
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
     * @since 0.9.1
     *
     * @return WPHelpKit_Article_Tag
     */
    public function __construct()
    {
        if ( self::$instance ) {
            return self::$instance;
        }
        self::$instance = $this;
        $this->add_hooks();
        if ( is_admin() ) {
            $this->add_admin_hooks();
        }
    }
    
    /**
     * Add hooks.
     *
     * @since 0.9.1
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_action( 'init', array( $this, 'add_shortcode' ) );
        return;
    }
    
    /**
     * Add admin hooks.
     *
     * @since 0.9.1
     *
     * @return void
     */
    protected function add_admin_hooks()
    {
        return;
    }
    
    /**
     * Add our article tag archive shortcode.
     *
     * @since 0.9.1
     *
     * @return void
     *
     * @action init
     */
    public function add_shortcode()
    {
        add_shortcode( self::$tag_archive_shortcode, array( $this, 'tag_archive_shortcode' ) );
        return;
    }
    
    /**
     * Generate the output for our article tag archive shortcode.
     *
     * @since 0.9.0
     *
     * @param array $attrs Keys are shortcode attribute names, values are
     *                     shortcode attribute values.
     * @return string
     */
    public function tag_archive_shortcode( $attrs )
    {
        $settings = WPHelpKit_Settings::get_instance();
        $default_attrs = array(
            'tag'             => '',
            'number_of_posts' => ( is_customize_preview() ? -1 : get_option( 'posts_per_page', 10 ) ),
            'pagination'      => true,
        );
        $attrs = shortcode_atts( $default_attrs, $attrs );
        if ( is_numeric( $attrs['tag'] ) ) {
            $attrs['tag'] = intval( $attrs['tag'] );
        }
        
        if ( is_int( $attrs['tag'] ) ) {
            $attrs['tag'] = get_term_by( 'id', $attrs['tag'], self::$tag );
        } else {
            $attrs['tag'] = get_term_by( 'name', $attrs['tag'], self::$tag );
        }
        
        if ( !$attrs['tag'] instanceof WP_Term ) {
            return '';
        }
        $args = array(
            'post_type'      => WPHelpKit_Article::$post_type,
            'tax_query'      => array( array(
            'taxonomy' => 'helpkit-tag',
            'field'    => 'id',
            'terms'    => $attrs['tag']->term_id,
        ) ),
            'orderby'        => 'meta_value_num title',
            'order'          => 'ASC',
            'posts_per_page' => ( is_customize_preview() ? -1 : $attrs['number_of_posts'] ),
            'paged'          => ( !is_customize_preview() && $attrs['pagination'] && get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ),
        );
        $post_query = new WP_Query( $args );
        $html = '';
        while ( $post_query->have_posts() ) {
            $post_query->the_post();
            $permalink = get_the_permalink();
            $html .= sprintf(
                '<li class="wphelpkit-article" id="post-%d"><span class="wphelpkiticons wphelpkiticons-article"></span><a href="%s" class="wphelpkit-article-title">%s</a></li>',
                get_the_ID(),
                esc_url( $permalink ),
                get_the_title()
            );
        }
        wp_reset_postdata();
        /**
         * Filters the CSS classes used on the article tag archive.
         *
         * @since 0.9.0
         *
         * @var array $classes Default: empty array.
         */
        $classes = apply_filters( 'wphelpkit-class', array() );
        $default_class = array( 'wphelpkit', 'wphelpkit-tag' );
        $classes = array_merge( $default_class, $classes );
        $classes = implode( ' ', $classes );
        $pagination = '';
        
        if ( !is_customize_preview() && $attrs['pagination'] ) {
            $pagination = paginate_links();
            $pagination = '<nav class="navigation pagination">' . '<div class="nav-links">' . $pagination . '</div>' . '</nav>';
        }
        
        if ( !empty($html) ) {
            $html = "<ul class='wphelpkit-articles'>" . $html . "</ul>";
        }
        $html = '<div class="' . $classes . '">' . $html . $pagination . '</div>';
        wp_enqueue_style( 'wphelpkit-styles' );
        return $html;
    }
    
    /**
     * Retrieve the terms in our category taxonomy.
     *
     * @since 0.0.3
     *
     * @param array $args {
     *     Optional. Array or string of arguments. See WPHelpKit_Article::get_terms()
     *     for information on accepted arguments.
     *
     *     @type string $taxonomy Fixed to 'helpkit-tag', cannot be changed.
     * }
     * @return array|int|WP_Error List of WP_Term instances and their children.
     *                            Will return WP_Error, if any of $taxonomies do not exist.
     *                            Will return number of terms when 'count' is true.
     */
    public function get_tags( $args = array() )
    {
        $default_args = array(
            'taxonomy' => self::$tag,
        );
        $args = wp_parse_args( $default_args, $args );
        return WPHelpKit_Article::get_instance()->get_terms( $args );
    }

}