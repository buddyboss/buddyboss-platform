/* jshint node:true */
/**
 * Generate the derived CSS files for an admin build target.
 *
 * The sass step of each `build:admin:<target>` npm script emits only the
 * compressed base stylesheet (e.g. `admin.css`). The runtime enqueue code
 * (`bb-admin-settings-page.php`, `class-bb-readylaunch-onboarding.php`)
 * always loads the `.min.css` file — the shipped zip strips the unminified
 * pair — and RTL sites load the `-rtl` variants. Without this step a fresh
 * webpack/sass rebuild silently deletes those derived files and the admin
 * screens load with no stylesheet at all (PROD-9826 Issue 1).
 *
 * Produces, next to the given base file:
 *   <base>.min.css      — copy of the (already compressed) base CSS
 *   <base>-rtl.css      — rtlcss transform of the base CSS
 *   <base>-rtl.min.css  — copy of the RTL CSS
 *
 * Usage: node bin/build-admin-css-derivatives.js <path/to/base.css>
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs     = require( 'fs' );
var path   = require( 'path' );
var rtlcss = require( 'rtlcss' );

var basePath = process.argv[ 2 ];

if ( ! basePath ) {
	process.stderr.write( 'usage: node bin/build-admin-css-derivatives.js <path/to/base.css>\n' );
	process.exit( 1 );
}

if ( ! fs.existsSync( basePath ) || ! /\.css$/.test( basePath ) || /\.min\.css$|-rtl\.css$/.test( basePath ) ) {
	process.stderr.write( '[css-derivatives] expected an existing base .css file, got: ' + basePath + '\n' );
	process.exit( 1 );
}

var dir  = path.dirname( basePath );
var stem = path.basename( basePath, '.css' );

var css    = fs.readFileSync( basePath, 'utf8' );
var rtlCss = rtlcss.process( css );

var outputs = {};
outputs[ path.join( dir, stem + '.min.css' ) ]     = css;
outputs[ path.join( dir, stem + '-rtl.css' ) ]     = rtlCss;
outputs[ path.join( dir, stem + '-rtl.min.css' ) ] = rtlCss;

Object.keys( outputs ).forEach( function ( outPath ) {
	fs.writeFileSync( outPath, outputs[ outPath ] );
	process.stdout.write( '[css-derivatives] wrote ' + outPath + '\n' );
} );
