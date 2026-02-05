<?php
/**
 * BuddyBoss Admin Settings - Reactions Callbacks.
 *
 * Sanitize and render callback functions for Reactions feature settings.
 *
 * @package BuddyBoss\Features\Community\Reactions
 * @since BuddyBoss [BBVERSION]
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render custom reactions button field.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @return string
 */
function bb_reactions_render_button_field() {
	$button_settings = bb_reaction_button_options();
	$button_icon     = isset( $button_settings['icon'] ) ? $button_settings['icon'] : 'thumbs-up';
	$button_text     = isset( $button_settings['text'] ) ? trim( $button_settings['text'] ) : __( 'Like', 'buddyboss' );

	ob_start();
	?>
	<div class="bb-reaction-button-card">
		<div class="bb-reaction-button-card__preview">
			<div class="bb-reaction-button-card__icon-wrapper">
				<button type="button" class="bb-reaction-button-card__icon-btn" id="bb-reaction-button-chooser">
					<i class="bb-icon-rf bb-icon-<?php echo esc_attr( $button_icon ); ?>"></i>
				</button>
			</div>
			<div class="bb-reaction-button-card__footer">
				<input
					name="bb_reactions_button[text]"
					id="bb-reaction-button-text"
					type="text"
					maxlength="12"
					value="<?php echo esc_attr( $button_text ); ?>"
					placeholder="<?php esc_attr_e( 'Like', 'buddyboss' ); ?>"
					class="bb-reaction-button-card__text-input"
				/>
				<button type="button" class="bb-reaction-button-card__menu-btn" aria-label="<?php esc_attr_e( 'More options', 'buddyboss' ); ?>">
					<i class="bb-icon-rf bb-icon-ellipsis-h"></i>
				</button>
			</div>
		</div>
		<input
			type="hidden"
			name="bb_reactions_button[icon]"
			id="bb-reaction-button-hidden-field"
			value="<?php echo esc_attr( $button_icon ); ?>"
		/>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Sanitize reaction content types.
 *
 * Handles the toggle_list field type for bb_all_reactions option.
 * Accepts associative array like: { activity: 1, activity_comment: 1, blogs: 0, private_message: 0 }
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_content_types( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$allowed_keys = array( 'activity', 'activity_comment' );
	$sanitized    = array();

	foreach ( $allowed_keys as $key ) {
		$sanitized[ $key ] = isset( $value[ $key ] ) ? (bool) $value[ $key ] : false;
	}

	return $sanitized;
}

/**
 * Sanitize reactions button settings.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param mixed $value The value to sanitize.
 *
 * @return array
 */
function bb_reactions_sanitize_button_settings( $value ) {
	if ( ! is_array( $value ) ) {
		return array();
	}

	$sanitized = array();

	if ( isset( $value['icon'] ) ) {
		$sanitized['icon'] = sanitize_text_field( $value['icon'] );
	}

	if ( isset( $value['text'] ) ) {
		$text              = trim( stripslashes( sanitize_text_field( $value['text'] ) ) );
		$sanitized['text'] = strlen( $text ) > 12 ? substr( $text, 0, 12 ) : $text;
	}

	return $sanitized;
}
