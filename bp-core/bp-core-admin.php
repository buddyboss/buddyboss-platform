<?php
/**
 * Main BuddyPress Admin Class.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyPress 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup BuddyPress Admin.
 *
 * @since BuddyPress 1.6.0
 */
function bp_admin() {
	buddypress()->admin = new BP_Admin();
	return;

	// These are strings we may use to describe maintenance/security releases, where we aim for no new strings.
	_n_noop( 'Maintenance Release', 'Maintenance Releases', 'buddyboss' );
	_n_noop( 'Security Release', 'Security Releases', 'buddyboss' );
	_n_noop( 'Maintenance and Security Release', 'Maintenance and Security Releases', 'buddyboss' );

	/* translators: 1: BuddyPress version number. */
	_n_noop(
		'<strong>Version %1$s</strong> addressed a security issue.',
		'<strong>Version %1$s</strong> addressed some security issues.',
		'buddyboss'
	);

	/* translators: 1: BuddyPress version number, 2: plural number of bugs. */
	_n_noop(
		'<strong>Version %1$s</strong> addressed %2$s bug.',
		'<strong>Version %1$s</strong> addressed %2$s bugs.',
		'buddyboss'
	);

	/* translators: 1: BuddyPress version number, 2: plural number of bugs. Singular security issue. */
	_n_noop(
		'<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bug.',
		'<strong>Version %1$s</strong> addressed a security issue and fixed %2$s bugs.',
		'buddyboss'
	);

	/* translators: 1: BuddyPress version number, 2: plural number of bugs. More than one security issue. */
	_n_noop(
		'<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bug.',
		'<strong>Version %1$s</strong> addressed some security issues and fixed %2$s bugs.',
		'buddyboss'
	);

	__( 'For more information, see <a href="%s">the release notes</a>.', 'buddyboss' );
}
