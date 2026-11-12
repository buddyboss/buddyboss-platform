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
function bboss_settings_slug() {
	echo esc_attr( bboss_get_settings_slug() );
}
	/**
	 * Return the settings component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bboss_get_settings_slug() {

	/**
	 * Filters the Settings component slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $slug Settings component slug.
	 */
	return apply_filters( 'bp_get_settings_slug', bboss()->settings->slug );
}

/**
 * Output the settings component root slug.
 *
 * @since BuddyPress 1.5.0
 */
function bboss_settings_root_slug() {
	echo esc_attr( bboss_get_settings_root_slug() );
}
	/**
	 * Return the settings component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @return string
	 */
function bboss_get_settings_root_slug() {

	/**
	 * Filters the Settings component root slug.
	 *
	 * @since BuddyPress 1.5.0
	 *
	 * @param string $root_slug Settings component root slug.
	 */
	return apply_filters( 'bp_get_settings_root_slug', bboss()->settings->root_slug );
}

/**
 * Add the 'pending email change' message to the settings page.
 *
 * @since BuddyPress 2.1.0
 */
function bboss_settings_pending_email_notice() {
	$pending_email = bboss_get_user_meta( bboss_displayed_user_id(), 'pending_email_change', true );

	if ( empty( $pending_email['newemail'] ) ) {
		return;
	}

	if ( bboss_get_displayed_user_email() == $pending_email['newemail'] ) {
		return;
	}

	?>

	<aside class="bp-feedback bp-messages error">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
		<?php
		printf(
			/* translators: %s: the new pending email address. */
			esc_html__( 'There is a pending change of your email address to %s.', 'buddyboss' ),
			'<strong>' . esc_html( $pending_email['newemail'] ) . '</strong>'
		);
		?>
		<br />
		<?php
		echo wp_kses_post(
			sprintf(
				/* translators: 1: the current email address, 2: URL to cancel the pending email change. */
				__( 'Check your email (%1$s) for the verification link, or <a href="%2$s">cancel the pending change</a>.', 'buddyboss' ),
				'<strong>' . esc_html( bboss_get_displayed_user_email() ) . '</strong>',
				esc_url( wp_nonce_url( bboss_displayed_user_domain() . bboss_get_settings_slug() . '/?dismiss_email_change=1', 'bp_dismiss_email_change' ) )
			)
		);
		?>
		</p>
	</aside>

	<?php
}
add_action( 'bp_before_member_settings_template', 'bboss_settings_pending_email_notice' );
