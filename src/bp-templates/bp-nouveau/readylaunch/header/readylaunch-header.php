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

<?php
$bb_rl_theme_mode = BB_Readylaunch::instance()->bb_rl_get_theme_mode();
$theme_mode_class = '';
if ( 'choice' === $bb_rl_theme_mode ) {
	$dark_mode = isset( $_COOKIE['bb-rl-dark-mode'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['bb-rl-dark-mode'] ) ) : 'false';
	if ( 'true' === $dark_mode ) {
		$theme_mode_class = 'bb-rl-dark-mode';
	}
} elseif ( 'dark' === $bb_rl_theme_mode ) {
	$theme_mode_class = 'bb-rl-dark-mode';
}
?>

<body <?php body_class( 'bb-readylaunch-template ' . $theme_mode_class ); ?>>
<?php
wp_body_open();
bp_get_template_part( 'sidebar/left-sidebar' );
?>
<div id="page" class="site bb-readylaunch">
	<header id="masthead" class="bb-rl-header">
		<div class="bb-rl-container bb-rl-header-container flex justify-between items-center">
			<a href="#" class="bb-rl-left-panel-mobile"><i class="bb-icons-rl-list"></i></a>
			<?php
			bp_get_template_part( 'header/site-logo' );
			wp_nav_menu(
				array(
					'theme_location' => bp_get_option( 'bb_rl_header_menu', 'bb-readylaunch' ),
					'menu_id'        => '',
					'container'      => false,
					'fallback_cb'    => false,
					'menu_class'     => 'bb-readylaunch-menu',
				)
			);
			?>
			<div id="header-aside" class="header-aside">
				<div class="header-aside-inner flex items-center">
					<?php
					if ( bp_is_active( 'search' ) ) {
						bp_get_template_part( 'common/search/search-model' );
					}

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
							<?php if ( is_user_logged_in() ) { ?>
								<div class="bb-rl-profile-dropdown">
									<?php bp_get_template_part( 'header/profile-dropdown' ); ?>
								</div>
							<?php } ?>
						</div>
					<?php } else { ?>
						<div class="bb-rl-header-buttons">
							<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bb-rl-button bb-rl-button--tertiaryLink bb-rl-button--small signin-button"><?php esc_html_e( 'Sign in', 'buddyboss' ); ?></a>

							<?php if ( get_option( 'users_can_register' ) ) : ?>
								<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small signup"><?php esc_html_e( 'Sign up', 'buddyboss' ); ?></a>
							<?php endif; ?>
						</div>
					<?php } ?>
				</div>
			</div>

			<div class="bb-readylaunch-mobile-menu__wrap">
				<?php
				if ( bp_is_active( 'search' ) ) {
					?>
						<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form search-form" id="mobile-search-form">
							<label for="mobile-search" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></label>
							<div class="bb-rl-network-search-bar">
								<input id="mobile-search" name="s" type="search" value="" placeholder="<?php esc_attr_e( 'Search community...', 'buddyboss' ); ?>">
								<input type="hidden" name="bp_search" value="1">
								<button type="submit" id="mobile-search-submit" class="nouveau-search-submit">
									<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
									<span id="mobile-button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
								</button>
								<a href="javascript:;" class="bb-rl-network-search-clear bp-hide"><?php esc_html_e( 'Clear Search', 'buddyboss' ); ?></a>
							</div>
						</form>
					<?php } ?>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'bb-readylaunch',
							'menu_id'        => '',
							'container'      => false,
							'fallback_cb'    => false,
							'menu_class'     => 'bb-readylaunch-mobile-menu',
						)
					);
					if ( is_user_logged_in() && ( bp_is_active( 'messages' ) || bp_is_active( 'notifications' ) ) ) {
						?>
				<div class="bb-readylaunch-mobile-menu_items">
					<ul>
						<?php if ( bp_is_active( 'messages' ) ) { ?>
							<li>
								<a href="javascript:void(0);" ref="notification_bell" class="notification-link">
									<i class="bb-icons-rl-chat-teardrop-text"></i>
									<span class="notification-label"><?php esc_html_e( 'Messages', 'buddyboss' ); ?></span>
									<?php
										$unread_message_count = messages_get_unread_count();
									if ( $unread_message_count > 0 ) :
										?>
										<span class="count"><?php echo esc_html( $unread_message_count ); ?>+</span>
									<?php endif; ?>
								</a>
							</li>
						<?php } ?>
						<?php if ( bp_is_active( 'notifications' ) ) { ?>
							<li>
								<a href="javascript:void(0);" ref="notification_bell" class="notification-link">
									<i class="bb-icons-rl-bell-simple"></i>
									<span class="notification-label"><?php esc_html_e( 'Notifications', 'buddyboss' ); ?></span>
									<?php
										$notifications             = bp_notifications_get_unread_notification_count( bp_loggedin_user_id() );
										$unread_notification_count = ! empty( $notifications ) ? $notifications : 0;
									if ( $unread_notification_count > 0 ) :
										?>
										<span class="count"><?php echo esc_html( $unread_notification_count ); ?>+</span>
									<?php endif; ?>
								</a>
							</li>
						<?php } ?>
					</ul>
				</div>
						<?php
					}
					bp_get_template_part( 'sidebar/left-sidebar' );
					?>
			</div>
		</div>
	</header>
	<main id="primary" class="site-main">
		<div class="bb-rl-container">
