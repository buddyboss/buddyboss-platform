/**
 * BuddyBoss Admin Settings 2.0 - Help Screen
 *
 * Placeholder shell for the new in-app Help page. The Knowledge Base modal
 * (graduation-cap icon in the Header) is unrelated — this is the legacy
 * `?page=bp-help` documentation page being folded into Settings 2.0 as
 * `?page=bb-settings&tab=help`. Content will be wired up in a follow-up.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { __ } from '@wordpress/i18n';

/**
 * Help Screen Component
 *
 * @returns {JSX.Element} Help screen
 */
export function HelpScreen() {
	return (
		<div className="bb-admin-help-screen">
			<div className="bb-admin-help-screen__header">
				<h1 className="bb-admin-help-screen__title">
					{ __( 'Help', 'buddyboss' ) }
				</h1>
				<p className="bb-admin-help-screen__intro">
					{ __( 'Documentation and resources for BuddyBoss Platform.', 'buddyboss' ) }
				</p>
			</div>
			<div className="bb-admin-help-screen__body">
				<p>
					{ __( 'Help content will appear here.', 'buddyboss' ) }
				</p>
			</div>
		</div>
	);
}

export default HelpScreen;
