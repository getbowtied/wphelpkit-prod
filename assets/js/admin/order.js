/* global wphelpkit_order */

/**
 * Drag-and-drop ordering of articles and categories the Customizer Live Preview.
 *
 * @since 0.1.0
 */
( function ( $ ) {
    'use strict';

    /**
     * Inform the user that a iten is draggable (or not).
     *
     * @since 0.6.1
     */
    $.each(['.wphelpkit-category', '.wphelpkit-subcategory', '.wphelpkit-article'], function () {
        if ( ( '.wphelpkit-category' === this && ! $(this).parents('.wphelpkit-archive').length ) ||
                ( '.wphelpkit-article' === this && $(this).parents('.wphelpkit-tag').length ) ||
                ( '.wphelpkit-article' === this && $(this).parents('.wphelpkit-related-articles').length ) ) {
            // we're previewing a specific category (and not the article archive)
            // or a tag archive or a related article list
            // so don't add the "I'm draggable" outline on the category.
            // see also the comment below in the "mouseleave" handler.
            return true;
        }

        // mouse enter
        $(this).on('hover', function ( event ) {
            if ( 1 === event.buttons ) {
                // primary mouse button down, drag in progress, don't update.
                return;
            }

            // clear any other draggables
            $('.wphelpkit-draggable').removeClass('wphelpkit-draggable');
            // indicate that we're draggable
            $(this).addClass('wphelpkit-draggable');
        });
        // mouse leave
        $(this).on('mouseleave', function () {
            if ( 1 === event.buttons ) {
                // primary mouse button down, drag in progress, don't update.
                return;
            }

            // clear that we're draggable
            $(this).removeClass('wphelpkit-draggable');
            // the check for having `.wphelpkit-archive` as an ancestor is to
            // ensure that the category only gets the "I'm draggable" outline
            // if we're previewing the article archive and not just an individual
            // category archive.
            if ( ! $(this).hasClass('wphelpkit-category') &&
                    $(this).parents('.wphelpkit-archive').length ) {
                // indicate that our parent category is draggable again
                $(this).parents('.wphelpkit-category').addClass('wphelpkit-draggable');
            }
        });
    });

    /**
     * Inform the user that an "operation" is starting or done.
     *
     * @since 0.1.0
     *
     * @param {bool} busy True if operation is starting, false if done.
     * @return void
     *
     */
    var setBusy = function ( busy, item ) {
        if ( busy ) {
            $(item).parents('body').addClass('wphelpkit-busy');
        } else {
            $(item).parents('body').removeClass('wphelpkit-busy');
        }
    };

    /**
     * Highlight the relevant items on the screen during a drag.
     *
     * The highlighting is achieved by making areas that aren't relevant
     * less opaque.
     *
     * @since 0.1.0
     *
     * @param {jQuery Object} item The table row that is being dragged.
     * @param {bool}          start True if the drag is starting; false if it is ending.
     * @return {void}
     */
    var highlight = function ( item, start ) {
        if ( start ) {
            if ( $(item).hasClass('wphelpkit-category') ) {
                $(item).css('overflow-y', 'hidden');
                $('.wphelpkit-category').css('opacity', '0.5');
                $(item).css('opacity', '1');
            } else if ( $(item).hasClass('wphelpkit-article') ) {
                $('.wphelpkit-category').css('opacity', '0.5');
                $(item).parents('.wphelpkit-category').find('.wphelpkit-subcategories, .see-all').css('opacity', '0.5');
                $(item).parents('.wphelpkit-category').css('opacity', '1');
            } else if ( $(item).hasClass('wphelpkit-subcategory') ) {
                $('.wphelpkit-category').css('opacity', '0.5');
                $(item).parents('.wphelpkit-category').css('opacity', '1');
                $(item).parents('.wphelpkit-category').find('.wphelpkit-articles .wphelpkit-article').css('opacity', '0.5');
            }
        } else {
            if ( $(item).hasClass('wphelpkit-category') ) {
                $(item).css('overflow-y', 'initial');
                $('.wphelpkit-category').css('opacity', '1');
            } else if ( $(item).hasClass('wphelpkit-article') ) {
                $('.wphelpkit-category').css('opacity', '1');
                $(item).parents('.wphelpkit-category').find('.wphelpkit-subcategories, .see-all').css('opacity', '1');
            } else if ( $(item).hasClass('wphelpkit-subcategory') ) {
                $('.wphelpkit-category').css('opacity', '1');
                $(item).parents('.wphelpkit-category').find('.wphelpkit-articles .wphelpkit-article').css('opacity', '1');
            }
        }

        return;
    };

    /**
     * Make categories sortable.
     *
     * Note that unlike subcategories and articles, we do *not* constrain
     * the dragging to `parent`.
     *
     * @since 0.1.0
     */
    $('.wphelpkit-archive').sortable({
        items: '.wphelpkit-category',
        placeholder: 'wphelpkit-sortable-placeholder',
        forcePlaceholderSize: true,
        tolerance: 'pointer',

        /**
         * Perform necessary setup when starting to drag.
         *
         * @since 0.1.0
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        start: function ( e, ui ) {
            highlight(ui.item, true);
        },

        /**
         * Perform necessary cleanup when ending a drag.
         *
         * @since 0.1.0
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        stop: function ( e, ui ) {
            highlight(ui.item, false);
            successful_order_notice();
        },

        /**
         * Item has been dropped.  Update the "display order" term meta
         * via AJAX.
         *
         * @since 0.1.0
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        update : function ( e, ui ) {
            highlight(ui.item, false);

            // inform the user that we're starting an "operation".
            setBusy(true, ui.item);

            var data = {
                action: wphelpkit_order.category_action,
                nonce: wphelpkit_order.category_nonce,
                data: {
                    order: $(this).sortable('serialize'),
                },
            };

            // do the AJAX call.
            $.post(wphelpkit_order.ajaxurl, data)
                .always(function () {
                    // inform the user that the "operation" is done.
                    setBusy(false, ui.item);
                });
        },
    });

    /**
     * Make subcategories sortable.
     *
     * @since 0.1.0
     */
    $('.wphelpkit-subcategories').sortable({
        items: '.wphelpkit-subcategory',
        placeholder: 'wphelpkit-sortable-placeholder',
        forcePlaceholderSize: true,
        axis: 'y',
        containment: 'parent',
        tolerance: 'pointer',

        /**
         * Perform necessary setup when starting to drag.
         *
         * Currently, the only setup is adjusting the containment.
         *
         * @since 0.0.4
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        start: function ( e, ui ) {
            var instance = $(this).sortable('instance');

            instance.containment[1] -= 5;

            highlight(ui.item, true);

            return;
        },

        stop: function ( e, ui ) {
            highlight(ui.item, false);
            successful_order_notice();
        },

        /**
         * Item has been dropped.  Update the "display order" term meta
         * via AJAX.
         *
         * If the position where the item has been dropped is not "legal"
         * (e.g., a top-level item dropped between 2 child terms of another
         * top-level term) cancel the drop operation and leave the metas unchanged.
         *
         * @since 0.0.4
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        update : function ( e, ui ) {
            highlight(ui.item, false);

            // inform the user that we're starting an "operation".
            setBusy(true, ui.item);

            var data = {
                action: wphelpkit_order.category_action,
                nonce: wphelpkit_order.category_nonce,
                data: {
                    order: $(this).sortable('serialize'),
                },
            };

            // do the AJAX call.
            $.post(wphelpkit_order.ajaxurl, data)
                .always(function () {
                    // inform the user that the "operation" is done.
                    setBusy(false, ui.item);
                });
        },
    });

    /**
     * Make articles sortable.
     *
     * @since 0.1.0
     */
    $('.wphelpkit-category').sortable({
        items: '.wphelpkit-article',
        placeholder: 'wphelpkit-sortable-placeholder',
        forcePlaceholderSize: true,
        axis: 'y',
        containment: 'parent',
        tolerance: 'pointer',

        /**
         * Perform necessary setup when starting to drag.
         *
         * Currently, the only setup is adjusting the containment.
         *
         * @since 0.0.4
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        start: function ( e, ui ) {
            var instance = $(this).sortable('instance');
            instance.containment[1] -= 5;

            highlight(ui.item, true);
        },

        stop: function ( e, ui ) {
            highlight(ui.item, false);
            successful_order_notice();
        },

        /**
         * Item has been dropped.  Update the "display order" term meta
         * via AJAX.
         *
         * If the position where the item has been dropped is not "legal"
         * (e.g., a top-level item dropped between 2 child terms of another
         * top-level term) cancel the drop operation and leave the metas unchanged.
         *
         * @since 0.0.4
         *
         * @param {Event}  e  The event that initiated the drag.
         * @param {Object} ui Sortable UI obj.
         */
        update : function ( e, ui ) {
            highlight(ui.item, false);

            // inform the user that we're starting an "operation".
            setBusy(true, ui.item);

            var data = {
                action: wphelpkit_order.article_action,
                nonce: wphelpkit_order.article_nonce,
                data: {
                    order: $(this).sortable('serialize'),
                    category: $(ui.item).parents('.wphelpkit-category').attr('id').replace('tag-', ''),
                },
            };

            // do the AJAX call.
            $.post(wphelpkit_order.ajaxurl, data)
                .always(function () {
                    // inform the user that the "operation" is done.
                    setBusy(false, ui.item);
                });
        },
    });

    /**
     * Creates and animates a success notice after order completion
     *
     * @since 0.9.0
     *
     */
    function successful_order_notice()
    {
        if (!$('.wphelpkit-order-notice').length) {
            $('body').append('<div class="wphelpkit-order-notice"><div class="wphelpkit-order-notice-container">' + wphelpkit_order.notice + '</div></div>');
            $(".wphelpkit-order-notice").animate({
                bottom: '50',
            }, 300, function () {
            });
            setTimeout(function () {
                $(".wphelpkit-order-notice").animate({
                    opacity: 0,
                }, 500, function () {
                });
            }, 2500);
            setTimeout(function () {
                $('.wphelpkit-order-notice').remove();
            }, 3000);
        }
    }

} )(jQuery);
