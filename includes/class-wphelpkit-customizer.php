<?php

defined('ABSPATH') || die;

/**
 * The Customizer functionality.
 *
 * @since 0.0.2
 * @since 0.1.1 Renamed class to WPHelpKit_Customizer.
 */
class WPHelpKit_Customizer
{
    public static $get_archive_page_permalink_action = 'get_archive_page_permalink';

    /**
     * Our static instance.
     *
     * @since 0.0.2
     *
     * @var WPHelpKit_Customizer
     */
    private static $instance;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.2
     *
     * @return WPHelpKit_Customizer
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
     * @since 0.0.2
     *
     * @return WPHelpKit_Customizer
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
     * @since 0.0.2
     *
     * @return void
     */
    protected function add_hooks()
    {
        add_action('customize_register', array( $this, 'customize_register' ), 30);

        add_action('customize_preview_init', array( $this, 'enqueue_preview' ));
        add_action('customize_controls_enqueue_scripts', array( $this, 'enqueue_controls' ));

        add_action('wp_ajax_' . self::$get_archive_page_permalink_action, array( $this, 'get_archive_page_permalink' ));

        return;
    }

    /**
     * Get the permalink for a specific HelpKit page.
     *
     * If `$post_id` is sent, then get the permalink for that page; if not,
     * then get `WPHelpKit_Article`'s post type archive link.
     *
     * @since 0.6.1
     *
     * @return void, echo's its output.
     */
    public function get_archive_page_permalink()
    {
        check_ajax_referer(self::$get_archive_page_permalink_action . '-nonce', 'nonce');

        echo wp_kses_post(get_post_type_archive_link(WPHelpKit_Article::$post_type));

        exit;
    }

    /**
     * Add our panel/sections/settings/controls to the Customizer.
     *
     * @since 0.0.2
     * @since 0.9.1 Added [article_archive]display_categories_description control.
     * @since 0.9.1 Added [article_archive]display_categories_thumbnail control.
     *
     * @param WP_Customize_Manager $wp_customize The WP Customize Manager object.
     * @return void
     *
     * @action customize_register
     */
    public function customize_register($wp_customize)
    {
        $capability = 'manage_options';

        // add our top-level panel
        $panel_id = 'wphelpkit';
        $args = array(
            'title' => esc_html__('HelpKit', 'wphelpkit'),
            'capability' => $capability,
        );
        $wp_customize->add_panel($panel_id, $args);

        // now add our sections/settings/controls

        $default_settings_args = array(
            'type' => 'option',
            'capability' => 'manage_options',
            'default' => true,
            'transport' => 'postMessage',
        );
        $default_control_args = array(
            'dirty' => true,
        );

        // the structure of the following array is as follows:
        //
        // keys are section ids
        // values are:
        //     all but the 'settings' property are arguments for WP_Customize_Manager::add_section()
        //     the structure of the 'settings' property is as follows:
        //         keys are setting ids
        //         values are:
        //             all but the 'control' property are arguments for WP_Customize_Manager::add_setting()
        //             the 'control' propert is arguments for WP_Customize_manager::add_control()
        $sections = array(
            'article_archive' => array(
                'title' => esc_html__('HelpKit Index', 'wphelpkit'),
                'capability' => $capability,
                'panel' => $panel_id,
                'settings' => array(
                    'search' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Search', 'wphelpkit'),
                        ),
                    ),
                    'display_categories_description' => array(
                        'default' => false,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Display Category\'s Description', 'wphelpkit'),
                        ),
                    ),
                    'display_categories_thumbnail' => array(
                        'default' => false,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Display Category\'s Thumbnail', 'wphelpkit'),
                        ),
                    ),
                    'display_subcategories' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Display Subcategories', 'wphelpkit'),
                        ),
                    ),
                    'display_posts' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Display Posts', 'wphelpkit'),
                        ),
                    ),
                    'number_of_posts' => array(
                        'default' => 5,
                        'control' => array(
                            'type' => 'number',
                            'label' => esc_html__('Number of Posts to Display', 'wphelpkit'),
                            'input_attrs' => array(
                                'min' => 1,
                                'max' => 10,
                                'step' => 1,
                            ),
                            // need this (in addition to the JS that does the "same" thing) because
                            // when the Live Preview is updated while this section is open
                            // number_of_posts is always displayed :-(
                            // maybe there is some other JS hook I can use, but for now this works.
                            'active_callback' => array( $this, 'archive_number_of_posts_active' ),
                        ),
                    ),
                    'number_of_columns' => array(
                        'default' => 2,
                        'control' => array(
                            'type' => 'number',
                            'label' => esc_html__('Columns', 'wphelpkit'),
                            'input_attrs' => array(
                                'min' => 1,
                                'max' => 3,
                                'step' => 1,
                            ),
                        ),
                    ),
                ),
            ),
            'category_archive' => array(
                'title' => esc_html__('HelpKit Categories', 'wphelpkit'),
                'capability' => $capability,
                'panel' => $panel_id,
                'settings' => array(
                    'breadcrumbs' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Breadcrumbs', 'wphelpkit'),
                        ),
                    ),
                    'search' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Search', 'wphelpkit'),
                        ),
                    ),
                    'display_subcategories_description' => array(
                        'default' => false,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Subcategories Description', 'wphelpkit'),
                        ),
                    ),
                    'display_articles_excerpt' => array(
                        'default' => false,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Articles Excerpt', 'wphelpkit'),
                        ),
                    ),
                ),
            ),
            'article' => array(
                'title' => esc_html__('HelpKit Article', 'wphelpkit'),
                'capability' => $capability,
                'panel' => $panel_id,
                'settings' => array(
                    'breadcrumbs' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Breadcrumbs', 'wphelpkit'),
                        ),
                    ),
                    'search' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Search', 'wphelpkit'),
                        ),
                    ),
                    'article_voting' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Article Voting', 'wphelpkit'),
                        ),
                    ),
                    'related_articles' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Related Articles', 'wphelpkit'),
                        ),
                    ),
                    'number_of_views' => array(
                        'default' => true,
                        'control' => array(
                            'type' => 'checkbox',
                            'label' => esc_html__('Number of Views', 'wphelpkit'),
                        ),
                    ),
                ),
            ),
        );

        // loop through our sections and add them.
        foreach ($sections as $section_id => $args) {
            $settings = $args['settings'];
            unset($args['settings']);

            $raw_section_id = $section_id;

            $section_id = "wphelpkit_{$raw_section_id}";

            $wp_customize->add_section($section_id, $args);

            // loop through the settings in this section and add them.
            foreach ($settings as $setting_id => $args) {
                $setting_id = "wphelpkit[{$raw_section_id}][{$setting_id}]";

                $control_args = $args['control'];
                unset($args['control']);

                $args = wp_parse_args($args, $default_settings_args);

                switch ($control_args['type']) {
                    case 'checkbox':
                        $args['sanitize_callback'] = array( $this, 'sanitize_toggle' );

                        break;

                    case 'wphelpkit-archive-page':
                    case 'number':
                        $args['sanitize_callback'] = array( $this, 'sanitize_int' );

                        break;
                }
                $wp_customize->add_setting($setting_id, $args);

                // finally, add the control for this setting.
                $control_args = wp_parse_args($control_args, array( 'section' => $section_id, 'settings' => $setting_id ));
                $wp_customize->add_control($setting_id, $control_args);
            }
        }

        return;
    }

    /**
     * Sanitize toggle settings.
     *
     * @since 0.0.2
     *
     * @param bool|mixed $value The value of the setting.
     * @return bool True if `$value` is true, false otherwise
     *              (including when it is not a boolean).
     */
    public function sanitize_toggle($value)
    {
        return true === $value;
    }

    /**
     * Sanitize range-value settings.
     *
     * @since 0.0.2
     *
     * @param int|mixed $value The value of the setting.
     * @return int The `intval()` of `$value` (which will be 0 if `$value`
     *             is not numeric.
     */
    public function sanitize_int($value)
    {
        return intval($value);
    }

    /**
     * Enqueue our preview scripts.
     *
     * @since 0.0.2
     *
     * @return void
     *
     * @action customize_preview_init
     */
    public function enqueue_preview()
    {
        wp_enqueue_script('wphelpkit-customize-preview');
        wp_enqueue_style('wphelpkit-order');
        wp_enqueue_script('wphelpkit-order');

        return;
    }

    /**
     * Enqueue our dynamic controls scripts and styles.
     *
     * @since 0.0.2
     * @since 0.0.3 Dynamically change the Live Preview URL depending on which of our sections is opened.
     * @since 0.9.0 Use homepage URL for preview when categories or articles do not exist.
     *
     * @return void
     *
     * @action customize_controls_enqueue_scripts
     */
    public function enqueue_controls()
    {
        global $wp_customize;

        wp_enqueue_script('wphelpkit-customize-controls');

        // now setup the Customizer to preview specific pages when our
        // sections are opened.

        // choose a random category to preview when the category_archive section is opened.
        $categories = WPHelpKit_Article_Category::get_instance()->get_categories(array( 'orderby' => 'rand', 'number' => 1 ));
        if ($categories) {
            $category_archive = get_term_link($categories[0]);
        } else {
            $category_archive = get_home_url();
        }

        // choose a random article to preview when the category_archive section is opened.
        $articles = WPHelpKit_Article::get_instance()->get_posts(array( 'orderby' => 'rand', 'posts_per_page' => 1 ));
        if ($articles) {
            $article = get_the_permalink($articles[0]);
        } else {
            $article = get_home_url();
        }

        // get the URL for the archive.
        $article_archive = WPHelpKit_Archive_Info::get_instance()->get_info('url');

        // now, generate regexes from the category/article links
        $category_archive_regex = $this->regexify_permalink($category_archive);
        $article_regex = $this->regexify_permalink($article);

        // "localize" our customize-controls script with the relevant URLs.
        $data = array(
            'article_archive'        => $article_archive,
            'category_archive'       => $category_archive,
            'category_archive_regex' => $category_archive_regex,
            'article'                => $article,
            'article_regex'          => $article_regex,
            'nonce'                  => wp_create_nonce(self::$get_archive_page_permalink_action . '-nonce'),
            'action'                 => self::$get_archive_page_permalink_action,
        );
        wp_localize_script('wphelpkit-customize-controls', 'wphelpkit_customizer', $data);

        return;
    }

    /**
     * Regexify a URL for a category archive/single article.
     *
     * The resulting regex is used by the "page switching" algorithm to
     * know whether the currently previewed URL is is of the right "kind".
     *
     * Regexification is different depending on whether pretty permalinks
     * are being used or not.
     *
     * @since 0.1.4
     *
     * @param string $url The URL to regexify.
     * @return string
     */
    public function regexify_permalink($url)
    {
        $permastruct = get_option('permalink_structure');
        if (empty($permastruct)) {
            // plain permalinks.
            $regex = preg_replace('/=(.+)/', '=(.+)', $url);
            $regex = str_replace('?', '\?', $regex);
        } else {
            // pretty permalinks.
            $regex = preg_replace('/^(.*)\/(.+)\//', '$1/(.+)/?', $url);
        }

        return "^{$regex}";
    }

    /**
     * Should the number_of_posts control be active (i.e., displayed)?
     *
     * @since 0.6.1
     *
     * @param WP_Customize_Control $control Unused.
     * @return bool True if `wphelpkit[article_archive][display_posts]` is true, false otherwise.
     */
    public function archive_number_of_posts_active($control)
    {
        global $wp_customize;

        $page_control = $wp_customize->get_control('wphelpkit[article_archive][display_posts]');
        $page_control_value = $page_control->value();

        return $page_control_value;
    }
}
