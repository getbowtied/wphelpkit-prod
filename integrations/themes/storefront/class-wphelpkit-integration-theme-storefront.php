<?php

/**
 * Theme integration for Storefront (version agnostic).
 *
 * This integration is known to work with versions 2.3.5 and 2.4.3 of Storefront.
 * It has not been tested with any other verisons.
 *
 * @since 0.6.0
 */
class WPHelpKit_Integration_Theme_Storefront extends WPHelpKit_Integration_Theme
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
     *
     * @filter wphelpkit-wrap-class
     */
    public function wrap_class($class)
    {
        $class[] = 'hentry';

        return $class;
    }
}
