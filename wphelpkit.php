<?php

/*
 * Plugin Name: WPHelpKit
 * Description: Documentation and Knowledge Base — Organize, publish, and manage help articles for your SaaS or software product.
 * Version: 1.4
 * Author: WPHelpKit
 * Plugin URI: https://wphelpkit.com
 * Release Asset: true
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wphelpkit
 *
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !function_exists( 'is_plugin_active' ) ) {
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
}
//============================
// Freemius initialization
//============================

if ( function_exists( 'wphelpkit_fs_init' ) ) {
    wphelpkit_fs_init()->set_basename( false, __FILE__ );
} else {
    // DO NOT REMOVE THIS IF, IT IS ESSENTIAL FOR THE `function_exists` CALL ABOVE TO PROPERLY WORK.
    
    if ( !function_exists( 'wphelpkit_fs_init' ) ) {
        // Create a helper function for easy SDK access.
        function wphelpkit_fs_init()
        {
            global  $wphelpkit_fs_init ;
            
            if ( !isset( $wphelpkit_fs_init ) ) {
                // Include Freemius SDK.
                require_once dirname( __FILE__ ) . '/freemius/start.php';
                $wphelpkit_fs_init = fs_dynamic_init( array(
                    'id'             => '4904',
                    'slug'           => 'wphelpkit',
                    'premium_slug'   => 'wphelpkit-pro',
                    'type'           => 'plugin',
                    'public_key'     => 'pk_b16f1368eb9b019f0ea863584229f',
                    'is_premium'     => false,
                    'has_addons'     => false,
                    'has_paid_plans' => true,
                    'menu'           => array(
                    'slug'        => 'wphelpkit-settings',
                    'first-path'  => 'edit.php?post_type=helpkit',
                    'contact'     => true,
                    'support'     => false,
                    'affiliation' => false,
                    'parent'      => array(
                    'slug' => 'edit.php?post_type=helpkit',
                ),
                ),
                    'navigation'     => 'tabs',
                    'is_live'        => true,
                ) );
            }
            
            return $wphelpkit_fs_init;
        }
        
        // Init Freemius.
        wphelpkit_fs_init();
        // Disable some Freemius features.
        wphelpkit_fs_init()->add_filter( 'hide_freemius_powered_by', '__return_true' );
        // Signal that SDK was initiated.
        do_action( 'wphelpkit_fs_init_loaded' );
    }
    
    //============================
    // WPHelpKit
    //============================
    
    if ( !class_exists( 'WPHelpKit' ) ) {
        require dirname( __FILE__ ) . '/vendor/autoload.php';
        /**
         * The main plugin class.
         *
         * @since 0.0.1
         * @since 0.0.3 Removed `$settings` propery and `get_option()` method.  Use WPHelpKit_Settings::get_instance()->get_option() instead.
         * @since 0.1.1 Renamed class to WPHelpKit_Plugin.
         * @since 0.9.0 Renamed class to WPHelpKit.
         */
        class WPHelpKit
        {
            /**
             * Our static instance.
             *
             * @since 0.0.1
             *
             * @var WPHelpKit
             */
            private static  $instance ;
            /**
             * Our version number.
             *
             * @since 0.0.1
             *
             * @var string
             */
            const  VERSION = '1.4' ;
            /**
             * Transient name to set when we are activated.
             *
             * @since 0.6.2
             *
             * @var string
             */
            public static  $activated_transient = 'wphelpkit-activated' ;
            /**
             * Get our instance.
             *
             * Calling this static method is preferable to calling the class
             * constrcutor directly.
             *
             * @since 0.0.1
             *
             * @return WPHelpKit
             */
            public static function get_instance()
            {
                if ( !self::$instance ) {
                    self::$instance = new self();
                }
                return self::$instance;
            }
            
            /**
             * Constructor.
             *
             * Initialize our static instance and add hooks.
             *
             * @since 0.0.1
             */
            public function __construct()
            {
                if ( self::$instance ) {
                    return self::$instance;
                }
                self::$instance = $this;
                $this->add_hooks();
                if ( is_admin() ) {
                    $this->add_admin_hooks();
                }
            }
            
            /**
             * Add hooks.
             *
             * @since 0.0.1
             *
             * @return void
             */
            protected function add_hooks()
            {
                global  $wp_filter ;
                register_activation_hook( __FILE__, array( $this, 'activate' ) );
                add_action( 'plugins_loaded', array( $this, 'setup' ) );
                add_action( 'init', array( $this, 'register_scripts_styles' ) );
                add_action( 'init', array( $this, 'maybe_create_helpkit_page' ), PHP_INT_MAX );
                add_filter(
                    'block_categories_all',
                    array( $this, 'block_categories' ),
                    10,
                    2
                );
                add_action( 'enqueue_block_editor_assets', array( $this, 'blacklist_blocks' ) );
                add_action( 'import_start', array( 'WPHelpKit_Importer', 'get_instance' ) );
                return;
            }
            
            /**
             * Add admin hooks.
             *
             * @since 0.1.0
             *
             * @return void
             */
            protected function add_admin_hooks()
            {
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
                return;
            }
            
            /**
             * Perform basic setup operations.
             *
             * @since 0.0.1
             *
             * @return void
             *
             * @action plugins_loaded
             */
            public function setup()
            {
                WPHelpKit_Session_Handler::get_instance();
                // need to instantiate our Settings before the Article CPT
                // since the Article CPT uses the settings when registering
                // the post_type/taxomomies
                WPHelpKit_Settings::get_instance();
                WPHelpKit_Integration::get_instance();
                WPHelpKit_Article::get_instance();
                WPHelpKit_Article_Category::get_instance();
                WPHelpKit_Article_Tag::get_instance();
                WPHelpKit_Search::get_instance();
                WPHelpKit_Customizer::get_instance();
                WPHelpKit_Index_Tree::get_instance();
                return;
            }
            
            /**
             * Register our scripts and styles.
             *
             * @since 0.0.1
             *
             * @return void
             *
             * @action init
             */
            public function register_scripts_styles()
            {
                $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min' );
                $rtl = ( is_rtl() ? '-rtl' : '' );
                $styles = array(
                    'wphelpkit-wphelpkiticons' => array(),
                    'wphelpkit-styles'         => array(
                    'dependencies' => array( 'wphelpkit-wphelpkiticons' ),
                ),
                    'wphelpkit-admin'          => array(
                    'is_admin'     => true,
                    'dependencies' => array( 'wphelpkit-wphelpkiticons' ),
                ),
                    'wphelpkit-order'          => array(
                    'is_admin' => true,
                ),
                );
                foreach ( $styles as $handle => $data ) {
                    // contruct $src & $dependencies if they are not already set.
                    $admin = ( isset( $data['is_admin'] ) && $data['is_admin'] ? 'admin/' : '' );
                    $src = ( !isset( $data['src'] ) ? 'assets/css/' . $admin . str_replace( 'wphelpkit-', '', $handle ) : $data['src'] );
                    if ( !isset( $data['src'] ) ) {
                    }
                    $dependencies = ( !isset( $data['dependencies'] ) ? array() : $data['dependencies'] );
                    // register the style.
                    wp_register_style(
                        $handle,
                        plugins_url( "{$src}{$rtl}{$suffix}.css", __FILE__ ),
                        $dependencies,
                        self::VERSION
                    );
                }
                $scripts = array(
                    'wphelpkit-customize-preview'   => array(
                    'is_admin'     => true,
                    'dependencies' => array( 'jquery' ),
                ),
                    'wphelpkit-customize-controls'  => array(
                    'is_admin'     => true,
                    'dependencies' => array( 'jquery', 'customize-controls' ),
                ),
                    'wphelpkit-order'               => array(
                    'is_admin'     => true,
                    'dependencies' => array( 'jquery-ui-sortable' ),
                    'localize'     => array(
                    'l10n' => array(
                    'ajaxurl'         => admin_url( 'admin-ajax.php' ),
                    'category_action' => WPHelpKit_Article_Category::$category_order_action,
                    'category_nonce'  => wp_create_nonce( WPHelpKit_Article_Category::$category_order_action . '-nonce' ),
                    'article_action'  => WPHelpKit_Article::$article_order_action,
                    'article_nonce'   => wp_create_nonce( WPHelpKit_Article::$article_order_action . '-nonce' ),
                    'notice'          => esc_html__( 'Page updated.', 'wphelpkit' ),
                ),
                ),
                ),
                    'wphelpkit-search-block-editor' => array(
                    'src'          => 'assets/js/blocks/dist/search/search',
                    'dependencies' => array( 'wp-element' ),
                    'localize'     => array(
                    'l10n' => array(
                    'name' => WPHelpKit_Search::$block,
                ),
                ),
                ),
                    'wphelpkit-blacklist-blocks'    => array(
                    'is_admin'     => true,
                    'dependencies' => array( 'wp-blocks' ),
                ),
                    'wphelpkit-article-link'        => array(
                    'dependencies' => array( 'jquery' ),
                    'localize'     => array(
                    'l10n' => array(
                    'ajaxurl' => admin_url( 'admin-ajax.php' ),
                    'action'  => WPHelpKit_Article::$article_link_action,
                    'nonce'   => wp_create_nonce( WPHelpKit_Article::$article_link_action . '-nonce' ),
                ),
                ),
                ),
                );
                foreach ( $scripts as $handle => $data ) {
                    // contruct $src, $dependencies & $in_footer if they are not already set.
                    $admin = ( isset( $data['is_admin'] ) && $data['is_admin'] ? 'admin/' : '' );
                    $src = ( !isset( $data['src'] ) ? 'assets/js/' . $admin . str_replace( 'wphelpkit-', '', $handle ) : $data['src'] );
                    $dependencies = ( !isset( $data['dependencies'] ) ? array() : $data['dependencies'] );
                    $in_footer = ( !isset( $data['in_footer'] ) ? true : $data['in_footer'] );
                    // register the script.
                    wp_register_script(
                        $handle,
                        plugins_url( "{$src}{$suffix}.js", __FILE__ ),
                        $dependencies,
                        self::VERSION,
                        $in_footer
                    );
                    // localize the script if necessary.
                    
                    if ( isset( $data['localize'] ) ) {
                        // contruct $object_name if not already set.
                        $object_name = ( !isset( $data['localize']['object_name'] ) ? str_replace( '-', '_', $handle ) : $data['localize']['object_name'] );
                        // localize the script.
                        wp_localize_script( $handle, $object_name, $data['localize']['l10n'] );
                    }
                
                }
                return;
            }
            
            /**
             * Enqueue our admin scripts and styles.
             *
             * @since 0.1.0
             *
             * @return void
             *
             * @action admin_enqueue_scripts
             */
            public function admin_enqueue_scripts()
            {
                global  $pagenow ;
                wp_enqueue_style( 'wphelpkit-admin' );
                return;
            }
            
            /**
             * Add a new "block category" for our blocks.
             *
             * @since 0.1.0
             *
             * @param array $categories
             * @param WP_Post $post
             * @return array
             */
            public function block_categories( $categories, $post )
            {
                $category = array( array(
                    'slug'  => 'wphelpkit',
                    'title' => esc_html__( 'WPHelpKit', 'wphelpkit' ),
                ) );
                $categories = array_merge( $categories, $category );
                return $categories;
            }
            
            /**
             * Only allow our blocks for specific post type(s).
             *
             * @since 0.0.5
             *
             * @return void
             */
            public function blacklist_blocks()
            {
                global  $post ;
                if ( 'page' === get_post_type( $post ) ) {
                    return;
                }
                wp_enqueue_script( 'wphelpkit-blacklist-blocks' );
                return;
            }
            
            /**
             * Check WP version
             *
             * @since 0.9.2
             *
             * @param string $operator used for comparation
             * @param string $version used for comparation
             * @return bool value of comparison
             */
            public static function is_wp_version( $operator, $version )
            {
                global  $wp_version ;
                return version_compare( $wp_version, $version, $operator );
            }
            
            /**
             * Is the user "previewing" something in Gutenberg?
             *
             * @since 0.0.5
             * @since 0.9.2 use is_block_editor() to determine if current page is a
             * gutenberg editor page when WP >= 5.3.2
             *
             * @return bool True if prevewing, false otherwise.
             */
            public static function is_gutenberg_preview()
            {
                if ( !self::is_wp_version( '>=', '5.3.2' ) || !function_exists( 'get_current_screen' ) ) {
                    return isset( $_REQUEST['context'] ) && 'edit' === $_REQUEST['context'];
                }
                $current_screen = get_current_screen();
                if ( !is_null( $current_screen ) && method_exists( $current_screen, 'is_block_editor' ) && $current_screen->is_block_editor() ) {
                    // Gutenberg page on 5.3.2+.
                    return true;
                }
                return false;
            }
            
            /**
             * Is the user "previewing" something either in the Customizer or in Gutenberg?
             *
             * @since 0.0.5
             *
             * @return bool True if prevewing, false otherwise.
             */
            public static function is_previewing()
            {
                return is_customize_preview() || self::is_gutenberg_preview();
            }
            
            /**
             * Set a transient on plugin activation, so the next hit knows we were just activated.
             *
             * @since 0.6.1
             *
             * @return void
             *
             * @action activate_wp-helpkit/plugin.php
             */
            public function activate()
            {
                set_transient( self::$activated_transient, true );
                // flush the rewrite rules after registering post_type and taxonomies.
                set_transient( WPHelpKit_Article::$flush_rewrite_rules_transient, true );
                return;
            }
            
            /**
             * On plugin activation, create a page to use as the HelpKit archive.
             *
             * @since 0.6.1
             *
             * @return void
             *
             * @action init
             */
            public function maybe_create_helpkit_page()
            {
                $settings = WPHelpKit_Settings::get_instance();
                $activated = get_transient( self::$activated_transient );
                if ( !$activated ) {
                    return;
                }
                delete_transient( self::$activated_transient );
                $archive_info = WPHelpKit_Archive_Info::get_instance();
                if ( 'default' !== $archive_info->type ) {
                    // archive already set.
                    return;
                }
                // create an archive page.
                $title = esc_html__( 'HelpKit', 'wphelpkit' );
                $slug = 'helpkit';
                /**
                 * Filters the title of the default HelpKit archive page.
                 *
                 * @since 0.6.1
                 *
                 * @param string $title The title of the page.
                 */
                $title = apply_filters( 'wphelpkit-default-archive-page-title', $title );
                $postarr = array(
                    'post_type'    => 'page',
                    'post_title'   => $title,
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_content' => '',
                );
                $page_id = wp_insert_post( $postarr );
                // set the new page as the index_page.
                if ( isset( $page_id ) && !empty($page_id) ) {
                    $settings->set_option( 'index_page', $page_id );
                }
                return;
            }
        
        }
        // instantiate ourselves
        WPHelpKit::get_instance();
    }
    
    if ( !function_exists( 'wphelpkit_is_json_request' ) ) {
        /**
         * Checks whether current request is a JSON request, or is expecting a JSON response.
         *
         * @since 0.2.0
         * @since WP Core 5.0.0
         *
         * @return bool True if Accepts or Content-Type headers contain application/json, false otherwise.
         */
        function wphelpkit_is_json_request()
        {
            if ( isset( $_SERVER['HTTP_ACCEPT'] ) && false !== strpos( $_SERVER['HTTP_ACCEPT'], 'application/json' ) ) {
                return true;
            }
            if ( isset( $_SERVER['CONTENT_TYPE'] ) && 'application/json' === $_SERVER['CONTENT_TYPE'] ) {
                return true;
            }
            return false;
        }
    
    }
}
