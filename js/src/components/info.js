const { Notice } = wp.components;

const { Fragment } = wp.element;

const { __ } = wp.i18n;

/**
 * Displays the information from the `Information` panel
 *
 * @since  1.0.0
 * @return {Void}
 */
export const SGFInfoPanel = () => {
    const props = {
        className     : 'sgf-information-notice',
        status        : 'success',
        isDismissible : false
    };

    return (
        <Fragment>

            <Notice { ...props } >
                { __( 'It works only with posts and pages for now.' ) }
            </Notice>

            <Notice { ...props } >
                { __( 'It is safe to say that if you do not have an Internet connection, Google fonts will not display (unless you have them installed on your system).' ) }
            </Notice>

            <Notice { ...props } >
                { __( 'The plugin will dequeue other Google fonts registered by themes/plugins. WordPress Core registered styles are not dequeued. If you do not select a font, it will fallback to default system (OS) fonts.' ) }
            </Notice>

            <Notice { ...props } >
                { __( 'If you want to use the global option, make sure you publish the post. If for some reason you switch the post to draft mode, the global option will reset and you will need to enable it again.' ) }
            </Notice>

        </Fragment>
    );
}