<?php
/**
 * Search functions
 *
 * @since BuddyPress 3.0.0
 * @version 3.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Register Scripts for the search component
 *
 * @since BuddyPress 3.0.0
 *
 * @param  array $scripts The array of scripts to register
 *
 * @return array  The same array with the specific search scripts.
 */
function bp_nouveau_search_register_scripts( $scripts = array() ) {

	if ( ! isset( $scripts['bp-nouveau'] ) ) {
		return $scripts;
	}

	return array_merge(
		$scripts,
		array(
			'bp-nouveau-search' => array(
				'file'         => 'js/buddypress-search%s.js',
				'dependencies' => array( 'bp-nouveau' ),
				'footer'       => true,
			),
		)
	);
}

/**
 * Enqueue the search scripts
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_search_enqueue_scripts() {

	/* To show number of listing per page. */
	$per_page = '5';
	if ( function_exists( 'bp_search_get_form_option' ) ) {
		$per_page = bp_search_get_form_option( 'bp_search_number_of_results', 5 );
	}

	$data = array(
		'nonce'                 => wp_create_nonce( 'bp_search_ajax' ),
		'action'                => 'bp_search_ajax',
		'debug'                 => true, // set it to false on production
		'ajaxurl'               => admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' ),
		// 'search_url'    => home_url( '/' ), Now we are using form[role='search'] selector
		'loading_msg'           => esc_html__( 'Loading suggestions...', 'buddyboss' ),
		'enable_ajax_search'    => function_exists( 'bp_is_search_autocomplete_enable' ) && bp_is_search_autocomplete_enable(),
		'per_page'              => $per_page,
		'autocomplete_selector' => "form[role='search']:not(.bp-dir-search-form), form.search-form:not(.bp-dir-search-form), form.searchform:not(.bp-dir-search-form), form#adminbarsearch:not(.bp-dir-search-form), .bp-search-form>#search-form:not(.bp-dir-search-form)",
		'form_selector'         => ".bp-search-page form.bp-dir-search-form[role='search']",
		'forums_autocomplete'   => false,
	);

	if ( isset( $_GET['s'] ) ) {
		$data['search_term'] = $_GET['s'];
	}

	if ( bp_is_active( 'forums' ) ) {
		$data['forums_autocomplete'] = (
			bp_is_search_post_type_enable( bbp_get_forum_post_type() ) ||
			bp_is_search_post_type_enable( bbp_get_topic_post_type() ) ||
			bp_is_search_post_type_taxonomy_enable( bbpress()->topic_tag_tax_id, bbp_get_topic_post_type() ) ||
			bp_is_search_post_type_enable( bbp_get_reply_post_type() )
		);
	}

	wp_enqueue_script( 'jquery-ui-autocomplete' );
	wp_enqueue_script( 'bp-nouveau-search' );

	wp_localize_script( 'bp-nouveau-search', 'BP_SEARCH', apply_filters( 'bp_search_js_settings', $data ) );
}

/**
 * Output search message autocomplete init JS.
 *
 * @since BuddyPress 3.0.0
 */
function bp_nouveau_search_messages_autocomplete_init_jsblock() {
	?>

	<script>
		window.user_profiles = Array();
		jQuery( document ).ready( function() {
			var obj = jQuery( '.send-to-input' ).autocomplete( {
				source: function( request, response ) {
					jQuery( 'body' ).data( 'ac-item-p', 'even' );
					var term = request.term;
					if ( term in window.user_profiles ) {
						response( window.user_profiles[term] );
						return;
					}
					var data = {
						'action': 'messages_autocomplete_results',
						'search_term': request.term,
					};
					jQuery.ajax( {
						url: ajaxurl + '?q=' + request.term + '&limit=10',
						data: data,
						success: function( data ) {
							var new_data = Array();
							d = data.split( '\n' );
							jQuery.each( d, function( i, item ) {
								new_data[new_data.length] = item;
							} );
							if ( data != '' ) {
								response( new_data );
							}
						},
					} );
				},
				minLength: 1,
				select: function( event, ui ) {
					sel_item = ui.item;
					var d = String( sel_item.label ).split( ' (' );
					var un = d[1].substr( 0, d[1].length - 1 );
					//check if it already exists;
					if ( 0 === jQuery( '.acfb-holder' ).find( '#un-' + un ).length ) {
						var ln = '#link-' + un;
						var l = jQuery( ln ).attr( 'href' );
						var v = '<li class="selected-user friend-tab" id="un-' + un + '"><span><a href="' + l + '">' + d[0] + '</a></span> <span class="p">X</span></li>';
						if ( jQuery( '.acfb-holder' ).find( '.friend-tab' ).length == 0 ) {
							var x = jQuery( '.acfb-holder' ).prepend( v );
						} else {
							var x = jQuery( '.acfb-holder' ).find( '.friend-tab' ).last().after( v );
						}
						jQuery( this ).val( '' ); //clear username field after selecting username from autocomplete dropdown
						jQuery( '#send-to-usernames' ).addClass( un );
					}
					return false;
				},
				focus: function( event, ui ) {
					jQuery( '.ui-autocomplete li' ).removeClass( 'ui-state-hover' );
					jQuery( '.ui-autocomplete' ).find( 'li:has(a.ui-state-focus)' ).addClass( 'ui-state-hover' );
					return false;
				},
			} );

			obj.data( 'ui-autocomplete' )._renderItem = function( ul, item ) {
				ul.addClass( 'ac_results' );
				if ( jQuery( 'body' ).data( 'ac-item-p' ) == 'even' ) {
					c = 'ac_event';
					jQuery( 'body' ).data( 'ac-item-p', 'odd' );
				} else {
					c = 'ac_odd';
					jQuery( 'body' ).data( 'ac-item-p', 'even' );
				}
				return jQuery( '<li class=\'' + c + '\'>' ).append( '<a>' + item.label + '</a>' ).appendTo( ul );
			};

			obj.data( 'ui-autocomplete' )._resizeMenu = function() {
				var ul = this.menu.element;
				ul.outerWidth( this.element.outerWidth() );
			};

			jQuery( document ).on( 'click', '.selected-user', function() {
				jQuery( this ).remove();
			} );
			jQuery( '#send_message_form' ).submit( function() {
				tosend = Array();
				jQuery( '.acfb-holder' ).find( '.friend-tab' ).each( function( i, item ) {
					un = jQuery( this ).attr( 'id' );
					un = un.replace( 'un-', '' );
					tosend[tosend.length] = un;
				} );
				document.getElementById( 'send-to-usernames' ).value = tosend.join( ' ' );
			} );
		} );
	</script>

	<?php
}

/**
 * Enqueue scripts and localize data for BuddyBoss ReadyLaunch search functionality.
 *
 * This function registers the necessary JavaScript files and provides localization
 * data for handling search functionalities in the BuddyBoss ReadyLaunch theme.
 * It dynamically sets parameters such as nonces, AJAX action URLs, messages, and
 * other settings required for the search feature.
 *
 * @since BuddyBoss 2.9.00
 *
 * @return void
 */
function bb_rl_search_enqueue_scripts() {
	global $bp;

	/* To show the number of listings per page. */
	$per_page = '5';
	if ( function_exists( 'bp_search_get_form_option' ) ) {
		$per_page = bp_search_get_form_option( 'bp_search_number_of_results', 5 );
	}

	$data = array(
		'nonce'                 => wp_create_nonce( 'bp_search_ajax' ),
		'action'                => 'bp_search_ajax',
		'debug'                 => true, // set it to false on production.
		'ajaxurl'               => admin_url( 'admin-ajax.php', is_ssl() ? 'admin' : 'http' ),
		'loading_msg'           => esc_html__( 'Loading suggestions...', 'buddyboss' ),
		'enable_ajax_search'    => function_exists( 'bp_is_search_autocomplete_enable' ) && bp_is_search_autocomplete_enable(),
		'per_page'              => $per_page,
		'autocomplete_selector' => '.bb-rl-network-search-modal .search-form',
		'form_selector'         => '.bp-search-form-wrapper #search-form',
		'forums_autocomplete'   => false,
	);

	if ( isset( $_GET['s'] ) ) {
		$data['search_term'] = $_GET['s'];
	}

	$min = bp_core_get_minified_asset_suffix();

	wp_enqueue_script( 'jquery-ui-autocomplete' );
	wp_enqueue_script( 'bb-rl-nouveau-search', trailingslashit( $bp->plugin_url ) . "bp-templates/bp-nouveau/readylaunch/js/buddypress-search{$min}.js", array( 'bp-nouveau' ), bp_get_version(), true );

	wp_localize_script( 'bb-rl-nouveau-search', 'BP_SEARCH', apply_filters( 'bp_search_js_settings', $data ) );
}
