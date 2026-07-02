/* jshint node:true */
/**
 * Pair detection helper for the unminified-asset debug-fetch pipeline.
 *
 * Used by Gruntfile.js tasks:
 *   - generate_debug_manifest  (writes unminified-manifest.json into BUILD_DIR)
 *   - compress:main exclusions (drops unminified-with-pair files from the zip)
 *
 * A "pair file" is a first-party asset where BOTH `<name>.<ext>` and
 * `<name>.min.<ext>` ship from this repo, for ext in {js, css}. Vendor and
 * generated apidoc trees are excluded because:
 *   - vendor/ files come from Composer / external libs and aren't republished
 *     on the production branch under our control.
 *   - endpoints/ files are apidoc browser output, not enqueued as WP assets.
 *   - node_modules/ is a build-only directory and never ships.
 *
 * Single source of truth: any caller that needs "the set of paired assets"
 * should go through findPairFiles(), so the Grunt zip exclusion and the
 * runtime manifest can never disagree.
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs     = require( 'fs' );
var path   = require( 'path' );
var crypto = require( 'crypto' );

/**
 * Top-level directory names that disqualify a file from the pair set.
 *
 * Match is anchored to the FIRST segment of the relative path only — so
 * the Composer `vendor/` at the plugin root is excluded, but bundled
 * third-party trees like `bp-core/css/vendor/` and `bp-core/js/vendor/`
 * ARE included (they ship the same `.css` / `.min.css` pattern as our
 * own assets and benefit from the same strip-and-fetch treatment).
 *
 * Same anchoring rationale for `node_modules/` (dev-only, plugin-root
 * only) and `endpoints/` (apidoc generator output at plugin root).
 */
var EXCLUDED_TOP_LEVEL = [ 'vendor', 'node_modules', 'endpoints' ];

/**
 * Asset extensions that are offloaded to S3 and stripped from the zip
 * (images + woff2 fonts). Kept in lockstep with BB_S3_Image_Offload::EXTENSIONS
 * and the Gruntfile S3_OFFLOAD_STRIP_GLOBS. Listed in the debug manifest so the
 * runtime fetcher can restore them into the plugin under SCRIPT_DEBUG.
 */
var OFFLOAD_EXTENSIONS = [ 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'woff2' ];

/**
 * Top-level dirs excluded from the offloaded-asset set. Unlike the JS/CSS pair
 * set we DON'T exclude `endpoints/` here — its glyphicons font/images are
 * offloaded to S3 and stripped, so they must be restorable too. `vendor/` and
 * `node_modules/` carry no first-party offloaded assets and never ship.
 */
var OFFLOAD_EXCLUDED_TOP_LEVEL = [ 'vendor', 'node_modules' ];

/**
 * Paths that must NOT be offloaded and therefore ship in the zip. Excluded here
 * so the debug manifest reflects what is actually stripped. Two classes:
 *
 *   1. Webpack-emitted bundle image dirs (admin React apps live under
 *      `.../build/images/`). Their URLs are assembled inside the JS bundle from
 *      webpack's runtime public path — a reference neither the PHP HTML rewriter
 *      nor the CSS `url()` rewriter can ever reach — so they cannot be served
 *      from S3.
 *   2. Images read as LOCAL files by server-side PHP/GD (bp-core/images/blank.png,
 *      bp-core/images/suspended-mystery-man.jpg). Never emitted as a URL, so they
 *      cannot come from S3 and must ship locally.
 *
 * Kept in lockstep with the Gruntfile S3_OFFLOAD_KEEP_GLOBS.
 *
 * @since BuddyBoss [BBVERSION]
 */
var OFFLOAD_KEPT_PATH_RE = /(^|\/)build\/images\/|(^|\/)bp-core\/images\/(?:blank\.png|suspended-mystery-man\.jpg)$/;

/**
 * Recursively walk a directory and return every regular file's path,
 * relative to the starting directory. Symlinks are not followed (to
 * avoid loops in misconfigured build trees).
 *
 * @param {string} rootDir Absolute path to walk.
 * @returns {string[]} Relative file paths, using forward slashes.
 */
function walkFiles( rootDir ) {
	var results = [];

	function recurse( current ) {
		var entries;
		try {
			entries = fs.readdirSync( current, { withFileTypes: true } );
		} catch ( e ) {
			// Unreadable directory — skip silently. We don't want a transient
			// permission glitch on one subdir to nuke a release build.
			return;
		}

		for ( var i = 0; i < entries.length; i++ ) {
			var entry    = entries[ i ];
			var fullPath = path.join( current, entry.name );

			if ( entry.isSymbolicLink() ) {
				continue;
			}
			if ( entry.isDirectory() ) {
				recurse( fullPath );
			} else if ( entry.isFile() ) {
				results.push( fullPath );
			}
		}
	}

	recurse( rootDir );
	return results;
}

/**
 * Test whether a relative path lives under one of the excluded top-level
 * directories. Only the FIRST segment is consulted — nested directories
 * named "vendor" (e.g. `bp-core/css/vendor/`) are intentionally not
 * excluded; they ship third-party assets that benefit from the same
 * minified-only treatment as first-party code.
 *
 * @param {string} relPath Forward-slash relative path.
 * @returns {boolean}
 */
function isExcluded( relPath ) {
	var firstSlash = relPath.indexOf( '/' );
	var topSegment = firstSlash === -1 ? relPath : relPath.substring( 0, firstSlash );
	return EXCLUDED_TOP_LEVEL.indexOf( topSegment ) !== -1;
}

/**
 * Find every paired asset under rootDir.
 *
 * A pair is recorded when BOTH `<base>.<ext>` and `<base>.min.<ext>` exist
 * as regular files on disk under rootDir, ext in {js, css}, neither path
 * crosses an excluded segment.
 *
 * Returned shape, per pair:
 *   {
 *     ext:        'js' | 'css',
 *     relMin:     'bp-templates/.../buddypress.min.css',
 *     relUnmin:   'bp-templates/.../buddypress.css',
 *     absMin:     '/abs/path/to/buddypress.min.css',
 *     absUnmin:   '/abs/path/to/buddypress.css'
 *   }
 *
 * Paths in the `rel*` fields use forward slashes regardless of host OS,
 * matching the form used in grunt globs and the runtime manifest.
 *
 * @param {string} rootDir Absolute path to walk.
 * @returns {Array}
 */
function findPairFiles( rootDir ) {
	var absRoot = path.resolve( rootDir );

	if ( ! fs.existsSync( absRoot ) ) {
		throw new Error( 'findPairFiles: directory does not exist: ' + absRoot );
	}

	var allFiles = walkFiles( absRoot );
	var fileSet  = Object.create( null );

	for ( var i = 0; i < allFiles.length; i++ ) {
		var rel = path.relative( absRoot, allFiles[ i ] ).split( path.sep ).join( '/' );
		fileSet[ rel ] = allFiles[ i ];
	}

	var pairs = [];
	var seen  = Object.create( null );

	for ( var rel in fileSet ) {
		if ( ! Object.prototype.hasOwnProperty.call( fileSet, rel ) ) {
			continue;
		}

		var minMatch = rel.match( /^(.+)\.min\.(js|css)$/ );
		if ( ! minMatch ) {
			continue;
		}

		var base = minMatch[ 1 ];
		var ext  = minMatch[ 2 ];
		var unminRel = base + '.' + ext;

		if ( ! fileSet[ unminRel ] ) {
			continue;
		}
		if ( isExcluded( rel ) || isExcluded( unminRel ) ) {
			continue;
		}
		if ( seen[ unminRel ] ) {
			continue;
		}
		seen[ unminRel ] = true;

		pairs.push( {
			ext:      ext,
			relMin:   rel,
			relUnmin: unminRel,
			absMin:   fileSet[ rel ],
			absUnmin: fileSet[ unminRel ]
		} );
	}

	pairs.sort( function ( a, b ) {
		return a.relUnmin < b.relUnmin ? -1 : ( a.relUnmin > b.relUnmin ? 1 : 0 );
	} );

	return pairs;
}

/**
 * Find every offloaded asset (image or woff2 font) under rootDir.
 *
 * These are stripped from the zip and served from S3 in production, but the
 * runtime fetcher restores them into the plugin directory under SCRIPT_DEBUG so
 * the install matches a dev checkout. Returned shape, per asset:
 *   { rel: 'bp-core/images/foo.png', abs: '/abs/path/to/foo.png' }
 *
 * @param {string} rootDir Absolute path to walk.
 * @returns {Array}
 */
function findOffloadedAssets( rootDir ) {
	var absRoot = path.resolve( rootDir );

	if ( ! fs.existsSync( absRoot ) ) {
		throw new Error( 'findOffloadedAssets: directory does not exist: ' + absRoot );
	}

	var assets = [];
	var allFiles = walkFiles( absRoot );

	for ( var i = 0; i < allFiles.length; i++ ) {
		var rel = path.relative( absRoot, allFiles[ i ] ).split( path.sep ).join( '/' );

		var firstSlash = rel.indexOf( '/' );
		var topSegment = firstSlash === -1 ? rel : rel.substring( 0, firstSlash );
		if ( OFFLOAD_EXCLUDED_TOP_LEVEL.indexOf( topSegment ) !== -1 ) {
			continue;
		}

		// Webpack bundle images ship in the zip (not offloaded) — skip them so
		// the manifest matches what is actually stripped.
		if ( OFFLOAD_KEPT_PATH_RE.test( rel ) ) {
			continue;
		}

		var dot = rel.lastIndexOf( '.' );
		if ( dot === -1 ) {
			continue;
		}
		var ext = rel.substring( dot + 1 ).toLowerCase();
		if ( OFFLOAD_EXTENSIONS.indexOf( ext ) === -1 ) {
			continue;
		}

		assets.push( { rel: rel, abs: allFiles[ i ] } );
	}

	assets.sort( function ( a, b ) {
		return a.rel < b.rel ? -1 : ( a.rel > b.rel ? 1 : 0 );
	} );

	return assets;
}

/**
 * Compute the SHA-256 of a file, returned as 'sha256:<hex>'.
 *
 * The prefix is intentional — the runtime manifest stores it verbatim so
 * the PHP verifier can detect future algorithm upgrades without ambiguity.
 *
 * @param {string} filePath Absolute path to a readable file.
 * @returns {string}
 */
function computeSha256( filePath ) {
	var hash = crypto.createHash( 'sha256' );
	var data = fs.readFileSync( filePath );
	hash.update( data );
	return 'sha256:' + hash.digest( 'hex' );
}

module.exports = {
	findPairFiles:       findPairFiles,
	findOffloadedAssets: findOffloadedAssets,
	computeSha256:       computeSha256,
	EXCLUDED_TOP_LEVEL:  EXCLUDED_TOP_LEVEL,
	OFFLOAD_EXTENSIONS:  OFFLOAD_EXTENSIONS
};

/**
 * CLI usage — `node bin/debug-assets-pair-finder.js <dir>` prints a JSON
 * dump of the pair set. Handy for spot-checking what would ship vs. not.
 */
if ( require.main === module ) {
	var target = process.argv[ 2 ];
	if ( ! target ) {
		process.stderr.write( 'usage: node debug-assets-pair-finder.js <build-dir>\n' );
		process.exit( 1 );
	}
	var pairs = findPairFiles( target );
	process.stdout.write( JSON.stringify( pairs.map( function ( p ) {
		return { relUnmin: p.relUnmin, relMin: p.relMin };
	} ), null, 2 ) + '\n' );
	process.stderr.write( 'pair count: ' + pairs.length + '\n' );
}
