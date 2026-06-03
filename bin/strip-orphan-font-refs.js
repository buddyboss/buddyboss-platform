#!/usr/bin/env node
/* jshint node:true */
/**
 * Rewrite CSS inside a staged build directory to remove `url(...)` refs to
 * font files we strip from the customer zip. Files remain in src/ and on
 * the production branch; this step only touches the BUILD_DIR copy so the
 * shipped zip's CSS doesn't 404 on missing fonts.
 *
 * Called from the Gruntfile AFTER `commit_build_to_mothership_release`
 * (the production branch keeps the original CSS) and BEFORE `compress`.
 *
 * Usage:
 *   node bin/strip-orphan-font-refs.js <build-dir> <stripped-rel-paths-comma-separated>
 *
 * Example:
 *   node bin/strip-orphan-font-refs.js buddyboss-platform/ \
 *     "bp-templates/bp-nouveau/icons/fonts/box-filled.eot,\
 *      bp-templates/bp-nouveau/icons/fonts/box-filled.ttf,..."
 *
 * Design notes:
 *  - We resolve every `url(...)` reference against the CSS file's own
 *    location (the way the browser would). A ref is "orphan" iff the
 *    resolved absolute path matches a stripped path.
 *  - We only strip the specific orphan entries; other `src:` entries in
 *    the same cascade (woff2, woff, svg) remain intact.
 *  - Both minified (single-line, single-quoted) and unminified
 *    (multi-line, indented) syntaxes are handled.
 *  - Idempotent: running on already-cleaned CSS is a no-op.
 *
 * @since BuddyBoss [BBVERSION]
 */

'use strict';

var fs   = require( 'fs' );
var path = require( 'path' );

/**
 * Walk a directory recursively and return every regular file's absolute
 * path. Symlinks are not followed.
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
 * Resolve a CSS url() target (which can include `?query` / `#fragment`)
 * against the CSS file's directory, returning the absolute filesystem path
 * with query/fragment stripped. Returns null for absolute URLs / data URIs.
 */
function resolveCssUrl( cssDir, rawTarget ) {
	// Strip surrounding quotes if present.
	var t = rawTarget.trim();
	if ( ( t[0] === '"' && t[t.length-1] === '"' ) || ( t[0] === "'" && t[t.length-1] === "'" ) ) {
		t = t.substring( 1, t.length - 1 );
	}
	// Drop query and fragment.
	var q = t.indexOf( '?' );
	if ( q !== -1 ) { t = t.substring( 0, q ); }
	var h = t.indexOf( '#' );
	if ( h !== -1 ) { t = t.substring( 0, h ); }
	// Skip absolute URLs / data URIs — those don't resolve to BUILD_DIR.
	if ( /^[a-z][a-z0-9+.-]*:\/\//i.test( t ) || /^data:/i.test( t ) ) { return null; }
	// Skip already-empty or root-anchored (we only handle relative refs).
	if ( ! t ) { return null; }
	if ( t[0] === '/' ) { return null; }
	return path.resolve( cssDir, t );
}

/**
 * Replace orphan src entries inside a single CSS string. Returns the
 * rewritten string. The strategy is to scan each `url(...)` occurrence,
 * determine if it's orphan, and if so remove the enclosing entry — keeping
 * sibling entries in the same `src:` declaration intact.
 *
 * We iterate left-to-right with a running cursor, rebuilding the output as
 * we go. This is more robust than regex-replace when multiple entries can
 * appear on one line (minified syntax).
 */
function stripOrphanRefs( cssText, cssDir, strippedSet ) {
	var out = [];
	var i = 0;
	var len = cssText.length;
	var modified = false;

	// Find every `url(` occurrence — the rest of the entry (the `... format(...)`
	// suffix and the surrounding `, ` or `;` separators) is decided per match.
	var urlRe = /url\(\s*([^)]+?)\s*\)/g;
	var m;
	var lastEnd = 0;

	while ( ( m = urlRe.exec( cssText ) ) !== null ) {
		var urlStart  = m.index;       // index of `u` in `url(`
		var urlEnd    = urlRe.lastIndex; // one past the `)`
		var target    = m[1];

		var absTarget = resolveCssUrl( cssDir, target );
		if ( ! absTarget || ! strippedSet.has( absTarget ) ) {
			continue; // keep this ref untouched
		}

		// This ref is orphan. Now compute the slice of the original CSS
		// representing the WHOLE entry — including any leading whitespace
		// + leading `,` from the previous entry, the optional `format(...)`
		// suffix, and the trailing `,` separator (or terminal `;` if this
		// is the last entry in a cascade).

		// 1. Expand right to capture optional ` format(...)` suffix and
		//    one trailing separator (`,` or `;`). We stop at the first
		//    `;` (end of declaration) or `,` (next entry).
		var rightCursor = urlEnd;
		while ( rightCursor < len && /\s/.test( cssText[ rightCursor ] ) ) { rightCursor++; }
		if ( cssText.substr( rightCursor, 7 ) === 'format(' ) {
			rightCursor += 7;
			// skip to matching close-paren
			var paren = 1;
			while ( rightCursor < len && paren > 0 ) {
				if ( cssText[ rightCursor ] === '(' ) { paren++; }
				else if ( cssText[ rightCursor ] === ')' ) { paren--; }
				rightCursor++;
			}
		}
		// Consume trailing whitespace.
		while ( rightCursor < len && /\s/.test( cssText[ rightCursor ] ) ) { rightCursor++; }
		// Decide separator behaviour:
		//   - If next char is `,` → consume it and following whitespace
		//     (this entry sits in the middle of a cascade; later entries follow)
		//   - If next char is `;` → keep `;` for the surviving src (we'll
		//     also need to drop the preceding `,` if we're the last entry)
		var consumedTrailingComma = false;
		if ( cssText[ rightCursor ] === ',' ) {
			rightCursor++;
			consumedTrailingComma = true;
			while ( rightCursor < len && /[ \t]/.test( cssText[ rightCursor ] ) ) { rightCursor++; }
			// Also swallow one newline so an entire entry line disappears
			// in unminified CSS rather than leaving a blank line.
			if ( cssText[ rightCursor ] === '\n' ) { rightCursor++; }
			while ( rightCursor < len && /[ \t]/.test( cssText[ rightCursor ] ) ) { rightCursor++; }
		}

		// 2. Expand left to capture leading whitespace AND a preceding
		//    comma if this entry sits at the tail of a cascade (i.e. no
		//    trailing comma — we just consumed `;`). When there's a
		//    trailing comma, the next entry takes over, so we leave the
		//    preceding `,` alone.
		var leftCursor = urlStart;
		while ( leftCursor > 0 && /[ \t]/.test( cssText[ leftCursor - 1 ] ) ) { leftCursor--; }
		if ( ! consumedTrailingComma ) {
			// Walk back past whitespace+newlines to find a preceding `,`.
			var scan = leftCursor - 1;
			while ( scan >= 0 && /\s/.test( cssText[ scan ] ) ) { scan--; }
			if ( scan >= 0 && cssText[ scan ] === ',' ) {
				leftCursor = scan;
			}
		} else {
			// Mid-cascade — keep the preceding entry's trailing comma and
			// just consume our own leading whitespace + newline.
			if ( cssText[ leftCursor ] === '\n' && leftCursor > 0 ) {
				leftCursor--;
				while ( leftCursor > 0 && /[ \t]/.test( cssText[ leftCursor - 1 ] ) ) { leftCursor--; }
				if ( cssText[ leftCursor ] !== '\n' ) { leftCursor++; }
			}
		}

		// 3. Special case: if the entry is the FIRST src:url(...) in a
		//    multi-src declaration like
		//        src: url('X.eot');
		//        src: url('X.eot?#iefix') format('embedded-opentype'),
		//             url('X.woff2') ...
		//    we may have consumed `, ` of an unrelated next entry; the
		//    `src: ` prefix still sits at leftCursor. That's still valid
		//    CSS — the resulting line becomes `src: url('X.woff2') ...`
		//    after the orphan is gone. The regex-walk handles that by
		//    just promoting the next entry naturally.

		// Append unchanged region + skip orphan.
		out.push( cssText.substring( lastEnd, leftCursor ) );
		lastEnd  = rightCursor;
		modified = true;

		// Advance the regex past this entry.
		urlRe.lastIndex = rightCursor;
	}

	if ( ! modified ) { return cssText; }

	out.push( cssText.substring( lastEnd ) );
	var result = out.join( '' );

	// Cleanup pass: a `src: ;` (empty declaration) means we stripped the
	// only entry in a cascade. Drop the whole declaration line.
	result = result.replace( /^[ \t]*src:\s*;[ \t]*\n/gm, '' );
	// Minified equivalent: `src:;`.
	result = result.replace( /src:;/g, '' );
	// And `src: url(...)  ;` with stray whitespace between url() and `;`.
	result = result.replace( /^([ \t]*src:[ \t]*url\([^)]+\))[ \t]*;[ \t]*$/gm, '$1;' );

	return result;
}

function main() {
	var buildDir       = process.argv[ 2 ];
	var strippedCsv    = process.argv[ 3 ] || '';

	if ( ! buildDir ) {
		process.stderr.write( 'usage: node bin/strip-orphan-font-refs.js <build-dir> <stripped-paths-csv>\n' );
		process.exit( 1 );
	}
	if ( ! fs.existsSync( buildDir ) ) {
		process.stderr.write( '[strip-orphan-font-refs] build dir not found: ' + buildDir + '\n' );
		process.exit( 1 );
	}

	// Normalize stripped paths into absolute paths under buildDir.
	var strippedSet = new Set();
	var paths = strippedCsv.split( ',' ).map( function ( p ) { return p.trim(); } ).filter( Boolean );
	for ( var i = 0; i < paths.length; i++ ) {
		strippedSet.add( path.resolve( buildDir, paths[ i ] ) );
	}

	if ( strippedSet.size === 0 ) {
		process.stdout.write( '[strip-orphan-font-refs] no stripped paths supplied — nothing to do.\n' );
		return;
	}

	// Walk for CSS / SCSS files. We mutate SCSS too in case any ship to the
	// zip; harmless on the unminified copies.
	var allFiles = walkFiles( buildDir );
	var cssFiles = allFiles.filter( function ( f ) {
		return /\.(css|scss)$/i.test( f );
	} );

	var touched   = 0;
	var bytesIn   = 0;
	var bytesOut  = 0;

	for ( var j = 0; j < cssFiles.length; j++ ) {
		var cssFile = cssFiles[ j ];
		var text;
		try {
			text = fs.readFileSync( cssFile, 'utf8' );
		} catch ( e ) {
			continue;
		}
		var cssDir  = path.dirname( cssFile );
		var rewritten = stripOrphanRefs( text, cssDir, strippedSet );
		if ( rewritten !== text ) {
			fs.writeFileSync( cssFile, rewritten );
			touched++;
			bytesIn  += Buffer.byteLength( text, 'utf8' );
			bytesOut += Buffer.byteLength( rewritten, 'utf8' );
		}
	}

	process.stdout.write(
		'[strip-orphan-font-refs] rewrote ' + touched + ' CSS file(s); '
		+ ( bytesIn - bytesOut ) + ' bytes pruned across the build dir.\n'
	);
}

main();
