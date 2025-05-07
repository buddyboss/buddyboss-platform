/* global bp, BP_Nouveau, _, bbTopicsManagerVars */
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
			nonceSelector			: '#bb_topic_nonce',
			actionFromSelector		: '#bb_action_from',
			addTopicButtonSelector  : '.bb-add-topic',
			closeModalSelector      : '.bb-model-close-button, #bb_topic_cancel',
			submitButtonSelector    : '#bb_topic_submit',
			editTopicSelector       : '.bb-edit-topic',
			deleteTopicSelector     : '.bb-delete-topic',
			errorContainerSelector  : '.bb-hello-error',
			errorContainer          : '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>',
			topicActionsButton 		: '.bb-topic-actions-wrapper .bb-topic-actions_button',

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

		initTopicsManagerFrontend: function() {
			bp.Nouveau = bp.Nouveau || {};

			// Bail if not set.
			if ( typeof bp.Nouveau.Activity === 'undefined' || typeof BP_Nouveau === 'undefined' ) {
				return;
			}

			_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

			bp.Models      = bp.Models || {};
			bp.Collections = bp.Collections || {};
			bp.Views = bp.Views || {};

			bp.Views.TopicSelector = bp.View.extend(
				{
					tagName: 'div',
					className: 'whats-new-topic-selector',
					template: bp.template( 'activity-post-form-topic-selector' ),
					events: {
						'click .bb-topic-selector-button': 'toggleTopicSelectorDropdown',
						'click .bb-topic-selector-list a': 'selectTopic'
					},
		
					initialize: function () {
						this.model.on( 'change', this.render, this ); // TODO: Add specific event to update topic selector
		
						// Add document-level click handler
						$( document ).on( 'click.topicSelector', $.proxy( this.closeTopicSelectorDropdown, this ) );
					},
		
					render: function () {
						this.$el.html( this.template( this.model.attributes ) );
					},
		
					toggleTopicSelectorDropdown: function () {
						this.$el.toggleClass( 'is-active' );
					},
		
					selectTopic: function ( event ) {
						event.preventDefault();
						
						var topicId = $( event.currentTarget ).data( 'topic-id' );
						var topicName = $( event.currentTarget ).text().trim();
		

						this.model.set( 'topic_id', topicId );
						this.model.set( 'topic_name', topicName );
		
						this.$el.find( '.bb-topic-selector-button' ).text( topicName );
						this.$el.removeClass('is-active');
						
						this.$el.find( '.bb-topic-selector-list li a[data-topic-id="' + topicId + '"]' ).addClass( 'selected' );
						
						$( document ).trigger( 'bb_topic_selected', [ topicId ] );
					},
		
					closeTopicSelectorDropdown: function ( event ) {
						// Don't close if clicking inside the topic selector
						if ( $( event.target ).closest( '.whats-new-topic-selector' ).length ) {
							return;
						}
						
						this.$el.removeClass( 'is-active' );
					}
				}
			);

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
			} );
		},

		/**
		 * Make the topics sortable.
		 */
		makeTopicsSortable : function () {
			this.$topicList.sortable( {
				update: function ( event, ui ) {
					console.log( event, ui );
					$( event.target ).addClass( 'is-loading' );

					// Make the AJAX call to update the topics order.
				},
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

			// Clear form fields.
			this.$topicName.val( '' );
			this.$topicId.val( '' );

			// Show modal
			this.$modal.show();
			this.$backdrop.show();

			// Trigger modal opened event.
			$( document ).trigger( 'bb_modal_opened', [ this.$modal ] );
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

			var selectedData    = this.$topicName.data( 'selected' );
			var topicName       = selectedData ? selectedData.name : this.$topicName.val();
			var topicWhoCanPost = this.$topicWhoCanPost.filter( ':checked' ).val();
			var topicId         = this.$topicId.val();
			var itemId          = this.$itemId.val();
			var itemType        = this.$itemType.val();
			var nonce           = this.$nonce.val();
			var actionFrom      = this.$actionFrom.val();
			if ( topicName === '' ) {
				return;
			}

			// Add loading state to modal
			this.$modal.addClass( 'loading' );

			// Prepare data for AJAX request.
			var data = {
				action          : this.config.addTopicAction,
				name            : topicName,
				permission_type : topicWhoCanPost,
				topic_id        : topicId,
				item_id         : itemId,
				item_type       : itemType,
				nonce           : nonce,
				action_from     : actionFrom
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
				// Remove loading state
				this.$modal.removeClass( 'loading' );
				
				if ( response.success ) {
					window.location.reload();
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

			// Trigger modal closed event.
			$( document ).trigger( 'bb_modal_closed', [ this.$modal ] );
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

			var $button   = $( event.currentTarget );
			var topicAttr = $button.data( 'topic-attr' );
			var topicId   = topicAttr.topic_id;
			var itemId    = topicAttr.item_id;
			var itemType  = topicAttr.item_type;
			var nonce 	  = topicAttr.nonce;

			// Add modal open class.
			$( 'body' ).addClass( this.config.modalOpenClass );

			// Show modal.
			this.$modal.show();
			this.$backdrop.show();
			this.$topicWhoCanPost.prop( 'checked', false );
			
			$( document ).trigger( 'bb_modal_opened', [ this.$modal ] );

			// Remove any existing error messages.
			var errorElm = this.$modal.find( this.config.errorContainerSelector );
			if ( errorElm.length > 0 ) {
				errorElm.remove();
			}

			// Prepare data for AJAX request.
			var data = {
				action    : this.config.editTopicAction,
				topic_id  : topicId,
				item_id   : itemId,
				item_type : itemType,
				nonce     : nonce
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl || wp.ajax.settings.url;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
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
					} else {
						// For plain input.
						this.$topicName.val( topic.name );
						this.$topicName.prop('readonly', topic.is_global_activity);
					}
					this.$topicWhoCanPost.filter( '[value="' + topic.permission_type + '"]' ).prop( 'checked', true );
					this.$topicId.val( topic.topic_id );
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

			var $button    = $( event.currentTarget );
			var $topicItem = $button.closest( '.bb-activity-topic-item' );
			var topicAttr  = $button.data( 'topic-attr' );
			var topicId    = topicAttr.topic_id;
			var nonce      = topicAttr.nonce;
			var itemId     = topicAttr.item_id;
			var itemType   = topicAttr.item_type;

			if ( confirm( bbTopicsManagerVars.delete_topic_confirm ) ) {

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
						$topicItem.fadeOut( 300, function () {
							$( this ).remove();
							// Check if we need to show the "Add new topic" button after deletion.
							this.checkTopicsLimit();
						}.bind( this ) );
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
			if ( bbTopicsManagerVars.bb_is_activity_topic_required ) {
				this.$document.on( 'mouseenter focus', '#whats-new-submit', this.showTopicTooltip.bind( this ) );
				this.$document.on( 'mouseleave blur', '#whats-new-submit', this.hideTopicTooltip.bind( this ) );

				this.addTopicTooltip = false;
				// Add topic tooltip.
				$( document ).on( 'bb_display_full_form', function () {
					if ( $( '.activity-update-form #whats-new-submit .bb-topic-tooltip-wrapper' ).length === 0 ) {
						$( '.activity-update-form.modal-popup #whats-new-submit' ).prepend( '<div class="bb-topic-tooltip-wrapper"><div class="bb-topic-tooltip">' + bbTopicsManagerVars.topic_tooltip_error + '</div></div>' );
					}
				} );

				$( document ).on( 'postValidate', function ( event, data ) {
					var $topicName = $( '.whats-new-topic-selector' ).find( '.bb-topic-selector-list li a.selected' );
					if ( data.contentEmpty && ! $topicName.length ) {
						this.addTopicTooltip = true;
						$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
					} else if ( ! data.contentEmpty && ! $topicName.length ) {
						this.addTopicTooltip = false;
						$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
					} else if ( ! data.contentEmpty && $topicName.length ) {
						this.addTopicTooltip = false;
						$( '.activity-update-form.modal-popup #whats-new-form' ).removeClass( 'focus-in--empty' );
					}
				} );

				$( document ).on( 'bb_topic_selected', function ( event, topicID ) {
					if ( topicID && ! this.addTopicTooltip ) {
						$( '.activity-update-form.modal-popup #whats-new-form' ).removeClass( 'focus-in--empty' );
					} else {
						$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
					}
				});

				$( document ).on( 'bb_draft_activity_loaded', function ( event, activity_data ) {
					if ( activity_data.topic_id ) {
						$( '.activity-update-form.modal-popup #whats-new-form' ).removeClass( 'focus-in--empty' );
					} else {
						$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
					}
				} );
			}
		},

		showTopicTooltip : function ( event ) {
			var $wrapper   = $( event.currentTarget ),
			    $postBtn   = $wrapper.closest( '#whats-new-submit' ),
			    $topicName = $wrapper.closest( '#activity-form-submit-wrapper' ).find( '.bb-topic-selector-list li a.selected' );

			if ( $postBtn.closest( '.focus-in--empty' ).length > 0 ) {
				if ( ! $topicName.length ) {
					$postBtn.find( '.bb-topic-tooltip-wrapper' ).addClass( 'active' ).show();
					$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
				} else if ( this.addTopicTooltip && $topicName.length ) {
					$postBtn.find( '.bb-topic-tooltip-wrapper' ).removeClass( 'active' ).hide();
					$( '.activity-update-form.modal-popup #whats-new-form' ).addClass( 'focus-in--empty' );
				} else if ( ! this.addTopicTooltip && $topicName.length ) {
					$postBtn.find( '.bb-topic-tooltip-wrapper' ).removeClass( 'active' ).hide();
				}
			}

		},

		hideTopicTooltip : function () {
			$( '.bb-topic-tooltip-wrapper' ).removeClass( 'active' ).hide();
		},
	};

	$(
		function () {
			BBTopicsManager.start();
		}
	);

	// Make the manager available globally.
	window.BBTopicsManager = BBTopicsManager;

} )( jQuery );