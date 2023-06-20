<?php
/**
 * BuddyPress XProfile Member Types field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.1.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Member Types xprofile field type.
 *
 * @since BuddyBoss 1.1.3
 */
class BP_XProfile_Field_Type_Member_Types extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the Member Types field type.
	 *
	 * @since BuddyBoss 1.1.3
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddyboss' );
		$this->name     = __( 'Profile Type', 'buddyboss' );

		$this->supports_options = false;

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Member_Types class.
		 *
		 * @since BuddyBoss 1.1.3
		 *
		 * @param BP_XProfile_Field_Type_Member_Types $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_select_member_type_post_type', $this );
	}

	/**
	 * Output HTML for this field type's children options on the wp-admin Profile Fields "Add Field" and "Edit Field" screens.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyBoss 1.1.3
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

		$active_member_type = bp_get_active_member_types();
		if ( empty( $active_member_type ) ) {
			?>
			<div id="<?php echo esc_attr( $type ); ?>" class="postbox bp-options-box" style="<?php echo esc_attr( $class ); ?> margin-top: 15px;">
				<h3>
					<?php
					printf(
						'%s',
						sprintf(
							__(
								'Please make sure to add some <a href="%s">profile types</a> first.',
								'buddyboss'
							),
							add_query_arg(
								array(
									'post_type' => bp_get_member_type_post_type(),
								),
								admin_url( 'edit.php' )
							)
						)
					);
					?>
				</h3>
			</div>

			<?php
		}
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyBoss 1.1.3
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
		}

		if ( function_exists( 'bp_check_member_type_field_have_options' ) && true === bp_check_member_type_field_have_options() ) {
			?>

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
	 * @since BuddyBoss 1.1.3
	 *
	 * @param array $args Optional. The arguments passed to {@link bp_the_profile_field_options()}.
	 */
	public function edit_field_options_html( array $args = array() ) {
		global $field;
		$post_type_selected = $this->get_selected_post_type( $field->id );
		$post_selected      = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );

		$html = '';

		if ( ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			$new_post_selected = (int) $_POST[ 'field_' . $this->field_obj->id ];
			$post_selected     = ( $post_selected != $new_post_selected ) ? $new_post_selected : $post_selected;
		}

		// Add Profile Type selected if users have previously added profile type in his/her account.
		if ( '' === $post_selected ) {
			$member_type = bp_get_member_type( (int) $args['user_id'] );
			if ( '' !== $member_type ) {
				$post_selected = bp_member_type_post_by_type( $member_type );
			}
		}

		// Get all active member types.
		$bp_active_member_types = bp_get_active_member_types();

		if ( ! empty( $bp_active_member_types ) ) {

			$html .= '<option value="">' . /* translators: no option picked in select box */ esc_html__( '----', 'buddyboss' ) . '</option>';

			foreach ( $bp_active_member_types as $bp_active_member_type ) {
				$enabled = get_post_meta( $bp_active_member_type, '_bp_member_type_enable_profile_field', true );
				$name    = get_post_meta( $bp_active_member_type, '_bp_member_type_label_singular_name', true );
				if ( '' === $enabled || '1' === $enabled || (int)$post_selected === $bp_active_member_type ) {
					$html .= sprintf(
						'<option value="%s" %s>%s</option>',
						$bp_active_member_type,
						( $post_selected == $bp_active_member_type ) ? ' selected="selected"' : '',
						$name
					);
				}
			}
		}

		echo apply_filters( 'bp_get_the_profile_field_member_type_post_type', $html, $args['type'], $post_type_selected, $this->field_obj->id );
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bp_profile_fields()} template loop.
	 *
	 * @since BuddyBoss 1.1.3
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$html = $this->get_edit_field_html_elements( $raw_properties );
		?>
		<select <?php echo $html; ?>>
			<?php bp_the_profile_field_options(); ?>
		</select>
		<?php
	}

	/**
	 * Check if valid.
	 *
	 * @param int $values post id.
	 *
	 * @return bool
	 */
	public function is_valid( $values ) {
		return empty( $values ) || get_post( $values );
	}

	/**
	 * Get the terms content.
	 *
	 * @param int $field_id field id.
	 *
	 * @return string
	 */
	private static function get_selected_post_type( $field_id ) {

		if ( ! $field_id ) {
			return '';
		}

		return bp_xprofile_get_meta( $field_id, 'field', 'selected_post_type', true );
	}

	/**
	 * Format profile type to display name instead of id.
	 *
	 * @since BuddyBoss 1.8.5
	 *
	 * @param string     $field_value The profile type id value, as saved in the database.
	 * @param string|int $field_id    Optional. ID of the field.
	 * @return string profile type name.
	 */
	public static function display_filter( $field_value, $field_id = '' ) {
		if ( empty( $field_value ) || ! is_int( $field_value ) ) {
			return $field_value;
		}

		$member_type_name = get_post_meta( $field_value, '_bp_member_type_label_singular_name', true );

		if ( '' === $member_type_name || false === $member_type_name ) {
			return esc_html__( '---', 'buddyboss' );
		}

		return $member_type_name;
	}
}
