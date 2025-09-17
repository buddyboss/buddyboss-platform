/*global replies_data*/
jQuery( document ).ready(
	function() {

		// var bbp_topic_id = jQuery( '#bbp_topic_id' );
		//
		// bbp_topic_id.suggest(
		// bbp_topic_id.data( 'ajax-url' ),
		// {
		// onSelect: function() {
		// var value = this.value;
		// bbp_topic_id.val( value.substr( 0, value.indexOf( ' ' ) ) );
		// }
		// }
		// );

		if ( jQuery( '#bbp_reply_attributes' ). length ) {

			jQuery( '#titlewrap' ).hide();
			jQuery( '#bbp_forum_id' ).on(
				'change',
				function () {

					var discussion = jQuery( '#parent_id' );
					discussion.html( '' );
					discussion.append( jQuery( '<option></option>' ).attr( 'value','' ).text( replies_data.loading_text ) );
					jQuery.ajax(
						{
							'url' : ajaxurl,
							'method' : 'POST',
							'data' : {
								'action' : 'bbp_suggest_topic',
								'post_parent' : this.value
							},
							'success' : function( response ) {
								discussion.html( '' );
								discussion.html( response );
							}
						}
					);
				}
			);

			jQuery( '#parent_id' ).on(
				'change',
				function () {
					var reply = jQuery( '#bbp_reply_to' );
					reply.html( '' );
					reply.append( jQuery( '<option></option>' ).attr( 'value','' ).text( replies_data.loading_text ) );
					jQuery.ajax(
						{
							'url' : ajaxurl,
							'method' : 'POST',
							'data' : {
								'action' : 'bbp_suggest_reply',
								'post_parent' : this.value
							},
							'success' : function( response ) {
								reply.html( '' );
								reply.html( response );
							}
						}
					);
				}
			);
		}

	}
);
