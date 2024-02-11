<?php

/**
 * Generate an array (keyed by page->ID) of pages for use in the
 *
 * @since 0.6.1
 * @since 0.6.2 Removed the overridden `walk()` method.
 */
class WPHelpKit_Customizer_Page_Walker extends Walker_PageDropdown
{
    /**
     * Starts the element output.
     *
     * @since 0.6.1
     *
     * @see Walker::start_el()
     *
     * @param array  $output Used to append additional content. Passed by reference.
     * @param WP_Post $page   Page data object.
     * @param int     $depth  Optional. Depth of page in reference to parent pages. Used for padding.
     *                        Default 0.
     * @param array   $args   Optional. Uses 'selected' argument for selected page to set selected HTML
     *                        attribute for option element. Uses 'value_field' argument to fill "value"
     *                        attribute. See wp_dropdown_pages(). Default empty array.
     * @param int     $id     Optional. ID of the current page. Default 0 (unused).
     * @return void
     */
    public function start_el(&$output, $page, $depth = 0, $args = array(), $id = 0)
    {
        $pad = str_repeat('&nbsp;', $depth * 3);

        if (empty($output)) {
            // `Walker_PageDropdown` normally treats `$output` as a string so we have
            // to convert to an array on the first call.
            $output = array();
        }

        $idx = $page->ID;

        $output[ $idx ] = $pad . esc_html($page->post_title);

        return;
    }
}
