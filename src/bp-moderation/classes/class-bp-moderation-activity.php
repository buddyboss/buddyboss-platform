<?php
/**
 * BuddyBoss Moderation Activity Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Activity extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity';

	/**
	 * BP_Moderation_Activity constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		// Register moderation data.
		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend and if Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_activity_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );

		add_action( 'bb_moderation_before_get_related_' . $this->item_type, array( $this, 'remove_pre_validate_check' ) );
		add_action( 'bb_moderation_after_get_related_' . $this->item_type, array( $this, 'add_pre_validate_check' ) );

		add_filter( 'bp_get_activity_content_body', array( $this, 'bb_activity_content_remove_mention_link' ), 10, 1 );
		add_filter( 'bp_get_activity_content', array( $this, 'bb_activity_content_remove_mention_link' ), 10, 1 );

		// Code after below condition should not execute if moderation setting for this content disabled.
		if ( ! bp_is_moderation_content_reporting_enable( 0, self::$moderation_type ) ) {
			return;
		}

		// Update report button.
		add_filter( "bp_moderation_{$this->item_type}_button_sub_items", array( $this, 'update_button_sub_items' ) );

		// Validate item before proceed.
		add_filter( "bp_moderation_{$this->item_type}_validate", array( $this, 'validate_single_item' ), 10, 2 );

		// Report button text.
		add_filter( "bb_moderation_{$this->item_type}_report_button_text", array( $this, 'report_button_text' ), 10, 2 );
		add_filter( "bb_moderation_{$this->item_type}_reported_button_text", array( $this, 'report_button_text' ), 10, 2 );

		// Report popup content type.
		add_filter( "bp_moderation_{$this->item_type}_report_content_type", array( $this, 'report_content_type' ), 10, 2 );

		add_action( 'bp_follow_before_save', array( $this, 'bb_follow_before_save' ) );
	}

	/**
	 * Get permalink
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $activity_id activity id.
	 *
	 * @return string
	 */
	public static function get_permalink( $activity_id ) {
		$url = bp_activity_get_permalink( $activity_id );

		return add_query_arg( array( 'modbypass' => 1 ), $url );
	}

	/**
	 * Get Content owner id.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param integer $activity_id Activity id.
	 *
	 * @return int
	 */
	public static function get_content_owner_id( $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		return ( ! empty( $activity->user_id ) ) ? $activity->user_id : 0;
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
		$content_types[ self::$moderation_type ] = __( 'Activity', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param array  $where   Activity Where sql.
	 * @param object $suspend Suspend object.
	 *
	 * @return array
	 */
	public function update_where_sql( $where, $suspend ) {
		$this->alias = $suspend->alias;

		$exclude_group_sql = '';
		// Allow group activities from blocked/suspended users.
		if (
			bp_is_active( 'groups' ) &&
			function_exists( 'did_action' ) && ! did_action( 'get_template_part_activity/widget' )
		) {
			$exclude_group_sql = ' OR a.component = "groups"';
		}

		$sql = $this->exclude_where_query();
		if ( ! empty( $sql ) ) {
			$where['moderation_where'] = $sql;
		}

		// Allow to search isblocked members activity comment but isblocked members activity should not be searchable.
		if (
			function_exists( 'bb_did_filter' ) &&
			! bb_did_filter( 'bp_activity_comments_search_where_conditions' )
		) {
			if ( isset( $where['moderation_where'] ) && ! empty( $where['moderation_where'] ) ) {
				$where['moderation_where'] .= ' AND ';
			}

			$where['moderation_where'] .= '( a.user_id NOT IN ( ' . bb_moderation_get_blocked_by_sql() . ' ) )';
		}

		if ( ! empty( $exclude_group_sql ) ) {
			$sql = $this->exclude_where_query( false );

			$where['moderation_where'] .= $exclude_group_sql . ' AND ( ' . $sql . ' ) ';
		}
		return $where;
	}

	/**
	 * Validate the activity is valid or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param boolean $restrict Check the item is valid or not.
	 * @param object  $activity Current activity object.
	 *
	 * @return false
	 */
	public function restrict_single_item( $restrict, $activity ) {

		if ( apply_filters( 'bb_moderation_restrict_single_item_' . self::$moderation_type, false, $activity ) ) {
			return $restrict;
		}

		$username_visible = isset( $_GET['username_visible'] ) ? sanitize_text_field( wp_unslash( $_GET['username_visible'] ) ) : false;

		if ( ! empty( $username_visible ) ) {
			return $restrict;
		}

		if (
			'activity_comment' !== $activity->type &&
			$this->is_content_hidden( (int) $activity->id ) &&
			(
				// Allow comment to group activity.
				! bp_is_active( 'groups' ) ||
				'groups' !== $activity->component
			)
		) {
			return false;
		}

		return $restrict;
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

		$activity = new BP_Activity_Activity( (int) $item_id );

		if ( empty( $activity->id ) ) {
			return array();
		}

		/**
		 * Restricted Report link for Auto-created activity. Like Group create, Group join, Reply create etc.
		 */
		if ( in_array( $activity->type, array( 'new_member', 'new_avatar', 'updated_profile', 'created_group', 'joined_group', 'group_details_updated', 'friendship_created', 'friendship_accepted', 'friends_register_activity_action', 'new_blog_post', 'new_blog' ), true ) ) {
			return array(
				'id'   => false,
				'type' => false,
			);
		}

		$sub_items = array();
		$updated   = false;
		switch ( $activity->type ) {
			case 'bbp_forum_create':
				$forum_id = $activity->item_id;
				$updated  = true;
				if ( function_exists( 'bbp_is_forum_group_forum' ) && bbp_is_forum_group_forum( $forum_id ) ) {
					$sub_items['id']   = current( bbp_get_forum_group_ids( $forum_id ) );
					$sub_items['type'] = BP_Moderation_Groups::$moderation_type;
				} else {
					$sub_items['id']   = $activity->item_id;
					$sub_items['type'] = BP_Moderation_Forums::$moderation_type;
				}
				break;
			case 'bbp_topic_create':
				$updated = true;
				if ( bp_is_active( 'forums' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Forum_Topics::$moderation_type ) ) {
					$sub_items['id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
					$sub_items['type'] = BP_Moderation_Forum_Topics::$moderation_type;
				} else {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				}
				break;
			case 'bbp_reply_create':
				$updated = true;
				if ( bp_is_active( 'forums' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Forum_Replies::$moderation_type ) ) {
					$sub_items['id']   = ( 'groups' === $activity->component ) ? $activity->secondary_item_id : $activity->item_id;
					$sub_items['type'] = BP_Moderation_Forum_Replies::$moderation_type;
				} else {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				}
				break;
		}

		$media_id     = bp_activity_get_meta( $activity->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $activity->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $activity->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

		if ( ( ! empty( $media_id ) || ! empty( $media_ids ) ) && false === $updated ) {
			if ( bp_is_active( 'media' ) && ! empty( $media_id ) ) {
				if ( ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Media::$moderation_type ) ) {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				} else {
					$sub_items['id']   = $media_id;
					$sub_items['type'] = BP_Moderation_Media::$moderation_type;
				}
			} elseif ( bp_is_active( 'media' ) && ! empty( $media_ids ) ) {
				$explode_medias = explode( ',', $media_ids );
				if ( 1 === count( $explode_medias ) && ! empty( current( $explode_medias ) ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Media::$moderation_type ) ) {
					$sub_items['id']   = current( $explode_medias );
					$sub_items['type'] = BP_Moderation_Media::$moderation_type;
				} elseif ( 1 < count( $explode_medias ) ) {
					$sub_items['id']   = $activity->id;
					$sub_items['type'] = self::$moderation_type;
				} else {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				}
			} else {
				$sub_items['id']   = false;
				$sub_items['type'] = false;
			}
		} elseif ( ( ! empty( $document_id ) || ! empty( $document_ids ) ) && false === $updated ) {
			if ( bp_is_active( 'document' ) && ! empty( $document_id ) ) {
				if ( ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Document::$moderation_type ) ) {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				} else {
					$sub_items['id']   = $document_id;
					$sub_items['type'] = BP_Moderation_Document::$moderation_type;
				}
			} elseif ( bp_is_active( 'document' ) && ! empty( $document_ids ) ) {
				$explode_documents = explode( ',', $document_ids );
				if ( 1 === count( $explode_documents ) && ! empty( current( $explode_documents ) ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Document::$moderation_type ) ) {
					$sub_items['id']   = current( $explode_documents );
					$sub_items['type'] = BP_Moderation_Document::$moderation_type;
				} elseif ( 1 < count( $explode_documents ) ) {
					$sub_items['id']   = $activity->id;
					$sub_items['type'] = self::$moderation_type;
				} else {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				}
			} else {
				$sub_items['id']   = false;
				$sub_items['type'] = false;
			}
		} elseif ( ( ! empty( $video_id ) || ! empty( $video_ids ) ) && false === $updated ) {
			if ( bp_is_active( 'video' ) && ! empty( $video_id ) ) {
				if ( ! bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Video::$moderation_type ) ) {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				} else {
					$sub_items['id']   = $video_id;
					$sub_items['type'] = BP_Moderation_Video::$moderation_type;
				}
			} elseif ( bp_is_active( 'video' ) && ! empty( $video_ids ) ) {
				$explode_videos = explode( ',', $video_ids );
				if ( 1 === count( $explode_videos ) && ! empty( current( $explode_videos ) ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Video::$moderation_type ) ) {
					$sub_items['id']   = current( $explode_videos );
					$sub_items['type'] = BP_Moderation_Video::$moderation_type;
				} elseif ( 1 < count( $explode_videos ) ) {
					$sub_items['id']   = $activity->id;
					$sub_items['type'] = self::$moderation_type;
				} else {
					$sub_items['id']   = false;
					$sub_items['type'] = false;
				}
			} else {
				$sub_items['id']   = false;
				$sub_items['type'] = false;
			}
		}

		return $sub_items;
	}

	/**
	 * Filter to check the activity is valid or not.
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

		$activity = new BP_Activity_Activity( (int) $item_id );

		if ( empty( $activity ) || empty( $activity->id ) ) {
			return false;
		}

		return $retval;
	}

	/**
	 * Remove Pre-validate check.
	 *
	 * @since BuddyBoss 1.7.5
	 */
	public function remove_pre_validate_check() {
		remove_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10 );
	}

	/**
	 * Add Pre-validate check.
	 *
	 * @since BuddyBoss 1.7.5
	 */
	public function add_pre_validate_check() {
		add_filter( 'bp_activity_activity_pre_validate', array( $this, 'restrict_single_item' ), 10, 2 );
	}

	/**
	 * Function to change report button text.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $button_text Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string|void
	 */
	public function report_button_text( $button_text, $item_id ) {

		$activity = new BP_Activity_Activity( $item_id );

		if ( empty( $activity->id ) ) {
			return $button_text;
		}

		$updated = false;

		switch ( $activity->type ) {
			case 'bbp_forum_create':
				$button_text = esc_html__( 'Report Forum', 'buddyboss' );
				$updated     = true;
				break;
			case 'bbp_topic_create':
				$button_text = esc_html__( 'Report Discussion', 'buddyboss' );
				$updated     = true;
				break;
			case 'bbp_reply_create':
				$button_text = esc_html__( 'Report Reply', 'buddyboss' );
				$updated     = true;
				break;
			default:
				$button_text = esc_html__( 'Report Post', 'buddyboss' );
		}

		$media_id     = bp_activity_get_meta( $activity->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $activity->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $activity->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

		if ( ( ! empty( $media_id ) || ! empty( $media_ids ) ) && false === $updated ) {
			if ( ! empty( $media_id ) ) {
				$button_text = esc_html__( 'Report Photo', 'buddyboss' );
			}
			if ( ! empty( $media_ids ) ) {
				$exploded_media = explode( ',', $media_ids );
				if ( 1 < count( $exploded_media ) ) {
					$button_text = esc_html__( 'Report Post', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Photo', 'buddyboss' );
				}
			}
		}

		if ( ( ! empty( $document_id ) || ! empty( $document_ids ) ) && false === $updated ) {
			if ( ! empty( $document_id ) ) {
				$button_text = esc_html__( 'Report Document', 'buddyboss' );
			}
			if ( ! empty( $document_ids ) ) {
				$exploded_document = explode( ',', $document_ids );
				if ( 1 < count( $exploded_document ) ) {
					$button_text = esc_html__( 'Report Post', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Document', 'buddyboss' );
				}
			}
		}

		if ( ( ! empty( $video_id ) || ! empty( $video_ids ) ) && false === $updated ) {
			if ( ! empty( $video_id ) ) {
				$button_text = esc_html__( 'Report Video', 'buddyboss' );
			}
			if ( ! empty( $video_ids ) ) {
				$exploded_video = explode( ',', $video_ids );
				if ( 1 < count( $exploded_video ) ) {
					$button_text = esc_html__( 'Report Post', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Video', 'buddyboss' );
				}
			}
		}

		return $button_text;
	}

	/**
	 * Function to change report type.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Button text.
	 * @param int    $item_id     Item id.
	 *
	 * @return string|void
	 */
	public function report_content_type( $content_type, $item_id ) {

		$activity = new BP_Activity_Activity( $item_id );

		if ( empty( $activity->id ) ) {
			return $content_type;
		}

		$updated = false;

		switch ( $activity->type ) {
			case 'bbp_forum_create':
				$content_type = esc_html__( 'Forum', 'buddyboss' );
				$updated      = true;
				break;
			case 'bbp_topic_create':
				$content_type = esc_html__( 'Discussion', 'buddyboss' );
				$updated      = true;
				break;
			case 'bbp_reply_create':
				$content_type = esc_html__( 'Reply', 'buddyboss' );
				$updated      = true;
				break;
			default:
				$content_type = esc_html__( 'Post', 'buddyboss' );
		}

		$media_id     = bp_activity_get_meta( $activity->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $activity->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $activity->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $activity->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $activity->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $activity->id, 'bp_video_ids', true );

		if ( ( ! empty( $media_id ) || ! empty( $media_ids ) ) && false === $updated ) {
			if ( ! empty( $media_id ) ) {
				$content_type = esc_html__( 'Photo', 'buddyboss' );
			}
			if ( ! empty( $media_ids ) ) {
				$exploded_media = explode( ',', $media_ids );
				if ( 1 < count( $exploded_media ) ) {
					$content_type = esc_html__( 'Post', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Photo', 'buddyboss' );
				}
			}
		}

		if ( ( ! empty( $document_id ) || ! empty( $document_ids ) ) && false === $updated ) {
			if ( ! empty( $document_id ) ) {
				$content_type = esc_html__( 'Document', 'buddyboss' );
			}
			if ( ! empty( $document_ids ) ) {
				$exploded_document = explode( ',', $document_ids );
				if ( 1 < count( $exploded_document ) ) {
					$content_type = esc_html__( 'Post', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Document', 'buddyboss' );
				}
			}
		}

		if ( ( ! empty( $video_id ) || ! empty( $video_ids ) ) && false === $updated ) {
			if ( ! empty( $video_id ) ) {
				$content_type = esc_html__( 'Video', 'buddyboss' );
			}
			if ( ! empty( $video_ids ) ) {
				$exploded_video = explode( ',', $video_ids );
				if ( 1 < count( $exploded_video ) ) {
					$content_type = esc_html__( 'Post', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Video', 'buddyboss' );
				}
			}
		}

		return $content_type;
	}

	/**
	 * Function to prevent following to that user who has blocked and who is blocked the current user.
	 *
	 * @since BuddyBoss 2.2.5
	 *
	 * @param BP_Activity_Follow $follow Contains following data.
	 */
	public function bb_follow_before_save( $follow ) {
		if (
			bb_moderation_is_user_blocked_by( $follow->leader_id ) ||
			bp_moderation_is_user_blocked( $follow->leader_id )
		) {
			$follow->leader_id = '';
		}
	}

	/**
	 * Remove mentioned link from activity post.
	 *
	 * @since BuddyBoss 2.2.7
	 *
	 * @param string $content  Activity content.
	 *
	 * @return string
	 */
	public function bb_activity_content_remove_mention_link( $content ) {
		if ( empty( $content ) ) {
			return $content;
		}

		$content = bb_moderation_remove_mention_link( $content );

		return $content;
	}
}
