<?php
/**
 * The header for ReadyLaunch.
 *
 * @package ReadyLaunch
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
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
<?php bp_get_template_part( 'sidebar/left-sidebar' ); ?>
<div id="page" class="site bb-readylaunch">
	<header id="masthead" class="bbrl-header">
		<div class="bbrl-container bbrl-header-container flex justify-between items-center">
			<div id="site-logo" class="bbrl-site-branding">
				<?php get_template_part( 'template-parts/site-logo' ); ?>
			</div>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'bb-readylaunch',
					'menu_id'        => '',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'bb-readylaunch-menu',
				)
			);
			?>
			<div id="header-aside" class="header-aside">
				<div class="header-aside-inner flex items-center">
					<button class="bbrl-button bbrl-button--secondaryOutline bbrl-header-search">
						<i class="bb-icons-rl-magnifying-glass"></i> <?php echo esc_html( 'Search community', 'buddyboss' ); ?>
					</button>
					<?php
					if ( is_user_logged_in() ) {
						if ( bp_is_active( 'messages' ) ) {
							bp_get_template_part( 'header/messages-dropdown' );
						}
						if ( bp_is_active( 'notifications' ) ) {
							bp_get_template_part( 'header/notification-dropdown' );
						}
						?>
						<div class="user-wrap user-wrap-container">
							<?php
							$current_user = wp_get_current_user();
							$user_link    = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $current_user->ID ) : get_author_posts_url( $current_user->ID );
							$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $current_user->ID ) : $current_user->display_name;
							?>

							<a class="user-link" href="<?php echo esc_url( $user_link ); ?>">
								<?php echo get_avatar( get_current_user_id(), 100 ); ?>
							</a>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</header>
	<main id="primary" class="site-main">
