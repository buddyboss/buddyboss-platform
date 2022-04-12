<?php
/**
 * The template for members settings ( Profile )
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/profile.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading profile-settings-screen">
	<?php esc_html_e( 'Profile Visibility Settings', 'buddyboss' ); ?>
</h2>

<p class="bp-help-text profile-visibility-info">
	<?php esc_html_e( 'Select who may see your profile details.', 'buddyboss' ); ?>
</p>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_get_settings_slug() . '/profile/' ); ?>" method="post" class="standard-form" id="settings-form">

	<?php if ( bp_xprofile_get_settings_fields() ) : ?>

		<?php
		while ( bp_profile_groups() ) :
			bp_the_profile_group();
			$group_id	= bp_get_the_profile_group_id();
			// Check if Current Group is repeater if YES then get number of fields inside current group.
			$is_group_repeater_str  = bp_xprofile_get_meta( $group_id, 'group', 'is_repeater_enabled', true );
			$is_group_repeater      = ( 'on' === $is_group_repeater_str ) ? true : false;
			$group_url              = esc_url( trailingslashit( bp_displayed_user_domain() . bp_get_profile_slug() . '/edit/group/' . $group_id ) );
		?>

			<?php if ( bp_profile_fields() ) : ?>

				<table class="profile-settings bp-tables-user" id="<?php echo esc_attr( 'xprofile-settings-' . bp_get_the_profile_group_slug() ); ?>">
					<thead>
						<tr>
							<th class="title field-group-name"><?php bp_the_profile_group_name(); ?></th>
							<th class="title"><?php esc_html_e( 'Visibility', 'buddyboss' ); ?></th>
						</tr>
					</thead>

					<tbody>

						<?php
						while ( bp_profile_fields() ) :
							bp_the_profile_field();
						?>

							<tr <?php bp_field_css_class(); ?>>
								<td class="field-name"><?php bp_the_profile_field_name(); ?></td>
								<?php if ( $is_group_repeater ) : ?>
									<td class="field-visibility">
										<a title="<?php esc_html_e( 'Manage Privacy', 'buddyboss' ); ?>" href="<?php echo esc_url( $group_url ); ?>" ><?php esc_html_e( 'Manage Privacy', 'buddyboss' ); ?></a>
									</td>
								<?php else : ?>
									<td class="field-visibility"><?php bp_profile_settings_visibility_select(); ?></td>
								<?php endif; ?>
							</tr>

						<?php endwhile; ?>

					</tbody>
				</table>

			<?php endif; ?>

		<?php endwhile; ?>

	<?php endif; ?>

	<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

	<?php bp_nouveau_submit_button( 'members-profile-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
