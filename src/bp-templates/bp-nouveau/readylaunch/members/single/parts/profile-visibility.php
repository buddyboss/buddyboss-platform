<?php
/**
 * The template for members single profile edit field visibility
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/parts/profile-visibility.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

if ( empty( $GLOBALS['profile_template'] ) ) {
	return;
}

$get_profile_field_id = bp_get_the_profile_field_id();
if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

	<div class="bb-rl-field-visibility-block">
		<p class="field-visibility-settings-toggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php echo esc_attr( $get_profile_field_id ); ?>">
			<span class="field-visibility-label"><?php esc_html_e( 'Visibility:', 'buddyboss' ); ?></span>
			<span class="field-visibility-toggle-action">
				<?php
				printf(
					/* translators: field visibility level, e.g. "public". */
					'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
				);
				?>
				<i class="bb-icons-rl-caret-down"></i>
			</span>
		</p>

		<div class="field-visibility-settings" id="field-visibility-settings-<?php echo esc_attr( $get_profile_field_id ); ?>">
			<fieldset>
				<?php bp_profile_visibility_radio_buttons(); ?>
			</fieldset>
		</div>
	</div>

<?php else : ?>

	<p class="field-visibility-settings-notoggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php echo esc_attr( $get_profile_field_id ); ?>">
		<?php
		printf(
			'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
		);
		?>
	</p>

<?php endif;
