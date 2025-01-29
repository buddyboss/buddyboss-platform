/* globals BP_HELP, WPAPI */
/**
 * BuddyBoss Help implementation.
 *
 * @since BuddyBoss 1.2.1
 */
window.wp = window.wp || {};
window.bp = window.bp || {};

(function ( exports, $ ) {

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	var options         = options || {};
	options.context = 'view';
	options.data    = options.data || {};
	options.path    = 'buddyboss/v1/members';
	options.method  = 'GET';

	options.data = _.extend(
		options.data,
		{
			page: 1,
			per_page: 20,
		}
	);

	bp.apiRequest( options ).done(
		function ( data ) {
			// Get the container and template.
			var container = $( '#members-list' );
			var template  = _.template( $( '#member-template' ).html() );

			container.empty(); // Clear previous data if any.

			// Loop through the data and apply the template.
			data.forEach(
				function (member) {
					var memberHTML = template(
						{
							avatar: member.avatar_urls.full,
							name: member.name,
							id: member.id,
							url: member.link,
							followers: member.followers,
							type: 'Member',
						}
					);

					container.append( memberHTML );
				}
			);
		}
	).fail(
		function () {
			console.log( 'error' );
		}
	);

} )( bp, jQuery );
