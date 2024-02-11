<?php

/**
 * Abstract base class for theme integrations.
 *
 * Sub-classes for bundled integrations will be in the `integrations/themes/{theme_name}`
 * directory (`integrations/themes/twentynineteen`).  There will **always** be a version
 * agnostic integration in the root of that directory (e.g., the PHP sub-class and CSS files).
 *
 * If a version-specific bundled integration is necessary for a particular theme, it will be
 * located in a sub-directory whose name is based on the theme version in question
 * (e.g., `integrtaions/themes/twentynineteen/11` for version Twenty Nineteen version 1.1).
 * In such cases, the sub-class for the version-specific integration **should** be a sub-class
 * of the version agnostic integration's sub-class.  If the version-specific integration
 * requires **any** CSS changes from the version-agnostic integration, it **must** provide
 * **all** of the CSS styles it needs (even if they are a superset of the version-agnostic
 * styles) by setting `$this->asset_base_url` in their `__construct()` method and including the
 * relevant CSS files in its sub-directory.  However, if the filters they require are the
 * same as provided by the version-agnostic integration they need **not** provide
 * implementations of the relevent methods (i.e., they only need to provide implementations
 * of filter methods that are different from those provided by the version-agnostic
 * integration sub-class.
 *
 * @since 0.6.0
 */
abstract class WPHelpKit_Integration_Theme
{
    /**
     * Our static instance.
     *
     * @since 0.6.0
     *
     * @var WPHelpKit_Integration_Theme
     */
    private static $instance;

    /**
     * The version string to use when enqueueing assets.
     *
     * For built-in integrations it will be the version of WPHelpKit.
     * Themes that provide their own integrations **should** set
     * this in their `__construct()` method to the version of their theme.
     * If they do not, then the WPHelpKit version will be used.
     *
     * @since 0.6.0
     *
     * @var string
     */
    protected $version;

    /**
     * Base URL for integration assets.
     *
     * For built-in integrations it will be `plugins_url( '', __FILE__ )`.
     * Themes that provide their own integrations **must** set
     * this in their `__construct()` method using something like
     * `get_stylesheet_directory_uri() . '/path_to_assets'.
     *
     * @since 0.6.0
     *
     * @var string
     */
    protected $asset_base_url;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.6.0
     *
     * @param string $class PHP class name of subclass.
     * @return WPHelpKit_Integration_Theme
     */
    public static function get_instance($class)
    {
        if (! self::$instance) {
            self::$instance = new $class;
        }

        return self::$instance;
    }

    /**
     * Constructor.
     *
     * Initialize our static instance and add hooks.
     *
     * @since 0.6.0
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        $this->version = WPHelpKit::VERSION;

        // built-in integrations MUST set $this->asset_base_url in their
        // __construct() method.

        $this->add_hooks();
    }

    /**
     * Add hooks.
     *
     * Sub-classes that override this method **must** call `parent::add_hooks()`
     * before returning.
     *
     * @since 0.6.0
     *
     * @return void
     */
    protected function add_hooks()
    {
        // use PHP_INT_MAX to help ensure that assets enqueued by integrations
        // load after assets enqueued by the theme itself.
        add_action('wp_enqueue_scripts', array( $this, 'enqueue' ), PHP_INT_MAX);

        // no need to hook into our integration filters is were on the back-end.
        // these are the filters that integrations can hook into.
        // keys are either 'wphelpkit' for WPHelpKit defined filters or
        // 'core' for WP Core defined filters.
        // values are an array, of which keys are the number of arguments the hooked function accepts
        // and values are an array of hook names.
        $filters = array(
            'wphelpkit' => array(
                1 => array(
                    'wrap-id',
                    'main-id',
                    'primary-id',
                    'wrap-class',
                    'page-header-class',
                    'page-title-class',
                    'content-area-class',
                    'site-main-class',
                    'page-content-class',
                    'search-pagination',
                    'search-pagination-prev-text',
                    'search-pagination-next-text',
                ),
            ),
            'core' => array(
                1 => array(
                    'body_class',
                ),
                2 => array(
                    'is_active_sidebar',
                ),
                3 => array(
                    'post_class',
                ),
            ),
        );

        // loop through and add the appropriate filters.
        foreach ($filters as $who => $_filters) {
            foreach ($_filters as $num_args => $__filters) {
                foreach ($__filters as $filter) {
                    $method = $filter;
                    if ('wphelpkit' === $who) {
                        $method = str_replace('-', '_', $filter);
                        $filter = "wphelpkit-{$filter}";
                    }

                    if (method_exists($this, $method)) {
                        // use PHP_INT_MAX to help ensure that integrations' method
                        // is applied after any other functions hooked to the filter.
                        add_filter($filter, array( $this, $method ), PHP_INT_MAX, $num_args);
                    }
                }
            }
        }

        return;
    }

    /**
     * Enqueue styles.
     *
     * Sub-classes **may** override this method to control the assets they enqueue.
     * If they do not override this method, then they must include style.css, style-rtl.css
     * and minified versions of each.
     *
     * @since 0.6.0
     *
     * @return void
     */
    public function enqueue()
    {
        if (! $this->is_wphelpkit_template_used()) {
            // the current request is not using one of our templates
            // so no need to load our integration assets.
            return;
        }

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
        $rtl = is_rtl() ? '-rtl' : '';

        $class = get_class($this);
        $handle = "{$class}-style";
        $src = "{$this->asset_base_url}/style{$rtl}{$suffix}.css";

        wp_enqueue_style($handle, $src, array(), $this->version);

        return;
    }

    /**
     * Is a WPHelpKit template used for the current request?
     *
     * @since 0.6.0
     *
     * @return bool
     */
    public function is_wphelpkit_template_used()
    {
        return WPHelpKit_Templates::get_instance()->is_template_used();
    }

    // the methods below are the default/no-op methods (that just return
    // their args) used for each hook we provide.

    /**
     * Filters the wrap @id.
     *
     * @since 0.6.0
     *
     * @param string $id
     * @return string
     *
     * @filter wphelpkit-wrap-id
     */
    public function wrap_id($id)
    {
        return $id;
    }

    /**
     * Filters the primary @id.
     *
     * @since 0.6.0
     *
     * @param string $id
     * @return string
     *
     * @filter wphelpkit-primary-id
     */
    public function primary_id($id)
    {
        return $id;
    }

    /**
     * Filters the main @id.
     *
     * @since 0.6.0
     *
     * @param string $id
     * @return string
     *
     * @filter wphelpkit-main-id
     */
    public function main_id($id)
    {
        return $id;
    }

    /**
     * Filters the wrap @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-wrap-class
     */
    public function wrap_class($class)
    {
        return $class;
    }

    /**
     * Filters the page_header @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-page-header-class
     */
    public function page_header_class($class)
    {
        return $class;
    }

    /**
     * Filters the page_title @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-page-title-class
     */
    public function page_title_class($class)
    {
        return $class;
    }

    /**
     * Filters the content_area @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-content-area-class
     */
    public function content_area_class($class)
    {
        return $class;
    }

    /**
     * Filters the site_main @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-site-main-class
     */
    public function site_main_class($class)
    {
        return $class;
    }

    /**
     * Filters the page_content @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter wphelpkit-page-content-class
     */
    public function page_content_class($class)
    {
        return $class;
    }

    /**
     * Filters the search pagination.
     *
     * @since 0.6.0
     *
     * @param string $pagination HTML markup for search results pagination.
     * @return string
     *
     * @filter wphelpkit-search-pagination
     */
    public function search_pagination($pagination)
    {
        return $pagination;
    }

    /**
     * Filters the search pagination "Previous" text.
     *
     * @since 0.6.0
     *
     * @param string $text Text to use for the "Previous" link.
     * @return string
     *
     * @filter wphelpkit-search-pagination-prev-text
     */
    public function search_pagination_prev_text($text)
    {
        return $text;
    }

    /**
     * Filters the search pagination "Next" text.
     *
     * @since 0.6.0
     *
     * @param string $text Text to use for the "Next" link.
     * @return string
     *
     * @filter wphelpkit-search-paginiation-next-text
     */
    public function search_pagination_next_text($text)
    {
        return $text;
    }

    /**
     * Filters the body @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter body_class
     */
    public function body_class($class)
    {
        return $class;
    }

    /**
     * Filters the post @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     *
     * @filter post_class
     */
    public function post_class($classes, $class, $post_id)
    {
        return $classes;
    }

    /**
     * Filters the sidebar templates.
     *
     * @since 0.9.0
     *
     * @param boolean $is_active_sidebar
     * @return boolean
     *
     * @filter post_class
     */
    public function is_active_sidebar($is_active_sidebar)
    {
        return $is_active_sidebar;
    }
}
