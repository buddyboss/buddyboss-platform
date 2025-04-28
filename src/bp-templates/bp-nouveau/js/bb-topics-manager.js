/* global bbTopicsManagerVars */
( function ( $ ) {

	/**
	 * Topics Manager.
	 *
	 * A global manager for topics functionality that can be used
	 * in both admin and frontend contexts.
	 *
	 * @type {Object}
	 */
	var TopicsManager = {

		/**
		 * Default configuration.
		 */
		config : {
			// Selectors.
			topicListSelector       : '.bb-activity-topics-list',
			modalSelector           : '#bb-activity-topic-form_modal',
			modalContentSelector    : '.bb-action-popup-content',
			backdropSelector        : '#bb-hello-backdrop-activity-topic',
			topicNameSelector       : '#bb_activity_topic_name',
			topicWhoCanPostSelector : 'input[name="bb_activity_topic_who_can_post"]',
			topicIdSelector         : '#bb_activity_topic_id',
			addTopicButtonSelector  : '.bb-add-topic',
			closeModalSelector      : '.bb-model-close-button, #activity_topic_cancel',
			submitButtonSelector    : '#bb_activity_topic_submit',
			editTopicSelector       : '.bb-edit-activity-topic',
			deleteTopicSelector     : '.bb-delete-activity-topic',
			errorContainerSelector  : '.bb-hello-error',
			errorContainer          : '<div class="bb-hello-error"><i class="bb-icon-rf bb-icon-exclamation"></i>',

			// Classes.
			modalOpenClass : 'activity-modal-open',

			// AJAX actions.
			addTopicAction    : 'bb_add_activity_topic',
			editTopicAction   : 'bb_edit_activity_topic',
			deleteTopicAction : 'bb_delete_activity_topic',

			// Other settings.
			topicsLimit : bbTopicsManagerVars.topics_limit,
			ajaxUrl     : bbTopicsManagerVars.ajax_url,
		},

		start : function () {
			this.init();
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

			// Check topics limit on initialization.
			this.checkTopicsLimit();
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

			var topicName       = this.$topicName.val();
			var topicWhoCanPost = $( 'input[name="bb_activity_topic_who_can_post"]:checked' ).val();
			var topicId         = this.$topicId.val();
			var nonce           = $( '#bb_activity_topic_nonce' ).val();

			if ( topicName === '' ) {
				return;
			}

			// Remove any existing error messages.
			var $topicNameField = this.$modal.find( '#bb_activity_topic_name' ).closest( this.config.modalContentSelector );
			$topicNameField.find( '.bb-hello-error' ).remove();

			// Prepare data for AJAX request.
			var data = {
				action          : this.config.addTopicAction,
				name            : topicName,
				permission_type : topicWhoCanPost,
				topic_id        : topicId,
				nonce           : nonce
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
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

			var $button = $( event.currentTarget );
			var topicId = $button.data( 'topic-id' );
			var nonce   = $button.data( 'nonce' );

			// Add modal open class.
			$( 'body' ).addClass( this.config.modalOpenClass );

			// Show modal.
			this.$modal.show();
			this.$backdrop.show();

			// Remove any existing error messages.
			var errorElm = this.$modal.find( this.config.errorContainerSelector );
			if ( errorElm.length > 0 ) {
				errorElm.remove();
			}

			// Prepare data for AJAX request.
			var data = {
				action   : this.config.editTopicAction,
				topic_id : topicId,
				nonce    : nonce
			};

			// Use the configured AJAX URL.
			var ajaxUrl = this.config.ajaxUrl || wp.ajax.settings.url;

			// Send AJAX request.
			$.post( ajaxUrl, data, function ( response ) {
				if ( response.success ) {
					var topic = response.data.topic;
					this.$topicName.val( topic.name );
					this.$topicWhoCanPost.prop( 'checked', false );
					this.$topicWhoCanPost.find( 'input[name="bb_activity_topic_who_can_post"][value="' + topic.permission_type + '"]' ).prop( 'checked', true );
					this.$topicId.val( topic.id );
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

			var $topicItem = $( event.currentTarget ).closest( '.bb-activity-topic-item' );
			var topicId    = $( event.currentTarget ).data( 'topic-id' );
			var nonce      = $( event.currentTarget ).data( 'nonce' );

			if ( confirm( bbTopicsManagerVars.delete_topic_confirm ) ) {

				// Prepare data for AJAX request.
				var data = {
					action   : this.config.deleteTopicAction,
					topic_id : topicId,
					nonce    : nonce
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
	};

	$(
		function () {
			TopicsManager.start();
		}
	);

	// Make the manager available globally.
	window.TopicsManager = TopicsManager;

} )( jQuery );