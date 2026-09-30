/**
 * BuddyBoss Admin Settings 2.0 - BBIcon Component
 *
 * Renders an icon span using ReadyLaunch icon classes.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

/**
 * BBIcon component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props      Component props.
 * @param {string} props.name Icon name (without prefix).
 * @returns {JSX.Element} Icon span element.
 */
export function BBIcon( { name } ) {
	return <span className={ `bb-icons-rl-${ name }` } />;
}
