/**
 * BuddyBoss Integrations marketplace — detail drawer (right slide-in).
 *
 * Single detail presentation (the centered "About" popup from Figma was
 * dropped). Fetches the integration's full record by slug, renders the header
 * card + sanitized `content.rendered`. Loading / error / empty states mirror the
 * Knowledge Base article view.
 *
 * PENDING TEAM (placeholders, isolated):
 *  - "Works with" badges (Q6): candidate source is integrations_require term
 *    names; hidden until the ID→badge mapping is confirmed.
 *  - Install vs Learn More (Q4): header shows Learn More for now.
 * See docs/superpowers/specs/2026-06-24-integrations-marketplace-design.md.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { fetchIntegrationBySlug } from '../../utils/integrationsApi';
import { sanitizeHtml, safeUrl } from '@bb/admin-common';

export function IntegrationDrawer( { slug, onClose } ) {
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

	const title = item?.title?.rendered ? decodeEntities( item.title.rendered ) : '';
	const description = item?.short_description ? decodeEntities( item.short_description ) : '';
	const collection = item?.collection_name ? decodeEntities( item.collection_name ) : '';
	const logo = item?.logo_image_url && 'string' === typeof item.logo_image_url ? item.logo_image_url : '';
	const learnMoreUrl = item?.link || item?.link_url || '';
	const contentHtml = item?.content?.rendered ? item.content.rendered : '';

	return (
		<div className="bb-integrations-drawer" role="dialog" aria-modal="true" aria-label={ title || __( 'Integration details', 'buddyboss' ) }>
			<div className="bb-integrations-drawer__overlay" onClick={ onClose } aria-hidden="true" />

			<div className="bb-integrations-drawer__panel" ref={ panelRef }>
				<button
					type="button"
					className="bb-integrations-drawer__close"
					onClick={ onClose }
					aria-label={ __( 'Close', 'buddyboss' ) }
				>
					<i className="bb-icons-rl bb-icons-rl-x" aria-hidden="true" />
				</button>

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
								{ logo ? (
									<img src={ safeUrl( logo ) } alt="" />
								) : (
									<i className="bb-icons-rl bb-icons-rl-puzzle-piece" aria-hidden="true" />
								) }
							</span>
							<h2 className="bb-integrations-drawer__title">
								{ title }
								{ collection && (
									<span className="bb-integrations-drawer__collection"> · { collection }</span>
								) }
							</h2>
							{ description && (
								<p className="bb-integrations-drawer__desc">{ description }</p>
							) }

							{ /* PENDING Q6: "Works with" badges (integrations_require → names) go here. */ }

							<div className="bb-integrations-drawer__actions">
								{ /* PENDING Q4: Install/Activate goes here when the install path is defined. */ }
								{ learnMoreUrl && (
									<a
										href={ safeUrl( learnMoreUrl ) }
										className="button button-primary"
										target="_blank"
										rel="noopener noreferrer"
									>
										{ __( 'Learn More', 'buddyboss' ) }
									</a>
								) }
							</div>
						</div>

						{ contentHtml && (
							<div
								className="bb-integrations-drawer__body"
								// eslint-disable-next-line react/no-danger
								dangerouslySetInnerHTML={ { __html: sanitizeHtml( contentHtml ) } }
							/>
						) }
					</div>
				) }
			</div>
		</div>
	);
}
