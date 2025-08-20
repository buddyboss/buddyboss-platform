/* global bp, BP_Nouveau, _, Backbone, bbTopicsManagerVars */
/* @version 1.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function ( $ ) {

	/**
	 * Topics Manager.
	 *
	 * A global manager for topics functionality that can be used
	 * in both admin and frontend contexts.
	 *
	 * @type {Object}
	 */
	var BBTopicsManager = {

		/**
		 * Default configuration.
		 */
		config : {
			// Selectors.
			topicListSelector       : '.bb-activity-topics-list',
			modalSelector           : '#bb-rl-activity-topic-form_modal',
			modalContentSelector    : '.bb-action-popup-content',
			backdropSelector        : '#bb-hello-backdrop-activity-topic',
			topicNameSelector       : '#bb_topic_name',
			topicWhoCanPostSelector : 'input[name="bb_permission_type"]',
			topicIdSelector         : '#bb_topic_id',
			itemIdSelector          : '#bb_item_id',
			itemTypeSelector        : '#bb_item_type',
			nonceSelector           : '#bb_topic_nonce',
			actionFromSelector      : '#bb_action_from',
			addTopicButtonSelector  : '.bb-add-topic',
			closeModalSelector      : '.bb-model-close-button, .bb-hello-activity-topic #bb_topic_cancel, .bb-hello-activity-topic-migrate #bb_topic_cancel',
			submitButtonSelector    : '#bb_topic_submit',
			editTopicSelector       : '.bb-edit-topic',
			deleteTopicSelector     : '.bb-delete-topic',
			errorContainerSelector  : '.bb-hello-error',
			errorContainer          : '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>',
			topicActionsButton      : '.bb-topic-actions-wrapper .bb-topic-actions_button',
			migrateTopicButtonSelector      : '#bb_topic_migrate',
			migrateTopicBackdropModal       : '#bb-hello-backdrop-activity-topic-migrate',
			migrateTopicContainerModal      : '#bb-activity-topic-migrate-form_modal',

			// Classes.
			modalOpenClass : 'activity-modal-open',

			// AJAX actions.
			addTopicAction     : 'bb_add_topic',
			editTopicAction    : 'bb_edit_topic',
			deleteTopicAction  : 'bb_delete_topic',
			migrateTopicAction : 'bb_migrate_topic',

			// Other settings.
			topicsLimit : bbTopicsManagerVars.topics_limit,
			ajaxUrl     : bbTopicsManagerVars.ajax_url,
		},

		/**
		 * Navigation flag to prevent infinite loops.
		 */
		isNavigating : false,

		start : function () {
			this.init();
			this.initTopicsManagerFrontend();
		},

		/**
		 * Initialize the manager with custom configuration.
		 *
		 * @param {Object} customConfig - Custom configuration to override defaults.
		 */
		init : function ( customConfig ) {
			// Merge custom config with defaults.
			if ( customConfig ) {
				$.extend( true, this.config, customConfig );
			}

			// Set up DOM elements.
			this.setupElements();

			// Add event listeners.
			this.addListeners();

			// Make the topics sortable.
			this.makeTopicsSortable();

			// Check topics limit on initialization.
			this.checkTopicsLimit();
		},

		initTopicsManagerFrontend : function () {
			bp.Nouveau = bp.Nouveau || {};

			// Bail if not set.
			if ( typeof bp.Nouveau.Activity === 'undefined' || typeof BP_Nouveau === 'undefined' ) {
				return;
			}

			_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

			bp.Models      = bp.Models || {};
			bp.Collections = bp.Collections || {};
			bp.Views       = bp.Views || {};

			var activityParams           = BP_Nouveau.activity.params;
			this.isEnabledActivityTopic  = ! _.isUndefined( activityParams.topics.bb_is_enabled_activity_topics ) ? activityParams.topics.bb_is_enabled_activity_topics : false;
			this.isActivityTopicRequired = ! _.isUndefined( activityParams.topics.bb_is_activity_topic_required ) ? activityParams.topics.bb_is_activity_topic_required : false;
			this.isActivityTopicRequired = ! _.isUndefined( activityParams.topics.bb_is_activity_topic_required ) ? activityParams.topics.bb_is_activity_topic_required : false;
			this.topicTooltipError       = this.isEnabledActivityTopic ? ! _.isUndefined( activityParams.topics.topic_tooltip_error ) ? activityParams.topics.topic_tooltip_error : false : false;
			this.topicLists              = this.isEnabledActivityTopic ? ! _.isUndefined( activityParams.topics.topic_lists ) ? activityParams.topics.topic_lists : [] : [];

			if ( typeof bp.View !== 'undefined' ) {
				bp.Views.TopicSelector = bp.View.extend(
					{
						tagName   : 'div',
						className : 'whats-new-topic-selector',
						template  : bp.template( 'bb-activity-post-form-topic-selector' ),
						events    : {
							'click .bb-topic-selector-button' : 'toggleTopicSelectorDropdown',
							'click .bb-topic-selector-list a' : 'selectTopic'
						},

						initialize : function () {
							var topicId = 0;
							if ( ! _.isUndefined( this.model.get( 'topics' ) ) ) {
								topicId = ! _.isUndefined( this.model.get( 'topics' ).topic_id ) ? this.model.get( 'topics' ).topic_id : 0;
							} else if ( ! _.isUndefined( bp.draft_activity.data.topics ) ) {
								topicId = ! _.isUndefined( bp.draft_activity.data.topics.topic_id ) ? bp.draft_activity.data.topics.topic_id : 0;
							}

							var topicName = '';
							if ( ! _.isUndefined( this.model.get( 'topics' ) ) ) {
								topicName = ! _.isUndefined( this.model.get( 'topics' ).topic_name ) ? this.model.get( 'topics' ).topic_name : '';
							} else if ( ! _.isUndefined( bp.draft_activity.data.topics ) ) {
								topicName = ! _.isUndefined( bp.draft_activity.data.topics.topic_name ) ? bp.draft_activity.data.topics.topic_name : '';
							}

							var topicLists = [];
							var modelTopics = this.model.get( 'topics' );
							
							// Priority order: model data > draft data > global data.
							if ( modelTopics && ! _.isUndefined( modelTopics.topic_lists ) ) {
								topicLists = modelTopics.topic_lists;
							} else if ( ! _.isUndefined( bp.draft_activity.data.topics ) && ! _.isUndefined( bp.draft_activity.data.topics.topic_lists ) ) {
								topicLists = bp.draft_activity.data.topics.topic_lists;
							} else {
								topicLists = BBTopicsManager.topicLists;
							}

							this.model.set( 'topics', {
								topic_id    : topicId,
								topic_name  : topicName,
								topic_lists : topicLists
							} );

							this.listenTo( Backbone, 'topic:update', this.updateTopics );

							// Listen to model changes for topics.
							this.listenTo( this.model, 'change:topics', function( model, topics ) {
								if ( topics ) {
									this.updateTopics( topics );
								}
							} );

							// Add document-level click handler
							$( document ).on( 'click.topicSelector', $.proxy( this.closeTopicSelectorDropdown, this ) );

						},

						updateTopics : function ( topics ) {

							// Fix to handle various formats of incoming topics data.
							var topicsArray;

							// Check if topics is an object with topic_lists property.
							if ( _.isObject( topics ) && ! _.isUndefined( topics.topic_lists ) ) {
								topicsArray = topics.topic_lists;
							}

							var currentTopics = this.model.get( 'topics' );
							var topicId       = currentTopics ? currentTopics.topic_id : 0;
							var topicName     = currentTopics ? currentTopics.topic_name : '';

							// If the incoming topics object has topic_id and topic_name, use them.
							if ( _.isObject( topics ) ) {
								if ( ! _.isUndefined( topics.topic_id ) ) {
									topicId = topics.topic_id;
								}
								if ( ! _.isUndefined( topics.topic_name ) ) {
									topicName = topics.topic_name;
								}
							}

							// Update the model with the new topics
							this.model.set( 'topics', {
								topic_lists : topicsArray,
								topic_id    : topicId,
								topic_name  : topicName
							} );

							// Remove the topic tooltip if there are no topics.
							if ( _.isEmpty( topicsArray ) ) {
								// Try multiple selectors to ensure we catch all instances
								$( '.bb-topic-tooltip-wrapper' ).remove();
							} else {
								// Add topic tooltip while group topics are loaded.
								$( document ).trigger( 'bb_display_full_form' );
							}

							// Trigger input event on #whats-new to trigger postValidate.
							if (
								'undefined' !== typeof bp.Nouveau.Activity &&
								bp.Nouveau.Activity.postForm
							) {
								$( '#whats-new' ).trigger( 'input' );
							}

							this.render();
						},

						render : function () {
							this.$el.html( this.template( this.model.attributes ) );
						},

						toggleTopicSelectorDropdown : function () {
							this.$el.toggleClass( 'is-active' );
						},

						selectTopic : function ( event ) {
							event.preventDefault();

							var topicId   = $( event.currentTarget ).data( 'topic-id' );
							var topicName = $( event.currentTarget ).text().trim();

							if ( '' === topicId ) {
								this.model.set('topics', {
									topic_id: 0,
									topic_name: '', // This will trigger the template to show "Select Topic"
									topic_lists: this.model.get('topics').topic_lists
								});
								topicName = this.$el.find( '.bb-topic-selector-button' ).data( 'select-topic-text' );
							} else {
								this.model.set('topics', {
									topic_id: topicId,
									topic_name: topicName,
									topic_lists: this.model.get('topics').topic_lists
								});
							}

							this.$el.find( '.bb-topic-selector-button' ).text( topicName );
							this.$el.removeClass( 'is-active' );

							this.$el.find('.bb-topic-selector-list li a').removeClass('selected');
							if ( '' !== topicId ) {
								this.$el.find( '.bb-topic-selector-list li a[data-topic-id="' + topicId + '"]' ).addClass( 'selected' );
							}

							// Trigger input event on #whats-new to trigger postValidate.
							if (
								typeof bp.Nouveau.Activity !== 'undefined' &&
								bp.Nouveau.Activity.postForm
							) {
								$( '#whats-new' ).trigger( 'input' );
							}
						},

						closeTopicSelectorDropdown : function ( event ) {
							// Don't close if clicking inside the topic selector
							if ( $( event.target ).closest( '.whats-new-topic-selector' ).length ) {
								return;
							}

							this.$el.removeClass( 'is-active' );
						}
					}
				);
			}

			this.addFrontendListeners();
		},

		/**
		 * Set up DOM elements
		 */
		setupElements : function () {
			this.$document        = $( document );
			this.$topicList       = $( this.config.topicListSelector );
			this.$modal           = $( this.config.modalSelector );
			this.$backdrop        = $( this.config.backdropSelector );
			this.$topicName       = $( this.config.topicNameSelector );
			this.$topicWhoCanPost = $( this.config.topicWhoCanPostSelector );
			this.$topicId         = $( this.config.topicIdSelector );
			this.$addTopicButton  = $( this.config.addTopicButtonSelector );
			this.$nonce           = $( this.config.nonceSelector );
			this.$itemId          = $( this.config.itemIdSelector );
			this.$itemType        = $( this.config.itemTypeSelector );
			this.$actionFrom      = $( this.config.actionFromSelector );
			
			// Migrate topic elements.
			this.$migrateTopicBackdropModalSelector  = $( this.config.migrateTopicBackdropModal );
			this.$migrateTopicContainerModalSelector = $( this.config.migrateTopicContainerModal );
		},

		/**
		 * Add event listeners
		 */
		addListeners : function () {
			// Add topic button click - directly bind to the handler.
			this.$document.on( 'click', this.config.addTopicButtonSelector, this.handleAddTopic.bind( this ) );

			// Submit button click - directly bind to the handler.
			this.$document.on( 'click', this.config.submitButtonSelector, this.handleSubmitTopic.bind( this ) );

			// Close modal button click - directly bind to the handler.
			this.$document.on( 'click', this.config.closeModalSelector, this.handleCloseModal.bind( this ) );

			// Edit topic button click - directly bind to the handler.
			this.$document.on( 'click', this.config.editTopicSelector, this.handleEditTopic.bind( this ) );

			// Delete topic button click - directly bind to the handler.
			this.$document.on( 'click', this.config.deleteTopicSelector, this.handleDeleteTopic.bind( this ) );

			// Handle actions dropdown.
			this.$document.on( 'click', this.config.topicActionsButton, this.handleActionsDropdown.bind( this ) );

			// Close actions dropdown
			this.$document.on( 'click', '.bb-topic-actions-wrapper .bp-secondary-action', this.closeActionsDropdown.bind( this ) );

			// Close context menu dropdown when clicking outside
			this.$document.on( 'click', function ( e ) {
				if ( ! $( e.target ).closest( '.bb-topic-actions-wrapper' ).length ) {
					$( '.bb-topic-actions-wrapper' ).removeClass( 'active' );
				}
			});

			// Disable submit button if no topic is selected.
			this.$document.on( 'change', '.bb-topic-name-field', this.enableDisableSubmitButton.bind( this ) );
			this.$document.on( 'keyup', '#bb_topic_name, .bb-topic-name-field', this.enableDisableSubmitButton.bind( this ) );
			
			this.$document.on( 'change', 'input[name="bb_migrate_existing_topic"]', this.enableDisableMigrateTopicButton.bind( this ) );
			this.$document.on( 'change', '#bb_existing_topic_id', this.updateMigrateButtonForDropdown.bind( this ) );
			this.$document.on( 'click', this.config.migrateTopicButtonSelector, this.handleMigrateTopic.bind( this ) );
		},

		/**
		 * Make the topics sortable.
		 */
		makeTopicsSortable : function () {
			var self = this;
			if ( ! this.$topicList.length ) {
				return;
			}
			this.$topicList.sortable( {
				update : function ( event ) {
					var $container = $( event.target );
					$container.addClass( 'is-loading' );

					// Get the sorted topic IDs.
					var topicIds = [];
					$container.find( '.bb-activity-topic-item' ).find( '.bb-edit-topic' ).each( function () {
						var $topicData = $( this ).data( 'topic-attr' );
						var topicId    = $topicData.topic_id;
						if ( topicId ) {
							topicIds.push( topicId );
						}
					} );

					// Prepare data for AJAX request.
					var data = {
						action    : 'bb_update_topics_order',
						topic_ids : topicIds,
						nonce     : bbTopicsManagerVars.bb_update_topics_order_nonce
					};

					// Make the AJAX call to update topics order.
					$.post( self.config.ajaxUrl, data, function ( response ) {
						$container.removeClass( 'is-loading' );

						if ( response.success ) {
							// Show success notification if needed.
							if ( response.data && response.data.message ) {
								// Display success message.
								$container.after( '<div class="bb-topics-sort-success notice notice-success"><p>' + response.data.message + '</p></div>' );
								setTimeout( function () {
									$( '.bb-topics-sort-success' ).fadeOut( 300, function () {
										$( this ).remove();
									} );
								}, 3000 );
							}
						} else {
							// Show error and revert if needed.
							if ( response.data && response.data.error ) {
								alert( response.data.error );
							}
							// Optionally revert the sort.
							$container.sortable( 'cancel' );
						}
					}.bind( this ) ).fail( function () {
						$container.removeClass( 'is-loading' );
						alert( bbTopicsManagerVars.generic_error );
						$container.sortable( 'cancel' );
					} );
				}
			} );
		},

		/**
		 * Handle adding a new topic.
		 *
		 * @param {Event} event - The click event.
		 */
		handleAddTopic : function ( event ) {

			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			if ( this.checkTopicsLimit() ) {
				return;
			}

			$( 'body' ).addClass( this.config.modalOpenClass );

			// Remove any existing error messages.
			this.$modal.find( this.config.errorContainerSelector ).remove();
			
			if ( this.$modal.find( '.bb-model-header h4 .target_name' ).length ) {
				this.$modal.find( '.bb-model-header h4 .target_name' ).text( bbTopicsManagerVars.create_topic_text );
			}
			if ( this.$modal.find( '.bb-hello-title h2' ).length ) {
				this.$modal.find( '.bb-hello-title h2' ).text( bbTopicsManagerVars.create_topic_text );
			}

			// Clear form fields.
			this.$topicName.val( '' );
			this.$topicId.val( '' );
			this.$topicWhoCanPost.first().prop( 'checked', true );

			// Show modal
			this.$modal.show();
			this.$backdrop.show();

			// Trigger modal opened event.
			$( document ).trigger( 'bb_modal_opened', [this.$modal] );
		},

		/**
		 * Handle submitting a topic.
		 *
		 * @param {Event} event - The click event.
		 */
		handleSubmitTopic : function ( event ) {
			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			// Remove any existing error messages.
			this.$modal.find( this.config.errorContainerSelector ).remove();

			var selectedData     = this.$topicName.data( 'selected' );
			var topicName        = selectedData ? selectedData.name : this.$topicName.val();
			var topicWhoCanPost  = this.$topicWhoCanPost.filter( ':checked' ).val();
			var topicId          = this.$topicId.val();
			var itemId           = this.$itemId.val();
			var itemType         = this.$itemType.val();
			var nonce            = this.$nonce.val();
			var actionFrom       = this.$actionFrom.val();
			var isGlobalActivity = $( '#bb_is_global_activity' ).val();
			if ( topicName === '' ) {
				return;
			}

			// Add loading state to modal
			this.$modal.addClass( 'loading' );

			// Prepare data for AJAX request.
			var data = {
				action             : this.config.addTopicAction,
				name               : topicName,
				permission_type    : topicWhoCanPost,
				topic_id           : topicId,
				item_id            : itemId,
				item_type          : itemType,
				nonce              : nonce,
				action_from        : actionFrom,
				is_global_activity : isGlobalActivity
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
				// Remove loading state
				this.$modal.removeClass( 'loading' );
				if ( response.success && response.data.content ) {
					var content                 = response.data.content;
					var topicData               = content.topic;
					var currentTopicId          = topicData.topic_id;
					var currentTopicName        = topicData.name;
					var currentTopicWhoCanPost  = topicData.permission_type;
					var currentItemId           = topicData.item_id;
					var currentItemType         = topicData.item_type;
					var currentTopicNonce       = content.edit_nonce;
					var currentTopicDeleteNonce = content.delete_nonce;
					var isGlobalActivity        = ! _.isUndefined( topicData.is_global_activity ) ? topicData.is_global_activity : false;
					var topicListsTemplate      = wp.template( 'bb-topic-lists' );

					var collectedTopicData = {
						topic_id           : currentTopicId,
						topic_name         : currentTopicName,
						topic_who_can_post : currentTopicWhoCanPost,
						item_id            : currentItemId,
						item_type          : currentItemType,
						edit_nonce         : currentTopicNonce,
						delete_nonce       : currentTopicDeleteNonce
					};

					if ( isGlobalActivity ) {
						collectedTopicData.is_global_activity = isGlobalActivity;
					}

					var renderedTopicLists  = topicListsTemplate( {
						topics : collectedTopicData
					} );
					var $renderedTopicLists = $( renderedTopicLists );

					// Find existing topic item if it exists
					var $existingTopic = this.$topicList.find( '.bb-activity-topic-item[data-topic-id="' + topicId + '"]' );
					if ( $existingTopic.length ) {
						$existingTopic.replaceWith( $renderedTopicLists );
					} else {
						this.$topicList.append( $renderedTopicLists );
					}
					this.handleCloseModal( event );
					this.checkTopicsLimit();
				} else {
					this.$modal.find( this.config.modalContentSelector ).prepend( this.config.errorContainer );
					this.$modal.find( this.config.errorContainerSelector ).append( response.data.error );
				}
			}.bind( this ) );
		},

		/**
		 * Handle closing the modal
		 *
		 * @param {Event} event - The click event
		 */
		handleCloseModal: function ( event ) {
			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			$( 'body' ).removeClass( this.config.modalOpenClass );

			if (
				this.$modal.hasClass( 'bb-modal-panel--activity-topic' ) ||
				this.$modal.hasClass( 'bb-action-popup--activity-topic' )
			) {
				this.$modal.hide();
				this.$backdrop.hide();
			}

			if (
				this.$migrateTopicContainerModalSelector.hasClass( 'bb-modal-panel--activity-topic-migrate' ) ||
				this.$migrateTopicContainerModalSelector.hasClass( 'bb-action-popup--activity-migrate-topic' )
			) {
				this.$migrateTopicBackdropModalSelector.hide();
				this.$migrateTopicContainerModalSelector.hide();

				// Reset data.
				$( 'input[name="bb_migrate_existing_topic"]:first' ).prop( 'checked', true );
				$( '#bb_existing_topic_id option:not(:first)' ).prop( 'selected', false );
			}

			// Reset data.
			this.$topicName.val( '' );
			this.$topicWhoCanPost.prop( 'checked', false );
			this.$topicId.val( '' );
			$( '#bb_is_global_activity' ).val( '' );
			this.$topicName.prop( 'readonly', false );
			this.$topicName.prop( 'disabled', false );
			$( this.config.submitButtonSelector ).prop( 'disabled', true );

			// Trigger modal closed event.
			$( document ).trigger( 'bb_modal_closed', [this.$modal] );
		},

		/**
		 * Handle editing a topic.
		 *
		 * @param {Event} event - The click event.
		 */
		handleEditTopic : function ( event ) {
			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			var $button          = $( event.currentTarget );
			var topicAttr        = $button.data( 'topic-attr' );
			var topicId          = topicAttr.topic_id;
			var itemId           = topicAttr.item_id;
			var itemType         = topicAttr.item_type;
			var nonce            = topicAttr.nonce;
			var isGlobalActivity = topicAttr.bb_is_global_activity;

			// Add modal open class.
			$( 'body' ).addClass( this.config.modalOpenClass );
			
			if ( this.$modal.find( '.bb-model-header h4 .target_name' ).length ) {
				this.$modal.find( '.bb-model-header h4 .target_name' ).text( bbTopicsManagerVars.edit_topic_text );
			}
			if ( this.$modal.find( '.bb-hello-title h2' ).length ) {
				this.$modal.find( '.bb-hello-title h2' ).text( bbTopicsManagerVars.edit_topic_text );
			}

			// Show modal.
			this.$modal.show();
			this.$backdrop.show();
			this.$topicWhoCanPost.prop( 'checked', false );

			$( document ).trigger( 'bb_modal_opened', [this.$modal] );

			// Remove any existing error messages.
			var errorElm = this.$modal.find( this.config.errorContainerSelector );
			if ( errorElm.length > 0 ) {
				errorElm.remove();
			}

			// Show loader
			this.$modal.addClass( 'is-loading' );

			// Prepare data for AJAX request.
			var data = {
				action             : this.config.editTopicAction,
				topic_id           : topicId,
				item_id            : itemId,
				item_type          : itemType,
				nonce              : nonce,
				is_global_activity : isGlobalActivity
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl || wp.ajax.settings.url;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
				this.$modal.removeClass( 'is-loading' );
				if ( response.success ) {
					var topic = response.data.topic;
					if ( this.$topicName.hasClass( 'select2-hidden-accessible' ) ) {
						// For select2.
						if ( 0 === this.$topicName.find( 'option[value=\'' + topic.slug + '\']' ).length ) {
							var newOption = new Option( topic.name, topic.slug, true, true );
							this.$topicName.append( newOption );
						}
						this.$topicName.val( topic.slug ).trigger( 'change' );
						this.$topicName.prop( 'disabled', topic.is_global_activity );
						if ( topic.is_global_activity ) {
							this.$topicName.closest( '.input-field' ).addClass( 'bb-topic-global-selected' );
						} else {
							this.$topicName.closest( '.input-field' ).removeClass( 'bb-topic-global-selected' );
						}
					} else {
						// For plain input.
						this.$topicName.val( topic.name );
						this.$topicName.prop( 'readonly', topic.is_global_activity );
					}
					this.$topicWhoCanPost.filter( '[value="' + topic.permission_type + '"]' ).prop( 'checked', true );
					this.$topicId.val( topic.topic_id );
					$( '#bb_is_global_activity' ).val( topic.is_global_activity );
					this.handleEnableDisableSubmitButton( topic.name );
				} else {
					this.$modal.find( this.config.modalContentSelector ).prepend( this.config.errorContainer );
					this.$modal.find( this.config.errorContainerSelector ).append( response.data.error );
				}
			}.bind( this ) );
		},

		/**
		 * Handle deleting a topic
		 *
		 * @param {Event} event - The click event
		 */
		handleDeleteTopic : function ( event ) {
			// Prevent default action and stop event propagation
			event.preventDefault();
			event.stopPropagation();

			var $button        = $( event.currentTarget );
			var $topicItem     = $button.closest( '.bb-activity-topic-item' );
			var topicAttr      = $button.data( 'topic-attr' );
			var topicId        = topicAttr.topic_id;
			var nonce          = topicAttr.nonce;
			var itemId         = topicAttr.item_id;
			var itemType       = topicAttr.item_type;
			var topicName      = $topicItem.find( '.bb-topic-title' ).text().trim();

			var $radioValue    = $( 'input[name="bb_migrate_existing_topic"]:checked' ).val();
			var $dropdownValue = $( '#bb_existing_topic_id' ).val();
			this.handleEnableDisableMigrateTopicButton( $radioValue, $dropdownValue );

			// First, check if there are existing posts on this topic.
			this.checkTopicPostsBeforeDelete( {
				topicId    : topicId,
				topicName  : topicName,
				nonce      : nonce,
				itemId     : itemId,
				itemType   : itemType,
				$topicItem : $topicItem
			} );
		},

		/**
		 * Check if topic has existing posts before deletion.
		 *
		 * @param {Object} args - Arguments object containing topic data.
		 */
		checkTopicPostsBeforeDelete : function ( args ) {
			var data = {
				action    : this.config.deleteTopicAction,
				topic_id  : args.topicId,
				nonce     : args.nonce,
				item_id   : args.itemId,
				item_type : args.itemType
			};

			var topicName = args.topicName || '';
			if ( BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb-hello-title' ).length ) {
				BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb-hello-title' ).text( bbTopicsManagerVars.delete_topic_text.replace( '%s', topicName ) );
			}
			if ( BBTopicsManager.$migrateTopicContainerModalSelector.find( '.bb-model-header h4 .target_name' ).length ) {
				BBTopicsManager.$migrateTopicContainerModalSelector.find( '.bb-model-header h4 .target_name' ).text( bbTopicsManagerVars.delete_topic_text.replace( '%s', topicName ) );
			}

			// Show loader
			this.$migrateTopicContainerModalSelector.addClass( 'is-loading' );

			// Add modal open class.
			$( 'body' ).addClass( this.config.modalOpenClass );

			// Show modal.
			this.$migrateTopicContainerModalSelector.show();
			this.$migrateTopicBackdropModalSelector.show();

			// Send initial AJAX request to check for existing posts.
			$.post( this.config.ajaxUrl, data, function ( response ) {
				BBTopicsManager.$migrateTopicContainerModalSelector.removeClass( 'is-loading' );
				if ( response.success && response.data ) {
					var topicData = response.data;

					BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb_topic_id' ).val( topicData.topic_id );
					BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb_item_id' ).val( topicData.item_id );
					BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb_item_type' ).val( topicData.item_type );
					BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb_topic_nonce' ).val( topicData.nonce );
					
					// Clear existing options to prevent duplicates, then append new ones
					var $topicSelect = BBTopicsManager.$migrateTopicContainerModalSelector.find( '#bb_existing_topic_list #bb_existing_topic_id' );
					$topicSelect.find( 'option:not(:first)' ).remove();
					
					if ( topicData.topic_lists ) {
						_.each( topicData.topic_lists, function ( topic ) {
							var option = new Option( topic.name, topic.topic_id );
							$topicSelect.append( option );
						} );
					}
				} else {

				}
			} ).fail( function () {

			}.bind(this));
		},

		handleMigrateTopic : function ( event ) {
			var oldTopicId  = this.$migrateTopicContainerModalSelector.find( '#bb_topic_id' ).val();
			var nonce       = this.$migrateTopicContainerModalSelector.find( '#bb_topic_nonce' ).val();
			var itemId      = this.$migrateTopicContainerModalSelector.find( '#bb_item_id' ).val();
			var itemType    = this.$migrateTopicContainerModalSelector.find( '#bb_item_type' ).val();
			var newTopicId  = this.$migrateTopicContainerModalSelector.find( '#bb_existing_topic_id' ).val();
			var $topicItem  = $( '.bb-activity-topics-list .bb-activity-topic-item[data-topic-id="' + oldTopicId + '"]' );
			var migrateType = $( 'input[name="bb_migrate_existing_topic"]:checked' ).val();

			// Prepare data for AJAX request.
			var data = {
				action       : this.config.migrateTopicAction,
				old_topic_id : oldTopicId,
				nonce        : nonce,
				item_id      : itemId,
				item_type    : itemType,
				migrate_type : migrateType
			};

			if ( 'migrate' === migrateType ) {
				data.new_topic_id = newTopicId;
			}

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl;

			// Show Loader
			$( this.config.migrateTopicButtonSelector ).addClass( 'is-loading' );

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
				$( this.config.migrateTopicButtonSelector ).removeClass( 'is-loading' );
				if ( response.success ) {
					$topicItem.remove();
					this.handleCloseModal( event );
					this.checkTopicsLimit();
				} else {
					this.$migrateTopicContainerModalSelector.find( '.bb-hello-content' ).prepend( this.config.errorContainer );
					this.$migrateTopicContainerModalSelector.find( '.bb-hello-error' ).append( response.data.error );
				}
			}.bind( this ) ).fail( function () {
				this.$migrateTopicContainerModalSelector.find( '.bb-hello-content' ).prepend( this.config.errorContainer );
				this.$migrateTopicContainerModalSelector.find( '.bb-hello-error' ).append( bbTopicsManagerVars.generic_error );
			}.bind( this ) );
		},

		/**
		 * Check if we've reached the maximum number of topics.
		 *
		 * @return {boolean} True if limit reached, false otherwise.
		 */
		checkTopicsLimit : function () {
			var topicsCount      = this.$topicList.find( '.bb-activity-topic-item' ).length;
			var topicsLimit      = this.config.topicsLimit;
			var $limitNotReached = $( '.bb-topic-limit-not-reached' );
			var $limitReached    = $( '.bb-topic-limit-reached' );

			var topicsLimitReached = topicsCount >= topicsLimit;

			// If the limit is reached, hide the add button.
			if ( topicsLimitReached ) {
				this.$addTopicButton.hide();
				$limitNotReached.hide();
				$limitReached.show();
			} else {
				// If we're below the limit, show the add button.
				this.$addTopicButton.show();
				if ( this.$addTopicButton.hasClass( 'bp-hide' ) ) {
					this.$addTopicButton.removeClass( 'bp-hide' );
				}
				if ( 0 === parseInt( topicsCount ) ) {
					$limitNotReached.hide();
				} else {
					$limitNotReached.show();
				}
				$limitReached.hide();
			}

			if ( topicsCount > 0 ) {
				this.$topicList.closest( '.bb-activity-topics-content' ).addClass( 'bb-has-topics' );
			} else {
				this.$topicList.closest( '.bb-activity-topics-content' ).removeClass( 'bb-has-topics' );
			}

			return topicsLimitReached;
		},

		/**
		 * Handle actions dropdown.
		 *
		 * @param {Event} event - The click event.
		 */
		handleActionsDropdown : function ( event ) {
			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			var $currentWrapper = $( event.currentTarget ).closest( '.bb-topic-actions-wrapper' );

			// Close other open dropdowns
			$( '.bb-topic-actions-wrapper.active' ).not( $currentWrapper ).removeClass( 'active' );

			// Toggle current dropdown
			$currentWrapper.toggleClass( 'active' );
		},

		/**
		 * Close actions dropdown.
		 *
		 * @param {Event} event - The click event.
		 */
		closeActionsDropdown : function ( event ) {
			$( event.target ).closest( '.bb-topic-actions-wrapper' ).removeClass( 'active' );
		},

		/**
		 * Add frontend listeners.
		 */
		addFrontendListeners : function () {
			if ( BBTopicsManager.isEnabledActivityTopic ) {
				if ( this.isActivityTopicRequired ) {
					this.$document.on( 'mouseenter focus', '#whats-new-submit', this.showTopicTooltip.bind( this ) );
					this.$document.on( 'mouseleave blur', '#whats-new-submit', this.hideTopicTooltip.bind( this ) );
				}

				if ( BBTopicsManager.isActivityTopicRequired ) {
					// Add topic tooltip.
					this.$document.on( 'bb_display_full_form', function () {
						if ( $( '.activity-update-form #whats-new-submit .bb-topic-tooltip-wrapper' ).length === 0 ) {
							$( '.activity-update-form.modal-popup #whats-new-submit' ).prepend( '<div class="bb-topic-tooltip-wrapper"><div class="bb-topic-tooltip">' + BBTopicsManager.topicTooltipError + '</div></div>' );
						}
					} );
				}

				this.$document.on( 'click', '.activity-topic-selector li a', this.topicActivityFilter.bind( this ) );

				if ( undefined !== BP_Nouveau.is_send_ajax_request && '1' === BP_Nouveau.is_send_ajax_request ) {
					this.$document.ready( this.handleUrlHashTopic.bind( this ) );
				}

				this.$document.on( 'click', '.bb-topic-url', this.topicActivityFilter.bind( this ) );

				// Listen for browser back/forward navigation.
				$( window ).on( 'hashchange', this.handleBrowserNavigation.bind( this ) );
				$( window ).on( 'popstate', this.handleBrowserNavigation.bind( this ) );
			}
		},

		showTopicTooltip : function ( event ) {
			var $wrapper = $( event.currentTarget ),
			    $postBtn = $wrapper.closest( '#whats-new-submit' );

			if ( $postBtn.closest( '.focus-in--empty' ).length > 0 ) {
				$postBtn.find( '.bb-topic-tooltip-wrapper' ).addClass( 'active' ).show();
			}

		},

		hideTopicTooltip : function () {
			$( '.bb-topic-tooltip-wrapper' ).removeClass( 'active' ).hide();
		},

		topicActivityFilter : function ( event ) {
			event.preventDefault();
			event.stopPropagation();

			var $topicItem      = $( event.currentTarget );
			var topicId         = $topicItem.data( 'topic-id' );
			var topicUrl        = $topicItem.attr( 'href' );
			var topicFilterATag = $( '.activity-topic-selector li a' );
			var $filterBarLink  = topicFilterATag.filter( '[data-topic-id="' + topicId + '"]' );
			var $newMainBarItem;

			if ( $topicItem.closest( 'li' ).hasClass( 'menu-item-has-children' ) ) {
				return;
			}

			// Extract topic parameter from URL.
			var topicParam = '', url;
			if ( topicUrl ) {
				try {
					// Create URL object to properly parse the URL
					url        = new URL( topicUrl, window.location.origin );
					topicParam = url.searchParams.get( 'bb-topic' );
				} catch ( e ) {
					// Fallback for older browsers or invalid URLs
					var urlParams = new URLSearchParams( topicUrl.split( '?' )[ 1 ] || '' );
					topicParam    = urlParams.get( 'bb-topic' );
				}
			}

			// Are we on the main activity/news feed?
			var isMainFeed = BP_Nouveau.activity.params.topics.is_activity_directory;
			if ( isMainFeed ) {
				if ( $topicItem.closest( 'li.groups' ).length > 0 ) {
					window.location.href = topicUrl;
					return;
				}
			}

			// Get current URL and construct new URL with topic parameter.
			var currentUrl = window.location.href;
			try {
				// Create URL object to properly handle the URL
				url = new URL( currentUrl );
				if ( ! topicId || $topicItem.hasClass( 'all' ) || 'all' === topicParam ) {
					url.searchParams.delete( 'bb-topic' );
				} else {
					url.searchParams.set( 'bb-topic', topicParam );
				}
				window.history.pushState( {}, '', url.toString() );
			} catch ( e ) {
				// Fallback for older browsers or invalid URLs
				var newUrl = currentUrl.split( '?' )[ 0 ];
				if ( topicId && ! $topicItem.hasClass( 'all' ) && 'all' !== topicParam ) {
					newUrl += '?bb-topic=' + encodeURIComponent( topicParam );
				}
				window.history.pushState( {}, '', newUrl );
			}

			// Remove all selected/active classes.
			topicFilterATag.removeClass( 'selected active' );

			if ( $filterBarLink.length ) {
				var $clickedListItem = $filterBarLink.closest( 'li' );
				var isDropdownItem   = $clickedListItem.closest( '.bb_nav_more_dropdown' ).length > 0;

				if ( isDropdownItem ) {
					// Move from dropdown to main bar.
					this.moveTopicPosition( {
						$topicItem : $filterBarLink,
						topicId    : topicId
					} );
					// After move, select the new main bar item.
					$newMainBarItem = $( '.activity-topic-selector li a[data-topic-id="' + topicId + '"]' ).first();
					$newMainBarItem.addClass( 'selected active' );
				} else {
					// Just add classes, do not move.
					$filterBarLink.addClass( 'selected active' );
				}
			}

			// Store the topic ID in BP's storage.
			if ( ! topicId || $topicItem.hasClass( 'all' ) || 'all' === topicParam ) {
				bp.Nouveau.setStorage( 'bp-activity', 'topic_id', '' );
				var $allItem = topicFilterATag.first();
				if ( $allItem.length > 0 ) {
					$allItem.addClass( 'selected active' );
				}
			} else {
				bp.Nouveau.setStorage( 'bp-activity', 'topic_id', topicId );
			}

			// Use an existing BuddyBoss activity filter system.
			bp.Nouveau.Activity.filterActivity( event );
		},

		handleUrlHashTopic : function () {
			// Get topic slug from URL parameters.
			var topicSlug = new URLSearchParams( window.location.search ).get( 'bb-topic' );

			if ( topicSlug ) {
				var topicFilterATag = $( '.activity-topic-selector li a' );
				// Find the topic link with matching href or data attribute.
				var $topicLink = topicFilterATag.filter( function () {
					var href = $( this ).attr( 'href' ) || '';
					var dataSlug = $( this ).data( 'topic-slug' );
					
					// Extract the topic value from href
					var hrefMatch = href.match(/bb-topic=([^&]+)/);
					var hrefTopicSlug = hrefMatch ? hrefMatch[1] : '';
					
					// Check for exact matches only
					return hrefTopicSlug === topicSlug || dataSlug === topicSlug;
				} );

				if ( $topicLink.length ) {
					// If we found a matching topic, trigger the filter.
					$topicLink.trigger( 'click' );

					// Move the topic position after "All" for reload.
					BBTopicsManager.moveTopicPosition( {
						$topicItem : $topicLink,
						topicId    : $topicLink.data( 'topic-id' )
					} );

					// Set selected/active classes.
					topicFilterATag.removeClass( 'selected active' );
					$topicLink.addClass( 'selected active' );

					// Store the topic ID in BP's storage.
					bp.Nouveau.setStorage( 'bp-activity', 'topic_id', $topicLink.data( 'topic-id' ) );

					// Scroll to the feed [data-bp-list="activity"].
					var $feed = $( '[data-bp-list="activity"]' );
					if ( $feed.length > 0 ) {
						$( 'html, body' ).animate( {
							scrollTop : $feed.offset().top - 200
						}, 300 );
					}
				}
			} else {
				// No topic selected, reset to "All".
				bp.Nouveau.setStorage( 'bp-activity', 'topic_id', '' );
				var $allItem = $( '.activity-topic-selector li a' ).first();
				if ( $allItem.length > 0 ) {
					$allItem.addClass( 'selected active' );
				}
			}
		},

		moveTopicPosition : function ( args ) {
			var $topicItem = args.$topicItem,
			    topicId    = args.topicId;

			// Get topic container and elements.
			var $topicSelector = $( '.activity-topic-selector' ),
			    $topicList     = $topicSelector.find( '> ul' ),
			    $moreButton    = $topicList.find( 'li:has(a.more-action-button)' ); // Find the "More" button

			// If this is a click on a dropdown item.
			var isDropdownItem = $topicItem.closest( '.bb_nav_more_dropdown' ).length > 0,
			    $clickedListItem;

			// If not "All", and it's a dropdown item, perform reordering.
			if ( topicId && ! $topicItem.hasClass( 'all' ) && isDropdownItem ) {
				// Get the last visible topic (last one before More button, which will be moved to dropdown).
				var $lastVisibleItem = $moreButton.prev( 'li' );

				// For the clicked item in the dropdown, clone it with all data and events
				$clickedListItem       = $topicItem.closest( 'li' );
				var $clonedClickedItem = $clickedListItem.clone( true );

				// Insert the cloned dropdown item after "All".
				$topicList.find( 'li:first-child' ).after( $clonedClickedItem );

				// For the last visible item, clone it with all data and events.
				var $clonedLastItem = $lastVisibleItem.clone( true );

				// Find the dropdown and add the cloned last item to it.
				// Get the dropdown directly instead of relying on a class.
				var $dropdown = $( '.more-action-button' ).closest( 'li' ).find( 'ul' );
				if ( ! $dropdown.length ) {
					// If not found, try another approach to find the dropdown.
					$dropdown = $( 'ul.bb_nav_more_dropdown' );
				}

				if ( $dropdown.length ) {
					$dropdown.prepend( $clonedLastItem );

					// Remove the clicked item from dropdown - more reliable approach.
					// Find all items in dropdown with the same topic ID.
					$dropdown.find( 'li a' ).each( function () {
						var $a = $( this );
						if ( $a.data( 'topic-id' ) === topicId ) {
							$a.closest( 'li' ).remove();
						}
					} );

					// Remove the last visible item.
					$lastVisibleItem.remove();

					// Add a delay to ensure the DOM has been updated.
					setTimeout( function () {
						// Double-check and remove any duplicate items with this topic ID from dropdown.
						$dropdown.find( 'li a' ).each( function () {
							var $a = $( this );
							if ( $a.data( 'topic-id' ) === topicId ) {
								$a.closest( 'li' ).remove();
							}
						} );
					}, 100 );

					// recalculate available width for the dropdown.
					bp.Nouveau.wrapNavigation( '.activity-topic-selector ul', 120, true );
				}
			}

			// Fallback: If not dropdown and not "All", ensure the topic is after "All"
			if ( topicId && ! $topicItem.hasClass( 'all' ) && ! isDropdownItem ) {
				$clickedListItem = $topicItem.closest( 'li' );
				var $allItem     = $topicList.find( 'li:first-child' );
				// Only move if not already after "All"
				if ( ! $clickedListItem.is( $allItem.next() ) ) {
					$clickedListItem.insertAfter( $allItem );
				}
			}
		},

		bbTopicValidateContent : function ( args ) {
			var $selector     = args.selector,
			    $validContent = args.validContent,
			    $class        = args.class,
			    data          = args.data;
				
			var topic_id = '';
			if (
				! _.isUndefined( data.topics ) &&
				! _.isUndefined( data.topics.topic_id ) &&
				0 !== parseInt( data.topics.topic_id )
			) {
				topic_id = data.topics.topic_id;
			} else {
				var topicSelector = $( '#buddypress .whats-new-topic-selector .bb-topic-selector-list li' );
				if ( topicSelector.length ) {
					var topicId   = topicSelector.find( 'a.selected' ).data( 'topic-id' ) || 0;
					topic_id = topicId;
				}
			}

			// Need to check if the poll is enabled and the poll_id is set.
			// It will mainly use when we change the topic from the topic selector.
			if (
				! _.isUndefined( data.poll ) &&
				! _.isUndefined( data.poll_id ) &&
				'' !== data.poll_id
			) {
				$validContent = true;
			}
			if (
				! _.isUndefined( data.topics ) &&
				! _.isUndefined( data.topics.topic_lists ) &&
				data.topics.topic_lists.length > 0
			) {
				// If the post is not empty and the topic is selected, remove the empty class and the tooltip.
				if (
					$validContent &&
					! _.isUndefined( topic_id ) &&
					0 !== parseInt( topic_id )
				) {
					$selector.removeClass( $class );
					$( '#whats-new-submit' ).find( '.bb-topic-tooltip-wrapper' ).remove();
				} else if (
					$validContent &&
					(
						_.isUndefined( topic_id ) ||
						0 === parseInt( topic_id )
					)
				) {
					$selector.addClass( $class );
					$( document ).trigger( 'bb_display_full_form' ); // Trigger the display full form event to show the tooltip.
				} else if (
					! $validContent &&
					! _.isUndefined( topic_id ) &&
					0 !== parseInt( topic_id )
				) {
					// If the post is empty and the topic is selected, add the empty class and the tooltip.
					$selector.addClass( $class );
					$( '#whats-new-submit' ).find( '.bb-topic-tooltip-wrapper' ).remove();
				} else if (
					! $validContent &&
					(
						_.isUndefined( topic_id ) ||
						0 === parseInt( topic_id )
					)
				) {
					// If the post is empty and the topic is not selected, add the empty class and the tooltip.
					$selector.addClass( $class );
					$( document ).trigger( 'bb_display_full_form' ); // Trigger the display full form event to show the tooltip.
				} else {
					$selector.removeClass( $class );
				}
			}
		},

		enableDisableSubmitButton : function ( event ) {
			var value = $( event.currentTarget ).val();
			this.handleEnableDisableSubmitButton( value );
		},

		handleEnableDisableSubmitButton : function ( value ) {
			if ( '' === value ) {
				if ( this.$migrateTopicContainerModalSelector && this.$migrateTopicContainerModalSelector.is(':visible') ) {
					$( this.config.migrateTopicButtonSelector ).prop( 'disabled', true );
				}
				if ( this.$modal && this.$modal.is(':visible') ) {
					$( this.config.submitButtonSelector ).prop( 'disabled', true );
				}
			} else {
				if ( this.$migrateTopicContainerModalSelector && this.$migrateTopicContainerModalSelector.is(':visible') ) {
					$( this.config.migrateTopicButtonSelector ).prop( 'disabled', false );
				}
				if ( this.$modal && this.$modal.is(':visible') ) {
					$( this.config.submitButtonSelector ).prop( 'disabled', false );
				}
			}
		},

		enableDisableMigrateTopicButton : function ( event ) {
			var value          = $( event.currentTarget ).val();
			var $dropdownValue = $('#bb_existing_topic_id').val();
			this.handleEnableDisableMigrateTopicButton( value, $dropdownValue );
		},

		updateMigrateButtonForDropdown : function ( event ) {
			var selectedRadio  = $('input[name="bb_migrate_existing_topic"]:checked').val();
			var $dropdownValue = $( event.currentTarget ).val();
			this.handleEnableDisableMigrateTopicButton( selectedRadio, $dropdownValue );
		},

		handleEnableDisableMigrateTopicButton : function ( value, $dropdownValue ) {
			if (
				'delete' === value ||
				(
					'migrate' === value &&
					0 !== parseInt( $dropdownValue )
				)
			) {
				$( this.config.migrateTopicButtonSelector ).prop( 'disabled', false );
			} else {
				$( this.config.migrateTopicButtonSelector ).prop( 'disabled', true );
			}
		},

		handleBrowserNavigation : function ( event ) {
			// Set navigation flag to prevent URL updates.
			this.isNavigating = true;

			// Get topic slug from URL parameters.
			var topicSlug = new URLSearchParams( window.location.search ).get( 'bb-topic' );
			var topicFilterATag;
			if ( topicSlug ) {
				topicFilterATag = $( '.activity-topic-selector li a' );
				// Find the topic link with matching href or data attribute.
				var $topicLink = topicFilterATag.filter( function () {
					var href = $( this ).attr( 'href' ) || '';
					var dataSlug = $( this ).data( 'topic-slug' );
					
					// Extract the topic value from href
					var hrefMatch = href.match(/bb-topic=([^&]+)/);
					var hrefTopicSlug = hrefMatch ? hrefMatch[1] : '';
					
					// Check for exact matches only
					return hrefTopicSlug === topicSlug || dataSlug === topicSlug;
				} );

				if ( $topicLink.length ) {
					var topicId = $topicLink.data( 'topic-id' );

					// Remove all selected/active classes first.
					topicFilterATag.removeClass( 'selected active' );

					// Move the topic position if needed.
					var $clickedListItem = $topicLink.closest( 'li' );
					var isDropdownItem   = $clickedListItem.closest( '.bb_nav_more_dropdown' ).length > 0;

					if ( isDropdownItem ) {
						// Move from dropdown to main bar.
						this.moveTopicPosition( {
							$topicItem : $topicLink,
							topicId    : topicId
						} );
						// After move, select the new main bar item.
						var $newMainBarItem = $( '.activity-topic-selector li a[data-topic-id="' + topicId + '"]' ).first();
						$newMainBarItem.addClass( 'selected active' );
					} else {
						// Just add classes, do not move.
						$topicLink.addClass( 'selected active' );
					}

					// Store the topic ID in BP's storage.
					bp.Nouveau.setStorage( 'bp-activity', 'topic_id', topicId );

					// Trigger the activity filter without preventing default (since we're handling navigation).
					bp.Nouveau.Activity.filterActivity( event );
				}
			} else {
				topicFilterATag = $( '.activity-topic-selector li a' );
				// No hash means "All" topics.
				topicFilterATag.removeClass( 'selected active' );
				bp.Nouveau.setStorage( 'bp-activity', 'topic_id', '' );
				var $allItem = topicFilterATag.first();
				if ( $allItem.length > 0 ) {
					$allItem.addClass( 'selected active' );
				}

				// Trigger the activity filter to show all topics.
				bp.Nouveau.Activity.filterActivity( event );
			}

			// Reset navigation flag after a short delay.
			var self = this;
			setTimeout( function () {
				self.isNavigating = false;
			}, 100 );
		}
	};

	$(
		function () {
			BBTopicsManager.start();
		}
	);

	// Make the manager available globally.
	window.BBTopicsManager = BBTopicsManager;

} )( jQuery );
