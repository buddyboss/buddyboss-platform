/* jshint browser: true */
/* global bp */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	/**
	 * [ReadLaunch description]
	 *
	 * @type {Object}
	 */
	bp.Readylaunch.Forums = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.addListeners();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			var $document = $( document );

			$document.on( 'change', '#bb-rl-forum-scope-options', this.handleForumScopeChange );
		},

		handleForumScopeChange: function ( e ) {
			e.preventDefault();
			var $current = $( this ),
				$link    = $current.val();

			window.location.href = $link;

			return false;
		},
	};

	// Launch members.
	bp.Readylaunch.Forums.start();

} )( bp, jQuery );
