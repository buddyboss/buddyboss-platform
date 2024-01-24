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
			this.fetchXhr    = null;
			this.loader      = [];
			this.loader_html = $( '<p class="reaction-loader"><i class="bb-icon-l bb-icon-spinner animate-spin"></i></p>' );

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

			var self        = bp.Nouveau.ActivityReaction,
				target_init = $( event.currentTarget ),
				target      = target_init.next( '.activity-state-popup' ),
				item_id     = 0,
				item_type   = '';

			if ( 0 < target_init.parents( '.acomment-display' ).first().length ) {
				item_id   = target_init.parents( '.activity-comment' ).first().data( 'bp-activity-comment-id' ).toString();
				item_type = 'activity_comment';
			} else {
				item_id   = target_init.parents( '.activity-item' ).data( 'bp-activity-id' ).toString();
				item_type = 'activity';
			}

			var collection_key = item_id + '_0';

			// remove the pop-up.
			target.find( '#reaction-content-' + item_id + ' .reaction-loader' ).remove();
			target.find( '#reaction-content-' + item_id + ' .activity_reaction_popup_error' ).remove();

			if ( '' === $.trim( target.find( '#reaction-content-' + item_id ).html() ) ) {
				self.collections[ collection_key ] = new bp.Collections.ActivityReactionCollection();
				self.loader[ item_id ]             = new bp.Views.ReactionPopup(
					{
						collection: self.collections[ collection_key ],
						item_id: item_id,
						targetElement: target.find( '#reaction-content-' + item_id ),
						item_type: item_type,
					}
				);
			}
			target.show();
		}
	};

	bp.Models.reactedItems = Backbone.Model.extend( {} );

	bp.Collections.ActivityReactionCollection = Backbone.Collection.extend(
		{
			model: bp.Models.reactedItems,
			options: {},
			per_page: 20,
			this: this,
			url: BP_Nouveau.ajaxurl,

			initialize: function () {
				this.options = {
					page: 1,
					per_page: this.per_page,
					item_type: 'activity',
				};
			},

			sync: function ( method, model, options ) {

				// Abort current operation when request multiple.
				if ( null !== bp.Nouveau.ActivityReaction.fetchXhr ) {
					bp.Nouveau.ActivityReaction.fetchXhr.abort();
				}

				var self = this;

				options         = options || {};
				options.context = self;
				options.data    = options.data || {};

				_.extend(
					options.data,
					_.pick( self.options, [ 'page', 'per_page', 'before' ] )
				);

				// Add generic data and nonce.
				options.path    	  = BP_Nouveau.ajaxurl;
				options.method  	  = 'POST';
				options.data._wpnonce = BP_Nouveau.nonces.activity;
				options.data.action   = 'bb_get_reactions';

				bp.Nouveau.ActivityReaction.fetchXhr = Backbone.sync( method, model, options );

				return bp.Nouveau.ActivityReaction.fetchXhr;
			},

			parse: function ( resp ) {
				var data = ( resp.success ) ? resp.data : {};

				return ! _.isUndefined( data.reacted_users ) ? data.reacted_users : {};
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
			initialize: function ( option ) {
				this.loader        = bp.Nouveau.ActivityReaction.loader_html;
				this.options       = option;
				this.targetElement = option.targetElement;
				this.targetElement.append( this.loader );
				this.collection.fetch(
					{
						data: _.pick( option, [ 'page', 'per_page', 'item_id', 'item_type' ] ),
						success : _.bind( this.onOpenSuccessRender, this ),
						error   : _.bind( this.onOpenFailedRender, this )
					}
				);
			},

			onOpenSuccessRender: function ( collection, response, options ) {
				this.loader.remove();

				if ( response.success ) {
					// Prepare the object to pass into views.
					var args = {
						collection: this.options.collection,
						item_id: this.options.item_id,
						item_type: this.options.item_type,
						model: this.collection.toJSON(),
						data: ( response.success ) ? response.data : {},
					};

					// Render popup heading.
					var popupHeadingView = new bp.Views.ReactionPopupHeading( args );
					this.targetElement.append( popupHeadingView.render().el );

					// Render popup content.
					var ReactionPopupContent = new bp.Views.ReactionPopupContent( args );
					this.targetElement.append( ReactionPopupContent.render().el );

					// Add scroll event.
					if ( this.targetElement.find( '.activity-state-popup_tab_item > ul' ) ) {
						var inside_self = this;
						this.targetElement.find( '.activity-state-popup_tab_item > ul' ).each(
							function () {
								$( this ).on( 'scroll', _.bind( inside_self.onScrollLoadMore, inside_self ) );
							}
						);
					}
				} else {
					this.onOpenFailedRender( collection, response, options );
				}

				return this;
			},

			onOpenFailedRender: function ( collection, response, options ) {
				this.loader.remove();

				if (
					'undefined' !== typeof response.statusText &&
					'abort' === response.statusText
				) {
					return;
				}

				// Prepare the object to pass into views.
				var args = {
					collection: options.collection,
					data: ( ! response.success ) ? response.data : {},
				};

				// Remove notice.
				this.targetElement.find( '.activity_reaction_popup_error' ).remove();

				// Render popup heading.
				var popupHeadingView = new bp.Views.ReactionErrorHandle( args );
				this.targetElement.append( popupHeadingView.render().el );
			},

			onScrollLoadMore: function( e ) {
				var element = e.currentTarget,
					target  = $( element );

				if ( ! $( element ).hasClass( 'loading' ) ) {
					var distanceFromBottom = element.scrollHeight - target.scrollTop() - target.outerHeight(),
						threshold          = 10,
						reaction_id        = target.parents( '.activity-state-popup_tab_item' ).attr( 'data-reaction-id' ),
						total_pages        = parseInt( target.parents( '.activity-state-popup_tab_item' ).attr( 'data-total-pages' ) ),
						paged              = target.parents( '.activity-state-popup_tab_item' ).attr( 'data-paged' );

					if ( 'undefined' === typeof paged ) {
						paged = 1;
					}

					// Check if the user has scrolled to the bottom.
					if (
						distanceFromBottom <= threshold &&
						paged < total_pages &&
						! $( element ).hasClass( 'loading' )
					) {

						if ( 0 === target.parents( '.activity-state-popup_tab_item' ).find( '.reaction-loader' ).length ) {
							target.parents( '.activity-state-popup_tab_item' ).append( this.loader.show() );
						} else {
							target.parents( '.activity-state-popup_tab_item' ).find( '.reaction-loader' ).show();
						}

						$( element ).addClass( 'loading' );
						paged = parseInt( paged, 10 ) + 1;

						var arguments = {
							item_id: this.options.item_id,
							item_type: this.options.item_type,
							reaction_id: reaction_id,
							page: paged
						};

						var selected_collection = arguments.item_id + '_' + arguments.reaction_id;

						if ( 'undefined' == typeof bp.Nouveau.ActivityReaction.collections[ selected_collection ] ) {
							bp.Nouveau.ActivityReaction.collections[ selected_collection ] = new bp.Collections.ActivityReactionCollection();
						}

						this.collection = bp.Nouveau.ActivityReaction.collections[ selected_collection ];

						this.args = {
							collection: this.options.collection,
							item_id: this.options.item_id,
							item_type: this.options.item_type,
							model: this.collection.toJSON(),
						};

						if ( this.collection.length > 0 ) {
							Object.assign( arguments, {
								before: this.collection.last().get( 'id' ),
							} );
						}

						_.extend(
							this.collection.options,
							_.pick( arguments, [ 'page', 'item_id', 'item_type', 'reaction_id', 'before' ] )
						);

						this.args.collection = this.collection;
						this.collection.fetch(
							{
								data: _.pick( arguments, [ 'page', 'item_id', 'item_type', 'reaction_id' ] ),
								success: _.bind( this.onLoadMoreSuccessRender, this, target ),
								error: _.bind( this.onLoadMoreFailedRender, this, target ),
							}
						);
					}
				}
			},

			onLoadMoreSuccessRender: function ( target, collection, response ) {
				this.loader.remove();

				if ( response.success ) {
					var models    = this.collection.toJSON();
					var next_page = ( response.success && ! _.isUndefined( response.data.page ) ) ? response.data.page : 0;

					if ( next_page !== 0 ) {
						target.parents( '.activity-state-popup_tab_item' ).attr( 'data-paged', next_page );
					}

					_.each(
						models,
						function ( model ) {
							var reactionItemView = new bp.Views.ReactionItem( { model: model } );
							target.append( reactionItemView.render().el );
						}
					);

					target.removeClass( 'loading' );
				} else {
					this.onLoadMoreFailedRender( target, collection, response );
				}

				return this;
			},

			onLoadMoreFailedRender: function( target, collection, response ) {
				this.loader.remove();

				if (
					'undefined' !== typeof response.statusText &&
					'abort' === response.statusText
				) {
					return;
				}

				// Prepare the object to pass into views.
				var args = {
					data: ( ! response.success ) ? response.data : {},
				};

				// Remove notice.
				target.find( '.activity_reaction_popup_error' ).remove();

				// Render popup heading.
				var popupHeadingView = new bp.Views.ReactionErrorHandle( args );
				target.append( popupHeadingView.render().el );
			}
		}
	);

	// View for popup heading.
	bp.Views.ReactionPopupHeading = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity-state-popup_title',
			template: bp.template( 'activity-reacted-popup-heading' ),
			initialize: function ( options ) {
				this.data = options.data;
			},
			render: function () {
				this.$el.html( this.template( this.data ) );
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
				this.data    = options.data;
			},
			render: function() {

				var args = {
					collection: this.options.collection,
					item_id: this.options.item_id,
					item_type: this.options.item_type,
					model: this.model,
					data: this.data,
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
			initialize: function (options) {
				this.loader     = bp.Nouveau.ActivityReaction.loader_html;
				this.options    = options;
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
				this.$el.html( this.template( this.options.data ) );
				return this;
			},

			LoadTabData: function ( e ) {
				var current       = $( e.currentTarget ),
					tab           = current.data( 'tab' ),
					targetElement = current.parents( '.activity-state-popup_tab' ).find( '.' + tab );

				if ( 0 < targetElement.length ) {
					if ( 0 !== targetElement.find( '.activity-state_users li' ).length ) {
						return;
					}

					targetElement.find( '.activity_reaction_popup_error' ).remove();
					targetElement.append( this.loader.show() );

					var arguments = {
						item_id: this.options.item_id,
						item_type: this.options.item_type,
						reaction_id: targetElement.data( 'reaction-id' ),
						page: targetElement.data( 'paged' )
					};

					var selected_collection = arguments.item_id + '_' + arguments.reaction_id;

					if ( 'undefined' == typeof bp.Nouveau.ActivityReaction.collections[ selected_collection ] ) {
						bp.Nouveau.ActivityReaction.collections[ selected_collection ] = new bp.Collections.ActivityReactionCollection();
					}

					this.collection = bp.Nouveau.ActivityReaction.collections[ selected_collection ];

					this.args.collection = this.collection;
					this.collection.fetch(
						{
							data: _.pick( arguments, [ 'page', 'per_page', 'item_id', 'item_type', 'reaction_id' ] ),
							success: _.bind( this.onTabChangeSuccessRender, this, targetElement ),
							error: _.bind( this.onTabChangeFailedRender, this, targetElement ),
						}
					);
				}
			},

			onTabChangeSuccessRender: function ( targetElement, collection, response ) {
				if ( targetElement.find( '.reaction-loader' ) ) {
					targetElement.find( '.reaction-loader' ).remove();
				}

				if ( response.success ) {
					var models = this.collection.toJSON();

					_.each(
						models,
						function ( model ) {
							var reactionItemView = new bp.Views.ReactionItem( { model: model } );
							targetElement.find( '.activity-state_users' ).append( reactionItemView.render().el );
						}
					);
				} else {
					this.onTabChangeFailedRender( targetElement, collection, response );
				}

				return this;
			},

			onTabChangeFailedRender: function( targetElement, collection, response ) {
				if ( targetElement.find( '.reaction-loader' ) ) {
					targetElement.find( '.reaction-loader' ).remove();
				}

				if (
					'undefined' !== typeof response.statusText &&
					'abort' === response.statusText
				) {
					return;
				}

				// Prepare the object to pass into views.
				var args = {
					data: ( ! response.success ) ? response.data : {},
				};

				// Remove notice.
				targetElement.find( '.activity_reaction_popup_error' ).remove();

				// Render popup heading.
				var popupHeadingView = new bp.Views.ReactionErrorHandle( args );
				targetElement.append( popupHeadingView.render().el );
			}
		}
	);

	bp.Views.ReactionPopupLists = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity-state-popup_tab_content',
			template: bp.template( 'activity-reacted-popup-tab-content' ),
			initialize: function ( options ) {
				this.options = options;
				this.data    = options.data;
			},
			render: function() {
				this.$el.html( this.template( this.data ) );
				return this;
			}
		}
	);

	bp.Views.ReactionItem = Backbone.View.extend(
		{
			tagName: 'li',
			className: 'activity-state_user',
			template: bp.template( 'activity-reacted-item' ),
			render: function() {
				this.$el.html( this.template( this.model ) );
				return this;
			}
		}
	);

	// View for a popup error handle.
	bp.Views.ReactionErrorHandle = Backbone.View.extend(
		{
			tagName: 'div',
			className: 'activity_reaction_popup_error',
			template: bp.template( 'activity-reacted-no-data' ),
			initialize: function ( options ) {
				this.data = options.data;
			},
			render: function () {
				var response = this.data;

				if ( 'undefined' === typeof response.message || 0 >= response.message.length ) {
					// Prepare the object to pass into views.
					response.message = BP_Nouveau.activity.strings.reactionAjaxError;
				}
				this.$el.html( this.template( response ) );

				return this;
			},
		}
	);

	// Launch BP Nouveau Subscriptions.
	bp.Nouveau.ActivityReaction.start();

})( bp, jQuery );
