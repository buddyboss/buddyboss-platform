<?php
/**
 * ReadyLaunch - Member Profile Edit template.
 *
 * This template handles editing member profile information.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bp_nouveau_xprofile_hook( 'before', 'edit_content' );

$profile_group_id = (int) bp_get_current_profile_group_id();

if ( bp_has_profile( 'profile_group_id=' . $profile_group_id ) ) {

	while ( bp_profile_groups() ) :
		bp_the_profile_group();
		?>

		<div class="bb-rl-profile-edit-header">
			<h2 class="screen-heading edit-profile-screen">
				<?php
				if ( 1 === $profile_group_id ) {
					echo esc_html__( 'Edit profile', 'buddyboss' );
				} else {
					printf(
						/* translators: %s = profile field group name */
						__( 'Edit "%s" Information', 'buddyboss' ),
						bp_get_the_profile_group_name()
					);
				}
				?>
			</h2>
		</div>

		<div class="bb-rl-profile-edit-wrapper">
			<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form profile-edit <?php bp_the_profile_group_slug(); ?>">
				<?php
				bp_nouveau_xprofile_hook( 'before', 'field_content' );

				while ( bp_profile_fields() ) :
					bp_the_profile_field();

					$get_profile_field_id = bp_get_the_profile_field_id();
					$member_type_field_id = function_exists( 'bp_get_xprofile_member_type_field_id' ) ? bp_get_xprofile_member_type_field_id() : 0;

					if ( function_exists( 'bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
						if ( $member_type_field_id && $get_profile_field_id === $member_type_field_id ) {
							continue;
						}
					}

					if ( function_exists( 'bp_check_member_type_field_have_options' ) && false === bp_check_member_type_field_have_options() && $get_profile_field_id === $member_type_field_id ) {
						continue;
					}

					bp_nouveau_xprofile_hook( 'before', 'field_html' );
					?>

					<div<?php bp_field_css_class( 'editfield' ); ?>>
						<fieldset>
							<?php
							$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
							$field_type->edit_field_html();
							bp_nouveau_xprofile_edit_visibilty();
							?>
						</fieldset>
					</div>

					<?php
					bp_nouveau_xprofile_hook( 'after', 'field_html' );

				endwhile;

				bp_nouveau_xprofile_hook( 'after', 'field_content' );
				?>
				<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />
				<?php bp_nouveau_submit_button( 'member-profile-edit' ); ?>
			</form>
		</div>

		<?php
	endwhile;
}
bp_nouveau_xprofile_hook( 'after', 'edit_content' );
