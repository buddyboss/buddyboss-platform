/* global wp, bp, BP_Nouveau, _, Backbone */
/* jshint devel: true */
/* @version 3.1.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

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
	bp.Nouveau.Subscriptions = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.views         = new Backbone.Collection();
			this.subscriptions = new bp.Collections.Subscriptions();
			this.types         = [];

			// Listen to events ("Add hooks!").
			this.addListeners();

			this.Initialize();
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
		},

		Initialize: function() {
			this.types = $('.subscription-views .bb-accordion');

			// Create the loop view.
			var subscription_list = new bp.Views.SubscriptionItems( { collection: this.subscriptions, type: 'forum' } );

			this.views.add( { id: 'subscriptions', view: subscription_list } );

			subscription_list.inject( '.bb-accordion_panel' );
		},
	};

	bp.Models.subscriptionItem = Backbone.Model.extend(
		{
			defaults: {
				id: 0,
				user_id: 0,
				type: '0',
				item_id: 0,
				secondary_item_id: '',
				date_recorded: '',
			}
		}
	);

	bp.Collections.Subscriptions = Backbone.Collection.extend(
		{
			model: bp.Models.subscriptionItem,
			options: {},
			subscription_items: null,

			initialize : function() {
				this.options = { page: 1, per_page: 5 };
			},

			sync: function( method, model, options ) {
				options         = options || {};
				options.context = this;
				options.data    = options.data || {};
				options.path    = 'buddyboss/v1/subscription';
				// options.type    = 'GET';

				options.data = _.extend(
					options.data,
					{
					}
				);

				bp.apiRequest( options ).done(
					function( data ) {
						this.subscription_items = data;
					}
				).fail(
					function( error ) {
						this.subscription_items = error;
					}
				);

				return this.subscription_items;
			},

			parse: function( resp ) {
				return resp;
			},
		}
	);

	// Extend wp.Backbone.View with .prepare() and .inject().
	bp.Nouveau.Subscriptions.View = bp.Backbone.View.extend(
		{
			inject: function( selector ) {
				this.render();
				$( selector ).html( this.el );
				this.views.ready();
			},

			prepare: function() {
				if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
					return this.model.toJSON();
				} else {
					return {};
				}
			}
		}
	);

	bp.Views.SubscriptionItems = bp.Nouveau.Subscriptions.View.extend(
		{
			tagName  : 'div',
			className  : 'subscription-items-main',
			events: {

			},
			loader : false,

			initialize: function() {
				this.loader = new bp.Views.SubscriptionLoading();
				this.views.add( this.loader );

				this.requestSubscriptions();

				var Views = [
					new bp.Nouveau.Subscriptions.View( { tagName: 'ul', id: 'subscription-items', className: 'subscription-items' } )
				];

				_.each(
					Views,
					function( view ) {
						this.views.add( view );
					},
					this
				);

				this.collection.on( 'add', this.addThread, this );
			},

			requestSubscriptions: function() {
				if ( _.isUndefined( this.options.type ) ) {
					this.options.data.type = this.options.type;
				}

				this.collection.fetch(
					{
						data    : _.pick( this.options ),
						success : _.bind( this.subscriptionFetched, this ),
						error   : _.bind( this.subscriptionFetchError, this )
					}
				);
			},

			addThread: function( item ) {
				this.views.add( '.subscription-items', new bp.Views.SubscriptionItem( { model: item } ) );
			},

			subscriptionFetched: function() {
				if ( this.loader ) {
					this.loader.remove();
				}
			},

			subscriptionFetchError: function( collection, response ) {
				if ( this.loader ) {
					this.loader.remove();
				}
			},
		}
	);

	bp.Views.SubscriptionItem = bp.Nouveau.Subscriptions.View.extend(
		{
			tagName: 'li',
			className: 'bb-subscription-item',
			template  : bp.template( 'bb-subscription-item' ),
			initialize: function() {}
		}
	);

	// Loading view.
	bp.Views.SubscriptionLoading = bp.Nouveau.Subscriptions.View.extend(
		{
			tagName: 'div',
			className: '',
			template  : bp.template( 'bb-member-subscription-loading' )
		}
	);

	// Launch BP Nouveau Groups.
	bp.Nouveau.Subscriptions.start();

} )( bp, jQuery );
