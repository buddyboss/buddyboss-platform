<?php
/**
 * BuddyBoss XProfile Biography Field Class.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss 3.3.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Biography xprofile field type.
 *
 * Behaves like a paragraph field, but its value is kept in two-way sync with the
 * WordPress "Biographical Info" user field (usermeta `description`). Only one
 * field of this type may exist per site, since two fields would otherwise write
 * to the same user meta key.
 *
 * No character limit is enforced: WordPress renders Biographical Info as a plain
 * textarea with no maximum length, so a bio saved from wp-admin must never be
 * rejected when the member later saves their profile.
 *
 * @since BuddyBoss 3.3.0
 */
class BB_XProfile_Field_Type_Biography extends BP_XProfile_Field_Type_Textarea {

	/**
	 * Constructor for the biography field type.
	 *
	 * @since BuddyBoss 3.3.0
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Single Fields', 'buddyboss-platform' );
		$this->name     = __( 'Bio', 'buddyboss-platform' );

		// WordPress renders Biographical Info as a plain textarea, so match it.
		$this->supports_richtext = false;

		/**
		 * Fires inside __construct() method for BB_XProfile_Field_Type_Biography class.
		 *
		 * @since BuddyBoss 3.3.0
		 *
		 * @param BB_XProfile_Field_Type_Biography $this Current instance of the
		 *                                               field type biography.
		 */
		do_action( 'bb_xprofile_field_type_biography', $this );
	}

	/**
	 * Output the edit field HTML for this field type.
	 *
	 * Widens the textarea from the parent's 40 columns to 100. A bio is
	 * long-form prose, and WordPress renders Biographical Info at full width in
	 * wp-admin, so the narrow default reads as a mismatch against the field
	 * members see there.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes.
	 */
	public function edit_field_html( array $raw_properties = array() ) {
		parent::edit_field_html( $this->bb_with_default_cols( $raw_properties ) );
	}

	/**
	 * Output HTML for this field type on the wp-admin Profile Fields screen.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param array $raw_properties Optional key/value array of permitted attributes.
	 */
	public function admin_field_html( array $raw_properties = array() ) {
		parent::admin_field_html( $this->bb_with_default_cols( $raw_properties ) );
	}

	/**
	 * Apply this field type's default column count.
	 *
	 * Set on the properties passed up to the parent rather than overriding the
	 * markup, so an explicit `cols` from a caller or a template override still
	 * wins.
	 *
	 * @since BuddyBoss 3.3.0
	 *
	 * @param array $raw_properties Attributes passed in by the caller.
	 *
	 * @return array Attributes with a default column count applied.
	 */
	protected function bb_with_default_cols( array $raw_properties = array() ) {
		if ( ! isset( $raw_properties['cols'] ) ) {
			$raw_properties['cols'] = 100;
		}

		return $raw_properties;
	}
}
