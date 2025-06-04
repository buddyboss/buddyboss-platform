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
			$document.on( 'click', '.bb-rl-forum-tabs-item a', this.handleForumTabsClick );
		},

		handleForumScopeChange: function ( e ) {
			e.preventDefault();
			var $current = $( this ),
				$link    = $current.val();

			window.location.href = $link;

			return false;
		},

		handleForumTabsClick: function ( e ) {
			e.preventDefault();
			var $current = $( this ).parent(),
				$tab    = $current.data( 'id' );

			$('.bb-rl-forum-tabs-item').removeClass( 'selected' );
			$current.addClass( 'selected' );

			$('.bb-rl-forum-tabs-content').removeClass( 'selected' );
			$('#' + $tab).addClass( 'selected' );
		},
	};

	// Launch members.
	bp.Readylaunch.Forums.start();

} )( bp, jQuery );
