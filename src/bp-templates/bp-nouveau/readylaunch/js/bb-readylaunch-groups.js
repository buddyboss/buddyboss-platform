/* jshint browser: true */
/* global bp, bbReadyLaunchGroupsVars, BP_Nouveau */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function ( exports, $ ) {

	/**
	 * [ReadLaunch description]
	 *
	 * @type {Object}
	 */
	bp.Readylaunch.Groups = {
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

			$document.on(
				'click',
				'.bb-rl-group-extra-info .bb_more_options .generic-button a.item-button',
				function ( e ) {
					var modalId = 'model--' + $( this ).attr( 'id' );
					var $modal  = $( '#' + modalId );

					if ( ! $modal.length ) {
						return;
					}

					e.preventDefault();
					bp.Readylaunch.Groups.openModal( modalId );
				}
			);

			$document.on(
				'click',
				'.bb-rl-modal-close-button',
				function (e) {
					e.preventDefault();
					$( this ).closest( '.bb-rl-action-popup' ).removeClass( 'open' );
				}
			);
		},

		openModal: function ( modalId ) {
			var $modal = $( '#' + modalId );

			if ( ! $modal.length ) {
				return;
			}

			$modal.addClass( 'open' );
		},
	};

	// Launch members.
	bp.Readylaunch.Groups.start();

} )( bp, jQuery );
