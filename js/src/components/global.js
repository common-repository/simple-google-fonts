const { Notice, ToggleControl } = wp.components;

const { __ } = wp.i18n;

/**
 * Displays the global option and adds a notice if the post isn't published
 *
 * @since  1.0.0
 * @param  {Object} props Inherited props from `SimpleGoogleFonts`
 * @return {Void}
 */
export const SGFGlobalOption = props => {
    const { isPublished } = props.info;
    const { meta, updateEl } = props;
    
    if( ! isPublished ) {
        return (
            <Notice
                className="sgf-globally-warning" 
                status="warning"
                isDismissible={ false }
            >
                { __( 'You can not use this option if the post is not published. If the global option was enabled for this post and you switched to draft mode, you will have to enable it again after you publish.' ) }
            </Notice>
        );
    }  
    
    return (
        <ToggleControl
            label={ __( 'Make styles global' ) }
            checked={ meta.sgf_is_global || false  }
            help={ __( 'Make the styles used for this post global? If the answer is yes, then the styles will apply globally as defaults for new posts and old posts that do not have any Google Fonts styles saved.' ) }
            onChange={ value => updateEl( 'global', 'is', value ) }
        />
    );
} 
