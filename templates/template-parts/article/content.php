<?php

/**
 * Template part for displaying single articles.
 *
 * Based on twentyseventeen's template-parts/post/content.php.
 *
 * @since 0.0.3
 */
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
// Exit if accessed directly
?>

<article id='post-<?php 
the_ID();
?>' <?php 
post_class();
?>>
    <header class='entry-header'>
        <div class='wphelpkit-prelude'>
            <?php 
wphelpkit_breadcrumbs();
wphelpkit_search_form();
?>
        </div>
<?php 
the_title( '<h1 class="entry-title">', '</h1>' );
?>
        <div class='entry-meta'>
            <span class='wphelpkit-article-author'>
<?php 
echo  wp_kses_post( get_avatar( $post->post_author, 40 ) ) ;
echo  wp_kses_post( sprintf( '<span class="name">%s</span>', get_the_author() ) ) ;
?>
            </span>
            <span class='wphelpkit-article-datetime'>
<?php 
$action = esc_html__( 'Published', 'wphelpkit' );
$time_string = '%1$s <time class="entry-date published" datetime="%2$s">%3$s</time>';
$date = get_the_date();
$date_w3c = get_the_date( DATE_W3C );

if ( get_the_time( 'U' ) !== get_the_modified_time( 'U' ) ) {
    $action = esc_html__( 'Updated', 'wphelpkit' );
    $time_string = '%1$s <time class="entry-date published updated" datetime="%2$s">%3$s</time>';
    $date = get_the_modified_date();
    $date_w3c = get_the_modified_date( DATE_W3C );
}

$time_string = sprintf(
    $time_string,
    $action,
    $date_w3c,
    $date
);
// Wrap the time string in a link, and preface it with 'Posted on'.
?>
                <span class="screen-reader-text"><?php 
esc_html_e( 'Posted on', 'wphelpkit' );
?></span>
                <span><?php 
echo  wp_kses_post( $time_string ) ;
?></span>

            </span>
<?php 
?>
        </div>
    </header><!-- .entry-header -->

    <?php 

if ( '' !== get_the_post_thumbnail() ) {
    ?>
        <div class='post-thumbnail'>
            <a href='<?php 
    the_permalink();
    ?>'>
                <?php 
    the_post_thumbnail();
    ?>
            </a>
        </div><!-- .post-thumbnail -->
    <?php 
}

?>

    <div class='entry-content'>
        <?php 
/* translators: %s: Name of current post */
the_content( esc_html__( 'Continue reading', 'wphelpkit' ) . '<span class="screen-reader-text"> ' . get_the_title() . '</span>' );
wp_link_pages( array(
    'before'      => '<div class="page-links">' . esc_html__( 'Pages:', 'wphelpkit' ),
    'after'       => '</div>',
    'link_before' => '<span class="page-number">',
    'link_after'  => '</span>',
) );
?>

    </div><!-- .entry-content -->

    <footer class='entry-footer'>

        <?php 
/* translators: used between list items, there is a space after the comma */
$separate_meta = esc_html__( ', ', 'wphelpkit' );
$tags_list = get_the_term_list(
    get_the_ID(),
    WPHelpKit_Article_Tag::$tag,
    '',
    $separate_meta,
    ''
);
if ( $tags_list && !is_wp_error( $tags_list ) ) {
    echo  '<span class="wphelpkit-tags-links"><span class="wphelpkiticons wphelpkiticons-tag"></span><span class="screen-reader-text">' . esc_html__( 'Tags', 'wphelpkit' ) . '</span>' . wp_kses_post( $tags_list ) . '</span>' ;
}
edit_post_link( esc_html__( 'Edit', 'wphelpkit' ) . '<span class="screen-reader-text"> ' . get_the_title() . '</span>', '<span class="edit-link">', '</span>' );
$related_articles = WPHelpKit_Article::get_instance()->get_related_articles( $post );

if ( WPHelpKit_Settings::get_instance()->get_option( 'related_articles' ) && !empty($related_articles) || WPHelpKit_Settings::get_instance()->get_option( 'article_voting' ) && wphelpkit_fs_init()->is__premium_only() ) {
    ?>
            <div class='wphelpkit-colophon'>
                <?php 
    wphelpkit_related_articles();
    ?>
            </div>
            <?php 
}

?>

    </footer>
</article><!-- #post-## -->
