// `@wordpress/html-entities` is not an installed devDependency in this repo
// (it's resolved to the `wp.htmlEntities` global by the WP webpack externals
// config at build time, not present in node_modules). Jest has no module to
// resolve, so `kbApi.js`'s `import { decodeEntities } from '@wordpress/html-entities'`
// fails to require. Virtual-mock it so this unit test can exercise `kbApi.js`
// in isolation without adding a new package dependency.
jest.mock( '@wordpress/html-entities', () => ( {
	decodeEntities: ( text ) => text,
} ), { virtual: true } );

describe( 'kbApi standalone config fallback', () => {
	afterEach( () => {
		delete window.bbAdminData;
		delete window.bbIntegrationsData;
		delete window.bbKb;
		jest.resetModules();
	} );

	test( 'proxy endpoint uses window.bbKb.apiUrl when no settings/integrations data', () => {
		window.bbKb = { apiUrl: '/wp-json/bb/v1/', nonce: 'n1' };
		// jsdom's `global` has no `fetch` property in this project's jest
		// preset (no whatwg-fetch polyfill), so `jest.spyOn( global, 'fetch' )`
		// throws ("Property `fetch` does not exist"). Assign a jest.fn()
		// directly instead — same `.mock.calls` assertions, no polyfill needed.
		global.fetch = jest.fn().mockResolvedValue( {
			ok: true, json: async () => ( { body: [], headers: {} } ),
		} );
		const spy = global.fetch;
		const { kbApi } = require( '../kbApi' );
		return kbApi.getArticle( 'slug' ).then( () => {
			expect( spy.mock.calls[ 0 ][ 0 ] ).toBe( '/wp-json/bb/v1/help-content/proxy' );
			expect( spy.mock.calls[ 0 ][ 1 ].headers[ 'X-WP-Nonce' ] ).toBe( 'n1' );
			delete global.fetch;
		} );
	} );
} );
