/**
 * BuddyBoss Admin Settings 2.0 - Help Screen
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback, useMemo } from '@wordpress/element';
import { Button, Spinner } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { useKb } from '../context/KbContext';
import { getTaxonomy, clearTaxonomy } from '../components/knowledge-base/taxonomyCache';
import { getCuratedOverrides } from '../components/knowledge-base/curatedOverrides';
import { ajaxFetch } from '../utils/ajax';
import doneForYouImage from '../images/help-done-for-you.png';

// BuddyBoss.com knowledge base REST endpoint used for Help search.
var HELP_SEARCH_ENDPOINT = 'https://buddyboss.com/wp-json/wp/v2/ht-kb/';
var HELP_SEARCH_DEBOUNCE_MS = 300;
var HELP_SEARCH_MIN_LENGTH = 2;

/**
 * Strip HTML tags from a string and collapse whitespace to plain text.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} html Raw HTML string.
 *
 * @returns {string} Plain text.
 */
function bbStripHtml( html ) {
	var el = document.createElement( 'div' );
	el.innerHTML = html || '';
	return ( el.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}

/**
 * Walk a flat taxonomy up the parent chain to the top-level category slug.
 *
 * The Knowledge Base modal renders a category view keyed by its top-level
 * slug, so an article that lives in a nested sub-category must be resolved
 * to its top-level ancestor before the modal can open to it.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  taxonomy Flat array of ht-kb-category term objects.
 * @param {number} termId   Term ID to resolve from.
 *
 * @returns {string} Top-level category slug, or '' when it cannot be resolved.
 */
function bbResolveTopLevelSlug( taxonomy, termId ) {
	if ( ! Array.isArray( taxonomy ) || ! termId ) {
		return '';
	}

	var byId = {};
	taxonomy.forEach( function ( term ) {
		byId[ term.id ] = term;
	} );

	var current = byId[ termId ];
	var guard = 0;
	while ( current && current.parent && byId[ current.parent ] && guard < 20 ) {
		current = byId[ current.parent ];
		guard++;
	}

	return current ? current.slug : '';
}

/**
 * Build a top-level-category-slug → aggregated article count map from the
 * flat KB taxonomy.
 *
 * A top-level category's count is the recursive sum of its own articles plus
 * every descendant sub-category's articles, matching the count shown on the
 * Knowledge Base modal landing grid.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array} taxonomy Flat array of ht-kb-category term objects.
 *
 * @returns {Object<string, number>} Map of top-level slug → aggregated count.
 */
function bbBuildKbCounts( taxonomy ) {
	var bySlug = {};

	if ( ! Array.isArray( taxonomy ) ) {
		return bySlug;
	}

	var byId = {};
	var childrenByParent = {};
	taxonomy.forEach( function ( term ) {
		byId[ term.id ] = term;
		var list = childrenByParent[ term.parent ] || [];
		list.push( term );
		childrenByParent[ term.parent ] = list;
	} );

	function aggregate( termId ) {
		var own = byId[ termId ];
		var total = own && 'number' === typeof own.count ? own.count : 0;
		var kids = childrenByParent[ termId ] || [];
		kids.forEach( function ( child ) {
			total += aggregate( child.id );
		} );
		return total;
	}

	taxonomy.forEach( function ( term ) {
		if ( ! term.parent ) {
			bySlug[ term.slug ] = aggregate( term.id );
		}
	} );

	return bySlug;
}

/**
 * Help Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @returns {JSX.Element} Help screen.
 */
export function HelpScreen( { onNavigate } ) {
	var searchState = useState( '' );
	var searchQuery = searchState[ 0 ];
	var setSearchQuery = searchState[ 1 ];

	var resultsState = useState( [] );
	var results = resultsState[ 0 ];
	var setResults = resultsState[ 1 ];

	var loadingState = useState( false );
	var isLoading = loadingState[ 0 ];
	var setIsLoading = loadingState[ 1 ];

	var openState = useState( false );
	var isOpen = openState[ 0 ];
	var setIsOpen = openState[ 1 ];

	// Whether the DocsBot chat panel is currently open. Drives the footer
	// button's icon + label (robot/"Chat with Buddy" when closed, ×/"Need Help"
	// when open). Tracked separately from `isOpen` above, which controls the
	// search-results dropdown.
	var chatOpenState = useState( false );
	var chatOpen = chatOpenState[ 0 ];
	var setChatOpen = chatOpenState[ 1 ];

	/**
	 * Toggle the DocsBot chat widget open/closed.
	 *
	 * The widget is injected by the PHP loader (enqueued only on this Settings
	 * screen, mounted only on the Help tab); its default floating launcher is
	 * hidden so this footer button is the sole trigger. The loader exposes
	 * window.DocsBotAI with a toggle() method once chat.js has loaded. Guarded
	 * because the script loads asynchronously — a click before it is ready is a
	 * harmless no-op. The button label/icon are driven by the polled open state
	 * below, so they stay correct even when the chat is closed from inside the
	 * panel's own close control.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns {void}
	 */
	var toggleChatbot = useCallback( function () {
		if ( window.DocsBotAI && 'function' === typeof window.DocsBotAI.toggle ) {
			window.DocsBotAI.toggle();
		}
	}, [] );

	// Ref to the footer so "Ask Buddy" can scroll it into view.
	var footerRef = useRef( null );

	/**
	 * Handle the "Ask Buddy" CTA: scroll down to the help footer, then open the
	 * DocsBot chat.
	 *
	 * Unlike the footer button (which toggles), this always opens — it would be
	 * confusing for an "Ask Buddy" call-to-action to close an already-open chat.
	 * The open() call is guarded because the widget loads asynchronously.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @returns {void}
	 */
	var askBuddy = useCallback( function () {
		var footer = footerRef.current;
		if ( footer ) {
			// Scroll the window to the footer, THEN open the chat. Two layout
			// quirks here drove this approach: (1) the admin clamps the document
			// element to the viewport height and content scrolls on the window,
			// and smooth scrolling is unreliable in this context — so we compute
			// the footer's absolute offset and scroll the window to it directly;
			// (2) opening the DocsBot chat focuses its input, which can yank the
			// page back to the top if it happens before the scroll lands, so the
			// scroll is performed first.
			var top = footer.getBoundingClientRect().top + window.pageYOffset;
			window.scrollTo( 0, top );
		}
		if ( window.DocsBotAI && 'function' === typeof window.DocsBotAI.open ) {
			window.DocsBotAI.open();
		}
	}, [] );

	// Keep `chatOpen` in sync with the widget's actual state. DocsBot exposes
	// `isChatbotOpen` as the source of truth; polling it (rather than relying on
	// DocsBot's internal event names) keeps the button correct whether the chat
	// is closed via this button or the panel's own × control, and is resilient
	// to widget version changes. The interval is cleared on unmount.
	useEffect( function () {
		var id = window.setInterval( function () {
			var open = !! ( window.DocsBotAI && window.DocsBotAI.isChatbotOpen );
			setChatOpen( function ( prev ) {
				return prev === open ? prev : open;
			} );
		}, 400 );
		return function () {
			window.clearInterval( id );
		};
	}, [] );

	// Per-category article counts for the "BuddyBoss Knowledge Base" grid,
	// keyed by top-level category slug. Null until the taxonomy resolves.
	// Top-level KB categories for the "BuddyBoss Knowledge Base" grid, loaded
	// live from the KB taxonomy (the same source the documentation modal uses)
	// instead of a hardcoded list. `kbStatus` drives the loader / error / grid.
	var kbTermsState = useState( [] );
	var kbTerms = kbTermsState[ 0 ];
	var setKbTerms = kbTermsState[ 1 ];

	var kbStatusState = useState( 'loading' );
	var kbStatus = kbStatusState[ 0 ];
	var setKbStatus = kbStatusState[ 1 ];

	var kbRetryState = useState( 0 );
	var kbRetry = kbRetryState[ 0 ];
	var setKbRetry = kbRetryState[ 1 ];

	// Authoritative Support Access enabled state, mirrored from the same
	// server endpoint the Support Access screen uses, so the card badge below
	// reflects whether access is actually on. Held disabled until resolved so
	// the badge never claims a status before the real value is known.
	var supportAccessState = useState( false );
	var supportAccessEnabled = supportAccessState[ 0 ];
	var setSupportAccessEnabled = supportAccessState[ 1 ];

	var supportAccessLoadedState = useState( false );
	var supportAccessLoaded = supportAccessLoadedState[ 0 ];
	var setSupportAccessLoaded = supportAccessLoadedState[ 1 ];

	var searchRef = useRef( null );
	var debounceRef = useRef( null );
	var abortRef = useRef( null );

	var kb = useKb();
	var kbDispatch = kb.dispatch;
	var openKb = kb.open;
	var closeKb = kb.close;

	// Load the real Support Access state on mount so the "Support Access" card
	// badge reflects the live toggle value from SupportAccessScreen.
	useEffect( function () {
		var cancelled = false;
		ajaxFetch( 'bb_admin_support_access_get' )
			.then( function ( res ) {
				if ( cancelled ) {
					return;
				}
				if ( res && res.success && res.data ) {
					setSupportAccessEnabled( !! res.data.enabled );
				}
			} )
			.catch( function () {
				// Leave the default (disabled) on error.
			} )
			.finally( function () {
				if ( ! cancelled ) {
					setSupportAccessLoaded( true );
				}
			} );
		return function () {
			cancelled = true;
		};
	}, [] );

	// Debounced knowledge-base search against the BuddyBoss.com REST API.
	useEffect( function () {
		var query = searchQuery.trim();

		if ( debounceRef.current ) {
			clearTimeout( debounceRef.current );
		}

		if ( query.length < HELP_SEARCH_MIN_LENGTH ) {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			setResults( [] );
			setIsLoading( false );
		} else {
			setIsLoading( true );
			setIsOpen( true );

			debounceRef.current = setTimeout( function () {
				if ( abortRef.current ) {
					abortRef.current.abort();
				}

				var controller = new AbortController();
				abortRef.current = controller;

				window.fetch(
					HELP_SEARCH_ENDPOINT +
						'?search=' + encodeURIComponent( query ) +
						'&per_page=8&_fields=id,slug,title,excerpt,link,ht-kb-category',
					{ signal: controller.signal }
				)
					.then( function ( response ) {
						if ( ! response.ok ) {
							throw new Error( 'Help search request failed.' );
						}
						return response.json();
					} )
					.then( function ( data ) {
						setResults( Array.isArray( data ) ? data : [] );
						setIsLoading( false );
					} )
					.catch( function ( error ) {
						if ( error && 'AbortError' === error.name ) {
							return;
						}
						setResults( [] );
						setIsLoading( false );
					} );
			}, HELP_SEARCH_DEBOUNCE_MS );
		}

		return function () {
			if ( debounceRef.current ) {
				clearTimeout( debounceRef.current );
			}
		};
	}, [ searchQuery ] );

	// Close the results dropdown on outside click or Escape.
	useEffect( function () {
		function handlePointerDown( event ) {
			if ( searchRef.current && ! searchRef.current.contains( event.target ) ) {
				setIsOpen( false );
			}
		}

		function handleKeyDown( event ) {
			if ( 'Escape' === event.key ) {
				setIsOpen( false );
			}
		}

		document.addEventListener( 'mousedown', handlePointerDown );
		document.addEventListener( 'keydown', handleKeyDown );

		return function () {
			document.removeEventListener( 'mousedown', handlePointerDown );
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [] );

	// Abort any in-flight request and timer when the screen unmounts.
	useEffect( function () {
		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
			if ( debounceRef.current ) {
				clearTimeout( debounceRef.current );
			}
		};
	}, [] );

	// Load the KB taxonomy on mount (and on retry). This serves two purposes:
	// it warms the cache so resolving a clicked search result / resource card
	// to its KB category is a cache hit instead of a cold cross-origin round
	// trip, and it supplies the live category cards + counts shown on the
	// "BuddyBoss Knowledge Base" grid.
	useEffect( function () {
		var controller = new AbortController();
		setKbStatus( 'loading' );

		getTaxonomy( controller.signal )
			.then( function ( taxonomy ) {
				if ( Array.isArray( taxonomy ) ) {
					setKbTerms( taxonomy );
					setKbStatus( 'ready' );
				} else {
					setKbStatus( 'error' );
				}
			} )
			.catch( function ( err ) {
				// Aborts fire on unmount mid-fetch — stay silent for those.
				if ( err && 'AbortError' === err.name ) {
					return;
				}
				setKbStatus( 'error' );
			} );

		return function () {
			controller.abort();
		};
	}, [ kbRetry ] );

	// Fold the flat taxonomy into the top-level category cards for the grid:
	// curated icon/title/description/order applied by slug (matching the
	// documentation modal), with recursive descendant article counts. Unknown
	// top-levels fall back to the raw API name and a generic book icon.
	var resourceCards = useMemo( function () {
		if ( ! Array.isArray( kbTerms ) || 0 === kbTerms.length ) {
			return [];
		}

		var byId             = {};
		var childrenByParent = {};
		kbTerms.forEach( function ( term ) {
			byId[ term.id ] = term;
			var list = childrenByParent[ term.parent ] || [];
			list.push( term );
			childrenByParent[ term.parent ] = list;
		} );

		var counts    = bbBuildKbCounts( kbTerms );
		var overrides = getCuratedOverrides();
		var topLevels = childrenByParent[ 0 ] || [];

		var built = topLevels.map( function ( term ) {
			var curated = overrides[ term.slug ] || null;
			return {
				id:          term.id,
				slug:        term.slug,
				name:        decodeEntities( curated ? curated.title : ( term.name || '' ) ),
				description: decodeEntities( curated ? curated.description : ( term.description || '' ) ),
				icon:        curated ? curated.icon : 'bb-icons-rl-book',
				order:       curated ? curated.order : 999,
				count:       'number' === typeof counts[ term.slug ] ? counts[ term.slug ] : 0,
			};
		} );

		// Curated cards first by their order, then anything uncurated by name.
		built.sort( function ( a, b ) {
			if ( a.order !== b.order ) {
				return a.order - b.order;
			}
			return a.name.localeCompare( b.name );
		} );

		return built;
	}, [ kbTerms ] );

	/**
	 * Handle selection of a knowledge-base search result.
	 *
	 * Opens the Knowledge Base modal immediately on the clicked article so the
	 * modal + loader appear right away — without waiting on the taxonomy
	 * request. The modal mounts into the category view's loading state (null
	 * category slug); once the taxonomy resolves the article's top-level
	 * category, the real slug + article are dispatched. If the category
	 * cannot be resolved, the modal is closed and the documentation page is
	 * opened in a new tab instead.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {Object} result Knowledge base article object from the REST API.
	 */
	var handleResultClick = useCallback( function ( result ) {
		if ( ! result || ! result.slug ) {
			return;
		}

		// Close the search dropdown.
		setIsOpen( false );

		// Open the modal straight away. A null category slug keeps KBCategory
		// in its loading state until the real slug is resolved below, so the
		// user sees the modal + loader instantly instead of waiting for the
		// cross-origin taxonomy fetch to complete.
		kbDispatch( { type: 'selectCategory', slug: null } );
		kbDispatch( { type: 'selectArticle', slug: result.slug } );
		openKb();

		var categoryIds = Array.isArray( result[ 'ht-kb-category' ] ) ? result[ 'ht-kb-category' ] : [];

		getTaxonomy()
			.then( function ( taxonomy ) {
				var topSlug = bbResolveTopLevelSlug( taxonomy, categoryIds[ 0 ] );

				if ( ! topSlug ) {
					throw new Error( 'Unresolved knowledge base category.' );
				}

				kbDispatch( { type: 'selectCategory', slug: topSlug } );
				kbDispatch( { type: 'selectArticle', slug: result.slug } );
			} )
			.catch( function () {
				// Category could not be resolved — back out of the modal and
				// fall back to the documentation page.
				closeKb();
				if ( result.link ) {
					window.open( result.link, '_blank', 'noopener,noreferrer' );
				}
			} );
	}, [ kbDispatch, openKb, closeKb ] );

	/**
	 * Open the Knowledge Base modal at a specific top-level category.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {string} slug Knowledge base category slug.
	 */
	var openKbCategory = useCallback( function ( slug ) {
		kbDispatch( { type: 'selectCategory', slug: slug } );
		openKb();
	}, [ kbDispatch, openKb ] );

	var hasQuery = searchQuery.trim().length >= HELP_SEARCH_MIN_LENGTH;
	var showResults = isOpen && hasQuery;

	return (
		<div className="bb-admin-help-screen">
			<section className="bb-admin-help-hero" aria-labelledby="bb-admin-help-hero-title">
				<div className="bb-admin-help-hero__intro">
					<h1 id="bb-admin-help-hero-title" className="bb-admin-help-hero__title">
						{ __( 'Need Help?', 'buddyboss' ) }
					</h1>
					<p className="bb-admin-help-hero__subtitle">
						{ __( 'Search our help guides, chat with us, or send us a message.', 'buddyboss' ) }
					</p>
				</div>

				<div className="bb-admin-help-hero__search" role="search" ref={ searchRef }>
					<label htmlFor="bb-admin-help-hero-search" className="screen-reader-text">
						{ __( 'Search help guides', 'buddyboss' ) }
					</label>
					<i
						className="bb-icons-rl bb-icons-rl-magnifying-glass bb-admin-help-hero__search-icon"
						aria-hidden="true"
					></i>
					<input
						id="bb-admin-help-hero-search"
						type="search"
						className="bb-admin-help-hero__search-input"
						placeholder={ __( 'Describe your issue', 'buddyboss' ) }
						value={ searchQuery }
						autoComplete="off"
						role="combobox"
						aria-expanded={ showResults }
						aria-controls="bb-admin-help-hero-results"
						onChange={ function ( e ) {
							setSearchQuery( e.target.value );
						} }
						onFocus={ function () {
							if ( hasQuery ) {
								setIsOpen( true );
							}
						} }
					/>

					{ showResults && (
						<div
							id="bb-admin-help-hero-results"
							className="bb-admin-help-hero__results"
							role="listbox"
						>
							{ isLoading && (
								<p className="bb-admin-help-hero__results-status">
									<Spinner />
								</p>
							) }

							{ ! isLoading && 0 === results.length && (
								<p className="bb-admin-help-hero__results-status">
									{ __( 'No results found.', 'buddyboss' ) }
								</p>
							) }

							{ ! isLoading && results.map( function ( result ) {
								return (
									<button
										type="button"
										key={ result.id }
										className="bb-admin-help-hero__result"
										role="option"
										aria-selected="false"
										onClick={ function () {
											handleResultClick( result );
										} }
									>
										<span className="bb-admin-help-hero__result-title">
											<i
												className="bb-icons-rl bb-icons-rl-file-text bb-admin-help-hero__result-icon"
												aria-hidden="true"
											></i>
											<span className="bb-admin-help-hero__result-title-text">
												{ decodeEntities( ( result.title && result.title.rendered ) || '' ) }
											</span>
										</span>
										{ result.excerpt && result.excerpt.rendered && (
											<span className="bb-admin-help-hero__result-text">
												{ bbStripHtml( result.excerpt.rendered ) }
											</span>
										) }
									</button>
								);
							} ) }
						</div>
					) }
				</div>
			</section>
			<div className="bb-admin-help-wrapper">
				<div className="bb-admin-help-cards">
					<article className="bb-admin-help-card">
						<i
							className="bb-icons-rl bb-icons-rl-paper-plane-tilt bb-admin-help-card__icon"
							aria-hidden="true"
						></i>
						<div className="bb-admin-help-card__body">
							<h2 className="bb-admin-help-card__title">
								{ __( 'Support Ticket', 'buddyboss' ) }
							</h2>
							<p className="bb-admin-help-card__description">
								{ __( 'Send your request directly to our technical support team – we’re ready to help troubleshoot and guide you.', 'buddyboss' ) }
							</p>
						</div>
						<a
							href="https://buddyboss.com/my-account/?tab=support"
							className="bb-admin-help-card__action is-secondary"
							target="_blank"
							rel="nofollow"
						>
							{ __( 'Submit a Ticket', 'buddyboss' ) }
						</a>
					</article>

					<article className="bb-admin-help-card">
						<i
							className="bb-icons-rl bb-icons-rl-key bb-admin-help-card__icon"
							aria-hidden="true"
						></i>
						<div className="bb-admin-help-card__body">
							<div className="bb-admin-help-card__title-row">
								<h2 className="bb-admin-help-card__title">
									{ __( 'Support Access', 'buddyboss' ) }
								</h2>
								{ supportAccessLoaded && (
									supportAccessEnabled ? (
										<span className="bb-admin-help-card__status bb-admin-help-card__status--positive">
											<i
												className="bb-icons-rl bb-icons-rl-check-circle"
												aria-hidden="true"
											></i>
											{ __( 'Enabled', 'buddyboss' ) }
										</span>
									) : (
										<span className="bb-admin-help-card__status bb-admin-help-card__status--neutral">
											<i
												className="bb-icons-rl bb-icons-rl-x-circle"
												aria-hidden="true"
											></i>
											{ __( 'Disabled', 'buddyboss' ) }
										</span>
									)
								) }
							</div>
							<p className="bb-admin-help-card__description">
								{ __( 'Allow our support team to securely access your site using temporary credentials to troubleshoot issues.', 'buddyboss' ) }
							</p>
						</div>
						<Button
							variant="secondary"
							className="bb-admin-help-card__action"
							onClick={ function () {
								if ( 'function' === typeof onNavigate ) {
									onNavigate( '/settings/help/support-access' );
								}
							} }
						>
							{ __( 'Open Access', 'buddyboss' ) }
						</Button>
					</article>
				</div>

				<section
					className="bb-admin-help-resources"
					aria-labelledby="bb-admin-help-resources-title"
				>
					<h2
						id="bb-admin-help-resources-title"
						className="bb-admin-help-resources__title"
					>
						{ __( 'BuddyBoss Knowledge Base', 'buddyboss' ) }
					</h2>
					{ 'loading' === kbStatus && (
						<div className="bb-admin-help-resources__loading" aria-busy="true" aria-live="polite">
							<Spinner />
						</div>
					) }

					{ 'error' === kbStatus && (
						<div className="bb-admin-help-resources__error" role="alert">
							<p>{ __( 'Couldn’t load the knowledge base.', 'buddyboss' ) }</p>
							<Button
								variant="secondary"
								onClick={ function () {
									// Clear the module memo before retrying so a cached
									// empty/failed response isn't re-served as success.
									clearTaxonomy();
									setKbRetry( function ( c ) {
										return c + 1;
									} );
								} }
							>
								{ __( 'Retry', 'buddyboss' ) }
							</Button>
						</div>
					) }

					{ 'ready' === kbStatus && (
						<div className="bb-admin-help-resources__grid">
							{ resourceCards.map( function ( card ) {
								return (
									<button
										type="button"
										key={ card.slug }
										className="bb-admin-help-resource-card"
										onClick={ function () {
											openKbCategory( card.slug );
										} }
									>
										<div className="bb-admin-help-resource-card__head">
											<i
												className={ 'bb-icons-rl ' + card.icon + ' bb-admin-help-resource-card__icon' }
												aria-hidden="true"
											></i>
											<span className="bb-admin-help-resource-card__title">
												{ card.name }
											</span>
										</div>
										{ card.description && (
											<div className="bb-admin-help-resource-card__description">
												{ card.description }
											</div>
										) }
										{ 'number' === typeof card.count && (
											<span className="bb-admin-help-resource-card__count">
												{
													/* translators: %d is the number of articles in this resource category. */
													sprintf( _n( '%d article', '%d articles', card.count, 'buddyboss' ), card.count )
												}
											</span>
										) }
									</button>
								);
							} ) }
						</div>
					) }
				</section>

				<section
					className="bb-admin-help-cta"
					aria-labelledby="bb-admin-help-cta-title"
				>
					<h2
						id="bb-admin-help-cta-title"
						className="bb-admin-help-cta__title"
					>
						{ __( 'Can’t find the Answer?', 'buddyboss' ) }
					</h2>
					<Button
						variant="primary"
						className="bb-admin-help-cta__action"
						onClick={ askBuddy }
					>
						<i
							className="bb-icons-rl bb-icons-rl-robot bb-admin-help-cta__action-icon"
							aria-hidden="true"
						></i>
						<span className="bb-admin-help-cta__action-label">
							{ __( 'Ask Buddy', 'buddyboss' ) }
						</span>
						<i
							className="bb-icons-rl bb-icons-rl-arrow-right bb-admin-help-cta__action-icon"
							aria-hidden="true"
						></i>
					</Button>
				</section>

				<div className="bb-admin-help-row">
					<section
						className="bb-admin-help-getting-started"
						aria-labelledby="bb-admin-help-getting-started-title"
					>
						<h2
							id="bb-admin-help-getting-started-title"
							className="bb-admin-help-getting-started__title"
						>
							{ __( 'Get Started', 'buddyboss' ) }
						</h2>
						<ul className="bb-admin-help-getting-started__list">
							{ [
								{ key: 'install-theme',     label: __( 'How to install the BuddyBoss Theme', 'buddyboss' ) },
								{ key: 'default-data',      label: __( 'How to Setup Default Data in BuddyBoss', 'buddyboss' ) },
								{ key: 'login-register',    label: __( 'How to Customize the Login & Registration Page in BuddyBoss', 'buddyboss' ) },
								{ key: 'install-theme-2',   label: __( 'How to install the BuddyBoss Theme', 'buddyboss' ) },
								{ key: 'default-data-2',    label: __( 'How to Setup Default Data in BuddyBoss', 'buddyboss' ) },
								{ key: 'login-register-2',  label: __( 'How to Customize the Login & Registration Page in BuddyBoss', 'buddyboss' ) },
							].map( function ( item ) {
								return (
									<li key={ item.key } className="bb-admin-help-getting-started__item">
										<a href="#" className="bb-admin-help-getting-started__link">
											<i
												className="bb-icons-rl bb-icons-rl-file-text bb-admin-help-getting-started__icon"
												aria-hidden="true"
											></i>
											<span className="bb-admin-help-getting-started__label">
												{ item.label }
											</span>
										</a>
									</li>
								);
							} ) }
						</ul>
					</section>

					<section
						className="bb-admin-help-promo"
						aria-labelledby="bb-admin-help-promo-title"
					>
						<div className="bb-admin-help-promo__media">
							<img
								src={ doneForYouImage }
								alt={ __( 'Done For You Service preview', 'buddyboss' ) }
								className="bb-admin-help-promo__image"
							/>
						</div>
						<div className="bb-admin-help-promo__body">
							<div className="bb-admin-help-promo__text">
								<p className="bb-admin-help-promo__eyebrow">
									{ __( 'Done For You Service', 'buddyboss' ) }
								</p>
								<h2
									id="bb-admin-help-promo-title"
									className="bb-admin-help-promo__title"
								>
									{ __( 'Get help launching your site', 'buddyboss' ) }
								</h2>
								<p className="bb-admin-help-promo__description">
									{ __( 'Get your own team that will help you launch your site. Let them know what your business is about, then provide them with your logo and brand colors, then sit back while we do all the heavy lifting.', 'buddyboss' ) }
								</p>
							</div>
							<Button variant="primary" className="bb-admin-help-promo__action">
								<span className="bb-admin-help-promo__action-label">
									{ __( 'Get Done For You Web Service', 'buddyboss' ) }
								</span>
							</Button>
						</div>
					</section>
				</div>
			</div>

			<footer
				ref={ footerRef }
				className="bb-admin-help-footer"
				aria-label={ __( 'Chat with Buddy', 'buddyboss' ) }
			>
				<Button
					variant="primary"
					className="bb-admin-help-footer__action"
					onClick={ toggleChatbot }
					aria-expanded={ chatOpen }
				>
					<i
						className={
							'bb-icons-rl bb-admin-help-footer__action-icon ' +
							( chatOpen ? 'bb-icons-rl-x' : 'bb-icons-rl-robot' )
						}
						aria-hidden="true"
					></i>
					<span className="bb-admin-help-footer__action-label">
						{ chatOpen
							? __( 'Need Help', 'buddyboss' )
							: __( 'Chat with Buddy', 'buddyboss' ) }
					</span>
				</Button>
			</footer>
		</div>
	);
}

export default HelpScreen;
