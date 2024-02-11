<?php

defined( 'ABSPATH' ) || die;
/**
 * This class represents the WPHelpKit Article Category.
 *
 * @since 0.9.1
 */
class WPHelpKit_Article_Category
{
    /**
     * Our static instance.
     *
     * @since 0.9.1
     *
     * @var WPHelpKit_Article_Category
     */
    private static  $instance ;
    /**
     * Our "Categories" taxonomy.
     *
     * @since 0.0.1
     * @since 0.9.0 'wphelpkit-category' turned into 'helpkit-category'.
     *
     * @var string
     */
    public static  $category = 'helpkit-category' ;
    /**
     * Our category archive shortcode.
     *
     * @since 0.2.0
     *
     * @var string
     */
    public static  $category_archive_shortcode = 'wphelpkit_category' ;
    /**
     * Our drag-and-drop category order AJAX action.
     *
     * @since 0.0.4
     *
     * @var string
     */
    public static  $category_order_action = 'wphelpkit-category-order' ;
    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.9.1
     *
     * @return WPHelpKit_Article_Category
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
     * @return WPHelpKit_Article_Category
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
        add_action( 'init', array( $this, 'setup' ) );
        add_action( 'init', array( $this, 'add_shortcode' ) );
        add_action( 'template_redirect', array( $this, 'maybe_redirect_to_article' ) );
        add_action( 'pre_get_terms', array( $this, 'category_orderby' ) );
        add_action(
            'created_' . self::$category,
            array( $this, 'category_created' ),
            10,
            2
        );
        add_action(
            'delete_' . self::$category,
            array( $this, 'category_deleted' ),
            10,
            4
        );
        add_filter( 'pre_option_default_' . self::$category, array( $this, 'pre_default_category' ) );
        add_action(
            'save_post_' . WPHelpKit_Article::$post_type,
            array( $this, 'maybe_assign_default_category' ),
            10,
            3
        );
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
        add_action( 'wp_ajax_' . self::$category_order_action, array( $this, 'ajax_category_order' ) );
        add_action(
            self::$category . '_row_actions',
            array( $this, 'category_row_actions' ),
            10,
            2
        );
        add_action( 'restrict_manage_posts', array( $this, 'add_category_dropdown' ) );
        add_action( 'admin_action_wphelpkit-make-default-category', array( $this, 'make_default_category' ) );
        return;
    }
    
    /**
     * Perform basic setup operations.
     *
     * @since 0.9.1
     *
     * @return void
     *
     * @action init
     */
    public function setup()
    {
        $default_category = WPHelpKit_Settings::get_instance()->get_option( 'default_category' );
        
        if ( empty($default_category) || !get_term_by( 'id', $default_category, self::$category ) ) {
            $uncategorized_name = esc_html__( 'Uncategorized', 'wphelpkit' );
            $uncategorized = get_term_by( 'name', $uncategorized_name, self::$category );
            
            if ( !$uncategorized ) {
                $uncategorized = wp_insert_term( $uncategorized_name, self::$category );
                
                if ( is_wp_error( $uncategorized ) ) {
                    return $uncategorized;
                } else {
                    $uncategorized = get_term_by( 'id', $uncategorized['term_id'], self::$category );
                }
            
            }
            
            WPHelpKit_Settings::get_instance()->set_option( 'default_category', $uncategorized->term_id );
        }
    
    }
    
    /**
     * Add our article category archive shortcode.
     *
     * @since 0.9.1
     *
     * @return void
     *
     * @action init
     */
    public function add_shortcode()
    {
        add_shortcode( self::$category_archive_shortcode, array( $this, 'category_archive_shortcode' ) );
        return;
    }
    
    /**
     * Redirect category to first article when Settings option is set to true.
     *
     * @since 0.9.2
     *
     * @return void
     *
     * @action init
     */
    public function maybe_redirect_to_article()
    {
        if ( !(!is_search() && !WPHelpKit::is_previewing() && is_tax( self::$category ) && 'yes' === WPHelpKit_Settings::get_instance()->get_option( 'category_redirect_to_article' )) ) {
            return;
        }
        $taxonomy = WPHelpKit_Article_Category::$category;
        $current_category = get_term_by( 'slug', get_query_var( 'term' ), $taxonomy );
        $article = get_posts( array(
            'post_type'      => WPHelpKit_Article::$post_type,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'meta_value_num title',
            'order'          => 'ASC',
            'meta_key'       => WPHelpKit_Article::get_instance()->display_order_meta_key( $current_category->term_id ),
            'tax_query'      => array( array(
            'taxonomy'         => $taxonomy,
            'field'            => 'term_id',
            'terms'            => $current_category->term_id,
            'include_children' => false,
        ) ),
        ) );
        // category doesn't have a direct child
        
        if ( !isset( $article[0] ) ) {
            $child_categories = get_terms( array(
                'taxonomy'       => WPHelpKit_Article_Category::$category,
                'parent'         => $current_category->term_id,
                'hide_empty'     => true,
                'hierarchical'   => true,
                'posts_per_page' => -1,
                'orderby'        => WPHelpKit_Article::$display_order_meta_key,
                'pagination'     => true,
            ) );
            // category doesn't have a child category
            if ( empty($child_categories) ) {
                return;
            }
            foreach ( $child_categories as $child_category ) {
                $article = get_posts( array(
                    'post_type'      => WPHelpKit_Article::$post_type,
                    'post_status'    => 'publish',
                    'posts_per_page' => 1,
                    'orderby'        => 'meta_value_num title',
                    'order'          => 'ASC',
                    'meta_key'       => WPHelpKit_Article::get_instance()->display_order_meta_key( $child_category->term_id ),
                    'tax_query'      => array( array(
                    'taxonomy'         => $taxonomy,
                    'field'            => 'term_id',
                    'terms'            => $child_category->term_id,
                    'include_children' => false,
                ) ),
                ) );
                if ( isset( $article[0] ) ) {
                    break;
                }
            }
        }
        
        wp_safe_redirect( get_permalink( $article[0]->ID ), 301 );
        exit;
    }
    
    /**
     * Generate the output for our article archive shortcode.
     *
     * @since 0.2.0
     * @since 0.3.0 Added 'title' as secondary sort key for articles with the same
     *              display order.
     * @since 0.9.0 Added pagination to shortcode $attrs.
     * @since 0.9.1 Add new categories description attribute.
     * @since 0.9.1 Add new categories thumbnail attribute.
     * @since 0.9.1 Add new categories subcategories_description attribute.
     * @since 0.9.1 Add new categories articles_excerpt attribute.
     *
     * @param array $attrs Keys are shortcode attribute names, values are
     *                     shortcode attribute values.
     * @return string
     */
    public function category_archive_shortcode( $attrs )
    {
        global  $post ;
        WPHelpKit_Templates::load_template_tags();
        $settings = WPHelpKit_Settings::get_instance();
        $is_previewing = WPHelpKit::is_previewing();
        $default_attrs = array(
            'display_subcategories'     => true,
            'number_of_posts'           => ( is_customize_preview() ? -1 : get_option( 'posts_per_page', 10 ) ),
            'header'                    => true,
            'category'                  => '',
            'display_posts'             => $settings->get_option( '[article_archive]display_posts' ),
            'pagination'                => true,
            'description'               => $settings->get_option( '[article_archive]display_categories_description' ),
            'thumbnail'                 => $settings->get_option( '[article_archive]display_categories_thumbnail' ),
            'subcategories_description' => $settings->get_option( '[category_archive]display_subcategories_description' ),
            'articles_excerpt'          => $settings->get_option( '[category_archive]display_articles_excerpt' ),
        );
        $attrs = shortcode_atts( $default_attrs, $attrs );
        if ( is_numeric( $attrs['category'] ) ) {
            $attrs['category'] = intval( $attrs['category'] );
        }
        
        if ( is_int( $attrs['category'] ) ) {
            $attrs['category'] = get_term_by( 'id', $attrs['category'], self::$category );
        } else {
            $attrs['category'] = get_term_by( 'name', $attrs['category'], self::$category );
        }
        
        if ( !$attrs['category'] instanceof WP_Term ) {
            return '';
        }
        // sanitize int vals
        foreach ( array( 'number_of_posts' ) as $key ) {
            $attrs[$key] = intval( $attrs[$key] );
        }
        // sanitize boolean vals
        foreach ( array( 'display_posts', 'display_subcategories' ) as $key ) {
            if ( is_string( $attrs[$key] ) ) {
                $attrs[$key] = in_array( strtolower( $attrs[$key] ), array( 'true', '1' ) );
            }
        }
        $args = array(
            'post_type'      => WPHelpKit_Article::$post_type,
            'tax_query'      => array( array(
            'taxonomy'         => $attrs['category']->taxonomy,
            'field'            => 'id',
            'terms'            => $attrs['category']->term_id,
            'include_children' => false,
        ) ),
            'orderby'        => 'meta_value_num title',
            'order'          => 'ASC',
            'meta_key'       => WPHelpKit_Article::get_instance()->display_order_meta_key( $attrs['category']->term_id ),
            'posts_per_page' => ( is_customize_preview() ? -1 : $attrs['number_of_posts'] ),
            'paged'          => ( !is_customize_preview() && $attrs['pagination'] && get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 ),
        );
        $post_query = new WP_Query( $args );
        $html = $display = $see_all = '';
        $i = 0;
        $href = esc_url( get_term_link( $attrs['category'] ) );
        
        if ( is_customize_preview() || $attrs['display_posts'] ) {
            // save thee global `$post`.
            $the_global_post = $post;
            while ( $post_query->have_posts() ) {
                $post_query->the_post();
                // decide whether to show the article in the customizer live preview.
                $display = '';
                if ( $is_previewing ) {
                    $display = ( $attrs['display_posts'] && (-1 === $attrs['number_of_posts'] || $i < $attrs['number_of_posts']) ? '' : ' style="display: none"' );
                }
                $permalink = get_the_permalink();
                $categories = get_the_terms( get_the_ID(), self::$category );
                $data_category = '';
                if ( is_array( $categories ) && count( $categories ) > 1 ) {
                    // this article is in more than 1 category, so add a @data attribute so
                    // that article-link.js can set things up for correctly showing this
                    // category in the breadcrumbs.
                    $data_category = sprintf( " data-helpkit-category='%d'", $attrs['category']->term_id );
                }
                
                if ( is_tax( WPHelpKit_Article_Category::$category ) && ($attrs['articles_excerpt'] || is_customize_preview()) && !empty($article_excerpt = get_the_excerpt( get_the_ID() )) ) {
                    $display_excerpt = '';
                    if ( $is_previewing ) {
                        $display_excerpt = ( $attrs['articles_excerpt'] ? '' : ' style="display: none"' );
                    }
                    $html .= sprintf(
                        '<li class="wphelpkit-article" %s id="post-%d"><div class="wphelpkit-article-title-wrapper"><span class="wphelpkiticons wphelpkiticons-article"></span><a href="%s" class="wphelpkit-article-title"%s>%s</a></div><p class="wphelpkit-article-excerpt" %s>%s</p></li>',
                        $display,
                        get_the_ID(),
                        esc_url( $permalink ),
                        $data_category,
                        get_the_title(),
                        $display_excerpt,
                        wp_trim_words( $article_excerpt, 20 )
                    );
                } else {
                    $html .= sprintf(
                        '<li class="wphelpkit-article" %s id="post-%d"><div class="wphelpkit-article-title-wrapper"><span class="wphelpkiticons wphelpkiticons-article"></span><a href="%s" class="wphelpkit-article-title"%s>%s</a></div></li>',
                        $display,
                        get_the_ID(),
                        esc_url( $permalink ),
                        $data_category,
                        get_the_title()
                    );
                }
                
                $i++;
            }
            if ( !$attrs['pagination'] ) {
                
                if ( $post_query->post_count && -1 !== $attrs['number_of_posts'] && $post_query->found_posts > $attrs['number_of_posts'] || $is_previewing && $post_query->found_posts ) {
                    $display = ( $attrs['display_posts'] && (-1 !== $attrs['number_of_posts'] && $post_query->found_posts > $attrs['number_of_posts']) ? '' : ' style="display: none;"' );
                    $see_all = sprintf(
                        "<a href='%s' class='see-all'%s>%s</a>",
                        $href,
                        $display,
                        sprintf( __( 'See all %d articles', 'wphelpkit' ), $post_query->found_posts )
                    );
                }
            
            }
            // reset the global `$post'.
            wp_reset_postdata();
            $post = $the_global_post;
        }
        
        wp_enqueue_style( 'wphelpkit-styles' );
        $description = '';
        
        if ( is_customize_preview() || $attrs['description'] ) {
            $display = '';
            if ( $is_previewing ) {
                $display = ( $attrs['description'] ? '' : ' style="display: none"' );
            }
            $description = wp_trim_words( category_description( $attrs['category']->term_id ), 20 );
            $description = sprintf( "<div class='wphelpkit-category-description'%s>%s</div>", $display, $description );
        }
        
        $header = '';
        $image = '';
        $image_html = '';
        if ( $attrs['header'] ) {
            $header = $image_html . '<h2>' . '<a href="' . $href . '">' . $attrs['category']->name . '</a>' . '</h2>' . $description;
        }
        $sub_categories = wphelpkit_get_sub_categories( $attrs['category'], $attrs['display_subcategories'], $attrs['subcategories_description'] );
        
        if ( !is_post_type_archive( WPHelpKit_Article::$post_type ) ) {
            $class = apply_filters( 'wphelpkit-class', array() );
        } else {
            $class = array();
        }
        
        $default_class = array( 'wphelpkit', 'wphelpkit-category' );
        $class = array_merge( $default_class, $class );
        $class = implode( ' ', $class );
        $pagination = '';
        
        if ( !is_customize_preview() && $attrs['pagination'] ) {
            $pagination = paginate_links();
            $pagination = '<nav class="navigation pagination">' . '<div class="nav-links">' . $pagination . '</div>' . '</nav>';
        }
        
        if ( !empty($html) ) {
            $html = "<ul class='wphelpkit-articles'>" . $html . "</ul>";
        }
        return '<div class="' . $class . '" id="tag-' . $attrs['category']->term_id . '">' . $header . $sub_categories . $html . $see_all . $pagination . '</div>';
    }
    
    /**
     * Short-circuit WP Core trying to get our default category via `get_option()`.
     *
     * In order to have `WP_Terms_List_Table` not allow deleting our default
     * category via bulk actions and still allow the user to edit that default
     * category in both single site and multisite setups, it is easier to do
     * this than to hook into both `user_has_cap` and `map_meta_cap` and have
     * complicated logic that separate the cases and prevents the first while
     * allowing the second.
     *
     * @since 0.0.4
     *
     * @return int|string
     *
     * @filter pre_option_default_ . self::$category
     */
    public function pre_default_category()
    {
        return WPHelpKit_Settings::get_instance()->get_option( 'default_category' );
    }
    
    /**
     * Modify a query to order categories by their "display order" term meta.
     *
     * @since 0.0.4
     * @since 0.1.3 Removed all `WP_List_Table`-related article/category ordering functionality,
     *              as that is now handled in the Custominzer Live Preview.
     *
     * @param WP_Term_Query $query Current instance of WP_Term_Query.
     * @return void
     *
     * @action pre_get_terms
     */
    public function category_orderby( $query )
    {
        if ( !(!is_admin() && WPHelpKit_Article::$display_order_meta_key === $query->query_vars['orderby']) ) {
            return;
        }
        $meta_key = WPHelpKit_Article::get_instance()->display_order_meta_key();
        $meta_query_args = array(
            'display_order' => array(
            'key'     => $meta_key,
            'value'   => 0,
            'compare' => '>=',
        ),
        );
        $meta_query = new WP_Meta_Query( $meta_query_args );
        $query->meta_query = $meta_query;
        $query->query_vars['orderby'] = 'meta_value_num';
        $query->query_vars['meta_key'] = $meta_key;
        return;
    }
    
    /**
     * Modify row actions in our Category List Table.
     *
     * @since 0.0.3
     *
     * @param array $actions An array of action links to be displayed.
     *                       Default 'Edit', 'Quick Edit', 'Delete', and 'View'.
     * @param WP_Term $term The term whose row actions we're modifying.
     * @return array The modified `$actions`.
     *
     * @action wphelpkit-category_row_actions
     */
    public function category_row_actions( $actions, $term )
    {
        $default = WPHelpKit_Settings::get_instance()->get_option( 'default_category' );
        
        if ( $term->term_id === $default ) {
            // `$term` is the default, so don't let the user delete it.
            unset( $actions['delete'] );
        } else {
            // Add the "Make Default" aciton.
            $redirect_to = urlencode( remove_query_arg( array( 'action', 'action2' ), wp_kses_post( $_SERVER['REQUEST_URI'] ) ) );
            $args = array(
                'action'          => 'wphelpkit-make-default-category',
                'term_id'         => $term->term_id,
                'wp_http_referer' => $redirect_to,
            );
            $href = add_query_arg( $args, admin_url() );
            $actions['wphelpkit-make-default'] = sprintf( "<a href='%s'>%s</a>", $href, esc_html__( 'Make Default', 'wphelpkit' ) );
        }
        
        return $actions;
    }
    
    /**
     * Add a dropdown/filter to the edit.php screen for our Category taxonomy.
     *
     * @since 0.0.4
     *
     * @return void
     *
     * @action restrict_manage_posts
     */
    public function add_category_dropdown()
    {
        global  $typenow ;
        if ( WPHelpKit_Article::$post_type !== $typenow ) {
            return;
        }
        $tax_obj = get_taxonomy( self::$category );
        $tax_slug = WPHelpKit_Settings::get_instance()->get_option( 'category_base' );
        $selected = ( isset( $_REQUEST[$tax_slug] ) ? sanitize_text_field( $_REQUEST[$tax_slug] ) : '' );
        $args = array(
            'show_option_all' => sprintf( '%s %s', esc_html__( 'All', 'wphelpkit' ), $tax_obj->labels->name ),
            'taxonomy'        => self::$category,
            'name'            => $tax_slug,
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => $tax_obj->hierarchical,
            'show_count'      => true,
            'hide_empty'      => true,
            'hide_if_empty'   => true,
            'pad_counts'      => true,
            'value_field'     => 'slug',
            'fields'          => 'all',
        );
        wp_dropdown_categories( $args );
        return;
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
     *     @type string $taxonomy Fixed to 'helpkit-category', cannot be changed.
     * }
     * @return array|int|WP_Error List of WP_Term instances and their children.
     *                            Will return WP_Error, if any of $taxonomies do not exist.
     *                            Will return number of terms when 'count' is true.
     */
    public function get_categories( $args = array() )
    {
        $default_args = array(
            'taxonomy' => self::$category,
        );
        $args = wp_parse_args( $default_args, $args );
        return WPHelpKit_Article::get_instance()->get_terms( $args );
    }
    
    /**
     * Get the display order for a given category.
     *
     * @since 0.3.0
     *
     * @param WP_Term|int|string $term The category within which to set the
     *                                 display order for.  If an int, interpreted as
     *                                 the `term_id`; if a string, interpreted as the `slug`.
     * @return int|bool The display order on success, false otherwise.  When checking
     *                  for failure, must use `false === $retun_val` as 0 is a legal
     *                  return value.
     */
    public function get_category_display_order( $term )
    {
        if ( !$term instanceof WP_Term ) {
            
            if ( is_int( $term ) ) {
                $term = get_term_by( 'id', $term, self::$category );
            } elseif ( is_string( $term ) ) {
                $term = get_term_by( 'slug', $term, self::$category );
            }
        
        }
        if ( self::$category !== $term->taxonomy ) {
            // not our category taxonomy, nothing to do.
            return false;
        }
        $display_order = get_term_meta( $term->term_id, WPHelpKit_Article::get_instance()->display_order_meta_key(), true );
        if ( '' === $display_order ) {
            // display order not set.
            return 0;
        }
        return intval( $display_order );
    }
    
    /**
     * Update "display order" term meta via AJAX.
     *
     * @since 0.0.4
     *
     * @return void Send JSON back to caller and exists.
     *
     * @action wp_ajax_ . self::$category_order_action
     */
    public function ajax_category_order()
    {
        check_ajax_referer( self::$category_order_action . '-nonce', 'nonce' );
        
        if ( isset( $_REQUEST['data'] ) && is_array( $_REQUEST['data'] ) ) {
            $data = array_map( 'sanitize_text_field', $_REQUEST['data'] );
            parse_str( $data['order'], $order );
            if ( !is_array( $order ) ) {
                wp_send_json_error( "Error! Something went wrong with the ordering." );
            }
            $new_order = array();
            foreach ( $order['tag'] as $position => $id ) {
                $id = intval( $id );
                $this->set_category_display_order( $id, ++$position );
                $new_order[] = $position;
            }
            wp_send_json_success( $new_order );
        } else {
            wp_send_json_error( "Error! Invalid data." );
        }
    
    }
    
    /**
     * Set the display order for a given category.
     *
     * @since 0.3.0
     *
     * @param WP_Term|int|string $category The category within which to set the
     *                           display order for.  If an int, interpreted as
     *                           the `term_id`; if a string, interpreted as the `slug`.
     * @param int $display_order The display order to set.  Default 0.
     * @return bool Truthy value if successfully set, false otherwise.
     */
    public function set_category_display_order( $term, $display_order = 0 )
    {
        if ( !$term instanceof WP_Term ) {
            
            if ( is_int( $term ) ) {
                $term = get_term_by( 'id', $term, self::$category );
            } elseif ( is_string( $term ) ) {
                $term = get_term_by( 'slug', $term, self::$category );
            }
        
        }
        if ( self::$category !== $term->taxonomy ) {
            // not our category taxonomy, nothing to do.
            return false;
        }
        $display_order = intval( $display_order );
        return update_term_meta( $term->term_id, WPHelpKit_Article::get_instance()->display_order_meta_key(), $display_order );
    }
    
    /**
     * Delete "display order" post meta when the relevant category is deleted.
     *
     * @since 0.0.4
     *
     * @param int     $term         Term ID.
     * @param int     $tt_id        Term taxonomy ID.
     * @param mixed   $deleted_term Copy of the already-deleted term, in the form specified
     *                              by the parent function. WP_Error otherwise.
     * @param array   $object_ids   List of term object IDs.
     * @return void
     *
     * @action delete_wphelpkit-category
     */
    public function category_deleted(
        $term_id,
        $tt_id,
        $deleted_term,
        $object_ids
    )
    {
        foreach ( $object_ids as $post_id ) {
            delete_post_meta( $post_id, WPHelpKit_Article::$display_order_meta_key . '-' . $tt_id );
        }
        return;
    }
    
    /**
     * Add default "display order" post & term meta when a new category is created.
     *
     * @since 0.0.4
     *
     * @param int $term_id Term ID.
     * @param int $tt_id   Term taxonomy ID.
     * @return void
     *
     * @action create_wphelpkit-category
     */
    public function category_created( $term_id, $tt_id )
    {
        /**
         * Filters whether whether default display order post & term meta should
         * be added on category creation.
         *
         * @since 0.3.0
         *
         * @param bool $add_display_order A truthy value will add default display order
         *                                information; false will not add it.  The only
         *                                time it should *not* be added is during an import,
         *                                since in that case, the display order information
         *                                will come from the import.
         */
        if ( !apply_filters( 'wphelpkit-pre-category-created', true ) ) {
            return;
        }
        $this->set_category_display_order( $term_id, 0 );
        return;
    }
    
    /**
     * Assign the default category to articles that do not already have categories assigned.
     *
     * @since 0.0.3
     *
     * @param int $post_id
     * @param WP_Post $post
     * @param bool $update
     * @return void
     *
     * @action save_post_wphelpkit-article
     */
    public function maybe_assign_default_category( $post_id, $post, $update )
    {
        // ensure we really should be perform these steps.
        
        if ( !wphelpkit_is_json_request() ) {
            if ( isset( $_REQUEST['action'] ) && in_array( $_REQUEST['action'], array( 'trash', 'untrash' ) ) ) {
                return;
            }
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }
        }
        
        
        if ( !current_user_can( 'edit_post', $post_id ) ) {
            wp_die( 'Cheatin&#8217; uh?' );
            return;
        }
        
        $categories = get_the_terms( $post, self::$category );
        if ( !empty($categories) && !is_wp_error( $categories ) ) {
            // article already has categories, nothing to do.
            return;
        }
        // add the default category.
        // verify the default category actually exists before assinging it.
        $default_category = WPHelpKit_Settings::get_instance()->get_option( 'default_category' );
        
        if ( get_term_by( 'id', $default_category, self::$category ) ) {
            wp_set_post_terms( $post_id, $default_category, self::$category );
        } else {
            wp_send_json_error( "Error! Error assigning a default category to article." );
        }
        
        return;
    }
    
    /**
     * Make a category the default category.
     *
     * @since 0.0.3
     *
     * @return void
     *
     * @action admin_action_wphelpkit-make-default-category
     */
    public function make_default_category()
    {
        if ( !isset( $_REQUEST['term_id'] ) ) {
            return;
        }
        $term = get_term_by( 'id', sanitize_text_field( $_REQUEST['term_id'] ), self::$category );
        if ( !$term ) {
            return;
        }
        WPHelpKit_Settings::get_instance()->set_option( 'default_category', $term->term_id );
        wp_safe_redirect( esc_url_raw( $_REQUEST['wp_http_referer'] ) );
        exit;
    }

}