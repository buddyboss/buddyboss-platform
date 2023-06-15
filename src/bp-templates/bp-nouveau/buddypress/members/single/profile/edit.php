<?php
/**
 * The template for members single profile edit
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/profile/edit.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_xprofile_hook( 'before', 'edit_content' ); ?>

<?php if ( bp_has_profile( 'profile_group_id=' . bp_get_current_profile_group_id() ) ) :
	while ( bp_profile_groups() ) :
		bp_the_profile_group();
	?>

<h2 class="screen-heading edit-profile-screen">
	<?php
	printf(
		/* translators: %s = profile field group name */
		__( 'Edit "%s" Information', 'buddyboss' ),
		bp_get_the_profile_group_name()
	)
	?>
</h2>

		<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form profile-edit <?php bp_the_profile_group_slug(); ?>">

			<?php bp_nouveau_xprofile_hook( 'before', 'field_content' ); ?>

				<?php if ( bp_profile_has_multiple_groups() ) : ?>
					<ul class="button-tabs button-nav">

						<?php bp_profile_group_tabs(); ?>

					</ul>
				<?php endif; ?>

				<?php
				while ( bp_profile_fields() ) :
					bp_the_profile_field();

					if ( function_exists('bp_member_type_enable_disable' ) && false === bp_member_type_enable_disable() ) {
						if ( function_exists( 'bp_get_xprofile_member_type_field_id') && bp_get_the_profile_field_id() === bp_get_xprofile_member_type_field_id() ) {
							continue;
						}
					}

					if ( function_exists( 'bp_check_member_type_field_have_options' ) && false === bp_check_member_type_field_have_options() && bp_get_the_profile_field_id() === bp_get_xprofile_member_type_field_id()) {
						continue;
					}
					?>

                    <?php bp_nouveau_xprofile_hook( 'before', 'field_html' ); ?>

					<div<?php bp_field_css_class( 'editfield' ); ?>>
						<fieldset>

						<?php
						$field_type = bp_xprofile_create_field_type( bp_get_the_profile_field_type() );
						$field_type->edit_field_html();
						?>

						<?php bp_nouveau_xprofile_edit_visibilty(); ?>

						</fieldset>
					</div>

                    <?php bp_nouveau_xprofile_hook( 'after', 'field_html' ); ?>

				<?php endwhile; ?>

			<?php bp_nouveau_xprofile_hook( 'after', 'field_content' ); ?>

			<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

			<?php bp_nouveau_submit_button( 'member-profile-edit' ); ?>

		</form>

	<?php endwhile; ?>

<?php endif; ?>

<?php
bp_nouveau_xprofile_hook( 'after', 'edit_content' );
