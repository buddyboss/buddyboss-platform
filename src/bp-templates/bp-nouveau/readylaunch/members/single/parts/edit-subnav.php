<?php
/**
 * ReadyLaunch - Member Edit Sub Navigation template.
 *
 * This template handles the secondary navigation for member profile editing.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>

<?php
$bp_nouveau = bboss_nouveau();
$has_nav    = bboss_nouveau_has_nav( array( 'type' => 'secondary' ) );
$nav_count  = count( $bp_nouveau->sorted_nav );

if ( ! $has_nav || $nav_count <= 1 ) {
	unset( $bp_nouveau->sorted_nav, $bp_nouveau->displayed_nav, $bp_nouveau->object_nav );
	return;
}
?>
<nav class="bb-rl-profile-edit-subnav" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Sub Menu', 'buddyboss-platform' ); ?>">
	<ul class="subnav">
		<?php
		// Profile link.
		$profile_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_profile_slug() );

		$is_enable_profile_avatar = true;
		if ( function_exists( 'bboss_disable_group_avatar_uploads' ) && bboss_disable_avatar_uploads() ) {
			$is_enable_profile_avatar = false;
		}

		if ( $is_enable_profile_avatar && bboss()->avatar->show_avatars ) {
			?>
			<li class="bb-rl-profile-subnav-item <?php echo esc_attr( bboss_is_user_change_avatar() ? 'selected' : '' ); ?>" id="bb-rl-xprofile-change-avatar">
				<a href="<?php echo esc_url( trailingslashit( $profile_link . 'change-avatar' ) ); ?>"><?php esc_html_e( 'Profile Photo', 'buddyboss-platform' ); ?></a>
			</li>
			<?php
		}

		$edit_profile_link = trailingslashit( bboss_loggedin_user_domain() . bboss_get_profile_slug() . '/edit/group/' );
		$args              = array(
			'user_id'                => bboss_loggedin_user_id(),
			'fetch_fields'           => false,
			'fetch_field_data'       => false,
			'fetch_visibility_level' => false,
		);

		$group_name = bboss_get_profile_group_name();

		if ( bboss_has_profile( $args ) ) {

			while ( bboss_profile_groups() ) {
				bboss_the_profile_group();

				$profile_group_id   = (int) bboss_get_the_profile_group_id();
				$profile_group_name = bboss_get_the_profile_group_name();

				$class = bboss_is_user_profile_edit() && $profile_group_name === $group_name ? 'selected' : '';
				?>
				<li class="bb-rl-profile-subnav-item <?php echo esc_attr( $class ); ?>" id="bb-rl-xprofile-edit-<?php echo esc_attr( bboss_get_the_profile_group_id() ); ?>">
					<a href="<?php echo esc_url( $edit_profile_link . bboss_get_the_profile_group_id() ); ?>">
						<?php
						if ( 1 === $profile_group_id ) {
							echo esc_html__( 'General Information', 'buddyboss-platform' );
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
