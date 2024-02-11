<?php

/**
 * Theme integration for WP bundled Twenty Nineteen theme (version agnostic).
 *
 * This integration is known to work with versions 1.0 and 1.1 of Twenty Nineteen
 * (and to not work with at least one of the 1.0 "pre-release" verisons, which
 * unfortunately, all had "Version: 1.0" in their style.css header).
 * It has not been tested with any other verisons.
 *
 * @since 0.6.0
 */
class WPHelpKit_Integration_Theme_Twenty_Nineteen extends WPHelpKit_Integration_Theme
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
     * Filters the wrap @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     */
    public function wrap_class($class)
    {
        $class[] = 'entry';

        return $class;
    }

    /**
     * Filters the page_content @class.
     *
     * @since 0.6.0
     *
     * @param array $class Array of CSS classes.
     * @return array
     */
    public function page_content_class($class)
    {
        return array( 'entry-content' );
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

        twentynineteen_the_posts_navigation();

        $pagination = ob_get_clean();

        return $pagination;
    }
}
