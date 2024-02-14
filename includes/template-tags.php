<?php

/**
 * Template tags for use by our templates (or other themes).
 *
 * @since 0.0.1
 * @since 0.0.2 Added preliminary support for Customizer live-preview
 *              for some template tags.
 * @since 0.0.5 Added support for article archive as a template tag.
 */
defined( 'ABSPATH' ) || die;
/**
 * Generate category breadcrumbs.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed to wphelpkit_get_breadcrumbs().
 * @since 0.2.3 For searches, use the search term(s) as the "leaf" node.
 * @since 0.9.0 Always display a 'Home' link
 *
 * @return string
 */
function wphelpkit_get_breadcrumbs()
{
    global  $post ;
    $delimiter = esc_html__( '&#47;', 'wphelpkit' );
    $display = WPHelpKit_Settings::get_instance()->get_option( 'breadcrumbs' ) || is_search() || is_tax( WPHelpKit_Article_Tag::$tag );
    if ( !is_customize_preview() ) {
        if ( !$display ) {
            return '';
        }
    }
    $display = ( $display ? '' : ' style="display: none"' );
    $archive_info = WPHelpKit_Archive_Info::get_instance();
    $breadcrumbs = array();
    // always start with the "home" link
    $homepage_output = get_option( 'show_on_front' );
    
    if ( $homepage_output === 'page' ) {
        $frontpage_id = get_option( 'page_on_front' );
    } else {
        if ( $homepage_output === 'posts' ) {
            $frontpage_id = get_option( 'page_for_posts' );
        }
    }
    
    
    if ( $frontpage_id && $frontpage_id == $archive_info->id ) {
        // if Helpkit Index is also the frontpage, then display a 'Home' link
        // to that page/archive
        $breadcrumbs[] = sprintf( '<a href="%s">%s</a>', esc_url( $archive_info->url ), esc_html__( 'Home', 'wphelpkit' ) );
    } else {
        // get the homepage link
        $breadcrumbs[] = sprintf( '<a href="%s">%s</a>', esc_url( get_home_url() ), esc_html__( 'Home', 'wphelpkit' ) );
        // get the articles archive
        $breadcrumbs[] = sprintf(
            '<span class="breadcrumb-separator">%s</span><a href="%s">%s</a>',
            $delimiter,
            esc_url( $archive_info->url ),
            esc_html( $archive_info->text )
        );
    }
    
    // get the "current" term, depending on whether we're in a taxonomy
    // archive or a single post
    
    if ( is_tax( WPHelpKit_Article_Category::$category ) ) {
        // save the category taxonomy, to ensure it gets used throughout
        $taxonomy = WPHelpKit_Article_Category::$category;
        $term = get_term_by( 'slug', get_query_var( 'term' ), $taxonomy );
        $_term = $term;
    } elseif ( is_tax( WPHelpKit_Article_Tag::$tag ) ) {
        // save the tag taxonomy, to ensure it gets used throughout
        $taxonomy = WPHelpKit_Article_Tag::$tag;
        $term = get_term_by( 'slug', get_query_var( 'term' ), $taxonomy );
        $_term = $term;
    } elseif ( is_singular( WPHelpKit_Article::$post_type ) ) {
        $taxonomy = WPHelpKit_Article_Category::$category;
        $term = wp_get_post_terms( $post->ID, $taxonomy );
        if ( $term[0] ) {
            $term = $_term = $term[0];
        }
    } elseif ( !is_search() ) {
        // prevent output if called from an inappropriate place.
        return;
    }
    
    // walk up the tree and gathering ancestor terms
    $_breadcrumbs = array();
    while ( isset( $_term->parent ) && $_term->parent > 0 ) {
        $_term = get_term( $_term->parent, $taxonomy );
        $crumb = sprintf(
            '<span class="breadcrumb-separator">%s</span><a href="%s">%s</a>',
            $delimiter,
            get_term_link( $_term, $taxonomy ),
            $_term->name
        );
        $_breadcrumbs[] = $crumb;
    }
    // reverse them so they are in the correct order.
    $breadcrumbs = array_merge( $breadcrumbs, array_reverse( $_breadcrumbs ) );
    // add the "leaf" node
    
    if ( is_singular( WPHelpKit_Article::$post_type ) ) {
        if ( $term instanceof WP_Term ) {
            $breadcrumbs[] = sprintf(
                '<span class="breadcrumb-separator">%s</span><a href="%s">%s</a>',
                $delimiter,
                get_term_link( $term, $taxonomy ),
                $term->name
            );
        }
        $title = $post->post_title;
        
        if ( isset( $_REQUEST['leaf_article'] ) && $_REQUEST['leaf_article'] ) {
            $leaf = sanitize_text_field( $_REQUEST['leaf_article'] );
            if ( is_search() && intval( $leaf ) ) {
                $title = get_the_title( intval( $leaf ) );
            }
        }
        
        $breadcrumbs[] = sprintf( '<span class="breadcrumb-separator">%s</span><span>%s</span>', $delimiter, $title );
    } elseif ( is_search() ) {
        $breadcrumbs[] = sprintf( '<span class="breadcrumb-separator">%s</span><span>%s</span>', $delimiter, sanitize_text_field( $_REQUEST['s'] ) );
    } elseif ( isset( $term ) ) {
        $breadcrumbs[] = sprintf( '<span class="breadcrumb-separator">%s</span><span>%s</span>', $delimiter, $term->name );
    }
    
    /**
     * Filters the breadcrumbs before they are ourput.
     *
     * @since 0.6.4
     *
     * @param array   $breadcrumbs Array of HTML LI tags for the breadcrumbs.
     * @param WP_Post The article post.
     */
    $breadcrumbs = apply_filters( 'wphelpkit-breadcrumbs', $breadcrumbs, $post );
    $aria_label = esc_html__( 'Breadcrumb', 'wphelpkit' );
    $breadcrumbs = implode( '', $breadcrumbs );
    $display = ( is_search() || is_tax( WPHelpKit_Article_Tag::$tag ) || WPHelpKit_Settings::get_instance()->get_option( 'breadcrumbs' ) ? '' : " style='display: none';" );
    return '<nav class="wphelpkit-breadcrumbs" aria-label="' . $aria_label . '" ' . $display . '>' . $breadcrumbs . '</nav>';
}

/**
 * Output category breadcrumbs.
 *
 * @since 0.0.1
 * @since 0.0.2 Added support for single post as leaf node.
 * @since 0.1.1 Renamed to wphelpkit_breadcrumbs().
 *
 * @return void Echo's its output.
 */
function wphelpkit_breadcrumbs()
{
    echo  wphelpkit_get_breadcrumbs() ;
    return;
}

/**
 * Generate sub-categories.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed to wphelpkit_get_sub_categories().
 * @since 0.9.1 add subcategory description based on
 *                [category_archive]display_subcategories_description option
 *
 * @param WP_Term $term The term whose children should be output.
 * @param bool    $display Whether to display/hide the subcategories.
 *                The markup is generated either way.
 * @param bool    $descriptions Whether to display/hide the subcategories descriptions.
 * @return string|WP_Error
 */
function wphelpkit_get_sub_categories( $term = null, $display = true, $descriptions = false )
{
    if ( !is_customize_preview() && !$display ) {
        return '';
    }
    if ( empty($term) ) {
        $term = get_queried_object();
    }
    $args = array(
        'parent'  => $term->term_id,
        'orderby' => WPHelpKit_Article::$display_order_meta_key,
    );
    $children = WPHelpKit_Article_Category::get_instance()->get_categories( $args );
    if ( is_wp_error( $children ) ) {
        return $children;
    }
    if ( empty($children) ) {
        return '';
    }
    $_children = array();
    foreach ( $children as $child ) {
        /**
         * Filters whether to include article counts in all descendants of child.
         *
         * @since 0.1.3
         *
         * @param bool $include_children Return a truthy value to show counts
         *                               for all descendant categories; oterwise will
         *                               show only the count of articles directory in
         *                               the category.
         * @param WP_Term $category      The category to get article counts for.
         */
        $include_children = apply_filters( 'wphelpkit-category-article-counts-include-children', false, $child );
        $count = WPHelpKit_Article::get_instance()->get_article_count( $child, $include_children );
        
        if ( is_tax( WPHelpKit_Article_Category::$category ) && ($descriptions || is_customize_preview()) && !empty($subcategory_description = category_description( $child->term_id )) ) {
            $display_description = '';
            if ( WPHelpKit::is_previewing() ) {
                $display_description = ( WPHelpKit_Settings::get_instance()->get_option( '[category_archive]display_subcategories_description' ) ? '' : ' style="display: none"' );
            }
            $_children[] = sprintf(
                "<li class='wphelpkit-subcategory' id='tag-{$child->term_id}'><div class='wphelpkit-subcategory-title-wrapper'><span class='wphelpkiticons wphelpkiticons-folder'></span><a href='%s' class='wphelpkit-subcategory-title'>%s</a><span class='wphelpkit-article-count'>{$count}</span></div><p class='wphelpkit-subcategory-description' %s>%s</p></li>",
                get_term_link( $child ),
                $child->name,
                $display_description,
                wp_trim_words( $subcategory_description, 20 )
            );
        } else {
            $_children[] = sprintf( "<li class='wphelpkit-subcategory' id='tag-{$child->term_id}'><div class='wphelpkit-subcategory-title-wrapper'><span class='wphelpkiticons wphelpkiticons-folder'></span><a href='%s' class='wphelpkit-subcategory-title'>%s</a><span class='wphelpkit-article-count'>{$count}</span></div></li>", get_term_link( $child ), $child->name );
        }
    
    }
    $_children = implode( '', $_children );
    $display = ( $display && !empty($children) ? '' : ' style="display: none"' );
    return '<ul class="wphelpkit-subcategories"' . $display . '>' . $_children . '</ul>';
}

/**
 * Output sub-categories.
 *
 * @since 0.0.1
 * @since 0.1.1 Renamed to wphelpkit_sub_categories().
 *
 * @param WP_Term $term The term whose children should be output.
 * @return void Echo's its output.
 */
function wphelpkit_sub_categories( $term = null )
{
    echo  wp_kses_post( wphelpkit_get_sub_categories( $term ) ) ;
    return;
}

/**
 * Generate our search form.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed to wphelpkit_get_search_form().
 *
 * @param array $attrs Attributes for our search shortcode.
 * @return string
 */
function wphelpkit_get_search_form( $attrs = array() )
{
    $_attrs = '';
    foreach ( $attrs as $name => $val ) {
        $_attrs .= sprintf( ' %s="%s"', $name, esc_attr( $val ) );
    }
    $_attrs = trim( $_attrs );
    $shortcode = sprintf( '[%s %s]', WPHelpKit_Search::$shortcode, $_attrs );
    return do_shortcode( $shortcode );
}

/**
 * Output our search form.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed to wphelpkit_search_form().
 *
 * @param array $attrs Attributes for our search shortcode.
 * @param string $content Content for our search shortcode.
 * @return void Echo's it's output.
 */
function wphelpkit_search_form( $attrs = array(), $content = '' )
{
    echo  wphelpkit_get_search_form( $attrs, $content ) ;
    return;
}

/**
 * Generate related articles.
 *
 * @since 0.0.3
 * @since 0.1.1 Renamed to wphelpkit_get_related_articles().
 * @since 0.9.0 Changed heading markup to h3.
 *
 * @param WP_Post|int $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return string
 */
function wphelpkit_get_related_articles( $post = null )
{
    $post = get_post( $post );
    $related_articles = WPHelpKit_Article::get_instance()->get_related_articles( $post );
    if ( empty($related_articles) ) {
        return '';
    }
    $display = WPHelpKit_Settings::get_instance()->get_option( 'related_articles' );
    
    if ( !is_customize_preview() ) {
        if ( !$display ) {
            return '';
        }
        $display = '';
    } else {
        $display = ( $display ? '' : ' style="display: none"' );
    }
    
    $_related_articles = '';
    foreach ( $related_articles as $related_article ) {
        $permalink = get_the_permalink( $related_article );
        $title = get_the_title( $related_article );
        $_related_articles .= "<li class='wphelpkit-article'><span class='wphelpkiticons wphelpkiticons-article'></span><a href='{$permalink}' class='wphelpkit-article-title'>{$title}</a></li>";
    }
    $heading = esc_html__( 'Related Articles', 'wphelpkit' );
    return '<div class="wphelpkit-related-articles"' . $display . '>' . '<h3>' . $heading . '</h3>' . '<ul>' . $_related_articles . '</ul>' . '</div>';
}

/**
 * Output related articles.
 *
 * @since 0.0.3
 * @since 0.1.1 Renamed to wphelpkit_related_articles().
 *
 * @param WP_Post|int $post Optional. Post ID or WP_Post object. Default is global $post.
 * @return void Echo's its output.
 */
function wphelpkit_related_articles( $post = null )
{
    echo  wp_kses_post( wphelpkit_get_related_articles( $post ) ) ;
    return;
}

/**
 * Generate the article archive.
 *
 * @since 0.0.5
 * @since 0.1.1 Renamed to wphelpkit_get_archive().
 * @since 0.9.1 Add new display_categories_description attribute.
 * @since 0.9.1 Add new display_categories_thumbnail attribute.
 *
 * @param array $attrs {
 *     Optional.
 *
 *     @type string $heading The text to display at the top of the archive.
 *     @type bool   $display_posts Whether to display articles under each category.
 *     @type int    $number_of_posts Number of articles to display if `$display_posts` is true.
 *     @type bool   $display_subcategories Whether to display subcategories.
 *     @type int    $number_of_columns Number of columns to display categories in.
 *     @type bool   $search Whether to display the search box.
 * }
 * @return string
 */
function wphelpkit_get_archive( $attrs = array() )
{
    $settings = WPHelpKit_Settings::get_instance();
    $default_attrs = array(
        'display_posts'                  => $settings->get_option( '[article_archive]display_posts' ),
        'number_of_posts'                => $settings->get_option( '[article_archive]number_of_posts' ),
        'display_subcategories'          => $settings->get_option( '[article_archive]display_subcategories' ),
        'number_of_columns'              => $settings->get_option( '[article_archive]number_of_columns' ),
        'search'                         => $settings->get_option( '[article_archive]search' ),
        'display_categories_description' => $settings->get_option( '[article_archive]display_categories_description' ),
        'display_categories_thumbnail'   => $settings->get_option( '[article_archive]display_categories_thumbnail' ),
    );
    $_attrs = '';
    $attrs = shortcode_atts( $default_attrs, $attrs );
    foreach ( $attrs as $name => $val ) {
        $_attrs .= sprintf( ' %s="%s"', $name, esc_attr( $val ) );
    }
    $_attrs = trim( $_attrs );
    $shortcode = sprintf( '[%s %s]', WPHelpKit_Article::$article_archive_shortcode, $_attrs );
    return do_shortcode( wp_kses_post( $shortcode ) );
}

/**
 * Output the article archive.
 *
 * @since 0.0.5
 * @since 0.1.1 Renamed to wphelpkit_archive().
 *
 * @param array $attrs {
 *     Optional.
 *
 *     @type string $heading The text to display at the top of the archive.
 *     @type bool   $display_posts Whether to display articles under each category.
 *     @type int    $number_of_posts Number of articles to display if `$display_posts` is true.
 *     @type bool   $display_subcategories Whether to display subcategories.
 *     @type int    $number_of_columns Number of columns to display categories in.
 *     @type bool   $search Whether to display the search box.
 * }
 * @return string
 */
function wphelpkit_archive( $attrs = array() )
{
    echo  wphelpkit_get_archive( $attrs ) ;
    //escaped in the function: return do_shortcode(wp_kses_post($shortcode));
    return;
}

/**
 * Generate a category archive.
 *
 * @since 0.2.0
 * @since 0.9.1 Add new description attribute.
 * @since 0.9.1 Add new thumbnail attribute.
 * @since 0.9.1 Add new subcategories_description attribute.
 * @since 0.9.1 Add new articles_excerpt attribute.
 *
 * @param array $attrs {
 *     `$category` is required.  All others are optional.
 *
 *     @type string|int $category The category whose archive is to be displayed.
 *                                If an int, interpreted as the `term_id`; if a
 *                                string, interpreted as the `name`.
 *     @type bool $header Whether the display the category name as a linked HTML h2 tag.
 *     @type int  $number_of_posts Number of articles to display.
 *     @type bool $display_subcategories Whether to display subcategories.
 *     @type bool display_posts Whether to display posts.
 *     @type bool pagination Whether to display pagination.
 *     @type bool description Whether to display category's description in CPT's archive.
 *     @type bool thumbnail Whether to display category's thumbnail in CPT's archive.
 *     @type bool subcategories_description Whether to display subcategory's description in category archive.
 *     @type bool articles_excerpt Whether to display article's excerpt in category archive.
 * }
 * @return string
 */
function wphelpkit_get_category_archive( $attrs = array() )
{
    $settings = WPHelpKit_Settings::get_instance();
    $default_attrs = array(
        'category'                  => '',
        'number_of_posts'           => ( is_customize_preview() ? -1 : get_option( 'posts_per_page', 10 ) ),
        'header'                    => true,
        'display_posts'             => $settings->get_option( '[article_archive]display_posts' ),
        'display_subcategories'     => true,
        'pagination'                => true,
        'description'               => $settings->get_option( '[article_archive]display_categories_description' ),
        'thumbnail'                 => $settings->get_option( '[article_archive]display_categories_thumbnail' ),
        'subcategories_description' => $settings->get_option( '[category_archive]display_subcategories_description' ),
        'articles_excerpt'          => $settings->get_option( '[category_archive]display_articles_excerpt' ),
    );
    $_attrs = '';
    $attrs = shortcode_atts( $default_attrs, $attrs );
    foreach ( $attrs as $name => $val ) {
        $_attrs .= sprintf( ' %s="%s"', $name, esc_attr( $val ) );
    }
    $_attrs = trim( $_attrs );
    $shortcode = sprintf( '[%s %s]', WPHelpKit_Article_Category::$category_archive_shortcode, $_attrs );
    return do_shortcode( $shortcode );
}

/**
 * Generate a tag archive.
 *
 * @since 0.9.0
 *
 * @param array $attrs {
 *     `tag` is required.
 * }
 * @return string
 */
function wphelpkit_get_tag_archive( $attrs = array() )
{
    $settings = WPHelpKit_Settings::get_instance();
    $default_attrs = array(
        'tag'             => '',
        'number_of_posts' => ( is_customize_preview() ? -1 : get_option( 'posts_per_page', 10 ) ),
        'pagination'      => true,
    );
    $_attrs = '';
    $attrs = shortcode_atts( $default_attrs, $attrs );
    foreach ( $attrs as $name => $val ) {
        $_attrs .= sprintf( ' %s="%s"', $name, esc_attr( $val ) );
    }
    $_attrs = trim( $_attrs );
    $shortcode = sprintf( '[%s %s]', WPHelpKit_Article_Tag::$tag_archive_shortcode, $_attrs );
    return do_shortcode( wp_kses_post( $shortcode ) );
}

/**
 * Output a category archive.
 *
 * @since 0.2.0
 *
 * @param array $attrs {
 *     `$category` is required.  All others are optional.
 *
 *     @type string|int $category The category whose archive is to be displayed.
 *                                If an int, interpreted as the `term_id`; if a
 *                                string, interpreted as the `name`.
 *     @type bool $header Whether the display the category name as a linked HTML h2 tag.
 *     @type int  $number_of_posts Number of articles to display.
 *     @type bool $display_subcategories Whether to display subcategories.
 * }
 * @return void Echo's its output.
 */
function wphelpkit_category_archive( $attrs )
{
    echo  wphelpkit_get_category_archive( $attrs ) ;
}

/**
 * Output a tag archive.
 *
 * @since 0.9.0
 *
 * @param array $attrs {
 *     `tag` is required.
 * }
 * @return void Echo's its output.
 */
function wphelpkit_tag_archive( $attrs )
{
    echo  wphelpkit_get_tag_archive( $attrs ) ;
    //escaped in the function: return do_shortcode(wp_kses_post($shortcode));
}
