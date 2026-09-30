/**
 * BuddyBoss Admin Settings 2.0 - Field Type Icons
 *
 * Shared mapping of xProfile field types to icon CSS classes.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * Field type icon class mapping.
 *
 * @since BuddyBoss [BBVERSION]
 */
var FIELD_TYPE_ICONS = {
	textbox: 'bb-icons-rl-text-t',
	textarea: 'bb-icons-rl-text-align-left',
	selectbox: 'bb-icons-rl-list',
	multiselectbox: 'bb-icons-rl-list-checks',
	checkbox: 'bb-icons-rl-check-square',
	radio: 'bb-icons-rl-radio-button',
	datebox: 'bb-icons-rl-calendar',
	number: 'bb-icons-rl-hash',
	telephone: 'bb-icons-rl-phone',
	url: 'bb-icons-rl-link',
	gender: 'bb-icons-rl-gender-intersex',
	socialnetworks: 'bb-icons-rl-share-network',
	membertypes: 'bb-icons-rl-tag',
};

/**
 * Get the icon CSS class for a given field type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} type     Field type key.
 * @param {string} fallback Optional fallback icon class.
 * @returns {string} Icon class name.
 */
function getFieldTypeIcon( type, fallback ) {
	return FIELD_TYPE_ICONS[ type ] || fallback || 'bb-icons-rl-text-t';
}

export { FIELD_TYPE_ICONS, getFieldTypeIcon };
