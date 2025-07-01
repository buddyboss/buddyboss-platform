<?php
/**
 * ReadyLaunch - Header Profile Dropdown template.
 *
 * This template handles the profile dropdown display in the header for ReadyLaunch.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	return;
}

$profile_url = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );
?>

<ul class="bb-rl-profile-lists">
	<li class="bb-rl-profile-list-item">
		<a href="<?php echo esc_url( $profile_url ); ?>" class="bb-rl-profile-list-link">
			<i class="bb-icons-rl-user"></i>
			<?php esc_html_e( 'View profile', 'buddyboss' ); ?>
		</a>

		<ul class="bb-profile-submenu-lists">
			<?php
			// Always use logged-in user's profile.
			$profile_link = trailingslashit( bp_loggedin_user_domain() );

			// Temporarily set displayed user to logged-in user to get correct navigation.
			$old_displayed_user          = buddypress()->displayed_user;
			buddypress()->displayed_user = buddypress()->loggedin_user;

			$profile_nav = buddypress()->members->nav;

			// Reorder the user's primary nav according to the customizer setting.
			bp_nouveau_set_nav_item_order( $profile_nav, bp_nouveau_get_appearance_settings( 'user_nav_order' ) );

			// Get the navigation items using bp_get_nav_menu_items().
			$profile_nav = $profile_nav->get_primary();

			// Restore the original displayed user.
			buddypress()->displayed_user = $old_displayed_user;

			if ( ! empty( $profile_nav ) ) {
				foreach ( $profile_nav as $nav_item ) {
					$excluded_slugs = array();

					// Check for notifications function.
					if ( function_exists( 'bp_get_notifications_slug' ) ) {
						$excluded_slugs[] = bp_get_notifications_slug();
					}

					// Check for messages function.
					if ( function_exists( 'bp_get_messages_slug' ) ) {
						$excluded_slugs[] = bp_get_messages_slug();
					}

					// Check for settings function.
					if ( function_exists( 'bp_get_settings_slug' ) ) {
						$excluded_slugs[] = bp_get_settings_slug();
					}

					// Check for profile function.
					if ( function_exists( 'bp_get_profile_slug' ) ) {
						$excluded_slugs[] = bp_get_profile_slug();
					}

					if ( in_array( $nav_item->slug, $excluded_slugs, true ) ) {
						continue;
					}

					// Skip if show_for_displayed_user is empty and it's not the invites slug.
					if (
						empty( $nav_item->show_for_displayed_user ) &&
						( ! function_exists( 'bp_get_invites_slug' ) || bp_get_invites_slug() !== $nav_item->slug )
					) {
						continue;
					}

					?>
					<li class="bb-rl-profile-sublist-link" id="bb-rl-profile-view-<?php echo esc_attr( $nav_item->slug ); ?>">
						<a href="<?php echo esc_url( $nav_item->link ); ?>">
							<?php echo esc_html( $nav_item->name ); ?>
						</a>
					</li>
					<?php
				}
			}
			?>
		</ul>
	</li>

	<?php
	if ( bp_is_active( 'xprofile' ) ) {
		?>
		<li class="bb-rl-profile-list-item">
			<a href="<?php echo esc_url( bp_loggedin_user_domain() ); ?>" class="bb-rl-profile-list-link">
				<i class="bb-icons-rl-pencil-simple"></i>
				<?php esc_html_e( 'Edit profile', 'buddyboss' ); ?>
			</a>

			<ul class="bb-profile-submenu-lists">
				<?php
				// Profile link.
				$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

				$is_enable_profile_avatar = true;
				if ( function_exists( 'bp_disable_group_avatar_uploads' ) && bp_disable_avatar_uploads() ) {
					$is_enable_profile_avatar = false;
				}

				if ( $is_enable_profile_avatar && buddypress()->avatar->show_avatars ) {
					?>
					<li class="bb-rl-profile-sublist-link" id="bb-rl-xprofile-change-avatar">
						<a href="<?php echo esc_url( trailingslashit( $profile_link . 'change-avatar' ) ); ?>"><?php esc_html_e( 'Profile Photo', 'buddyboss' ); ?></a>
					</li>
					<?php
				}

				$edit_profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() . '/edit/group/' );
				$args              = array(
					'user_id'                => bp_loggedin_user_id(),
					'fetch_fields'           => false,
					'fetch_field_data'       => false,
					'fetch_visibility_level' => false,
				);

				if ( bp_has_profile( $args ) ) {

					while ( bp_profile_groups() ) {
						bp_the_profile_group();
						?>
						<li class="bb-rl-profile-sublist-link" id="bb-rl-xprofile-edit-<?php echo esc_attr( bp_get_the_profile_group_id() ); ?>">
							<a href="<?php echo esc_url( $edit_profile_link . bp_get_the_profile_group_id() ); ?>"><?php bp_the_profile_group_name(); ?></a>
						</li>
						<?php
					}
				}
				?>
			</ul>
		</li>

		<?php
	}

	if ( bp_is_active( 'settings' ) ) {
		// Always use logged-in user's settings.
		$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );
		?>

		<li class="bb-rl-profile-list-item">
			<a href="<?php echo esc_url( $settings_link ); ?>" class="bb-rl-profile-list-link">
				<i class="bb-icons-rl-user-gear"></i>
				<?php esc_html_e( 'Account settings', 'buddyboss' ); ?>
			</a>

			<ul class="bb-profile-submenu-lists">
				<?php
				// Temporarily set displayed user to logged-in user to get correct navigation.
				$old_displayed_user          = buddypress()->displayed_user;
				buddypress()->displayed_user = buddypress()->loggedin_user;

				// Get the navigation items.
				$settings_nav = buddypress()->members->nav->get_secondary( array( 'parent_slug' => bp_get_settings_slug() ) );

				// Restore the original displayed user.
				buddypress()->displayed_user = $old_displayed_user;

				$excluded_slugs = array( 'capabilities' );
				if ( bp_disable_account_deletion() || bp_current_user_can( 'bp_moderate' ) ) {
					$excluded_slugs[] = 'delete-account';
				}


				if (
					(int) bp_loggedin_user_id() !== (int) bp_displayed_user_id() &&
					! bp_disable_account_deletion() &&
					! bp_current_user_can( 'bp_moderate' )
				) {
					$delete_nav_item = new BP_Core_Nav_Item(
						array(
							'name'            => __( 'Delete Account', 'buddyboss' ),
							'slug'            => 'delete-account',
							'parent_url'      => $settings_link,
							'link'            => trailingslashit( $settings_link . 'delete-account' ),
							'parent_slug'     => bp_get_settings_slug(),
							'screen_function' => 'bp_settings_screen_delete_account',
							'position'        => 90,
							'user_has_access' => ! is_super_admin( bp_displayed_user_id() ),
						)
					);

					$settings_nav[ $delete_nav_item->position ] = $delete_nav_item;
				}


				if ( ! empty( $settings_nav ) ) {
					foreach ( $settings_nav as $nav_item ) {
						if (
							(
								(int) bp_loggedin_user_id() === (int) bp_displayed_user_id() &&
								empty( $nav_item->user_has_access )
							) ||
							in_array( $nav_item->slug, $excluded_slugs, true )
						) {
							continue;
						}

						$menu_link = str_replace( bp_displayed_user_domain(), bp_loggedin_user_domain(), $nav_item->link );

						?>
						<li class="bb-rl-profile-sublist-link" id="bb-rl-profile-my-account-settings-<?php echo esc_attr( $nav_item->slug ); ?>">
							<a href="<?php echo esc_url( $menu_link ); ?>">
							<?php echo esc_html( $nav_item->name ); ?>
							</a>
						</li>
						<?php
					}
				}
				?>
			</ul>
		</li>
		<?php
	}
	?>

	<?php
	$bb_rl_theme_mode = bb_load_readylaunch()->bb_rl_get_theme_mode();
	if ( 'choice' === $bb_rl_theme_mode ) {
		?>
		<li class="bb-rl-profile-list-item">
			<a href="#" class="bb-rl-profile-list-link">
				<i class="bb-icons-rl-sun"></i>
				<?php esc_html_e( 'Theme', 'buddyboss' ); ?>
			</a>

			<ul class="bb-profile-submenu-lists">
				<li class="bb-rl-profile-sublist-link" id="bb-rl-profile-theme-light">
					<a href="#">
						<?php esc_html_e( 'Light', 'buddyboss' ); ?>
					</a>
				</li>
				<li class="bb-rl-profile-sublist-link" id="bb-rl-profile-theme-dark">
					<a href="#">
						<?php esc_html_e( 'Dark', 'buddyboss' ); ?>
					</a>
				</li>
			</ul>
		</li>
		<?php
	}
	?>

	<li class="bb-rl-profile-list-item">
		<a href="<?php echo esc_url( wp_logout_url( bp_get_requested_url() ) ); ?>">
			<i class="bb-icons-rl-sign-out"></i>
			<?php esc_html_e( 'Log out', 'buddyboss' ); ?>
		</a>
	</li>

</ul>
