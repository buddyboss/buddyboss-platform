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
	bp.Nouveau.ActivityReaction = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.views       = new Backbone.Collection();
			this.collections = [];
			this.types       = [];
			this.fetchXhr    = [];
			this.loader      = [];
			this.loader_html = $( '<p class="reaction-loader"><i className="bb-icon-l bb-icon-spinner animate-spin"></i></p>' );

			// Listen to events ("Add hooks!").
			this.addListeners();

			this.Initialize();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'click', '.activity-state-reactions', this.showActivityReactions );
		},

		Initialize: function () {
		},

		showActivityReactions: function( event ) {
			event.preventDefault();
			var self        = bp.Nouveau.ActivityReaction;
			var target_init = $( event.currentTarget );

			var target    = target_init.next( '.activity-state-popup' );
			var item_id   = target_init.parents( '.activity-item' ).data( 'bp-activity-id' ).toString();
			var item_type = target_init.parents( 'li' ).hasClass( 'activity-comment' );

			var collection_key = item_id + '_0';

			self.collections[collection_key] = new bp.Collections.ActivityReactionCollection();

			self.loader[item_id] = new bp.Views.ReactionPopup(
				{
					collection: self.collections[collection_key],
					item_id: item_id,
					targetElement: target.find( '#reaction-content-' + item_id ),
					item_type: true === item_type ? 'activity_comment' : 'activity'
				}
			);
			target.show();
		}
	};

	bp.Models.reactedItem = Backbone.Model.extend( {} );

	bp.Collections.ActivityReactionCollection = Backbone.Collection.extend(
		{
			model: bp.Models.reactedItem,
			options: {},
			per_page: 20,
			this: this,
			url: BP_Nouveau.ajaxurl,

			initialize: function () {
				this.options = {
					page: 1,
					per_page: this.per_page,
					action: 'bb_get_reactions',
					total_pages: 1,
					item_type: 'activity',
				};
			},

			sync: function ( method, model, options ) {
				var options     = options || {};
				options.context = this;
				options.data    = options.data || {};
				options.path    = BP_Nouveau.ajaxurl;
				options.method  = 'POST';

				options.data = _.extend(
					options.data,
					model.options
				);

				// Add generic nonce.
				options.data._wpnonce = BP_Nouveau.nonces.activity;

				return Backbone.sync( method, model, options );
			},

			parse: function ( resp ) {
				return ( resp.success ) ? resp.data : {};
			}
		}
	);

	// Loading view.
	bp.Views.ReactionPopup = Backbone.View.extend(
		{
			tagName: 'div',
			className: '',
			template: bp.template( 'activity-reacted-popup-loader' ),
			targetElement: '',
			options: {},
			initialize: function ( options ) {
				this.loader = bp.Nouveau.ActivityReaction.loader_html;
				this.options = options;
				this.targetElement = options.targetElement;
				this.targetElement.append( this.loader );
				this.collection.on( 'sync', this.render, this );
				this.collection.fetch( { data: _.pick( options, [ 'page', 'per_page', 'item_id', 'item_type' ] ) } );
			},

			render: function () {
				this.loader.hide();

				var args = {
					collection: this.options.collection,
					item_id: this.options.item_id,
					item_type: this.options.item_type,
					model: this.collection.last()
				};

				// Render popup heading.
				var popupHeadingView = new bp.Views.ReactionPopupHeading( args );
				this.targetElement.append( popupHeadingView.render().el );

				var ReactionPopupContent = new bp.Views.ReactionPopupContent( args );
				this.targetElement.append( ReactionPopupContent.render().el );

				return this;
			},
		}
	);

	// View for popup heading.
	bp.Views.ReactionPopupHeading = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity-state-popup_title',
			template: bp.template( 'activity-reacted-popup-heading' ),
			render: function () {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			},
		}
	);

	// View for reacted users.
	bp.Views.ReactionPopupContent = Backbone.View.extend(
		{
			tagName: 'div',
			template: _.template( '' ),
			className: 'activity-state-popup_tab',
			options: {},
			initialize: function ( options ) {
				this.options = options;
			},
			render: function() {

				var args = {
					collection: this.options.collection,
					item_id: this.options.item_id,
					item_type: this.options.item_type,
					model: this.model,
				};

				var ReactionPopupTabs = new bp.Views.ReactionPopupTabs( args );
				this.$el.append( ReactionPopupTabs.render().el );

				var ReactionPopupLists = new bp.Views.ReactionPopupLists( args );
				this.$el.append( ReactionPopupLists.render().el );

				return this;
			}
		}
	);

	// View for reacted tabs.
	bp.Views.ReactionPopupTabs = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity-state-popup_tab_panel',
			template: bp.template( 'activity-reacted-popup-tab' ),
			model: this.model,
			options: {},
			collection: {},
			targetElement: '',
			initialize: function (options) {
				this.loader = bp.Nouveau.ActivityReaction.loader_html;
				this.options = options;
				this.collection = options.collection;
				// Listen for clicks on tabs.
				this.$el.on( 'click', 'li > a', _.bind( this.LoadTabData, this ) );

				this.args = {
					collection: this.options.collection,
					item_id: this.options.item_id,
					item_type: this.options.item_type,
					model: this.model,
				};
			},

			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			},

			LoadTabData: function ( e ) {
				var current = $( e.currentTarget ),
					tab     = current.data( 'tab' );

					this.targetElement = current.parents( '.activity-state-popup_tab' ).find( '.' + tab );

				if ( this.targetElement.length > 0 ) {
					if ( this.targetElement.find( '.activity-state_users li' ).length !== 0 ) {
						return;
					}

					this.targetElement.append( this.loader.show() );

					var arguments = {
						item_id: this.options.item_id,
						item_type: this.options.item_type,
						reaction_id: this.targetElement.data( 'reaction-id' ),
						page: this.targetElement.data( 'paged' ),
					};

					var selected_collection = arguments.item_id + '_' + arguments.reaction_id;

					if ( 'undefined' == typeof bp.Nouveau.ActivityReaction.collections[ selected_collection ] ) {
						bp.Nouveau.ActivityReaction.collections[ selected_collection ] = new bp.Collections.ActivityReactionCollection();
					}

					this.collection = bp.Nouveau.ActivityReaction.collections[ selected_collection ];

					this.args.collection = this.collection;
					this.collection.on( 'sync', this.renderLoad, this );
					this.collection.fetch( { data: _.pick( arguments, [ 'page', 'per_page', 'item_id', 'item_type', 'reaction_id' ] ) } );
				}
			},

			renderLoad: function () {
				this.loader.hide();
				this.args.model = this.collection.last();
				var ReactionItem = new bp.Views.ReactionItem( this.args );
				this.targetElement.find( '.activity-state_users' ).append( ReactionItem.render().el );
				return this;
			},
		}
	);

	bp.Views.ReactionPopupLists = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity-state-popup_tab_content',
			template: bp.template( 'activity-reacted-popup-tab-content' ),
			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			}
		}
	);

	bp.Views.ReactionItem = Backbone.View.extend(
		{
			template: bp.template( 'activity-reacted-item' ),
			render: function() {
				this.$el.html( this.template( this.model.toJSON() ) );
				return this;
			}
		}
	);

	// Launch BP Nouveau Subscriptions.
	bp.Nouveau.ActivityReaction.start();

})( bp, jQuery );
