<?php

/**
 * Helper class to centralize all functionality around our post_type archive.
 *
 * This allows our `WPHelpKit_Article` class to be cleaner.
 *
 * @since 0.6.1
 * @since 0.6.2 Added 'archive_slug' key to the `$archive_info` property.
 *              Also removed the 'wphelpkit-index', 'menu_item_post_type'
 *              and 'menu_item_custom' archive "types".
 */
class WPHelpKit_Archive_Info
{
    /**
     * Our static instance.
     *
     * @since 0.6.1
     *
     * @var WPHelpKit_Article
     */
    private static $instance;

    /**
     * Cached archive info.
     *
     * @since 0.6.1
     *
     * @var array
     */
    protected $archive_info;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.6.1
     *
     * @return WPHelpKit_Article
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
     * @since 0.6.1
     *
     * @return WPHelpKit_Article
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
     * @since 0.6.1
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_filter('display_post_states', array( $this, 'add_index_post_state' ), 10, 2);

        // try to ensure we get the last say on canonical URLs
        remove_action('wp_head', 'rel_canonical');
        add_action('wp_head', array( $this, 'rel_canonical' ), 1);
        add_filter('get_canonical_url', array( $this, 'get_canonical_url' ), PHP_INT_MAX, 2);

        add_action('pre_get_posts', array( $this, 'pre_get_posts' ));

        return;
    }

    /**
     * Magic getter for our properties.
     *
     * @since 0.6.1
     *
     * @param string $name The property name to get.
     * @return string
     */
    public function __get($name)
    {
        static $archive_info_refreshed = false;

        if (! $this->archive_info || ( is_customize_preview() && ! $archive_info_refreshed )) {
            $this->archive_info = $this->get_archive_info();
            $archive_info_refreshed = true;
        }

        return isset($this->archive_info[ $name ]) ? $this->archive_info[ $name ] : '';
    }

    /**
     * Get archive info.
     *
     * @since 0.6.1
     *
     * @param string $name The name of the property to get.  Default '', in which case
     *                     an array of all properties is returned.
     * @return string|array
     */
    public function get_info($name = '')
    {
        static $archive_info_refreshed = false;

        if (! $this->archive_info || ( is_customize_preview() && ! $archive_info_refreshed )) {
            $this->archive_info = $this->get_archive_info();
            $archive_info_refreshed = true;
        }

        if (empty($name)) {
            return $this->archive_info;
        }

        return isset($this->archive_info[ $name ]) ? $this->archive_info[ $name ] : '';
    }

    /**
     * Gather the archive info.
     *
     * @since 0.6.1
     *
     * @return array
     */
    protected function get_archive_info()
    {
        $archive_info = $this->get_archive_info_settings_page();
        if ($archive_info) {
            return $archive_info;
        }

        // none of the above sources apply, return the "default" archive info.
        return array(
            'type'         => 'default',
            'url'          => get_post_type_archive_link(WPHelpKit_Article::$post_type),
            'text'         => apply_filters('wphelpkit-breadcrumbs-home-text', esc_html__('Index', 'wphelpkit')),
            'content'      => '',
            'archive_slug' => WPHelpKit_Article::$post_type,
        );
    }

    /**
     * Refresh the archive info.
     *
     * @see WPHelpKit_Article::register_post_type().
     *
     * @since 0.6.2
     *
     * @return void
     */
    public function refresh_archive_info()
    {
        $this->archive_info = $this->get_archive_info();

        return;
    }

    /**
     * Get archive info from the Customizer "Page".
     *
     * @since 0.6.1
     * @since 0.9.0 If page is also set as front page, then use CPT's slug as archive slug.
     * @since 0.9.1 If page is a child page, then get the proper slug.
     *
     * @return array|false Array of archive info if 'index_page' option is
     *                     set (and that page contains our archive block/shortcode);
     *                     false otherwise.
     */
    protected function get_archive_info_settings_page()
    {
        $settings_index_page = WPHelpKit_Settings::get_instance()->get_option('index_page');
        if ($settings_index_page) {
            $settings_index_page = get_post($settings_index_page);
            if ($settings_index_page && 'publish' === get_post_status($settings_index_page)) {

                // establish post type's archive slug
                if ( isset($settings_index_page->post_parent) && !empty($settings_index_page->post_parent) ) {
                    $permastruct = get_option('permalink_structure');
                    if(empty($permastruct)) {
                        // plain permalinks.
                        $post_type_slug = get_post_field( 'post_name', $settings_index_page );
                    } else {
                        // pretty permalinks.
                        $slug_path = substr( get_permalink( $settings_index_page ), 0, -1 );
                        $home_path = get_home_url() . '/';
                        $post_type_slug = str_replace( $home_path, '', $slug_path );
                    }
                } else {
                    $post_type_slug = get_post_field( 'post_name', $settings_index_page );
                }

                return array(
                    'type'         => 'settings_index_page',
                    'url'          => get_permalink($settings_index_page),
                    'id'           => $settings_index_page->ID,
                    'text'         => $settings_index_page->post_title,
                    'content'      => $settings_index_page->post_content,
                    'archive_slug' => $post_type_slug,
                );
            }
        }

        return false;
    }

    /**
     * Hook into pre_get_posts to do the main query.
     *
     * Used to set article post type archive to front page when WPHelpkit Index
     * page is also set as Front Page
     *
     * @since 0.9.0
     *
     * @param WP_Query $q Query instance.
     */
    public function pre_get_posts($q)
    {
        // We only want to affect the main query.
        if (! $q->is_main_query()) {
            return;
        }

        if ($q->is_page() && absint($q->get('page_id')) === absint(get_option('page_on_front')) &&
            absint($q->get('page_id')) === absint($this->id)) {
            // This is a front-page archive.
            $q->set('post_type', WPHelpKit_Article::$post_type);
            $q->set('page_id', '');

            // Fix conditional Functions like is_front_page.
            $q->is_singular          = false;
            $q->is_post_type_archive = true;
            $q->is_archive           = true;
            $q->is_page              = false;

            // Remove post type archive name from front page title tag.
            add_filter('post_type_archive_title', '__return_empty_string', 5);
        }
    }

    /**
     * Add our index post_state if appropriate.
     *
     * @since 0.6.1
     *
     * @param array $post_states Array of post_states.
     * @param WP_Post $post
     * @return array
     */
    public function add_index_post_state($post_states, $post)
    {
        $id = $this->get_info('id');
        if ($id == $post->ID) {
            $post_states[] = esc_html__('WPHelpKit Index', 'wphelpkit');
        }

        return $post_states;
    }

    /**
     * Outputs rel=canonical.
     *
     * Note that we hijack WP Core's hooking of @see rel_canonical() into `wp_head`.
     * If the current request is not for something that our special canonicalization
     * applies, then we revert to calling
     * {@link https://developer.wordpress.org/reference/functions/rel_canonical/ rel_canonical()}.
     *
     * @since 0.6.1
     *
     * @return void
     *
     * @action wp_head
     */
    public function rel_canonical()
    {
        if (is_singular() || ! is_post_type_archive(WPHelpKit_Article::$post_type)) {
            // let WP core handle it, but rely on us hooking into `get_canonical_url`
            // for the case of posts/pages that contain our archive block/shortcode.
            rel_canonical();

            return;
        }

        $canonical_url = esc_url($this->get_info('url'));

        echo '<link rel="canonical" href="' . esc_html($canonical_url) . '" />';

        return;
    }

    /**
     * Get the canonical URL for a post containing our archive block/shortcode.
     *
     * @since 0.6.1
     *
     * @param string $canonical_url
     * @param WP_Post $post
     * @return string
     *
     * @action get_canonical_url
     */
    public function get_canonical_url($canonical_url, $post)
    {
        if (! WPHelpKit_Article::get_instance()->contains_article_archive($post)) {
            return $canonical_url;
        }

        return set_url_scheme(WPHelpKit_Archive_Info::get_instance()->get_info('url'));
    }
}
