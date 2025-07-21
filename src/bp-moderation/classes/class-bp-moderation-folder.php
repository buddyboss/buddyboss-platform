<?php
/**
 * BuddyBoss Moderation Document Folder Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Document Folder.
 *
 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );
	}

	/**
	 * Get permalink.
	 *
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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
	 * @since BuddyBoss 1.5.6
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

		if ( isset( $where['moderation_where'] ) && ! empty( $where['moderation_where'] ) ) {
			$where['moderation_where'] .= ' AND ';
		}

		// Check if groups component is active before applying grouponly bypass.
		$grouponly_bypass = '';
		if ( bp_is_active( 'groups' ) ) {
			$grouponly_bypass = ' OR f.privacy = "grouponly"';
		}

		$where['moderation_where'] .= '( f.user_id NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) ' . $grouponly_bypass . ' )';

		return $where;
	}

	/**
	 * Filter to check the document folder is valid or not.
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

		$document_folder = new BP_Document_Folder( (int) $item_id );

		if ( empty( $document_folder ) || empty( $document_folder->id ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Prepare Where sql for exclude Blocked items.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $blocked_user_query If true then blocked user query will fire.
	 *
	 * @return string|void
	 */
	protected function exclude_where_query( $blocked_user_query = true ) {
		$where = '';

		$where .= "( {$this->alias}.hide_parent = 0 OR {$this->alias}.hide_parent IS NULL ) AND
		( {$this->alias}.hide_sitewide = 0 OR {$this->alias}.hide_sitewide IS NULL )";

		if ( true === $blocked_user_query ) {
			$blocked_query = $this->blocked_user_query();
			if ( ! empty( $blocked_query ) ) {
				if ( ! empty( $where ) ) {
					$where .= ' AND ';
				}

				// Check if groups component is active before applying grouponly bypass.
				$grouponly_bypass = '';
				if ( bp_is_active( 'groups' ) ) {
					$grouponly_bypass = " OR f.privacy = 'grouponly'";
				}
				$where .= "( ( {$this->alias}.id NOT IN ( $blocked_query ) {$grouponly_bypass} ) OR {$this->alias}.id IS NULL )";
			}
		}

		return $where;
	}
}
