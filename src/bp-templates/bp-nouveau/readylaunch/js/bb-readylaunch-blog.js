/**
 * ReadyLaunch — Blog archive interactions.
 *
 * Grid/list view switcher (persisted in localStorage) and the Category /
 * Activity filter dropdowns (each option value is the URL to navigate to).
 *
 * @since BuddyBoss [BBVERSION]
 */
( function () {
	'use strict';

	var BB_RL_BLOG_VIEW_KEY = 'bbRlBlogView';

	/**
	 * Apply a view (grid|list) to the blog container and switcher buttons.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} view View key, `grid` or `list`.
	 */
	function bbRlBlogApplyView( view ) {
		var grid    = document.querySelector( '.bb-rl-blog-grid' );
		var buttons = document.querySelectorAll( '.bb-rl-blog-view-switcher__button' );
		var i;

		if ( ! grid ) {
			return;
		}

		if ( 'list' === view ) {
			grid.className += ' bb-rl-blog-grid--list';
		} else {
			grid.className = grid.className.replace( /\s*bb-rl-blog-grid--list/g, '' );
		}

		for ( i = 0; i < buttons.length; i++ ) {
			if ( buttons[ i ].getAttribute( 'data-bb-rl-blog-view' ) === view ) {
				buttons[ i ].className += ' is-active';
				buttons[ i ].setAttribute( 'aria-pressed', 'true' );
			} else {
				buttons[ i ].className = buttons[ i ].className.replace( /\s*is-active/g, '' );
				buttons[ i ].setAttribute( 'aria-pressed', 'false' );
			}
		}
	}

	/**
	 * Read the persisted view preference.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return {string} `grid` or `list`.
	 */
	function bbRlBlogSavedView() {
		try {
			return 'list' === window.localStorage.getItem( BB_RL_BLOG_VIEW_KEY ) ? 'list' : 'grid';
		} catch ( e ) {
			return 'grid';
		}
	}

	function bbRlBlogInit() {
		var buttons = document.querySelectorAll( '.bb-rl-blog-view-switcher__button' );
		var selects = document.querySelectorAll( '.bb-rl-blog-filter__select' );
		var i;

		if ( ! document.querySelector( '.bb-rl-blog-grid' ) ) {
			return;
		}

		bbRlBlogApplyView( bbRlBlogSavedView() );

		for ( i = 0; i < buttons.length; i++ ) {
			buttons[ i ].addEventListener( 'click', function () {
				var view = this.getAttribute( 'data-bb-rl-blog-view' );

				bbRlBlogApplyView( view );

				try {
					window.localStorage.setItem( BB_RL_BLOG_VIEW_KEY, view );
				} catch ( e ) {
					// Storage unavailable — the view still switches for this page.
				}
			} );
		}

		for ( i = 0; i < selects.length; i++ ) {
			selects[ i ].addEventListener( 'change', function () {
				if ( this.value ) {
					window.location.href = this.value;
				}
			} );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', bbRlBlogInit );
	} else {
		bbRlBlogInit();
	}
} )();
