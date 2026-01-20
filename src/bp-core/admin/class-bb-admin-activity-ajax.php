<?php
/**
 * BuddyBoss Admin Activity AJAX Handler
 *
 * Handles AJAX requests for Activity management in the Settings 2.0 admin interface.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Admin_Activity_Ajax
 *
 * @since BuddyBoss 3.0.0
 */
class BB_Admin_Activity_Ajax {

	/**
	 * Nonce action.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings_2_0';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function __construct() {
		$this->register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	private function register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_activity_types', array( $this, 'get_activity_types' ) );
		add_action( 'wp_ajax_bb_admin_get_activity_topics', array( $this, 'get_activity_topics' ) );
		add_action( 'wp_ajax_bb_admin_get_activities', array( $this, 'get_activities' ) );
		add_action( 'wp_ajax_bb_admin_get_activity', array( $this, 'get_activity' ) );
		add_action( 'wp_ajax_bb_admin_update_activity', array( $this, 'update_activity' ) );
		add_action( 'wp_ajax_bb_admin_delete_activity', array( $this, 'delete_activity' ) );
		add_action( 'wp_ajax_bb_admin_spam_activity', array( $this, 'spam_activity' ) );
	}

	/**
	 * Verify AJAX request.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @return bool|void
	 */
	private function verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		return true;
	}

	/**
	 * Get activity types.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_activity_types() {
		$this->verify_request();

		$types = array();

		// Get all registered activity actions.
		if ( function_exists( 'bp_activity_get_actions' ) ) {
			$actions = bp_activity_get_actions();

			foreach ( $actions as $component => $component_actions ) {
				foreach ( $component_actions as $action_key => $action_data ) {
					$types[] = array(
						'key'       => $action_key,
						'label'     => $action_data['value'],
						'component' => $component,
					);
				}
			}
		}

		// Remove mis-named activity type from before BP 1.6.
		$types = array_filter(
			$types,
			function ( $type ) {
				return $type['key'] !== 'friends_register_activity_action';
			}
		);

		// Sort by label.
		usort(
			$types,
			function ( $a, $b ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		wp_send_json_success( array_values( $types ) );
	}

	/**
	 * Get activity topics.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_activity_topics() {
		$this->verify_request();

		$topics = array();

		// Check if activity topics manager exists.
		if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
			$topics_manager = bb_activity_topics_manager_instance();
			if ( method_exists( $topics_manager, 'bb_get_activity_topics' ) ) {
				$raw_topics = $topics_manager->bb_get_activity_topics();
				if ( ! empty( $raw_topics ) && is_array( $raw_topics ) ) {
					foreach ( $raw_topics as $topic ) {
						$topics[] = array(
							'id'   => isset( $topic['topic_id'] ) ? (int) $topic['topic_id'] : 0,
							'name' => isset( $topic['name'] ) ? $topic['name'] : '',
							'slug' => isset( $topic['slug'] ) ? $topic['slug'] : '',
						);
					}
				}
			}
		}

		wp_send_json_success( $topics );
	}

	/**
	 * Get activities list.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_activities() {
		$this->verify_request();

		$per_page = isset( $_POST['per_page'] ) ? (int) $_POST['per_page'] : 20;
		$page     = isset( $_POST['page'] ) ? (int) $_POST['page'] : 1;

		// Determine spam filter - match old implementation behavior.
		$spam = 'ham_only'; // Default to non-spam activities.
		if ( ! empty( $_POST['spam'] ) ) {
			$spam_param = sanitize_text_field( wp_unslash( $_POST['spam'] ) );
			if ( 'spam' === $spam_param || 'spam_only' === $spam_param ) {
				$spam = 'spam_only';
			} elseif ( 'all' === $spam_param ) {
				$spam = 'all';
			}
		}

		$args = array(
			'per_page'         => $per_page,
			'page'             => $page,
			'sort'             => isset( $_POST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_POST['order'] ) ) ) : 'DESC',
			'count_total'      => 'count_query', // Match old implementation.
			'show_hidden'      => true, // Show all activities including hidden ones for admin.
			'spam'             => $spam,
			'display_comments' => 'stream', // Match old implementation - include activity comments.
			'status'           => false, // Show all statuses (published, scheduled, draft) for admin.
			'privacy'          => false, // Show all privacy levels for admin.
			'scope'            => false, // No scope restriction for admin.
		);

		// Search terms.
		if ( ! empty( $_POST['search'] ) ) {
			$args['search_terms'] = sanitize_text_field( wp_unslash( $_POST['search'] ) );
		}

		// Filter by type/action if provided.
		if ( ! empty( $_POST['type'] ) ) {
			$args['filter'] = array(
				'action' => sanitize_text_field( wp_unslash( $_POST['type'] ) ),
			);
		}

		// Filter by user if provided.
		if ( ! empty( $_POST['user_id'] ) ) {
			if ( ! isset( $args['filter'] ) ) {
				$args['filter'] = array();
			}
			$args['filter']['user_id'] = (int) $_POST['user_id'];
		}

		// Filter by component if provided.
		if ( ! empty( $_POST['component'] ) ) {
			if ( ! isset( $args['filter'] ) ) {
				$args['filter'] = array();
			}
			$args['filter']['object'] = sanitize_text_field( wp_unslash( $_POST['component'] ) );
		}

		// Get activities.
		$activities = bp_activity_get( $args );

		$formatted_activities = array();
		if ( ! empty( $activities['activities'] ) ) {
			foreach ( $activities['activities'] as $activity ) {
				$formatted_activities[] = $this->prepare_activity_for_response( $activity );
			}
		}

		// Get total count.
		$total = isset( $activities['total'] ) ? (int) $activities['total'] : count( $formatted_activities );

		wp_send_json_success(
			array(
				'activities'  => $formatted_activities,
				'total'       => $total,
				'page'        => $page,
				'per_page'    => $per_page,
				'total_pages' => ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Get single activity.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function get_activity() {
		$this->verify_request();

		$activity_id = isset( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		wp_send_json_success( $this->prepare_activity_for_response( $activity ) );
	}

	/**
	 * Update activity.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function update_activity() {
		$this->verify_request();

		$activity_id = isset( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		// Load activity as BP_Activity_Activity object (has save() method).
		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		// Store previous spam status.
		$prev_spam_status = $activity->is_spam;

		// Update activity action if provided.
		if ( isset( $_POST['activity_action'] ) ) {
			$activity->action = wp_unslash( $_POST['activity_action'] );
		}

		// Update activity title if provided.
		if ( isset( $_POST['title'] ) ) {
			$activity->post_title = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		}

		// Update activity content if provided.
		if ( isset( $_POST['content'] ) ) {
			$activity->content = wp_unslash( $_POST['content'] );
		}

		// Update primary link if provided.
		if ( isset( $_POST['primary_link'] ) && ! empty( $_POST['primary_link'] ) ) {
			$activity->primary_link = esc_url_raw( wp_unslash( $_POST['primary_link'] ) );
		}

		// Update user ID if provided.
		if ( isset( $_POST['user_id'] ) && ! empty( $_POST['user_id'] ) ) {
			$activity->user_id = (int) $_POST['user_id'];
		}

		// Update item ID if provided.
		if ( isset( $_POST['item_id'] ) ) {
			$activity->item_id = (int) $_POST['item_id'];
		}

		// Update secondary item ID if provided.
		if ( isset( $_POST['secondary_item_id'] ) ) {
			$activity->secondary_item_id = (int) $_POST['secondary_item_id'];
		}

		// Update activity type if provided.
		if ( isset( $_POST['type'] ) && ! empty( $_POST['type'] ) ) {
			$activity->type = sanitize_text_field( wp_unslash( $_POST['type'] ) );
		}

		// Update activity spam status if provided.
		if ( isset( $_POST['is_spam'] ) ) {
			$new_spam_status = filter_var( $_POST['is_spam'], FILTER_VALIDATE_BOOLEAN );

			if ( $new_spam_status && ! $prev_spam_status ) {
				// Mark as spam.
				bp_activity_mark_as_spam( $activity );
			} elseif ( ! $new_spam_status && $prev_spam_status ) {
				// Mark as ham (not spam).
				bp_activity_mark_as_ham( $activity );
			}
		}

		/**
		 * Fires before an activity item is updated via AJAX.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_Activity_Activity $activity Activity object.
		 */
		do_action( 'bb_ajax_activity_before_update', $activity );

		// Save activity using BP_Activity_Activity::save() method.
		$result = $activity->save();

		if ( ! $result ) {
			wp_send_json_error( array( 'message' => __( 'Failed to update activity.', 'buddyboss' ) ) );
		}

		// Update activity topic if provided.
		if ( isset( $_POST['topic_id'] ) ) {
			$topic_id = (int) $_POST['topic_id'];
			if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
				$topics_manager = bb_activity_topics_manager_instance();
				if ( method_exists( $topics_manager, 'bb_add_activity_topic_relationship' ) ) {
					$topics_manager->bb_add_activity_topic_relationship(
						array(
							'activity_id' => $activity_id,
							'topic_id'    => $topic_id,
							'component'   => 'activity',
						)
					);
				}
			}
		}

		/**
		 * Fires after an activity item is updated via AJAX.
		 *
		 * @since BuddyBoss 3.0.0
		 *
		 * @param BP_Activity_Activity $activity Activity object.
		 */
		do_action( 'bb_ajax_activity_after_update', $activity );

		// Reload activity to get updated data.
		$updated_activity = new BP_Activity_Activity( $activity_id );

		wp_send_json_success(
			array(
				'activity' => $this->prepare_activity_for_response( $updated_activity ),
				'message'  => __( 'Activity updated successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Delete activity.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function delete_activity() {
		$this->verify_request();

		$activity_id = isset( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		// Delete activity.
		if ( ! bp_activity_delete( array( 'id' => $activity_id ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete activity.', 'buddyboss' ) ) );
		}

		wp_send_json_success(
			array(
				'deleted' => true,
				'id'      => $activity_id,
				'message' => __( 'Activity deleted successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Mark activity as spam or ham.
	 *
	 * @since BuddyBoss 3.0.0
	 */
	public function spam_activity() {
		$this->verify_request();

		$activity_id = isset( $_POST['activity_id'] ) ? (int) $_POST['activity_id'] : 0;
		$is_spam     = isset( $_POST['is_spam'] ) ? filter_var( $_POST['is_spam'], FILTER_VALIDATE_BOOLEAN ) : true;

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		if ( $is_spam ) {
			bp_activity_mark_as_spam( $activity );
			$message = __( 'Activity marked as spam.', 'buddyboss' );
		} else {
			bp_activity_mark_as_ham( $activity );
			$message = __( 'Activity marked as not spam.', 'buddyboss' );
		}

		// Save changes.
		$activity->save();

		wp_send_json_success(
			array(
				'activity' => $this->prepare_activity_for_response( $activity ),
				'message'  => $message,
			)
		);
	}

	/**
	 * Prepare activity for response.
	 *
	 * @since BuddyBoss 3.0.0
	 *
	 * @param BP_Activity_Activity $activity Activity object.
	 * @return array Formatted activity data.
	 */
	private function prepare_activity_for_response( $activity ) {
		// Get user data.
		$user_id   = (int) $activity->user_id;
		$user_data = get_userdata( $user_id );

		// Get user display name - try multiple methods.
		$user_name = '';
		if ( $user_data ) {
			$user_name = $user_data->display_name;
		}
		if ( empty( $user_name ) && function_exists( 'bp_core_get_user_displayname' ) ) {
			$user_name = bp_core_get_user_displayname( $user_id );
		}
		if ( empty( $user_name ) && $user_data ) {
			$user_name = $user_data->user_login;
		}

		// Get user avatar - try multiple methods.
		$user_avatar = '';

		// Method 1: Use bp_core_fetch_avatar with html=false to get URL.
		if ( function_exists( 'bp_core_fetch_avatar' ) ) {
			$avatar_result = bp_core_fetch_avatar(
				array(
					'item_id' => $user_id,
					'object'  => 'user',
					'type'    => 'thumb',
					'html'    => false,
				)
			);

			// Check if it returned a URL (string starting with http).
			if ( ! empty( $avatar_result ) && is_string( $avatar_result ) ) {
				if ( strpos( $avatar_result, 'http' ) === 0 ) {
					$user_avatar = $avatar_result;
				} elseif ( strpos( $avatar_result, '<img' ) !== false ) {
					// If it returned HTML despite html=false, extract the src.
					preg_match( '/src=["\']([^"\']+)["\']/', $avatar_result, $matches );
					if ( ! empty( $matches[1] ) ) {
						$user_avatar = $matches[1];
					}
				}
			}
		}

		// Method 2: Fallback to get_avatar_url.
		if ( empty( $user_avatar ) ) {
			$user_avatar = get_avatar_url( $user_id, array( 'size' => 50 ) );
		}

		// Method 3: Fallback using user email.
		if ( empty( $user_avatar ) && $user_data && ! empty( $user_data->user_email ) ) {
			$user_avatar = get_avatar_url( $user_data->user_email, array( 'size' => 50 ) );
		}

		// Parse the action text (strip HTML and extract the action description).
		$action_text = '';
		if ( ! empty( $activity->action ) ) {
			// Strip HTML and get plain text action.
			$action_text = wp_strip_all_tags( $activity->action );
			// Remove the user name from the beginning if present.
			if ( ! empty( $user_name ) && strpos( $action_text, $user_name ) === 0 ) {
				$action_text = trim( substr( $action_text, strlen( $user_name ) ) );
			}
		}

		// Get group name if this is a group activity.
		$group_name = '';
		if ( 'groups' === $activity->component && ! empty( $activity->item_id ) && function_exists( 'groups_get_group' ) ) {
			$group = groups_get_group( $activity->item_id );
			if ( ! empty( $group->name ) ) {
				$group_name = $group->name;
			}
		}

		// Format date.
		$date_formatted = '';
		if ( ! empty( $activity->date_recorded ) ) {
			if ( function_exists( 'bp_core_time_since' ) ) {
				$date_formatted = bp_core_time_since( $activity->date_recorded );
			} else {
				$date_formatted = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $activity->date_recorded ) );
			}
		}

		// Get activity topic ID if topics manager exists.
		$topic_id = 0;
		if ( function_exists( 'bb_activity_topics_manager_instance' ) ) {
			$topics_manager = bb_activity_topics_manager_instance();
			if ( method_exists( $topics_manager, 'bb_get_activity_topic' ) ) {
				$topic_result = $topics_manager->bb_get_activity_topic( (int) $activity->id, 'id' );
				if ( ! empty( $topic_result ) ) {
					$topic_id = (int) $topic_result;
				}
			}
		}

		return array(
			'id'                      => (int) $activity->id,
			'user_id'                 => $user_id,
			'user_name'               => $user_name,
			'user_avatar'             => $user_avatar,
			'user_link'               => function_exists( 'bp_core_get_user_domain' ) ? bp_core_get_user_domain( $user_id ) : get_author_posts_url( $user_id ),
			'title'                   => isset( $activity->post_title ) ? $activity->post_title : '',
			'content'                 => $activity->content,
			'primary_link'            => isset( $activity->primary_link ) ? $activity->primary_link : '',
			'component'               => $activity->component,
			'type'                    => $activity->type,
			'action'                  => $activity->action,
			'action_text'             => $action_text,
			'group_name'              => $group_name,
			'item_id'                 => (int) $activity->item_id,
			'secondary_item_id'       => (int) $activity->secondary_item_id,
			'date_recorded'           => $activity->date_recorded,
			'date_recorded_formatted' => $date_formatted,
			'is_spam'                 => (bool) $activity->is_spam,
			'hide_sitewide'           => (bool) $activity->hide_sitewide,
			'comment_count'           => isset( $activity->comment_count ) ? (int) $activity->comment_count : 0,
			'permalink'               => function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( $activity->id ) : '',
			'topic_id'                => $topic_id,
		);
	}
}

// Initialize.
new BB_Admin_Activity_Ajax();
