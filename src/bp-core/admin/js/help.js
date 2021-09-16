/* globals BP_HELP, WPAPI */
/**
 * BuddyBoss Help implementation.
 *
 * @since BuddyBoss 1.2.1
 */

( function ( $ ) {
	$( window ).on(
		'load',
		function () {
			var bp_help_wpapi = new WPAPI( { endpoint: 'https://buddyboss.com/resources/wp-json' } );

			bp_help_wpapi.docs = bp_help_wpapi.registerRoute( 'wp/v2', '/docs/(?P<id>)', {
				params: [ 'before', 'after', 'author', 'parent', 'post', 'order', 'orderby', 'per_page' ]
			} );

			// Inital call render.
			bp_help_load_page();

			/**
			 * Render help page.
			 */
			function bp_help_load_page() {
				bbapp_help_empty_everything();
				var article_id = getArticleParam();

				// Hide Both Section by Default.
				$( '#bp-help-main-menu-wrap' ).hide();
				$( '.bp-help-content-wrap' ).hide();

				/**
				 * Loads Main Page.
				 */
				if ( !article_id ) {

					$( '#bp-help-main-menu-wrap' ).addClass( 'loading' ).html( '<div class="content-loader"><div></div><div></div><div></div></div>' );
					$( '#bp-help-main-menu-wrap' ).show();

					bp_help_wpapi.docs().parent( 0 ).order( 'asc' ).orderby( 'menu_order' ).per_page( 100 ).then( function ( docs ) {

						$( '#bp-help-main-menu-wrap' ).removeClass( 'loading' );

						var bp_help_cards = '';
						$.each( docs, function ( index, value ) {
							bp_help_cards += '<div class="bp-help-card bp-help-menu-wrap">\n' +
								'\t\t\t<div class="inside">';
							bp_help_cards += '<h2><a href="' + BP_HELP.bb_help_url + '&article=' + value.id + '">' + value.title.rendered + '</a></h2>';
							bp_help_cards += value.content.rendered;
							bp_help_cards += '</div>\n' +
								'\t\t</div>';
						} );
						$( '#bp-help-main-menu-wrap' ).html( bp_help_cards );
						bp_help_bind_ajax_links();

					} ).catch( function ( err ) {
						$( '#bp-help-main-menu-wrap' ).removeClass( 'loading' );
						var status = navigator.onLine;
						if ( !status ) {
							$( '#bp-help-main-menu-wrap' ).html( '<div class="notice notice-error"><p>' + BP_HELP.bb_help_no_network + '</p></div>' );
						} else {
							$( '#bp-help-main-menu-wrap' ).html( err );
						}
					} );
				}

				/**
				 * Loads Inner Section Page.
				 */
				if ( article_id ) {
					// show loading animation
					$( '#bp-help-main-menu-wrap' ).addClass( 'loading' ).html( '<div class="content-loader"><div></div><div></div><div></div></div>' );
					$( '#bp-help-main-menu-wrap' ).show();

					// Fetch all articles
					bp_help_wpapi.docs().id( article_id ).then( function ( doc ) {

						// IF layout is GRID
						if ( typeof ( doc.layout_type ) !== 'undefined' && doc.layout_type == 'view' ) {

							bbapp_help_empty_everything();

							$( '#bp-help-main-menu-wrap' ).removeClass( 'loading' );

							var bp_help_cards = '';
							$.each( doc.hierarchy, function ( index, value ) {

								var card_excerpt = value.post_excerpt;
								// If excerpt empty then show post content
								if ( !( card_excerpt ) ) {
									card_excerpt = value.post_content;
								}

								bp_help_cards += '<div class="bp-help-card bp-help-menu-wrap">\n' +
									'\t\t\t<div class="inside">';
								bp_help_cards += '<h2><a href="' + BP_HELP.bb_help_url + '&article=' + value.ID + '">' + value.post_title + '</a></h2>';
								bp_help_cards += card_excerpt;
								bp_help_cards += '</div>\n' +
									'\t\t</div>';
							} );

							$( '#bp-help-main-menu-wrap' ).html( bp_help_cards );
							bp_help_bind_ajax_links();

							// Default layout is article
						} else {

							$( '#bp-help-content-area' ).addClass( 'loading' ).html( '<div class="content-loader"><div></div><div></div><div></div></div>' );
							$( '.bp-help-sidebar' ).addClass( 'loading' ).html( '<div class="content-loader"><div></div><div></div><div></div></div>' );
							$( '.bp-help-content-wrap' ).show();

							bbapp_help_empty_everything();

							$( '#bp-help-content-area' ).removeClass( 'loading' );
							$( '.bp-help-sidebar' ).removeClass( 'loading' );

							$( '#bp-help-content-area' ).html( '<h1>' + doc.title.rendered + '</h1>' + doc.content.rendered );
							$( '#bp-help-content-area' ).find( 'a' ).attr( 'target', '_blank' );
							bp_help_js_render_hierarchy_dom( doc );
							bp_help_bind_ajax_links();
						}

					} ).catch( function ( err ) {
						$( '#bp-help-content-area' ).removeClass( 'loading' );
						$( '.bp-help-sidebar' ).removeClass( 'loading' ).html( '' );
						var status = navigator.onLine;
						if ( !status ) {
							$( '#bp-help-content-area' ).html( '<div class="notice notice-error"><p>' + BP_HELP.bb_help_no_network + '</p></div>' );
						} else {
							$( '#bp-help-content-area' ).html( err );
						}
					} );
				}
			}

			/**
			 * Load help content when navigate to one menu to another menu.
			 */
			function bp_help_bind_ajax_links() {
				jQuery( '.buddyboss_page_bp-help a[href*="' + BP_HELP.bb_help_url + '"]' ).click( function ( e ) {
					e.stopImmediatePropagation();
					if ( window.history.replaceState ) {
						e.preventDefault();
						// Change the URL Gracefully.
						window.history.replaceState( {}, null, jQuery( this ).attr( 'href' ) );
						bp_help_load_page(); // then load the page again.
						jQuery( 'html, body' ).animate( {
							scrollTop: 0
						}, 'fast' );
					}
				} );

				/**
				 * a[bb_article_id] anchor tag with "bb_article_id" attribute will come from Resources site.
				 * When artcile id specified, we will load provided url within the WP site instead of redirecting to Resources site.
				 */
				jQuery( 'a[bb_article_id]' ).click( function ( e ) {
					e.stopImmediatePropagation();
					if ( window.history.replaceState ) {
						e.preventDefault();

						var redirect_article_id = jQuery( this ).attr( 'bb_article_id' );
						var redirec_url = BP_HELP.bb_help_url + '&article=' + redirect_article_id;

						// Change the URL Gracefully.
						window.history.replaceState( {}, null, redirec_url );
						bp_help_load_page(); // then load the page again.
						jQuery( 'html, body' ).animate( {
							scrollTop: 0
						}, 'fast' );
					}
				} );

			}

			function bbapp_help_empty_everything() {
				// Empty everything
				$( '.bp-help-menu' ).html( '' );
				$( '.bp-help-content-area' ).html( '' );
				$( '.bp-help-sidebar' ).html( '' );
				$( '.bp-help-main-menu-wrap' ).find( '*' ).not( '.bp_loading' ).remove();
				jQuery( '.article-child' ).find( '*' ).not( '#article-child-title' ).remove(); // remove all child but #article-child-title
				jQuery( '#article-child-title' ).hide();
			}

			function getArticleParam() {
				var bbapp_help_page_url = new URL( window.location.href );
				var article_id = bbapp_help_page_url.searchParams.get( 'article' );
				if ( !article_id || typeof article_id == 'undefined' ) {
					return false;
				}
				return article_id;
			}

			function bp_help_js_render_hierarchy_dom( doc ) {
				var bp_help_page_url = new URL( window.location.href );
				var article_id = bp_help_page_url.searchParams.get( 'article' );
				var ancestors = doc.ancestors ? doc.ancestors.reverse() : [];
				var children = doc.children ? doc.children : [];
				var hierarchy = doc.hierarchy ? doc.hierarchy : [];

				var breadcrumps = '<li class="main"><a href="' + BP_HELP.bb_help_url + '" class="dir">' + BP_HELP.bb_help_title + '</a></li>';

				if ( ancestors.length ) {
					var level = 2;
					$.each( ancestors, function ( key, value ) {
						breadcrumps += '<li class="main level-' + level + ' ' + value.post_name + '"><a href="' + BP_HELP.bb_help_url + '&article=' + value.ID + '" class="dir">' + value.post_title + '</a></li>';
						level++;
					} );
				}

				breadcrumps += '<li class="main level-1 ' + doc.slug + '"><a href="' + BP_HELP.bb_help_url + '&article=' + doc.id + '" class="dir">' + doc.title.rendered + '</a></li>';

				$( '.bp-help-menu' ).append( breadcrumps );

				var selected = doc.id == article_id ? 'selected' : '';
				var sidebar = '';
				var articles_children = '';
				if ( ancestors.length ) {
					sidebar = '<ul class="loop-1"><li class="main level-1 ' + selected + '"><a href="' + BP_HELP.bb_help_url + '&article=' + ancestors[ 0 ].ID + '" class="dir">' + ancestors[ 0 ].post_title + '</a>';
				} else {
					sidebar = '<ul class="loop-1"><li class="main level-1 ' + selected + '"><a href="' + BP_HELP.bb_help_url + '&article=' + doc.id + '" class="dir">' + doc.title.rendered + '</a>';
				}

				if ( hierarchy.length ) {
					sidebar += '<ul class="loop-2">';
					for ( var k = 0; k < hierarchy.length; k++ ) {
						sidebar += bp_help_js_get_doc_hierarchy_dom( hierarchy[ k ], 2 );
					}
					sidebar += '</ul>';
				}

				if ( children.length ) {
					articles_children += '<ul class="loop-1">';
					for ( var i = 0; i < children.length; i++ ) {
						articles_children += bp_help_js_get_doc_children_dom( children[ i ], 1 );
					}
					articles_children += '</ul>';
					$( '.article-child #article-child-title' ).show();
					$( '.article-child' ).append( articles_children );
				}

				sidebar += '</li></ul>';

				$( '.bp-help-sidebar' ).html( sidebar );
			}

			function bp_help_js_get_doc_children_dom( doc, level ) {
				var articles_children = '<li class="main level-' + level + ' ' + doc.post_name + '"><a href="' + BP_HELP.bb_help_url + '&article=' + doc.ID + '">' + doc.post_title;

				if ( typeof doc.children !== 'undefined' && doc.children.length ) {

					level = level + 1;
					articles_children += '<ul class="loop-' + level + '">';
					for ( var k = 0; k < doc.children.length; k++ ) {
						articles_children += bp_help_js_get_doc_children_dom( doc.children[ k ], level );
					}
					articles_children += '</ul>';
				}

				articles_children += '</li>';

				return articles_children;
			}

			function bp_help_js_get_doc_hierarchy_dom( doc, level ) {
				var bp_help_page_url = new URL( window.location.href );
				var article_id = bp_help_page_url.searchParams.get( 'article' );
				var selected = doc.ID == article_id ? 'selected' : '';
				var sidebar = '<li class = "main level-' + level + ' ' + doc.post_name + ' ' + selected + '"><a href="' + BP_HELP.bb_help_url + '&article=' + doc.ID + '">' + doc.post_title;

				if ( typeof doc.children !== 'undefined' && doc.children.length ) {
					sidebar += ' <span class="sub-menu-count">(' + doc.children.length + ')</span>';
				}

				sidebar += '</a>';

				if ( typeof doc.children !== 'undefined' && doc.children.length ) {
					var active = selected ? true : false;
					if ( !active ) {
						for ( var i = 0; i < doc.children.length; i++ ) {
							if ( doc.children[ i ].ID == article_id ) {
								active = true;
								break;
							}
						}
					}
					var activeClass = active ? 'active' : '';
					sidebar += '<span class=actions><span class="open ' + activeClass + '"></span></span>';

					level = level + 1;
					var hiddenClass = active ? 'active' : 'hidden';
					sidebar += '<ul class="' + hiddenClass + ' loop-' + level + '">';
					for ( var k = 0; k < doc.children.length; k++ ) {
						sidebar += bp_help_js_get_doc_hierarchy_dom( doc.children[ k ], level );
					}
					sidebar += '</ul>';
				}

				sidebar += '</li>';

				return sidebar;
			}
		}
	);


} )( jQuery );