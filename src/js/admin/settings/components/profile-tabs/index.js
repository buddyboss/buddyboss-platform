/**
 * Custom Profile Tabs field — Settings 2.0 custom field type fallback.
 *
 * Platform registers the `bb_profile_tabs` field type under the Member Profiles
 * → Custom Profile Tabs panel as a placeholder. When BuddyBoss Platform Pro is active it
 * registers its own renderer on `bb_admin_settings_custom_field` at the default
 * priority 10; if Pro is inactive, this priority-99 fallback renders the
 * Activation Required CTA so the panel always has a meaningful empty state.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { addFilter, hasFilter } from '@wordpress/hooks';
import ProfileTabsActivationRequired from './ProfileTabsActivationRequired';

addFilter(
	'bb_admin_settings_custom_field',
	'buddyboss-platform/profile-tabs-fallback',
	function ( fallback, field ) {
		if ( field && 'bb_profile_tabs' === field.type ) {
			if ( fallback ) {
				return fallback;
			}
			if ( hasFilter( 'bb_admin_settings_custom_field', 'buddyboss-platform-pro/profile-tabs' ) ) {
				return fallback;
			}
			return <ProfileTabsActivationRequired />;
		}
		return fallback;
	},
	99
);
