<?php
/**
 * Template part for displaying a message that posts cannot be found
 *
 * Based on Twenty Seventeen's template-parts/post/content-none.php
 *
 * @since 0.1.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<section class='no-results not-found'>
    <header class='page-header'>
        <h1 class='page-title'><?php esc_html_e('Nothing Found', 'wphelpkit'); ?></h1>
    </header>
    <div class='page-content'>
            <p><?php esc_html_e('It seems we can\'t find what you\'re looking for. Perhaps searching can help.', 'wphelpkit'); ?></p>
            <?php wphelpkit_search_form(); ?>
    </div><!-- .page-content -->
</section><!-- .no-results -->
