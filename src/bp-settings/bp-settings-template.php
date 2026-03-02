<?php
/**
 * BuddyBoss Settings Template Functions.
 *
 * @package BuddyBoss\Settings\Template
 * @since BuddyPress 1.5.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Output the settings component slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_settings_slug() {
	echo bp_get_settings_slug();
}
	/**
	 * Return the settings component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_settings_slug() {

	/**
	 * Filters the Settings component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Settings component slug.
	 */
	return apply_filters( 'bp_get_settings_slug', buddypress()->settings->slug );
}

/**
 * Output the settings component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bp_settings_root_slug() {
	echo bp_get_settings_root_slug();
}
	/**
	 * Return the settings component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bp_get_settings_root_slug() {

	/**
	 * Filters the Settings component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $root_slug Settings component root slug.
	 */
	return apply_filters( 'bp_get_settings_root_slug', buddypress()->settings->root_slug );
}

/**
 * Add the 'pending email change' message to the settings page.
 *
 * @since BuddyPress 2.1.0
 */
function bp_settings_pending_email_notice() {
	$pending_email = bp_get_user_meta( bp_displayed_user_id(), 'pending_email_change', true );

	if ( empty( $pending_email['newemail'] ) ) {
		return;
	}

	if ( bp_get_displayed_user_email() == $pending_email['newemail'] ) {
		return;
	}

	?>

	<aside class="bp-feedback bp-messages error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
		<?php
		printf(
			__( 'There is a pending change of your email address to %s.', 'buddyboss' ),
			'<strong>' . esc_html( $pending_email['newemail'] ) . '</strong>'
		);
		?>
		<br />
		<?php
		printf(
			__( 'Check your email (%1$s) for the verification link, or <a href="%2$s">cancel the pending change</a>.', 'buddyboss' ),
			'<strong>' . esc_html( bp_get_displayed_user_email() ) . '</strong>',
			esc_url( wp_nonce_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/?dismiss_email_change=1', 'bp_dismiss_email_change' ) )
		);
		?>
		</p>
	</aside>

	<?php
}
add_action( 'bp_before_member_settings_template', 'bp_settings_pending_email_notice' );
