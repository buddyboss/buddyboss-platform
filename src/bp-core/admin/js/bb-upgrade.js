window.bp = window.bp || {};

(function () {

	var APIDomain = 'https://www.buddyboss.com/';

	function renderIntegrations() {

		var defaultOptions = {
			previewParent: jQuery( '.bb-integrations-section-listing' ),
			data: [],
			collections: null,
			categories: null,
			searchQuery: '',
			collectionId: '',
			categoryId: 'all',
			page: 1,
			per_page: 20,
			categoryHeadings: false
		};

		// Initial render.
		render( defaultOptions );

		function fetchIntegrations( append ) {
			var requestData = {
				'_embed': true,
				'per_page': defaultOptions.per_page,
				'page': defaultOptions.page,
				'orderby': 'category_name',
				'order': 'asc',
			};

			if ( defaultOptions.searchQuery ) {
				requestData.search = defaultOptions.searchQuery;
			}

			if ( defaultOptions.collectionId && defaultOptions.collectionId !== 'all' ) {
				requestData.integrations_collection = defaultOptions.collectionId;
			} else {
				requestData['orderby'] = "category_name";
				defaultOptions.categoryHeadings = true;
			}

			if ( defaultOptions.categoryId && defaultOptions.categoryId !== 'all' ) {
				requestData.integrations_category = defaultOptions.categoryId;
			}

			jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations',
					data: requestData,
					success: function ( response ) {
						if ( append ) {
							defaultOptions.data = defaultOptions.data.concat( response );
						} else {
							defaultOptions.data = response;
						}
						render( defaultOptions );
					},
					error: function ( response ) {
						console.log( 'Error fetching integrations' );
						if ( response && response.status === 400 ) {
							jQuery( '.bb-integrations_loadmore' ).remove();
							return;
						}
					}
				}
			);
		}

		function fetchCollectionsAndCategories() {
			var collectionsRequest = jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations_collection?per_page=99'
				}
			);

			var categoriesRequest = jQuery.ajax(
				{
					method: 'GET',
					url: APIDomain + 'wp-json/wp/v2/integrations_category?per_page=99&orderby=name&hide_empty=1'
				}
			);

			jQuery.when( collectionsRequest, categoriesRequest ).done(
				function ( collectionsResponse, categoriesResponse ) {
					defaultOptions.collections = collectionsResponse[0];
					defaultOptions.categories = {};
					for (var i = 0; i < categoriesResponse[0].length; i++) {
						var collection = categoriesResponse[0][i];
						defaultOptions.categories[collection.id] = collection.name;
					}
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
				defaultOptions.searchQuery = jQuery( this ).val();
				defaultOptions.page        = 1;
				fetchIntegrations( false );
			}
		);

		jQuery( document ).on(
			'change',
			'select[name="categories_integrations"]',
			function () {
				defaultOptions.categoryId = jQuery( this ).val();
				defaultOptions.page       = 1;
				fetchIntegrations( false );
			}
		);

		jQuery( document ).on(
			'click',
			'.bb-integrations_loadmore',
			function (e) {
				e.preventDefault();
				jQuery( this ).addClass( 'loading' );
				defaultOptions.page += 1;
				fetchIntegrations( true );
			}
		);
	}

	renderIntegrations();

}(jQuery));
