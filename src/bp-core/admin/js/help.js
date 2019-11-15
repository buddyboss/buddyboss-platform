/**
 * BuddyBoss Help implementation.
 *
 * @since BuddyBoss 1.2.1
 */

(function( $ ) {
	$( window ).on(
		'load',
		function() {

			var bp_help_wpapi = new WPAPI({ endpoint: 'https://buddyboss.com/resources/wp-json' });
			//var bp_help_wpapi = new WPAPI({ endpoint: 'http://localhost/buddyboss/wp-json' });
			bp_help_wpapi.docs = bp_help_wpapi.registerRoute( 'wp/v2', '/docs/(?P<id>)', {
				params: [ 'before', 'after', 'author', 'parent', 'post', 'order', 'orderby' ]
			} );

			if ( $( '#bp-help-main-menu-wrap' ).length ) {
				bp_help_wpapi.docs().parent(0).order('asc').orderby('menu_order').then(function (docs) {
					var bp_help_cards = '';
					$.each( docs, function ( index, value ) {
						bp_help_cards += '<div class="bp-help-card bp-help-menu-wrap">\n' +
							'\t\t\t<div class="inside">';
						bp_help_cards += '<h2><a href="'+BP_HELP.bb_help_url+'&article='+value.id+'">'+value.title.rendered+'</a></h2>';
						bp_help_cards += value.content.rendered;
						bp_help_cards += '</div>\n' +
							'\t\t</div>';
					} );
					$( '#bp-help-main-menu-wrap' ).html(bp_help_cards);
				});
			}

			if ( $( '#bp-help-content-area' ).length ) {
				var url = new URL(window.location.href);
				var article_id = url.searchParams.get("article");
				bp_help_wpapi.docs().id( article_id ).then(function (doc) {
					$( '#bp-help-content-area' ).append('<h1>' + doc.title.rendered + '</h1>');
					$( '#bp-help-content-area' ).append(doc.content.rendered);
					bp_help_js_render_hierarchy_dom( doc );
					//$( '.bp-help-content-wrap .bp-help-sidebar .loop-1 .main.level-1 > a' ).attr('href',BP_HELP.bb_help_url+'&article='+doc.id);
					//$( '.bp-help-content-wrap .bp-help-sidebar .loop-1 .main.level-1 > a' ).text(doc.title.rendered);
					//$( '.bp-help-content-wrap .bp-help-sidebar .loop-1 .main.level-1 > a' ).closest('li').addClass(doc.slug);
				});
			}

			function bp_help_js_render_hierarchy_dom( doc ) {

				var ancestors = doc.ancestors ? doc.ancestors.reverse() : [];
				var children = doc.children ? doc.children : [];

				var breadcrumps = '<li class="main"><a href="'+BP_HELP.bb_help_url+'" class="dir">'+BP_HELP.bb_help_title+'</a></li>';
				breadcrumps += '<li class="main level-1 '+doc.slug+'"><a href="'+BP_HELP.bb_help_url+'&article='+doc.id+'" class="dir">'+doc.title.rendered+'</a></li>';

				if ( ancestors.length ) {
					var level = 2;
					$.each( ancestors, function (key,value) {
						breadcrumps += '<li class="main level-'+level+' '+value.post_name+'"><a href="'+BP_HELP.bb_help_url+'&article='+value.ID+'" class="dir">'+value.post_title+'</a></li>';
						level++;
					} );
				}

				$( '.bp-help-menu' ).append(breadcrumps);

				var sidebar = '<ul class="loop-1"><li class="main level-1"><a href="'+BP_HELP.bb_help_url+'&article='+doc.id+'" class="dir">'+doc.title.rendered+'</a>';

				// if ( children.length ) {
				// 	sidebar += '<ul class="loop-2">';
				// 	for(var k = 0; k < children.length; k++){
				// 		sidebar += bp_help_js_get_doc_children_dom(sidebar,children[k],2);
				// 	}
				// 	sidebar += '</ul>';
				// }

				sidebar += '</li></ul>';

				$( '.bp-help-sidebar' ).html(sidebar);
			}

			function bp_help_js_get_doc_children_dom( sidebar,doc,level ) {
				sidebar += '<li class="main level-'+level+' '+doc.post_name+'"><a href="'+BP_HELP.bb_help_url+'&article='+doc.ID+'">'+doc.post_title+'</a>';

				if ( doc.children ) {
					level = level + 1;
					sidebar += '<ul class="loop-'+level+'">';
					for(var k = 0; k < doc.children.length; k++){
						sidebar += bp_help_js_get_doc_children_dom(sidebar,doc.children[k],level);
					}
					sidebar += '</ul>';
				}

				sidebar += '</li>';

				return sidebar;
			}
		}
	);
})( jQuery );
