<?php
/**
 * The template for members single profile edit
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile/edit.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_xprofile_hook( 'before', 'edit_content' );

if ( bboss_has_profile( 'profile_group_id=' . bboss_get_current_profile_group_id() ) ) {

	while ( bboss_profile_groups() ) :
		bboss_the_profile_group();
		?>

		<h2 class="screen-heading edit-profile-screen">
			<?php
			printf(
			/* translators: %s = profile field group name */
				esc_html__( 'Edit "%s" Information', 'buddyboss-platform' ),
				esc_html( bboss_get_the_profile_group_name() )
			)
			?>
		</h2>

		<form action="<?php bboss_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form profile-edit <?php bboss_the_profile_group_slug(); ?>">
			<?php
			bboss_nouveau_xprofile_hook( 'before', 'field_content' );

			if ( bboss_profile_has_multiple_groups() ) {
				?>
				<ul class="button-tabs button-nav">
					<?php bboss_profile_group_tabs(); ?>
				</ul>
				<?php
			}

			while ( bboss_profile_fields() ) :
				bboss_the_profile_field();

				$get_profile_field_id = bboss_get_the_profile_field_id();
				$member_type_field_id = function_exists( 'bboss_get_xprofile_member_type_field_id') ? bboss_get_xprofile_member_type_field_id() : 0;

				if ( function_exists('bboss_member_type_enable_disable' ) && false === bboss_member_type_enable_disable() ) {
					if ( $member_type_field_id && $get_profile_field_id === $member_type_field_id ) {
						continue;
					}
				}

				if ( function_exists( 'bboss_check_member_type_field_have_options' ) && false === bboss_check_member_type_field_have_options() && $get_profile_field_id === $member_type_field_id ) {
					continue;
				}

				bboss_nouveau_xprofile_hook( 'before', 'field_html' );
				?>

				<div<?php bboss_field_css_class( 'editfield' ); ?>>
					<fieldset>
						<?php
						$field_type = bboss_xprofile_create_field_type( bboss_get_the_profile_field_type() );
						$field_type->edit_field_html();
						bboss_nouveau_xprofile_edit_visibilty();
						?>
					</fieldset>
				</div>

				<?php
				bboss_nouveau_xprofile_hook( 'after', 'field_html' );

			endwhile;

			bboss_nouveau_xprofile_hook( 'after', 'field_content' );
			?>
			<input type="hidden" name="field_ids" id="field_ids" value="<?php bboss_the_profile_field_ids(); ?>" />
			<?php bboss_nouveau_submit_button( 'member-profile-edit' ); ?>
		</form>

		<?php
	endwhile;
}
bboss_nouveau_xprofile_hook( 'after', 'edit_content' );
