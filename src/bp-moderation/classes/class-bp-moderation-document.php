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
		add_filter( "bp_moderation_{$this->item_type}_button_args", array( $this, 'update_button_args' ), 10, 2 );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );

		// Prepare report button for documents when activity moderation is disabled.
		if ( bp_is_active( 'activity' ) && ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity::$moderation_type ) ) {
			add_filter( 'bp_activity_get_report_link', array( $this, 'update_report_button_args' ), 10, 2 );
		}

		// Prepare report button for documents when activity comment moderation is disabled.
		if ( bp_is_active( 'activity' ) && ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Activity_Comment::$moderation_type ) ) {
			add_filter( 'bp_activity_comment_get_report_link', array( $this, 'update_report_button_args' ), 10, 2 );
		}
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

		if ( isset( $where['moderation_where'] ) && ! empty( $where['moderation_where'] ) ) {
			$where['moderation_where'] .= ' AND ';
		}
		$where['moderation_where'] .= ' ( d.user_id NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) OR ( d.privacy = "comment" OR d.privacy = "forums" ) ) ';
		return $where;
	}

	/**
	 * Function to modify the button args
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param array $args    Button args.
	 * @param int   $item_id Item id.
	 *
	 * @return array
	 */
	public function update_button_args( $args, $item_id ) {
		$document = new BP_Document( $item_id );

		// Remove report button if forum is group forums.
		if ( ! empty( $document->id ) && ! empty( $document->privacy ) && in_array( $document->privacy, array( 'comment', 'forums' ), true ) ) {
			return array();
		}

		return $args;
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

	/**
	 * Function to change report button text.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string
	 */
	public function report_button_text( $button_text, $item_id ) {
		return esc_html__( 'Report Document', 'buddyboss' );
	}

	/**
	 * Function to change report type.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string
	 */
	public function report_content_type( $content_type, $item_id ) {
		return esc_html__( 'Document', 'buddyboss' );
	}

	/**
	 * Function to update activity report button arguments.
	 *
	 * @since BuddyBoss 1.7.7
	 *
	 * @param array $report_button Activity report button.
	 * @param array $args          Arguments.
	 *
	 * @return array|string
	 */
	public function update_report_button_args( $report_button, $args ) {

		$activity = new BP_Activity_Activity( $args['button_attr']['data-bp-content-id'] );

		if ( empty( $activity->id ) ) {
			return $report_button;
		}

		$document_id  = bp_activity_get_meta( $activity->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );

		if (
			(
				! empty( $document_id ) ||
				! empty( $document_ids )
			) &&
			! in_array(
				$activity->type,
				array(
					'bbp_forum_create',
					'bbp_topic_create',
					'bbp_reply_create',
				),
				true
			)
		) {
			$explode_documents = explode( ',', $document_ids );
			if ( ! empty( $document_id ) ) {
				$args['button_attr']['data-bp-content-id']   = $document_id;
				$args['button_attr']['data-bp-content-type'] = self::$moderation_type;
			}
			if ( 1 === count( $explode_documents ) && ! empty( current( $explode_documents ) ) ) {
				$args['button_attr']['data-bp-content-id']   = current( $explode_documents );
				$args['button_attr']['data-bp-content-type'] = self::$moderation_type;
			}
			$report_button = bp_moderation_get_report_button( $args, false );
		}

		return $report_button;
	}

	/**
	 * Prepare Where sql for exclude Blocked items.
	 *
	 * @since BuddyBoss 2.3.50
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
				$where .= "( ( {$this->alias}.id NOT IN ( $blocked_query ) OR ( d.privacy = 'comment' OR d.privacy = 'forums' ) ) OR {$this->alias}.id IS NULL )";
			}
		}

		return $where;
	}
}
