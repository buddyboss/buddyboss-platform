<?php
/**
 * LearnDash Password Reset Template for ReadyLaunch.
 *
 * @since   BuddyBoss 2.9.00
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Get registration variation setting
$registration_variation = function_exists( 'learndash_registration_variation' ) ? learndash_registration_variation() : 'classic';

// Check if modern variation is enabled
$is_modern = function_exists( 'LearnDash_Theme_Register_LD30' ) && 
             defined( 'LearnDash_Theme_Register_LD30::$variation_modern' ) && 
             $registration_variation === LearnDash_Theme_Register_LD30::$variation_modern;

// If modern variation, use the modern template
if ( $is_modern ) {
	// Load the modern password reset template
	if ( function_exists( 'learndash_reset_password_output_modern' ) ) {
		learndash_reset_password_output_modern( array() );
		return;
	}
}

// Get form width from attributes
$form_width = isset( $attr['width'] ) ? $attr['width'] : '';

// Check if password reset is enabled
$reset_password_enabled = function_exists( 'learndash_reset_password_is_enabled' ) ? learndash_reset_password_is_enabled() : true;

$active_template_key = function_exists( 'LearnDash_Theme_Register::get_active_theme_key' ) ? LearnDash_Theme_Register::get_active_theme_key() : 'ld30';
?>

<div class="bb-learndash-content-wrap">
	<article id="post-<?php echo esc_attr( get_the_ID() ); ?>" <?php post_class( array( 'bb-rl-learndash-reset-password', 'bb-rl-lms-reset-password', 'bb-rl-lms-course' ) ); ?>>
		<div class="bb-rl-lms-inner-block">
			<header class="bb-rl-heading">
				<div class="bb-rl-reset-password-banner">
					<h1 class="bb-rl-entry-title"><?php esc_html_e( 'Reset Your Password', 'buddyboss' ); ?></h1>
					<p class="bb-rl-reset-password-subtitle"><?php esc_html_e( 'Enter your email address and we\'ll send you a link to reset your password.', 'buddyboss' ); ?></p>
				</div>
			</header>

			<div class="bb-rl-reset-password-content">
				<div class="bb-rl-reset-password-content-inner">
					<div id="learndash-reset-password-wrapper" <?php echo ( ! empty( $form_width ) ) ? 'style="width: ' . esc_attr( $form_width ) . ';"' : ''; ?>>
						<?php
						// Show success message if password reset was successful
						if ( isset( $_GET['ld-reset-success'] ) && 'true' === $_GET['ld-reset-success'] ) {
							learndash_get_template_part(
								'modules/alert.php',
								array(
									'type'    => 'success',
									'icon'    => 'alert',
									'message' => __( 'Password reset email sent successfully.', 'buddyboss' ),
								),
								true
							);
						}

						// Show error message if there was an issue
						if ( isset( $_GET['ld-reset-error'] ) && 'true' === $_GET['ld-reset-error'] ) {
							learndash_get_template_part(
								'modules/alert.php',
								array(
									'type'    => 'warning',
									'icon'    => 'alert',
									'message' => __( 'There was an error processing your request. Please try again.', 'buddyboss' ),
								),
								true
							);
						}
						?>

						<?php if ( ! $reset_password_enabled ) : ?>
							<div class="bb-rl-reset-password-disabled">
								<?php esc_html_e( 'Password reset is currently disabled.', 'buddyboss' ); ?>
							</div>
						<?php else : ?>
							<div class="bb-rl-reset-password-form-wrapper">
								<?php
								// Call the original LearnDash password reset function directly to avoid shortcode loops
								if ( function_exists( 'learndash_reset_password_output' ) ) {
									// Temporarily remove our shortcode override to avoid loops
									remove_shortcode( 'ld_reset_password' );
									
									// Call the original function
									learndash_reset_password_output( array() );
									
									// Re-add our shortcode override
									add_shortcode( 'ld_reset_password', array( 'BB_Readylaunch_Learndash_Helper', 'bb_rl_learndash_reset_password_shortcode' ) );
								} else {
									// Fallback to WordPress password reset form
									wp_login_form( array(
										'redirect' => home_url(),
									) );
								}
								?>
							</div>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	</article>

	<?php 
	// Check if sidebar is enabled for password reset pages
	$bb_readylaunch = function_exists( 'BB_Readylaunch::instance' ) ? BB_Readylaunch::instance() : null;
	if ( $bb_readylaunch && method_exists( $bb_readylaunch, 'bb_is_sidebar_enabled_for_courses' ) && $bb_readylaunch->bb_is_sidebar_enabled_for_courses() ) : 
	?>
		<aside class="bb-learndash-sidebar">
			<div class="bb-rl-sidebar-content">
				<?php do_action( 'bb_readylaunch_learndash_sidebar' ); ?>
			</div>
		</aside>
	<?php endif; ?>
</div> 