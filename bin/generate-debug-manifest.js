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
 *   - `grunt build_test`  — runs without a git push, so BUILD_DIR has no
 *                            `.git`. Instead of the sentinel, the script pins
 *                            the manifest to the current `origin/production`
 *                            HEAD — the last commit `grunt build` published,
 *                            and the only public commit whose flattened layout
 *                            matches the manifest's paths. That lets the runtime
 *                            fetcher download-and-restore the originals from
 *                            GitHub for a test build WITHOUT this build having
 *                            to commit or push anything. Files byte-identical to
 *                            that release (images/fonts that didn't change,
 *                            etc.) restore; files changed in the test build fail
 *                            SHA-256 verification and are left to the .min /
 *                            S3-fallback path. If `origin/production` can't be
 *                            resolved (never fetched, offline, private repo),
 *                            the script falls back to the sentinel and the
 *                            fetcher stays dormant.
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
 * Run a git command and return trimmed stdout, or '' on any failure.
 *
 * Errors are swallowed (not fatal): a missing ref, no network, or a shallow
 * clone should degrade the manifest to the sentinel, never break the build.
 *
 * @param {string[]} gitArgs  Arguments passed to `git`.
 * @param {string}   [cwd]    Working directory for the command.
 * @return {string} Trimmed stdout, or '' when the command fails.
 */
function git( gitArgs, cwd ) {
	try {
		var opts = { encoding: 'utf8', stdio: [ 'ignore', 'pipe', 'pipe' ], timeout: 20000 };
		if ( cwd ) {
			opts.cwd = cwd;
		}
		return childProcess.execFileSync( 'git', gitArgs, opts ).trim();
	} catch ( e ) {
		return '';
	}
}

/**
 * Return the commit SHA the runtime fetcher should pin to.
 *
 *   - Production `build`: BUILD_DIR is a git working tree checked out from the
 *     production branch and committed, so its HEAD is the canonical pushed
 *     commit — pin to that.
 *   - `build_test`: BUILD_DIR has no `.git`. Pin to the source checkout's
 *     current `origin/production` HEAD — the last commit `grunt build`
 *     published, whose flattened layout matches the manifest paths — so the
 *     fetcher can download-and-restore from GitHub without this build pushing
 *     anything. A best-effort `git fetch` refreshes the ref first; if it can't
 *     be resolved at all, fall back to the sentinel (fetcher stays dormant).
 *
 * All git failures are swallowed so the manifest is still written.
 *
 * @param {string} buildDir Staged build directory.
 * @return {string} A 40-char commit SHA, or the SENTINEL_SHA.
 */
function captureCommitSha( buildDir ) {
	var gitDir = path.join( buildDir, '.git' );
	if ( fs.existsSync( gitDir ) ) {
		var headSha = git( [ '-C', buildDir, 'rev-parse', 'HEAD' ] );
		return /^[0-9a-f]{40}$/.test( headSha ) ? headSha : SENTINEL_SHA;
	}

	// build_test: resolve origin/production from the source checkout (the dir
	// this script runs in). Refresh the remote-tracking ref best-effort first so
	// a stale checkout still pins to the latest published production commit.
	git( [ 'fetch', '--quiet', 'origin', 'production' ] );
	var prodSha = git( [ 'rev-parse', 'origin/production' ] );
	return /^[0-9a-f]{40}$/.test( prodSha ) ? prodSha : SENTINEL_SHA;
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
