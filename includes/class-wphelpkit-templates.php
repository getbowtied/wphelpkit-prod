<?php

defined('ABSPATH') || die;

/**
 * Provide basic "templating" for WPHelpKit.
 *
 * @since 0.0.1
 * @since 0.0.2 Added support for search template.
 * @since 0.0.3 Added support for singular article template.
 * @since 0.1.1 Renamed class to WPHelpKit_Templates.
 */
class WPHelpKit_Templates
{
    /**
     * Our static instance.
     *
     * @since 0.0.1
     *
     * @var WPHelpKit_Templates
     */
    private static $instance;

    /**
     * Is one of our templates used?
     *
     * @since 0.0.1
     *
     * @var bool True if one of our templates is used, False if the theme
     *           or another plugin supplied an appropriate template.
     */
    protected $our_template_used = false;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.1
     *
     * @return WPHelpKit_Templates
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
     * @since 0.0.1
     *
     * @return WPHelpKit_Templates
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        if (! is_admin() || is_customize_preview()) {
            self::load_template_tags();
        }

        $this->add_hooks();
    }

    /**
     * Add hooks.
     *
     * Adding any hooks that fire *before* `wp` will have no effect,
     * because this class is not instantiated until the `wp` action fires.
     *
     * @since 0.0.1
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_filter('template_include', array( $this, 'template_include' ));

        add_filter('get_the_archive_title', array( $this, 'archive_title' ));

        add_action('wp_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ));

        add_action('helpkit_archive_content', array( $this, 'page_content' ));

        add_filter('body_class', array( $this, 'body_class' ));
        add_filter('post_class', array( $this, 'post_class' ), 10, 3);
        add_filter('wphelpkit-class', array( $this, 'wphelpkit_class' ));

        return;
    }

    /**
     * Load our template tages, so they are available for themes to use.
     *
     * @since 0.0.1
     * @since 0.0.5 Made a static method.
     *
     * @return void
     */
    public static function load_template_tags()
    {
        require_once __DIR__ . '/template-tags.php';

        return;
    }

    /**
     * Override the WP's template loading processing to use our templates
     * where appropriate.
     *
     * @since 0.0.1
     * @since 0.0.2 Added support for search template.
     * @since 0.0.3 Increments article view count if is_singular( 'wphelpkit-article' ) is true.
     *
     * @param string $template  Path to the template. See locate_template().
     * @return string
     *
     * @filter template_include
     */
    public function template_include($template)
    {
        if (! WPHelpKit_Article::get_instance()->could_use_template()) {
            return $template;
        }

        $template_slug = '';
        if (is_search() && WPHelpKit_Article::$post_type === get_query_var('post_type')) {
            $template_slug = 'search-helpkit-article.php';
        } elseif (is_post_type_archive(WPHelpKit_Article::$post_type)) {
            $template_slug = sprintf('archive-%s.php', WPHelpKit_Article::$post_type);
            wp_enqueue_script('wphelpkit-article-link');
        } elseif (is_tax()) {
            $template_slug = sprintf('taxonomy-%s.php', get_queried_object()->taxonomy);
            wp_enqueue_script('wphelpkit-article-link');
        } elseif (is_singular(WPHelpKit_Article::$post_type)) {
            $template_slug = sprintf('single-%s.php', WPHelpKit_Article::$post_type);

            // update the number of views for the given article
            // unless we're in the Live Preview.
            if ( wphelpkit_fs_init()->is__premium_only() && !is_customize_preview()) {
                WPHelpKit_Article::get_instance()->increment_number_of_views__premium_only();
            }
        }

        // Look within passed path within the theme - this is priority.
    	$overwritten_template = locate_template(
    		array(
    			trailingslashit( apply_filters( 'wphelpkit-template-part', 'wphelpkit/' ) ) . $template_slug,
    			$template_slug,
    		)
    	);

        if ( $overwritten_template ) {
            // the theme or another plugin already has a template for this
            // request, so just return it.
            $this->our_template_used = true;
            
            return $overwritten_template;
        }

        $_template = wp_normalize_path(realpath(sprintf('%s/../templates/%s', __DIR__, $template_slug)));
        if (! file_exists($_template)) {
            // we didn't supply the relavent template, so return the original
            return $template;
        }

        // save the fact that we are supplying the template
        // @see WPHelpKit_Templates::body_class()
        $this->our_template_used = true;

        return $_template;
    }

    /**
     * Override WP's default archive title.
     *
     * @since 0.0.1
     *
     * @param string $title
     * @return string
     *
     * @filter archive_title
     */
    public function archive_title($title)
    {
        if (is_post_type_archive(WPHelpKit_Article::$post_type)) {
            $archive_info = WPHelpKit_Archive_Info::get_instance();
            $title = $archive_info->text;
        } elseif (is_tax(WPHelpKit_Article_Category::$category) || is_tax(WPHelpKit_Article_Tag::$tag)) {
            $title = get_queried_object()->name;
        }

        return $title;
    }

    /**
     * Enqueue our scripts and styles.
     *
     * @since 0.0.1
     * @since 0.0.2 Added support for auto-TOC generation for articles.
     * @since 0.9.0 Moved support for auto-TOC generation in WPHelpKit_Article.
     *
     * @return void
     */
    public function enqueue_scripts_styles()
    {
        if (! WPHelpKit_Article::get_instance()->could_use_template()) {
            return;
        }

        wp_enqueue_style('wphelpkit-styles');

        return;
    }

    /**
     * Get various CSS classes and element @id's for use in our templates.
     *
     * Applies various HelpKit-specific filters so that themes can
     * override the defaults.
     *
     * @since 0.20
     *
     * @return array Keys are 'classes' and 'ids'.
     */
    public function get_classes_and_ids()
    {
        // get the CSS classes to be used for the "wrapper" elements
        $wrap = apply_filters('wphelpkit-wrap-class', array( 'wrap' ));
        $wrap = implode(' ', $wrap);

        $page_header = apply_filters('wphelpkit-page-header-class', array( 'entry-header' ));
        $page_header = implode(' ', $page_header);

        $archive_title = apply_filters('wphelpkit-archive-title-class', array( 'entry-title' ));
        $archive_title = implode(' ', $archive_title);

        $content_area = apply_filters('wphelpkit-content-area-class', array( 'content-area' ));
        $content_area = implode(' ', $content_area);

        $page_content = apply_filters('wphelpkit-page-content-class', array( 'page-content' ));
        $page_content = implode(' ', $page_content);

        $site_main = apply_filters('wphelpkit-site-main-class', array( 'site-main' ));
        $site_main = implode(' ', $site_main);

        $classes = compact('page_header', 'archive_title', 'content_area', 'site_main', 'wrap', 'page_content');

        // get the HTML @id's to be used for the "wrapper" elements
        $wrap = apply_filters('wphelpkit-wrap-id', 'wrap');
        $primary = apply_filters('wphelpkit-primary-id', 'primary');
        $main = apply_filters('wphelpkit-main-id', 'main');

        $ids = compact('wrap', 'primary', 'main');

        $ids_and_classes = array(
            'classes' => $classes,
            'ids' => $ids,
        );

        return $ids_and_classes;
    }

    /**
     * Add a class to html/body if we are supplying the template.
     *
     * This *may* make it easier to simplify the CSS for styling our templates.
     *
     * @since 0.0.1
     *
     * @param array $classes An array of body classes.
     * @return string
     */
    public function body_class($classes)
    {
        global $post;

        if ($this->is_template_used()) {
            $classes[] = 'wphelpkit-template';
        }

        if (has_block(WPHelpKit_Search::$block)) {
            $classes[] = 'wphelpkit-search-block';
        }

        if ($post) {
            if (has_shortcode($post->post_content, WPHelpKit_Article::$article_archive_shortcode)) {
                $classes[] = 'wphelpkit-archive-shortcode';
            }
            if (has_shortcode($post->post_content, WPHelpKit_Search::$shortcode)) {
                $classes[] = 'wphelpkit-search-shortcode';
            }
        }

        if (WPHelpKit_Article::get_instance()->is_post_type_archive()) {
            $classes[] = 'archive';
            $classes[] = 'post-type-archive';
            $classes[] = 'post-type-archive-' . WPHelpKit_Article::$post_type;
        }

        return $classes;
    }

    /**
     * Add a class to article tag of our post type.
     *
     * @since 0.2.0
     *
     * @param array $classes An array of body classes.
     * @return string
     */
    public function post_class($classes, $class, $post_id)
    {
        if (WPHelpKit_Article::$post_type === get_post_type($post_id)) {
            $classes[] = 'wphelpkit';
        }

        return $classes;
    }

    /**
     * Add Index page content to archive.
     *
     * @since 0.9.0
     *
     * @return void
     */
    public function page_content()
    {
        if (is_post_type_archive(WPHelpKit_Article::$post_type)) {
            $archive_info = WPHelpKit_Archive_Info::get_instance();
            echo wp_kses_post($archive_info->content);
        }

        return;
    }

    /**
     * Add 'entry-content' to various of our "top-level wrapper" elements.
     *
     * @since 0.2.0
     *
     * @param array $classes CSS classes to use on various WPHelpKit
     *                       "top-level wrapper" elements.
     * @return array
     *
     * @filter wphelpkit-class
     */
    public function wphelpkit_class($classes)
    {
        if (is_post_type_archive(WPHelpKit_Article::$post_type) ||
                is_tax(WPHelpKit_Article_Category::$category) || is_tax(WPHelpKit_Article_Tag::$tag) ) {
            $classes[] = 'entry-content';
        }

        return $classes;
    }

    /**
     * Is one of our templates being used?
     *
     * @since 0.6.0
     *
     * @return bool
     */
    public function is_template_used()
    {
        return $this->our_template_used;
    }
}
