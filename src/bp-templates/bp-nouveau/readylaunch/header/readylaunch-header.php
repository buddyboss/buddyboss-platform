<?php
/**
 * The header for ReadyLaunch.
 *
 * @package ReadyLaunch
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
</head>

<body <?php body_class( 'bb-reaylaunch-template' ); ?>>
<?php wp_body_open(); ?>
<div id="page" class="site app-layout">
	<header id="masthead" class="">
		<div class="container site-header-container flex">
			<a href="#" class="bb-toggle-panel"><i class="bb-icon-l bb-icon-sidebar"></i></a>
			<div id="site-logo" class="site-branding">
				<?php get_template_part( 'template-parts/site-logo' ); ?>
			</div>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'bb-readylaunch',
//					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => '',
					'walker'         => new BuddyBoss_SubMenuWrap(),
					//'menu_class'     => 'primary-menu bb-primary-overflow',
				)
			);
			?>
		</div>
		<div id="header-aside" class="header-aside">
			<div class="header-aside-inner">
				<?php
				if ( is_user_logged_in() ) {
					if ( bp_is_active( 'messages' ) ) {
						bp_get_template_part( 'header/messages-dropdown' );
					}
					if ( bp_is_active( 'notifications' ) ) {
						bp_get_template_part( 'header/notification-dropdown' );
					}
				}
				?>
			</div>
		</div>
	</header>
	<main id="primary" class="site-main">
