<?php

/**
 * Theme integration for WP bundled Twenty Seventeen theme (version agnostic).
 *
 * This integration is known to work with versions 1.7, 1.8 and 1.9 of Twenty Seventeen.
 * It has not been tested with any other verisons.
 *
 * @since 0.6.0
 */
class WPHelpKit_Integration_Theme_Twenty_Seventeen extends WPHelpKit_Integration_Theme
{
    /**
     * Constructor.
     *
     * Initialize our static instance and add hooks.
     *
     * @since 0.6.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->asset_base_url = plugins_url('', __FILE__);
    }

    /**
     * Filters the page_header @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     */
    public function page_header_class($class)
    {
        return array( 'page-header' );
    }

    /**
     * Filters the page_title @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     */
    public function page_title_class($class)
    {
        return array( 'page-title' );
    }

    /**
     * Filters the search pagination.
     *
     * @since 0.6.0
     *
     * @param string $pagination HTML markup for search results pagination.
     * @return string
     */
    public function search_pagination($pagination)
    {
        ob_start();

        the_posts_pagination(array(
            'prev_text' => twentyseventeen_get_svg(array( 'icon' => 'arrow-left' )) . '<span class="screen-reader-text">' . esc_html__('Previous page', 'twentyseventeen') . '</span>',
            'next_text' => '<span class="screen-reader-text">' . esc_html__('Next page', 'twentyseventeen') . '</span>' . twentyseventeen_get_svg(array( 'icon' => 'arrow-right' )),
            'before_page_number' => '<span class="meta-nav screen-reader-text">' . esc_html__('Page', 'twentyseventeen') . ' </span>',
        ));

        $pagination = ob_get_clean();

        return $pagination;
    }

    /**
     * Hack: Make sure our post_type/taxonomy archives don't have a sidebar.
     *
     * @since 0.0.1
     * @since 0.1.1 Use WPHelpKit_Templates::$our_template_used to determine whether to deactivate sidebars.
     * @since 0.6.0 Use WPHelpKit_Templates::is_template_used() to determine whether to deactivate sidebars.
     * @since 0.9.0 Moved to this class as part of Twenty Seventeen integration.
     *
     * @param bool $is_active_sidebar Whether or not the sidebar should be considered
     *                                "active". In other words, whether the sidebar
     *                                contains any widgets.
     * @param int|string $index Index, name, or ID of the dynamic sidebar.
     * @return bool
     *
     * @filter is_active_sidebar
     */
    public function is_active_sidebar($is_active_sidebar)
    {
        if (WPHelpKit_Templates::get_instance()->is_template_used()) {
            $is_active_sidebar = false;
        }

        return $is_active_sidebar;
    }
}
