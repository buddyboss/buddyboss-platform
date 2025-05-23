<?php
/**
 * The template for displaying site logo
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\ReadyLaunch
 * @version 1.0.0
 */

// This is for better SEO.
$elem = ( is_front_page() && is_home() ) ? 'h1' : 'div';
$logo = get_bloginfo( 'name' );
?>

<div id="site-logo" class="bb-rl-site-branding">
	<<?php echo esc_html( $elem ); ?> class="site-title">
	<?php
	$bb_rl_light_logo = bp_get_option( 'bb_rl_light_logo', '' );
	$bb_rl_dark_logo  = bp_get_option( 'bb_rl_dark_logo', '' );
	if ( ! empty( $bb_rl_light_logo ) ) {
		$logo = $bb_rl_light_logo;
	} elseif ( ! empty( $bb_rl_dark_logo ) ) {
		$logo = $bb_rl_dark_logo;
	}
	if ( ! empty( $logo ) ) {
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php echo esc_url( $logo['url'] ); ?>" alt="<?php echo esc_attr( $logo['title'] ); ?>" />
		</a>
		<?php
	} else {
		?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/logo.png' ); ?>" alt="Logo" />
		</a>
		<?php
	}
	?>
	</<?php echo esc_html( $elem ); ?>>
</div>