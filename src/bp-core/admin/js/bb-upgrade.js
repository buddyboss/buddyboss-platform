window.bp = window.bp || {};

( function () {

	var APIDomain = 'https://www.buddyboss.com/';

	var xhr = null;

	function renderIntegrations() {

		var defaultOptions = {
			previewParent: jQuery( '.bb-integrations-section-listing' ),
			data: null,
			collections: null,
			categoriesArr: null,
			categoriesObj: null,
			searchQuery: '',
			collectionId: '',
			categoryId: 'all',
			page: 1,
			per_page: 20,
			categoryHeadings: false,
			totalpages: 1,
		};

		var searchDebounce = null;

		// Initial render.
		render( defaultOptions );

		function fetchIntegrations( append ) {
			var requestData = {
				'per_page': defaultOptions.per_page,
				'page': defaultOptions.page,
				'orderby': 'category_name',
				'order': 'asc',
				'exclude': '390262', // Exclude submit one.
			};

			if ( defaultOptions.searchQuery ) {
				requestData.search = defaultOptions.searchQuery;
			}

			if ( defaultOptions.collectionId && defaultOptions.collectionId !== 'all' ) {
				requestData.integrations_collection = defaultOptions.collectionId;
			} else {
				defaultOptions.categoryHeadings = true;
			}

			if ( defaultOptions.categoryId && defaultOptions.categoryId !== 'all' ) {
				requestData.integrations_category = defaultOptions.categoryId;
			}

			if ( xhr ) {
				xhr.abort();
			}

			xhr = jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations',
					data: requestData,
					success: function ( response, textStatus, jqXHR ) {
						if ( append ) {
							defaultOptions.data = defaultOptions.data.concat( response );
						} else {
							defaultOptions.data = response;
						}
						defaultOptions.totalpages = parseInt( jqXHR.getResponseHeader( 'X-WP-TotalPages' ) );
						render( defaultOptions );
						xhr = null;
					},
					error: function ( response ) {
						console.log( 'Error fetching integrations' );
						if ( response && response.status === 400 ) {
							jQuery( '.bb-integrations_loadmore' ).remove();
							return;
						}
						xhr = null;
					},
				}
			);
		}

		function fetchCollectionsAndCategories() {

			function getTimestamp() {
				return new Date().getTime();
			}

			// Duration in milliseconds for 7 days.
			var duration = 7 * 24 * 60 * 60 * 1000;

			// Check localStorage before making API requests.
			var cachedCollections   = localStorage.getItem( 'bb-integrations-collections' );
			var cachedCategoriesObj = localStorage.getItem( 'bb-integrations-categories-obj' );
			var cachedCategoriesArr = localStorage.getItem( 'bb-integrations-categories-arr' );
			var cachedTimestamp     = localStorage.getItem( 'bb-integrations-time' );

			if ( cachedCollections && cachedCategoriesObj && cachedCategoriesArr && cachedTimestamp ) {
				var currentTime = getTimestamp();
				var cachedTime  = parseInt( cachedTimestamp, 10 );
				if ( currentTime - cachedTime < duration ) {
					defaultOptions.collections   = JSON.parse( cachedCollections );
					defaultOptions.categoriesObj = JSON.parse( cachedCategoriesObj );
					defaultOptions.categoriesArr = JSON.parse( cachedCategoriesArr );
					render( defaultOptions );
					fetchIntegrations( false );
					return;
				}
			}

			var collectionsRequest = jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations_collection?per_page=99&orderby=id',
				}
			);

			var categoriesRequest = jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations_category?per_page=99&orderby=name&hide_empty=1',
				}
			);

			jQuery.when( collectionsRequest, categoriesRequest ).done(
				function ( collectionsResponse, categoriesResponse ) {
					defaultOptions.collections   = collectionsResponse[ 0 ];
					defaultOptions.categoriesObj = {};
					defaultOptions.categoriesArr = [];
					for ( var i = 0; i < categoriesResponse[ 0 ].length; i++ ) {
						var collection                                = categoriesResponse[ 0 ][ i ];
						defaultOptions.categoriesObj[ collection.id ] = collection.name;
						defaultOptions.categoriesArr.push( [ collection.id, collection.name ] );
					}

					// Store the data in localStorage.
					localStorage.setItem( 'bb-integrations-collections', JSON.stringify( defaultOptions.collections ) );
					localStorage.setItem( 'bb-integrations-categories-obj', JSON.stringify( defaultOptions.categoriesObj ) );
					localStorage.setItem( 'bb-integrations-categories-arr', JSON.stringify( defaultOptions.categoriesArr ) );
					localStorage.setItem( 'bb-integrations-time', getTimestamp().toString() );
					render( defaultOptions );
					fetchIntegrations( false );
				}
			).fail(
				function () {
					console.log( 'Error fetching collections or categories' );
				}
			);
		}

		function render( renderOptions ) {
			var tmpl     = jQuery( '#tmpl-bb-integrations' ).html();
			var compiled = _.template( tmpl );
			var html     = compiled( renderOptions );

			if ( renderOptions.previewParent ) {
				renderOptions.previewParent.html( html );
			}
		}

		// Initial data fetch for collections and categories, followed by integrations.
		fetchCollectionsAndCategories();

		// Event listeners for input changes.
		jQuery( document ).on(
			'change',
			'input[name="integrations_collection"]',
			function () {
				jQuery( this ).closest( '.bb-integrations_filters' ).addClass( 'loading' );
				if ( jQuery( this ).siblings( 'span' ).text().toLowerCase() === 'all' ) {
					defaultOptions.collectionId = 'all';
				} else {
					defaultOptions.collectionId = jQuery( this ).val();
				}
				defaultOptions.page = 1;
				fetchIntegrations( false );
			}
		);

		jQuery( document ).on(
			'keyup',
			'input[name="search_integrations"]',
			function () {
				if ( searchDebounce ) {
					clearTimeout( searchDebounce );
				}
				searchDebounce = setTimeout(
					function () {
						jQuery( this ).closest( '.bb-integrations_search' ).addClass( 'loading' );
						defaultOptions.searchQuery = jQuery( this ).val();
						defaultOptions.page        = 1;
						fetchIntegrations( false );
					}.bind( this ),
					500
				);
			}
		);

		jQuery( document ).on(
			'change',
			'select[name="categories_integrations"]',
			function () {
				jQuery( this ).closest( '.bb-integrations_filters' ).addClass( 'loading' );
				defaultOptions.categoryId = jQuery( this ).val();
				defaultOptions.page       = 1;
				fetchIntegrations( false );
			}
		);

		jQuery( document ).on(
			'click',
			'.bb-integrations_loadmore',
			function ( e ) {
				e.preventDefault();
				jQuery( this ).addClass( 'loading' );
				defaultOptions.page += 1;
				fetchIntegrations( true );
			}
		);

		jQuery( document ).on(
			'click',
			'.bb-integrations_search .clear-search',
			function ( e ) {
				e.preventDefault();
				defaultOptions.page        = 1;
				defaultOptions.searchQuery = '';
				jQuery( this ).closest( '.bb-integrations_search' ).addClass( 'loading' );
				fetchIntegrations( false );
			}
		);
	}

	if ( jQuery( '.bb-integrations-section-listing' ).length ) {
		renderIntegrations();
	}

}( jQuery ) );
