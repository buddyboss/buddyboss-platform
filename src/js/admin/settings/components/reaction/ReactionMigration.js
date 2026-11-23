/**
 * BuddyBoss Admin Settings 2.0 - Reaction Migration Component
 *
 * Displays migration warning notice with "Start Conversion" button.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { formatNumber } from '../../utils/format';

export function ReactionMigration({ field, onStartConversion }) {
	const [isDismissed, setIsDismissed] = useState(false);

	const migrationData = field.migration_data || {};
	const migrationStatus = field.migration_status || '';

	// Only show for pending migrations (not in-progress or completed)
	const hasPendingMigration =
		migrationData &&
		migrationData.action &&
		migrationData.total_reactions > 0 &&
		'inprogress' !== migrationStatus &&
		'completed' !== migrationStatus;

	if (isDismissed || !hasPendingMigration) {
		return null;
	}

	const totalReactions = migrationData.total_reactions || 0;
	const fromMode = 'like_to_emotions_action' === migrationData.action ? __( 'Likes', 'buddyboss-community-social-network-groups-forums-messaging' ) : __( 'Reactions', 'buddyboss-community-social-network-groups-forums-messaging' );

	const handleDismiss = (e) => {
		e.preventDefault();
		setIsDismissed(true);

		// Call "Do Later" endpoint.
		if (window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url) {
			window.jQuery.ajax({
				url: window.bbReactionAdminVars.ajax_url,
				method: 'POST',
				data: {
					action: 'bb_pro_reaction_migration_do_later',
					nonce: window.bbReactionAdminVars.nonce?.migration_do_later || '',
				},
				success: () => {
					// Refetch feature data so the notice won't reappear when navigating back.
					if (typeof window !== 'undefined') {
						window.dispatchEvent(new CustomEvent('bb-admin-refetch-feature'));
					}
				},
			});
		}
	};

	const handleStartConversion = (e) => {
		e.preventDefault();
		if (onStartConversion) {
			onStartConversion(migrationData);
		}
	};

	return (
		<div className="bb-admin-settings-form__field bb-admin-settings-form__field--full-width bb-admin-reaction-migration-wrapper">
			<div className="bb-admin-notice bb-admin-notice--warning">
				<div className="bb-admin-notice__icon">
					<span className="bb-icons-rl bb-icons-rl-warning-circle" />
				</div>
				<div className="bb-admin-notice__content">
					<p>
						{__('You have ', 'buddyboss-community-social-network-groups-forums-messaging')}
						<strong>{formatNumber(totalReactions)}</strong>
						{' '}
						{fromMode}
						{' '}
						{__('previously submitted on your site which can be converted to', 'buddyboss-community-social-network-groups-forums-messaging')}
						{' '}
						{ 'like_to_emotions_action' === migrationData.action
							? __('an Emotion', 'buddyboss-community-social-network-groups-forums-messaging')
							: __('Likes', 'buddyboss-community-social-network-groups-forums-messaging')}
						.
					</p>
				</div>
				<div className="bb-admin-notice__actions">
					<button
						type="button"
						className="bb-admin-notice__button"
						onClick={handleStartConversion}
					>
						{__('Start Conversion', 'buddyboss-community-social-network-groups-forums-messaging')}
					</button>
				</div>
				<button
					type="button"
					className="bb-admin-notice__close"
					onClick={handleDismiss}
					aria-label={__('Dismiss', 'buddyboss-community-social-network-groups-forums-messaging')}
				>
					<span className="bb-icons-rl bb-icons-rl-x" />
				</button>
			</div>
		</div>
	);
}
