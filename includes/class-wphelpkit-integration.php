<?php

/**
 * Class to handle integrations.
 *
 * @since 0.6.0
 *
 */
class WPHelpKit_Integration
{
    /**
     * Our static instance.
     *
     * @since 0.6.0
     *
     * @var WPHelpKit_Integration
     */
    private static $instance;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.6.0
     *
     * @return WPHelpKit_Integration
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
     * @since 0.6.0
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
     * @since 0.6.0
     *
     * @return void
     */
    public function add_hooks()
    {
        add_action('init', array( $this, 'load_integrations' ));

        return;
    }

    /**
     * Load integrations.
     *
     * @since 0.6.0
     *
     * @return void
     *
     * @action init
     *
     */
    public function load_integrations()
    {
        $this->load_theme_integration();

        return;
    }

    /**
     * Load theme integrations.
     *
     * @since 0.6.0
     *
     * @return void
     *
     * @action init
     *
     */
    protected function load_theme_integration()
    {
        // get current theme and generate the possible PHP class names
        // for integrations.
        $theme = wp_get_theme();
        $classes = $this->generate_theme_class_names($theme);

        /**
         * Filters the theme integration PHP classes to search for.
         *
         * @since 0.6.0
         *
         * @param array $classes PHP classes to search for.
         * @param WP_Theme $theme Current theme.
         */
        $classes = apply_filters('wphelpkit-integration-theme', $classes, $theme);

        // loop over classes until one is found that is a sub-class of WPHelpKit_Integration_Theme.
        foreach ((array) $classes as $class) {
            // note: the comparison performed by `is_a()` is case-sensitive,
            //       unliked the `instanceof` operator.  This means that the class names
            //       for integrations must be constructed carefully.
            //       We can't use `instanceof` since it requires the thing being compared
            //       to be an object and not a string.
            if (is_a($class, 'WPHelpKit_Integration_Theme', true)) {
                $class::get_instance($class);

                break;
            }
        }

        return;
    }

    /**
     * Generate the possible PHP class names for the theme integration.
     *
     * @since 0.6.0
     *
     * @param WP_Theme $theme
     * @return array Array of PHP class names in descending order of
     *               specificity.
     */
    protected function generate_theme_class_names($theme)
    {
        $classes = array();

        $parent_theme = $theme->parent();
        if ($parent_theme) {
            $classes[] = sprintf(
                'WPHelpKit_Integration_Theme_%s_%s_%s',
                $this->sanitize($parent_theme->name, 'name'),
                $this->sanitize($parent_theme->version, 'version'),
                $this->sanitize($theme->name, 'name')
            );
            // note that for child themes, we do not need look for a class
            // with the theme's version number.
            // note also that WP does not support "grandchild" themes so we
            // don't have to walk up the parent "tree"...there will only every
            // be 1 "ancestor" theme.
        }

        $classes[] = sprintf(
            'WPHelpKit_Integration_Theme_%s_%s',
            $this->sanitize($theme->name, 'name'),
            $this->sanitize($theme->version, 'version')
        );
        $classes[] = sprintf(
            'WPHelpKit_Integration_Theme_%s',
            $this->sanitize($theme->name, 'name')
        );

        return $classes;
    }

    /**
     * Sanitize theme names and version strings.
     *
     * @since 0.6.0
     *
     * @param string $str String to sanitize.
     * @param string $type The type of sanitation to perform.  Must be either 'name' or 'version'.
     * @return string
     */
    protected function sanitize($str, $type)
    {
        switch ($type) {
            case 'name':
                // ensure the theme "name" is a legal PHP class name.

                // replace spaces and dashes with underscores.
                $str = str_replace(array( ' ', '-' ), '_', $str);

                // replace non-alphanumerics (+ '_') with empty string.
                $str = preg_replace('/[^A-Za-z0-9_]/', '', $str);

                break;

            case 'version':
                // replace spaces and dashes with underscores.
                $str = str_replace(array( ' ', '-' ), '_', $str);

                // replace '.' and whitespace with the empty string.
                $str = preg_replace('/[.\s]+/', '', $str);

                break;
        }

        return $str;
    }
}
