<?php
/**
 * The template for BP Nouveau Register and Logon page header
 *
 * This template handles the header section for registration and login pages.
 * It displays the site logo or community name and appropriate action links.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<header class="bb-rl-login-header">
	<div class="bb-rl-login-header-logo">
		<?php
		$bb_rl_light_logo = bp_get_option( 'bb_rl_light_logo', array() );
		if ( ! empty( $bb_rl_light_logo ) ) {
			?>
			<img src="<?php echo esc_url( $bb_rl_light_logo['url'] ); ?>" alt="<?php echo esc_attr( $bb_rl_light_logo['title'] ); ?>">
			<style>
				#login h1.wp-login-logo a {
					background-image: url(<?php echo esc_url( $bb_rl_light_logo['url'] ); ?>);
				}
			</style>
			<?php
		} else {
			$community_name = bp_get_option( 'blogname', '' );
			?>
			<h2>
				<?php
				if ( ! empty( $community_name ) ) {
					echo esc_html( $community_name );
				} else {
					echo esc_html( get_the_title() );
				}
				?>
			</h2>
			<?php
		}
		?>
	</div>
	<div class="bb-rl-login-header-actions">
		<?php if ( is_user_logged_in() ) : ?>
			<a href="<?php echo esc_url( wp_logout_url() ); ?>" class="bb-rl-button bb-rl-button--secondary-fill bb-rl-button--small"><?php esc_html_e( 'Sign Out', 'buddyboss' ); ?></a>
		<?php elseif ( bp_is_register_page() ) : ?>
			<span class="bb-rl-login-header-actions-text"><?php esc_html_e( 'Already have an account?', 'buddyboss' ); ?></span>
			<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bb-rl-button bb-rl-button--secondary-fill bb-rl-button--small"><?php esc_html_e( 'Sign In', 'buddyboss' ); ?></a>
		<?php else : ?>
			<span class="bb-rl-login-header-actions-text"><?php esc_html_e( 'Don\'t have an account?', 'buddyboss' ); ?></span>
			<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="bb-rl-button bb-rl-button--secondary-fill bb-rl-button--small"><?php esc_html_e( 'Sign Up', 'buddyboss' ); ?></a>
		<?php endif; ?>
	</div>
</header>
