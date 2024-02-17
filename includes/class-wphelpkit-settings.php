<?php

defined('ABSPATH') || die;

/**
 * All things settings.
 *
 * @since 0.0.1
 * @since 0.0.2 Added support for storage/lookup of Customizer settings.
 * @since 0.0.3 Added support for default_category setting.
 * @since 0.0.5 Added support geting/setting Customizer section options via
 *              `$name`'s like `[section]option`.
 * @since 0.1.1 Renamed class to WPHelpKit_Settings.
 */
class WPHelpKit_Settings
{
    /**
     * Our static instance.
     *
     * @since 0.0.1
     *
     * @var WPHelpKit_Settings
     */
    private static $instance;

    /**
     * Our option name.
     *
     * @since 0.0.1
     *
     * @var string
     */
    public static $option_name = 'wphelpkit';

    /**
     * The HTML @id for our settings section.
     *
     * @since 0.0.2
     *
     * @var string
     */
    public static $section_id = 'wphelpkit';

    /**
     * Our option values.
     *
     * @since 0.0.1
     *
     * @var array Keys are option names, values are option values.
     */
    protected $options = array();

    /**
     * Our default option values.
     *
     * @since 0.0.1
     *
     * @var array
     */
    protected $default_option_values;

    /**
     * Regex to parse "contextualized" option names.
     *
     * @since 0.0.5
     *
     * @var string
     */
    public static $option_name_regex = '/^\[([^]]+)\](.+)$/';

    /**
     * The number of slug fields we show on Settings > Permalinks.
     *
     * @since 0.6.2
     *
     * @var integer
     */
    protected $num_slugs = 0;

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.1
     *
     * @return WPHelpKit_Settings
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
     * @since 0.0.3 Added support for default_category setting.
     * @since 0.9.1 Added support for index_page setting.
     * @since 0.9.1 Added support for [article_archive]display_categories_description setting.
     * @since 0.9.1 Added support for [article_archive]display_categories_thumbnail setting.
     * @since 0.9.2 Added support for search_in_category setting.
     * @since 0.9.2 Added support for category_index_tree setting.
     *
     * @return WPHelpKit_Settings
     */
    public function __construct()
    {
        if (self::$instance) {
            return self::$instance;
        }
        self::$instance = $this;

        $this->default_option_values = array(
            // the following are set on the `Settings > Permalinks` screen
            'post_type_slug'                    => WPHelpKit_Article::$post_type,
            'category_base'                     => WPHelpKit_Article_Category::$category,
            'tag_base'                          => WPHelpKit_Article_Tag::$tag,
            'article_permalink_structure'       => '',
            // the following are set on the `HelpKit > Settings` screen
            'index_page'                        => 0,
            'search_in_category'                => 'yes',
            'category_index_tree'               => 'yes',
            'category_redirect_to_article'      => 'no',
            // the following is set with the 'Make Default' row action
            'default_category'                  => '',
            // the following are set in the customizer
            'article_archive'                   => array(
                'title' => esc_html__('HelpKit', 'wphelpkit'),
                'display_posts' => true,
                'number_of_posts' => 5,
                'display_subcategories' => true,
                'number_of_columns' => 2,
                'search' => true,
                'display_categories_description' => false,
                'display_categories_thumbnail' => false,
            ),
            'category_archive'                  => array(
                'breadcrumbs' => true,
                'search' => true,
                'display_subcategories_description' => false,
                'display_articles_excerpt' => false,
            ),
            'article'                           => array(
                'breadcrumbs' => true,
                'search' => true,
                'article_voting' => true,
                'related_articles' => true,
                'number_of_views' => true,
                'article_comments' => true,
            ),
        );

        $this->options = get_option(self::$option_name);

        if (empty($this->options)) {
            add_option(self::$option_name, $this->default_option_values);
            $this->options = get_option(self::$option_name);
        } else {
            // add a sanity check comparing keys in $this->options to those in
            // $this->default_option_values and reconsile. this is in case we
            // change the "structure" of our options between versions.
            $options = wp_parse_args($this->options, $this->default_option_values);
            update_option(self::$option_name, $options);
        }

        $this->add_hooks();
        $this->add_admin_hooks();
    }

    /**
     * Add hooks.
     *
     * @since 0.6.2
     *
     * @return void
     */
    public function add_hooks()
    {
        // prepare to possibly flush rewrite rules when our "HelpKit Index" page changes.
        foreach (array( self::$option_name ) as $option) {
            add_action("update_option_{$option}", array( $this, 'maybe_flush_rewrite_rules_on_update_option' ), 10, 3);
        }

        // prepare to possibly flush rewrite rules when our "HelpKit Index" page is edited.
        add_action('save_post_page', array( $this, 'maybe_flush_rewrite_rules_on_save_post' ), 10, 3);


        return;
    }

    /**
     * Add admin hooks.
     *
     * @since 0.0.1
     *
     * @return void
     */
    protected function add_admin_hooks()
    {
        add_action('admin_menu', array( $this, 'add_settings_page' ));
        add_action('admin_init', array( $this, 'add_helpkit_settings' ));
        add_action('admin_init', array( $this, 'add_permalink_settings' ));

        add_filter('plugin_action_links_wp-helpkit/plugin.php', array( $this, 'add_settings_link' ));

        add_action('admin_init', array( $this, 'save_settings' ));

        add_action('wp_loaded', array( $this, 'maybe_reload_settings' ), 20);

        return;
    }

    /**
     * Get an option value.
     *
     * @since 0.0.1
     * @since 0.0.2 Added support for "contextualizing" option looked
     *              for our various Customizer sections.
     * @since 0.0.5 Added support get Customizer section options via
     *              `$name`'s like `[section]option`.
     *
     * @param string $name The option name.
     * @param string $default The option default value.
     * @return mixed The option value
     */
    public function get_option($name, $default = null)
    {
        global $post;

        $value = $default;

        if (isset($this->options[ $name ])) {
            $value = $this->options[ $name ];
        } elseif (isset($this->default_option_values[ $name ])) {
            $value = $this->default_option_values[ $name ];
        } elseif (! empty($default)) {
            $value = $default;
        } elseif ('search' === $name &&
                ( $post &&
                    (
                        has_block(WPHelpKit_Search::$block) ||
                        has_shortcode($post->post_content, WPHelpKit_Search::$shortcode)
                    )
                ) ) {
            $value = true;
        } else {
            // option `$name` not found.  Try to find it by customizer section.
            $customize_section = '';

            $matches = array();
            if (preg_match(self::$option_name_regex, $name, $matches)) {
                $customize_section = $matches[1];
                $name = $matches[2];
            } elseif (WPHelpKit_Article::get_instance()->is_post_type_archive()) {
                $customize_section = 'article_archive';
            } elseif (is_tax(WPHelpKit_Article_Category::$category)) {
                $customize_section = 'category_archive';
            } elseif (is_singular(WPHelpKit_Article::$post_type)) {
                $customize_section = 'article';
            }

            if (! empty($customize_section)) {
                if (isset($this->options[ $customize_section ][ $name ])) {
                    $value = $this->options[ $customize_section ][ $name ];
                } elseif (isset($this->default_option_values[ $customize_section ][ $name ])) {
                    $value = $this->default_option_values[ $customize_section ][ $name ];
                }
            }
        }

        return $value;
    }

    /**
     * Reload settings when in the Customizer.
     *
     * This ensures any changes in the current Customizer changeset are correctly
     * reflected in our options.
     *
     * @since 0.6.1
     *
     * @return void
     *
     * @action wp_loaded
     */
    public function maybe_reload_settings()
    {
        if (! is_customize_preview()) {
            return;
        }

        $this->options = get_option(self::$option_name);

        if (empty($this->options)) {
            add_option(self::$option_name, $this->default_option_values);
            $this->options = get_option(self::$option_name);
        }

        return;
    }

    /**
     * Set an option value.
     *
     * @since 0.0.3
     * @since 0.0.5 Added support for seting Customizer section options via
     *              `$name`'s like `[section]option`.
     *
     * @param string $name The option name.
     * @param mixed $value THe option value.
     */
    public function set_option($name, $value)
    {
        $customize_section = '';
        $matches = array();
        if (preg_match(self::$option_name_regex, $name, $matches)) {
            $customize_section = $matches[1];
            $name = $matches[2];
        }

        if (! empty($customize_section)) {
            $this->options[ $customize_section ][ $name ] = $value;
        } else {
            $this->options[ $name ] = $value;
        }

        update_option(self::$option_name, $this->options);

        return;
    }

    /**
     * Add our admin Settings page.
     *
     * @since 0.9.1
     *
     * @return void
     *
     * @action admin_menu
     */
    public function add_settings_page()
    {
        add_submenu_page(
            'edit.php?post_type=helpkit',
            __( 'Settings', 'wphelpkit' ),
            __( 'Settings', 'wphelpkit' ),
            'manage_options',
            'wphelpkit-settings',
            array( $this, 'settings_page' ),
            4
        );

        return;
    }

    /**
     * Settings page output.
     *
     * @since 0.9.1
     *
     * @return void
     */
    public function settings_page() {
    ?>
        <div class="wrap fs-section fs-full-size-wrapper">
            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url(get_admin_url()) . 'edit.php?post_type=helpkit&page=wphelpkit-settings' ?>" class="nav-tab fs-tab nav-tab-active home">
                    <?php esc_html_e( 'Settings', 'wphelpkit' ); ?>
                </a>
            </h2>
            <div class="fs-tab-options-wrapper">
                <form method="post" action="options.php">
                    <?php wp_nonce_field('wphelpkit_settings_action', 'wphelpkit_settings_nonce'); ?>
                    <?php settings_fields('wphelpkit-settings-group'); ?>
                    <?php do_settings_sections( 'wphelpkit-settings' ); ?>
                    <?php submit_button(); ?>
                </form>
            </div>
        </div>
    <?php
    }

    /**
     * Add our settings to the `HelpKit > Settings` screen.
     *
     * @since 0.9.1
     *
     * @return void
     *
     * @action admin_init
     */
    public function add_helpkit_settings()
    {
        //register settings
        register_setting(
            'wphelpkit-settings-group',
            'index_page',
            array(
                'sanitize_callback' => array( $this, 'sanitize_option' ),
                'show_in_rest'      => true,
            )
        );

        register_setting(
            'wphelpkit-settings-group',
            'search_in_category',
            array(
                'sanitize_callback' => array( $this, 'sanitize_checkbox_option' ),
                'show_in_rest'      => true,
            )
        );

        register_setting(
            'wphelpkit-settings-group',
            'category_index_tree',
            array(
                'sanitize_callback' => array( $this, 'sanitize_checkbox_option' ),
                'show_in_rest'      => true,
            )
        );

        register_setting(
            'wphelpkit-settings-group',
            'category_redirect_to_article',
            array(
                'sanitize_callback' => array( $this, 'sanitize_checkbox_option' ),
                'show_in_rest'      => true,
            )
        );

        //register section
        add_settings_section(
            'wphelpkit-settings-section',
            '',
            array( $this, 'output_settings_section' ),
            'wphelpkit-settings'
        );

        //register option fields
        add_settings_field(
            'index_page',
            __( 'HelpKit Index Page', 'wphelpkit' ),
            array( $this, 'output_index_page_select_settings_field' ),
            'wphelpkit-settings',
            'wphelpkit-settings-section',
            array( 'option' => 'index_page' )
        );

        add_settings_field(
            'search_in_category',
            __( 'Category Search', 'wphelpkit' ),
            array( $this, 'output_checkbox_settings_field' ),
            'wphelpkit-settings',
            'wphelpkit-settings-section',
            array( 'option' => 'search_in_category', 'label' => 'Search current category only' )
        );

        add_settings_field(
            'category_redirect_to_article',
            __( 'Category Redirect', 'wphelpkit' ),
            array( $this, 'output_checkbox_settings_field' ),
            'wphelpkit-settings',
            'wphelpkit-settings-section',
            array( 'option' => 'category_redirect_to_article', 'label' => 'Redirect category to the first article' )
        );

        add_settings_field(
            'category_index_tree',
            __( 'Index Tree', 'wphelpkit' ),
            array( $this, 'output_checkbox_settings_field' ),
            'wphelpkit-settings',
            'wphelpkit-settings-section',
            array( 'option' => 'category_index_tree', 'label' => 'Only show children of the current category' )
        );

        return;
    }

    /**
     * Add our settings to the `Settings > Permalink` screen.
     *
     * @since 0.0.1
     * @since 0.9.0 Removed post_type_slug since the page slug will be used and
     * can be edited by the user.
     * @since 0.9.3 Added HelpKit Permalinks section
     *
     * @return void
     *
     * @action admin_init
     */
    public function add_permalink_settings()
    {
        register_setting(
            'permalink',
            self::$option_name,
            array(
                'sanitize_callback' => array( $this, 'sanitize_option' ),
                'show_in_rest'      => true,
            )
        );

        add_settings_section(
            'wphelpkit-slugs',
            esc_html__('HelpKit Slugs', 'wphelpkit'),
            array( $this, 'output_settings_section' ),
            'permalink'
        );

        add_settings_section(
            'wphelpkit-permalinks',
            esc_html__('HelpKit Permalinks', 'wphelpkit'),
            array( $this, 'output_helpkit_permalinks_radio_settings_field' ),
            'permalink'
        );

        $fields = array(
            'category_base' => esc_html__('Category Slug', 'wphelpkit'),
            'tag_base' => esc_html__('Tag Slug', 'wphelpkit'),
        );

        $this->num_slugs = count($fields);
        foreach ($fields as $option => $label) {
            add_settings_field(
                'wphelpkit-' . $option,
                $label,
                array( $this, 'output_text_settings_field' ),
                'permalink',
                'wphelpkit-slugs',
                array( 'option' => $option )
            );
        }

        add_settings_field(
            'wphelpkit_permalink_custom_settings',
            '',
            array( $this, 'output_wphelpkit_permalink_custom_settings' ),
            'permalink',
            'wphelpkit-slugs'
        );

        return;
    }

    /**
     * Output our settings seciton.
     *
     * Callback passed to `add_settings_section()`.
     *
     * @since 0.0.1
     *
     * @return void
     */
    public function output_settings_section()
    {
        // the a tag is so that we can link to this section from the Settings
        // action link in the plugins list table.
        echo sprintf("<a name='%s'></a>", esc_attr(self::$section_id));

        return;
    }

    /**
     * Output a "text" setting field.
     *
     * Callback passed to `add_settings_field()`.
     *
     * @since 0.0.1
     *
     * @param array $args {
     *     @type string $option The "option" whose field we are to output.
     * }
     * @return void
     */
    public function output_text_settings_field($args)
    {
        echo sprintf(
            "<input type='text' name='%s[%s]' id='%s[%s]' value='%s' class='regular-text code' />",
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            esc_attr($this->get_option($args['option']))
        );

        return;
    }

    public function output_wphelpkit_permalink_custom_settings()
    {
        wp_nonce_field('wphelpkit_permalink_custom_settings_action', 'wphelpkit_permalink_custom_settings_nonce');
        return;
    }

    /**
     * Output the helpkit permalinks "radio" setting field.
     *
     * Callback passed to `add_settings_field()`.
     *
     * @since 0.9.3
     *
     * @return void
     */
    public function output_helpkit_permalinks_radio_settings_field()
    {
        $base_slug = urldecode( ( self::get_option('index_page') > 0 && get_post( self::get_option('index_page') ) ) ? get_page_uri( self::get_option('index_page') ) : _x( 'helpkit', 'default-slug', 'wphelpkit' ) );
        $structures = array(
			0 => '',
			1 => '/' . rtrim(trailingslashit( $base_slug ), '/'),
			2 => '/' . rtrim(trailingslashit( $base_slug ) . trailingslashit( '%' . WPHelpKit_Article_Category::$category . '%' ), '/'),
		);
    ?>
        <p><?php echo esc_html__('This setting affects help articles URLs only, not things such as article categories.', 'wphelpkit'); ?></p>

        <table class="form-table helpkit-permalink-structure">
			<tbody>
				<tr>
					<th><label><input name="<?php echo esc_attr(self::$option_name).'[article_permalink_structure]'; ?>" type="radio" value="<?php echo esc_attr( $structures[0] ); ?>" <?php checked( $structures[0], self::get_option('article_permalink_structure') ); ?> /> <?php esc_html_e( 'Default', 'wphelpkit' ); ?></label></th>
					<td><code><?php echo esc_html( home_url() ) . '/?' . esc_html(WPHelpKit_Article::$post_type) . '=sample-article'; ?></code> / <code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html(WPHelpKit_Article::$post_type) . '/sample-article/' ?></code></td>
				</tr>
				<?php if ( self::get_option('index_page') ) : ?>
					<tr>
						<th><label><input name="<?php echo esc_attr(self::$option_name).'[article_permalink_structure]'; ?>" type="radio" value="<?php echo esc_attr( $structures[1] ); ?>" <?php checked( $structures[1], self::get_option('article_permalink_structure') ); ?> /> <?php esc_html_e( 'Archive base', 'wphelpkit' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ); ?>/sample-article/</code></td>
					</tr>
					<tr>
						<th><label><input name="<?php echo esc_attr(self::$option_name).'[article_permalink_structure]'; ?>" type="radio" value="<?php echo esc_attr( $structures[2] ); ?>" <?php checked( $structures[2], self::get_option('article_permalink_structure') ); ?> /> <?php esc_html_e( 'Archive base with category', 'wphelpkit' ); ?></label></th>
						<td><code><?php echo esc_html( home_url() ); ?>/<?php echo esc_html( $base_slug ) . '/' . esc_html(self::get_option('category_base')); ?>/sample-article/</code></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>

    <?php
        return;
    }

    /**
     * Output a "checkbox" setting field.
     *
     * Callback passed to `add_settings_field()`.
     *
     * @since 0.9.1
     *
     * @param bool $default default value of checkbox
     * @return void
     */
    public function output_checkbox_settings_field($args)
    {
        echo sprintf(
            '<input type="checkbox" id="%s" name="%s[%s]" value="1" %s /> <label for="%s[%s]">%s</label>',
            esc_attr($args['option']),
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            checked( 1, ('yes' === $this->get_option($args['option'])), false ),
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            esc_html( $args['label'], 'wphelpkit' )
        );

    }

    /**
     * Output a "select" setting field.
     *
     * Callback passed to `add_settings_field()`.
     *
     * @since 0.9.1
     *
     * @param array $args {
     *     @type string $option The "option" whose field we are to output.
     * }
     * @return void
     */
    public function output_index_page_select_settings_field($args)
    {
        $page_walker = new WPHelpKit_Customizer_Page_Walker();

        $waker_args = array(
            'post_type' => 'page',
            'posts_per_page' => -1,
            'orderby' => 'menu_order title date',
            'order' => 'ASC',
        );
        $pages = get_posts($waker_args);

        // because we're dealing with an associative array retruned by the page walker,
        // we have to use the associative array "concatenation" operator (+) rather
        // than the usual `array_merge()` which only works with numerically indexed arrays.
        $choices = array( 0 => esc_html__('&mdash; Select &mdash;', 'wphelpkit') ) +
            $page_walker->walk($pages, 0);

        $select_options = '';
        foreach ($choices as $value => $label) {
            $select_options .= '<option value="' . esc_attr($value) . '"' . selected($this->get_option($args['option']), $value, false) . '>' . esc_html($label) . '</option>';
        }

        echo sprintf(
            "<select id='%s[%s]' name='%s[%s]'>%s</select>",
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            esc_attr(self::$option_name),
            esc_attr($args['option']),
            $select_options // Already safely escaped component-wise
        );

        return;
    }

    /**
     * Save our settings.
     *
     * @since 0.0.1
     *
     * @return void
     * 
     */
    public function save_settings()
    {
        if ( ! current_user_can('manage_options') ) {
            return;
        }

        if
        ( 
            ! ( isset($_POST['wphelpkit_settings_nonce']) && wp_verify_nonce($_POST['wphelpkit_settings_nonce'], 'wphelpkit_settings_action') ) &&
            ! ( isset($_POST['wphelpkit_permalink_custom_settings_nonce']) && wp_verify_nonce($_POST['wphelpkit_permalink_custom_settings_nonce'], 'wphelpkit_permalink_custom_settings_action') )
        )
        {
            return;
        }
            
        if( isset($_POST[ self::$option_name ]) && is_array($_POST[ self::$option_name ]) ) {

            $data = array_map( 'sanitize_text_field', $_POST[ self::$option_name ] );

            if ( isset($_POST['wphelpkit_settings_nonce']) && wp_verify_nonce($_POST['wphelpkit_settings_nonce'], 'wphelpkit_settings_action') ) {
                $data['search_in_category'] = $this->sanitize_checkbox_option($_POST[ self::$option_name ]['search_in_category'] || false);
                $data['category_index_tree'] = $this->sanitize_checkbox_option($_POST[ self::$option_name ]['category_index_tree'] || false);
                $data['category_redirect_to_article'] = $this->sanitize_checkbox_option($_POST[ self::$option_name ]['category_redirect_to_article'] || false);
            }

            if ( isset($_POST['wphelpkit_permalink_custom_settings_nonce']) && wp_verify_nonce($_POST['wphelpkit_permalink_custom_settings_nonce'], 'wphelpkit_permalink_custom_settings_action') ) {
                $product_base = isset( $data['article_permalink_structure'] ) ? $this->clean_permalink( wp_unslash( $data['article_permalink_structure'] ) ) : '';
                $data['article_permalink_structure'] = $this->sanitize_permalink_option( $product_base );

                $counts = array_count_values($data);
                if ((count($counts)-1) !== $this->num_slugs) {
                    // same slug used more than once, not good
                    add_settings_error(
                        'error',
                        'wphelpkit_slugs',
                        esc_html__('The WPHelpKit slugs must all be unique.', 'wphelpkit'),
                        'error'
                    );

                    return;
                }
            }

            // update our option.
            $this->options = wp_parse_args($data, $this->options);
            update_option(self::$option_name, $this->options);

        }

        return;
    }

    /**
     * Sanitize our option.
     *
     * @since 0.0.1
     * @since 0.6.1 Changed sanitation of slugs to match what Core does for `{category|tag}_base`.
     *
     * @param array $value Our option value.
     * @return array Sanitized option value.
     */
    public function sanitize_option($value)
    {
        global $wpdb;

        if (! is_array($value)) {
            $value = $this->default_option_values;
        }

        foreach ($this->default_option_values as $key => $val) {
            if (empty($value[ $key ])) {
                $value[ $key ] = $val;
            } elseif (in_array($key, array( 'category_base', 'tag_base', 'index_page' ))) {
                $value[ $key ] = $wpdb->strip_invalid_text_for_column($wpdb->options, 'option_value', $value[ $key ]);
                if (is_wp_error($value[ $key ])) {
                    $error = $value[ $key ]->get_error_message();
                    add_settings_error(self::$option_name, 'invalid_' . self::$option_name, $error);
                } else {
                    $value[ $key ] = esc_url_raw($value[ $key ]);
                    $value[ $key ] = str_replace('http://', '', $value[ $key ]);
                }
            }
        }

        return $value;
    }

    /**
     * Sanitize our checkbox option.
     *
     * @since 0.9.2
     *
     * @param bool $value checkbox value.
     *
     * @return string string that represents input's value.
     *
     * @action plugin_action_links_wphelpkit/plugin.php
     */
    function sanitize_checkbox_option( $value ) {
    	$value = is_bool( $value ) ? $value : ( 'yes' === $value || 1 === $value || 'true' === $value || '1' === $value );

    	return true === $value ? 'yes' : 'no';
    }

    /**
     * Sanitize our permalinks.
     *
     * @since  0.9.3
     *
     * @param  string $value Permalink.
     *
     * @return string
     */
    function sanitize_permalink_option( $value ) {
    	global $wpdb;

    	$value = $wpdb->strip_invalid_text_for_column( $wpdb->options, 'option_value', $value );

    	if ( is_wp_error( $value ) ) {
    		$value = '';
    	}

    	$value = esc_url_raw( trim( $value ) );
    	$value = str_replace( 'http://', '', $value );

    	return untrailingslashit( $value );
    }

    /**
     * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
     * Non-scalar values are ignored.
     *
     * @since  0.9.3
     * @param string|array $var Data to sanitize.
     *
     * @return string|array
     */
    function clean_permalink( $var ) {
    	if ( is_array( $var ) ) {
    		return array_map( array( $this, 'clean_permalink' ), $var );
    	} else {
    		return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
    	}
    }

    /**
     * Add a 'Settings' action link in the Plugins list table.
     *
     * @since 0.0.1
     *
     * @param array $actions An array of plugin action links. By default this can include
     *              'activate', 'deactivate', and 'delete'. With Multisite active this
     *              can also include 'network_active' and 'network_only' items.
     * @param string $plugin_file Path to the plugin file relative to the plugins directory.
     * @param array $plugin_data An array of plugin data. @link get_plugin_data().
     * @param string $context The plugin context. By default this can include 'all',
     *               'active', 'inactive', 'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
     * @return array `$actions` with our settings link added.
     *
     * @action plugin_action_links_wphelpkit/plugin.php
     */
    public function add_settings_link($actions)
    {
        if (current_user_can('manage_options')) {
            $settings_link = sprintf(
                '<a href="%s">%s</a>',
                'options-permalink.php#' . self::$section_id,
                esc_html__('Settings')
            );

            array_unshift($actions, $settings_link);
        }

        return $actions;
    }

    /**
     * Maybe flush rewrite rules when certain options are updated.
     *
     * @since 0.6.2
     *
     * @param string $old_value
     * @param string $value
     * @param string $option
     */
    public function maybe_flush_rewrite_rules_on_update_option($old_value, $value, $option)
    {
        switch ($option) {
            case self::$option_name:
                if ( ($old_value['index_page'] === $value['index_page']) &&
                ($old_value['article_permalink_structure'] === $value['article_permalink_structure']) ) {
                    // nothing to do, so bail.
                    return;
                }

                break;
            default:
                // nothing to do, so bail.
                return;
        }

        // setup to flush rewrite rules on the next request.
        set_transient(WPHelpKit_Article::$flush_rewrite_rules_transient, true);

        return;
    }

    /**
     * Maybe flush rewrite rules when our "HelpKit Index" page is edited.
     *
     * @since 0.6.2
     *
     * @param int $post_id Post ID.
     * @param WP_Post $post Post object.
     * @param bool $update Whether this is an existing post being updated or not.
     *
     * @action save_post_page
     */
    public function maybe_flush_rewrite_rules_on_save_post($post_id, $post, $update)
    {
        $archive_info = WPHelpKit_Archive_Info::get_instance();
        if (! ( in_array($archive_info->type, array( 'settings_index_page' )) &&
                $archive_info->id === $post_id ) ) {
            // the post is not our HelpKit Index page, nothing to do, so bail.
            return;
        }

        $page_status = get_post_status($post);
        if ('publish' === $page_status  && get_permalink($post) === $archive_info->url) {
            // the page is still published and it's permalink hasn't changed,
            // nothing to do, so bail.
            return;
        }

        if ('publish' !== $page_status) {
            switch ($archive_info->type) {
                case 'settings_index_page':
                    // while the article permalink will still work, the "Home" breadcrumb URL
                    // will be the "plain" permalink which isn't ideal.  So, unset the
                    // Customizer page and continue.
                    WPHelpKit_Settings::get_instance()->set_option('index_page', 0);

                    break;
            }
        }

        // setup to flush rewrite rules on the next request.
        set_transient(WPHelpKit_Article::$flush_rewrite_rules_transient, true);

        return;
    }

}
