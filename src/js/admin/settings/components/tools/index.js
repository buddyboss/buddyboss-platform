/**
 * Tools panel — Settings 2.0 custom field type registrations.
 *
 * Platform owns the Repair Platform panel (`bb_tools_repair_platform` field
 * type) and the Activation Required CTA fallback for the two Tools-plugin
 * panels (`bb_tools_sample_data`, `bb_tools_migration_tools`). The Tools
 * plugin's own React bundle (when active) registers its handlers at the
 * default priority 10 with namespace `buddyboss-tools/*`; if Tools is
 * inactive or its build for a given panel is not yet shipped, this fallback
 * resolver (registered at priority 99) renders the CTA so the user always
 * sees a meaningful empty state.
 *
 * @since BuddyBoss [BBVERSION]
 */
import { addFilter } from '@wordpress/hooks';
import RepairPlatform from './RepairPlatform';
import ActivationRequiredCTA from './ActivationRequiredCTA';

addFilter(
	'bb_admin_settings_custom_field',
	'buddyboss-platform/tools-repair-platform',
	function ( fallback, field ) {
		if ( field && 'bb_tools_repair_platform' === field.type ) {
			return <RepairPlatform />;
		}
		return fallback;
	}
);

addFilter(
	'bb_admin_settings_custom_field',
	'buddyboss-platform/tools-sample-data-fallback',
	function ( fallback, field ) {
		if ( field && 'bb_tools_sample_data' === field.type ) {
			if ( fallback ) {
				return fallback;
			}
			if ( wp.hooks.hasFilter( 'bb_admin_settings_custom_field', 'buddyboss-tools/sample-data' ) ) {
				return fallback;
			}
			return <ActivationRequiredCTA />;
		}
		return fallback;
	},
	99
);

addFilter(
	'bb_admin_settings_custom_field',
	'buddyboss-platform/tools-migration-tools-fallback',
	function ( fallback, field ) {
		if ( field && 'bb_tools_migration_tools' === field.type ) {
			if ( fallback ) {
				return fallback;
			}
			if ( wp.hooks.hasFilter( 'bb_admin_settings_custom_field', 'buddyboss-tools/migration-tools' ) ) {
				return fallback;
			}
			return <ActivationRequiredCTA />;
		}
		return fallback;
	},
	99
);
