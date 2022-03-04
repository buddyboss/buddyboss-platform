jQuery( document ).ready(
	function ( $ ) {

		var xhr_favorite;
		var xhr_subscription;

		function bbp_ajax_call( action, topic_id, nonce, update_selector ) {
			  var $data = {
					action : action,
					id     : topic_id,
					nonce  : nonce
			};

			/*globals bbpTopicJS:false */
			$bbp_xhr = $.post(
				bbpTopicJS.bbp_ajaxurl,
				$data,
				function ( response ) {
					if ( response.success ) {
						 $( update_selector ).html( response.content );
					} else {
						if ( ! response.content ) {
							response.content = bbpTopicJS.generic_ajax_error;
						}
						alert( response.content );
					}

					if ( action === 'favorite' ) {
						xhr_favorite = false;
					} else if ( action === 'subscribe' ) {
						xhr_subscription = false;
					}
				}
			);

			return $bbp_xhr;
		}

		$( '#favorite-toggle' ).on(
			'click',
			'span a.favorite-toggle',
			function( e ) {
				e.preventDefault();
				if ( xhr_favorite ) {
					return;
				}

				xhr_favorite = bbp_ajax_call( 'favorite', $( this ).attr( 'data-topic' ), bbpTopicJS.fav_nonce, '#favorite-toggle' );
			}
		);

		$( '#subscription-toggle' ).on(
			'click',
			'span a.subscription-toggle',
			function( e ) {
				e.preventDefault();
				if ( xhr_subscription ) {
					return;
				}
				xhr_subscription = bbp_ajax_call( 'subscription', $( this ).attr( 'data-topic' ), bbpTopicJS.subs_nonce, '#subscription-toggle' );
			}
		);
	}
);
