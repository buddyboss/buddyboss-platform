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
			<?php echo $logo; ?>
		</a>
	</<?php echo $elem; ?>>
</div>
