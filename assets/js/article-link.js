/* global wphelpkit_article_link */

/**
 * Intercept clicks on links for articles that are in more than 1 category
 * when on the article/category archive pages so that a transient can
 * be added via AJAX so that the correct category is displayed in the
 * breadcrumbs when that article is displayed.
 *
 * @since 0.1.1
 */
( function ( $ ) {
    'use strict';

    $('.wphelpkit-article-title').on('click', function ( event ) {
        event.preventDefault();

        var category = $(this).data('helpkit-category'),
            href = $(this).attr('href'),
            data;

        if ( ! category ) {
            // the article is in only 1 category, so just process the click.
            window.location = href;

            return;
        }

        data = {
            action: wphelpkit_article_link.action,
            nonce: wphelpkit_article_link.nonce,
            data: {
                id: $(this).parent().parent().attr('id').replace('post-', ''),
                category: category,
            },
        };

        // store the transient via AJAX.
        $.post(wphelpkit_article_link.ajaxurl, data)
            .done(function () {
                // transient stored, so now process the click.
                window.location = href;
            });
    });
} )(jQuery);
