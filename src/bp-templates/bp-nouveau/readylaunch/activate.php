<?php
/**
 * ReadyLaunch - The template for members activate.
 *
 * @since   BuddyPress 3.0.0
 * @since   BuddyBoss 2.9.30
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$readylaunch_instance = bb_load_readylaunch();
$readylaunch_instance->bb_rl_login_enqueue_scripts();
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>
<?php
$readylaunch_instance = bb_load_readylaunch();
$bb_rl_theme_mode     = $readylaunch_instance->bb_rl_get_theme_mode();
$theme_mode_class     = '';
if ( 'choice' === $bb_rl_theme_mode ) {
	$dark_mode = isset( $_COOKIE['bb-rl-dark-mode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['bb-rl-dark-mode'] ) ) : 'false';
	if ( 'true' === $dark_mode ) {
		$theme_mode_class = 'bb-rl-dark-mode';
	}
} elseif ( 'dark' === $bb_rl_theme_mode ) {
	$theme_mode_class = 'bb-rl-dark-mode';
}
?>

<body <?php body_class( 'bb-readylaunch-template ' . $theme_mode_class ); ?>>

<?php
bp_get_template_part( 'common/header-register' );
?>

<div class="bb-readylaunch" id="activate-page">

	<?php
	bp_nouveau_template_notices();
	bp_nouveau_activation_hook( 'before', 'content' );

	if ( bp_account_was_activated() ) {

		if ( isset( $_GET['e'] ) ) { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! Your account details have been sent to you in a separate email.', 'buddyboss' ); ?></p>
		<?php } else { ?>
			<p><?php esc_html_e( 'Your account was activated successfully! You can now log in with the username and password you provided when you signed up.', 'buddyboss' ); ?></p>
			<?php
		}

		printf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( wp_login_url( bp_get_root_domain() ) ),
			esc_html__( 'Log In', 'buddyboss' )
		);
	} else {
		?>
		<p><?php esc_html_e( 'Please provide a valid activation key.', 'buddyboss' ); ?></p>
		<form action="" method="post" class="standard-form" id="activation-form">
			<label for="key"><?php esc_html_e( 'Activation Key:', 'buddyboss' ); ?></label>
			<input type="text" name="key" id="key" value="<?php echo esc_attr( bp_get_current_activation_key() ); ?>" />
			<?php
			/**
			 * Fires before the activation submit button.
			 *
			 * @since BuddyBoss 2.5.60
			 */
			do_action( 'bb_before_activate_submit_buttons' );
			?>
			<p class="submit">
				<input type="submit" name="submit" value="<?php esc_attr_e( 'Activate', 'buddyboss' ); ?>" />
			</p>
		</form>
		<?php
	}

	bp_nouveau_activation_hook( 'after', 'content' );
	?>

</div><!-- .page -->

</body>
</html>
