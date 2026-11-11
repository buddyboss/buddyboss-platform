/**
 * BuddyBoss Admin Settings 2.0 - Reaction Notice Component
 *
 * Displays status for in-progress or completed migrations.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { formatNumber } from '../../utils/format';

/** Poll interval for migration progress refresh (30 seconds). */
const MIGRATION_POLL_INTERVAL_MS = 30000;

// Module-level store so dismissed migrations stay hidden after remount (e.g. navigate away/back).
// Resets on full page reload; per admin session only.
// NOTE: These module-level variables are intentionally shared. Only one ReactionNotice instance
// renders at a time (one active side panel), so there is no cross-instance contamination.
const dismissedMigrationSignatures = {};

// Module-level store for live migration progress so it survives unmount/remount
// when navigating between side panels. Without this, progress resets to 0
// because the component re-initializes from stale cached field data.
let liveMigrationProgress = null;

export function ReactionNotice({ field }) {
	const [isDismissed, setIsDismissed] = useState(false);
	const [confirmStopVisible, setConfirmStopVisible] = useState(false);
	// Use module-level progress cache if available (survives panel navigation),
	// otherwise fall back to the field data from the feature cache.
	const [migrationData, setMigrationData] = useState(
		liveMigrationProgress ? liveMigrationProgress.migrationData : ( field.migration_data || {} )
	);
	const [migrationStatus, setMigrationStatus] = useState(
		liveMigrationProgress ? liveMigrationProgress.migrationStatus : ( field.migration_status || '' )
	);
	const autoRefreshRef = useRef(null);

	const updateProgress = useCallback( ( data, status ) => {
		setMigrationData( data );
		setMigrationStatus( status );
		liveMigrationProgress = status === 'completed' ? null : { migrationData: data, migrationStatus: status };
	}, [] );

	const applyMigrationResponse = useCallback( ( data, status ) => {
		updateProgress( data || {}, status || '' );
		if ( status === 'completed' ) {
			window.dispatchEvent( new CustomEvent( 'bb-admin-refetch-feature' ) );
		}
	}, [ updateProgress ] );

	// Build a stable signature for the current migration so dismissal
	// is scoped to this specific migration within the current admin session.
	const migrationSignature = (() => {
		const data = field.migration_data || {};
		if (!data.action || !data.type || !data.total_reactions) {
			return '';
		}
		return JSON.stringify({
			action: data.action,
			type: data.type,
			total: data.total_reactions,
			from: data.from_emotions || [],
			to: data.to_emotions || 0,
		});
	})();

	// Whether this migration was already dismissed in this session (survives remount).
	const isDismissedInSession = !!(migrationSignature && dismissedMigrationSignatures[migrationSignature]);

	// Sync with field (server/cache). Don't overwrite live in-progress with stale cache when user navigates back.
	useEffect( () => {
		const serverData = field.migration_data || {};
		const serverStatus = field.migration_status || '';

		if ( 'completed' === serverStatus || 'dismissed' === serverData.status ) {
			updateProgress( serverData, serverStatus );
			liveMigrationProgress = null;
		} else if ( ! serverData.action ) {
			const liveIsInProgress = liveMigrationProgress && (
				'inprogress' === liveMigrationProgress.migrationStatus ||
				'running' === liveMigrationProgress.migrationData?.status
			);
			if ( ! liveIsInProgress ) {
				updateProgress( serverData, serverStatus );
				liveMigrationProgress = null;
			}
		} else if ( ! liveMigrationProgress ) {
			updateProgress( serverData, serverStatus );
		}

		if ( migrationSignature && ! dismissedMigrationSignatures[ migrationSignature ] ) {
			setIsDismissed(false);
		}
	}, [ field.migration_data, field.migration_status, migrationSignature, updateProgress ] );

	const isInProgress = 'inprogress' === migrationStatus || 'running' === migrationData.status;
	const isCompleted = 'completed' === migrationStatus || 'completed' === migrationData.status;

	// On mount (and when navigating back): show last-known progress, then fetch latest via AJAX.
	useEffect( () => {
		const initialStatus = field.migration_status || '';
		const initialDataStatus = field.migration_data?.status || '';
		const liveStatus = liveMigrationProgress?.migrationStatus || '';
		const liveDataStatus = liveMigrationProgress?.migrationData?.status || '';

		if (
			'completed' === initialStatus ||
			'completed' === initialDataStatus ||
			'completed' === liveStatus ||
			'completed' === liveDataStatus
		) {
			return;
		}

		const hasFieldMigration = field.migration_data?.action || ( initialStatus && '' !== initialStatus );
		const hasLiveProgress = liveMigrationProgress && (
			'inprogress' === liveStatus || 'running' === liveDataStatus
		);

		if ( ( hasFieldMigration || hasLiveProgress ) && window.bbReactionAdminVars?.ajax_url ) {
			jQuery.ajax( {
				url: window.bbReactionAdminVars.ajax_url,
				method: 'POST',
				data: {
					action: 'bb_pro_reaction_check_migration',
					nonce: window.bbReactionAdminVars.nonce?.check_migration || '',
				},
				success: ( response ) => {
					if ( response.success && response.data ) {
						applyMigrationResponse(
							response.data.migration_data,
							response.data.migration_status
						);
					}
				},
			} );
		}
	// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		if ( ! isInProgress || isDismissed ) {
			return;
		}
		autoRefreshRef.current = setInterval( () => {
			if ( ! window.bbReactionAdminVars?.ajax_url ) {
				return;
			}
			jQuery.ajax( {
				url: window.bbReactionAdminVars.ajax_url,
				method: 'POST',
				data: {
					action: 'bb_pro_reaction_check_migration',
					nonce: window.bbReactionAdminVars.nonce?.check_migration || '',
				},
				success: ( response ) => {
					if ( response.success && response.data ) {
						const newStatus = response.data.migration_status || '';
						applyMigrationResponse( response.data.migration_data, newStatus );
						if ( newStatus === 'completed' ) {
							clearInterval( autoRefreshRef.current );
						}
					}
				},
			} );
		}, MIGRATION_POLL_INTERVAL_MS );

		return () => {
			if ( autoRefreshRef.current ) {
				clearInterval( autoRefreshRef.current );
			}
		};
	}, [ isInProgress, isDismissed, applyMigrationResponse ] );

	// If this migration was dismissed in this session (module-level store survives remount),
	// or currently dismissed in state, or not in-progress/completed, hide the notice.
	if (isDismissedInSession || isDismissed || (!isInProgress && !isCompleted)) {
		return null;
	}

	const handleDismiss = () => {
		setIsDismissed(true);
		liveMigrationProgress = null;
		if (migrationSignature) {
			dismissedMigrationSignatures[migrationSignature] = true;
		}

		// For completed status, call dismiss endpoint
		if ( isCompleted && window.bbReactionAdminVars?.ajax_url ) {
			jQuery.ajax( {
				url: window.bbReactionAdminVars.ajax_url,
				method: 'POST',
				data: {
					action: 'bb_pro_reaction_dismiss_migration_notice',
					nonce: window.bbReactionAdminVars.nonce?.dismiss_migration_notice || '',
				},
				success: () => {
					window.dispatchEvent( new CustomEvent( 'bb-admin-refetch-feature' ) );
				},
			} );
		}
	};

	const handleRecheckStatus = ( e ) => {
		e.preventDefault();
		if ( ! window.bbReactionAdminVars?.ajax_url ) {
			return;
		}
		jQuery.ajax( {
			url: window.bbReactionAdminVars.ajax_url,
			method: 'POST',
			data: {
				action: 'bb_pro_reaction_check_migration',
				nonce: window.bbReactionAdminVars.nonce?.check_migration || '',
			},
			success: ( response ) => {
				if ( response.success && response.data ) {
					applyMigrationResponse(
						response.data.migration_data,
						response.data.migration_status
					);
				}
			},
		} );
	};

	const handleStopMigration = ( e ) => {
		e.preventDefault();
		if ( ! window.bbReactionAdminVars?.ajax_url ) {
			return;
		}
		setConfirmStopVisible( true );
	};

	const handleConfirmStop = () => {
		setConfirmStopVisible( false );
		jQuery.ajax( {
			url: window.bbReactionAdminVars.ajax_url,
			method: 'POST',
			data: {
				action: 'bb_pro_reaction_migration_stop_conversion',
				nonce: window.bbReactionAdminVars.nonce?.migration_stop_conversion || '',
			},
			success: ( response ) => {
				if ( response.success ) {
					liveMigrationProgress = null;
					window.dispatchEvent( new CustomEvent( 'bb-admin-refetch-feature' ) );
				}
			},
		} );
	};

	// Render completed notice
	if (isCompleted) {
		const action = migrationData.action || '';
		const totalReactions = migrationData.total_reactions || 0;
		const fromEmotionsName = migrationData.from_emotions_name || '';
		const toEmotionsName = migrationData.to_emotions_name || '';

		// Normalize footer migration action values to match switch migration actions.
		// Footer migrations set action to the reaction mode ('emotions'/'likes'),
		// while switch migrations use 'like_to_emotions_action'/'emotions_to_like_action'.
		let normalizedAction = action;
		if ( 'emotions' === action ) {
			normalizedAction = 'like_to_emotions_action';
		} else if ( 'likes' === action ) {
			normalizedAction = 'emotions_to_like_action';
		}

		// Render as JSX so dynamic values are escaped (no dangerouslySetInnerHTML).
		const completedMessageJsx = (() => {
			if ('like_to_emotions_action' === normalizedAction) {
				return (
					<p>
						<strong>{formatNumber(totalReactions)} {fromEmotionsName}</strong>
						{' '}
						{__('were successfully converted to the', 'buddyboss')}
						{' '}
						<strong>{toEmotionsName}</strong>
						{' '}
						{__('emotion.', 'buddyboss')}
					</p>
				);
			}
			if ('emotions_to_like_action' === normalizedAction) {
				return (
					<p>
						<strong>{formatNumber(totalReactions)}</strong>
						{' '}
						{__('reactions were successfully converted to', 'buddyboss')}
						{' '}
						<strong>{toEmotionsName}</strong>.
					</p>
				);
			}
			return null;
		})();

		return (
			<div className="bb-admin-settings-form__field bb-admin-settings-form__field--full-width bb-admin-reaction-notice-wrapper">
				<div className="bb-admin-notice bb-admin-notice--success">
					<div className="bb-admin-notice__icon">
						<span className="bb-icons-rl bb-icons-rl-check-circle" />
					</div>
					<div className="bb-admin-notice__content">
						{completedMessageJsx}
					</div>
					<button
						type="button"
						className="bb-admin-notice__close"
						onClick={handleDismiss}
						aria-label={__('Dismiss', 'buddyboss')}
					>
						<span className="bb-icons-rl bb-icons-rl-x" />
					</button>
				</div>
			</div>
		);
	}

	// Render in-progress notice
	if (isInProgress) {
		const total = parseInt(migrationData.total_reactions || 0);
		const updatedEmotions = parseInt(migrationData.updated_emotions || 0);
		const percentage = total > 0 ? Math.ceil((updatedEmotions * 100) / total) : 0;

		return (
			<div className="bb-admin-settings-form__field bb-admin-settings-form__field--full-width bb-admin-reaction-notice-wrapper">
				<div className="bb-admin-notice bb-admin-notice--info bb-admin-notice--progress">
					<div className="bb-admin-notice__icon">
						<span className="bb-icons-rl bb-icons-rl-spinner animate-spin" />
					</div>
					<div className="bb-admin-notice__content">
						<p>
							<strong>
								{__('%1$s out of %2$s %3$s reactions have been converted', 'buddyboss')
									.replace('%1$s', formatNumber(updatedEmotions))
									.replace('%2$s', formatNumber(total))
									.replace('%3$s', `(${percentage}%)`)}
							</strong>
						</p>
						<p>
							{__('This action is being performed in the background, but may take some time based on the amount of data.', 'buddyboss')}
						</p>
					</div>
					<div className="bb-admin-notice__actions">
						<button
							type="button"
							className="bb-admin-notice__button bb-admin-notice__button--outline"
							onClick={handleRecheckStatus}
						>
							{__('Recheck status', 'buddyboss')}
						</button>
						<button
							type="button"
							className="bb-admin-notice__button bb-admin-notice__button--text"
							onClick={handleStopMigration}
						>
							{__('Stop', 'buddyboss')}
						</button>
					</div>
					{ confirmStopVisible && (
						<div className="bb-admin-notice__confirm">
							<span>{ __( 'Are you sure you want to stop the migration?', 'buddyboss' ) }</span>
							<button
								type="button"
								className="bb-admin-notice__button bb-admin-notice__button--danger"
								onClick={ handleConfirmStop }
							>
								{ __( 'Yes, stop', 'buddyboss' ) }
							</button>
							<button
								type="button"
								className="bb-admin-notice__button bb-admin-notice__button--text"
								onClick={ () => setConfirmStopVisible( false ) }
							>
								{ __( 'Cancel', 'buddyboss' ) }
							</button>
						</div>
					) }
				</div>
			</div>
		);
	}

	return null;
}
