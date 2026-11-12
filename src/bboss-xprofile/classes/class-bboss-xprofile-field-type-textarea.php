<?php
/**
 * BuddyPress XProfile Textarea Field Classes.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Textarea xprofile field type.
 *
 * @since BuddyPress 2.0.0
 */
class BBoss_XProfile_Field_Type_Textarea extends BBoss_XProfile_Field_Type {

	/**
	 * Constructor for the textarea field type.
	 *
	 * @since BuddyPress 2.0.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category          = __( 'Single Fields', 'buddyboss' );
		$this->name              = __( 'Paragraph Text', 'buddyboss' );
		$this->supports_richtext = true;

		$this->set_format( '/^.*$/m', 'replace' );

		/**
		 * Fires inside __construct() method for BBoss_XProfile_Field_Type_Textarea class.
		 *
		 * @since BuddyPress 2.0.0
		 *
		 * @param BBoss_XProfile_Field_Type_Textarea $this Current instance of
		 *                                              the field type textarea.
		 */
		do_action( 'bp_xprofile_field_type_textarea', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Must be used inside the {@link bboss_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of
	 *                              {@link http://dev.w3.org/html5/markup/textarea.html permitted attributes}
	 *                              that you want to add.
	 */
	public function edit_field_html( array $raw_properties = array() ) {

		// User_id is a special optional parameter that certain other fields
		// types pass to {@link bp_the_profile_field_options()}.
		if ( isset( $raw_properties['user_id'] ) ) {
			unset( $raw_properties['user_id'] );
		}

		$richtext_enabled = bboss_xprofile_is_richtext_enabled_for_field(); ?>

		<legend id="<?php bboss_the_profile_field_input_name(); ?>-1">
			<?php bboss_the_profile_field_name(); ?>
			<?php if ( bboss_is_register_page() ) : ?>
				<?php bboss_the_profile_field_optional_label(); ?>
			<?php else : ?>
				<?php bboss_the_profile_field_required_label(); ?>
			<?php endif; ?>
		</legend>

		<?php if ( bboss_get_the_profile_field_description() ) : ?>
			<p class="description" id="<?php bboss_the_profile_field_input_name(); ?>-3"><?php bboss_the_profile_field_description(); ?></p>
			<?php
		endif;

		/** This action is documented in bboss-xprofile/bp-xprofile-classes */
		do_action( bboss_get_the_profile_field_errors_action() );

		if ( ! $richtext_enabled ) {
			$r = bboss_parse_args(
				$raw_properties,
				array(
					'cols' => 40,
					'rows' => 5,
				)
			);

			?>

			<textarea <?php echo $this->get_edit_field_html_elements( $r ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_edit_field_html_elements() returns attribute markup escaped via bp_get_form_field_attributes(). ?> aria-labelledby="<?php bboss_the_profile_field_input_name(); ?>-1" aria-describedby="<?php bboss_the_profile_field_input_name(); ?>-3"><?php bboss_the_profile_field_edit_value(); ?></textarea>

			<?php

		} else {

			// Define an array of toolbar options.
			$toolbar_buttons = array(
				'bold',
				'italic',
				'underline',
				'blockquote',
				'strikethrough',
				'bullist',
				'numlist',
				'undo',
				'redo',
				'link',
				'fullscreen',
			);

			if ( current_user_can( 'unfiltered_html' ) ) {
				$align_buttons = array( 'alignleft', 'aligncenter', 'alignright' );

				$position = array_search( 'numlist', $toolbar_buttons, true );
				if ( false !== $position ) {
					array_splice( $toolbar_buttons, $position + 1, 0, $align_buttons );
				} else {
					$toolbar_buttons = array_merge( $toolbar_buttons, $align_buttons );
				}
			}

			/**
			 * Filters the arguments passed to `wp_editor()` in richtext xprofile fields.
			 *
			 * @since BuddyPress 2.4.0
			 * @since BuddyBoss 2.6.50
			 * Remove align button for non-admin members.
			 *
			 * @param array $args {
			 *     Array of optional arguments. See `wp_editor()`.
			 *     @type bool $teeny         Whether to use the teeny version of TinyMCE. Default true.
			 *     @type bool $media_buttons Whether to show media buttons. Default false.
			 *     @type bool $quicktags     Whether to show the quicktags buttons. Default true.
			 *     @type int  $textarea_rows Number of rows to display in the editor. Defaults to 1 in the
			 *                               'admin' context, and 10 in the 'edit' context.
			 *     @type array $tinymce {
			 *       Array of TinyMCE arguments.
			 *          @type string $toolbar1 Comma-separated list of buttons to display in the first row of the toolbar.
			 *      }
			 * }
			 * @param string $context The display context. 'edit' when the markup is intended for the
			 *                        profile edit screen, 'admin' when intended for the Profile Fields
			 *                        Dashboard panel.
			 */
			$editor_args = apply_filters(
				'bp_xprofile_field_type_textarea_editor_args',
				array(
					'teeny'         => true,
					'media_buttons' => false,
					'quicktags'     => true,
					'textarea_rows' => 10,
					'tinymce'       => array(
						'toolbar1' => implode( ',', $toolbar_buttons ),
					),
				),
				'edit'
			);

			wp_editor(
				bboss_get_the_profile_field_edit_value(),
				bboss_get_the_profile_field_input_name(),
				$editor_args
			);
		}

	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * Must be used inside the {@link bboss_profile_fields()} template loop.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes that you want to add.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		$richtext_enabled = bboss_xprofile_is_richtext_enabled_for_field();

		if ( ! $richtext_enabled ) {

			$r = bboss_parse_args(
				$raw_properties,
				array(
					'cols' => 40,
					'rows' => 5,
				)
			);
			?>

			<textarea <?php echo $this->get_edit_field_html_elements( $r ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_edit_field_html_elements() returns attribute markup escaped via bp_get_form_field_attributes(). ?>></textarea>

			<?php
		} else {

			/** This filter is documented in bboss-xprofile/classes/class-bboss-xprofile-field-type-textarea.php */
			$editor_args = apply_filters(
				'bp_xprofile_field_type_textarea_editor_args',
				array(
					'teeny'         => true,
					'media_buttons' => false,
					'quicktags'     => true,
					'textarea_rows' => 1,
				),
				'admin'
			);

			wp_editor(
				'',
				'xprofile_textarea_' . bboss_get_the_profile_field_id(),
				$editor_args
			);
		}
	}

	/**
	 * This method usually outputs HTML for this field type's children options on the wp-admin Profile Fields
	 * "Add Field" and "Edit Field" screens, but for this field type, we don't want it, so it's stubbed out.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param BBoss_XProfile_Field $current_field The current profile field on the add/edit screen.
	 * @param string            $control_type  Optional. HTML input type used to render the current
	 *                                         field's child options.
	 */
	public function admin_new_field_html( BBoss_XProfile_Field $current_field, $control_type = '' ) {}
}
