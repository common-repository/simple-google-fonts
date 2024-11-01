/////////////////////////////////////////////////
////////////// Simple Google Fonts //////////////
/////////////////////////////////////////////////

import '../css/src/plugin.scss';
import { default as plugin } from './src';

const { registerPlugin } = wp.plugins;

/** 
 * Register the plugin 
 */
registerPlugin( 'simple-google-fonts', {
	icon   : 'hidden',
	render : plugin
} );