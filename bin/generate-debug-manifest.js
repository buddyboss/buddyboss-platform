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
 *                            the manifest to the SOURCE checkout's current HEAD
 *                            commit — the exact commit being tested (e.g. your
 *                            feature branch, PROD-9826) — provided that commit
 *                            is already pushed to `origin`. Because a source
 *                            branch keeps files under `src/` (the build flattens
 *                            that away), the fetch base URL gets a `/src` suffix
 *                            so the flattened manifest paths resolve on GitHub.
 *                            This lets the runtime fetcher download-and-restore
 *                            THIS build's originals — including files changed on
 *                            the branch — WITHOUT this build committing or
 *                            pushing anything itself. If HEAD isn't a 40-hex
 *                            commit or isn't on any remote (unpushed / offline),
 *                            the script falls back to the sentinel and the
 *                            fetcher stays dormant (S3 / .min fallback).
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
 * Resolve the commit the runtime fetcher should pin to, plus how its tree is
 * laid out on GitHub.
 *
 *   - Production `build`: BUILD_DIR is a git working tree checked out from the
 *     production branch and committed, so its HEAD is the canonical pushed
 *     commit. The production branch stores FLATTENED files, so the fetch base
 *     is the bare commit (no `/src`).
 *   - `build_test`: BUILD_DIR has no `.git`. Pin to the SOURCE checkout's
 *     current HEAD — the exact commit under test (e.g. a feature branch) — as
 *     long as it is already on a remote (fetchable). A source branch keeps
 *     files under `src/`, so `srcLayout` is set and the caller appends `/src`
 *     to the fetch base; the flattened manifest paths then resolve on GitHub.
 *     If HEAD isn't a real pushed commit, fall back to the sentinel so the
 *     fetcher stays dormant instead of pinning an unfetchable SHA.
 *
 * All git failures are swallowed so the manifest is still written.
 *
 * @param {string} buildDir Staged build directory.
 * @return {{commitSha: string, srcLayout: boolean}} Pin descriptor.
 */
function captureCommitSha( buildDir ) {
	var gitDir = path.join( buildDir, '.git' );
	if ( fs.existsSync( gitDir ) ) {
		var headSha = git( [ '-C', buildDir, 'rev-parse', 'HEAD' ] );
		return {
			commitSha: /^[0-9a-f]{40}$/.test( headSha ) ? headSha : SENTINEL_SHA,
			srcLayout: false
		};
	}

	// build_test: pin the source checkout's current HEAD (the commit being
	// tested). Only do so when the commit is actually published to a remote —
	// otherwise the fetcher would pin a SHA that 404s every file. Refresh the
	// current branch's remote-tracking ref best-effort first so the
	// contains-check sees a freshly-pushed commit.
	var srcSha = git( [ 'rev-parse', 'HEAD' ] );
	if ( ! /^[0-9a-f]{40}$/.test( srcSha ) ) {
		return { commitSha: SENTINEL_SHA, srcLayout: false };
	}

	var branch = git( [ 'rev-parse', '--abbrev-ref', 'HEAD' ] );
	if ( branch && 'HEAD' !== branch ) {
		git( [ 'fetch', '--quiet', 'origin', branch ] );
	}

	// `git branch -r --contains <sha>` lists remote branches holding the commit;
	// empty output means it isn't pushed anywhere the fetcher could reach.
	var onRemote = git( [ 'branch', '-r', '--contains', srcSha ] );
	if ( '' === onRemote ) {
		return { commitSha: SENTINEL_SHA, srcLayout: false };
	}

	return { commitSha: srcSha, srcLayout: true };
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

	var pairs   = pairFinder.findPairFiles( buildDir );
	var assets  = pairFinder.findOffloadedAssets( buildDir );
	var pin     = captureCommitSha( buildDir );
	var commitSha = pin.commitSha;

	// Fetch base URL: bare commit for the flattened production branch, or the
	// commit + `/src` for a source-branch build_test pin so the flattened
	// manifest paths resolve against the repo's `src/` tree on GitHub.
	var fetchBaseUrl = '';
	if ( SENTINEL_SHA !== commitSha ) {
		fetchBaseUrl = REPO_URL_BASE + '/' + commitSha + ( pin.srcLayout ? '/src' : '' );
	}

	// The manifest lists every file stripped from the zip that the runtime
	// fetcher restores into the plugin under SCRIPT_DEBUG:
	//   - unminified JS/CSS pairs (key = unminified rel path)
	//   - offloaded images + woff2 fonts (key = asset rel path)
	// All are fetched from the pinned commit and restored to their natural
	// plugin paths so a debug install matches a dev checkout.
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
		fetch_base_url: fetchBaseUrl,
		generated_at:   new Date().toISOString(),
		files:          files
	};

	var manifestPath = path.join( buildDir, MANIFEST_NAME );
	fs.writeFileSync( manifestPath, JSON.stringify( manifest, null, 2 ) + '\n' );

	process.stdout.write(
		'[generate-debug-manifest] wrote ' + manifestPath + '\n' +
		'  plugin_version: ' + pluginVersion + '\n' +
		'  commit_sha:     ' + commitSha + '\n' +
		'  fetch_base_url: ' + ( fetchBaseUrl || '(none — sentinel)' ) + '\n' +
		'  pair count:     ' + pairs.length + '\n' +
		'  asset count:    ' + assets.length + '\n' +
		'  total files:    ' + Object.keys( files ).length + '\n'
	);
}

main();
