<?php
/**
 * BuddyPress XProfile Gender field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Gender xprofile field type.
 *
 * @since BuddyBoss 1.0.0
 */
class BP_XProfile_Field_Type_Gender extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the gender field type.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddyboss' );
		$this->name     = __( 'Gender', 'buddyboss' );

		$this->supports_options = true;

		$this->set_format( '/^.+$/', 'replace' );

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Gender class.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param BP_XProfile_Field_Type_Gender $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_selectbox_gender', $this );
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
	 * @since BuddyBoss 1.0.0
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

		if ( isset( $this->field_obj->id) && ! empty( $this->field_obj->id ) ) {
			$order = bp_xprofile_get_meta( $this->field_obj->id, 'field', 'gender-option-order' );
		} else {
			$order = array();
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

			if ( ! empty( $order ) ) {
				$key = $order[ $k ];

				if ( 'male' === $key ) {
					$option_value = 'his_' . $options[ $k ]->name;
				} elseif ( 'female' === $key ) {
					$option_value = 'her_' . $options[ $k ]->name;
				} else {
					$option_value = 'their_' . $options[ $k ]->name;
				}
			} else {
				if ( '1' === $options[ $k ]->option_order ) {
					$option_value = 'his_' . $options[ $k ]->name;
				} elseif ( '2' === $options[ $k ]->option_order ) {
					$option_value = 'her_' . $options[ $k ]->name;
				} else {
					$option_value = 'their_' . $options[ $k ]->name;
				}
			}

			// Run the allowed option name through the before_save filter, so
			// we'll be sure to get a match.
			$allowed_options = xprofile_sanitize_data_value_before_save( $option_value, false, false );

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
			 * @since BuddyBoss 1.0.0
			 *
			 * @param string $value    Option tag for current value being rendered.
			 * @param object $value    Current option being rendered for.
			 * @param int    $id       ID of the field object being rendered.
			 * @param string $selected Current selected value.
			 * @param string $k        Current index in the foreach loop.
			 */
			$html .= apply_filters( 'bp_get_the_profile_field_options_select_gender', '<option' . $selected . ' value="' . esc_attr( stripslashes( $option_value ) ) . '">' . esc_html( stripslashes( $options[ $k ]->name ) ) . '</option>', $options[ $k ], $this->field_obj->id, $selected, $k );
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
			<h3><?php esc_html_e( 'Male and Female use "his" and "her" pronouns in the activity feed. Other options use "their".', 'buddyboss' ); ?></h3>
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
				$options = $current_field->get_children( true );

				// Set position of Gender fields option.
				if ( isset( $_POST['gender-option-order'] ) && ! empty( $_POST['gender-option-order'] ) ) {
					bp_xprofile_update_field_meta( $current_field->id, 'gender-option-order', $_POST['gender-option-order'] );
				}

				if ( isset( $current_field->id ) && ! empty( $current_field->id ) ) {
					$order = bp_xprofile_get_meta( $current_field->id, 'field', 'gender-option-order' );
				} else {
					$order = array();
				}

				if ( empty( $options ) ) {
					$options   = array();
					$options[] = (object) array(
						'id'                => 1,
						'is_default_option' => false,
						'name'              => __( 'Male', 'buddyboss' ),
						'key'               => 'male',
					);
					$options[] = (object) array(
						'id'                => 2,
						'is_default_option' => false,
						'name'              => __( 'Female', 'buddyboss' ),
						'key'               => 'female',
					);
					$options[] = (object) array(
						'id'                => 3,
						'is_default_option' => false,
						'name'              => __( 'Prefer Not to Answer', 'buddyboss' ),
						'key'               => 'other',
					);
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
							<?php
							if ( isset( $options[ $i ]->key ) ) {
								$key = $options[ $i ]->key;
							} else if ( ! empty( $order ) ) {
								if ( ! empty( $order[ $i ] ) ) {
									$key = $order[ $i ];
								} else {
									$key = sanitize_key( $options[ $i ]->name );
								}
							} else {
								if ( 1 === $j ) {
									$key = sanitize_key( 'male' );
								} elseif ( 2 === $j ) {
									$key = sanitize_key( 'female' );
								} elseif ( 3 === $j ) {
									$key = sanitize_key( 'other' );
								} else {
									$key = sanitize_key( $options[ $i ]->name );
								}
							}

							?>
							<input type="hidden" name="<?php echo esc_attr( "{$type}-option-order[]" ); ?>" value="<?php echo $key; ?>" />
							<input type="text" name="<?php echo esc_attr( "{$type}_option[{$j}_{$key}]" ); ?>" id="<?php echo esc_attr( "{$type}_option{$j}" ); ?>" value="<?php echo esc_attr( stripslashes( $options[ $i ]->name ) ); ?>" />
							<label for="<?php echo esc_attr( "{$type}_option{$default_name}" ); ?>">
								<?php
								if ( 'male' === $key ) {
									_e( 'Male', 'buddyboss' );
								} elseif ( 'female' === $key ) {
									_e( 'Female', 'buddyboss' );
								} else {
									_e( 'Other', 'buddyboss' );
								}
								?>
							</label>

							<?php if ( ! in_array( $key, array( 'male', 'female' ) ) ) : ?>
								<div class ="delete-button">
									<a href='javascript:remove_div("<?php echo esc_attr( "{$type}_div{$j}" ); ?>")' class="delete"><?php esc_html_e( 'Delete', 'buddyboss' ); ?></a>
								</div>
							<?php endif; ?>

						</div>

					<?php endfor; ?>

					<input type="hidden" name="<?php echo esc_attr( "{$type}_option_number" ); ?>" id="<?php echo esc_attr( "{$type}_option_number" ); ?>" value="<?php echo esc_attr( $j + 1 ); ?>" />
				<?php } ?>

				<div id="<?php echo esc_attr( "{$type}_more" ); ?>"></div>
				<p><a href="javascript:add_option('<?php echo esc_js( $type ); ?>')"><?php esc_html_e( 'Add Another Option', 'buddyboss' ); ?></a></p>

				<?php

				/**
				 * Fires at the end of the new field additional settings area.
				 *
				 * @since BuddyBoss 1.0.0
				 *
				 * @param BP_XProfile_Field $current_field Current field being rendered.
				 */
				do_action( 'bp_xprofile_admin_new_field_additional_settings_gender', $current_field )
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
		static $get_parent_id_of_gender_field = null;
		static $count_cache = array();
		global $wpdb;

		if ( empty( $values ) ) {
			return true;
		}

		$split_value = explode( '_', $values, 2 );
		if ( 2 === count( $split_value ) ) {
			if ( '' !== $split_value[1] && '' !== $split_value[0] ) {
				$table_name = bp_core_get_table_prefix() . 'bp_xprofile_fields';

				if ( null === $get_parent_id_of_gender_field ) {
					$get_parent_id_of_gender_field = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table_name} WHERE type = %s AND parent_id = %d ", 'gender', 0 ) );
				}

				$count_cache_key = 'bb_count_cache_' . $split_value[1] . $get_parent_id_of_gender_field;
				if ( ! isset( $count_cache[ $count_cache_key ] ) ) {
					$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM {$table_name} WHERE type = %s AND name = %s AND parent_id = %d ", 'option', $split_value[1], $get_parent_id_of_gender_field ) );

					$count_cache[ $count_cache_key ] = $count;
				} else {
					$count = $count_cache[ $count_cache_key ];
				}

				if ( '1' === $count ) {
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}
