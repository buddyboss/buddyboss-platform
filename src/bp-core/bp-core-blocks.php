<?php
/**
 * Core BP Blocks functions.
 *
 * @package BuddyBoss
 * @subpackage Core
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * BuddyPress blocks require the BP REST API.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return bool True if the current installation supports BP Blocks.
 *              False otherwise.
 */
function bp_support_blocks() {
	/**
	 * Filter here, returning `false`, to completely disable BuddyPress blocks.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $value True if the BP REST API is available. False otherwise.
	 */
	return apply_filters( 'bp_support_blocks', bp_rest_api_is_available() );
}

/**
 * Enqueue additional BP Assets for the Block Editor.
 *
 * @since BuddyBoss [BBVERSION]
 */
function bp_enqueue_block_editor_assets() {

	/**
	 * Fires when it's time to enqueue BP Block assets.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	do_action( 'bp_enqueue_block_editor_assets' );
}
add_action( 'enqueue_block_editor_assets', 'bp_enqueue_block_editor_assets', 9 );

/**
 * Filters the Block Editor settings to gather BuddyPress ones into a `bp` key.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $editor_settings Default editor settings.
 * @return array The editor settings, including BP blocks, specific ones.
 */
function bp_blocks_editor_settings( $editor_settings = array() ) {
	/**
	 * Filter here to include your BB Blocks specific settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array $bp_editor_settings BP blocks specific editor settings.
	 */
	$bp_editor_settings = (array) apply_filters( 'bp_blocks_editor_settings', array() );

	if ( $bp_editor_settings ) {
		$editor_settings['bb'] = $bp_editor_settings;
	}

	return $editor_settings;
}
add_filter( 'block_editor_settings_all', 'bp_blocks_editor_settings' );

/**
 * Register a BuddyPress block type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args The registration arguments for the block type.
 * @return BP_Block   The BuddyPress block type object.
 */
function bp_register_block( $args = array() ) {
	if ( isset( $args['metadata'] ) && is_string( $args['metadata'] ) && file_exists( $args['metadata'] ) ) {
		$callback = array();

		if ( isset( $args['render_callback'] ) ) {
			$callback['render_callback'] = $args['render_callback'];
		}

		return register_block_type_from_metadata( $args['metadata'], $callback );
	}

	return new BP_Block( $args );
}

/**
 * Gets a Widget Block list of classnames.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $block_name The Block name.
 * @return array The list of widget classnames for the Block.
 */
function bp_blocks_get_widget_block_classnames( $block_name = '' ) {
	$components         = bp_core_get_active_components( array(), 'objects' );
	$components['core'] = buddypress()->core;
	$classnames         = array();

	foreach ( $components as $component ) {
		if ( isset( $component->block_globals[ $block_name ] ) ) {
			$block_props = $component->block_globals[ $block_name ]->props;

			if ( isset( $block_props['widget_classnames'] ) && $block_props['widget_classnames'] ) {
				$classnames = (array) $block_props['widget_classnames'];
				break;
			}
		}
	}

	return $classnames;
}

/**
 * Make sure the BP Widget Block classnames are included in Widget Blocks.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $classname The classname to be used in the block widget's container HTML.
 * @param string $block_name The name of the block.
 * @return string The classname to be used in the block widget's container HTML.
 */
function bp_widget_block_dynamic_classname( $classname, $block_name ) {
	$bp_classnames = bp_blocks_get_widget_block_classnames( $block_name );

	if ( $bp_classnames ) {
		$bp_classnames = array_map( 'sanitize_html_class', $bp_classnames );
		$classname    .= ' ' . implode( ' ', $bp_classnames );
	}

	return $classname;
}
add_filter( 'widget_block_dynamic_classname', 'bp_widget_block_dynamic_classname', 10, 2 );

/**
 * Callback function to render the Ready Launch Header block.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $attributes The block attributes.
 * @return string          HTML output.
 */
function bp_block_render_readylaunch_header_block( $attributes = array() ) {
	$block_args = bp_parse_args(
		$attributes,
		array(
			'showSearch'        => true,
			'showMessages'      => true,
			'showNotifications' => true,
			'showProfileMenu'   => true,
			'darkMode'          => false,
		)
	);

	$align_class = '';
	if ( isset( $attributes['align'] ) ) {
		$align_class = 'align' . $attributes['align'];
	}

	$min = bp_core_get_minified_asset_suffix();

	// Get dark mode class.
	$dark_mode_class = $block_args['darkMode'] ? 'bb-rl-dark-mode' : '';

	if ( $block_args['showSearch'] && bp_is_active( 'search' ) ) {
		wp_enqueue_style( 'bp-select2' );
		if ( function_exists( 'bb_rl_search_enqueue_scripts' ) ) {
			bb_rl_search_enqueue_scripts();
		}
	}

	wp_enqueue_script( 'bb-readylaunch-header-view' );

	wp_enqueue_style( 'bb-readylaunch-icons', buddypress()->plugin_url . "bp-templates/bp-nouveau/readylaunch/icons/css/bb-icons-rl{$min}.css", array(), bp_get_version() );

	ob_start();
	?>
	<header id="masthead" class="bb-rl-header bb-rl-header-block <?php echo esc_attr( $dark_mode_class . ' ' . $align_class ); ?>">
		<div class="bb-rl-container bb-rl-header-container">
			<a href="#" class="bb-rl-left-panel-mobile"><i class="bb-icons-rl-list"></i></a>
			<?php
			bp_get_template_part( 'readylaunch/header/site-logo' );
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
			<div id="bb-rl-header-aside" class="bb-rl-header-aside">
				<div class="bb-rl-header-aside-inner">
					<?php
					if ( $block_args['showSearch'] && bp_is_active( 'search' ) ) {
						bp_get_template_part( 'readylaunch/common/search/search-model' );
					}

					if ( is_user_logged_in() ) {
						if ( $block_args['showMessages'] && bp_is_active( 'messages' ) ) {
							bp_get_template_part( 'readylaunch/header/messages-dropdown' );
						}
						if ( $block_args['showNotifications'] && bp_is_active( 'notifications' ) ) {
							bp_get_template_part( 'readylaunch/header/notification-dropdown' );
						}
						if ( $block_args['showProfileMenu'] ) {
							?>
						<div class="user-wrap user-wrap-container">
							<?php
							$current_user = wp_get_current_user();
							$user_link    = function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $current_user->ID ) : get_author_posts_url( $current_user->ID );
							$display_name = function_exists( 'bp_core_get_user_displayname' ) ? bp_core_get_user_displayname( $current_user->ID ) : $current_user->display_name;
							?>

							<a class="bb-rl-user-link" href="<?php echo esc_url( $user_link ); ?>">
								<?php echo get_avatar( get_current_user_id(), 100 ); ?>
							</a>
							<div class="bb-rl-profile-dropdown">
								<?php bp_get_template_part( 'readylaunch/header/profile-dropdown' ); ?>
							</div>
						</div>
							<?php
						}
					} else {
						?>
						<div class="bb-rl-header-buttons">
							<a href="<?php echo esc_url( wp_login_url() ); ?>" class="bb-rl-button bb-rl-button--tertiaryLink bb-rl-button--small signin-button"><?php esc_html_e( 'Sign in', 'buddyboss' ); ?></a>

							<?php if ( get_option( 'users_can_register' ) ) : ?>
								<a href="<?php echo esc_url( wp_registration_url() ); ?>" class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small signup"><?php esc_html_e( 'Sign up', 'buddyboss' ); ?></a>
							<?php endif; ?>
						</div>
					<?php } ?>
				</div>
			</div>

			<?php if ( $block_args['showSearch'] || is_user_logged_in() ) : ?>
			<div class="bb-readylaunch-mobile-menu__wrap">
				<?php
				if ( $block_args['showSearch'] && bp_is_active( 'search' ) ) {
					?>
					<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form search-form" id="search-form">
						<label for="search" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></label>
						<div class="bb-rl-network-search-bar">
							<input id="search" name="s" type="search" value="" placeholder="<?php esc_attr_e( 'Search community...', 'buddyboss' ); ?>">
							<input type="hidden" name="bp_search" value="1">
							<button type="submit" id="search-submit" class="nouveau-search-submit">
								<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
								<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
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

				if ( is_user_logged_in() && (
					( $block_args['showMessages'] && bp_is_active( 'messages' ) ) ||
					( $block_args['showNotifications'] && bp_is_active( 'notifications' ) )
				) ) {
					?>
					<div class="bb-readylaunch-mobile-menu_items">
						<ul>
							<?php if ( $block_args['showMessages'] && bp_is_active( 'messages' ) ) { ?>
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
							<?php if ( $block_args['showNotifications'] && bp_is_active( 'notifications' ) ) { ?>
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
				<?php } ?>
			</div>
			<?php endif; ?>
		</div>
	</header>
	<?php
	return ob_get_clean();
}
