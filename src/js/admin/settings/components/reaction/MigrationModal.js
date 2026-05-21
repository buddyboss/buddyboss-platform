/**
 * BuddyBoss Admin Settings 2.0 - Migration Modal Component
 *
 * Modal for starting and displaying the Reactions migration wizard.
 *
 * Renders a fully React-driven UI that consumes the structured `data`
 * payload returned by `bb_pro_reaction_get_migration_wizard()` (Pro PHP).
 * Replaced the previous raw-HTML injection + document-level event listeners
 * with React state, CheckboxControl, and SelectControl.
 *
 * Two screens:
 *   1. Selection — admin picks which reactions to migrate and (for the
 *      footer_wizard variant only) a target emotion.
 *   2. Confirmation — recap copy + warnings + "Start conversion" button.
 *
 * Wizard variants (driven by `migrationData.wizardType`):
 *   - 'footer' — footer migration wizard link. Both reactions (Likes +
 *     emotions) and `target_emotions` are populated. Admin must pick a
 *     target emotion via <SelectControl>.
 *   - 'switch' — emotions-mode → likes-mode switch flow. Only emotion
 *     reactions are populated; target is always "Likes" (no target
 *     select rendered).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Modal, CheckboxControl, SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { formatNumber } from '../../utils/format';
import { sanitizeHtml } from '../../utils/sanitize';

export function MigrationModal( { isOpen, onClose, migrationData } ) {
	const [ loading, setLoading ]                 = useState( true );
	const [ wizardLabel, setWizardLabel ]         = useState( __( 'Migration wizard', 'buddyboss' ) );
	const [ wizardData, setWizardData ]           = useState( null );
	const [ currentScreen, setCurrentScreen ]     = useState( 1 );
	const [ selectedReactions, setSelectedReactions ] = useState( {} );
	const [ targetEmotion, setTargetEmotion ]     = useState( '' );
	const [ submitting, setSubmitting ]           = useState( false );
	const [ error, setError ]                     = useState( '' );
	// Back-compat fallback for environments running an older Pro that only
	// returns the legacy `content` HTML (no structured `data` payload). We
	// render that HTML inside the React modal via sanitizeHtml() so admins
	// running mismatched Platform/Pro branches still see *something* useful
	// instead of a dead-end error — the form is read-only because the legacy
	// jQuery handlers aren't enqueued on the Settings 2.0 page.
	const [ legacyContent, setLegacyContent ]     = useState( '' );

	// Refs so submit/close handlers always close over the latest prop values
	// without forcing useCallback dependencies that would churn the effect.
	const onCloseRef = useRef( onClose );
	onCloseRef.current = onClose;
	const migrationDataRef = useRef( migrationData );
	migrationDataRef.current = migrationData;

	/**
	 * Wizard variant — drives which AJAX action loads the data and whether
	 * the target-emotion <SelectControl> is rendered on screen 1.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const wizardType = ( migrationData && migrationData.wizardType ) || 'footer';
	const isFooterWizard = 'footer' === wizardType;

	/**
	 * Notice-triggered context. When the modal opens from the reaction-migration
	 * notice, the spread payload carries `action` ('like_to_emotions_action' or
	 * 'emotions_to_like_action') and the stored `total_reactions`. The modal
	 * uses these to derive the title, customize the intro copy with the count,
	 * and pre-tick the relevant reactions in the source list.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const migrationAction = ( migrationData && migrationData.action ) || '';
	const migrationCount  = ( migrationData && migrationData.total_reactions ) || 0;

	/**
	 * Whether the wizard offers a target-emotion select. Only the footer
	 * wizard (Likes/Emotions → target emotion) needs it; the switch wizard
	 * always targets "Likes" so the select is hidden and `targetEmotion`
	 * stays empty.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const needsTargetSelect = isFooterWizard
		&& wizardData
		&& Array.isArray( wizardData.target_emotions )
		&& wizardData.target_emotions.length > 0;

	/**
	 * Load wizard data from the appropriate Pro AJAX endpoint and seed the
	 * structured payload into local state. Falls back to an error message
	 * when the endpoint returns no `data` key (e.g. running against an old
	 * Pro version that hasn't shipped the structured-data update yet).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const loadWizardData = useCallback( function () {
		setLoading( true );
		setError( '' );
		setWizardData( null );
		setCurrentScreen( 1 );
		setSelectedReactions( {} );
		setTargetEmotion( '' );
		setLegacyContent( '' );

		// Derive the wizard title from the notice action when present so the
		// modal opens with "Convert Likes" / "Convert Reactions" instead of the
		// generic default. Server `response.data.label` (returned by the footer
		// endpoint via the reconciliation helper) may override below.
		const noticeAction = ( migrationDataRef.current && migrationDataRef.current.action ) || '';
		setWizardLabel(
			'like_to_emotions_action' === noticeAction
				? __( 'Convert Likes', 'buddyboss' )
				: 'emotions_to_like_action' === noticeAction
					? __( 'Convert Reactions', 'buddyboss' )
					: __( 'Migration wizard', 'buddyboss' )
		);

		if ( ! window.bbReactionAdminVars || ! window.bbReactionAdminVars.ajax_url ) {
			setError( __( 'Unable to load migration wizard.', 'buddyboss' ) );
			setLoading( false );
			return;
		}

		const type = ( migrationDataRef.current && migrationDataRef.current.wizardType ) || 'footer';
		const ajaxAction = 'footer' === type
			? 'bb_pro_reaction_footer_migration'
			: 'bb_pro_reaction_migration_start_conversion';
		const ajaxNonce = 'footer' === type
			? ( window.bbReactionAdminVars.nonce && window.bbReactionAdminVars.nonce.footer_migration ) || ''
			: ( window.bbReactionAdminVars.nonce && window.bbReactionAdminVars.nonce.migration_start_conversion ) || '';

		const formData = new FormData();
		formData.append( 'action', ajaxAction );
		formData.append( 'nonce', ajaxNonce );

		fetch( window.bbReactionAdminVars.ajax_url, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					// Capture the raw response body so a non-JSON 500/403
					// payload (e.g. a PHP fatal rendered as an HTML page)
					// surfaces in the console with its contents instead of
					// silently falling through to the generic catch.
					return response.text().then( function ( body ) {
						throw new Error( 'HTTP ' + response.status + ' — ' + ( body || 'empty response' ).slice( 0, 200 ) );
					} );
				}
				return response.text().then( function ( raw ) {
					// Parse manually so JSON-parse failures (PHP warnings
					// printed before the JSON body) include the raw bytes
					// in the console for debugging.
					try {
						return JSON.parse( raw );
					} catch ( parseErr ) {
						throw new Error( 'Invalid JSON response — ' + raw.slice( 0, 200 ) );
					}
				} );
			} )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					// Apply the server-provided label only when the modal was
					// opened from the notice (i.e. there's a pending migration
					// action in client-side context). The footer endpoint always
					// returns the reconcile-derived label so it can serve both
					// flows, but for the inline "migration wizard" link we want
					// the generic title set by the open-time fallback above.
					const hasNoticeContext = !! ( migrationDataRef.current && migrationDataRef.current.action );
					if ( response.data.label && hasNoticeContext ) {
						setWizardLabel( response.data.label );
					}
					if ( response.data.data && Array.isArray( response.data.data.reactions ) ) {
						setWizardData( response.data.data );

						// Notice-triggered pre-selection — only the likes→emotions
						// direction pre-ticks, mirroring legacy where the
						// "Convert Likes" modal treated Likes as implicit (no
						// source checkbox rendered). The emotions→likes flow
						// leaves checkboxes unticked so the admin must explicitly
						// pick which emotions to convert, matching the legacy
						// "Convert Reactions" modal.
						const prescopedAction = ( migrationDataRef.current && migrationDataRef.current.action ) || '';
						if ( 'like_to_emotions_action' === prescopedAction ) {
							const prescoped = {};
							response.data.data.reactions.forEach( function ( r ) {
								if ( 'likes' === r.group ) {
									prescoped[ r.id ] = true;
								}
							} );
							if ( Object.keys( prescoped ).length > 0 ) {
								setSelectedReactions( prescoped );
							}
						} else if (
							! prescopedAction &&
							Array.isArray( response.data.data.target_emotions ) &&
							response.data.data.target_emotions.length > 0
						) {
							// Inline footer-wizard flow — legacy <select> had no empty
							// "Select an emotion" option, so the browser surfaced the
							// first emotion as the active value. Mirror that by seeding
							// targetEmotion with the first option's id; admin can still
							// switch via the dropdown before clicking Continue.
							setTargetEmotion( String( response.data.data.target_emotions[ 0 ].value ) );
						}
					} else if ( 'string' === typeof response.data.content && response.data.content.length > 0 ) {
						// Older Pro versions return only the legacy `content` HTML
						// (no structured `data` payload). Render that HTML so the
						// admin sees the wizard form instead of an error — the
						// legacy jQuery handlers aren't loaded on the Settings 2.0
						// page so it's read-only, but it surfaces what Pro would
						// have shown and lets the admin understand the state
						// without forcing a Pro update first.
						setLegacyContent( response.data.content );
					} else if ( response.data.message ) {
						setError( response.data.message );
					} else {
						setError( __( 'Migration wizard data is unavailable. Please update BuddyBoss Pro.', 'buddyboss' ) );
					}
				} else if ( response.data && response.data.message ) {
					setError( response.data.message );
				} else {
					if ( window.console && window.console.error ) {
						window.console.error( 'Migration wizard AJAX returned no usable payload:', response );
					}
					setError( __( 'Unable to load migration wizard.', 'buddyboss' ) );
				}
				setLoading( false );
			} )
			.catch( function ( err ) {
				if ( window.console && window.console.error ) {
					window.console.error( 'Migration wizard AJAX failed:', err );
				}
				setError(
					sprintf(
						/* translators: %s: short error description (HTTP status or parse error). */
						__( 'Unable to load migration wizard (%s).', 'buddyboss' ),
						err && err.message ? err.message : __( 'unknown error', 'buddyboss' )
					)
				);
				setLoading( false );
			} );
	}, [] );

	useEffect( function () {
		if ( isOpen ) {
			loadWizardData();
		}
	}, [ isOpen, loadWizardData ] );

	/**
	 * Toggle the selection state for a single reaction id.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const toggleReaction = useCallback( function ( reactionId, checked ) {
		setSelectedReactions( function ( prev ) {
			const next = Object.assign( {}, prev );
			if ( checked ) {
				next[ reactionId ] = true;
			} else {
				delete next[ reactionId ];
			}
			return next;
		} );
	}, [] );

	/**
	 * Select/deselect all reactions in a group at once. Used by the
	 * "Emotions (N)" / "All emotions (N)" group checkbox so admins with
	 * many emotions don't have to tick each row individually.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const toggleAllInGroup = useCallback( function ( reactions, group, checked ) {
		setSelectedReactions( function ( prev ) {
			const next = Object.assign( {}, prev );
			reactions.forEach( function ( r ) {
				if ( r.group === group ) {
					if ( checked ) {
						next[ r.id ] = true;
					} else {
						delete next[ r.id ];
					}
				}
			} );
			return next;
		} );
	}, [] );

	/**
	 * Memoized derived state for the Continue button. Computed from the
	 * latest selection + target so a render-cycle race can't enable the
	 * button before all required inputs are present.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const canContinue = useMemo( function () {
		const hasSelection = Object.keys( selectedReactions ).length > 0;
		if ( ! hasSelection ) {
			return false;
		}
		if ( needsTargetSelect ) {
			return '' !== String( targetEmotion );
		}
		return true;
	}, [ selectedReactions, targetEmotion, needsTargetSelect ] );

	/**
	 * Selected target emotion label — used in the confirmation screen's
	 * "You are about to convert X to <label>" copy. Falls back to "Likes"
	 * when the wizard is the switch variant (target is always Likes there).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const targetLabel = useMemo( function () {
		if ( ! isFooterWizard ) {
			return __( 'Likes', 'buddyboss' );
		}
		if ( ! wizardData || ! Array.isArray( wizardData.target_emotions ) ) {
			return '';
		}
		const match = wizardData.target_emotions.filter( function ( opt ) {
			return String( opt.value ) === String( targetEmotion );
		} );
		return match.length > 0 ? match[ 0 ].label : '';
	}, [ targetEmotion, wizardData, isFooterWizard ] );

	/**
	 * Total reactions selected — used in the confirmation copy "X reactions".
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const selectedCount = useMemo( function () {
		if ( ! wizardData || ! Array.isArray( wizardData.reactions ) ) {
			return 0;
		}
		return wizardData.reactions.reduce( function ( total, r ) {
			return selectedReactions[ r.id ] ? total + ( r.count || 0 ) : total;
		}, 0 );
	}, [ selectedReactions, wizardData ] );

	/**
	 * Submit the migration. POSTs the same settings payload shape the
	 * legacy jQuery handler used so the existing
	 * `bb_admin_save_feature_settings` consumer needs no changes.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	const handleStartConversion = useCallback( function () {
		setSubmitting( true );
		setError( '' );

		const settings = {
			migration_action: isFooterWizard ? 'footer' : 'switch',
			from_reactions:   Object.keys( selectedReactions ),
		};
		if ( needsTargetSelect && targetEmotion ) {
			settings.to_reactions = String( targetEmotion );
		}

		const formData = new FormData();
		formData.append( 'action', 'bb_admin_save_feature_settings' );
		formData.append( 'nonce', ( window.bbAdminData && window.bbAdminData.ajaxNonce ) || '' );
		formData.append( 'feature_id', 'reactions' );
		formData.append( 'settings', JSON.stringify( settings ) );

		const ajaxUrl = ( window.bbAdminData && window.bbAdminData.ajaxUrl )
			|| window.ajaxurl
			|| '/wp-admin/admin-ajax.php';

		fetch( ajaxUrl, {
			method:      'POST',
			credentials: 'same-origin',
			body:        formData,
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( response ) {
				if ( response.success ) {
					// Close the modal and let the parent refetch feature data
					// (same custom event the legacy implementation fired).
					window.dispatchEvent( new CustomEvent( 'bb-admin-refetch-feature' ) );
					onCloseRef.current();
				} else {
					setSubmitting( false );
					setError(
						( response.data && response.data.message )
							|| __( 'Migration failed. Please try again.', 'buddyboss' )
					);
				}
			} )
			.catch( function () {
				setSubmitting( false );
				setError( __( 'Migration failed. Please try again.', 'buddyboss' ) );
			} );
	}, [ isFooterWizard, needsTargetSelect, selectedReactions, targetEmotion ] );

	if ( ! isOpen ) {
		return null;
	}

	// Group-checkbox "Emotions (N)" / "All emotions (N)" totals — computed
	// once per render so the group toggle reflects whether every emotion
	// in the wizard's emotion subset is currently selected.
	const emotionRows = wizardData && Array.isArray( wizardData.reactions )
		? wizardData.reactions.filter( function ( r ) { return 'emotions' === r.group; } )
		: [];
	const emotionTotalCount = emotionRows.reduce( function ( t, r ) { return t + ( r.count || 0 ); }, 0 );
	const allEmotionsChecked = emotionRows.length > 0
		&& emotionRows.every( function ( r ) { return !! selectedReactions[ r.id ]; } );

	return (
		<Modal
			title={ wizardLabel }
			onRequestClose={ onClose }
			className="bb-admin-migration-modal bb-admin-settings-modal"
			__experimentalHideHeader={ false }
		>
			<div className="bb-admin-migration-modal__content">
				{ loading && (
					<div className="bb-admin-migration-modal__loader">
						<span className="bb-icons-rl bb-icons-rl-spinner animate-spin" />
					</div>
				) }

				{ error && ! loading && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--no-data">
						<div className="bb-admin-notice bb-admin-notice--error">
							<p>{ error }</p>
						</div>
						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-primary"
								onClick={ onClose }
							>
								{ __( 'Close', 'buddyboss' ) }
							</button>
						</div>
					</div>
				) }

				{ /* Legacy-content fallback — older Pro returns only `content`
				     (raw HTML) without the structured `data` payload. Render
				     the HTML as-is; it already contains its own Cancel /
				     Continue buttons. Sanitized through sanitizeHtml() using
				     the same allow-list other Settings 2.0 screens use
				     (ProfileTypeScreen, ForumsListScreen, etc.). The legacy
				     jQuery handlers aren't enqueued on the Settings 2.0 page
				     so the form is visual-only — admins can still dismiss via
				     the Modal X. */ }
				{ ! loading && ! error && ! wizardData && legacyContent && (
					<div
						className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--legacy"
						dangerouslySetInnerHTML={ { __html: sanitizeHtml( legacyContent ) } }
					/>
				) }

				{ /* No-data state — admin opened a wizard variant with no
				     applicable reactions to convert (e.g. footer_wizard
				     while site is in likes mode). PHP sets
				     `wizardData.no_data` true and provides a localized
				     message; React renders a clean "no reactions" panel
				     + Close action so the modal isn't an empty form. */ }
				{ ! loading && ! error && wizardData && wizardData.no_data && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--no-data">
						<p className="bb-admin-migration-modal__no-data-heading">
							<strong>{ __( 'You have no reactions to convert', 'buddyboss' ) }</strong>
						</p>
						<p>{ wizardData.no_data_message || __( 'No reactions are available to convert.', 'buddyboss' ) }</p>
						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-primary"
								onClick={ onClose }
							>
								{ __( 'Close', 'buddyboss' ) }
							</button>
						</div>
					</div>
				) }

				{ ! loading && ! error && wizardData && ! wizardData.no_data && 1 === currentScreen && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--screen-1">
						{ 'like_to_emotions_action' === migrationAction && migrationCount > 0 ? (
							<p>
								{ __( 'This action will convert the ', 'buddyboss' ) }
								<strong>
									{ sprintf(
										/* translators: %s: formatted reaction count. */
										'like_to_emotions_action' === migrationAction
											? __( '%s Likes', 'buddyboss' )
											: __( '%s reactions', 'buddyboss' ),
										formatNumber( migrationCount )
									) }
								</strong>
								{ ' ' }
								{ 'like_to_emotions_action' === migrationAction
									? __( 'previously submitted by members on your site to an Emotion of your choice. You can perform this action at any point in the future using this migration wizard.', 'buddyboss' )
									: __( 'previously submitted by members on your site to Likes. You can perform this action at any point in the future using this migration wizard.', 'buddyboss' )
								}
							</p>
						) : (
							<p>
								{ needsTargetSelect
									? __( 'This action will convert reactions previously submitted by members on your site to an Emotion of your choice. Reactions not selected can be converted at any point in the future using this migration wizard.', 'buddyboss' )
									: __( 'This action will convert reactions previously submitted by members on your site to Likes. Reactions not selected can be converted at any point in the future using the migration wizard.', 'buddyboss' )
								}
							</p>
						) }

						{ /* Source-reaction selector. Hidden when the modal is opened from
						     the migration notice — at that point the scope is fixed
						     (Likes for like→emotion, all emotions for emotion→like) and
						     pre-ticked on load, matching the legacy single-question modal. */ }
						{ ! ( 'like_to_emotions_action' === migrationAction && migrationCount > 0 ) && (
							<>
								<p className="bb-admin-migration-modal__section-heading">
									<strong>
										{ needsTargetSelect
											? __( 'Which reactions do you want to convert?', 'buddyboss' )
											: __( 'Which reactions do you want to convert to Likes?', 'buddyboss' )
										}
									</strong>
								</p>

								<div className="bb-admin-migration-modal__reactions">
									{ /* Likes row (footer_wizard only — switch_wizard targets Likes so doesn't list it as a source). */ }
									{ wizardData.reactions
										.filter( function ( r ) { return 'likes' === r.group; } )
										.map( function ( r ) {
											return (
												<CheckboxControl
													key={ r.id }
													label={ sprintf(
														/* translators: 1: reaction label (e.g. "Likes"), 2: formatted count (e.g. "1,829"). */
														__( '%1$s (%2$s)', 'buddyboss' ),
														r.label,
														formatNumber( r.count )
													) }
													checked={ !! selectedReactions[ r.id ] }
													onChange={ function ( checked ) { toggleReaction( r.id, checked ); } }
													__nextHasNoMarginBottom
												/>
											);
										} )
									}

									{ /* Emotions group header — toggles every emotion row at once. */ }
									{ emotionRows.length > 0 && (
										<CheckboxControl
											className="bb-admin-migration-modal__reactions-group-toggle"
											label={ sprintf(
												/* translators: 1: "Emotions" or "All emotions", 2: formatted total count. */
												__( '%1$s (%2$s)', 'buddyboss' ),
												isFooterWizard
													? __( 'Emotions', 'buddyboss' )
													: __( 'All emotions', 'buddyboss' ),
												formatNumber( emotionTotalCount )
											) }
											checked={ allEmotionsChecked }
											onChange={ function ( checked ) { toggleAllInGroup( wizardData.reactions, 'emotions', checked ); } }
											__nextHasNoMarginBottom
										/>
									) }

									{ /* Individual emotion rows — indented under the group toggle. */ }
									<ul className="bb-admin-migration-modal__reactions-list">
										{ emotionRows.map( function ( r ) {
											return (
												<li key={ r.id }>
													<CheckboxControl
														label={ sprintf(
															/* translators: 1: emotion label, 2: formatted count. */
															__( '%1$s (%2$s)', 'buddyboss' ),
															r.label,
															formatNumber( r.count )
														) }
														checked={ !! selectedReactions[ r.id ] }
														onChange={ function ( checked ) { toggleReaction( r.id, checked ); } }
														__nextHasNoMarginBottom
													/>
												</li>
											);
										} ) }
									</ul>
								</div>
							</>
						) }

						{ needsTargetSelect && (
							<>
								<p className="bb-admin-migration-modal__section-heading">
									<strong>
										{ 'like_to_emotions_action' === migrationAction
											? __( 'Which Emotion would you like to convert your Likes to?', 'buddyboss' )
											: __( 'Which Emotion would you like to convert your reactions to?', 'buddyboss' )
										}
									</strong>
								</p>
								<SelectControl
									className="bb-admin-migration-modal__target-select"
									value={ targetEmotion }
									options={ [
										// Leading empty option mirrors the legacy "Select an
										// Emotion" placeholder; for the inline footer-wizard
										// flow the first emotion is pre-selected on load so
										// this option is never the active one. canContinue
										// keeps Continue disabled while the empty value is
										// selected.
										{ value: '', label: __( 'Select an emotion', 'buddyboss' ) },
										...wizardData.target_emotions.map( function ( opt ) {
											return { value: String( opt.value ), label: opt.label };
										} )
									] }
									onChange={ function ( newValue ) { setTargetEmotion( newValue ); } }
									__nextHasNoMarginBottom
								/>
							</>
						) }

						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-secondary"
								onClick={ onClose }
							>
								{ __( 'Cancel', 'buddyboss' ) }
							</button>
							<button
								type="button"
								className="components-button is-primary"
								onClick={ function () { setCurrentScreen( 2 ); } }
								disabled={ ! canContinue }
							>
								{ __( 'Continue', 'buddyboss' ) }
							</button>
						</div>
					</div>
				) }

				{ ! loading && ! error && wizardData && ! wizardData.no_data && 2 === currentScreen && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--screen-2">
						<p>
							{ sprintf(
								/* translators: 1: count of selected reactions, 2: target label (emotion name or "Likes"). */
								_n(
									'You are about to convert %1$d reaction to %2$s.',
									'You are about to convert %1$d reactions to %2$s.',
									selectedCount,
									'buddyboss'
								),
								selectedCount,
								targetLabel
							) }
						</p>

						<ul className="bb-admin-migration-modal__warnings">
							<li>{ __( 'The new reactions will be immediately visible on your site after being converted.', 'buddyboss' ) }</li>
							<li>{ __( 'Depending on the amount of data to convert, the migration may take a while.', 'buddyboss' ) }</li>
							<li>{ __( 'You will be unable to edit reactions while the conversion is in progress.', 'buddyboss' ) }</li>
							<li>{ __( 'This action cannot be undone, but you can convert reactions to another reaction in the future.', 'buddyboss' ) }</li>
							<li>{ __( 'We recommend backing up your site before migrating and performing this action during an off-peak period.', 'buddyboss' ) }</li>
						</ul>

						<p>{ __( 'Do you want to start the conversion now?', 'buddyboss' ) }</p>

						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-secondary"
								onClick={ function () { setCurrentScreen( 1 ); } }
								disabled={ submitting }
							>
								{ __( 'Back', 'buddyboss' ) }
							</button>
							<button
								type="button"
								className="components-button is-primary"
								onClick={ handleStartConversion }
								disabled={ submitting }
							>
								{ submitting ? __( 'Converting…', 'buddyboss' ) : __( 'Start conversion', 'buddyboss' ) }
							</button>
						</div>
					</div>
				) }
			</div>
		</Modal>
	);
}
