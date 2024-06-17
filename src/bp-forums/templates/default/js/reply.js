/* globals tinyMCE */
var addReply = {

	/**
	 * Move the reply form when "Reply" is clicked.
	 *
	 * @since 2.6.2
	 * @param {string} replyId
	 * @param {string} parentId
	 * @param {string} respondId
	 * @param {string} postId
	 * @returns {undefined|Boolean}
	 */
	moveForm: function ( replyId, parentId, respondId, postId ) {

		/* Get initial elements */
		var t       = this,
			reply   = t.getElement( replyId ),
			respond = t.getElement( respondId ),
			cancel  = t.getElement( 'bbp-cancel-reply-to-link' ),
			parent  = t.getElement( 'bbp_reply_to' ),
			post    = t.getElement( 'bbp_topic_id' );

		/* Remove the editor, if its already been moved */
		t.removeEditor();

		/* Bail to avoid errors */
		if ( ! reply || ! respond || ! cancel || ! parent ) {
			return;
		}

		t.respondId = respondId;
		postId      = postId || false;

		/* Setup a temporary div for relocating back when clicking cancel */
		if ( ! t.getElement( 'bbp-temp-form-div' ) ) {
			var div = document.createElement( 'div' );

			div.id            = 'bbp-temp-form-div';
			div.style.display = 'none';

			respond.parentNode.appendChild( div );
		}

		/* Relocate the element */
		reply.parentNode.appendChild( respond );

		if ( post && postId ) {
			post.value = postId;
		}

		parent.value         = parentId;
		cancel.style.display = '';

		/* Add the editor where it now belongs */
		t.addEditor();

		/**
		 * When canceling a Reply.
		 *
		 * @since 2.6.2
		 * @returns {void}
		 */
		cancel.onclick = function () {
			t.cancelForm( this );
		};

		t.scrollToForm();

		/* Prevent click from going through */
		return false;
	},

	/**
	 * Cancel the reply form.
	 *
	 * @since 2.6.6
	 * @returns {void}
	 */
	cancelForm: function () {
		var r       = addReply,
			temp    = r.getElement( 'bbp-temp-form-div' ),
			cancel  = r.getElement( 'bbp-cancel-reply-to-link' ),
			respond = r.getElement( r.respondId );

		r.removeEditor();

		/* Allow click to go through */
		if ( ! temp || ! respond ) {
			return;
		}

		r.getElement( 'bbp_reply_to' ).value = '0';

		temp.parentNode.insertBefore( respond, temp );
		temp.parentNode.removeChild( temp );

		cancel.style.display = 'none';
		cancel.onclick       = null;

		r.addEditor();
		r.scrollToForm();

		/* Prevent click from going through */
		return false;
	},

	/**
	 * Scrolls to the top of the page.
	 *
	 * @since 2.6.2
	 * @return {void}
	 */
	scrollToForm: function() {

		/* Get initial variables to start computing boundaries */
		var t           = this,
			form        = t.getElement( 'new-post' ),
			elemRect    = form.getBoundingClientRect(),
			position    = (window.pageYOffset || document.scrollTop)  - (document.clientTop || 0),
			destination = ( position + elemRect.top ),
			negative    = ( destination < position ), // jshint ignore:line
			adminbar    = t.getElement( 'wpadminbar'),
			offset      = 0;

		/* Offset by the adminbar */
		if ( adminbar && ( typeof ( adminbar ) !== 'undefined' ) ) {
			offset = adminbar.scrollHeight;
		}

		/* Compute the difference, depending on direction */
		/* jshint ignore:start */
		distance = ( true === negative )
			? ( position - destination )
			: ( destination - position );

		/* Do some math to compute the animation steps */
		var vast       = ( distance > 800 ),
			speed_step = vast ? 30 : 20,
			speed      = Math.min( 12, Math.round( distance / speed_step ) ),
			step       = Math.round( distance / speed_step ),
			steps      = [],
			timer      = 0;

		/* Scroll up */
		if ( true === negative ) {
			while ( position > destination ) {
				position -= step;

				if ( position < destination ) {
					position = destination;
				}

				steps.push( position - offset );

				setTimeout( function() {
					window.scrollTo( 0, steps.shift() );
				}, timer * speed );

				timer++;
			}

			/* Scroll down */
		} else {
			while ( position < destination ) {
				position += step;

				if ( position > destination ) {
					position = destination;
				}

				steps.push( position - offset );

				setTimeout( function() {
					window.scrollTo( 0, steps.shift() );
				}, timer * speed );

				timer++;
			}
		}
		/* jshint ignore:end */
	},

	/**
	 * Get an element by ID
	 *
	 * @since 2.6.2
	 * @param {string} e
	 * @returns {HTMLElement} Element
	 */
	getElement: function (e) {
		return document.getElementById(e);
	},

	/**
	 * Remove the Editor
	 *
	 * @since 2.6.2
	 * @returns {void}
	 */
	removeEditor: function () {

		/* Bail to avoid error */
		if ( typeof ( tinyMCE ) === 'undefined' ) {
			return;
		}

		var tmce = tinyMCE.get( 'bbp_reply_content' );

		if ( tmce && ! tmce.isHidden() ) {
			this.mode = 'tmce';
			tmce.remove();

		} else {
			this.mode = 'html';
		}
	},

	/**
	 * Add the Editor
	 *
	 * @since 2.6.2
	 * @returns {void}
	 */
	addEditor: function () {

		/* Bail to avoid error */
		if ( typeof ( tinyMCE ) === 'undefined' ) {
			return;
		}

		if ( 'tmce' === this.mode ) {
			window.switchEditors.go( 'bbp_reply_content', 'tmce' );

		} else if ( 'html' === this.mode ) {
			window.switchEditors.go( 'bbp_reply_content', 'html' );
		}
	}
};
