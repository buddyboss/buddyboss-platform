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
				'.bb-rl-group-extra-info .bb_more_options .generic-button a.item-button:not(.group-manage), .bb-rl-groups-single-wrapper a.bb-rl-more-link, .bb-rl-about-group a.bb-rl-more-link',
				function ( e ) {
					e.preventDefault();
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

			$document.on( 'click', '.bb-rl-group-members-list-options a', this.handleGroupMembersListOptionsClick );
		},

		openModal: function ( modalId ) {
			var $modal = $( '#' + modalId );

			if ( ! $modal.length ) {
				return;
			}

			$modal.addClass( 'open' );
		},

		handleGroupMembersListOptionsClick : function ( e ) {
			e.preventDefault();
			var $link      = $( this );
			var $options   = $link.closest( '.bb-rl-group-members-list-options' );
			var $list      = $options.closest( '.widget_bb_group_members_widget' ).find( '.bb-rl-group-members-list' );
			var $groupAttr = JSON.parse( $link.data( 'group-attr' ) );
			$list.addClass( 'loading' );

			$options.find( 'a' ).removeClass( 'selected' );
			$link.addClass( 'selected' );

			$.post(
				ajaxurl,
				{
					action     : 'widget_groups_members_list',
					'_wpnonce' : $groupAttr.nonce,
					'group_id' : $groupAttr.group_id,
					'max'      : $groupAttr.max,
					'filter'   : $groupAttr.filter
				},
				function ( response ) {
					$list.removeClass( 'loading' );
					if ( response.data.success && $list.length && response.data.html ) {
						$list.html( response.data.html );
					}
				}
			);

			return false;
		},
	};

	// Launch members.
	bp.Readylaunch.Groups.start();

} )( bp, jQuery );
