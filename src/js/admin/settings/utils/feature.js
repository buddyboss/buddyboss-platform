/**
 * BuddyBoss Admin Feature Data Utilities
 *
 * Helpers for extracting data from the PHP-registered feature structure.
 *
 * Custom panel screens (GroupTypeScreen, ProfileTypeScreen, etc.) render their
 * own settings card instead of using SettingsForm. These helpers extract
 * section titles, field labels, and field descriptions from the feature data
 * so they come from PHP registration (bb_register_feature_field) rather than
 * being hardcoded in JS.
 *
 * @package BuddyBoss
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Get panel sections array from feature data.
 *
 * Internal helper to avoid repeating the panel lookup in every function.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature       Feature data from FeatureSettingsScreen.
 * @param {string} activePanelId The active side panel ID.
 * @returns {Array} Panel sections array, or empty array if not found.
 */
function getPanelSections( feature, activePanelId ) {
	if ( ! feature || ! feature.side_panels ) {
		return [];
	}

	var panel = feature.side_panels.find( function ( p ) {
		return p.id === activePanelId;
	} );

	return ( panel && panel.sections ) ? panel.sections : [];
}

/**
 * Find a field object by option name within panel sections.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Array}  sections  Panel sections array.
 * @param {string} fieldName The field option name (e.g. 'bp-disable-group-type-creation').
 * @returns {Object|null} The field object, or null if not found.
 */
function findField( sections, fieldName ) {
	for ( var i = 0; i < sections.length; i++ ) {
		var fields = sections[ i ].fields || [];
		for ( var j = 0; j < fields.length; j++ ) {
			if ( fields[ j ].name === fieldName ) {
				return fields[ j ];
			}
		}
	}

	return null;
}

/**
 * Get a section's title from the PHP-registered feature data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature       Feature data from FeatureSettingsScreen.
 * @param {string} activePanelId The active side panel ID.
 * @param {string} sectionId     The section ID (e.g. 'group_type_settings').
 * @returns {string} The section title or empty string.
 */
export function getSectionTitle( feature, activePanelId, sectionId ) {
	var sections = getPanelSections( feature, activePanelId );

	for ( var i = 0; i < sections.length; i++ ) {
		if ( sections[ i ].id === sectionId ) {
			return sections[ i ].title || '';
		}
	}

	return '';
}

/**
 * Get a field's label from the PHP-registered feature data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature       Feature data from FeatureSettingsScreen.
 * @param {string} activePanelId The active side panel ID.
 * @param {string} fieldName     The field option name (e.g. 'bp-disable-group-type-creation').
 * @returns {string} The field label or empty string.
 */
export function getFieldLabel( feature, activePanelId, fieldName ) {
	var field = findField( getPanelSections( feature, activePanelId ), fieldName );

	return field ? ( field.label || '' ) : '';
}

/**
 * Get a field's description from the PHP-registered feature data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature       Feature data from FeatureSettingsScreen.
 * @param {string} activePanelId The active side panel ID.
 * @param {string} fieldName     The field option name (e.g. 'bp-member-type-enable-disable').
 * @returns {string} The field description HTML or empty string.
 */
export function getFieldDescription( feature, activePanelId, fieldName ) {
	var field = findField( getPanelSections( feature, activePanelId ), fieldName );

	return field ? ( field.description || '' ) : '';
}

/**
 * Get a field's help text from the PHP-registered feature data.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} feature       Feature data from FeatureSettingsScreen.
 * @param {string} activePanelId The active side panel ID.
 * @param {string} fieldName     The field option name (e.g. 'bp-disable-group-type-creation').
 * @returns {string} The field help text HTML or empty string.
 */
export function getFieldHelpText( feature, activePanelId, fieldName ) {
	var field = findField( getPanelSections( feature, activePanelId ), fieldName );

	return field ? ( field.help_text || '' ) : '';
}
