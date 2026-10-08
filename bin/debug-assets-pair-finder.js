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
	findPairFiles:      findPairFiles,
	computeSha256:      computeSha256,
	EXCLUDED_TOP_LEVEL: EXCLUDED_TOP_LEVEL
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
