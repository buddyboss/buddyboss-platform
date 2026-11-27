<?php
/**
 * The template for members activate
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/activate.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_activation_hook( 'before', 'page' ); ?>

<div class="page" id="activate-page">

	<?php
	bboss_nouveau_template_notices();
	bboss_nouveau_activation_hook( 'before', 'content' );

	if ( bboss_account_was_activated() ) {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only; only picks which success message is shown.
		if ( isset( $_GET['e'] ) ) { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></p>
		<?php } else { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></p>
			<?php
		}

		printf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( wp_login_url( bboss_get_root_domain() ) ),
			esc_html__( 'Log In', 'buddyboss-community-social-network-groups-forums-messaging' )
		);
	} else {
		?>
		<p><?php esc_html_e( 'Please provide a valid activation key.', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></p>
		<form action="" method="post" class="standard-form" id="activation-form">
			<label for="key"><?php esc_html_e( 'Activation Key:', 'buddyboss-community-social-network-groups-forums-messaging' ); ?></label>
			<input type="text" name="key" id="key" value="<?php echo esc_attr( bboss_get_current_activation_key() ); ?>" />
			<?php
			/**
			 * Fires before the activation submit button.
			 *
			 * @since BuddyBoss 2.5.60
			 */
			do_action( 'bb_before_activate_submit_buttons' );
			?>
			<p class="submit">
				<input type="submit" name="submit" value="<?php esc_attr_e( 'Activate', 'buddyboss-community-social-network-groups-forums-messaging' ); ?>" />
			</p>
		</form>
		<?php
	}

	bboss_nouveau_activation_hook( 'after', 'content' );
	?>

</div><!-- .page -->

<?php bboss_nouveau_activation_hook( 'after', 'page' ); ?>
