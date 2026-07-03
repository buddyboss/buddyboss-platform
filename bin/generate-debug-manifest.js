#!/usr/bin/env node
/* jshint node:true */
/**
 * Generate `unminified-manifest.json` inside the staged build directory.
 *
 * The manifest lists every first-party file stripped from the shipped zip —
 * the unminified counterpart of each `.min.{js,css}` pair, plus offloaded
 * images/woff2 — each with its SHA-256. The Grunt build's
 * `configure_compress_exclusions` step reads this list to exclude those files
 * from the customer zip, keeping the download small.
 *
 * Usage:
 *   node bin/generate-debug-manifest.js <build-dir> <plugin-version>
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs         = require( 'fs' );
var path       = require( 'path' );
var pairFinder = require( './debug-assets-pair-finder.js' );

var MANIFEST_NAME = 'unminified-manifest.json';
var SCHEMA        = 1;

function fail( msg ) {
	process.stderr.write( '[generate-debug-manifest] ' + msg + '\n' );
	process.exit( 1 );
}

function main() {
	var buildDir      = process.argv[ 2 ];
	var pluginVersion = process.argv[ 3 ];

	if ( ! buildDir || ! pluginVersion ) {
		fail( 'usage: node bin/generate-debug-manifest.js <build-dir> <plugin-version>' );
	}
	if ( ! fs.existsSync( buildDir ) || ! fs.statSync( buildDir ).isDirectory() ) {
		fail( 'build dir does not exist or is not a directory: ' + buildDir );
	}

	var pairs  = pairFinder.findPairFiles( buildDir );
	var assets = pairFinder.findOffloadedAssets( buildDir );

	// The manifest lists every file stripped from the shipped zip so the Grunt
	// `configure_compress_exclusions` step knows what to exclude:
	//   - unminified JS/CSS pairs (key = unminified rel path)
	//   - offloaded images + woff2 fonts (key = asset rel path)
	var files = Object.create( null );
	for ( var i = 0; i < pairs.length; i++ ) {
		var p = pairs[ i ];
		files[ p.relUnmin ] = pairFinder.computeSha256( p.absUnmin );
	}
	for ( var j = 0; j < assets.length; j++ ) {
		var a = assets[ j ];
		files[ a.rel ] = pairFinder.computeSha256( a.abs );
	}

	var manifest = {
		schema:         SCHEMA,
		plugin_version: pluginVersion,
		generated_at:   new Date().toISOString(),
		files:          files
	};

	var manifestPath = path.join( buildDir, MANIFEST_NAME );
	fs.writeFileSync( manifestPath, JSON.stringify( manifest, null, 2 ) + '\n' );

	process.stdout.write(
		'[generate-debug-manifest] wrote ' + manifestPath + '\n' +
		'  plugin_version: ' + pluginVersion + '\n' +
		'  pair count:     ' + pairs.length + '\n' +
		'  asset count:    ' + assets.length + '\n' +
		'  total files:    ' + Object.keys( files ).length + '\n'
	);
}

main();
