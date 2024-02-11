<?php

defined('ABSPATH') || die;

/**
 * Abstract base class to manage Related Articles.
 *
 * Sub-classes *must* implement get_related_articles().
 * If they need to perform any initialization/etc they should override
 * WPHelpKit_Related_Articles::add_hooks() and/or WPHelpKit_Related_Articles::add_admin_hooks()
 * and hook into the appropriate actions/filters to perform that initialization.
 *
 * @since 0.0.3
 * @since 0.1.1 Renamed class to WPHelpKit_Related_Articles.
 */
abstract class WPHelpKit_Related_Articles
{
    /**
     * Our static instance.
     *
     * @since 0.0.3
     *
     * @var WPHelpKit_Related_Articles
     */
    private static $instance;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.3
     *
     * @return WPHelpKit_Related_Articles
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
     * @since 0.0.3
     *
     * @return WPHelpKit_Related_Articles
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        $this->add_hooks();
        $this->add_admin_hooks();
    }

    /**
     * Add hooks.
     *
     * @since 0.0.3
     *
     * @return void
     */
    protected function add_hooks()
    {
        return;
    }

    /**
     * Add admin hooks.
     *
     * @since 0.0.3
     *
     * @return void
     */
    protected function add_admin_hooks()
    {
        return;
    }

    /**
     * Retrieve list of related articles.
     *
     * Sub-classes *must* implement this method.
     *
     * @since 0.0.3
     *
     * @param WP_Post|int $post Optional. Post ID or WP_Post object. Default is global $post.
     * @return array array of article post IDs.
     */
    abstract public function get_related_articles($post = null);
}
