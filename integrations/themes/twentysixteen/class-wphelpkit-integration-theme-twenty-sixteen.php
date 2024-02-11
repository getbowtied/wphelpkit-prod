<?php

/**
 * Theme integration for WP bundled Twenty Sixteen theme (version agnostic).
 *
 * This integration is known to work with versions 1.5, 1.6 and 1.7 of Twenty Sixteen.
 * It has not been tested with any other verisons.
 *
 * @since 0.6.0
 */
class WPHelpKit_Integration_Theme_Twenty_Sixteen extends WPHelpKit_Integration_Theme
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
        if (WPHelpKit_Article::$post_type === get_post_type($post_id)) {
            $classes[] = 'type-page';
        }

        return $classes;
    }

    public function sidebar($default)
    {
        return true;
    }
}
