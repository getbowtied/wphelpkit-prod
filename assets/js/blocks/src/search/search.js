/* global wp */
/* global wphelpkit_search_block_editor */

const { createElement }     = wp.element;
const { registerBlockType } = wp.blocks;
const {
    ServerSideRender,
    TextControl,
    RadioControl,
    Panel,
    PanelBody,
    PanelRow,
    SVG,
    Path,
}                           = wp.components;
const { InspectorControls } = wp.blockEditor;
const { __ }                = wp.i18n;

/**
 * Register our search block.
 *
 * @since 0.0.5
 * @since 0.6.2 Changed block's icon.
 */
registerBlockType(wphelpkit_search_block_editor.name, {
    title: __('WPHelpKit Search'),
    description: __('Search WPHelpKit Articles.'),
    icon:
        <SVG xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'>
            <Path fill='none' d='M0 0h24v24H0V0z' />
            <Path d='M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59
            4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z' />
        </SVG>,
    category: 'wphelpkit',
    supports: {
        html: false, // there's nothing for user to edit
        multiple: false,
    },

    /**
     * Render our search widget (and it's inspector controls) in the editor.
     *
     * @since 0.0.5
     *
     * @param {Object} props
     * @return {array} Inspector controls and editor version of the form.
     */
    edit: function ( props ) {
        return [
            // the @onSubmit is so users don't submit the form while in the editor.
            <form id='wphelpkit-search-form' method='GET' onSubmit={ e => e.preventDefault() } key='form'>
                <input type='text' id="wphelpkit-search" placeholder={ props.attributes.placeholder } />
                <button type='submit' class='wphelpkit-search components-button is-button is-default is-large'>
                    <svg aria-hidden="true" role="img" focusable="false" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 30 30">
                        <path d="M 13 3 C 7.4889971 3 3 7.4889971 3 13 C 3 18.511003 7.4889971 23 13 23 C 15.396508 23 17.597385 22.148986 19.322266 20.736328 L 25.292969 26.707031 A 1.0001 1.0001 0 1 0 26.707031 25.292969 L 20.736328 19.322266 C 22.148986 17.597385 23 15.396508 23 13 C 23 7.4889971 18.511003 3 13 3 z M 13 5 C 17.430123 5 21 8.5698774 21 13 C 21 17.430123 17.430123 21 13 21 C 8.5698774 21 5 17.430123 5 13 C 5 8.5698774 8.5698774 5 13 5 z">
                        </path>
                    </svg>
                </button>
            </form>,

            <InspectorControls key='wphelpkit-search-inspector'>
                <PanelBody
                        key='wphelpkit-search-inspector-settings'
                        title={ __('Search Settings') }
                        initialOpen={ true }>
                    <TextControl
                        key='wphelpkit-search-inspector-placeholder'
                        label={ __('Placeholder Text') }
                        value={ props.attributes.placeholder }
                        onChange={ ( value ) => props.setAttributes({ placeholder: value }) }
                    />
                </PanelBody>
            </InspectorControls>
        ];
    },

    /**
     * No-Op save method.
     *
     * Dyanmic block, so only attributes are saved.
     *
     * @since 0.0.5
     */
    save: function () {
        return null;
    },
});
