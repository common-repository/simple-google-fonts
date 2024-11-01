import fontsObj, * as fonts from './json/google-fonts.json';

import { 
    PLUGIN_INFO, 
    HEADINGS,
    HEADINGS_TABS, 
    TEXT_TRANSFORM 
} from './constants';

import { 
    capitalizeWords, 
    newStyleLinkURL, 
    parseHeadingValues, 
    stringifyHeadingValues,
    getHeadingValue,
    createStyleNodes,
    addGlobalHeadings,
    addDefaultsToMeta
} from './utils';

import { SGFGlobalOption } from './components/global';

import { SGFInfoPanel } from './components/info';

import theStyles from './styles';

const { __ } = wp.i18n;

const { 
    PanelBody, 
    TabPanel, 
    SelectControl, 
    RangeControl 
} = wp.components;

const { Component, Fragment } = wp.element;

const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;

const { withDispatch, withSelect, select } = wp.data;

const { compose } = wp.compose;

const { uniq } = lodash;

/**
 * Simple Google Fonts
 * 
 * @since   1.0.0
 * @class   SimpleGoogleFonts
 * @extends {Component}
 */
class SimpleGoogleFonts extends Component {

    /**
     * Creates an instance of SimpleGoogleFonts.
     * 
     * @memberof SimpleGoogleFonts
     */
    constructor() {
        super( ...arguments );

        this.changeFontsWeights   = this.changeFontsWeights.bind( this );
        this.changeTransform      = this.changeTransform.bind( this );
        this.changeLineHeight     = this.changeLineHeight.bind( this );
        this.changeLetterSpacing  = this.changeLetterSpacing.bind( this );
        this.changeWordSpacing    = this.changeWordSpacing.bind( this );
        this.changeStyles         = this.changeStyles.bind( this );
        this.updateElement        = this.updateElement.bind( this );
    }

    /**
     * Do this once when the component did mount
     *
     * @todo     Maybe add some filters in future versions.
     * @memberof Component
     */
    componentDidMount() {
        // Create the link and style nodes
        createStyleNodes();

        // Change fonts and weights in the Google Fonts URL
        const panels = [ 'body', 'headings' ];
        const types  = [ 'fonts', 'weights' ];

        for( const panel of panels ) {
            for( const type of types ) {
                this.changeFontsWeights( panel, type );
            }
        }

        // Make sure we have the correct/updated styles
        this.changeStyles();
    }

    /**
     * Do some checks
     *
     * @todo     Maybe take another look at this.
     * @param    {Object} prevProps
     * @memberof SimpleGoogleFonts
     */
    componentDidUpdate( prevProps ) {
        const { meta } = this.props;
        const { isPublished } = this.props.info;
        const { isPublished:isPublishedPrev } = prevProps.info;

        if( isPublished !== isPublishedPrev ) {
            if( isPublished === false && meta.sgf_is_global === true ) {
                this.updateElement( 'global', 'is', false );
            }
        }
    }

    /**
     * All the font families as options to be used in a <select> element 
     * 
     * @since   1.0.0
     * @returns {Array} The label and index as its value
     */
    fontsOptions() {
        return fontsObj.map( ( font, i ) => ( { label: font.f, value: i } ) );
    }

    /**
     * All the font weights as options to be used in a <select> element 
     *
     * @since   1.0.0
     * @param   {Integer} font Font intex
     * @returns {Array}        The label and weight
     */
    weightsOptions( font ) {
        return fontsObj[ font ].v
        .filter( weight => ! weight.includes( '0i' ) )
        .map( weight => {
            return { label: weight, value: weight }
        } );
    }

    /**
     * All the text transform options to be used in a <select> element 
     * 
     * @since   1.0.0
     * @returns {Array} The label and transform
     */
    transformOptions() {
        return TEXT_TRANSFORM.map( size => ( { label: capitalizeWords( size ), value: size } ) );
    }

    /**
     * Get meta value
     *
     * @since   1.0.0
     * @param   {String} item  Select a specific key from our post meta
     * @param   {String} panel The panel in which this option is located
     * @returns {Object}       Selected meta
     */
    getMeta( item, panel ) {
        const newMeta = select('core/editor').getEditedPostAttribute( 'meta' );
        
        let meta = {
            ...newMeta
        }
        
        meta = addDefaultsToMeta( meta );
        meta = addGlobalHeadings( meta );

        if( item && panel ) {
            return meta[ `sgf_${item}_${panel}` ];
        }
        
        return meta;
    }

    /**
     * Changes the styles for preview, check `../js/src/styles/`
     *
     * @since  1.0.0
     * @return {Void}
     */
    changeStyles() {
        theStyles( this.getMeta(), fontsObj );
    }

    /**
     * Changes the font families and font weights based on the selected values
     *
     * @since   1.0.0
     * @todo    Simplify the weights part, create a function for both.
     * @param   {String}         panel Either `headings` or `body`
     * @param   {String}         type  Either change `fonts` or `weights`
     * @returns {Object|Boolean}              An object containing the new/current link and decoded family argument 
     *                                        or false if no font is selected.
     */
    changeFontsWeights( panel, type ) {
        const link      = document.querySelector( `#${simpleGFonts[ `${panel}_id` ]}` );
        const meta      = this.getMeta;
        const fontID    = meta( 'ff', panel );
        const newstyles = this.changeStyles;
        const def       = '400';
        const idef      = `${def}i`;

        const { timeout, default_url, weights_def } = simpleGFonts;
        
        if( fontID === 0 ) {
            link.href = default_url;
            setTimeout( newstyles, timeout );

            return false;
        }

        const variants = fontsObj[ fontID ].v;

        let newLink;

        const oldFamily = decodeURIComponent( link.href.split( '?' )[ 1 ] )
        .split( '&' )[ 0 ]
        .split( '=' )[ 1 ];

        switch( type ) {
            case 'fonts':
                let newFont = fontsObj[ fontID ].f.replace( ' ', '+' ) + `:${def}`;

                if( variants.find( variant => variant.includes( idef ) ) ) {
                    newFont = `${newFont},${idef}`;
                }

                newLink = newStyleLinkURL( [ oldFamily, newFont ], link );
                break;

            case 'weights':
                const oldWeights = oldFamily.split( ':' )[ 1 ];
                let weights;

                switch( panel ) {
                    case 'headings':
                        weights = meta( 'els', panel )
                        .map( heading => parseHeadingValues( heading ) )
                        .map( heading => heading.wt );

                        weights = uniq( weights );

                        let italics = [];

                        for( const weight of weights ) {
                            const italic = variants.find( variant => variant.includes( `${weight}i` ) );

                            if( italic ) {
                                italics.push( italic );
                            }
                        }

                        if( weights_def ) {
                            if( weights.find( weight => weight !== def ) ) {
                                weights.push( def );
                            }
    
                            if( weights.find( weight => weight !== idef ) ) {
                                if( variants.find( variant => variant.includes( idef ) ) ) {
                                    weights.push( idef );
                                }
                            }
                        }

                        weights = [ ...weights, ...italics ].sort().join( ',' );

                        newLink = newStyleLinkURL( [ oldWeights, weights ], link, false );
                        break;
        
                    case 'body':
                        let weight = meta( 'wt', panel );
                        weights = [];

                        weights.push( weight );

                        const italic = variants.findIndex( variant => 
                            variant.includes( `${weight}i` ) 
                        );

                        if( italic !== -1 ) {
                            weights.push( `${variants[ italic ]}` );
                        }

                        if( weights_def ) {
                            if( weight !== def ) {
                                weights.push( def );
                            }

                            if( variants.findIndex( variant => variant.includes( idef ) ) !== -1 ) {
                                weights.push( idef );
                            }
                        }

                        newLink = newStyleLinkURL( 
                            [ oldWeights, weights.sort().join( ',' ) ], 
                            link, false 
                        );
                        break;
        
                    default:
                        break;
                }
                break;

            default:
                break;
        }

        link.href = link.href.replace( link.href, newLink );

        setTimeout( newstyles, timeout );

        return { newLink, link, oldFamily };
    }

    /**
     * Wrapper for changeStyles(), in case we need to add something later
     *
     * @since  1.0.0
     * @return {Void}
     */
    changeTransform() {
        this.changeStyles();
    }

    /**
     * Wrapper for changeStyles(), in case we need to add something later
     *
     * @since  1.0.0
     * @return {Void}
     */
    changeLineHeight() {
        this.changeStyles();
    }

    /**
     * Wrapper for changeStyles(), in case we need to add something later
     *
     * @since  1.0.0
     * @return {Void}
     */
    changeLetterSpacing() {
        this.changeStyles();
    }

    /**
     * Wrapper for changeStyles(), in case we need to add something later
     *
     * @since  1.0.2
     * @return {Void}
     */
    changeWordSpacing() {
        this.changeStyles();
    }

    /**
     * Updates meta value on event
     *
     * @since  1.0.0
     * @param  {String}  panel           The panel where this is used
     * @param  {String}  type            Propriety type
     * @param  {Mixed}   value           Depending on the component, it can be a string a boolean
     * @param  {Boolean} [heading=false] Update headings meta?
     * @return {Void}
     */
    updateElement( panel, type, value, heading = false ) {
        const { oldmeta, updateSingleMeta, updateHeadingsMeta } = this.props;

        const meta     = this.getMeta();
        const changeFW = this.changeFontsWeights;
        const args     = { panel, type, value };

        if( ! heading ) {
            updateSingleMeta( args, meta, oldmeta );
        } else {
            const hargs = { el: panel, prop: type, value }

            updateHeadingsMeta( hargs, meta, oldmeta );
        }

        if( [ 'ff', 'wt' ].find( prop => prop === type ) ) {
            if( 'wt' === type ) {
                const wt = 'weights';
                if( HEADINGS.find( heading => heading === panel ) ) {
                    changeFW( 'headings', wt );
                } else {
                    changeFW( panel, wt );
                }
            }

            if( 'ff' === type ) {
                changeFW( panel, 'fonts' );
            }
        }

        switch( type ) {
            case 'lh':
                this.changeLineHeight();
                break;
            case 'ls':
                this.changeLetterSpacing();
                break;
            case 'tt':
                this.changeTransform();
                break;
            case 'ws':
                this.changeWordSpacing();
                break;
            default:
                break;
        }
    }

    /**
     * Render the damn thing
     * 
     * @todo     Use <Autocomplete/> for selecting fonts
     * @memberof Component|SimpleGoogleFonts
     */
    render() {
        const { sidebarId, pluginName, sidebarIcon } = PLUGIN_INFO;
        const { meta } = this.props;
        const updateEl = this.updateElement;

        return (
            <Fragment>

                <PluginSidebar
                    name={ sidebarId }
                    title={ pluginName }
                    icon={ sidebarIcon }
                    isPinnable={ true }
                >

                    { /* /////////// Headings panel /////////// */}
                    <PanelBody title={ __( 'Headings' ) } initialOpen={ false }>
                        <SelectControl 
                            label={ __( 'Headings font family' ) }
                            value={ meta.sgf_ff_headings }
                            options={ this.fontsOptions() }
                            onChange={ value => updateEl( 'headings', 'ff', value ) }
                        />

                        { /* ////// Headings tabs ////// */ }
                        <TabPanel className="sgf-headings-tabs"
                            activeClass="active-tab"
                            tabs={ HEADINGS_TABS }>
                            {
                                ( tab ) => { 
                                    const tabName = tab.hasOwnProperty( 'name' ) ? tab.name : tab;

                                    return (
                                    <Fragment>
                                        { /* /// Headings: Font weight /// */ }
                                        <SelectControl 
                                            label={ __( 'Font weight:' ) }
                                            value={ getHeadingValue( tabName, 'wt', meta ) }
                                            options={ this.weightsOptions( meta.sgf_ff_headings ) }
                                            onChange={ value => updateEl( tabName, 'wt', value, true ) }
                                        />
                                        
                                        { /* /// Headings: Text transform /// */ }
                                        <SelectControl 
                                            label={ __( 'Text transform:' ) }
                                            value={ getHeadingValue( tabName, 'tt', meta ) }
                                            options={ this.transformOptions() }
                                            onChange={ value => updateEl( tabName, 'tt', value, true ) }
                                        />

                                        { /* /// Headings: Line height /// */ }
                                        <RangeControl 
                                            label={ __( 'Line height:' ) }
                                            value={ getHeadingValue( tabName, 'lh', meta ) }
                                            min={ 1 }
                                            max={ 3 }
                                            step={ 0.05 }
                                            onChange={ value => updateEl( tabName, 'lh', value, true ) }
                                        />

                                        { /* /// Headings: Letter spacing /// */ }
                                        <RangeControl 
                                            label={ __( 'Letter spacing:' ) }
                                            value={ getHeadingValue( tabName, 'ls', meta ) }
                                            min={ 0 }
                                            max={ 3 }
                                            step={ 0.01 }
                                            onChange={ value => updateEl( tabName, 'ls', value, true ) }
                                        />

                                        { /* /// Headings: Word spacing /// */ }
                                        <RangeControl 
                                            label={ __( 'Word spacing:' ) }
                                            value={ getHeadingValue( tabName, 'ws', meta ) }
                                            min={ 0 }
                                            max={ 3 }
                                            step={ 0.01 }
                                            onChange={ value => updateEl( tabName, 'ws', value, true ) }
                                        />

                                    </Fragment>
                                ) }
                            }
                        </TabPanel>

                    </PanelBody>
  
                    { /* /////////// Body Panel /////////// */}
                    <PanelBody title={ __( 'Body' ) } initialOpen={ false }>
                        
                        { /* /// Body: Font family /// */ }
                        <SelectControl 
                            label={ __( 'Body font family' ) }
                            value={ meta.sgf_ff_body }
                            options={ this.fontsOptions() }
                            onChange={ value => updateEl( 'body', 'ff', value ) }
                        />
                        
                        { /* /// Body: Font weight /// */ }
                        <SelectControl 
                            label={ __( 'Font weight:' ) }
                            value={ meta.sgf_wt_body }
                            options={ this.weightsOptions( meta.sgf_ff_body ) }
                            onChange={ value => updateEl( 'body', 'wt', value ) }
                        />

                        { /* /// Body: Line height /// */ }
                        <RangeControl 
                            label={ __( 'Line height:' ) }
                            value={ meta.sgf_lh_body }
                            min={ 1 }
                            max={ 3 }
                            step={ 0.05 }
                            onChange={ value => updateEl( 'body', 'lh', value ) }
                        />

                        { /* /// Body: Letter spacing /// */ }
                        <RangeControl 
                            label={ __( 'Letter spacing:' ) }
                            value={ meta.sgf_ls_body }
                            min={ 0 }
                            max={ 3 }
                            step={ 0.01 }
                            onChange={ value => updateEl( 'body', 'ls', value ) }
                        />

                        { /* /// Body: Word spacing /// */ }
                        <RangeControl 
                            label={ __( 'Word spacing:' ) }
                            value={ meta.sgf_ws_body }
                            min={ 0 }
                            max={ 3 }
                            step={ 0.01 }
                            onChange={ value => updateEl( 'body', 'ws', value ) }
                        />
                        
                    </PanelBody>

                    { /* /////////// Global Options Panel /////////// */}
                    <PanelBody title={ __( 'Global Options' ) } initialOpen={ false }>
                        
                        <SGFGlobalOption { ...this.props } updateEl={ this.updateElement } />

                    </PanelBody>

                    { /* /////////// Information Panel /////////// */}
                    <PanelBody title={ __( 'Information' ) } initialOpen={ false }>
                        
                        <SGFInfoPanel />

                    </PanelBody>

	            </PluginSidebar>

                <PluginSidebarMoreMenuItem
                    target={ PLUGIN_INFO.sidebarId }
                    icon={ PLUGIN_INFO.sidebarIcon }
                >
                    { PLUGIN_INFO.pluginName }
                </PluginSidebarMoreMenuItem>

            </Fragment>
        );
    }
}

export default compose( [

    withSelect( select => {
        const editor      = select( 'core/editor' );
        const postMeta    = addDefaultsToMeta( editor.getEditedPostAttribute( 'meta' ) );
        const oldPostMeta = editor.getCurrentPostAttribute( 'meta' );
        const isPublished = editor.isCurrentPostPublished();
        const pageID      = editor.getCurrentPostId();
        
        let info = {
            meta    : { ...oldPostMeta, ...postMeta },
            oldmeta : oldPostMeta,
            info    : { pageID, isPublished }
        };

        info.meta = addGlobalHeadings( info.meta );

        return info;
    } ),
    
    withDispatch( dispatch => ( {

        /**
         * Updates meta for headings, `single` is set to `false` in `register_meta()` and this 
         * function will make sure it updates the correct string based on `el`
         *
         * @since  1.0.0
         * @todo   Maybe use state for headings defaults 
         * @param  {Object} elements An object with some info `{ el, prop, value }`
         * @param  {Object} newmeta  Updated/new meta
         * @param  {Object} oldmeta  Previous meta
         * @return {Void}
         */
        updateHeadingsMeta( elements, newmeta ) {
            const { el, prop, value } = elements;

            const stringified = stringifyHeadingValues;
            const parsed      = parseHeadingValues;

            const defaults = simpleGFonts.headings[ el ];
            const hdskey   = 'sgf_els_headings';
            const headings = newmeta[ hdskey ];

            let meta = {
                ...newmeta
            };

            if( headings.length ) {
                const found = headings.findIndex( item => item.startsWith( el, 3 ) );
                
                meta[ hdskey ] = [ ...headings ];

                if( found === -1 ) {
                    defaults[ prop ] = value;

                    meta[ hdskey ].push( 
                        stringified( defaults )
                    );
                } else {
                    let converted = parsed( headings[ found ] );
                    
                    converted[ prop ] = value;

                    meta[ hdskey ][ found ] = stringified( converted );
                }
            } else {
                meta[ hdskey ] = [];

                defaults[ prop ] = value;

                meta[ hdskey ].push( 
                    stringified( defaults ) 
                );
            }

            dispatch( 'core/editor' ).editPost( { meta } );
        },

        /**
         * Updates meta for single components, `single` is set to `true` in `register_meta()` 
         *
         * @since  1.0.0
         * @param  {Object} elements An object with some info `{ panel, type, value }`
         * @param  {Object} newmeta  Updated/new meta
         * @param  {Object} oldmeta  Previous meta
         * @return {Void}
         */
        updateSingleMeta( elements, newmeta ) {
            let { panel, type, value } = elements;
            const defaultWt = '400';

            if( type === 'ff' ){
                value = Number( value ); 
            }

            let meta = {
                ...newmeta,
                [ `sgf_${type}_${panel}` ]: value
            };

            if( panel === 'global' && type === 'is' ) {
                meta.sgf_date_global = meta.sgf_is_global ? Date.now().toString() : '';
            }

            if( panel === 'body' && type === 'ff' ) {
                meta.sgf_wt_body = defaultWt
            }

            if( panel === 'headings' && type === 'ff' ) {
                const { sgf_els_headings:headings } = meta;

                if( headings.length ) {
                    let newHeadings = [];

                    for( const heading of headings ) {
                        const converted = parseHeadingValues( heading );

                        newHeadings.push( stringifyHeadingValues( converted, 'wt', defaultWt ) );
                    }

                    meta.sgf_els_headings = newHeadings;
                }
            }
            
            dispatch( 'core/editor' ).editPost( { meta } );
        }

    } ) )
] )( SimpleGoogleFonts ); 