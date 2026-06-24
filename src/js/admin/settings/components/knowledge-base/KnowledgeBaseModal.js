/**
 * BuddyBoss Admin Settings 2.0 - Knowledge Base Modal Shell
 *
 * Full-page modal shell that owns:
 *   - Overlay + dialog markup
 *   - ESC key handling
 *   - Tab focus trap inside the dialog
 *   - Focus capture on open / restore on close (prefers `triggerRef`,
 *     falls back to whatever element had focus when the modal opened)
 *   - A11y attributes (`role="dialog"`, `aria-modal`, `aria-labelledby`)
 *   - View routing — delegates body rendering to either `<KBLanding>`
 *     (Group I) or `<KBCategory>` (Group L). Until those land, both
 *     branches render placeholder strings used to verify the router.
 *
 * The shell is render-gated on `state.isOpen` and returns `null` while
 * closed so the document tree carries no cost when the KB is not in use.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useKb } from '../../context/KbContext';
import KBLanding from './KBLanding';
import KBCategory from './KBCategory';

/**
 * Knowledge Base modal shell.
 *
 * Does NOT fetch any data — landing/category children own their loading.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}                          props            Component props.
 * @param {{ current: ?HTMLElement }} [props.triggerRef] Ref to the button that
 *                                                     opened the modal — used
 *                                                     to restore focus on close.
 * @return {?React.Element} Modal element, or `null` when closed.
 */
export default function KnowledgeBaseModal( { triggerRef } ) {
	const { state, close }       = useKb();
	const dialogRef              = useRef( null );
	const previouslyFocusedRef   = useRef( null );

	// Focus capture on open, restore on close.
	useEffect( () => {
		if ( state.isOpen ) {
			previouslyFocusedRef.current = document.activeElement;
			const closeBtn = dialogRef.current && dialogRef.current.querySelector( '.bb-kb-modal__close' );
			if ( closeBtn ) {
				closeBtn.focus();
			}
		} else if ( previouslyFocusedRef.current ) {
			if ( triggerRef && triggerRef.current ) {
				triggerRef.current.focus();
			} else {
				try {
					previouslyFocusedRef.current.focus();
				} catch ( e ) {
					// Previously-focused node may be detached; swallow.
				}
			}
		}
	}, [ state.isOpen, triggerRef ] );

	// ESC closes the modal.
	useEffect( () => {
		if ( ! state.isOpen ) {
			return undefined;
		}
		const onKey = ( e ) => {
			if ( e.key === 'Escape' ) {
				e.stopPropagation();
				close();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ state.isOpen, close ] );

	// Tab focus trap.
	useEffect( () => {
		if ( ! state.isOpen ) {
			return undefined;
		}
		const onKey = ( e ) => {
			if ( e.key !== 'Tab' || ! dialogRef.current ) {
				return;
			}
			const focusable = dialogRef.current.querySelectorAll(
				'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"]), input, select, textarea'
			);
			if ( ! focusable.length ) {
				return;
			}
			const first = focusable[ 0 ];
			const last  = focusable[ focusable.length - 1 ];
			if ( e.shiftKey && document.activeElement === first ) {
				e.preventDefault();
				last.focus();
			} else if ( ! e.shiftKey && document.activeElement === last ) {
				e.preventDefault();
				first.focus();
			}
		};
		document.addEventListener( 'keydown', onKey );
		return () => document.removeEventListener( 'keydown', onKey );
	}, [ state.isOpen ] );

	if ( ! state.isOpen ) {
		return null;
	}

	return (
		<div
			className="bb-kb-modal-overlay"
			onClick={ ( e ) => {
				if ( e.target === e.currentTarget ) {
					close();
				}
			} }
		>
			<div
				ref={ dialogRef }
				className="bb-kb-modal"
				role="dialog"
				aria-modal="true"
				aria-labelledby="bb-kb-modal-title"
			>
				<header className="bb-kb-modal__header">
					<span id="bb-kb-modal-title" className="bb-kb-modal__brand">
						{ window.bbAdminData && window.bbAdminData.logoUrl ? (
							<>
								<img className="bb-kb-modal__brand-logo" src={ window.bbAdminData.logoUrl } alt="BuddyBoss" />
								<span className="bb-kb-modal__brand-separator"></span>
							</>
						) : null }
						{ __( 'Documentation', 'buddyboss' ) }
					</span>
					<button
						type="button"
						className="bb-kb-modal__close"
						onClick={ close }
						aria-label={ __( 'Close documentation', 'buddyboss' ) }
					>
						<i className="bb-icons-rl-x" aria-hidden="true" />
					</button>
				</header>
				<div className="bb-kb-modal__body">
					{ state.view === 'landing'
						? <KBLanding />
						: <KBCategory /> }
				</div>
			</div>
		</div>
	);
}
