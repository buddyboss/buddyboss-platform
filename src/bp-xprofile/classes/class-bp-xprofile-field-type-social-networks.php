<?php
/**
 * BuddyPress XProfile Social Networks field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Social Networks xprofile field type.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_XProfile_Field_Type_Social_Networks extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the social networks field type.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddyboss' );
		$this->name     = __( 'Social Networks', 'buddyboss' );

		$this->supports_options = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Social_Networks class.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param BP_XProfile_Field_Type_Social_Networks $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_selectbox_social_networks', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyBoss 1.0.0
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

		bp_the_profile_field_options( array( 'user_id' => $user_id ) );

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
	 * @since BuddyBoss 1.0.0
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {
		$original_option_values = maybe_unserialize( BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] ) );

		$options = $this->field_obj->get_children();
		$html    = '';

		$raw_properties = array();

		if ( empty( $original_option_values ) && ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			$original_option_values = sanitize_text_field( $_POST[ 'field_' . $this->field_obj->id ] );
		}

		$option_values = ( $original_option_values ) ? (array) $original_option_values : array();
		$providers     = bp_xprofile_social_network_provider();
		$field_name    = bp_get_the_profile_field_input_name();
		foreach ( $options as $option ) {

			$social_value = ( isset( $original_option_values ) && isset( $original_option_values[ $option->name ] ) ) ? $original_option_values[ $option->name ] : '';
			$field        = $this->get_edit_field_html_elements(
				array_merge(
					array(
						'type'  => 'text',
						'name'  => $field_name . '[' . $option->name . ']',
						'id'    => $field_name . '[' . $option->name . ']',
						'value' => $social_value,
					),
					$raw_properties
				)
			);

			$key   = bp_social_network_search_key( $option->name, $providers );
			$html .= '<div class="editfield"><legend id="field_' . $option->id . '-1">' . $providers[ $key ]->name . '</legend>
						<input ' . $field . '></div>';
		}

		echo $html;
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyBoss 1.0.0
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
	 * @since BuddyBoss 1.0.0
	 *
	 * @param BP_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BP_XProfile_Field $current_field, $control_type = 'radio' ) {
		$type = array_search( get_class( $this ), bp_xprofile_get_field_types() );
		if ( false === $type ) {
			return;
		}

		$class            = $current_field->type != $type ? 'display: none;' : '';
		$current_type_obj = bp_xprofile_create_field_type( $type );
		?>

		<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
			<h3><?php esc_html_e( 'Please select the social networks to allow. If entered, they will display as icons in the user\'s profile.', 'buddyboss' ); ?></h3>
			<div class="inside" aria-live="polite" aria-atomic="true" aria-relevant="all">
				<p style="display: none;">
					<label for="sort_order_<?php echo esc_attr( $type ); ?>"><?php esc_html_e( 'Sort Order:', 'buddyboss' ); ?></label>
					<select name="sort_order_<?php echo esc_attr( $type ); ?>" id="sort_order_<?php echo esc_attr( $type ); ?>" >
						<option value="custom" <?php selected( 'custom', $current_field->order_by ); ?>><?php esc_html_e( 'Custom', 'buddyboss' ); ?></option>
						<option value="asc"    <?php selected( 'asc', $current_field->order_by ); ?>><?php esc_html_e( 'Ascending', 'buddyboss' ); ?></option>
						<option value="desc"   <?php selected( 'desc', $current_field->order_by ); ?>><?php esc_html_e( 'Descending', 'buddyboss' ); ?></option>
					</select>
				</p>

				<?php

				// Does option have children?
				$options     = $current_field->get_children( true );
				$fresh_setup = false;

				if ( empty( $options ) ) {
					$default_options = apply_filters( 'social_network_default_options', array( 'facebook', 'twitter', 'linkedIn' ) );
					$all_options     = bp_xprofile_social_network_provider();
					$options         = array();
					if ( empty( $default_options ) ) {
						$options = bp_xprofile_social_network_provider();
					} else {
						foreach ( $all_options as $opt ) {
							if ( in_array( $opt->value, $default_options ) ) {
								$options[] = $opt;
							}
						}
					}
					$fresh_setup = true;
				}

				// If no children options exists for this field, check in $_POST
				// for a submitted form (e.g. on the "new field" screen).
				if ( empty( $options ) ) {

					$options = array();
					$i       = 1;

					while ( isset( $_POST[ $type . '_option' ][ $i ] ) ) {

						// Multiselectbox and checkboxes support MULTIPLE default options; all other core types support only ONE.
						if ( $current_type_obj->supports_options && ! $current_type_obj->supports_multiple_defaults && isset( $_POST[ "isDefault_{$type}_option" ][ $i ] ) && (int) $_POST[ "isDefault_{$type}_option" ] === $i ) {
							$is_default_option = true;
						} elseif ( isset( $_POST[ "isDefault_{$type}_option" ][ $i ] ) ) {
							$is_default_option = (bool) $_POST[ "isDefault_{$type}_option" ][ $i ];
						} else {
							$is_default_option = false;
						}

						// Grab the values from $_POST to use as the form's options.
						$options[] = (object) array(
							'id'                => -1,
							'is_default_option' => $is_default_option,
							'name'              => sanitize_text_field( stripslashes( $_POST[ $type . '_option' ][ $i ] ) ),
						);

						++$i;
					}

					// If there are still no children options set, this must be the "new field" screen, so add one new/empty option.
					if ( empty( $options ) ) {
						$options[] = (object) array(
							'id'                => -1,
							'is_default_option' => false,
							'name'              => '',
						);
					}
				}

				// Render the markup for the children options.
				if ( ! empty( $options ) ) {
					$default_name = '';

					for ( $i = 0, $count = count( $options ); $i < $count; ++$i ) :
						$j = $i + 1;

						// Multiselectbox and checkboxes support MULTIPLE default options; all other core types support only ONE.
						if ( $current_type_obj->supports_options && $current_type_obj->supports_multiple_defaults ) {
							$default_name = '[' . $j . ']';
						}

						$class = 'sortable';

						?>

						<div id="<?php echo esc_attr( "{$type}_div{$j}" ); ?>" class="bp-option <?php echo esc_attr( $class ); ?>">
							<span class="bp-option-icon grabber"></span>
							<label for="<?php echo esc_attr( "{$type}_option{$j}" ); ?>" class="screen-reader-text">
												   <?php
													/* translators: accessibility text */
													esc_html_e( 'Add an option', 'buddyboss' );
													?>
								</label>
							<select class="select-social-networks" name="<?php echo esc_attr( "{$type}_option[{$j}]" ); ?>" id="<?php echo esc_attr( "{$type}_option{$j}" ); ?>">
								<?php
								foreach ( bp_xprofile_social_network_provider() as $option ) {
									$compare = ( true === $fresh_setup ) ? $options[ $i ]->value : $options[ $i ]->name;
									?>
									<option class="<?php echo $options[ $i ]->name . ' ' . $option->value; ?>" value="<?php echo esc_attr( $option->value ); ?>" <?php echo ( $compare === $option->value ) ? 'selected' : ''; ?>><?php echo $option->name; ?></option>
									<?php

								}
								?>

							</select>


							<?php if ( 1 !== $j && 1 !== $j ) : ?>
								<div class ="delete-button">
									<a href='javascript:hide("<?php echo esc_attr( "{$type}_div{$j}" ); ?>")' class="delete"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
								</div>
							<?php endif; ?>

						</div>

					<?php endfor; ?>

					<input type="hidden" name="<?php echo esc_attr( "{$type}_option_number" ); ?>" id="<?php echo esc_attr( "{$type}_option_number" ); ?>" value="<?php echo esc_attr( $j + 1 ); ?>" />
				<?php } ?>

				<?php
				if ( $options < bp_xprofile_social_network_provider() ) {
					?>
					<div id="<?php echo esc_attr( "{$type}_more" ); ?>"></div>					<p>
					<a class="social_networks_add_more" href="javascript:add_option('<?php echo esc_js( $type ); ?>')"><?php esc_html_e( 'Add Another Option', 'buddyboss' ); ?></a></p>
																								<?php
				}
				?>

				<?php

				/**
				 * Fires at the end of the new field additional settings area.
				 *
				 * @since BuddyBoss 1.0.0
				 *
				 * @param BP_XProfile_Field $current_field Current field being rendered.
				 */
				do_action( 'bp_xprofile_admin_new_field_additional_settings_social_networks', $current_field )
				?>
			</div>
		</div>

		<?php
	}

	/**
	 * Check if valid.
	 *
	 * @param int $values post id.
	 *
	 * @return bool
	 */
	public function is_valid( $values = '' ) {

		global $wpdb;

		if ( empty( $values ) ) {
			return true;
		}

		$valid = false;
		if ( is_array( $values ) ) {
			foreach ( $values as $value ) {
				if ( '' === $value || filter_var( $value, FILTER_VALIDATE_URL ) ) {
					$valid = true;
				} else {
					return false;
				}
			}
		} else {
			return false;
		}

		if ( true === $valid ) {
			return true;
		}
		return false;

	}

}
