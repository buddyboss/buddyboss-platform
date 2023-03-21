<?php
/**
 * BuddyPress XProfile Telephone Field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Telephone number xprofile field type.
 *
 * @since BuddyPress 3.0.0
 */
class BP_XProfile_Field_Type_Telephone extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the telephone number field type.
	 *
	 * @since BuddyPress 3.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Single Fields', 'buddyboss' );
		$this->name     = __( 'Phone', 'buddyboss' );

		$this->set_format( '/^.*$/', 'replace' );

		$this->do_settings_section = true;

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Telephone class.
		 *
		 * @since BuddyPress 3.0.0
		 *
		 * @param BP_XProfile_Field_Type_Telephone $this Current instance of the field type.
		 */
		do_action( 'bp_xprofile_field_type_telephone', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/input.text.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {
		/*
		 * User_id is a special optional parameter that certain other fields
		 * types pass to {@link bp_the_profile_field_options()}.
		 */
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$selected_format = bp_xprofile_get_meta( bp_get_the_profile_field_id(), 'field', 'phone_format', true );
		if ( empty( $selected_format ) ) {
			$selected_format = 'international';
		}

		$all_formats             = $this->get_phone_formats();
		$selected_format_details = isset( $all_formats[ $selected_format ] ) ? $all_formats[ $selected_format ] : array();

		$placeholder = isset( $selected_format_details['placeholder'] ) && ! empty( $selected_format_details['placeholder'] ) ? $selected_format_details['placeholder'] : '';
		$mask        = isset( $selected_format_details['mask'] ) && ! empty( $selected_format_details['mask'] ) ? $selected_format_details['mask'] : '';

		$r = bp_parse_args(
			$raw_properties,
			array(
				'type'        => 'tel',
				'value'       => wp_strip_all_tags( html_entity_decode( bp_get_the_profile_field_edit_value(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) ),
				'placeholder' => $placeholder,
			)
		); ?>

		<legend id="<?php bp_the_profile_field_input_name(); ?>-1">
			<?php bp_the_profile_field_name(); ?>
			<?php if ( bp_is_register_page() ) : ?>
				<?php bp_the_profile_field_optional_label(); ?>
			<?php else : ?>
				<?php bp_the_profile_field_required_label(); ?>
			<?php endif; ?>
		</legend>
		
		<?php if ( bp_get_the_profile_field_description() ) : ?>
			<p class="description" id="<?php bp_the_profile_field_input_name(); ?>-3"><?php bp_the_profile_field_description(); ?></p>
		<?php endif; ?>

		<?php

		/** This action is documented in bp-xprofile/bp-xprofile-classes */
		do_action( bp_get_the_profile_field_errors_action() );
		?>

		<input <?php echo $this->get_edit_field_html_elements( $r ); ?> aria-labelledby="<?php bp_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bp_the_profile_field_input_name(); ?>-3">

		<span class="input_mask_details" data-field_id="<?php echo esc_attr( bp_get_the_profile_field_input_name() ); ?>" data-val="<?php echo esc_attr( $mask ); ?>"></span>
			
		<?php

	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$r = bp_parse_args(
			$raw_properties,
			array(
				'type' => 'tel',
			)
		);
		?>

		<label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text">
															 <?php
																/* translators: accessibility text */
																esc_html_e( 'Phone', 'buddyboss' );
																?>
		</label>
		<input <?php echo $this->get_edit_field_html_elements( $r ); ?>>

		<?php
		global $field;
		$this->input_mask_script( $field );
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the
	 *                                         current field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );

		if ( false === $type ) {
			return;
		}

		$class = $current_field->type != $type ? 'display: none;' : '';

		$settings = $this->get_field_settings( $current_field );
		?>
		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box <?php echo $current_field->type; ?>" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
			<table class="form-table bp-date-options">
				<tr>
					<th scope="row">
						<label for="phone-format-elapsed"><?php _e( 'Phone Format', 'buddyboss' ); ?></label>
					</th>

					<td>
						<select name="field-settings[phone_format]" id="phone-format" >
							<?php
							foreach ( $this->get_phone_formats() as $format => $details ) {
								printf(
									"<option value='%s' %s >%s</option>",
									$format,
									selected( $settings['phone_format'], $format, false ),
									$details['label']
								);
							}
							?>
						</select>
					</td>
				</tr>
			</table>
		</div>
		<?php
	}

	/**
	 * Returns an array of phone formats
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @return array
	 */
	public function get_phone_formats() {
		$phone_formats = array(
			'standard'      => array(
				'label'       => '(###) ###-####',
				'mask'        => '(999) 999-9999',
				'placeholder' => '(###) ###-####',
			),
			'international' => array(
				'label'       => __( 'International', 'buddyboss' ),
				'mask'        => false,
				'placeholder' => '',
			),
		);

		return $phone_formats;
	}

	/**
	 * Get all settings for field.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param BP_XProfile_Field $field
	 * @return array
	 */
	public function get_field_settings( BP_XProfile_Field $field ) {
		$defaults = array(
			'phone_format' => 'international',
		);

		$settings = array();
		foreach ( $defaults as $key => $value ) {
			$saved = bp_xprofile_get_meta( $field->id, 'field', $key, true );

			if ( $saved ) {
				$settings[ $key ] = $saved;
			} else {
				$settings[ $key ] = $value;
			}
		}

		return $settings;
	}

	/**
	 * Save settings from the field edit screen in the Dashboard.
	 *
	 * @since BuddyBoss 1.0.0
	 *
	 * @param int   $field_id ID of the field.
	 * @param array $settings Array of settings.
	 * @return bool True on success.
	 */
	public function admin_save_settings( $field_id, $settings ) {
		$saved_settings = array();
		foreach ( $settings as $setting => $setting_val ) {
			switch ( $setting ) {
				case 'phone_format':
					$allowed_formats = $this->get_phone_formats();

					if ( ! isset( $allowed_formats[ $setting_val ] ) ) {
						$setting_val = 'international';// default
					}

					$saved_settings[ $setting ] = $setting_val;
					break;

				default:
					if ( isset( $settings[ $setting ] ) ) {
						$saved_settings[ $setting ] = $settings[ $setting ];
					}
					break;
			}
		}

		foreach ( $saved_settings as $setting_key => $setting_value ) {
			bp_xprofile_update_meta( $field_id, 'field', $setting_key, $setting_value );
		}

		return true;
	}

	/**
	 * Prints the jquery input mask script
	 *
	 * @since BuddyBoss 1.0.0
	 * @param BP_XProfile_Field $current_field
	 * @return void
	 */
	public function input_mask_script( BP_XProfile_Field $current_field ) {
		$selected_format = bp_xprofile_get_meta( $current_field->id, 'field', 'phone_format', true );
		if ( empty( $selected_format ) ) {
			$selected_format = 'international';
		}

		$all_formats             = $this->get_phone_formats();
		$selected_format_details = isset( $all_formats[ $selected_format ] ) ? $all_formats[ $selected_format ] : array();

		if ( isset( $selected_format_details['mask'] ) && ! empty( $selected_format_details['mask'] ) ) {
			?>
			<script type='text/javascript'>
				jQuery(document).ready(function($){
					jQuery('#field_<?php echo $current_field->id; ?>').mask('<?php echo $selected_format_details['mask']; ?>').bind('keypress', function(e){if(e.which == 13){jQuery(this).blur();} } );
				});
			</script>
			<?php
			echo ob_get_clean();
		}
	}

	/**
	 * Format URL values for display.
	 *
	 * @since BuddyPress 3.0.0
	 *
	 * @param string     $field_value The URL value, as saved in the database.
	 * @param string|int $field_id    Optional. ID of the field.
	 *
	 * @return string URL converted to a link.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		$url   = wp_strip_all_tags( html_entity_decode( $field_value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) );
		$parts = parse_url( $url );

		// Add the tel:// protocol to the field value.
		if ( isset( $parts['scheme'] ) ) {
			if ( strtolower( $parts['scheme'] ) !== 'tel' ) {
				$scheme = preg_quote( $parts['scheme'], '#' );
				$url    = preg_replace( '#^' . $scheme . '#i', 'tel', $url );
			}

			$url_text = preg_replace( '#^tel://#i', '', $url );

		} else {
			$url_text = $url;
			$url      = 'tel://' . $url;
		}

		return sprintf(
			'<a href="%1$s" rel="nofollow">%2$s</a>',
			wp_strip_all_tags( esc_url( $url, array( 'tel' ) ) ),
			esc_html( $url_text )
		);
	}
}
