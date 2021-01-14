<?php
/**
 * BuddyBoss Moderation Document Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Document.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Document extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'document';

	/**
	 * BP_Moderation_Document constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin or Reporting setting disabled
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_document_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );
	}

	/**
	 * Get permalink.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $document_id document id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $document_id ) {
		$document = new BP_Document( $document_id );
		return bp_document_download_link( $document->attachment_id, $document_id );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $document_id Document id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $document_id ) {
		$document = new BP_Document( $document_id );

		return ( ! empty( $document->user_id ) ) ? $document->user_id : 0;
	}

	/**
	 * Add Moderation content type.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Documents', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Remove hidden/blocked user's documents
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   documents Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		return $where;
	}

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_sub_items( $item_id ) {
		$document = new BP_Document( $item_id );

		if ( empty( $document->id ) ) {
			return array();
		}

		$sub_items = array();
		if ( bp_is_active( 'activity' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity::$moderation_type ) && ! empty( $document->activity_id ) ) {
			$sub_items['id']   = $document->activity_id;
			$sub_items['type'] = BP_Moderation_Activity::$moderation_type;
		}

		return $sub_items;
	}

	/**
	 * Filter to check the document is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param bool   $retval  Check item is valid or not.
	 * @param string $item_id item id.
	 *
	 * @return bool
	 */
	public function validate_single_item( $retval, $item_id ) {
		if ( empty( $item_id ) ) {
			return $retval;
		}

		$document = new BP_Document( (int) $item_id );

		if ( empty( $document ) || empty( $document->id ) ) {
			return false;
		}

		return $retval;
	}

}
