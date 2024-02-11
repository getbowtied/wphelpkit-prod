<?php

if (! class_exists('WP_Import')) {
    return;
}

/**
 * Handle importing WPHelpKit information.
 *
 * Since display order information for articles within a category is associated
 * with the `term_id` of the category, upon import those `term_id`'s _may_ change,
 * hence this handles remapping the old -> new `term_id`.
 *
 * @since 0.3.0 Prior to this, functionality was in WPHelpKit_Article.
 */
class WPHelpKit_Importer
{
    /**
     * Our static instance.
     *
     * @since 0.3.0
     *
     * @var WPHelpKit_Article
     */
    private static $instance;

    /**
     * Our Categories being imported.
     *
     * @since 0.1.0
     * @since 0.1.4 Changed to a `protected` property.
     *
     * @var array
     */
    protected $import_categories = array();

    /**
     * Get our instance.
     *
     * Calling this static method is preferable to calling the class
     * constrcutor directly.
     *
     * @since 0.0.1
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
     * @since 0.0.1
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

    protected function add_hooks()
    {
        add_filter('wphelpkit_import_terms', array( $this, 'wphelpkit_import_terms' ));
        add_filter('import_post_meta_key', array( $this, 'import_post_meta_key' ), 10, 3);

        // tell WPHelpKit_Article_Category::category_created() not to add display order
        // information, because it will be handled by the import data.
        add_filter('wphelpkit-pre-category-created', '__return_false');

        return;
    }

    /**
     * Collect our Categories being imported, so that their `term_id`'s can be
     * remapped for the article "display order" `meta_keys`.
     *
     * @since 0.1.0
     *
     * @param array $terms
     * @return array
     *
     * @filter wphelpkit_import_terms
     */
    public function wphelpkit_import_terms($terms)
    {
        foreach ($terms as $term) {
            if (WPHelpKit_Article_Category::$category !== $term['term_taxonomy']) {
                // not one of our category terms, nothing to do.
                continue;
            }

            // note: we map the old term_id to its slug, so that we can
            //       look it up by that and get its new term_id in
            //       WPHelpKit_Importer::import_post_meta_key().
            $this->import_categories[ $term['term_id'] ] = $term['slug'];
        }

        // return the original terms unchanged.
        return $terms;
    }

    /**
     * Remap category `term_id`'s in "display order" post `meta_key`'s on import.
     *
     * @since 0.1.0
     *
     * @param string $meta_key
     * @param int $post_id
     * @param array $post
     * @return string
     *
     * @filter import_post_meta_key
     */
    public function import_post_meta_key($meta_key, $post_id, $post)
    {
        if (WPHelpKit_Article::$post_type !== $post['post_type']) {
            // not our post type, nothing to do.
            return $meta_key;
        }

        $regex = '/^' . WPHelpKit_Article::$display_order_meta_key . '-(\d+)$/';
        if (! preg_match($regex, $meta_key, $matches)) {
            // not our display order meta key, nothing to do.
            return $meta_key;
        }

        $term_id = intval($matches[1]);
        if (! isset($this->import_categories[ $term_id ])) {
            // term_id was not imported, nothing to do.
            return $meta_key;
        }

        // get the term as it was imported.
        $new_term = get_term_by('slug', $this->import_categories[ $term_id ], WPHelpKit_Article_Category::$category);
        if (! $new_term) {
            // term wasn't imported, nothing to do.
            return $meta_key;
        }

        // generate the new meta_key.
        $meta_key = WPHelpKit_Article::get_instance()->display_order_meta_key($new_term->term_id);

        return $meta_key;
    }
}
