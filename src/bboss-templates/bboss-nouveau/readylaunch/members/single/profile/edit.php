<?php
/**
 * ReadyLaunch - Member Profile Edit template.
 *
 * This template handles editing member profile information.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_xprofile_hook( 'before', 'edit_content' );

$profile_group_id = (int) bboss_get_current_profile_group_id();

if ( bboss_has_profile( 'profile_group_id=' . $profile_group_id ) ) {

	while ( bboss_profile_groups() ) :
		bboss_the_profile_group();
		?>

		<div class="bb-rl-profile-edit-header">
			<h2 class="screen-heading edit-profile-screen">
				<?php
				if ( 1 === $profile_group_id ) {
					echo esc_html__( 'Edit profile', 'buddyboss' );
				} else {
					printf(
						/* translators: %s = profile field group name */
						esc_html__( 'Edit "%s" Information', 'buddyboss' ),
						bboss_get_the_profile_group_name() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- group name is pre-escaped via the wp_filter_kses filter chain; esc_html would double-encode entities such as "R&D".
					);
				}
				?>
			</h2>
		</div>

		<div class="bb-rl-profile-edit-wrapper">
			<form action="<?php bboss_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form profile-edit <?php bboss_the_profile_group_slug(); ?>">
				<?php
				bboss_nouveau_xprofile_hook( 'before', 'field_content' );

				while ( bboss_profile_fields() ) :
					bboss_the_profile_field();

					$get_profile_field_id = bboss_get_the_profile_field_id();
					$member_type_field_id = function_exists( 'bboss_get_xprofile_member_type_field_id' ) ? bboss_get_xprofile_member_type_field_id() : 0;

					if ( function_exists( 'bboss_member_type_enable_disable' ) && false === bboss_member_type_enable_disable() ) {
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
		</div>

		<?php
	endwhile;
}
bboss_nouveau_xprofile_hook( 'after', 'edit_content' );
