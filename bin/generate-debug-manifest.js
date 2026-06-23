#!/usr/bin/env node
/* jshint node:true */
/**
 * Generate `unminified-manifest.json` inside the staged build directory.
 *
 * Consumed at runtime by BB_Debug_Asset_Fetcher to download the unminified
 * counterpart of every `.min.{js,css}` we stripped from the shipped zip,
 * verifying each fetched blob against the SHA-256 captured here.
 *
 * Two callers:
 *   - `grunt build`       — runs AFTER `commit_build_to_mothership_release`
 *                            has pushed BUILD_DIR to the `production` branch.
 *                            At that point BUILD_DIR/.git exists and HEAD
 *                            points at the freshly-pushed commit, so the
 *                            captured SHA is the canonical reference the
 *                            runtime fetcher should pin to.
 *   - `grunt build_test`  — runs without a git push. The script falls back
 *                            to a sentinel value so the manifest is still
 *                            generated (useful for local QA), but the
 *                            runtime fetcher refuses to act on a non-real
 *                            commit SHA — local test builds gracefully
 *                            degrade to .min loading.
 *
 * The manifest is written ONLY into BUILD_DIR (i.e. it ships inside the
 * customer zip). It is intentionally NOT included in the `production`
 * branch commit — that branch always carries the unminified files for
 * the latest release, not whatever version a given customer is running.
 *
 * Usage:
 *   node bin/generate-debug-manifest.js <build-dir> <plugin-version>
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs           = require( 'fs' );
var path         = require( 'path' );
var childProcess = require( 'child_process' );
var pairFinder   = require( './debug-assets-pair-finder.js' );

var MANIFEST_NAME = 'unminified-manifest.json';
var SCHEMA        = 1;
var SENTINEL_SHA  = 'LOCAL_TEST_BUILD';
var REPO_URL_BASE = 'https://raw.githubusercontent.com/buddyboss/buddyboss-platform';

function fail( msg ) {
	process.stderr.write( '[generate-debug-manifest] ' + msg + '\n' );
	process.exit( 1 );
}

/**
 * Return the commit SHA at BUILD_DIR/.git HEAD, or the sentinel if BUILD_DIR
 * isn't a git working tree.
 *
 * We intentionally swallow git errors instead of failing the whole task —
 * the local `build_test` flow doesn't init a git repo inside BUILD_DIR, and
 * still wants a manifest written so the zip layout matches production.
 */
function captureCommitSha( buildDir ) {
	var gitDir = path.join( buildDir, '.git' );
	if ( ! fs.existsSync( gitDir ) ) {
		return SENTINEL_SHA;
	}
	try {
		var out = childProcess.execFileSync(
			'git',
			[ '-C', buildDir, 'rev-parse', 'HEAD' ],
			{ encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ] }
		);
		var sha = out.trim();
		if ( /^[0-9a-f]{40}$/.test( sha ) ) {
			return sha;
		}
		return SENTINEL_SHA;
	} catch ( e ) {
		return SENTINEL_SHA;
	}
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

	var pairs     = pairFinder.findPairFiles( buildDir );
	var assets    = pairFinder.findOffloadedAssets( buildDir );
	var commitSha = captureCommitSha( buildDir );

	// The manifest lists every file stripped from the zip that the runtime
	// fetcher restores into the plugin under SCRIPT_DEBUG:
	//   - unminified JS/CSS pairs (key = unminified rel path)
	//   - offloaded images + woff2 fonts (key = asset rel path)
	// All are fetched from the pinned production-branch commit and restored to
	// their natural plugin paths so a debug install matches a dev checkout.
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
		commit_sha:     commitSha,
		fetch_base_url: SENTINEL_SHA === commitSha ? '' : ( REPO_URL_BASE + '/' + commitSha ),
		generated_at:   new Date().toISOString(),
		files:          files
	};

	var manifestPath = path.join( buildDir, MANIFEST_NAME );
	fs.writeFileSync( manifestPath, JSON.stringify( manifest, null, 2 ) + '\n' );

	process.stdout.write(
		'[generate-debug-manifest] wrote ' + manifestPath + '\n' +
		'  plugin_version: ' + pluginVersion + '\n' +
		'  commit_sha:     ' + commitSha + '\n' +
		'  pair count:     ' + pairs.length + '\n' +
		'  asset count:    ' + assets.length + '\n' +
		'  total files:    ' + Object.keys( files ).length + '\n'
	);
}

main();
