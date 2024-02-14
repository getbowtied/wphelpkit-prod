<?php

defined( 'ABSPATH' ) || die;
/**
 * This class represents the WPHelpKit Article CPT.
 *
 * @since 0.0.1
 * @since 0.1.1 Renamed class to WPHelpKit_Article.
 * @since 0.1.3 Removed all `WP_List_Table`-related article/category ordering functionality,
 *              as that is now handled in the Custominzer Live Preview.
 */
class WPHelpKit_Article
{
    /**
     * Our static instance.
     *
     * @since 0.0.1
     *
     * @var WPHelpKit_Article
     */
    private static  $instance ;
    /**
     * Our post type.
     *
     * @since 0.0.1
     * @since 0.9.0 'wphelpkit-article' turned into 'helpkit'.
     *
     * @var string
     */
    public static  $post_type = 'helpkit' ;
    /**
     * Our post type's query var.
     *
     * @since 0.9.0
     *
     * @var string
     */
    public static  $post_type_query_var = 'helpkit' ;
    /**
     * Our article voting AJAX action.
     *
     * @since 0.0.3
     *
     * @var string
     */
    public static  $voting_action = 'wphelpkit-update-votes' ;
    /**
     * Our drag-and-drop article order AJAX action.
     *
     * @since 0.0.4
     *
     * @var string
     */
    public static  $article_order_action = 'wphelpkit-article-order' ;
    /**
     * Our "display order" post & term meta key.
     *
     * For categories, the key is used directly.  For articles,
     * the key is modified to include the category within which the
     * article is ordered.  @see WPHelpKit_Article::display_order_metakey().
     *
     * @since 0.0.4
     *
     * @var string
     */
    public static  $display_order_meta_key = '_wphelpkit-display-order' ;
    /**
     * Our "article votes" post meta key.
     *
     * @since 0.1.1
     *
     * @var string
     */
    public static  $article_votes_meta_key = '_wphelpkit-article-votes' ;
    /**
     * Our "article views" post meta key.
     *
     * @since 0.1.1
     *
     * @var string
     */
    public static  $article_views_meta_key = '_wphelpkit-article-views' ;
    /**
     * Class to manage related article functionality.
     *
     * @since 0.0.3
     * @since 0.0.5 Changed to `WPHelpKit_Related_Articles_Category` because
     *              of bugs in `WPHelpKit_Related_Articles_ACF`.
     *
     * @var string
     */
    public static  $related_articles_class = 'WPHelpKit_Related_Articles_Category' ;
    /**
     * Related articles implementation.
     *
     * @since 0.0.3
     *
     * @var WPHelpKit_Related_Articles
     */
    protected  $related_articles ;
    /**
     * Our article archive shortcode.
     *
     * @since 0.0.5
     *
     * @var string
     */
    public static  $article_archive_shortcode = 'wphelpkit' ;
    /**
     * Our article-link set transient AJAX action.
     *
     * @since 0.1.1
     *
     * @var string
     */
    public static  $article_link_action = 'wphelpkit-article-link' ;
    /**
     * Transient 'base' for article links.
     *
     * When an article appears in more than 1 category, we need a way
     * to know which category the user was view when they click the
     * link to view the article.  We store that category in a transient.
     *
     * Since 0.1.1
     *
     * @var string
     */
    public static  $article_link_transient = 'wphelpkit-article-link-' ;
    protected  $archive_info ;
    /**
     * Text to use as the placeholder in our search form.
     *
     * @since 0.6.2
     *
     * @var string
     */
    protected  $search_placeholder_text ;
    /**
     * Text to use as the submit button in our search form.
     *
     * @since 0.6.2
     *
     * @var string
     */
    protected  $search_submit_text ;
    /**
     * Transient name to set when we need to flush the rewrite rules (i.e.,
     * when the page used for the HelpKit archive has changed and hence, the
     * slug used in the permalinks of artciles has changed).
     *
     * @since 0.6.2
     *
     * @var string
     */
    public static  $flush_rewrite_rules_transient = 'wphelpkit-flush-rewrite-rules' ;
    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.1
     *
     * @return WPHelpKit_Article
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
     * @return WPHelpKit_Article
     */
    public function __construct()
    {
        if ( self::$instance ) {
            return self::$instance;
        }
        self::$instance = $this;
        $this->archive_info = WPHelpKit_Archive_Info::get_instance();
        $this->add_hooks();
        if ( is_admin() ) {
            $this->add_admin_hooks();
        }
        /**
         * Filters the text used for the placeholder in the search form.
         *
         * @since 0.6.2
         *
         * @param string $placeholder Placeholder text.
         */
        $this->search_placeholder_text = apply_filters( 'wphelpkit-archive-search-placeholder-text', esc_html__( 'Search Help Articles', 'wphelpkit' ) );
        /**
         * Filters the text used for the submit button in the search form.
         *
         * @since 0.6.2
         *
         * @param string $submit Submit button text.
         */
        $this->search_submit_text = apply_filters( 'wphelpkit-archive-search-submit-text', esc_html__( 'Search', 'wphelpkit' ) );
    }
    
    /**
     * Add hooks.
     *
     * @since 0.0.1
     * @since 0.9.0 Added the_content filter for auto-TOC generation.
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_action( 'init', array( $this, 'setup' ) );
        add_action( 'wp', array( $this, 'maybe_use_templates' ) );
        add_action( 'parse_tax_query', array( $this, 'exclude_posts_from_subcategory' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        add_filter( 'parse_query', array( $this, 'article_orderby' ) );
        add_action( 'init', array( $this, 'add_shortcode' ) );
        add_action( 'wp_ajax_' . self::$article_link_action, array( $this, 'ajax_article_link_set_transient' ) );
        add_action( 'wp_ajax_nopriv_' . self::$article_link_action, array( $this, 'ajax_article_link_set_transient' ) );
        add_action(
            'set_object_terms',
            array( $this, 'ensure_display_order_set' ),
            10,
            6
        );
        // prepare to possibly redirect page to post type's archive when plain permalinks are set.
        add_action( 'template_redirect', array( $this, 'maybe_redirect_to_archive' ) );
        add_action( 'pre_get_posts', array( $this, 'exclude_from_wp_search_results' ) );
        add_filter(
            'post_type_link',
            array( $this, 'post_type_link' ),
            10,
            2
        );
        return;
    }
    
    /**
     * Does a page contain our archive block/shortcode?
     *
     * @since 0.6.1
     *
     * @param WP_Post $page The page to check.  Default null (the current post).
     * @return bool
     */
    public function contains_article_archive( $page = null )
    {
        $page = get_post( $page );
        if ( !$page ) {
            return false;
        }
        return has_shortcode( $page->post_content, self::$article_archive_shortcode );
    }
    
    public function get_canonical_archive_url()
    {
        return $this->archive_info->url;
    }
    
    /**
     * Is the current request for something that could serve as our post_type archive?
     *
     * A request could be for out post_type archive in 2 cases:
     *
     * 1. It is the URL for our post_type archive
     * 2. It is for a post/page that contains our archive block/shortcode.
     *
     * @since 0.6.1
     *
     * @return bool
     */
    public function is_post_type_archive()
    {
        global  $pagenow, $post ;
        if ( is_post_type_archive( self::$post_type ) ) {
            return true;
        }
        
        if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) && isset( $_GET['post'] ) ) {
            $current_post = get_post( esc_attr( sanitize_text_field( $_GET['post'] ) ) );
        } elseif ( $post ) {
            $current_post = $post;
        } else {
            $current_post = get_queried_object();
        }
        
        if ( $current_post instanceof WP_Post ) {
            return $this->contains_article_archive( $current_post );
        }
        return false;
    }
    
    /**
     * Add our article archive shortcode.
     *
     * @since 0.0.5
     * @since 0.9.0 Added tag archive shortcode
     *
     * @return void
     *
     * @action init
     */
    public function add_shortcode()
    {
        add_shortcode( self::$article_archive_shortcode, array( $this, 'article_archive_shortcode' ) );
        return;
    }
    
    /**
     * Generate the output for our article archive shortcode.
     *
     * @since 0.0.5
     * @since 0.1.1 Removed `$content` and `$shortcode_tag` params.
     * @since 0.6.2 Added 'placeholder', 'submit' and 'align' attributes.
     * @since 0.9.1 Add new display_categories_description attribute.
     * @since 0.9.1 Add new display_categories_thumbnail attribute.
     *
     * @param array $attrs Keys are shortcode attribute names, values are
     *                     shortcode attribute values.
     * @return void Echo's it's output.
     */
    public function article_archive_shortcode( $attrs )
    {
        WPHelpKit_Templates::load_template_tags();
        $settings = WPHelpKit_Settings::get_instance();
        $default_attrs = array(
            'display_posts'                  => $settings->get_option( '[article_archive]display_posts' ),
            'number_of_posts'                => $settings->get_option( '[article_archive]number_of_posts' ),
            'display_subcategories'          => $settings->get_option( '[article_archive]display_subcategories' ),
            'number_of_columns'              => $settings->get_option( '[article_archive]number_of_columns' ),
            'search'                         => $settings->get_option( '[article_archive]search' ),
            'display_categories_description' => $settings->get_option( '[article_archive]display_categories_description' ),
            'display_categories_thumbnail'   => $settings->get_option( '[article_archive]display_categories_thumbnail' ),
            'placeholder'                    => $this->search_placeholder_text,
            'submit'                         => $this->search_submit_text,
            'align'                          => '',
        );
        $attrs = shortcode_atts( $default_attrs, $attrs );
        // verify boolean attrs.
        foreach ( array( 'display_posts', 'display_subcategories', 'search' ) as $attr ) {
            if ( is_string( $attrs[$attr] ) ) {
                $attrs[$attr] = in_array( strtolower( $attrs[$attr] ), array( 'true', '1' ) );
            }
        }
        $html = '';
        $category_args = array(
            'orderby' => WPHelpKit_Article::$display_order_meta_key,
            'parent'  => 0,
        );
        $categories = WPHelpKit_Article_Category::get_instance()->get_categories( $category_args );
        foreach ( $categories as $term ) {
            $html .= wphelpkit_get_category_archive( array_merge( $attrs, array(
                'category'    => $term->term_id,
                'pagination'  => false,
                'description' => $attrs['display_categories_description'],
            ) ) );
        }
        $search_attrs = array(
            'placeholder' => $attrs['placeholder'],
            'submit'      => $attrs['submit'],
        );
        $search = ( $attrs['search'] || is_customize_preview() ? wphelpkit_get_search_form( $search_attrs ) : '' );
        $prelude = '<div class="wphelpkit-prelude">' . $search . '</div>';
        /**
         * Filters the CSS classes used on the article archive.
         *
         * `wphelpkit` is **always** added to whatever is returned.
         *
         * @since 0.2.0
         *
         * @var array $classes Default: empty array.
         */
        $classes = apply_filters( 'wphelpkit-class', array() );
        $default_classes = array( 'wphelpkit' );
        $classes = array_merge( $classes, $default_classes );
        if ( !empty($attrs['align']) ) {
            $classes[] = "align{$attrs['align']}";
        }
        $classes = implode( ' ', array_reverse( $classes ) );
        $html = '<div class="' . $classes . '">' . $prelude . '<div class="wphelpkit-archive column-' . $attrs['number_of_columns'] . '">' . $html . '</div>' . '</div>';
        wp_enqueue_style( 'wphelpkit-styles' );
        return $html;
    }
    
    /**
     * Enqueue our scripts and styles.
     *
     * @since 0.0.3
     * @since 0.9.0 Added support for auto-TOC generation for each article using metabox.
     *
     * @global WP_Post $post
     *
     * @return void
     */
    public function enqueue_scripts()
    {
        global  $post ;
        if ( is_singular( WPHelpKit_Article::$post_type ) ) {
        }
        return;
    }
    
    /**
     * Enqueue our admin scripts.
     *
     * @since 0.0.4
     *
     * @global string $typenow
     * @global @taxnow
     *
     * @param string $hook
     * @return void
     */
    public function admin_enqueue_scripts( $hook )
    {
        global  $typenow, $taxnow ;
        switch ( $hook ) {
            case 'post.php':
            case 'post-new.php':
                if ( self::$post_type !== $typenow ) {
                    break;
                }
                break;
        }
        return;
    }
    
    /**
     * Add admin hooks.
     *
     * @since 0.0.2
     *
     * @return void
     */
    protected function add_admin_hooks()
    {
        add_filter( 'dashboard_glance_items', array( $this, 'add_dashboard_glance_items' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_ajax_' . self::$article_order_action, array( $this, 'ajax_article_order' ) );
        add_filter(
            'easy_primary_term_taxonomies',
            array( $this, 'prevent_epc' ),
            10,
            2
        );
        return;
    }
    
    /**
     * Ensure that our "display order" post meta is set properly whenever
     * categories are changed on an article.
     *
     * @since 0.1.4
     * @since 0.5.0 Map the `term_taxonomy_id`'s to `term_id`'s before setting/unsetting
     *              the relevant display_order post_meta.
     *
     * @param int    $object_id  Object ID.
     * @param array  $terms      An array of object terms.
     * @param array  $tt_ids     An array of term taxonomy IDs.
     * @param string $taxonomy   Taxonomy slug.
     * @param bool   $append     Whether to append new terms to the old terms.
     * @param array  $old_tt_ids Old array of term taxonomy IDs.
     * @return void
     *
     * @action set_object_terms
     */
    public function ensure_display_order_set(
        $object_id,
        $terms,
        $tt_ids,
        $taxonomy,
        $append,
        $old_tt_ids
    )
    {
        global  $wpdb ;
        $post = get_post( $object_id );
        if ( self::$post_type !== get_post_type( $post ) ) {
            // not our post type, nothing to do.
            return;
        }
        if ( WPHelpKit_Article_Category::$category !== $taxonomy ) {
            // not our category taxonomy, nothing to do.
            return;
        }
        $tt_ids = array_map( 'intval', $tt_ids );
        // remove the display order post meta for terms that have been removed.
        $removed_terms = array_diff( $old_tt_ids, $tt_ids );
        foreach ( $removed_terms as $removed_term ) {
            // get the term_id for this term_taxonomy_id.
            $term = get_term_by( 'term_taxonomy_id', $removed_term, WPHelpKit_Article_Category::$category );
            delete_post_meta( $object_id, $this->display_order_meta_key( $term->term_id ) );
        }
        // add the display order post meta for terms that have been added.
        $added_terms = array_diff( $tt_ids, $old_tt_ids );
        foreach ( $added_terms as $added_term ) {
            // get the term_id for this term_taxonomy_id.
            $term = get_term_by( 'term_taxonomy_id', $added_term, WPHelpKit_Article_Category::$category );
            $this->set_article_display_order( $object_id, $term->term_id, 0 );
        }
        return;
    }
    
    /**
     * Order articles.
     *
     * @since 0.0.4
     * @since 0.1.3 Removed all `WP_List_Table`-related article/category ordering functionality,
     *              as that is now handled in the Custominzer Live Preview.
     * @since 0.3.0 Added 'title' as secondary sort key for articles with the same
     *              display order.
     *
     * @param array WP_Query $query ???
     * @return void
     */
    public function article_orderby( $query )
    {
        if ( !(!is_admin() && !$query->is_search && $query->is_main_query() && $query->is_tax( WPHelpKit_Article_Category::$category )) ) {
            return;
        }
        $category = $query->queried_object;
        $query->set( 'meta_key', $this->display_order_meta_key( $category->term_id ) );
        $query->set( 'orderby', 'meta_value_num title' );
        $order = $query->get( 'order' );
        $query->set( 'order', ( $order ? $order : 'ASC' ) );
        return;
    }
    
    /**
     * Get the `meta_key` for post/term meta.
     *
     * For term meta, we just a fixed `meta_key`.  But for articles, there are
     * separate `meta_key`s for each category since articles can appear in more
     * than one category and may have a different ordering in each category.
     *
     * @since 0.0.4
     *
     * @param int $term_id Optional.  The term_id of a particular category.
     *                              Default 0.
     * @return string
     */
    public function display_order_meta_key( $term_id = 0 )
    {
        $meta_key = self::$display_order_meta_key;
        if ( $term_id ) {
            $meta_key .= "-{$term_id}";
        }
        return $meta_key;
    }
    
    /**
     * Update "display order" post meta via AJAX.
     *
     * @since 0.0.4
     *
     * @return void Send JSON back to caller and exists.
     *
     * @action wp_ajax_ . self::$article_order_action
     */
    public function ajax_article_order()
    {
        check_ajax_referer( self::$article_order_action . '-nonce', 'nonce' );
        
        if ( isset( $_REQUEST['data'] ) && is_array( $_REQUEST['data'] ) ) {
            $data = array_map( 'sanitize_text_field', $_REQUEST['data'] );
            if ( empty($data['category']) ) {
                // user not viewing articles in a specific category,
                // should never get here.
                wp_send_json_error( "Error! Article not found." );
            }
            $by = 'id';
            if ( !is_numeric( $data['category'] ) ) {
                $by = 'slug';
            }
            $category = get_term_by( $by, $data['category'], WPHelpKit_Article_Category::$category );
            if ( !$category ) {
                // category doesn't exist.
                // should never get here.
                wp_send_json_error( "Error! Category doesn't exist." );
            }
            parse_str( $data['order'], $order );
            if ( !is_array( $order ) ) {
                wp_send_json_error( "Error! Something went wrong with the ordering." );
            }
            $new_order = array();
            foreach ( $order['post'] as $position => $id ) {
                $id = intval( $id );
                $this->set_article_display_order( $id, $category->term_id, ++$position );
                $new_order[] = $position;
            }
            wp_send_json_success( $new_order );
        } else {
            wp_send_json_error( "Error! Invalid data." );
        }
    
    }
    
    /**
     * Perform basic setup operations.
     *
     * Registers our post_type and taxonomies.
     *
     * @since 0.0.1
     * @since 0.0.3 Added related_articles initialization. Added default category initialization (if needed).
     *
     * @return void
     *
     * @action init
     */
    public function setup()
    {
        if ( !$this->register_post_type() ) {
            return;
        }
        // perform other setup needed.
        $class = apply_filters( 'wphelpkit-related_articles-class', self::$related_articles_class );
        $this->related_articles = new $class();
        return;
    }
    
    /**
     * Register our post type.
     *
     * @since 0.0.1
     * @since 0.0.2 Added 'comments' to 'supports'.
     * @since 0.6.2 'has_archive' is now the post_type slug, rather than just
     *              a simple boolean.  And that slug is retrieved from `WPHelpKit_Archive_Info`
     *              instead of always being the value from our settings.  This allows
     *              the permalink for articles to use the slug of the "HelpKit Index" page.
     * @since 0.9.0 If the Index page is set and is not also set as front page,
     *              then use it as CPT archive.
     * @return bool
     */
    protected function register_post_type()
    {
        WPHelpKit_Settings::get_instance()->maybe_reload_settings();
        $archive_info = WPHelpKit_Archive_Info::get_instance();
        $post_type_slug = $archive_info->archive_slug;
        $args = array(
            'labels'              => array(
            'name'                  => esc_html__( 'Help Articles', 'wphelpkit' ),
            'singular_name'         => esc_html__( 'Help Article', 'wphelpkit' ),
            'add_new_item'          => esc_html__( 'Add New Article', 'wphelpkit' ),
            'edit_item'             => esc_html__( 'Edit Article', 'wphelpkit' ),
            'new_item'              => esc_html__( 'New Article', 'wphelpkit' ),
            'view_item'             => esc_html__( 'View Article', 'wphelpkit' ),
            'view_items'            => esc_html__( 'View Articles', 'wphelpkit' ),
            'search_items'          => esc_html__( 'Search Articles', 'wphelpkit' ),
            'not_found'             => esc_html__( 'No articles found', 'wphelpkit' ),
            'not_found_in_trash'    => esc_html__( 'No articles found in Trash', 'wphelpkit' ),
            'all_items'             => esc_html__( 'All Help Articles', 'wphelpkit' ),
            'archives'              => esc_html__( 'Article Archives', 'wphelpkit' ),
            'attributes'            => esc_html__( 'Article Attributes', 'wphelpkit' ),
            'insert_into_item'      => esc_html__( 'Insert into article', 'wphelpkit' ),
            'uploaded_to_this_item' => esc_html__( 'Uploaded to this article', 'wphelpkit' ),
            'menu_name'             => esc_html__( 'HelpKit', 'wphelpkit' ),
            'filter_items_list'     => esc_html__( 'Filter articles list', 'wphelpkit' ),
            'items_list_navigation' => esc_html__( 'Articles list navigation', 'wphelpkit' ),
            'items_list'            => esc_html__( 'Articles list', 'wphelpkit' ),
        ),
            'public'              => true,
            'hierarchical'        => false,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_rest'        => true,
            'menu_position'       => null,
            'menu_icon'           => '',
            'supports'            => array(
            'title',
            'editor',
            'revisions',
            'author',
            'excerpt',
            'thumbnail',
            'comments'
        ),
            'taxonomies'          => array( WPHelpKit_Article_Category::$category, WPHelpKit_Article_Tag::$tag ),
            'has_archive'         => $post_type_slug,
            'rewrite'             => array(
            'slug'       => WPHelpKit_Settings::get_instance()->get_option( 'article_permalink_structure' ),
            'with_front' => false,
        ),
            'query_var'           => self::$post_type_query_var,
            'can_export'          => true,
            'delete_with_user'    => false,
        );
        $post_type_obj = register_post_type( self::$post_type, $args );
        if ( is_wp_error( $post_type_obj ) ) {
            return $post_type_obj;
        }
        if ( 'default' === $archive_info->type ) {
            // if we're using the "default" archive type, then the value in
            // $archive_info->url will be false, since at the time it was established
            // (above) the post_type wasn't registered, so WP doesn't know what it's
            // permalink will be.  Hence, we have to refresh the archive info now.
            $archive_info->refresh_archive_info();
        }
        
        if ( $this->register_taxonomies() ) {
            // post_type and taxonomies are all registered.
            // so, flush rewrite rules if necessary.
            
            if ( get_transient( self::$flush_rewrite_rules_transient ) ) {
                delete_transient( self::$flush_rewrite_rules_transient );
                flush_rewrite_rules();
            }
            
            return true;
        }
        
        // failed to register taxonomies, so unregister our post_type.
        unregister_post_type( self::$post_type );
        return false;
    }
    
    /**
     * Register our taxonomies.
     *
     * @since 0.0.1
     *
     * @return bool
     */
    protected function register_taxonomies()
    {
        $category_base = WPHelpKit_Settings::get_instance()->get_option( 'category_base', WPHelpKit_Article_Category::$category );
        $args = array(
            'labels'             => array(
            'name'                  => esc_html__( 'Help Categories', 'wphelpkit' ),
            'singular_name'         => esc_html__( 'Help Category', 'wphelpkit' ),
            'search_items'          => esc_html__( 'Search Categories', 'wphelpkit' ),
            'all_items'             => esc_html__( 'All Categories', 'wphelpkit' ),
            'parent_item'           => esc_html__( 'Parent Category', 'wphelpkit' ),
            'parent_item_colon'     => esc_html__( 'Parent Category: ', 'wphelpkit' ),
            'edit_item'             => esc_html__( 'Edit Category', 'wphelpkit' ),
            'view_item'             => esc_html__( 'View Category', 'wphelpkit' ),
            'update_item'           => esc_html__( 'Update Category', 'wphelpkit' ),
            'add_new_item'          => esc_html__( 'Add New Category', 'wphelpkit' ),
            'new_item_name'         => esc_html__( 'New Category Name', 'wphelpkit' ),
            'not_found'             => esc_html__( 'No categories found', 'wphelpkit' ),
            'no_terms'              => esc_html__( 'No categories', 'wphelpkit' ),
            'items_list_navigation' => esc_html__( 'Categories list navigation', 'wphelpkit' ),
            'items_list'            => esc_html__( 'Categories list', 'wphelpkit' ),
            'back_to_items'         => esc_html__( 'Back to Categories', 'wphelpkit' ),
        ),
            'description'        => esc_html__( 'Help by Category', 'wphelpkit' ),
            'public'             => true,
            'publicly_queryable' => true,
            'hierarchical'       => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => true,
            'show_in_rest'       => true,
            'show_tagcloud'      => true,
            'show_in_quick_edit' => true,
            'show_admin_column'  => true,
            'rewrite'            => array(
            'slug'         => $category_base,
            'hierarchical' => true,
        ),
            'query_var'          => $category_base,
        );
        unset( $category_base );
        $tax = register_taxonomy( WPHelpKit_Article_Category::$category, self::$post_type, $args );
        if ( is_wp_error( $tax ) ) {
            return $tax;
        }
        $tag_base = WPHelpKit_Settings::get_instance()->get_option( 'tag_base', WPHelpKit_Article_Tag::$tag );
        $args = array(
            'labels'             => array(
            'name'                       => esc_html__( 'Help Tags', 'wphelpkit' ),
            'singular_name'              => esc_html__( 'Help Tag', 'wphelpkit' ),
            'search_items'               => esc_html__( 'Search Tags', 'wphelpkit' ),
            'all_items'                  => esc_html__( 'All Tags', 'wphelpkit' ),
            'edit_item'                  => esc_html__( 'Edit Tag', 'wphelpkit' ),
            'view_item'                  => esc_html__( 'View Tag', 'wphelpkit' ),
            'update_item'                => esc_html__( 'Update Tag', 'wphelpkit' ),
            'add_new_item'               => esc_html__( 'Add New Tag', 'wphelpkit' ),
            'new_item_name'              => esc_html__( 'New Tag Name', 'wphelpkit' ),
            'separate_items_with_commas' => esc_html__( 'Separate tags with commands', 'wphelpkit' ),
            'add_or_remove_items'        => esc_html__( 'Add or remove tags', 'wphelpkit' ),
            'choose_from_most_used'      => esc_html__( 'Choose from the most used tags', 'wphelpkit' ),
            'not_found'                  => esc_html__( 'No tags found', 'wphelpkit' ),
            'no_terms'                   => esc_html__( 'No tags', 'wphelpkit' ),
            'items_list_navigation'      => esc_html__( 'Tags list navigation', 'wphelpkit' ),
            'items_list'                 => esc_html__( 'Tags list', 'wphelpkit' ),
            'back_to_items'              => esc_html__( 'Back to Tags', 'wphelpkit' ),
        ),
            'description'        => esc_html__( 'Help by Tag', 'wphelpkit' ),
            'public'             => true,
            'publicly_queryable' => true,
            'hierarchical'       => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'show_in_nav_menus'  => false,
            'show_in_rest'       => true,
            'show_tagcloud'      => true,
            'show_in_quick_edit' => true,
            'show_admin_column'  => true,
            'rewrite'            => array(
            'slug' => $tag_base,
        ),
            'query_var'          => $tag_base,
        );
        unset( $tag_base );
        $tax = register_taxonomy( WPHelpKit_Article_Tag::$tag, self::$post_type, $args );
        
        if ( is_wp_error( $tax ) ) {
            unregister_taxonomy( WPHelpKit_Article_Category::$category );
            return $tax;
        }
        
        return true;
    }
    
    /**
     * Retrieve article posts.
     *
     * @since 0.0.3
     *
     * @param array $args {
     *     Optional.  Arguments to retrieve posts. See WP_Query::parse_query()
     *     for all available arguments.
     *
     *     @type string $post_type      Fixed to 'wphelpkit-article', cannot be changed.
     *     @type int    $posts_per_page Default -1.
     * }
     * @return array
     */
    public function get_posts( $args = array() )
    {
        $default_args = array(
            'post_type' => self::$post_type,
        );
        $args = wp_parse_args( $default_args, $args );
        $args = wp_parse_args( $args, array(
            'posts_per_page' => -1,
        ) );
        return get_posts( $args );
    }
    
    /**
     * Retrieve the terms in one of our taxonomies.
     *
     * @since 0.0.3
     *
     * @param array $args {
     *     Optional. Array or string of arguments. See WP_Term_Query::__construct()
     *     for information on accepted arguments.
     *
     *     @type string $taxonomy Must be one of 'helpkit-category' or
     *                              'helpkit-tag'. Default 'helpkit-category'.
     *     @type string $orderby  In addition to the values accepted by
     *                            WP_Term_Query::parse_orderby(), `rand` is also
     *                            accepted (for parity with WP_Query and get_posts()).
     * }
     * @return array|int|WP_Error List of WP_Term instances and their children.
     *                            Will return WP_Error, if any of $taxonomies do not exist.
     *                            Will return number of terms when 'count' is true.
     */
    public function get_terms( $args = array() )
    {
        if ( !isset( $args['taxonomy'] ) || !in_array( $args['taxonomy'], array( WPHelpKit_Article_Category::$category, WPHelpKit_Article_Tag::$tag ) ) ) {
            $args['taxonomy'] = WPHelpKit_Article_Category::$category;
        }
        // setup for radnom ordering if necessary.
        $rand = false;
        
        if ( isset( $args['orderby'] ) && 'rand' === $args['orderby'] ) {
            $rand = true;
            $args['orderby'] = 'none';
            
            if ( isset( $args['number'] ) ) {
                $number = intval( $args['number'] );
                unset( $args['number'] );
            }
        
        }
        
        $terms = get_terms( $args );
        
        if ( $rand && !empty($terms) ) {
            // randonly order terms.
            shuffle( $terms );
            if ( isset( $number ) ) {
                // return only the number of terms requested.
                $terms = array_slice( $terms, 0, $number );
            }
        }
        
        return $terms;
    }
    
    /**
     * Instantiate our template "manager" if the current request needs it.
     *
     * @since 0.0.1
     *
     * @return void
     *
     * @action wp
     */
    public function maybe_use_templates()
    {
        if ( $this->could_use_template() ) {
            WPHelpKit_Templates::get_instance();
        }
        return;
    }
    
    /**
     * Is the current request one that could use one of our templates?
     *
     * @since 0.0.1
     * @since 0.0.2 Added support for searching out post_type.
     *
     * @return bool
     */
    public function could_use_template()
    {
        return is_post_type_archive( self::$post_type ) || is_tax( WPHelpKit_Article_Category::$category ) || is_tax( WPHelpKit_Article_Tag::$tag ) || is_singular( self::$post_type ) || is_search() && WPHelpKit_Article::$post_type === get_query_var( 'post_type' );
    }
    
    /**
     * Output counts to Dashboard At a Glance widget for our articles.
     *
     * @since 0.0.3
     *
     * @return void
     *
     * @action dashboard_glance_items
     */
    public function add_dashboard_glance_items( $items )
    {
        $post_type = self::$post_type;
        $num_posts = wp_count_posts( $post_type );
        if ( !isset( $num_posts->publish ) || 0 === $num_posts->publish ) {
            return $items;
        }
        $num = number_format_i18n( $num_posts->publish );
        $post_type_obj = get_post_type_object( $post_type );
        $text = _n( "%s Help Article", "%s Help Articles", $num_posts->publish );
        $text = sprintf( $text, number_format_i18n( $num_posts->publish ) );
        
        if ( current_user_can( $post_type_obj->cap->edit_posts ) ) {
            $items[] = sprintf( '<a href="edit.php?post_type=%1$s">%2$s</a>', $post_type, $text );
        } else {
            $items[] = sprintf( '<span class="%1$s">%2$s</span>', $post_type, $text );
        }
        
        return $items;
    }
    
    /**
     * Retrieve list of related artciles.
     *
     * @since 0.0.3
     *
     * @param WP_Post|int $post Optional. Post ID or WP_Post object. Default is global $post.
     * @return array Array of post IDs (possibly empty) of related articles.
     */
    public function get_related_articles( $post = null )
    {
        $related_articles = array();
        if ( !$this->related_articles ) {
            // no related articles implementation, so no related articles
            return $related_articles;
        }
        $post = get_post( $post );
        if ( get_post_type( $post ) !== self::$post_type ) {
            // not an article, so no related posts
            return $related_articles;
        }
        $related_articles = $this->related_articles->get_related_articles( $post );
        return apply_filters( 'wphelpkit-related-articles', $related_articles, $post );
    }
    
    /**
     * Get the display order for an article within a given category.
     *
     * @since 0.1.4
     *
     * @param WP_Post|int $post The article to set display order for.
     * @param WP_Term|int|string $term The category within which to set the
     *                                 display order for.  If an int, interpreted as
     *                                 the `term_id`; if a string, interpreted as the `slug`.
     * @return int|bool The display order on success, false otherwise.  When checking
     *                  for failure, must use `false === $retun_val` as 0 is a legal
     *                  return value.
     */
    public function get_article_display_order( $post, $term )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            // not our post type, nothing to do.
            return false;
        }
        if ( !$term instanceof WP_Term ) {
            
            if ( is_int( $term ) ) {
                $term = get_term_by( 'id', $term, WPHelpKit_Article_Category::$category );
            } elseif ( is_string( $term ) ) {
                $term = get_term_by( 'slug', $term, WPHelpKit_Article_Category::$category );
            }
        
        }
        if ( WPHelpKit_Article_Category::$category !== $term->taxonomy ) {
            // not our category taxonomy, nothing to do.
            return false;
        }
        $display_order = get_post_meta( $post->ID, $this->display_order_meta_key( $term->term_id ), true );
        if ( '' === $display_order ) {
            // display order not set.
            return 0;
        }
        return intval( $display_order );
    }
    
    /**
     * Set the display order for an article within a given category.
     *
     * @since 0.1.4
     *
     * @param WP_Post|int $post The article to set display order for.
     * @param WP_Term|int|string $category The category within which to set the
     *                           display order for.  If an int, interpreted as
     *                           the `term_id`; if a string, interpreted as the `slug`.
     * @param int $display_order The display order to set.  Default 0.
     * @return bool Truthy value if successfully set, false otherwise.
     */
    public function set_article_display_order( $post, $term, $display_order = 0 )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            // not our post type, nothing to do.
            return false;
        }
        if ( !$term instanceof WP_Term ) {
            
            if ( is_int( $term ) ) {
                $term = get_term_by( 'id', $term, WPHelpKit_Article_Category::$category );
            } elseif ( is_string( $term ) ) {
                $term = get_term_by( 'slug', $term, WPHelpKit_Article_Category::$category );
            }
        
        }
        if ( WPHelpKit_Article_Category::$category !== $term->taxonomy ) {
            // not our category taxonomy, nothing to do.
            return false;
        }
        $display_order = intval( $display_order );
        return update_post_meta( $post->ID, $this->display_order_meta_key( $term->term_id ), $display_order );
    }
    
    /**
     * Exclude article posts that have only been assigned to children of
     * the currently queried category.
     *
     * For our Category archives, without this then article posts which are
     * assigned a term from a descendant of the currently queried term appear
     * as if they are assigned the currently queried term.
     *
     * @since 0.0.1
     * @since 0.0.4 Exit early if processing category dropdown filter on edit.php for artciles.
     *
     * @global string $pagenow
     * @global string $typenow
     *
     * @param WP_Query $query The WP_Query object.
     * @return void
     *
     * @action parse_tax_query
     */
    public function exclude_posts_from_subcategory( $query )
    {
        global  $pagenow, $typenow ;
        if ( !(!$query->is_search && $query->is_main_query() && $query->is_tax( WPHelpKit_Article_Category::$category )) ) {
            return;
        }
        if ( 'edit.php' === $pagenow && self::$post_type === $typenow ) {
            // allow the category dropdown filter on the edit articles screen to function.
            return;
        }
        $queried_object = $query->get_queried_object();
        foreach ( $query->tax_query->queries as &$tax_query ) {
            if ( $queried_object->taxonomy === $tax_query['taxonomy'] && in_array( $queried_object->slug, (array) $tax_query['terms'] ) ) {
                $tax_query['include_children'] = false;
            }
        }
        return;
    }
    
    /**
     * Set a transient with the category to use a "primary" when the
     * user navigates to an article (e.g., from the article/category archive).
     *
     * @since 0.1.1
     *
     * @return void Sends jSON data back to caller.
     *
     * @action wp_ajax_ . self::$article_link_action
     */
    public function ajax_article_link_set_transient()
    {
        check_ajax_referer( self::$article_link_action . '-nonce', 'nonce' );
        
        if ( isset( $_REQUEST['data'] ) && is_array( $_REQUEST['data'] ) ) {
            $data = array_map( 'sanitize_text_field', $_REQUEST['data'] );
            if ( empty($data['category']) || empty($data['id']) ) {
                // user not viewing articles in a specific category,
                // should never get here.
                wp_send_json_error();
            }
            
            if ( intval( $data['id'] ) || $data['id'] instanceof WP_Post ) {
                if ( intval( $data['id'] ) ) {
                    $data['id'] = intval( $data['id'] );
                }
                self::set_article_link_transient( $data['id'], $data['category'] );
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        
        } else {
            wp_send_json_error();
        }
    
    }
    
    /**
     * Construct the article link transient name for a post.
     *
     * @since 0.1.1
     *
     * @param WP_Post|int $post The post to construct the transient name for.
     * @return string
     */
    public static function get_article_link_transient_name( $post )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            return '';
        }
        $session_cookie = WPHelpKit_Session_Handler::get_instance()->get_session_cookie();
        return sprintf(
            '%s%s-%s',
            self::$article_link_transient,
            $session_cookie[3],
            $post->ID
        );
    }
    
    /**
     * Delete the article link transient for a post.
     *
     * @since 0.1.1
     *
     * @param WP_Post|int $post The post to delete the transient for.
     * @return bool The return value of `delete_transient()`.
     */
    public static function delete_article_link_transient( $post )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            return false;
        }
        $transient = self::get_article_link_transient_name( $post->ID );
        return delete_transient( $transient );
    }
    
    /**
     * Set the article link transient for a post.
     *
     * @since 0.1.1
     *
     * @param WP_Post|int $post The post to set the transient for.
     * @param int $term_id The category `term_id` to store.
     * @param int $expiration The expiration time of for the transient.
     * @return bool The return value of `set_transient()`.
     */
    public static function set_article_link_transient( $post, $term_id, $expiration = MINUTE_IN_SECONDS )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            return false;
        }
        $transient = self::get_article_link_transient_name( $post->ID );
        return set_transient( $transient, $term_id, $expiration );
    }
    
    /**
     * Get the article link transient for a post.
     *
     * The transient is deleted once it's retrieved.
     *
     * @since 0.1.1
     *
     * @param WP_Post|int $post The post to get the transient for.
     * @return int The catgegory `term_id` stored in the transient.
     */
    public static function get_article_link_transient( $post )
    {
        $post = get_post( $post );
        if ( self::$post_type !== get_post_type( $post ) ) {
            return 0;
        }
        $session_cookie = WPHelpKit_Session_Handler::get_instance()->get_session_cookie();
        $transient = self::get_article_link_transient_name( $post->ID );
        $value = get_transient( $transient );
        delete_transient( $transient );
        return $value;
    }
    
    /**
     * Prevent the Easy Primary Categories plugin from operating on our
     * category/post_type (since we're providing that functionality).
     *
     * @since 0.1.1
     *
     * @param array $taxonomes Array of taxonomy objects.  Keys are taxonomy slugs.
     * @param string $post_type The post_type being editing.
     * @return array
     */
    public function prevent_epc( $taxonomes, $post_type )
    {
        if ( self::$post_type === $post_type ) {
            unset( $taxonomes[WPHelpKit_Article_Category::$category] );
        }
        return $taxonomes;
    }
    
    /**
     * Exclude HelpKit articles from WP search results
     *
     * @since 0.9.1
     *
     * @param WP_Query $query
     *
     * @return void
     */
    public function exclude_from_wp_search_results( $query )
    {
        if ( is_admin() || !$query->is_main_query() || !$query->is_search() ) {
            // not search
            return;
        }
        if ( isset( $_GET['post_type'] ) && self::$post_type === sanitize_text_field( $_GET['post_type'] ) ) {
            // not WP search
            return;
        }
        $searchable_post_types = get_post_types( array(
            'exclude_from_search' => false,
        ) );
        
        if ( is_array( $searchable_post_types ) && in_array( self::$post_type, $searchable_post_types ) ) {
            unset( $searchable_post_types[self::$post_type] );
            $query->set( 'post_type', $searchable_post_types );
        }
        
        return;
    }
    
    /**
     * Get the count of articles in a category (for display in archives).
     *
     * @since 0.1.3
     *
     * @param WP_Term|int|string $category The category to get article counts for.
     *                                     If an int, represents the `term_id`.
     *                                     If a string, represents the `slug`.
     * @param bool $include_children       A truthy value means to get counts of
     *                                     articles in all descendant categories of
     *                                     `$category`.  A falsey value means to get
     *                                     the count only for articles directly in
     *                                     `$category`.
     * @return int The count of articles on success; 0 if `$category` is not a term
     *             in our category taxonomy.
     */
    public function get_article_count( $category, $include_children = false )
    {
        if ( !$category instanceof WP_Term ) {
            
            if ( is_int( $category ) ) {
                $category = get_term_by( 'id', $category, WPHelpKit_Article_Category::$category );
            } elseif ( is_string( $category ) ) {
                $category = get_term_by( 'slug', $category, WPHelpKit_Article_Category::$category );
            }
        
        }
        if ( !$category instanceof WP_Term || WPHelpKit_Article_Category::$category !== $category->taxonomy ) {
            return 0;
        }
        $args = array(
            'tax_query'      => array( array(
            'taxonomy'         => WPHelpKit_Article_Category::$category,
            'field'            => 'id',
            'terms'            => $category->term_id,
            'include_children' => $include_children,
        ) ),
            'fields'         => 'ids',
            'posts_per_page' => -1,
        );
        $posts = $this->get_posts( $args );
        return count( $posts );
    }
    
    /**
     * Sanitizes post meta value.
     *
     * @since 0.9.0
     *
     * @return bool
     */
    public function sanitize_post_meta_value( $value )
    {
        
        if ( $value ) {
            $value = ( is_bool( $value ) ? $value : 'yes' === $value || 1 === $value || 'true' === $value || '1' === $value || 'on' === $value );
            return ( true === $value ? 'on' : 'off' );
        }
        
        return 'off';
    }
    
    /**
     * Redirect Index page to archive when plain permalinks are set.
     *
     * @since 0.9.1
     *
     * @return void
     */
    function maybe_redirect_to_archive()
    {
        $permastruct = get_option( 'permalink_structure' );
        $archive_slug = WPHelpKit_Archive_Info::get_instance()->archive_slug;
        
        if ( empty($permastruct) && is_page( $archive_slug ) ) {
            wp_safe_redirect( home_url( '?post_type=' . self::$post_type ), 301 );
            exit;
        }
    
    }
    
    /**
     * Filter to allow helpkit_category in the permalinks for help articles.
     *
     * @since 0.9.3
     *
     * @param  string  $permalink The existing permalink URL.
     * @param  WP_Post $post WP_Post object.
     * @return string
     */
    function post_type_link( $permalink, $post )
    {
        if ( self::$post_type !== $post->post_type ) {
            return $permalink;
        }
        $category_placeholder = '%' . WPHelpKit_Article_Category::$category . '%';
        // Abort early if the placeholder rewrite tag isn't in the generated URL.
        if ( false === strpos( $permalink, $category_placeholder ) ) {
            return $permalink;
        }
        // Get the custom taxonomy terms in use by this post.
        $terms = get_the_terms( $post->ID, WPHelpKit_Article_Category::$category );
        
        if ( !empty($terms) ) {
            $terms = wp_list_sort( $terms, array(
                'parent'  => 'DESC',
                'term_id' => 'ASC',
            ) );
            $article = $terms[0];
            $article_slug = $article->slug;
            
            if ( $article->parent ) {
                $ancestors = get_ancestors( $article->term_id, WPHelpKit_Article_Category::$category );
                foreach ( $ancestors as $ancestor ) {
                    $article_ancestor = get_term( $ancestor, WPHelpKit_Article_Category::$category );
                    $article_slug = $article_ancestor->slug . '/' . $article_slug;
                }
            }
        
        } else {
            // If no terms are assigned to this post, use a string instead (can't leave the placeholder there).
            $article_slug = _x( 'uncategorized', 'slug', 'wphelpkit' );
        }
        
        $permalink = str_replace( $category_placeholder, $article_slug, $permalink );
        return $permalink;
    }

}