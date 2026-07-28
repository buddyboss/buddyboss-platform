<?php
/**
 * BuddyBoss XProfile Biography Field Class.
 *
 * @package BuddyBoss\XProfile\Classes
 * @since BuddyBoss [BBVERSION]
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
 * @since BuddyBoss [BBVERSION]
 */
class BB_XProfile_Field_Type_Biography extends BP_XProfile_Field_Type_Textarea {

	/**
	 * Constructor for the biography field type.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		parent::__construct();

		$this->category = __( 'Single Fields', 'buddyboss' );
		$this->name     = __( 'Bio', 'buddyboss' );

		// WordPress renders Biographical Info as a plain textarea, so match it.
		$this->supports_richtext = false;

		/**
		 * Fires inside __construct() method for BB_XProfile_Field_Type_Biography class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param BB_XProfile_Field_Type_Biography $this Current instance of the
		 *                                               field type biography.
		 */
		do_action( 'bb_xprofile_field_type_biography', $this );
	}
}
