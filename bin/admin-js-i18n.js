/**
 * Admin React apps — JS translation build step.
 *
 * Runs after `wp i18n make-pot` (see the `makepot` Grunt task) and reads the
 * `js/admin/...` references in src/languages/buddyboss.pot to write two files:
 *
 * 1. node_modules/.cache/bb-admin-js-i18n/map.json
 *    The `--use-map` input for `wp i18n make-json`. Maps every admin React
 *    source file to the built script(s) WordPress actually enqueues, so the
 *    strings of lazy-loaded route chunks land in the entry file's JSON
 *    (WordPress only loads JSON for a handle's own file).
 *
 * 2. src/bp-core/admin/bb-settings/script-translation-strings.php
 *    The per-handle string manifest from which bb_admin_script_translations_fallback()
 *    builds the React admin translations of these handles, always from the
 *    `buddyboss` catalog PHP loaded. The bundled JSON is skipped for these
 *    handles; a JSON file in wp-content/languages is still honoured.
 *
 * @since BuddyBoss [BBVERSION]
 */

const fs = require( 'fs' );
const path = require( 'path' );

const POT_FILE = 'src/languages/buddyboss.pot';
const MAP_FILE = 'node_modules/.cache/bb-admin-js-i18n/map.json';
const MANIFEST_FILE = 'src/bp-core/admin/bb-settings/script-translation-strings.php';

/**
 * Script handle => the built file it enqueues (relative to the packaged plugin
 * root, i.e. src/) and the source folders whose strings it renders.
 *
 * - The RL onboarding bundle is also injected lazily inside the Settings app
 *   (settings/components/appearance/WelcomeBanner.js), so its strings are
 *   listed under bb-admin-settings as well.
 * - js/admin/utils and js/admin/components are shared folders imported by the
 *   app bundles.
 */
const TARGETS = {
	'bb-admin-settings': {
		build: 'bp-core/admin/bb-settings/settings/build/index.js',
		sources: [ 'js/admin/settings/', 'js/admin/rl-onboarding/', 'js/admin/components/', 'js/admin/utils/' ],
	},
	'bb-admin-integrations': {
		build: 'bp-core/admin/bb-settings/integrations/build/index.js',
		sources: [ 'js/admin/integrations/', 'js/admin/utils/' ],
	},
	'bb-admin-common': {
		build: 'bp-core/admin/bb-settings/common/build/index.js',
		sources: [ 'js/admin/common/' ],
	},
	'bb-rl-onboarding-script': {
		build: 'bp-core/admin/bb-settings/rl-onboarding/build/rl-onboarding.js',
		sources: [ 'js/admin/rl-onboarding/', 'js/admin/utils/' ],
	},
};

/**
 * Decode a PO string literal (the part between the quotes).
 *
 * @param {string} value Escaped PO string.
 * @return {string} Decoded string.
 */
function unescapePo( value ) {
	return value.replace( /\\(["\\nt])/g, ( match, chr ) => {
		if ( 'n' === chr ) {
			return '\n';
		}
		if ( 't' === chr ) {
			return '\t';
		}
		return chr;
	} );
}

/**
 * Parse the POT file into entries with their msgctxt / msgid / msgid_plural
 * and source references.
 *
 * @param {string} content POT file contents.
 * @return {Array<Object>} Entries.
 */
function parsePot( content ) {
	const entries = [];
	const blocks = content.split( /\r?\n\r?\n/ );

	blocks.forEach( ( block ) => {
		const entry = { references: [], msgctxt: '', msgid: null, msgid_plural: '' };
		let current = null;

		block.split( /\r?\n/ ).forEach( ( line ) => {
			if ( line.startsWith( '#:' ) ) {
				line.slice( 2 ).trim().split( /\s+/ ).forEach( ( ref ) => {
					entry.references.push( ref.replace( /:\d+$/, '' ) );
				} );
				current = null;
				return;
			}

			const keyword = line.match( /^(msgctxt|msgid_plural|msgid|msgstr(?:\[\d+\])?)\s+"(.*)"$/ );
			if ( keyword ) {
				current = keyword[ 1 ].startsWith( 'msgstr' ) ? null : keyword[ 1 ];
				if ( current ) {
					entry[ current ] = unescapePo( keyword[ 2 ] );
				}
				return;
			}

			const continuation = line.match( /^"(.*)"$/ );
			if ( continuation && current ) {
				entry[ current ] += unescapePo( continuation[ 1 ] );
			}
		} );

		// Skip the header (empty msgid) and comment-only blocks.
		if ( entry.msgid ) {
			entries.push( entry );
		}
	} );

	return entries;
}

/**
 * Export a string as a single-quoted PHP literal.
 *
 * @param {string} value String.
 * @return {string} PHP literal.
 */
function phpString( value ) {
	return "'" + value.replace( /\\/g, '\\\\' ).replace( /'/g, "\\'" ) + "'";
}

const entries = parsePot( fs.readFileSync( POT_FILE, 'utf8' ) );
const map = {};
const manifest = {};

Object.keys( TARGETS ).forEach( ( handle ) => {
	manifest[ handle ] = new Map();
} );

entries.forEach( ( entry ) => {
	entry.references.forEach( ( file ) => {
		Object.keys( TARGETS ).forEach( ( handle ) => {
			const target = TARGETS[ handle ];

			if ( ! target.sources.some( ( dir ) => file.startsWith( dir ) ) ) {
				return;
			}

			map[ file ] = map[ file ] || [];
			if ( ! map[ file ].includes( target.build ) ) {
				map[ file ].push( target.build );
			}

			const key = ( entry.msgctxt ? entry.msgctxt + '\u0004' : '' ) + entry.msgid;
			manifest[ handle ].set( key, [ entry.msgid, entry.msgctxt, entry.msgid_plural ] );
		} );
	} );
} );

fs.mkdirSync( path.dirname( MAP_FILE ), { recursive: true } );
fs.writeFileSync( MAP_FILE, JSON.stringify( map, null, '\t' ) + '\n' );

let php = '<?php\n';
php += '/**\n';
php += ' * Strings rendered by the admin React apps, per script handle.\n';
php += ' *\n';
php += ' * GENERATED by bin/admin-js-i18n.js during `grunt makepot` — do not edit.\n';
php += ' * Each item is array( singular, context, plural ).\n';
php += ' *\n';
php += ' * @package BuddyBoss\\Core\\Administration\n';
php += ' */\n\n';
php += "defined( 'ABSPATH' ) || exit;\n\n";
php += 'return array(\n';

Object.keys( manifest ).forEach( ( handle ) => {
	php += '\t' + phpString( handle ) + ' => array(\n';
	manifest[ handle ].forEach( ( item ) => {
		php += '\t\tarray( ' + item.map( phpString ).join( ', ' ) + ' ),\n';
	} );
	php += '\t),\n';
} );

php += ');\n';

fs.writeFileSync( MANIFEST_FILE, php );

Object.keys( manifest ).forEach( ( handle ) => {
	console.log( `${ handle }: ${ manifest[ handle ].size } strings` );
} );
console.log( `Map: ${ Object.keys( map ).length } source files → ${ MAP_FILE }` );
