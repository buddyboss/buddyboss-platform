<?php
/**
 * ReadyLaunch - Member Profile Visibility template.
 *
 * This template handles the profile field visibility settings for member profiles.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( empty( $GLOBALS['profile_template'] ) ) {
	return;
}

$get_profile_field_id = bboss_get_the_profile_field_id();
if ( bboss_current_user_can( 'bp_xprofile_change_field_visibility' ) ) : ?>

	<div class="bb-rl-field-visibility-block">
		<p class="field-visibility-settings-toggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php echo esc_attr( $get_profile_field_id ); ?>">
			<span class="field-visibility-label"><?php esc_html_e( 'Visibility:', 'buddyboss' ); ?></span>
			<span class="field-visibility-toggle-action">
				<?php
				printf(
					/* translators: field visibility level, e.g. "public". */
					'<span class="current-visibility-level">' . esc_html( bboss_get_the_profile_field_visibility_level_label() ) . '</span>'
				);
				?>
				<i class="bb-icons-rl-caret-down"></i>
			</span>
		</p>

		<div class="field-visibility-settings" id="field-visibility-settings-<?php echo esc_attr( $get_profile_field_id ); ?>">
			<fieldset>
				<?php bboss_profile_visibility_radio_buttons(); ?>
			</fieldset>
		</div>
	</div>

<?php else : ?>

	<p class="field-visibility-settings-notoggle field-visibility-settings-header" id="field-visibility-settings-toggle-<?php echo esc_attr( $get_profile_field_id ); ?>">
		<?php
		printf(
			'<span class="current-visibility-level">' . esc_html( bboss_get_the_profile_field_visibility_level_label() ) . '</span>'
		);
		?>
	</p>

	<?php
endif;
