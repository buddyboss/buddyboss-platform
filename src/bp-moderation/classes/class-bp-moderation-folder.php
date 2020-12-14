<?php
/**
 * BuddyBoss Moderation Document Folder Classes
 *
 * @since   BuddyBoss 2.0.0
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Document Folder.
 *
 * @since BuddyBoss 2.0.0
 */
class BP_Moderation_Folder extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'document_folder';

	/**
	 * BP_Moderation_Folder constructor.
	 *
	 * @since BuddyBoss 2.0.0
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

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
		add_filter( 'bp_suspend_document_folder_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
	}

	/**
	 * Get permalink.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param int $folder_id folder id.
	 *
	 * @return string|void
	 */
	public static function get_permalink( $folder_id ) {
		return '';
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param integer $folder_id Folder id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $folder_id ) {
		return 0;
	}

	/**
	 * Remove hidden/blocked user's folders
	 *
	 * @since BuddyBoss 2.0.0
	 *
	 * @param string $where   folders Where sql.
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
}
