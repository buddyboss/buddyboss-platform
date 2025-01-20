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

	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap" rel="stylesheet">

	<?php wp_head(); ?>
</head>

<body <?php body_class( 'bb-reaylaunch-template' ); ?>>
<?php wp_body_open(); ?>
<?php bp_get_template_part( 'sidebar/left-sidebar' ); ?>
<div id="page" class="site bb-readylaunch">
	<header id="masthead" class="bbrl-header">
		<div class="bbrl-container bbrl-header-container flex justify-between items-center">
			<a href="#" class="bbrl-left-panel-mobile"><i class="bb-icons-rl-list"></i></a>
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
						<i class="bb-icons-rl-magnifying-glass"></i>
						<span class="bbrl-header-search__label">
							<?php echo esc_html( 'Search community', 'buddyboss' ); ?>
						</span>
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

			<div class="bb-readylaunch-mobile-menu__wrap">
				<?php
				if ( is_user_logged_in() ) {
				?>
					<div class="bbrl-mobile-panel-header flex items-center justify-between">
						<div class="bbrl-mobile-user-wrap flex items-center">
							<?php
							$current_user = wp_get_current_user();
							$user_link    = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $current_user->ID ) : get_author_posts_url( $current_user->ID );
							$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $current_user->ID ) : $current_user->display_name;
							?>

							<a class="bbrl-mobile-user-link" href="<?php echo esc_url( $user_link ); ?>">
								<?php echo get_avatar( get_current_user_id(), 100 ); ?>
							</a>
							<div>
									<a href="<?php echo esc_url( $user_link ); ?>" class="bbrl-mobile-user-name">
										<?php echo esc_html( $display_name ); ?>
									</a>
									<?php
									if ( function_exists( 'bp_is_active' ) && bp_is_active( 'settings' ) ) {
										$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
										?>
										<div class="bbrl-my-account-link">
											<a href="<?php echo esc_url( $settings_link ); ?>"><?php esc_html_e( 'My Account', 'buddyboss' ); ?></a>
										</div>
									<?php
									}
									?>
							</div>
						</div><!-- .bbrl-mobile-user-wrap -->
						<a href="#" class="bbrl-close-panel-mobile"><i class="bb-icons-rl-bold bb-icons-rl-x"></i></a>
					</div> <!-- .brl-mobile-panel-header -->
				<?php
				}
				wp_nav_menu(
					array(
						'theme_location' => 'bb-readylaunch',
						'menu_id'        => '',
						'container'      => false,
						'fallback_cb'    => false,
						'menu_class'     => 'bb-readylaunch-mobile-menu',
					)
				);
				?>
			</div>
		</div>
	</header>
	<main id="primary" class="site-main">
