/* global bp, BP_Mentions_Options */

window.bp = window.bp || {};

( function ( bp, $, undefined ) {
	var mentionsQueryCache = [],
		mentionsItem, keyCode;

	bp.mentions       = bp.mentions || {};
	bp.mentions.users = window.bp.mentions.users || [];

	if ( typeof window.BP_Suggestions === 'object' ) {
		if ( window.BP_Suggestions.friends && window.BP_Suggestions.friends.length > 0 ) {
			bp.mentions.users = window.BP_Suggestions.friends;
		} else if ( window.BP_Suggestions.members && window.BP_Suggestions.members.length > 0 ) {
			bp.mentions.users = window.BP_Suggestions.members;
		} else if ( bp.mentions.users && bp.mentions.users.length > 0 ) {
			bp.mentions.users = bp.mentions.users;
		}
	}

	bp.mentions.xhr        = null;
	bp.mentions.xhr_scroll = null;

	/**
	 * Adds BuddyPress @mentions to form inputs.
	 *
	 * @param {array|object} options If array, becomes the suggestions' data source. If object, passed as config to $.atwho().
	 * @since BuddyPress 2.1.0
	 */
	$.fn.bp_mentions = function ( options, suggestions, mentions ) {
		if ( $.isArray( options ) ) {
			options = { data: options };
		}

		if ( ! suggestions ) {
			suggestions = {};
		}

		if ( ! mentions ) {
			mentions = {};
		}

		// Auto-suggestion field.
		var userAgent = navigator.userAgent.toLowerCase();
		// we can slo use this - userAgent.indexOf('mobile');
		var isAndroid = userAgent.indexOf( 'android' ) > -1;
		var isChrome  = userAgent.indexOf( 'chrome' ) > -1;

		/**
		 * Default options for at.js; see https://github.com/ichord/At.js/.
		 */
		var suggestionsDefaults = $.extend(
			true,
			{},
			{
				delay: 500,
				hideWithoutSuffix: true,
				insertTpl: BP_Mentions_Options.insert_tpl,
				limit: 100,
				startWithSpace: false,

				callbacks: {
					/**
					 * Custom filter to only match the start of spaced words.
					 * Based on the core/default one.
					 *
					 * @param {string} query
					 * @param {array} data
					 * @param {string} search_key
					 * @return {array}
					 * @since BuddyPress 2.1.0
					 */
					filter: function ( query, data, search_key ) {
						var item, _i, _len, _results = [],
							regxp = new RegExp( '^' + query + '| ' + query, 'ig' ); // start of string, or preceded by a space.

						for ( _i = 0, _len = data.length; _i < _len; _i++ ) {
							item = data[ _i ];
							if ( item[ search_key ].toLowerCase().match( regxp ) ) {
								_results.push( item );
							}
						}

						return _results;
					},

					/**
					 * Removes some spaces around highlighted string and tweaks regex to allow spaces
					 * (to match display_name). Based on the core default.
					 *
					 * @param {unknown} li
					 * @param {string} query
					 * @return {string}
					 * @since BuddyPress 2.1.0
					 */
					highlighter: function ( li, query ) {
						if ( ! query ) {
							return li;
						}

						var regexp = new RegExp( '>(\\s*|[\\w\\s]*)(' + this.at.replace( '+', '\\+' ) + '?' + query.replace( '+', '\\+' ) + ')([\\w ]*)\\s*<', 'ig' );
						return li.replace(
							regexp,
							function ( str, $1, $2, $3 ) {
								return '>' + $1 + '<strong>' + $2 + '</strong>' + $3 + '<';
							}
						);
					},

					/**
					 * Reposition the suggestion list dynamically.
					 *
					 * @param {unknown} offset
					 * @since BuddyPress 2.1.0
					 */
					before_reposition: function ( offset ) {
						// get the iframe, if any, already applied with atwho.
						var caret,
							line,
							iframeOffset,
							move,
							$view          = $( '#atwho-ground-' + this.id + ' .atwho-view' ),
							$body          = $( 'body' ),
							atwhoDataValue = this.$inputor.data( 'atwho' );

						if ( 'undefined' !== atwhoDataValue && 'undefined' !== atwhoDataValue.iframe && null !== atwhoDataValue.iframe ) {
							caret = this.$inputor.caret( 'offset', { iframe: atwhoDataValue.iframe } );
							// Caret.js no longer calculates iframe caret position from the window (it's now just within the iframe).
							// We need to get the iframe offset from the window and merge that into our object.
							iframeOffset = $( atwhoDataValue.iframe ).offset();
							if ( 'undefined' !== iframeOffset ) {
								caret.left += iframeOffset.left;
								caret.top  += iframeOffset.top;
							}
						} else {
							caret = this.$inputor.caret( 'offset' );
						}

						// If the caret is past horizontal half, then flip it, yo.
						if ( caret.left > ( $body.width() / 2 ) ) {
							$view.addClass( 'right' );
							move = caret.left - offset.left - this.view.$el.width();
						} else {
							$view.removeClass( 'right' );
							move = caret.left - offset.left + 1;
						}

						// If we're on a small screen, scroll to caret.
						if ( $body.width() <= 400 ) {
							$( document ).scrollTop( caret.top - 6 );
						}

						// New position is under the caret (never above) and positioned to follow.
						// Dynamic sizing based on the input area (remove 'px' from end).
						line = parseInt( this.$inputor.css( 'line-height' ).substr( 0, this.$inputor.css( 'line-height' ).length - 2 ), 10 );
						if ( ! line || line < 5 ) { // sanity check, and catch no line-height.
							line = 19;
						}

						offset.top   = caret.top + line;
						offset.left += move;
					},

					/**
					 * Override default behaviour which inserts junk tags in the WordPress Visual editor.
					 *
					 * @param {unknown} $inputor Element which we're inserting content into.
					 * @param {string} content The content that will be inserted.
					 * @param {string} suffix Applied to the end of the content string.
					 * @return {string}
					 * @since BuddyPress 2.1.0
					 */
					inserting_wrapper: function ( $inputor, content ) {
						return '' + content + '';
					}
				}
			},
			suggestions
		);

		/**
		 * Default options for our @mentions; see https://github.com/ichord/At.js/.
		 */
		var mentionsDefaults = $.extend(
			true,
			{},
			{
				callbacks: {
					/**
					 * If there are no matches for the query in this.data, then query BuddyPress.
					 *
					 * @param {string} query Partial @mention to search for.
					 * @param {function} render_view Render page callback function.
					 * @since BuddyPress 2.1.0
					 * @since BuddyPress 3.0.0. Renamed from "remote_filter" for at.js v1.5.4 support.
					 */
					remoteFilter: function ( query, render_view ) {
						var params                 = { 'action': 'bp_get_suggestions', 'term': query, 'type': 'members' };
						var mentions_dropdown      = this.$el;
						var $self                  = this;
						var mentions_dropdown_list = mentions_dropdown.find( 'ul' );

						var mention_scroll = function() {
							if (
								mentions_dropdown_list.scrollTop() +
								mentions_dropdown_list.innerHeight() >=
								mentions_dropdown_list[ 0 ].scrollHeight &&
								true === $self.allow_scroll
							) {

								if (
									mentions_dropdown_list.hasClass( 'list-loading' ) ||
									( $self.total_pages != null && parseInt( $self.total_pages ) === parseInt( params.page ) ) ||
									bp.mentions.xhr_scroll
								) {
									return;
								}

								mentions_dropdown_list.addClass( 'list-loading' );
								if ( ! mentions_dropdown_list.find( 'li:last-child' ).hasClass( 'list-loader' ) ) {
									mentions_dropdown_list.append( '<li class="list-loader">Loading more results…</li>' );
								}

								params.term = $self.s_query;
								params.page = mentions_dropdown.data( 'page' ) + 1;
								mentions_dropdown.data( 'page', params.page );

								bp.mentions.xhr_scroll = $.getJSON( ajaxurl, params ).done(
									function ( response ) {
										if ( ! response.success ) {
											return;
										}

										bp.mentions.xhr_scroll = null;

										if ( typeof response.data.total_pages != 'undefined' ) {
											$self.total_pages = parseInt( response.data.total_pages );
										}

										mentionsQueryCache[ $self.s_query ].page = params.page;

										var new_data = $.map(
											response.data.results,
											/**
											 * Create a composite index to determine ordering of results;
											 * nicename matches will appear on top.
											 *
											 * @param {array} suggestion A suggestion's original data.
											 * @return {array} A suggestion's new data.
											 * @since BuddyPress 2.1.0
											 */
											function ( suggestion ) {
												suggestion.search = suggestion.search ||
													suggestion.ID + ' ' +
													suggestion.name;
												return suggestion;
											}
										);

										if ( new_data.length == 0 ) {
											mentions_dropdown_list.find( '.list-loader' ).remove();
											mentions_dropdown_list.removeClass( 'list-loading' );
											$self.allow_scroll = false;
											return;
										}

										$self.data = $self.data.concat( new_data );
										mentions_dropdown_list.find( '.list-loader' ).remove();
										$self.render_view( $self.data );
										mentionsQueryCache[ $self.s_query ].data = $self.data;
										mentions_dropdown_list.removeClass( 'list-loading' );
									}
								);
							}
						};

						$self.allow_scroll = true;
						$self.render_view  = render_view;
						$self.total_pages  = 1;
						$self.s_query      = query;
						mentions_dropdown.data( 'page', 1 );

						if ( bp.mentions.xhr ) {
							bp.mentions.xhr.abort();
						}

						if ( bp.mentions.xhr_scroll ) {
							bp.mentions.xhr_scroll.abort();
						}

						mentionsItem = mentionsQueryCache[ query ];
						if ( typeof mentionsItem === 'object' && mentionsItem.data ) {
							mentions_dropdown.data( 'page', mentionsItem.page );
							$self.data = mentionsItem.data;
							$self.render_view( mentionsItem.data );
							$self.total_pages = mentionsItem.total_pages;

							if ( mentionsItem.page < mentionsItem.total_pages && ! mentions_dropdown_list.find( 'li:last-child' ).hasClass( 'list-loader' ) ) {
								mentions_dropdown_list.append( '<li class="list-loader">Loading more results…</li>' );
							}

							bp.mentions.xhr_scroll = null;

							if ( ! mentions_dropdown.hasClass( 'has-events' ) ) {
								mentions_dropdown_list.on(
									'scroll',
									function () {
										mention_scroll();
									}
								);
								mentions_dropdown.addClass( 'has-events' );
							}

							return;
						}

						if ( ! mentions_dropdown_list.find( 'li:last-child' ).hasClass( 'list-loader' ) ) {
							mentions_dropdown_list.append( '<li class="list-loader">Loading more results…</li>' );
						}

						if ( mentions_dropdown.data( 'page' ) ) {
							params.page = mentions_dropdown.data( 'page' );
						} else {
							params.page = 1;
							mentions_dropdown.data( 'page', 1 );
						}

						if ( $.isNumeric( this.$inputor.data( 'suggestions-group-id' ) ) ) {
							params[ 'group-id' ] = parseInt( this.$inputor.data( 'suggestions-group-id' ), 10 );
						}

						bp.mentions.xhr = $.getJSON( ajaxurl, params )
							/**
							 * Success callback for the @suggestions lookup.
							 *
							 * @param {object} response Details of users matching the query.
							 * @since BuddyPress 2.1.0
							 */
							.done(
								function ( response ) {
									if ( ! response.success ) {
										return;
									}

									mentionsQueryCache[ query ] = {};

									mentionsQueryCache[ query ].page = params.page;

									var data = $.map(
										response.data.results,
										/**
										 * Create a composite index to determine ordering of results;
										 * nicename matches will appear on top.
										 *
										 * @param {array} suggestion A suggestion's original data.
										 * @return {array} A suggestion's new data.
										 * @since BuddyPress 2.1.0
										 */
										function ( suggestion ) {
											suggestion.search = suggestion.search || suggestion.ID + ' ' + suggestion.name;
											return suggestion;
										}
									);

									if ( typeof response.data.total_pages != 'undefined' ) {
										$self.total_pages                       = parseInt( response.data.total_pages );
										mentionsQueryCache[ query ].total_pages = parseInt( response.data.total_pages );
									}

									$self.data                       = data;
									mentionsQueryCache[ query ].data = data;
									mentions_dropdown_list.find( '.list-loader' ).remove();
									$self.render_view( data );
									mentions_dropdown_list.removeClass( 'list-loading' );

									$self.$inputor.trigger( 'keyup' );
									bp.mentions.xhr_scroll = null;
								}
							);

						if ( ! mentions_dropdown.hasClass( 'has-events' ) ) {
							mentions_dropdown_list.on(
								'scroll',
								function () {
									mention_scroll();
								}
							);
							mentions_dropdown.addClass( 'has-events' );
						}
					},

					sorter: function(query, items) {
						return items;
					},

					beforeReposition: function(offset) {
						// suggestions left position when RTL.
						if ( $( 'body.rtl' ).length > 0 ) {
							var at_view = this.$el.find( '.atwho-view' );
							if ( at_view.length > 0 ) {
								offset.left = this.rect().left - at_view.width();
							}
						}
					}
				},

				data: $.map(
					options.data,
					/**
					 * Create a composite index to search against of nicename + display name.
					 * This will also determine ordering of results, so nicename matches will appear on top.
					 *
					 * @param {array} suggestion A suggestion's original data.
					 * @return {array} A suggestion's new data.
					 * @since BuddyPress 2.1.0
					 */
					function ( suggestion ) {
						suggestion.search = suggestion.search || suggestion.ID + ' ' + suggestion.name;
						return suggestion;
					}
				),

			at: '@',
			searchKey: 'search',
			startWithSpace: true,
			displayTpl: BP_Mentions_Options.display_tpl
			},
			BP_Mentions_Options.extra_options,
			mentions
		);

		this.on(
			'blur.atwhoInner',
			function () {
				// Reset mention dropdown data.
				jQuery( '#atwho-ground-whats-new' ).data( 'page', 1 );
				jQuery( '#atwho-ground-whats-new' ).find( '.list-loader' ).remove();
				jQuery( '#atwho-ground-whats-new' ).find( '.list-loading' ).removeClass( 'list-loading' );
			}
		);

		// Update medium editors when mention inserted into editor.
		this.on(
			'inserted.atwho',
			function ( event ) {
				if ( isChrome ) {
					/**
					 * Issue with chrome only on news feed - When select any user name and hit enter then create p tag with space. So, focus will goes to next line.
					 */
					jQuery( this ).on(
						'keyup',
						function ( e ) {
							keyCode = e.keyCode;
							if ( 13 === keyCode ) {
								if ( jQuery( this ).hasClass( 'medium-editor-element' ) ) {
									var checkCeAttr = jQuery( this ).attr( 'contenteditable' );
									if ( typeof checkCeAttr !== 'undefined' && checkCeAttr !== false ) {
										jQuery( this ).attr( 'contenteditable', 'false' );
									}
								}
								setTimeout(
									function () {
										$.each(
											BP_Mentions_Options.selectors,
											function ( index, value ) {
												if ( jQuery( value ).hasClass( 'medium-editor-element' ) ) {
													 var checkCeAttrForAll = jQuery( value ).attr( 'contenteditable' );
													if ( typeof checkCeAttrForAll !== 'undefined' && checkCeAttrForAll !== false ) {
														jQuery( value ).attr( 'contenteditable', 'true' );
														jQuery( value ).find( '.atwho-inserted' ).closest( '.medium-editor-element' ).focus();
													}
												}
											}
										);
									},
									10
								);
							}
						}
					);
				}

				jQuery( this ).on(
					'keydown',
					function ( e ) {
						// Check backspace key down event.
						if ( ! isAndroid ) {
							if ( e.keyCode == 8 ) {
								jQuery( this ).find( '.atwho-inserted' ).each(
									function () {
										jQuery( this ).removeAttr( 'contenteditable' );
									}
								);
							} else {
								jQuery( this ).find( '.atwho-inserted' ).each(
									function () {
										jQuery( this ).attr( 'contenteditable', false );
									}
								);
							}
						}
					}
				);
				if ( typeof event.currentTarget !== 'undefined' && typeof event.currentTarget.innerHTML !== 'undefined' ) {
					var i = 0;
					if ( typeof window.forums_medium_reply_editor !== 'undefined' ) {
						var reply_editors = Object.keys( window.forums_medium_reply_editor );
						if ( reply_editors.length ) {
							for ( i = 0; i < reply_editors.length; i++ ) {
								window.forums_medium_reply_editor[ reply_editors[ i ] ].checkContentChanged();
							}
						}
					}
					if ( typeof window.forums_medium_topic_editor !== 'undefined' ) {
						var topic_editors = Object.keys( window.forums_medium_topic_editor );
						if ( topic_editors.length ) {
							for ( i = 0; i < topic_editors.length; i++ ) {
								window.forums_medium_topic_editor[ topic_editors[ i ] ].checkContentChanged();
							}
						}
					}
					if ( typeof window.forums_medium_forum_editor !== 'undefined' ) {
						var forum_editors = Object.keys( window.forums_medium_forum_editor );
						if ( forum_editors.length ) {
							for ( i = 0; i < forum_editors.length; i++ ) {
								window.forums_medium_forum_editor[ forum_editors[ i ] ].checkContentChanged();
							}
						}
					}
				}

				// Reset mention dropdown data.
				jQuery( '#atwho-ground-whats-new' ).data( 'page', 1 );
				jQuery( '#atwho-ground-whats-new' ).find( '.list-loader' ).remove();
				jQuery( '#atwho-ground-whats-new' ).find( '.list-loading' ).removeClass( 'list-loading' );

				jQuery( this ).focus();

			}
		);

		/**
		 * Remove all remaining element ( if there is any ) if no text remaining in the
		 * what's new text box.
		 */
		this.on(
			'keyup',
			function () {
				/**
				 * Removing the "contenteditable" in android devices.
				 * It was preventing the backspace somehow. So whenever
				 * we try to backspace, keyboard was automatically closed.
				 */
				if ( isAndroid ) {

					var new_length = jQuery( this ).text().length; // Get the new text length.
					localStorage.setItem( 'charCount', new_length ); // Set length to local storage.

					// Remove the "contenteditable".
					jQuery( this ).find( '.atwho-inserted' ).each(
						function () {
							jQuery( this ).removeAttr( 'contenteditable' );
						}
					);

				}
			}
		);

		var opts = $.extend( true, {}, suggestionsDefaults, mentionsDefaults, options );
		return $.fn.atwho.call( this, opts );
	};

	$( document ).ready(
		function () {
			// Reset counter for textbox character length.
			localStorage.setItem( 'charCount', 0 );
			$( document ).on(
				'focus',
				BP_Mentions_Options.selectors.join( ',' ),
				function () {
					if ( $( this ).data( 'bp_mentions_activated' ) ) {
						 return;
					}

					if ( typeof ( bp ) === 'undefined' ) {
							 return;
					}

					$( this ).bp_mentions( bp.mentions.users );
					$( this ).data( 'bp_mentions_activated', true );
				}
			);
		}
	);

	bp.mentions.tinyMCEinit = function () {
		if ( typeof window.tinyMCE === 'undefined' || window.tinyMCE.activeEditor === null || typeof window.tinyMCE.activeEditor === 'undefined' ) {
			return;
		} else {
			$( window.tinyMCE.activeEditor.contentDocument.activeElement )
				.atwho( 'setIframe', $( '.wp-editor-wrap iframe' )[ 0 ] )
				.bp_mentions( bp.mentions.users );
		}
	};
} )( bp, jQuery );
