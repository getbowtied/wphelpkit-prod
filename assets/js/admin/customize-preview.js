/* global wp */

/**
 * Manage Customizer Live Preview changes.
 *
 * @since 0.0.2
 * @since 0.0.3 Added support for related_articles, voting, views and comments
 *              to the article section.
 */
( function ( $ ) {
    'use strict';

    /**
     * Show only the correct number of posts in a given category.
     *
     * This is a named function because we need the same code in 2 different
     * places below.
     *
     * @since 0.0.2
     *
     * @param {number} newval The number of posts to show.
     */
    var number_posts = function ( newval ) {
        $('.wphelpkit-article').hide();
        $('.wphelpkit-category').each(function () {
            $('.wphelpkit-article:lt( ' + newval + ' )', $(this)).show();
        });

        $('.wphelpkit-category').each(function () {
            if ( $('.wphelpkit-article', this).length > newval ) {
                $('.see-all', this).show();
            } else {
                $('.see-all', this).hide();
            }
        });
    };

    // display_categories_description
    wp.customize('wphelpkit[article_archive][display_categories_description]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-category-description').show();
            } else {
                $('.wphelpkit-category-description').hide();
            }
        });
    });

    // display_categories_thumbnail
    wp.customize('wphelpkit[article_archive][display_categories_thumbnail]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-category-thumbnail').show();
            } else {
                $('.wphelpkit-category-thumbnail').hide();
            }
        });
    });

    // display_posts
    wp.customize('wphelpkit[article_archive][display_posts]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-articles').show();
                number_posts(wp.customize('wphelpkit[article_archive][number_of_posts]').get());
            } else {
                $('.wphelpkit-articles').hide();
                $('.see-all').hide();
            }
        });
    });

    // number_of_posts
    wp.customize('wphelpkit[article_archive][number_of_posts]', function ( control ) {
        control.bind(number_posts);
    });

    // number_of_columns
    wp.customize('wphelpkit[article_archive][number_of_columns]', function ( control ) {
        control.bind(function ( newval ) {
            $('.wphelpkit-archive').removeClass('column-1 column-2 column-3');
            $('.wphelpkit-archive').addClass('column-' + newval);
        });
    });

    /* categories section: no controls specific to this seciton */

    /* article section */

    

    // related_articles
    wp.customize('wphelpkit[article][related_articles]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-related-articles').show();
            } else {
                $('.wphelpkit-related-articles').hide();
            }
        });
    });

    // comments
    wp.customize('wphelpkit[article][comments]', function ( control ) {
        /**
         * @since 0.5.0 Show/Hide comments depending on the current value of the control.
         */
        if ( control.get() ) {
            $('#comments').show();
        } else {
            $('#comments').hide();
        }

        control.bind(function ( newval ) {
            if ( newval ) {
                $('#comments').show();
            } else {
                $('#comments').hide();
            }
        });
    });

    // number_of_views
    wp.customize('wphelpkit[article][number_of_views]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-article-views').show();
            } else {
                $('.wphelpkit-article-views').hide();
            }
        });
    });

    /* same control in multiple sections */

    // breadcrumbs
    $.each(['category_archive', 'article'], function ( idx, value ) {
        wp.customize('wphelpkit[' + value + '][breadcrumbs]', function ( control ) {
            control.bind(function ( newval ) {
                if ( newval ) {
                    $('.wphelpkit-breadcrumbs').show();
                } else {
                    $('.wphelpkit-breadcrumbs').hide();
                }
            });
        });
    });

    // display_subcategories_description
    wp.customize('wphelpkit[category_archive][display_subcategories_description]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-subcategory-description').show();
            } else {
                $('.wphelpkit-subcategory-description').hide();
            }
        });
    });

    // display_articles_excerpt
    wp.customize('wphelpkit[category_archive][display_articles_excerpt]', function ( control ) {
        control.bind(function ( newval ) {
            if ( newval ) {
                $('.wphelpkit-article-excerpt').show();
            } else {
                $('.wphelpkit-article-excerpt').hide();
            }
        });
    });

    // search
    $.each(['article_archive', 'category_archive', 'article'], function ( idx, value ) {
        wp.customize('wphelpkit[' + value + '][search]', function ( control ) {
            control.bind(function ( newval ) {
                if ( newval ) {
                    $('#wphelpkit-search-form').show();
                } else {
                    $('#wphelpkit-search-form').hide();
                }
            });
        });
    });

    // display_subcategories
    $.each(['article_archive', 'category_archive'], function ( idx, value ) {
        wp.customize('wphelpkit[' + value + '][display_subcategories]', function ( control ) {
            control.bind(function ( newval ) {
                if ( newval ) {
                    $('.wphelpkit-subcategories').each(function () {
                        if ( $('li', this).length ) {
                            $(this).show();
                        }
                    });
                } else {
                    $('.wphelpkit-subcategories').hide();
                }
            });
        });
    });
} )(jQuery);
