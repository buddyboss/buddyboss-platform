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
		$bb_rl_light_logo = bb_load_readylaunch()->bb_rl_get_theme_logo( 'light' );
		$bb_rl_dark_logo = bb_load_readylaunch()->bb_rl_get_theme_logo( 'dark' );
	?>
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="bb-rl-logo-wrap">
			<?php if ( ! empty( $bb_rl_light_logo ) ) : ?>
				<img class="bb-rl-light-logo" src="<?php echo esc_url( $bb_rl_light_logo['url'] ); ?>" alt="<?php echo esc_attr( $bb_rl_light_logo['title'] ); ?>" />
			<?php else: ?>
				<span class="bb-rl-light-logo-text"><?php echo esc_html( $logo ); ?></span>
			<?php endif; ?>
			<?php if ( ! empty( $bb_rl_dark_logo ) ) : ?>
				<img class="bb-rl-dark-logo" src="<?php echo esc_url( $bb_rl_dark_logo['url'] ); ?>" alt="<?php echo esc_attr( $bb_rl_dark_logo['title'] ); ?>" />
			<?php else: ?>
				<span class="bb-rl-dark-logo-text"><?php echo esc_html( $logo ); ?></span>
			<?php endif; ?>
		</a>
	<?php
	} else {
		$key = $bb_rl_theme_mode;
		$bb_rl_logo = bb_load_readylaunch()->bb_rl_get_theme_logo( $key );
		if ( ! empty( $bb_rl_logo ) ) {
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="bb-rl-logo-wrap">
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
	}
	?>
	</<?php echo esc_html( $elem ); ?>>
</div>
