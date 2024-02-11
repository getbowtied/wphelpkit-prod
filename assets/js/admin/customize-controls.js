/* global wp */

/* global wphelpkit_customizer */

/**
 * Manage Customizer controls.
 *
 * @since 0.0.2
 */
( function ( $ ) {
    'use strict';

    wp.customize.bind('ready', function () {
        // add an `hr` tag between each of our controls so that the sections
        // look less "crowded".
        $('li:gt( 0 ):not( :last )', 'ul[id^="sub-accordion-section-wphelpkit"]');

        $('#_customize-input-show_on_front-radio-wphelpkit-index').on('change', function () {
            // refresh Live Preview with the home (archive) URL
            wp.customize.previewUrl(wp.customize.settings.url.home);
        });

        // Detect when the article archive section is expanded (or closed) so we can adjust the preview accordingly.
        wp.customize.section('wphelpkit_article_archive', function ( section ) {
            section.expanded.bind(function ( /*isExpanding*/ ) {
                // Value of isExpanding will = true if you're entering the section, false if you're leaving it.
                // Only show the number_of_posts control when display_posts is true.
                // based on the color hue control conditionality in twentyseventeen's customize-controls.js.
                wp.customize('wphelpkit[article_archive][display_posts]', function ( setting ) {
                    wp.customize.control('wphelpkit[article_archive][number_of_posts]', function ( control ) {
                        var visibility = function () {
                            if ( setting.get() ) {
                                control.activate();
                            } else {
                                control.deactivate();
                            }
                        };

                        visibility();
                        setting.bind(visibility);
                    });
                });
            });
        });
    });
} )(jQuery);

/**
 * Dynamically change the Live Preview URL depending on which of our sections
 * is opened.
 *
 * @since 0.0.3
 * @since 0.1.3 code cleanup to remove duplication.
 *
 * @link https://github.com/wphelpkit/WP-HelpKit/issues/17
 * @link https://make.xwp.co/2016/07/21/navigating-to-a-url-in-the-customizer-preview-when-a-section-is-expanded/
 */
( function ( api, $ ) {
    'use strict';

    /**
     * @since 0.6.1 moved from inline to a named function.
     */
    var bind_section_open = function ( section, item ) {
        var previousUrl, clearPreviousUrl, previewUrlValue;
        previewUrlValue = api.previewer.previewUrl;
        clearPreviousUrl = function () {
            previousUrl = null;
        };

        section.expanded.bind(function ( isExpanded ) {
            if ( isExpanded ) {
                var previewUrl = previewUrlValue();
                if ( previewUrl === item.url ||
                        ( item.regex && previewUrl.match(item.regex) ) ) {
                    previousUrl = previewUrl;

                    return;
                }

                previousUrl = previewUrlValue.get();
                previewUrlValue.set(item.url);
                previewUrlValue.bind(clearPreviousUrl);
            } else {
                previewUrlValue.unbind(clearPreviousUrl);
                if ( previousUrl ) {
                    previewUrlValue.set(previousUrl);
                }
            }
        });
    };

    // these are our sections and their associated urls/regexes.
    var sections = {
        'wphelpkit_article_archive': {
            'url': wphelpkit_customizer.article_archive,
            'regex': '',
        },
        'wphelpkit_category_archive': {
            'url': wphelpkit_customizer.category_archive,
            'regex': wphelpkit_customizer.category_archive_regex,
        },
        'wphelpkit_article': {
            'url': wphelpkit_customizer.article,
            'regex': wphelpkit_customizer.article_regex,
        },
    };

    // loop over each section and setup the binding.
    $.each(sections, function ( section_id, item ) {
        api.section(section_id, function ( section ) {
            bind_section_open(section, item); });
    });
} ( wp.customize, jQuery ) );
