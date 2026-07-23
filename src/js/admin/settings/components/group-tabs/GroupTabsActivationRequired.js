/**
 * Custom Group Tabs — Activation Required empty state.
 *
 * Fallback rendered in the Social Groups → Custom Group Tabs panel when BuddyBoss
 * Platform Pro (which renders the management UI for the `bb_group_tabs` field)
 * is not active. Mirrors the Tools Activation Required empty state and reuses its
 * styling.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Render the Activation Required CTA for Custom Group Tabs.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return {JSX.Element} The CTA element.
 */
export default function GroupTabsActivationRequired() {
	const adminUrl = ( window.bbAdminData && window.bbAdminData.adminUrl ) || '/wp-admin/';

	return (
		<div className="bb-tools-activation-cta">
			<div className="bb-tools-activation-cta__icon">
				<span className="bb-icons-rl bb-icons-rl-info"></span>
			</div>
			<h2 className="bb-tools-activation-cta__title">
				{ __( 'Activation Required', 'buddyboss' ) }
			</h2>
			<p className="bb-tools-activation-cta__description">
				{ __( 'Please activate the BuddyBoss Platform Pro addon to use this feature.', 'buddyboss' ) }
			</p>
			<Button variant="primary" href={ adminUrl + 'plugins.php' }>
				{ __( 'Activate BuddyBoss Platform Pro', 'buddyboss' ) }
			</Button>
		</div>
	);
}
