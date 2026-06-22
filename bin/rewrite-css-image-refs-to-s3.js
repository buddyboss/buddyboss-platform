#!/usr/bin/env node
/* jshint node:true, esversion:6 */
/**
 * Rewrite CSS inside a staged build directory so every relative `url(...)`
 * reference to an image file points at the external S3 bucket instead of a
 * (soon-to-be-stripped) local plugin file.
 *
 * The shipped zip drops all image files (they live on S3), but the compiled
 * CSS still carries relative `url(../images/foo.png)` background references.
 * Those are served as static files by the browser — the runtime PHP HTML
 * rewriter ({@see BB_S3_Image_Offload}) can never touch them. So we rewrite
 * them here, at build time, to absolute S3 URLs. Files remain in src/ and on
 * the production branch; this step only mutates the BUILD_DIR copy.
 *
 * Called from the Gruntfile AFTER `copy:files` (CSS + images are staged in
 * BUILD_DIR) and BEFORE `compress` (which excludes the images from the zip).
 *
 * Path mapping: a relative ref is resolved against the CSS file's own
 * directory (the way a browser would), then expressed RELATIVE to BUILD_DIR.
 * Because BUILD_DIR mirrors the plugin source root, that relative path is the
 * source-relative path and maps 1:1 to the S3 object key. The bucket mirrors
 * the shipped (flattened) plugin tree at its root — no `src/` directory, since
 * that exists only in the dev checkout. The caller passes the S3 base
 * (optionally including a key prefix if the bucket nests under a sub-path).
 *
 * Usage:
 *   node bin/rewrite-css-image-refs-to-s3.js <build-dir> <s3-base-url> [ext-csv]
 *
 * Example:
 *   node bin/rewrite-css-image-refs-to-s3.js buddyboss-platform/ \
 *     "https://buddyboss-platform-assets.s3.us-east-1.amazonaws.com/"
 *
 * Design notes:
 *  - Only relative refs that resolve to a real image file UNDER BUILD_DIR are
 *    rewritten. Absolute URLs, protocol-relative, data: URIs, root-anchored
 *    paths, and non-image extensions (fonts: woff/woff2/ttf/eot) are left
 *    untouched — fonts ship locally, only images move to S3.
 *  - Quote style and any `?query` / `#fragment` suffix are preserved.
 *  - Idempotent: a ref already pointing at an absolute URL is skipped, so
 *    re-running is a no-op.
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs   = require( 'fs' );
var path = require( 'path' );

// Images plus woff2 fonts. woff2 lives in CSS @font-face url() refs, which are
// served as static files and can only be redirected by rewriting the CSS here.
// Keep in lockstep with BB_S3_Image_Offload::EXTENSIONS.
var DEFAULT_EXTENSIONS = [ 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp', 'woff2' ];

/**
 * Walk a directory recursively and return every regular file's absolute path.
 * Symlinks are not followed.
 */
function walkFiles( rootDir ) {
	var out = [];
	function recurse( dir ) {
		var entries;
		try {
			entries = fs.readdirSync( dir, { withFileTypes: true } );
		} catch ( e ) {
			return;
		}
		for ( var i = 0; i < entries.length; i++ ) {
			var e = entries[ i ];
			var p = path.join( dir, e.name );
			if ( e.isSymbolicLink() ) { continue; }
			if ( e.isDirectory() ) { recurse( p ); }
			else if ( e.isFile() ) { out.push( p ); }
		}
	}
	recurse( rootDir );
	return out;
}

/**
 * Rewrite relative image url() refs in a single CSS string to S3 URLs.
 * Returns the rewritten string (identical when nothing matched).
 */
function rewriteImageRefs( cssText, cssDir, buildDirAbs, s3Base, extSet ) {
	var urlRe = /url\(\s*([^)]+?)\s*\)/g;

	return cssText.replace( urlRe, function ( full, rawTarget ) {
		var t     = rawTarget.trim();
		var quote = '';

		// Preserve the original quote style.
		if ( ( t[0] === '"' && t[ t.length - 1 ] === '"' ) ||
			 ( t[0] === "'" && t[ t.length - 1 ] === "'" ) ) {
			quote = t[0];
			t     = t.substring( 1, t.length - 1 );
		}

		// Split off ?query / #fragment, preserved verbatim on the rewrite.
		var suffix = '';
		var sufIdx = t.search( /[?#]/ );
		if ( sufIdx !== -1 ) {
			suffix = t.substring( sufIdx );
			t      = t.substring( 0, sufIdx );
		}

		if ( ! t ) { return full; }

		// Leave absolute URLs, protocol-relative, data URIs, root-anchored.
		if ( /^[a-z][a-z0-9+.-]*:\/\//i.test( t ) || /^data:/i.test( t ) ||
			 t.substring( 0, 2 ) === '//' || t[0] === '/' ) {
			return full;
		}

		// Images only — fonts (woff/ttf/eot) stay local.
		var ext = path.extname( t ).replace( /^\./, '' ).toLowerCase();
		if ( ! extSet.has( ext ) ) { return full; }

		// Resolve against the CSS file dir; must live under BUILD_DIR and exist.
		var abs = path.resolve( cssDir, t );
		var rel = path.relative( buildDirAbs, abs );
		if ( ! rel || rel.substring( 0, 2 ) === '..' || path.isAbsolute( rel ) ) {
			return full;
		}
		if ( ! fs.existsSync( abs ) ) { return full; }

		var key = rel.split( path.sep ).join( '/' );
		return 'url(' + quote + s3Base + key + suffix + quote + ')';
	} );
}

function main() {
	var buildDir = process.argv[ 2 ];
	var s3Base   = process.argv[ 3 ];
	var extCsv   = process.argv[ 4 ] || '';

	if ( ! buildDir || ! s3Base ) {
		process.stderr.write( 'usage: node bin/rewrite-css-image-refs-to-s3.js <build-dir> <s3-base-url> [ext-csv]\n' );
		process.exit( 1 );
	}
	if ( ! fs.existsSync( buildDir ) ) {
		process.stderr.write( '[rewrite-css-image-refs-to-s3] build dir not found: ' + buildDir + '\n' );
		process.exit( 1 );
	}

	// Trailing-slash the base so concatenation with the relative key is clean.
	if ( s3Base[ s3Base.length - 1 ] !== '/' ) { s3Base += '/'; }

	var exts = extCsv
		? extCsv.split( ',' ).map( function ( e ) { return e.trim().toLowerCase(); } ).filter( Boolean )
		: DEFAULT_EXTENSIONS;
	var extSet = new Set( exts );

	var buildDirAbs = path.resolve( buildDir );
	var cssFiles    = walkFiles( buildDir ).filter( function ( f ) {
		return /\.css$/i.test( f );
	} );

	var touched = 0;
	var rewrites = 0;

	for ( var i = 0; i < cssFiles.length; i++ ) {
		var cssFile = cssFiles[ i ];
		var text;
		try {
			text = fs.readFileSync( cssFile, 'utf8' );
		} catch ( e ) {
			continue;
		}

		var before    = ( text.match( /url\(/g ) || [] ).length;
		var rewritten = rewriteImageRefs( text, path.dirname( cssFile ), buildDirAbs, s3Base, extSet );

		if ( rewritten !== text ) {
			fs.writeFileSync( cssFile, rewritten );
			touched++;
			// Count how many refs now point at the bucket (rough, for logging).
			var after = ( rewritten.split( s3Base ).length - 1 );
			rewrites += after;
			void before;
		}
	}

	process.stdout.write(
		'[rewrite-css-image-refs-to-s3] rewrote image url() refs in ' + touched +
		' CSS file(s) to ' + s3Base + ' (~' + rewrites + ' refs).\n'
	);
}

main();
