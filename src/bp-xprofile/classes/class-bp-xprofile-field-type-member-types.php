<?php
/**
 * BuddyPress XProfile Member Types field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Member Types xprofile field type.
 *
 * @since BuddyBoss 1.9.0
 */
class BP_XProfile_Field_Type_Member_Types extends BP_XProfile_Field_Type {

	/**
	 * Constructor for the Member Types field type.
	 *
	 * @since BuddyBoss 1.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Multi Fields', 'buddyboss' );
		$this->name     = __( 'Profile Types', 'buddyboss' );

		$this->supports_options = false;

		/**
		 * Fires inside __construct() method for BP_XProfile_Field_Type_Member_Types class.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param BP_XProfile_Field_Type_Member_Types $this Current instance of
		 *                                               the field type select box.
		 */
		do_action( 'bp_xprofile_field_type_select_member_type_post_type', $this );
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
		$user_id = bp_displayed_user_id();

		if ( isset( $raw_properties['user_id'] ) ) {
			$user_id = (int) $raw_properties['user_id'];
			unset( $raw_properties['user_id'] );
		}

		$html = $this->get_edit_field_html_elements( $raw_properties );
		?>

		<legend id="<?php bp_the_profile_field_input_name(); ?>-1">
			<?php bp_the_profile_field_name(); ?>
			<?php bp_the_profile_field_required_label(); ?>
		</legend>

		<?php do_action( bp_get_the_profile_field_errors_action() ); ?>

		<select <?php echo $html; ?>>
			<option value=""><?php _e( 'Select Profile Type', 'buddyboss' ); ?></option>
			<?php bp_the_profile_field_options( "user_id={$user_id}" ); ?>
		</select>

		<?php if ( bp_get_the_profile_field_description() ) : ?>
			<p class="description" id="<?php bp_the_profile_field_input_name(); ?>-3"><?php bp_the_profile_field_description(); ?></p>
		<?php endif; ?>

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
		global $field;
		$post_type_selected = $this->get_selected_post_type( $field->id );
		$post_selected      = BP_XProfile_ProfileData::get_value_byid( $this->field_obj->id, $args['user_id'] );

		$html = '';

		if ( ! empty( $_POST[ 'field_' . $this->field_obj->id ] ) ) {
			$new_post_selected = (int) $_POST[ 'field_' . $this->field_obj->id ];
			$post_selected     = ( $post_selected != $new_post_selected ) ? $new_post_selected : $post_selected;
		}
		// Get posts of custom post type selected.
		$posts = new \WP_Query( array(
			'posts_per_page' => - 1,
			'post_type'      => bp_get_member_type_post_type(),
			'orderby'        => 'title',
			'order'          => 'ASC'
		) );
		if ( $posts ) {
			foreach ( $posts->posts as $post ) {
				$html .= sprintf( '<option value="%s"%s>%s</option>',
					$post->ID,
					( $post_selected == $post->ID ) ? ' selected="selected"' : '',
					$post->post_title );
			}
		}


		echo apply_filters( 'bp_get_the_profile_field_member_type_post_type', $html, $args['type'], $post_type_selected, $this->field_obj->id );
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
	 * @param mixed $field_value
	 * @param string $field_id
	 *
	 * @return string
	 */
	public static function display_filter( $field_value, $field_id = '' ) {

		$post_id = absint( $field_value );

		if ( empty( $field_value ) || ! get_post( $post_id ) ) {
			return '';
		}

		return sprintf( '<a href="%1$s">%2$s</a>', esc_url( get_permalink( $post_id ) ), get_the_title( $post_id ) );
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
}
