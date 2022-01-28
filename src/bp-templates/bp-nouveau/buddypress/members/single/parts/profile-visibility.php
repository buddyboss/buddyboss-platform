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
?>

<?php if ( bp_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

	<p class="field-visibility-settings-toggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id(); ?>">

		<?php
		printf(
			/* translators: field visibility level, e.g. "public". */
			'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
		);
		?>
		<button class="visibility-toggle-link button" type="button"><?php esc_html_e( 'Change', 'buddyboss' ); ?></button>

	</p>

	<div class="field-visibility-settings" id="field-visibility-settings-<?php bp_the_profile_field_id(); ?>">
		<fieldset>
			<legend><?php esc_html_e( 'Select who is allowed to see this field?', 'buddyboss' ); ?></legend>

			<?php bp_profile_visibility_radio_buttons(); ?>

		</fieldset>
		<button class="field-visibility-settings-close button" type="button"><?php esc_html_e( 'Close', 'buddyboss' ); ?></button>
	</div>

<?php else : ?>

	<p class="field-visibility-settings-notoggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php bp_the_profile_field_id(); ?>">
		<?php
		printf(
			'<span class="current-visibility-level">' . bp_get_the_profile_field_visibility_level_label() . '</span>'
		);
		?>
	</p>

<?php endif;
