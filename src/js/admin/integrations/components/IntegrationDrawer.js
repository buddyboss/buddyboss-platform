/**
 * BuddyBoss Integrations marketplace — detail drawer (right slide-in).
 *
 * Fetches the integration's full record by slug and renders the header (logo,
 * title, collection, description, "Learn More ↗") plus the sanitized
 * content.rendered. Loading / error / empty states mirror the Knowledge Base
 * article view. Installing is done from the card, not here.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { fetchIntegrationBySlug } from '../../utils/integrationsApi';
import { sanitizeKbArticle, safeUrl, safeImageUrl } from '@bb/admin-common';
import { PluginActionButton } from './PluginActionButton';
import { wporgSlug } from '../../utils/pluginActions';

export function IntegrationDrawer( { slug, initialTitle, plugins, onClose } ) {
	const [ status, setStatus ] = useState( 'loading' ); // loading | ready | error | notfound
	const [ item, setItem ] = useState( null );
	const abortRef = useRef( null );
	const panelRef = useRef( null );
	const previouslyFocusedRef = useRef( null );

	// Fetch detail whenever the slug changes.
	useEffect( () => {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		const controller = new AbortController();
		abortRef.current = controller;

		setStatus( 'loading' );
		setItem( null );

		fetchIntegrationBySlug( slug, controller.signal )
			.then( ( record ) => {
				if ( controller.signal.aborted ) {
					return;
				}
				if ( record ) {
					setItem( record );
					setStatus( 'ready' );
				} else {
					setStatus( 'notfound' );
				}
			} )
			.catch( ( err ) => {
				if ( err && err.name === 'AbortError' ) {
					return;
				}
				setStatus( 'error' );
			} );

		return () => {
			controller.abort();
		};
	}, [ slug ] );

	// Close on Escape.
	useEffect( () => {
		const onKey = ( e ) => {
			if ( 'Escape' === e.key ) {
				onClose();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ onClose ] );

	// Focus management for the modal dialog (role="dialog" aria-modal="true"):
	// move focus into the panel on open and restore it to the opener on close.
	useEffect( () => {
		previouslyFocusedRef.current = document.activeElement;
		const closeBtn = panelRef.current && panelRef.current.querySelector( '.bb-integrations-drawer__close' );
		if ( closeBtn ) {
			closeBtn.focus();
		}
		return () => {
			if ( previouslyFocusedRef.current && previouslyFocusedRef.current.focus ) {
				previouslyFocusedRef.current.focus();
			}
		};
	}, [] );

	// Trap Tab focus inside the panel while the dialog is open.
	useEffect( () => {
		const onTrap = ( e ) => {
			if ( 'Tab' !== e.key || ! panelRef.current ) {
				return;
			}
			const focusables = panelRef.current.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])'
			);
			if ( ! focusables.length ) {
				return;
			}
			const first = focusables[ 0 ];
			const last = focusables[ focusables.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		};
		document.addEventListener( 'keydown', onTrap );
		return () => document.removeEventListener( 'keydown', onTrap );
	}, [] );

	// Make the rest of the page inert while the dialog is open, so a screen
	// reader's virtual cursor can't wander into the grid behind it (aria-modal
	// alone doesn't stop that). Walk from the drawer up to the app root, marking
	// every sibling along the way inert; the drawer's own ancestor chain is left
	// interactive. Restored on unmount.
	useEffect( () => {
		const drawerEl = panelRef.current && panelRef.current.closest( '.bb-integrations-drawer' );
		const root = document.getElementById( 'bb-admin-integrations' ) || document.querySelector( '.bb-admin-app' );
		if ( ! drawerEl || ! root ) {
			return undefined;
		}
		const inerted = [];
		let node = drawerEl;
		while ( node && node !== root && node.parentElement ) {
			Array.from( node.parentElement.children ).forEach( ( sibling ) => {
				if ( sibling !== node && ! sibling.hasAttribute( 'inert' ) ) {
					sibling.setAttribute( 'inert', '' );
					inerted.push( sibling );
				}
			} );
			node = node.parentElement;
		}
		return () => inerted.forEach( ( el ) => el.removeAttribute( 'inert' ) );
	}, [] );

	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	// Top-bar name: prefer the fetched title, fall back to the title handed over
	// from the clicked card. initialTitle is already decoded by the card, so it is
	// used as-is (decoding it again would over-decode e.g. &amp;amp;).
	const headerName = title || initialTitle || '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';
	// Resolve the sanitized logo URL up front; safeImageUrl returns '' for a
	// non-http(s) URL, in which case we fall back to the placeholder icon.
	const logoSrc = logo ? safeImageUrl( logo ) : '';
	const contentHtml = item?.content?.rendered ? item.content.rendered : '';
	// Sanitize once per content change — DOMParser is expensive and the drawer
	// re-renders on every plugins-prop identity change (activate/deactivate).
	const sanitizedContent = useMemo( () => ( contentHtml ? sanitizeKbArticle( contentHtml ) : '' ), [ contentHtml ] );
	// "Learn More ↗" → the plugin's own page (acf.plugin_link), falling back to
	// the integration page so there is always somewhere to go.
	const learnMoreUrl = item?.acf?.plugin_link || item?.link || item?.link_url || '';

	// "Works with" shows only once the plugin is actually installed on this site
	// (active or inactive) — not for pro plugins or ones not yet installed.
	const planLabel = ( item?.acf?.type_label || '' ).trim().toLowerCase();
	const isPaid = '' !== planLabel && 'free' !== planLabel;
	const pluginSlug = isPaid ? null : wporgSlug( item?.acf?.plugin_link || '' );
	const isInstalled = !! ( pluginSlug && plugins && plugins.installed && plugins.installed[ pluginSlug ] );

	// "Works with" — the integration's required platforms (integrations_require-<slug>
	// in class_list), resolved to name + ✓/✗ via the localized requirements map.
	const requirements = ( typeof window !== 'undefined' && window.bbIntegrationsData && window.bbIntegrationsData.requirements ) || {};
	const worksWith = ( Array.isArray( item?.class_list ) ? item.class_list : [] )
		.map( ( cls ) => {
			const match = /^integrations_require-(.+)$/.exec( cls );
			return match ? requirements[ match[ 1 ] ] : null;
		} )
		.filter( Boolean );

	return (
		<div className="bb-integrations-drawer" role="dialog" aria-modal="true" aria-label={ headerName || __( 'Integration details', 'buddyboss' ) }>
			<div className="bb-integrations-drawer__overlay" onClick={ onClose } aria-hidden="true" />

			<div className="bb-integrations-drawer__panel" ref={ panelRef }>
				<div className="bb-integrations-drawer__topbar">
					<h2 className="bb-integrations-drawer__name">{ headerName }</h2>
					<button
						type="button"
						className="bb-integrations-drawer__close"
						onClick={ onClose }
						aria-label={ __( 'Close', 'buddyboss' ) }
					>
						<i className="bb-icons-rl bb-icons-rl-x" aria-hidden="true" />
					</button>
				</div>

				{ 'loading' === status && (
					<div className="bb-integrations-drawer__state" aria-busy="true">
						<span className="spinner is-active" />
					</div>
				) }

				{ 'error' === status && (
					<div className="bb-integrations-drawer__state" role="alert">
						<p>{ __( 'We couldn’t load this integration. Please try again.', 'buddyboss' ) }</p>
					</div>
				) }

				{ 'notfound' === status && (
					<div className="bb-integrations-drawer__state">
						<p>{ __( 'This integration is no longer available.', 'buddyboss' ) }</p>
					</div>
				) }

				{ 'ready' === status && item && (
					<div className="bb-integrations-drawer__content">
						<div className="bb-integrations-drawer__header">
							<span className="bb-integrations-drawer__icon">
								{ logoSrc ? (
									<img src={ logoSrc } alt="" />
								) : (
									<i className="bb-icons-rl bb-icons-rl-puzzle-piece" aria-hidden="true" />
								) }
							</span>
							{ /* h3: the topbar __name <h2> is the dialog's primary heading. */ }
							<h3 className="bb-integrations-drawer__title">
								{ title }
							</h3>
							{ description && (
								<p className="bb-integrations-drawer__desc">{ description }</p>
							) }

							<div className="bb-integrations-drawer__actions">
								<PluginActionButton item={ item } plugins={ plugins } className="bb-integrations__btn bb-integrations__btn--fill" hideUnavailable />
								{ learnMoreUrl && (
									<a
										href={ safeUrl( learnMoreUrl ) }
										className="bb-integrations__btn bb-integrations__btn--outline"
										target="_blank"
										rel="noopener noreferrer"
									>
										{ __( 'Learn More', 'buddyboss' ) }
										<i className="bb-icons-rl bb-icons-rl-arrow-up-right" aria-hidden="true" />
									</a>
								) }
							</div>
						</div>

						{ /* "Works with" compatibility — only once the plugin is installed. */ }
						{ isInstalled && worksWith.length > 0 && (
							<div className="bb-integrations-drawer__works-with">
								<span className="bb-integrations-drawer__works-with-label">{ __( 'Works with:', 'buddyboss' ) }</span>
								{ worksWith.map( ( req, i ) => (
									<span key={ req.name } className="bb-integrations-drawer__works-with-item">
										{ i > 0 && (
											<span className="bb-integrations-drawer__works-with-sep" aria-hidden="true">·</span>
										) }
										<span className="bb-integrations-drawer__works-with-name">{ req.name }</span>
										<span
											className={ 'bb-integrations-drawer__works-with-icon bb-integrations-drawer__works-with-icon--' + ( req.met ? 'yes' : 'no' ) }
											role="img"
											aria-label={ req.met ? __( 'Compatible', 'buddyboss' ) : __( 'Not compatible', 'buddyboss' ) }
										>
											<i className={ req.met ? 'bb-icons-rl bb-icons-rl-check' : 'bb-icons-rl bb-icons-rl-x' } aria-hidden="true" />
										</span>
									</span>
								) ) }
							</div>
						) }

						{ sanitizedContent && (
							<div
								className="bb-integrations-drawer__body"
								// Same rich-content sanitizer the Knowledge Base modal uses — allows
								// WP block markup, images and trusted video embeds (YouTube/Vimeo).
								// Memoized above so DOMParser only re-runs when the HTML changes.
								// eslint-disable-next-line react/no-danger
								dangerouslySetInnerHTML={ { __html: sanitizedContent } }
							/>
						) }
					</div>
				) }
			</div>
		</div>
	);
}
