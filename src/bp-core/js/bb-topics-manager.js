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
			modalSelector           : '#bb-activity-topic-form_modal',
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
			closeModalSelector      : '.bb-model-close-button, #bb_topic_cancel',
			submitButtonSelector    : '#bb_topic_submit',
			editTopicSelector       : '.bb-edit-topic',
			deleteTopicSelector     : '.bb-delete-topic',
			errorContainerSelector  : '.bb-hello-error',
			errorContainer          : '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>',
			topicActionsButton      : '.bb-topic-actions-wrapper .bb-topic-actions_button',

			// Classes.
			modalOpenClass : 'activity-modal-open',

			// AJAX actions.
			addTopicAction    : 'bb_add_topic',
			editTopicAction   : 'bb_edit_topic',
			deleteTopicAction : 'bb_delete_topic',

			// Other settings.
			topicsLimit : bbTopicsManagerVars.topics_limit,
			ajaxUrl     : bbTopicsManagerVars.ajax_url,
		},

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

							var topicLists = BBTopicsManager.topicLists;
							if ( ! _.isUndefined( this.model.get( 'topics' ) ) ) {
								topicLists = ! _.isUndefined( this.model.get( 'topics' ).topic_lists ) ? this.model.get( 'topics' ).topic_lists : [];
							} else if ( ! _.isUndefined( bp.draft_activity.data.topics ) ) {
								topicLists = ! _.isUndefined( bp.draft_activity.data.topics.topic_lists ) ? bp.draft_activity.data.topics.topic_lists : [];
							}

							this.model.set( 'topics', {
								topic_id    : topicId,
								topic_name  : topicName,
								topic_lists : topicLists
							} );

							this.listenTo( Backbone, 'topic:update', this.updateTopics );

							// Add document-level click handler
							$( document ).on( 'click.topicSelector', $.proxy( this.closeTopicSelectorDropdown, this ) );

						},

						updateTopics : function ( topics ) {

							// Fix to handle various formats of incoming topics data
							var topicsArray;

							// Check if topics is an object with topic_lists property
							if ( _.isObject( topics ) && ! _.isUndefined( topics.topic_lists ) ) {
								topicsArray = topics.topic_lists;
							}

							var topicId   = ! _.isUndefined( this.model.get( 'topics' ) ) ? this.model.get( 'topics' ).topic_id : 0;
							var topicName = ! _.isUndefined( this.model.get( 'topics' ) ) ? this.model.get( 'topics' ).topic_name : '';

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
									topic_id: '',
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
								$container.after( '<div class="bb-topics-sort-success">' + response.data.message + '</div>' );
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
			this.$modal.find(this.config.errorContainerSelector).remove();

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
		handleCloseModal : function ( event ) {
			// Prevent default action and stop event propagation.
			event.preventDefault();
			event.stopPropagation();

			$( 'body' ).removeClass( this.config.modalOpenClass );

			this.$modal.hide();
			this.$backdrop.hide();

			// Reset data.
			this.$topicName.val( '' );
			this.$topicWhoCanPost.prop( 'checked', false );
			this.$topicId.val( '' );
			$( '#bb_is_global_activity' ).val( '' );
			this.$topicName.prop( 'readonly', false );
			this.$topicName.prop( 'disabled', false );

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
			var confirmMessage = bbTopicsManagerVars.delete_topic_confirm.replace( '%s', topicName );

			if ( confirm( confirmMessage ) ) {

				// Prepare data for AJAX request.
				var data = {
					action    : this.config.deleteTopicAction,
					topic_id  : topicId,
					nonce     : nonce,
					item_id   : itemId,
					item_type : itemType
				};

				// Use the configured AJAX URL.
				var ajaxUrl = this.config.ajaxUrl;

				// Send AJAX request
				$.post( ajaxUrl, data, function ( response ) {
					if ( response.success ) {
						$topicItem.remove();
						// Check if we need to show the "Add new topic" button after deletion.
						BBTopicsManager.checkTopicsLimit();
					} else {
						alert( response.data.error );
					}
				}.bind( this ) );
			}
		},

		/**
		 * Check if we've reached the maximum number of topics.
		 *
		 * @return {boolean} True if limit reached, false otherwise.
		 */
		checkTopicsLimit : function () {
			var topicsCount = this.$topicList.find( '.bb-activity-topic-item' ).length;
			var topicsLimit = this.config.topicsLimit;

			var topicsLimitReached = topicsCount >= topicsLimit;

			// If the limit is reached, hide the add button.
			if ( topicsLimitReached ) {
				this.$addTopicButton.hide();
			} else {
				// If we're below the limit, show the add button.
				this.$addTopicButton.show();
				if ( this.$addTopicButton.hasClass( 'bp-hide' ) ) {
					this.$addTopicButton.removeClass( 'bp-hide' );
				}
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
			if ( BBTopicsManager.isEnabledActivityTopic && BP_Nouveau.activity.params.topics.topic_lists.length > 0 ) {
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

				$( document ).on( 'bb_draft_activity_loaded', function ( event, activity_data ) {
					if ( activity_data && activity_data.topics ) {
						bp.Nouveau.Activity.postForm.model.set( 'topics', activity_data.topics );
						bp.draft_activity.data.topics = activity_data.topics;

						if ( '' !== activity_data.topics.topic_id ) {
							var $topicElement = $('.bb-topic-selector-list a[data-topic-id="' + activity_data.topics.topic_id + '"]');
							if ($topicElement.length > 0) {
								$topicElement.addClass('selected');
								var topicName = activity_data.topics.topic_name;
								if (!topicName) {
									topicName = $topicElement.text();
								}
								$('.bb-topic-selector-button').text(topicName);
							}
						}
					}
				} );

				this.$document.on( 'click', '.activity-topic-selector li a', this.topicActivityFilter.bind( this ) );

				this.$document.ready( this.handleUrlHashTopic.bind( this ) );

				this.$document.on( 'click', '.bb-topic-url', this.topicActivityFilter.bind( this ) );
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

			var $topicItem     = $( event.currentTarget );
			var topicId        = $topicItem.data( 'topic-id' );
			var topicUrl       = $topicItem.attr( 'href' );
			var $filterBarLink = $( '.activity-topic-selector li a[data-topic-id="' + topicId + '"]' );
			var $newMainBarItem;

			if ( $topicItem.closest( 'li' ).hasClass( 'menu-item-has-children' ) ) {
				return;
			}

			// Extract hash from full URL if present.
			var topicHash = '';
			if ( -1 !== topicUrl.indexOf( '#' ) ) {
				// Are we on the main activity/news feed?
				var isMainFeed = BP_Nouveau.activity.params.topics.is_activity_directory;
				if ( isMainFeed ) {
					if ( $topicItem.closest( 'li.groups' ).length > 0 ) {
						window.location.href = topicUrl;
						return;
					}
				}
				topicHash = topicUrl.substring( topicUrl.indexOf( '#' ) );
			}

			// Update the URL to include the topic slug (or remove it for "All").
			if ( history.pushState ) {
				var newUrl;
				if ( ! topicId || $topicItem.hasClass( 'all' ) || 'all' === topicHash.toLowerCase() ) {
					newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname;
				} else {
					newUrl = window.location.protocol + '//' + window.location.host + window.location.pathname + topicHash;
				}
				window.history.pushState( { path : newUrl }, '', newUrl );
			}

			// Remove all selected/active classes.
			$( '.activity-topic-selector li a' ).removeClass( 'selected active' );

			if ( $filterBarLink.length ) {
				var $clickedListItem = $filterBarLink.closest( 'li' );
				var isDropdownItem   = $clickedListItem.closest( '.bb_nav_more_dropdown' ).length > 0;

				if ( isDropdownItem ) {
					// Move from dropdown to main bar
					this.moveTopicPosition( {
						$topicItem : $filterBarLink,
						topicId    : topicId
					} );
					// After move, select the new main bar item
					$newMainBarItem = $( '.activity-topic-selector li a[data-topic-id="' + topicId + '"]' ).first();
					$newMainBarItem.addClass( 'selected active' );
				} else {
					// Just add classes, do not move
					$filterBarLink.addClass( 'selected active' );
				}
			}

			// Store the topic ID in BP's storage.
			if ( ! topicId || $topicItem.hasClass( 'all' ) || 'all' === topicUrl.toLowerCase() ) {
				bp.Nouveau.setStorage( 'bp-activity', 'topic_id', '' );
				var $allItem = $( '.activity-topic-selector li a' ).first();
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
			if ( window.location.hash && window.location.hash.startsWith( '#topic-' ) ) {
				var topicSlug = window.location.hash.substring( 1 ); // Remove the # symbol.

				// Find the topic link with matching href.
				var $topicLink = $( '.activity-topic-selector li a[href="#' + topicSlug + '"]' );

				if ( $topicLink.length ) {
					// If we found a matching topic, trigger the filter.
					$topicLink.trigger( 'click' );

					// Move the topic position after "All" its for reload.
					BBTopicsManager.moveTopicPosition( {
						$topicItem : $topicLink,
						topicId    : $topicLink.data( 'topic-id' )
					} );

					// Set selected/active classes
					$( '.activity-topic-selector li a' ).removeClass( 'selected active' );
					$topicLink.addClass( 'selected active' );

					// Scroll to the feed [data-bp-list="activity"]
					var $feed = $( '[data-bp-list="activity"]' );
					if ( $feed.length > 0 ) {
						jQuery( 'html, body' ).animate( { scrollTop: jQuery( $feed ).offset().top - 200 }, 300 );
					}
				}
			} else {
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
					! _.isUndefined( data.topics.topic_id ) &&
					0 !== parseInt( data.topics.topic_id )
				) {
					$selector.removeClass( $class );
					$( '#whats-new-submit' ).find( '.bb-topic-tooltip-wrapper' ).remove();
				} else if (
					$validContent &&
					(
						_.isUndefined( data.topics.topic_id ) ||
						0 === parseInt( data.topics.topic_id )
					)
				) {
					$selector.addClass( $class );
					$( document ).trigger( 'bb_display_full_form' ); // Trigger the display full form event to show the tooltip.
				} else if (
					! $validContent &&
					! _.isUndefined( data.topics.topic_id ) &&
					0 !== parseInt( data.topics.topic_id )
				) {
					// If the post is empty and the topic is selected, add the empty class and the tooltip.
					$selector.addClass( $class );
					$( '#whats-new-submit' ).find( '.bb-topic-tooltip-wrapper' ).remove();
				} else if (
					! $validContent &&
					(
						_.isUndefined( data.topics.topic_id ) ||
						0 === parseInt( data.topics.topic_id )
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
				$( this.config.submitButtonSelector ).prop( 'disabled', true );
			} else {
				$( this.config.submitButtonSelector ).prop( 'disabled', false );
			}
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
