<?php
/**
 * BuddyPress XProfile Slect Box Field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Selectbox xprofile field type.
 *
 * @since BuddyPress 2.0.0
 */
class BP_XProfile_Field_Type_Selectbox extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the selectbox field type.
	 *
	 * @since BuddyPress 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddyboss' );
		$this->name     = __( 'Drop Down', 'buddyboss' );

		$this->supports_options = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Selectbox class.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param BP_XProfile_Field_Type_Selectbox $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_selectbox', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/select.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that we pass to
		// {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		} else {
			$user_id = bp_displayed_user_id();
		} ?>

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

		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?> aria-labelledby="<?php bp_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bp_the_profile_field_input_name(); ?>-3">
			<?php bp_the_profile_field_options( array( 'user_id' => $user_id ) ); ?>
		</select>

		<?php
	}

	/**
	 * Output the edit field options HTML for this field type.
	 *
	 * BuddyPress considers a field's "options" to be, for example, the items in a selectbox.
	 * These are stored separately in the database, and their templating is handled separately.
	 *
	 * This templating is separate from {@link BP_XProfile_Field_Type::edit_field_html()} because
	 * it's also used in the wp-admin screens when creating new fields, and for backwards compatibility.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {
		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		$options = $this->field_obj->get_children();
		$html    = '<option value="">' . /* translators: no option picked in select box */ esc_html__( '----', 'buddyboss' ) . '</option>';

		if ( empty( $original_option_values ) && ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			$original_option_values = sanitize_text_field( $_POST[ 'field_' . $this->field_obj->id ] );
		}

		$option_values = ( $original_option_values ) ? (array) $original_option_values : array();
		for ( $k = 0, $count = count( $options ); $k < $count; ++$k ) {
			$selected = '';

			// Check for updated posted values, but errors preventing them from
			// being saved first time.
			foreach ( $option_values as $i => $option_value ) {
				if ( isset( $_POST[ 'field_' . $this->field_obj->id ] ) && $_POST[ 'field_' . $this->field_obj->id ] != $option_value ) {
					if ( ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
						$option_values[ $i ] = sanitize_text_field( $_POST[ 'field_' . $this->field_obj->id ] );
					}
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match.
			$allowed_options = xprofile_sanitize_data_value_before_save( $options[ $k ]->name, false, false );

			// First, check to see whether the user-entered value matches.
			if ( in_array( $allowed_options, $option_values ) ) {
				$selected = ' selected="selected"';
			}

			// Then, if the user has not provided a value, check for defaults.
			if ( ! is_array( $original_option_values ) && empty( $option_values ) && $options[ $k ]->is_default_option ) {
				$selected = ' selected="selected"';
			}

			/**
			 * Filters the HTML output for options in a select input.
			 *
			 * @since BuddyPress 1.1.0
			 *
			 * @param string $value    Option tag for current value being rendered.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_select', '<option' . $selected . ' value="' . esc_attr( stripslashes( $options[ $k ]->name ) ) . '">' . esc_html( stripslashes( $options[ $k ]->name ) ) . '</option>', $options[ $k ], $this->field_obj->id, $selected, $k );
		}

		/**
		 * Filters the HTML output for options in a select input.
		 *
		 * @since 2.7.40
		 *
		 * @param string $html      HTML output for options in a select input.
		 * @param object $field_obj Current field object.
		 */
		$html = apply_filters( 'bb_get_the_profile_field_options_select_html', $html, $this->field_obj );

		echo $html;
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		?>

		<label for="<?php bp_the_profile_field_input_name(); ?>" class="screen-reader-text">
															 <?php
																/* translators: accessibility text */
																esc_html_e( 'Select', 'buddyboss' );
																?>
		</label>
		<select <?php echo $this->get_edit_field_html_elements( $raw_properties ); ?>>
			<?php bp_the_profile_field_options(); ?>
		</select>

		<?php
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = '' ) {
		parent::admin_new_field_html( $current_field, 'radio' );
	}
}
