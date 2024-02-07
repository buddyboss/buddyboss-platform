/* global wp, bp, BP_Nouveau, _, Backbone */
/* jshint devel: true */
/* @version 3.1.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

(function ( exports, $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Nouveau description]
	 *
	 * @type {Object}
	 */
	bp.Nouveau.ActivityModal = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.views       = new Backbone.Collection();
			this.collections = [];
			this.types       = [];
			this.fetchXhr    = null;
			this.loader      = [];
			this.loader_html = $( '' );

			// Listen to events ("Add hooks!").
			this.addListeners();

			this.Initialize();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'click', '.acomments-view-more', this.showActivityModal );
		},

		Initialize: function () {
		},

		showActivityModal: function( event ) {
			event.preventDefault();

			var self      = bp.Nouveau.ActivityModal,
				target_init = $( event.currentTarget ),
				target      = $( '.bb-activity-model-wrapper' ),
				header 			= target.find('.bb-modal-activity-header-wrapper'),
				item_id     = target_init.parent().data( 'activity_id' ),
				activity_id = $( '#activity-' + item_id );
				item_type   = '';

			var modalHeadingView = new bp.Views.ActivityMoadlHeading( { model: this.model } );
			target.show();
			header.append(modalHeadingView.render().el);
		}
	};

	bp.Models.commentItems = Backbone.Model.extend( {} );

	bp.Collections.ActivityCommentsCollection = Backbone.Collection.extend(
		{
			model: bp.Models.commentItems,
			options: {},
			this: this,
			url: BP_Nouveau.ajaxurl,

			initialize: function () {
				this.options = {
					item_type: 'activity',
				};
			},

			sync: function ( method, model, options ) {

				
			},

			parse: function ( resp ) {
				
			}
		}
	);

	// View for modal heading.
	bp.Views.ActivityMoadlHeading = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'bb-modal-activity-header',
			template: bp.template( 'activity-modal-heading' ),
			events: {
				'click .bb-close-action-popup': 'closeView'
			},

			initialize: function ( options ) {
				this.data = options.data;
				this.headerRendered = false;
			},

			render: function () {
				if (!this.headerRendered) {
					this.$el.empty();
					this.$el.append(this.template({ data: this.data }));
					this.headerRendered = true;
				}
				return this;
			},

			closeView: function ( e ) {
				e.preventDefault();
				this.headerRendered = false;
				this.$el.closest( '.bb-activity-model-wrapper' ).hide();
				this.$el.remove();
			},
		}
	);

	// Launch BP Nouveau Activity Modal.
	bp.Nouveau.ActivityModal.start();

})( bp, jQuery );
