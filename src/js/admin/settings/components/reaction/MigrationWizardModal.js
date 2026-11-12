/**
 * BuddyBoss Admin Settings 2.0 - Migration Wizard Modal (React).
 *
 * React-driven migration wizard rendered when Pro returns the structured
 * `data` payload from `bb_pro_reaction_get_migration_wizard()`. Replaces
 * the legacy raw-HTML injection modal (`./MigrationModal.js`); the two
 * coexist during the transition window and are routed by a Pro-side
 * `bbReactionAdminVars.use_react_wizard` flag in `SettingsForm.js`.
 *
 * Two screens:
 *   1. Selection — admin picks which reactions to migrate and (for the
 *      footer_wizard variant) a target emotion via <SelectControl>.
 *   2. Confirmation — recap copy + warnings + "Start conversion" button.
 *
 * Wizard variants (driven by `migrationData.wizardType`):
 *   - 'footer' — footer migration wizard link. Calls
 *     `bb_pro_reaction_footer_migration`. Both reactions (Likes +
 *     emotions) and `target_emotions` are populated.
 *   - 'switch' — notice "Start Conversion". Calls
 *     `bb_pro_reaction_migration_start_conversion`. Only emotion
 *     reactions populated; target is always "Likes".
 *
 * Notice-triggered context (when `migrationData.action` is set):
 *   - Title is derived from action ("Convert Likes" / "Convert Reactions").
 *   - For `like_to_emotions_action` the source checkbox section is hidden
 *     and Likes is pre-ticked, matching the legacy "Convert Likes" modal.
 *   - For `emotions_to_like_action` the checkboxes remain visible and
 *     unticked, matching the legacy "Convert Reactions" modal.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useCallback, useEffect, useMemo, useRef, useState } from '@wordpress/element';
import { Modal, CheckboxControl, SelectControl } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { formatNumber } from '../../utils/format';

export function MigrationWizardModal( { isOpen, onClose, migrationData } ) {
	const [ loading, setLoading ]                     = useState( true );
	const [ wizardLabel, setWizardLabel ]             = useState( __( 'Migration wizard', 'buddyboss-platform' ) );
	const [ wizardData, setWizardData ]               = useState( null );
	const [ currentScreen, setCurrentScreen ]         = useState( 1 );
	const [ selectedReactions, setSelectedReactions ] = useState( {} );
	const [ targetEmotion, setTargetEmotion ]         = useState( '' );
	const [ submitting, setSubmitting ]               = useState( false );
	const [ error, setError ]                         = useState( '' );

	// Refs so submit/close handlers always close over the latest prop values
	// without forcing useCallback dependencies that would churn the effect.
	const onCloseRef = useRef( onClose );
	onCloseRef.current = onClose;
	const migrationDataRef = useRef( migrationData );
	migrationDataRef.current = migrationData;

	// Wizard variant + derived notice-context — drives title, intro copy,
	// pre-selection, and whether the target-emotion <SelectControl> shows.
	const wizardType        = ( migrationData && migrationData.wizardType ) || 'footer';
	const isFooterWizard    = 'footer' === wizardType;
	const migrationAction   = ( migrationData && migrationData.action ) || '';
	const migrationCount    = ( migrationData && migrationData.total_reactions ) || 0;

	// Target select shows whenever the server payload includes a non-empty
	// `target_emotions` list. That's true for both the footer wizard (`footer`
	// variant) and the switch wizard's likes→emotion direction (`switch`
	// variant with `like_to_emotions_action`). The switch wizard's
	// emotions→likes direction omits `target_emotions` because the target is
	// implicitly Likes, so the select stays hidden there — matching legacy.
	const needsTargetSelect = wizardData
		&& Array.isArray( wizardData.target_emotions )
		&& wizardData.target_emotions.length > 0;

	/**
	 * Load wizard data from Pro's AJAX endpoint and seed the structured
	 * payload into local state. Surfaces a clear error when Pro doesn't
	 * return structured `data` (e.g. running against an older Pro that
	 * predates the wizard data payload).
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

		// Derive the wizard title from the notice action when present so the
		// modal opens with "Convert Likes" / "Convert Reactions" instead of the
		// generic default. Server `response.data.label` (returned by the
		// reconcile helper) may override below when notice context is set.
		const noticeAction = ( migrationDataRef.current && migrationDataRef.current.action ) || '';
		setWizardLabel(
			'like_to_emotions_action' === noticeAction
				? __( 'Convert Likes', 'buddyboss-platform' )
				: 'emotions_to_like_action' === noticeAction
					? __( 'Convert Reactions', 'buddyboss-platform' )
					: __( 'Migration wizard', 'buddyboss-platform' )
		);

		if ( ! window.bbReactionAdminVars || ! window.bbReactionAdminVars.ajax_url ) {
			setError( __( 'Unable to load migration wizard.', 'buddyboss-platform' ) );
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
					return response.text().then( function ( body ) {
						throw new Error( 'HTTP ' + response.status + ' — ' + ( body || 'empty response' ).slice( 0, 200 ) );
					} );
				}
				return response.text().then( function ( raw ) {
					try {
						return JSON.parse( raw );
					} catch ( parseErr ) {
						throw new Error( 'Invalid JSON response — ' + raw.slice( 0, 200 ) );
					}
				} );
			} )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					// Apply server label only when modal was opened from notice
					// context. The reconcile helper always returns a label; for
					// the inline "migration wizard" link we want the generic
					// title set above to stick.
					const hasNoticeContext = !! ( migrationDataRef.current && migrationDataRef.current.action );
					if ( response.data.label && hasNoticeContext ) {
						setWizardLabel( response.data.label );
					}

					if ( response.data.data && Array.isArray( response.data.data.reactions ) ) {
						setWizardData( response.data.data );

						// Notice-triggered pre-selection — only the likes→emotions
						// direction pre-ticks. The emotions→likes flow leaves
						// checkboxes unticked so the admin explicitly chooses
						// which emotions to convert (matches legacy).
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
							// Inline footer-wizard flow — legacy <select> had no
							// empty "Select an emotion" option. Seed first emotion
							// so the dropdown isn't empty on first render.
							setTargetEmotion( String( response.data.data.target_emotions[ 0 ].value ) );
						}
					} else if ( response.data.message ) {
						setError( response.data.message );
					} else {
						setError( __( 'Migration wizard data is unavailable. Please update BuddyBoss Pro.', 'buddyboss-platform' ) );
					}
				} else if ( response.data && response.data.message ) {
					setError( response.data.message );
				} else {
					if ( window.console && window.console.error ) {
						window.console.error( 'Migration wizard AJAX returned no usable payload:', response );
					}
					setError( __( 'Unable to load migration wizard.', 'buddyboss-platform' ) );
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
						__( 'Unable to load migration wizard (%s).', 'buddyboss-platform' ),
						err && err.message ? err.message : __( 'unknown error', 'buddyboss-platform' )
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

	const targetLabel = useMemo( function () {
		if ( ! isFooterWizard ) {
			return __( 'Likes', 'buddyboss-platform' );
		}
		if ( ! wizardData || ! Array.isArray( wizardData.target_emotions ) ) {
			return '';
		}
		const match = wizardData.target_emotions.filter( function ( opt ) {
			return String( opt.value ) === String( targetEmotion );
		} );
		return match.length > 0 ? match[ 0 ].label : '';
	}, [ targetEmotion, wizardData, isFooterWizard ] );

	const selectedCount = useMemo( function () {
		if ( ! wizardData || ! Array.isArray( wizardData.reactions ) ) {
			return 0;
		}
		return wizardData.reactions.reduce( function ( total, r ) {
			return selectedReactions[ r.id ] ? total + ( r.count || 0 ) : total;
		}, 0 );
	}, [ selectedReactions, wizardData ] );

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
					window.dispatchEvent( new CustomEvent( 'bb-admin-refetch-feature' ) );
					onCloseRef.current();
				} else {
					setSubmitting( false );
					setError(
						( response.data && response.data.message )
							|| __( 'Migration failed. Please try again.', 'buddyboss-platform' )
					);
				}
			} )
			.catch( function () {
				setSubmitting( false );
				setError( __( 'Migration failed. Please try again.', 'buddyboss-platform' ) );
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
								{ __( 'Close', 'buddyboss-platform' ) }
							</button>
						</div>
					</div>
				) }

				{ ! loading && ! error && wizardData && wizardData.no_data && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--no-data">
						<p className="bb-admin-migration-modal__no-data-heading">
							<strong>{ __( 'You have no reactions to convert', 'buddyboss-platform' ) }</strong>
						</p>
						<p>{ wizardData.no_data_message || __( 'No reactions are available to convert.', 'buddyboss-platform' ) }</p>
						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-primary"
								onClick={ onClose }
							>
								{ __( 'Close', 'buddyboss-platform' ) }
							</button>
						</div>
					</div>
				) }

				{ ! loading && ! error && wizardData && ! wizardData.no_data && 1 === currentScreen && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--screen-1">
						{ 'like_to_emotions_action' === migrationAction && migrationCount > 0 ? (
							<p>
								{ __( 'This action will convert the ', 'buddyboss-platform' ) }
								<strong>
									{ sprintf(
										/* translators: %s: formatted reaction count. */
										__( '%s Likes', 'buddyboss-platform' ),
										formatNumber( migrationCount )
									) }
								</strong>
								{ ' ' }
								{ __( 'previously submitted by members on your site to an Emotion of your choice. You can perform this action at any point in the future using this migration wizard.', 'buddyboss-platform' ) }
							</p>
						) : (
							<p>
								{ needsTargetSelect
									? __( 'This action will convert reactions previously submitted by members on your site to an Emotion of your choice. Reactions not selected can be converted at any point in the future using this migration wizard.', 'buddyboss-platform' )
									: __( 'This action will convert reactions previously submitted by members on your site to Likes. Reactions not selected can be converted at any point in the future using the migration wizard.', 'buddyboss-platform' )
								}
							</p>
						) }

						{ /* Source-reaction selector. Hidden when modal opened from
						     the like→emotion notice — Likes is implicit there
						     (matching legacy "Convert Likes" single-question modal). */ }
						{ ! ( 'like_to_emotions_action' === migrationAction && migrationCount > 0 ) && (
							<>
								<p className="bb-admin-migration-modal__section-heading">
									<strong>
										{ needsTargetSelect
											? __( 'Which reactions do you want to convert?', 'buddyboss-platform' )
											: __( 'Which reactions do you want to convert to Likes?', 'buddyboss-platform' )
										}
									</strong>
								</p>

								<div className="bb-admin-migration-modal__reactions">
									{ wizardData.reactions
										.filter( function ( r ) { return 'likes' === r.group; } )
										.map( function ( r ) {
											return (
												<CheckboxControl
													key={ r.id }
													label={ sprintf(
														/* translators: 1: reaction label, 2: formatted count. */
														__( '%1$s (%2$s)', 'buddyboss-platform' ),
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

									{ emotionRows.length > 0 && (
										<CheckboxControl
											className="bb-admin-migration-modal__reactions-group-toggle"
											label={ sprintf(
												/* translators: 1: "Emotions" or "All emotions", 2: formatted total. */
												__( '%1$s (%2$s)', 'buddyboss-platform' ),
												isFooterWizard
													? __( 'Emotions', 'buddyboss-platform' )
													: __( 'All emotions', 'buddyboss-platform' ),
												formatNumber( emotionTotalCount )
											) }
											checked={ allEmotionsChecked }
											onChange={ function ( checked ) { toggleAllInGroup( wizardData.reactions, 'emotions', checked ); } }
											__nextHasNoMarginBottom
										/>
									) }

									<ul className="bb-admin-migration-modal__reactions-list">
										{ emotionRows.map( function ( r ) {
											return (
												<li key={ r.id }>
													<CheckboxControl
														label={ sprintf(
															/* translators: 1: emotion label, 2: formatted count. */
															__( '%1$s (%2$s)', 'buddyboss-platform' ),
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
											? __( 'Which Emotion would you like to convert your Likes to?', 'buddyboss-platform' )
											: __( 'Which Emotion would you like to convert your reactions to?', 'buddyboss-platform' )
										}
									</strong>
								</p>
								<SelectControl
									className="bb-admin-migration-modal__target-select"
									value={ targetEmotion }
									options={ [
										{ value: '', label: __( 'Select an emotion', 'buddyboss-platform' ) },
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
								{ __( 'Cancel', 'buddyboss-platform' ) }
							</button>
							<button
								type="button"
								className="components-button is-primary"
								onClick={ function () { setCurrentScreen( 2 ); } }
								disabled={ ! canContinue }
							>
								{ __( 'Continue', 'buddyboss-platform' ) }
							</button>
						</div>
					</div>
				) }

				{ ! loading && ! error && wizardData && ! wizardData.no_data && 2 === currentScreen && (
					<div className="bb-admin-migration-modal__wizard bb-admin-migration-modal__wizard--screen-2">
						<p>
							{ sprintf(
								/* translators: 1: count of selected reactions, 2: target label. */
								_n(
									'You are about to convert %1$d reaction to %2$s.',
									'You are about to convert %1$d reactions to %2$s.',
									selectedCount,
									'buddyboss-platform'
								),
								selectedCount,
								targetLabel
							) }
						</p>

						<ul className="bb-admin-migration-modal__warnings">
							<li>{ __( 'The new reactions will be immediately visible on your site after being converted.', 'buddyboss-platform' ) }</li>
							<li>{ __( 'Depending on the amount of data to convert, the migration may take a while.', 'buddyboss-platform' ) }</li>
							<li>{ __( 'You will be unable to edit reactions while the conversion is in progress.', 'buddyboss-platform' ) }</li>
							<li>{ __( 'This action cannot be undone, but you can convert reactions to another reaction in the future.', 'buddyboss-platform' ) }</li>
							<li>{ __( 'We recommend backing up your site before migrating and performing this action during an off-peak period.', 'buddyboss-platform' ) }</li>
						</ul>

						<p>{ __( 'Do you want to start the conversion now?', 'buddyboss-platform' ) }</p>

						<div className="bb-admin-migration-modal__footer">
							<button
								type="button"
								className="components-button is-secondary"
								onClick={ function () { setCurrentScreen( 1 ); } }
								disabled={ submitting }
							>
								{ __( 'Back', 'buddyboss-platform' ) }
							</button>
							<button
								type="button"
								className="components-button is-primary"
								onClick={ handleStartConversion }
								disabled={ submitting }
							>
								{ submitting ? __( 'Converting…', 'buddyboss-platform' ) : __( 'Start conversion', 'buddyboss-platform' ) }
							</button>
						</div>
					</div>
				) }
			</div>
		</Modal>
	);
}
