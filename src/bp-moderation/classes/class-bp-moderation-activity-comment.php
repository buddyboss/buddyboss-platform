<?php
/**
 * BuddyBoss Moderation Activity Comment Classes
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss\Moderation
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Database interaction class for the BuddyBoss moderation Activity Comment.
 *
 * @since BuddyBoss 1.5.6
 */
class BP_Moderation_Activity_Comment extends BP_Moderation_Abstract {

	/**
	 * Item type
	 *
	 * @var string
	 */
	public static $moderation_type = 'activity_comment';

	/**
	 * BP_Moderation_Activity_Comment constructor.
	 *
	 * @since BuddyBoss 1.5.6
	 */
	public function __construct() {

		parent::$moderation[ self::$moderation_type ] = self::class;
		$this->item_type                              = self::$moderation_type;

		add_filter( 'bp_moderation_content_types', array( $this, 'add_content_types' ) );

		/**
		 * Moderation code should not add for WordPress backend or IF Bypass argument passed for admin
		 */
		if ( ( is_admin() && ! wp_doing_ajax() ) || self::admin_bypass_check() ) {
			return;
		}

		/**
		 * If moderation setting enabled for this content then it'll filter hidden content.
		 * And IF moderation setting enabled for member then it'll filter blocked user content.
		 */
		add_filter( 'bp_suspend_activity_comment_get_where_conditions', array( $this, 'update_where_sql' ), 10, 2 );
		add_filter( 'bp_locate_template_names', array( $this, 'locate_blocked_template' ) );

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
		$content_types[ self::$moderation_type ] = __( 'Activity Comments', 'buddyboss' );

		return $content_types;
	}

	/**
	 * Update where query Remove hidden/blocked user's Activities
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $where   Activity Where sql.
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
	 * Update blocked comment template
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param string $template_names Template name.
	 *
	 * @return string
	 */
	public function locate_blocked_template( $template_names ) {
		global $activities_template;

		if ( 'activity/comment.php' !== $template_names ) {
			if ( ! is_array( $template_names ) || ! in_array( 'activity/comment.php', $template_names, true ) ) {
				return $template_names;
			}
		}

		if ( $this->is_content_hidden( $activities_template->activity->current_comment->id ) ) {
			return 'activity/blocked-comment.php';
		}

		return $template_names;
	}

	/**
	 * Function to modify button sub item
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @param int $comment_id Comment id.
	 *
	 * @return array
	 */
	public function update_button_sub_items( $comment_id ) {
		$comment = new BP_Activity_Activity( $comment_id );

		if ( empty( $comment->id ) ) {
			return array();
		}

		$sub_items = array();
		$activity  = new BP_Activity_Activity( $comment->item_id );
		if ( ! empty( $activity->id ) && 'blogs' === $activity->component ) {
			$post_type = get_post_type( $activity->secondary_item_id );
			if ( ! empty( $post_type ) && 'post' === $post_type ) {
				$post_comment_id = bp_activity_get_meta( $comment->id, "bp_blogs_{$post_type}_comment_id", true );

				if ( ! empty( $post_comment_id ) ) {
					$sub_items['id']   = $post_comment_id;
					$sub_items['type'] = BP_Moderation_Comment::$moderation_type;
				}
			}
		}

		$media_id     = bp_activity_get_meta( $comment->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $comment->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $comment->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $comment->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $comment->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $comment->id, 'bp_video_ids', true );

		if ( ! empty( $media_id ) || ! empty( $media_ids ) ) {
			if ( bp_is_active( 'media' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Media::$moderation_type ) ) {
				$explode_medias = explode( ',', $media_ids );
				if ( 1 === count( $explode_medias ) && ! empty( $explode_medias[0] ) ) {
					$media_id = $explode_medias[0];
				}
				$sub_items['id']   = $media_id;
				$sub_items['type'] = BP_Moderation_Media::$moderation_type;
				if ( 1 < count( $explode_medias ) ) {
					$sub_items['id']   = $comment->id;
					$sub_items['type'] = self::$moderation_type;
				}
			} else {
				$sub_items['id']   = false;
				$sub_items['type'] = false;
			}
		} elseif ( ! empty( $document_id ) || ! empty( $document_ids ) ) {
			if ( bp_is_active( 'document' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Document::$moderation_type ) ) {
				$explode_documents = explode( ',', $document_ids );
				if ( 1 === count( $explode_documents ) && ! empty( $explode_documents[0] ) ) {
					$document_id = $explode_documents[0];
				}
				$sub_items['id']   = $document_id;
				$sub_items['type'] = BP_Moderation_Document::$moderation_type;
				if ( 1 < count( $explode_documents ) ) {
					$sub_items['id']   = $comment->id;
					$sub_items['type'] = self::$moderation_type;
				}
			} else {
				$sub_items['id']   = false;
				$sub_items['type'] = false;
			}
		} elseif ( ! empty( $video_id ) || ! empty( $video_ids ) ) {
			if ( bp_is_active( 'video' ) && bp_is_moderation_content_reporting_enable( 0, BP_Moderation_Video::$moderation_type ) ) {
				$explode_videos = explode( ',', $video_ids );
				if ( 1 === count( $explode_videos ) && ! empty( $explode_videos[0] ) ) {
					$video_id = $explode_videos[0];
				}
				$sub_items['id']   = $video_id;
				$sub_items['type'] = BP_Moderation_Video::$moderation_type;
				if ( 1 < count( $explode_videos ) ) {
					$sub_items['id']   = $comment->id;
					$sub_items['type'] = self::$moderation_type;
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
	 * Check content is hidden or not.
	 *
	 * @since BuddyBoss 1.5.6
	 *
	 * @return bool
	 */
	protected function is_content_hidden( $item_id ) {

		$author_id = self::get_content_owner_id( $item_id );

		if ( ( $this->is_member_blocking_enabled() && ! empty( $author_id ) && ! bp_moderation_is_user_suspended( $author_id ) && bp_moderation_is_user_blocked( $author_id ) ) ||
		     ( $this->is_reporting_enabled() && BP_Core_Suspend::check_hidden_content( $item_id, $this->item_type ) ) ) {
			return true;
		}
		return false;
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

		$comment = new BP_Activity_Activity( $item_id );

		if ( empty( $comment->id ) ) {
			return $button_text;
		}

		$media_id     = bp_activity_get_meta( $comment->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $comment->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $comment->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $comment->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $comment->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $comment->id, 'bp_video_ids', true );

		if ( ! empty( $media_id ) || ! empty( $media_ids ) ) {
			if ( ! empty( $media_id ) ) {
				$button_text = esc_html__( 'Report Photo', 'buddyboss' );
			}
			if ( ! empty( $media_ids ) ) {
				$exploded_media = explode( ',', $media_ids );
				if ( 1 < count( $exploded_media ) ) {
					$button_text = esc_html__( 'Report Comment', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Photo', 'buddyboss' );
				}
			}
		} elseif ( ! empty( $document_id ) || ! empty( $document_ids ) ) {
			if ( ! empty( $document_id ) ) {
				$button_text = esc_html__( 'Report Document', 'buddyboss' );
			}
			if ( ! empty( $document_ids ) ) {
				$exploded_document = explode( ',', $document_ids );
				if ( 1 < count( $exploded_document ) ) {
					$button_text = esc_html__( 'Report Comment', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Document', 'buddyboss' );
				}
			}
		} elseif ( ! empty( $video_id ) || ! empty( $video_ids ) ) {
			if ( ! empty( $video_id ) ) {
				$button_text = esc_html__( 'Report Video', 'buddyboss' );
			}
			if ( ! empty( $video_ids ) ) {
				$exploded_video = explode( ',', $video_ids );
				if ( 1 < count( $exploded_video ) ) {
					$button_text = esc_html__( 'Report Comment', 'buddyboss' );
				} else {
					$button_text = esc_html__( 'Report Video', 'buddyboss' );
				}
			}
		} else {
			$button_text = esc_html__( 'Report Comment', 'buddyboss' );
		}

		return $button_text;
	}

	/**
	 * Function to change report type.
	 *
	 * @since BuddyBoss 1.7.3
	 *
	 * @param string $content_type Button text.
	 * @param int    $item_id      Item id.
	 *
	 * @return string
	 */
	public function report_content_type( $content_type, $item_id ) {
		$comment = new BP_Activity_Activity( $item_id );

		if ( empty( $comment->id ) ) {
			return $content_type;
		}

		$media_id     = bp_activity_get_meta( $comment->id, 'bp_media_id', true );
		$media_ids    = bp_activity_get_meta( $comment->id, 'bp_media_ids', true );
		$document_id  = bp_activity_get_meta( $comment->id, 'bp_document_id', true );
		$document_ids = bp_activity_get_meta( $comment->id, 'bp_document_ids', true );
		$video_id     = bp_activity_get_meta( $comment->id, 'bp_video_id', true );
		$video_ids    = bp_activity_get_meta( $comment->id, 'bp_video_ids', true );

		if ( ! empty( $media_id ) || ! empty( $media_ids ) ) {
			if ( ! empty( $media_id ) ) {
				$content_type = esc_html__( 'Photo', 'buddyboss' );
			}
			if ( ! empty( $media_ids ) ) {
				$exploded_media = explode( ',', $media_ids );
				if ( 1 < count( $exploded_media ) ) {
					$content_type = esc_html__( 'Comment', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Photo', 'buddyboss' );
				}
			}
		} elseif ( ! empty( $document_id ) || ! empty( $document_ids ) ) {
			if ( ! empty( $document_id ) ) {
				$content_type = esc_html__( 'Document', 'buddyboss' );
			}
			if ( ! empty( $document_ids ) ) {
				$exploded_document = explode( ',', $document_ids );
				if ( 1 < count( $exploded_document ) ) {
					$content_type = esc_html__( 'Comment', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Document', 'buddyboss' );
				}
			}
		} elseif ( ! empty( $video_id ) || ! empty( $video_ids ) ) {
			if ( ! empty( $video_id ) ) {
				$content_type = esc_html__( 'Video', 'buddyboss' );
			}
			if ( ! empty( $video_ids ) ) {
				$exploded_video = explode( ',', $video_ids );
				if ( 1 < count( $exploded_video ) ) {
					$content_type = esc_html__( 'Comment', 'buddyboss' );
				} else {
					$content_type = esc_html__( 'Video', 'buddyboss' );
				}
			}
		} else {
			$content_type = esc_html__( 'Comment', 'buddyboss' );
		}

		return $content_type;
	}
}
