import { 
    HEADINGS
} from './constants';

const { isEmpty } = lodash;

/**
 * Capitalizes the words in a string.
 *
 * @since   1.0.0
 * @param   {String} words
 * @returns {String}
 */
export const capitalizeWords = ( words ) => {
    return words.replace( /\b\w/g, l => l.toUpperCase() );
}

/**
 * Makes sure the new <link/> url is encoded correctly
 *
 * @since   1.0.0
 * @param   {DOMNode} link      <link/> element
 * @param   {Array}   items     Old and current decoded elements
 * @param   {boolean} [ff=true] Should this be used for fonts or weights
 * @returns {String}            New <link/> URL
 */
export const newStyleLinkURL = ( items, link, ff = true ) => {
    const [ old, current ] = items.map( item => {
        let newItem = encodeURIComponent( item );
        return ff ? newItem.replace( '%2B', '+' ) : newItem;
    } );

    return link.href.replace( old, current );
}
/**
 * Loop through the old meta and check if it has changed
 *
 * @since   1.0.0
 * @param   {Object} old     Old data
 * @param   {Object} current Current data
 * @returns {Object}         Reviewed data
 */
export const reviewedData = ( old, current ) => {
    return Object.keys( current ).reduce( ( prev, key ) => {
        if ( old[ key ] === current[ key ] ) {
            return prev;
        }

        return {
            ...prev,
            [ key ]: current[ key ],
        };
    }, {} );
}

/**
 * Parses the a heading's meta values.
 * 
 * @since   1.0.0
 * @param   {String} heading Meta value in this format `el:h1|wt:400|tt:none|lh:1.4|ls:0`
 * @returns {Object}         Parsed keys and values in an object
 */
export const parseHeadingValues = heading => {
    let parsed = {}
    
    for( const item of heading.split( '|' ) ) {
        let [ el, value ] = item.split( ':' );

        switch( el ) {
            case 'lh':
            case 'ls':
            case 'ws':
                value = Number( value );
                break;
        
            default:
                break;
        }

        parsed[ el ] = value;
    }

    return parsed;
}

/**
 * Undos `parseHeadingValues()`, converts it back to a string to be saved as meta.
 * It also changes a value for a selected key 
 *
 * @since   1.0.0
 * @param   {Object} parsed Parsed meta value, from string to object
 * @param   {String} prop   Propriety, key in object
 * @param   {Mixed}  value  New value for the selected propriety
 * @returns {String}        Converts the new obeject into a string, `el:h1|wt:400|tt:none|lh:1.4|ls:0`
 */
export const stringifyHeadingValues = ( parsed, prop, value ) => {
    return Object.keys( parsed ).map( item => { 
        if( item === prop ) {
            return [ item, value ].join( ':' )
        }

        return [ item, parsed[ item ] ].join( ':' ) 
    } ).join( '|' );
}

/**
 * Gets a value for a specific heading from post meta
 *
 * @since   1.0.0
 * @param   {String} el   The heading, `h2` for example
 * @param   {String} prop The propriety to select, for example `wt` for weight
 * @param   {Object} meta Current post meta information
 * @returns {Mixed}       Meta value for the selected heading propriety or returns the default value for it
 */
export const getHeadingValue = ( el, prop, meta ) => {
    const { headings:hdef } = simpleGFonts;
    
    const defaults = hdef[ el ];
    
    const headings = meta.sgf_els_headings;

    if( headings.length ) {
        const found = headings.findIndex( item => item.startsWith( el, 3 ) );

        if( found === -1 ) {
            return defaults[ prop ]
        } else {
            const converted = parseHeadingValues( headings[ found ] );
            
            return converted[ prop ]; 
        }
    } else {
        return defaults[ prop ];
    }
}

/**
 * Creates 3 DOMNodes, 2 for `<link>`s and one `<style>` tag used to enqueue Google fonts and
 * inline styles. 
 *
 * @since   1.0.0
 * @returns {Void}
 */
export const createStyleNodes = () => {
    const similar = {
        type  : 'text/css',
        media : 'all',
        rel   : 'stylesheet',
        href  : simpleGFonts.default_url,
    };

    const elements = [
        {
            tag: 'link',
            id: simpleGFonts.body_id,
            ...similar 
        },
        {
            tag: 'link',
            id: simpleGFonts.headings_id,
            ...similar
        },
        {
            tag: 'style',
            id: simpleGFonts.style_id,
            type: 'text/css'
        }
    ];

    for( const element of elements ) {
        const elNode = document.createElement( element.tag );
        const elKeys = Object.keys( element );

        for( const elKey of elKeys ) {
            if( elKey === 'tag' ) continue;

            elNode.setAttribute( elKey, element[ elKey ] );
        }

        document.head.appendChild( elNode );
    }
}

/**
 * Makes sure to add globals if no heading styles are not set. Also solves an issue with
 * REST API not displaying defaults if `$single` is set to `false` in `register_meta()` 
 *
 * @since   1.0.0
 * @param   {Object} meta 
 * @returns {Void}
 */
export const addGlobalHeadings = meta => {
    const { global_vals:gvals } = simpleGFonts;

    if( gvals ) {
        const { sgf_els_headings:gheadings } = gvals;

        if( gheadings && gheadings.length ) {
            meta.sgf_els_headings = [
                ...gheadings
            ];
        }
    }

    return meta;
}

/**
 * Adds defaults to meta if values don't exist.
 *
 * @todo    Clean-up, make it more readable
 * @since   1.0.2
 * @param   {Object} meta Meta without defaults
 * @returns {Object}      Meta with defaults
 */
export const addDefaultsToMeta = meta => {
    const { defaults, headings } = simpleGFonts;

    const parsed      = parseHeadingValues;
    const stringified = stringifyHeadingValues;
        
    let newDefaults     = {}; 
    let newHeadingsDefs = [];

    for( const headingType of Object.keys( headings ) ) {
        newHeadingsDefs.push( stringified( headings[ headingType ] ) );
    }
    
    for( const panelType of Object.keys( defaults ) ) {
        for( const option of Object.keys( defaults[ panelType ] ) ) {
            newDefaults[ `sgf_${option}_${panelType}` ] = defaults[ panelType ][ option ];
        }
    }
    
    meta = {
        ...newDefaults,
        ...meta
    }

    for( const heading of HEADINGS ) {
        const hObj = meta.sgf_els_headings.find( hEl => {
            const hElparse = parsed( hEl );

            if( isEmpty( hElparse ) ) return false;

            return hElparse.el === heading;
        } );
        
        if( hObj ) {
            const hObjIndex = meta.sgf_els_headings.findIndex( hOel => 
                parsed( hOel ).el === heading   
            );

            meta.sgf_els_headings[ hObjIndex ] = stringified( {
                ...parsed( newHeadingsDefs.find( nHdef => 
                    parsed( nHdef ).el === heading 
                ) ),
                ...parsed( hObj )
            } );
        } else {
            const defHeading = newHeadingsDefs.find( nHdef => {
                return parsed( nHdef ).el === heading;
            } );

            const exists = meta.sgf_els_headings.findIndex( hOel => 
                parsed( hOel ).el ===  parsed( defHeading ).el
            );

            if( defHeading && exists === -1 ) {
                meta.sgf_els_headings.push( defHeading );
            }
        }
    }

    return meta;
}