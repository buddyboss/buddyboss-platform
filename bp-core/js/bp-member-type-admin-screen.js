/* global ClipboardJS */
// profile types screen
if (typeof jq == 'undefined') {
	var jq = jQuery;
}

// Link any localized strings.
var l10n       = window._bpmtAdminL10n || {},
	btnChanged = false;


jq( document ).ready(
	function () {

		/** Copy profile type Shortcode *******************/
		var clipboard = new ClipboardJS( '.copy-to-clipboard' );
		clipboard.on(
			'success',
			function() {
				var  $btnCtc = jq( '.copy-to-clipboard' );
				$btnCtc.fadeOut(
					function(){
						$btnCtc.text( l10n.copied );
						$btnCtc.fadeIn();
						btnChanged = true;
					}
				);

			}
		);

		// Disable click event on copy button
		jq( '.copy-to-clipboard' ).on(
			'click',
			function(e) {
				e.preventDefault();
			}
		);

		// Change button text from "Copied" to "Copy to clipboard" on mouseout
		jq( '.copy-to-clipboard' ).on(
			'mouseout',
			function() {
				if ( btnChanged ) {
					var $btnCtc = jq( '.copy-to-clipboard' );
					$btnCtc.fadeOut(
						function(){
							$btnCtc.text( l10n.copytoclipboard );
							$btnCtc.fadeIn();
							btnChanged = false;
						}
					);
				}
			}
		);

		// Member post type validation
		jq( '.post-type-bp-member-type #post' ).submit(
			function () {

				jq( '#title' ).css( {border: 'none'} );
				jq( '.bp-member-type-label-name' ).css( {border: 'none'} );
				jq( '.bp-member-type-singular-name' ).css( {border: 'none'} );

				var p_title         = jq( '#title' ).val();
				var p_plural_name   = jq( '.bp-member-type-label-name' ).val();
				var p_singular_name = jq( '.bp-member-type-singular-name' ).val();

				if (p_title.length == 0) {
					jq( '#title' ).css(
						{'border-color': '#d54e21',
							'border-width': '1px',
							'border-style': 'solid'}
					);

				}
				if (p_plural_name.length == 0) {
					jq( '.bp-member-type-label-name' ).css(
						{'border-color': '#d54e21',
							'border-width': '1px',
							'border-style': 'solid'}
					);
				}
				if (p_singular_name.length == 0) {
					jq( '.bp-member-type-singular-name' ).css(
						{'border-color': '#d54e21',
							'border-width': '1px',
							'border-style': 'solid'}
					);
				}

				if ( p_title.length == 0 || p_plural_name.length == 0 || p_singular_name.length == 0 ) {
					return false;
				}

				return true;

			}
		);

		/**
		 * Show warning when we delete/trash a profile type, that already has users attached to it
		 */
		jq( 'a.submitdelete' ).on(
			'click',
			function (e) {

				var $submitDelete = jq( this ),
				msgWarn           = '',
				user_count        = +($submitDelete.parents( 'tr' ).children( '.total_users' ).text());

				// Performing trash
				if ( 'trash' === $submitDelete.parent().attr( 'class' ) ) {

					msgWarn = l10n.warnTrash.formatUnicorn( {total_users: user_count} );

					// Performing permanent delete
				} else if ( 'delete' === $submitDelete.parent().attr( 'class' ) ) {
					msgWarn = l10n.warnDelete.formatUnicorn( {total_users: user_count} );
				}

				if ( 0 < user_count && 0 < msgWarn.length && ! window.confirm( msgWarn ) ) {
					e.preventDefault();
				}

			}
		);

		/**
		 * Show warning when we bulk delete/trash a profile type, that already has users attached to it
		 */
		jq( '#doaction, #doaction2' ).on(
			'click',
			function(e) {
				var
				typeStr     = '',
				user_count,
				msgWarnBulk = '';

				// Check if we have users with profile type assigned
				jq( 'input[name="post[]"]:checked:not(:first-child):not(:last-child)' ).each(
					function(){
						var $this = jq( this );
						var $tr   = $this.parents( 'tr' );

						user_count = +($tr.children( '.total_users' ).text());

						if (  0 < user_count ) {
							typeStr += '\n' + $this.prev().text().trim().substr( 6 ).trim();
						}
					}
				);

				// Performing trash
				if ( 'trash' === jq( 'select[name^="action"]' ).val() ) {

					msgWarnBulk = l10n.warnBulkTrash + '\n' + typeStr;

					// Performing permanent delete
				} else if ( 'delete' === jq( 'select[name^="action"]' ).val() ) {
					msgWarnBulk = l10n.warnBulkDelete + '\n' + typeStr;
				}

				// You want to delete/trash? - Confirm
				if ( 0 < typeStr.length && 0 < msgWarnBulk.length && ! window.confirm( msgWarnBulk ) ) {
					e.preventDefault();
				}
			}
		);

		// Set tabindex
		if ( 'undefined' != typeof jq( '.post-type-bp-member-type #title' ) ) {
			jq( '.post-type-bp-member-type #title' ).attr( 'tabindex', 1 );
		}

		// tabindex
		if ( 'undefined' != typeof jq( '.post-type-bp-member-type #publish' ) ) {
			jq( '.post-type-bp-member-type #publish' ).attr( 'tabindex', 7 );
		}
	}
);

/**
 * JavaScript equivalent to sprintf for l10n strings
 *
 * str = "Hello, {name}, are you feeling {adjective}?".formatUnicorn({name:"Boss", adjective: "OK"});
 *
 * o/p Hello, Boss, are you feeling OK?
 */
if ( ! String.prototype.formatUnicorn) {
	String.prototype.formatUnicorn = function() {
		var str = this.toString();
		if ( ! arguments.length ) {
			return str;
		}
		var /*args = typeof arguments[0],*/
			args = (('string' == args || 'number' == args) ? arguments : arguments[0]);
		for ( var arg in args ) {
			str = str.replace( RegExp( '\\{' + arg + '\\}', 'gi' ), args[ arg ] );
		}
		return str;
	};
}
