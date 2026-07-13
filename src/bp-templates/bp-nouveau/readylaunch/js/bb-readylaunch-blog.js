/**
 * ReadyLaunch — Blog archive interactions.
 *
 * Grid/list view switcher (persisted in localStorage), the Category /
 * Activity filter dropdowns (each option value is the URL to navigate to),
 * and the Related Blogs prev/next carousel on single posts.
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

	/**
	 * Initialize the Related Blogs prev/next carousel (single post).
	 *
	 * The track is a native horizontal scroller; the buttons scroll it by
	 * one card and enable/disable themselves at the edges.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	function bbRlBlogRelatedCarouselInit() {
		var section = document.querySelector( '.bb-rl-blog-related-cards' );

		if ( ! section ) {
			return;
		}

		var track      = section.querySelector( '.bb-rl-blog-related-cards__track' );
		var prevButton = section.querySelector( '[data-bb-rl-related-nav="prev"]' );
		var nextButton = section.querySelector( '[data-bb-rl-related-nav="next"]' );

		if ( ! track || ! prevButton || ! nextButton ) {
			return;
		}

		/**
		 * Sync the buttons' disabled state with the track scroll position.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		function bbRlBlogRelatedUpdateNav() {
			var maxScroll = track.scrollWidth - track.clientWidth;
			var position  = Math.abs( track.scrollLeft );

			prevButton.disabled = position <= 1;
			nextButton.disabled = position >= maxScroll - 1;
		}

		/**
		 * Scroll the track by one card in the given direction.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param {number} direction `1` for next, `-1` for previous.
		 */
		function bbRlBlogRelatedScroll( direction ) {
			var card = track.querySelector( '.bb-rl-blog-card' );
			var gap  = 16;
			var step = card ? card.offsetWidth + gap : track.clientWidth;

			if ( document.documentElement && 'rtl' === document.documentElement.dir ) {
				direction = -direction;
			}

			track.scrollBy( { left: direction * step, behavior: 'smooth' } );
		}

		prevButton.addEventListener( 'click', function () {
			bbRlBlogRelatedScroll( -1 );
		} );

		nextButton.addEventListener( 'click', function () {
			bbRlBlogRelatedScroll( 1 );
		} );

		track.addEventListener( 'scroll', bbRlBlogRelatedUpdateNav, { passive: true } );
		window.addEventListener( 'resize', bbRlBlogRelatedUpdateNav );

		bbRlBlogRelatedUpdateNav();
	}

	function bbRlBlogInit() {
		bbRlBlogRelatedCarouselInit();

		var buttons = document.querySelectorAll( '.bb-rl-blog-view-switcher__button' );
		var selects = document.querySelectorAll( '.bb-rl-blog-filter__select' );
		var i;

		// URL-valued filter selects (archive header + member profile toolbar)
		// bind regardless of the view switcher below.
		for ( i = 0; i < selects.length; i++ ) {
			selects[ i ].addEventListener( 'change', function () {
				if ( this.value ) {
					window.location.href = this.value;
				}
			} );
		}

		// Only pages with the switcher (the blog archive) get the saved view —
		// other grids reusing the card markup (e.g. related posts) stay grid.
		if ( ! buttons.length || ! document.querySelector( '.bb-rl-blog-grid' ) ) {
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
	}

	/**
	 * Close a blog card more-options menu.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Element} menu Menu wrapper element.
	 */
	function bbRlBlogCloseCardMenu( menu ) {
		var toggle = menu.querySelector( '.bb-rl-blog-card__menu-toggle' );

		menu.className = menu.className.replace( /\s*is-open/g, '' );

		if ( toggle ) {
			toggle.setAttribute( 'aria-expanded', 'false' );
		}
	}

	// Blog card more-options menu (member profile cards) — delegated so it
	// covers every card without per-card bindings.
	document.addEventListener( 'click', function ( event ) {
		var toggle = event.target.closest ? event.target.closest( '.bb-rl-blog-card__menu-toggle' ) : null;
		var open   = document.querySelectorAll( '.bb-rl-blog-card__menu.is-open' );
		var menu;
		var i;

		if ( toggle ) {
			event.preventDefault();
			menu = toggle.parentNode;

			for ( i = 0; i < open.length; i++ ) {
				if ( open[ i ] !== menu ) {
					bbRlBlogCloseCardMenu( open[ i ] );
				}
			}

			if ( -1 !== menu.className.indexOf( 'is-open' ) ) {
				bbRlBlogCloseCardMenu( menu );
			} else {
				menu.className += ' is-open';
				toggle.setAttribute( 'aria-expanded', 'true' );
			}

			return;
		}

		for ( i = 0; i < open.length; i++ ) {
			bbRlBlogCloseCardMenu( open[ i ] );
		}
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', bbRlBlogInit );
	} else {
		bbRlBlogInit();
	}
} )();
