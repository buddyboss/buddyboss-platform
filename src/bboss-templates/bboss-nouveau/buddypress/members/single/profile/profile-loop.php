<?php
/**
 * The template for members profile loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile/profile-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<h2 class="screen-heading view-profile-screen"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></h2>
<?php
bboss_nouveau_xprofile_hook( 'before', 'loop_content' );

if ( bboss_has_profile() ) {

	while ( bboss_profile_groups() ) :
		bboss_the_profile_group();

		if ( bboss_profile_group_has_fields() ) :

			bboss_nouveau_xprofile_hook( 'before', 'field_content' );
			?>
			<div class="bp-widget <?php bboss_the_profile_group_slug(); ?>">

				<h3 class="screen-heading profile-group-title">
					<?php bboss_the_profile_group_name(); ?>
				</h3>

				<table class="profile-fields bp-tables-user">
					<?php
					while ( bboss_profile_fields() ) :
						bboss_the_profile_field();

						if ( function_exists( 'bboss_member_type_enable_disable' ) && false === bboss_member_type_enable_disable() ) {
							if ( function_exists( 'bboss_get_xprofile_member_type_field_id' ) && bboss_get_the_profile_field_id() === bboss_get_xprofile_member_type_field_id() ) {
								continue;
							}
						}

						bboss_nouveau_xprofile_hook( 'before', 'field_item' );

						if ( bboss_field_has_data() ) :
							?>

							<tr<?php bboss_field_css_class(); ?>>

								<td class="label"><?php bboss_the_profile_field_name(); ?></td>

								<td class="data"><?php bboss_the_profile_field_value(); ?></td>

							</tr>
							<?php
						endif;

						bboss_nouveau_xprofile_hook( '', 'field_item' );

					endwhile;

					bboss_nouveau_xprofile_hook( 'after', 'field_items' );
					?>
				</table>
			</div>

			<?php
			bboss_nouveau_xprofile_hook( 'after', 'field_content' );
		endif;

	endwhile;

	bboss_nouveau_xprofile_hook( '', 'field_buttons' );

} else {
	?>

	<div class="info bp-feedback">
		<span class="bp-icon" aria-hidden="true"></span>
		<p>
			<?php
			if ( bboss_is_my_profile() ) {
				esc_html_e( 'You have not yet added details to your profile.', 'buddyboss' );
			} else {
				esc_html_e( 'This member has not yet added details to their profile.', 'buddyboss' );
			}
			?>
		</p>
	</div><?php
}

bboss_nouveau_xprofile_hook( 'after', 'loop_content' );
