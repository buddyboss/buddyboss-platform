/**
 * Members & Groups Loading (PROD-9724): Load More continues the list that is on screen.
 *
 * Load More used to rebuild its request from session memory, which every list of an object
 * shares, so a list could continue with another list's tab, order or search. These tests load
 * the real BP Nouveau and ReadyLaunch bundles (sources and builds) against WordPress's own
 * jQuery and Underscore, stub only the AJAX transport, and check the request Load More sends.
 *
 * Run: npx wp-scripts test-unit-js src/bp-templates/bp-nouveau/js/__tests__/loadMoreQuery.test.js
 */
/* eslint-disable no-eval -- the bundles and WordPress's jQuery are classic scripts that must run in the jsdom global scope. */
const fs = require( 'fs' );
const path = require( 'path' );

const NOUVEAU = path.resolve( __dirname, '../..' );
// The plugin is developed inside a WordPress install; reuse the jQuery/Underscore it ships.
const WP_JS = path.resolve(
	__dirname,
	'../../../../../../../../wp-includes/js'
);
const LIBS = [
	path.join( WP_JS, 'jquery/jquery.js' ),
	path.join( WP_JS, 'underscore.min.js' ),
];
const haveLibs = LIBS.every( ( file ) => fs.existsSync( file ) );

const BUNDLES = [
	[ 'nouveau source', 'js/buddypress-nouveau.js' ],
	[ 'nouveau build', 'js/buddypress-nouveau.min.js' ],
	[ 'readylaunch source', 'readylaunch/js/buddypress-nouveau.js' ],
	[ 'readylaunch build', 'readylaunch/js/buddypress-nouveau.min.js' ],
];

const LIST = ( object, href ) =>
	'<div data-bp-list="' +
	object +
	'"><ul class="bp-list"><li data-bp-item-id="1"></li>' +
	'<li class="load-more"><a href="' +
	href +
	'">Load More</a></li></ul></div>';

/**
 * Render the markup, (re)load the bundle and stub the transport.
 *
 * @param {string} bundle Bundle path relative to bp-nouveau/.
 * @param {string} html   Markup inside #buddypress.
 * @return {Array} Posted request bodies, in order.
 */
function boot( bundle, html ) {
	document.body.innerHTML = '<div id="buddypress">' + html + '</div>';
	window.sessionStorage.clear();
	window.bp = {};
	window.BP_Nouveau = {
		nonces: {},
		objects: [],
		object_nav_parent: '#buddypress',
		directory_autoload: '1',
		loadingMore: 'Loading...',
		dir_labels: {},
	};
	window.bbRLObjectNavParent = '#buddypress';

	if ( ! window.jQuery ) {
		LIBS.forEach( ( file ) =>
			( 0, eval )( fs.readFileSync( file, 'utf8' ) )
		);
	}
	try {
		( 0, eval )( fs.readFileSync( path.join( NOUVEAU, bundle ), 'utf8' ) );
	} catch ( e ) {
		// start() wires page features that jsdom does not have; the methods under test are defined before it runs.
	}

	const posts = [];
	window.bp.Nouveau.objectNavParent = '#buddypress';
	window.bp.Nouveau.ajax = ( postData ) => {
		posts.push( window.jQuery.extend( {}, postData ) );
		return window.jQuery
			.Deferred()
			.resolve( { success: true, data: { contents: '' } } )
			.promise();
	};
	return posts;
}

function clickLoadMore() {
	window.bp.Nouveau.loadMoreItems( {
		preventDefault() {},
		data: window.bp.Nouveau,
		currentTarget: document.querySelector( 'li.load-more a' ),
	} );
}

const describeWithLibs = haveLibs ? describe : describe.skip;

if ( ! haveLibs ) {
	// eslint-disable-next-line no-console
	console.warn(
		'loadMoreQuery tests skipped: WordPress jQuery/Underscore not found at ' +
			WP_JS
	);
}

describeWithLibs.each( BUNDLES )( 'Load More query (%s)', ( label, bundle ) => {
	test( 'continues with the order the list was loaded with, not the one in session memory', () => {
		const posts = boot( bundle, LIST( 'members', '?upage=2' ) );
		// The Members directory left "active" in memory; Mutual Connections has no order-by and loads unordered.
		window.sessionStorage.setItem(
			'bp-members',
			JSON.stringify( { scope: 'all', filter: 'active' } )
		);
		window.bp.Nouveau.objectRequest( {
			object: 'members',
			scope: 'all',
			filter: null,
			page: 1,
			method: 'reset',
		} );

		clickLoadMore();

		expect( posts[ 1 ] ).toMatchObject( {
			method: 'append',
			filter: null,
			page: 2,
		} );
	} );

	test( 'a server-rendered list continues its highlighted tab and order-by, not session memory', () => {
		const posts = boot(
			bundle,
			'<ul><li data-bp-object="members" data-bp-scope="all" class="selected"></li>' +
				'<li data-bp-object="members" data-bp-scope="personal"></li></ul>' +
				'<select data-bp-filter="members"><option value="active" selected>a</option><option value="alphabetical">b</option></select>' +
				LIST( 'members', '?upage=4' )
		);
		window.sessionStorage.setItem(
			'bp-members',
			JSON.stringify( { scope: 'personal', filter: 'alphabetical' } )
		);

		clickLoadMore();

		expect( posts[ 0 ] ).toMatchObject( {
			scope: 'all',
			filter: 'active',
			page: 4,
		} );
	} );

	test( 'search text that was typed but not submitted does not reach Load More', () => {
		const posts = boot(
			bundle,
			'<div data-bp-search="members"><input type="search" value=""></div>' +
				LIST( 'members', '?upage=2' )
		);
		window.bp.Nouveau.objectRequest( {
			object: 'members',
			scope: 'all',
			filter: 'active',
			search_terms: '',
			page: 1,
			method: 'reset',
		} );
		document.querySelector( '[data-bp-search] input' ).value =
			'typed-not-submitted';

		clickLoadMore();

		expect( posts[ 1 ].search_terms ).toBe( '' );
	} );

	test( 'a Connections search (registered as "friends") is kept by Load More of the members list', () => {
		const posts = boot( bundle, LIST( 'members', '?upage=2' ) );
		window.bp.Nouveau.objectRequest( {
			object: 'members',
			scope: 'all',
			filter: 'active',
			search_terms: '',
			page: 1,
			method: 'reset',
		} );
		window.bp.Nouveau.objectRequest( {
			object: 'friends',
			scope: 'all',
			filter: 'active',
			search_terms: 'ann',
			page: 1,
			method: 'reset',
		} );

		clickLoadMore();

		expect( posts[ 2 ] ).toMatchObject( {
			method: 'append',
			search_terms: 'ann',
		} );
	} );

	test( 'a server-rendered Connections list reads its own "friends" order-by', () => {
		const posts = boot(
			bundle,
			'<select data-bp-filter="friends"><option value="newest" selected>n</option></select>' +
				LIST( 'members', '?upage=2' )
		);
		window.sessionStorage.setItem(
			'bp-members',
			JSON.stringify( { filter: 'active' } )
		);

		clickLoadMore();

		expect( posts[ 0 ].filter ).toBe( 'newest' );
	} );

	test( 'group members keep their template mapping and their own order-by', () => {
		const posts = boot( bundle, LIST( 'group_members', '?mlpage=2' ) );
		window.bp.Nouveau.objectRequest( {
			object: 'group_members',
			scope: null,
			filter: 'alphabetical',
			page: 1,
			method: 'reset',
		} );

		clickLoadMore();

		expect( posts[ 1 ] ).toMatchObject( {
			object: 'members',
			template: 'group_members',
			filter: 'alphabetical',
			page: 2,
		} );
	} );
} );
