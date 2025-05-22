<?php
/**
 * The layout for register templates.
 *
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

wp_enqueue_script( 'bp-select2' );
wp_enqueue_style( 'bp-select2' );
wp_enqueue_script( 'jquery-magnific-popup' );
wp_enqueue_style( 'jquery-magnific-popup' );
wp_enqueue_style( 'bb-rl-login-fonts', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/assets/fonts/fonts.css' );
wp_enqueue_style( 'bb-rl-login-style-icons', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl.min.css' );
wp_enqueue_style( 'bb-rl-login-style', buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/css/login.css' );
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
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home" class="register-page-logo">
			<img src="<?php echo esc_url( buddypress()->plugin_url . 'bp-templates/bp-nouveau/readylaunch/images/logo.png' ); ?>" alt="<?php esc_attr__( 'ReadyLaunch Logo', 'buddyboss' ); ?>">
		</a>
		<h1><?php echo get_the_title(); ?></h1>
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
			});

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
		});
	</script>
</body>
</html>

