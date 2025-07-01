<?php
/**
 * The layout for register templates.
 *
 * This template handles the registration page layout for the ReadyLaunch theme.
 * It includes necessary scripts and styles for the registration form functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'bp-select2' );
wp_enqueue_style( 'bp-select2' );
wp_enqueue_script( 'jquery-magnific-popup' );
wp_enqueue_style( 'jquery-magnific-popup' );
wp_enqueue_style( 'bb-rl-login-fonts', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css', array(), bp_get_version() );
wp_enqueue_style( 'bb-rl-login-style-icons', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.min.css', array(), bp_get_version() );
wp_enqueue_style( 'bb-rl-login-style', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/login.css', array(), bp_get_version() );
?>

<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'bb-rl-register' ); ?>>

	<?php bp_get_template_part( 'common/header-register' ); ?>
	<div class="register-page-main">
		<?php
		$bb_rl_light_logo = bp_get_option( 'bb_rl_light_logo', array() );
		if ( ! empty( $bb_rl_light_logo ) ) :
			$logo_url   = esc_url( $bb_rl_light_logo['url'] );
			$logo_title = ! empty( $bb_rl_light_logo['title'] ) ? $bb_rl_light_logo['title'] : '';
			?>
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="register-page-logo">
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_title ); ?>">
			</a>
		<?php endif; ?>
		<h1><?php echo esc_html( get_the_title() ); ?></h1>
		<?php
		if ( have_posts() ) :
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				the_content();
			endwhile;
		endif;
		?>
	</div>
	<script>
		jQuery( document ).ready( function( $ ) {
			$( '.register-page select[multiple]' ).select2();

			$( '.bb-password-wrap .bb-toggle-password' ).on( 'click', function( e ) {
				e.preventDefault();
				var $this = $( this );
				var $input = $this.closest( '.bb-password-wrap' ).find( 'input' );
				var $icon = $this.find( 'i' );
				if ( $input.attr( 'type' ) === 'password' ) {
					$input.attr( 'type', 'text' );
					$icon.addClass( 'bb-icon-eye-slash' ).removeClass( 'bb-icon-eye' );
				} else {
					$input.attr( 'type', 'password' );
					$icon.addClass( 'bb-icon-eye' ).removeClass( 'bb-icon-eye-slash' );
				}
			} );

			if ( $( '.popup-modal-register' ).length ) {
				$( '.popup-modal-register' ).magnificPopup(
					{
						type            : 'inline',
						preloader       : false,
						fixedBgPos      : true,
						fixedContentPos : true
					}
				);
			}
		} );
	</script>
</body>
</html>

