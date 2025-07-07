<?php
/**
 * ReadyLaunch - Member Edit Sub Navigation template.
 *
 * This template handles the secondary navigation for member profile editing.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php
$bp_nouveau = bp_nouveau();
$has_nav    = bp_nouveau_has_nav( array( 'type' => 'secondary' ) );
$nav_count  = count( $bp_nouveau->sorted_nav );

if ( ! $has_nav || $nav_count <= 1 ) {
	unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
	return;
}
?>
<nav class="bb-rl-profile-edit-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss' ); ?>">
	<ul class="subnav">
		<?php
		// Profile link.
		$profile_link = trailingslashit( bp_loggedin_user_domain() . bp_get_profile_slug() );

		$is_enable_profile_avatar = true;
		if ( function_exists( 'bp_disable_group_avatar_uploads' ) && bp_disable_avatar_uploads() ) {
			$is_enable_profile_avatar = false;
		}

		if ( $is_enable_profile_avatar && buddypress()->avatar->show_avatars ) {
			?>
			<li class="bb-rl-profile-subnav-item <?php echo esc_attr( bp_is_user_change_avatar() ? 'selected' : '' ); ?>" id="bb-rl-xprofile-change-avatar">
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

		$group_name = bp_get_profile_group_name();

		if ( bp_has_profile( $args ) ) {

			while ( bp_profile_groups() ) {
				bp_the_profile_group();

				$profile_group_id   = (int) bp_get_the_profile_group_id();
				$profile_group_name = bp_get_the_profile_group_name();

				$class = bp_is_user_profile_edit() && $profile_group_name === $group_name ? 'selected' : '';
				?>
				<li class="bb-rl-profile-subnav-item <?php echo esc_attr( $class ); ?>" id="bb-rl-xprofile-edit-<?php echo esc_attr( bp_get_the_profile_group_id() ); ?>">
					<a href="<?php echo esc_url( $edit_profile_link . bp_get_the_profile_group_id() ); ?>">
						<?php
						if ( 1 === $profile_group_id ) {
							echo esc_html__( 'General Information', 'buddyboss' );
						} else {
							echo esc_html( $profile_group_name );
						}
						?>
					</a>
				</li>
				<?php
			}
		}
		?>

	</ul>
</nav><!-- .item-list-tabs#subnav -->
