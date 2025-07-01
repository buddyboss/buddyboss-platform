<?php
/**
 * ReadyLaunch - Header Site Logo template.
 *
 * This template handles displaying the site logo in the header.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// This is for better SEO.
$elem = ( is_front_page() && is_home() ) ? 'h1' : 'div';
$logo = get_bloginfo( 'name' );
?>

<div id="site-logo" class="bb-rl-site-branding">
	<<?php echo esc_html( $elem ); ?> class="site-title">
	<?php
	$bb_rl_theme_mode = bb_load_readylaunch()->bb_rl_get_theme_mode();
	if ( 'choice' === $bb_rl_theme_mode ) {
		$dark_mode = isset( $_COOKIE['bb-rl-dark-mode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['bb-rl-dark-mode'] ) ) : 'false';
		$key       = 'light';
		if ( 'true' === $dark_mode ) {
			$key = 'dark';
		}
	} else {
		$key = $bb_rl_theme_mode;
	}
	$bb_rl_logo = bb_load_readylaunch()->bb_rl_get_theme_logo( $key );
	if ( ! empty( $bb_rl_logo ) ) {
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="bb-rl-light-logo">
			<img src="<?php echo esc_url( $bb_rl_logo['url'] ); ?>" alt="<?php echo esc_attr( $bb_rl_logo['title'] ); ?>" />
		</a>
		<?php
	} else {
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php echo esc_html( $logo ); ?>
		</a>
		<?php
	}
	?>
	</<?php echo esc_html( $elem ); ?>>
</div>
