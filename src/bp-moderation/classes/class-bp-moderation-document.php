<?php
/**
 * BuddyBoss Moderation Document Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Document.
 *
 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		// Check Component is disabled.
		if ( ! bp_is_active( 'document' ) ) {
			return;
		}

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin or Reporting setting disabled
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() || ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Remove hidden/blocked users content.
		add_filter( 'bp_suspend_document_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );

		// button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );
	}

	/**
	 * Get permalink.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $document_id document id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $document_id ) {
		return '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
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
	 * @since BuddyBoss 2.0.0
	 *
	 * @param array $content_types Supported Contents types.
	 *
	 * @return mixed
	 */
	public function add_content_types( $content_types ) {
		$content_types[ self::$moderation_type ] = __( 'Document', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Remove hidden/blocked user's documents
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   documents Where sql.
	 * @param object $suspend suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias               = $suspend->alias;
		$where['moderation_where'] = $this->exclude_where_query();

		return $where;
	}

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 2.0.0
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

}
