<?php
/**
 * The template for displaying site logo
 *
 * @since   BuddyBoss [BBVERSION]
 * @version 1.0.0
 */

// This is for better SEO
$elem = ( is_front_page() && is_home() ) ? 'h1' : 'div';
$logo = get_bloginfo( 'name' );
?>

<div id="site-logo" class="bb-rl-site-branding">
	<<?php echo $elem; ?> class="site-title">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<img src="<?php echo esc_url( buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/images/logo.png" ); ?>" alt="Logo" />
	</a>
	</<?php echo $elem; ?>>
</div>