<?php
/**
 * BuddyBoss Activity Admin AJAX Handler
 *
 * Handles AJAX requests for the Activity listing, editing, and actions
 * in the Settings 2.0 admin interface.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core\Administration
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Activity_Admin_Ajax
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Activity_Admin_Ajax {

	/**
	 * Nonce action (shared with BB_Admin_Settings_Ajax).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'bb_admin_settings';

	/**
	 * Constructor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function __construct() {
		$this->bb_register_ajax_handlers();
	}

	/**
	 * Register AJAX handlers.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_register_ajax_handlers() {
		add_action( 'wp_ajax_bb_admin_get_activities', array( $this, 'bb_admin_get_activities' ) );
		add_action( 'wp_ajax_bb_admin_get_activity', array( $this, 'bb_admin_get_activity' ) );
		add_action( 'wp_ajax_bb_admin_save_activity', array( $this, 'bb_admin_save_activity' ) );
		add_action( 'wp_ajax_bb_admin_activity_action', array( $this, 'bb_admin_activity_action' ) );
		add_action( 'wp_ajax_bb_admin_add_activity_comment', array( $this, 'bb_admin_add_activity_comment' ) );
	}

	/**
	 * Verify AJAX request (nonce + capability).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function bb_verify_request() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Security check failed.', 'buddyboss' ) ),
				403
			);
		}

		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Permission denied.', 'buddyboss' ) ),
				403
			);
		}

		return true;
	}

	/**
	 * Get paginated activities list.
	 *
	 * Expects POST parameters:
	 * - page: Current page number (default 1).
	 * - per_page: Items per page (default 20).
	 * - search: Search terms (optional).
	 * - activity_type: Activity type filter (optional).
	 * - spam: 'all', 'spam_only', or 'ham_only' (default 'ham_only').
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_get_activities() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'activity' ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$page          = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
		$per_page      = isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 20;
		$search_terms  = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$activity_type = isset( $_POST['activity_type'] ) ? sanitize_text_field( wp_unslash( $_POST['activity_type'] ) ) : '';
		$spam          = isset( $_POST['spam'] ) ? sanitize_text_field( wp_unslash( $_POST['spam'] ) ) : 'ham_only';
		$include_meta  = isset( $_POST['include_meta'] ) ? (bool) $_POST['include_meta'] : true;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// Validate spam parameter.
		if ( ! in_array( $spam, array( 'all', 'spam_only', 'ham_only' ), true ) ) {
			$spam = 'ham_only';
		}

		// Build filter (same as legacy BP_Activity_List_Table::prepare_items()).
		$filter       = array();
		$filter_query = false;
		if ( ! empty( $activity_type ) ) {
			$filter = array( 'action' => $activity_type );

			/**
			 * Filter to override the filter with a filter query (legacy compatibility).
			 *
			 * @since BuddyPress 2.5.0
			 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
			 *
			 * @param array $filter Filter array.
			 */
			$has_filter_query = apply_filters( 'bp_activity_list_table_filter_activity_type_items', $filter );

			if ( ! empty( $has_filter_query['filter_query'] ) ) {
				$filter       = array();
				$filter_query = $has_filter_query['filter_query'];
			}
		}

		// Get spam count (ignoring search/filter).
		$spams      = bp_activity_get(
			array(
				'display_comments' => 'stream',
				'show_hidden'      => true,
				'spam'             => 'spam_only',
				'count_total'      => 'count_query',
				'per_page'         => 1,
				'page'             => 1,
			)
		);
		$spam_count = isset( $spams['total'] ) ? (int) $spams['total'] : 0;

		// Get ham (non-spam) count for "All" tab (ignoring search/filter).
		$hams      = bp_activity_get(
			array(
				'display_comments' => 'stream',
				'show_hidden'      => true,
				'spam'             => 'ham_only',
				'count_total'      => 'count_query',
				'per_page'         => 1,
				'page'             => 1,
			)
		);
		$ham_count = isset( $hams['total'] ) ? (int) $hams['total'] : 0;

		// Get activities (same args as legacy BP_Activity_List_Table::prepare_items()).
		$get_args = array(
			'display_comments' => 'stream',
			'filter'           => $filter,
			'page'             => $page,
			'per_page'         => $per_page,
			'search_terms'     => ! empty( $search_terms ) ? $search_terms : false,
			'show_hidden'      => true,
			'spam'             => $spam,
			'count_total'      => 'count_query',
		);
		if ( false !== $filter_query ) {
			$get_args['filter_query'] = $filter_query;
		}
		$activities = bp_activity_get( $get_args );

		$total = isset( $activities['total'] ) ? (int) $activities['total'] : 0;
		$items = array();

		// Get columns (same as legacy BP_Activity_List_Table::get_columns()).
		$default_columns = array(
			'cb'       => '<input name type="checkbox" />',
			'author'   => __( 'Author', 'buddyboss' ),
			'comment'  => __( 'Activity', 'buddyboss' ),
			'action'   => __( 'Action', 'buddyboss' ),
			'response' => __( 'In Response To', 'buddyboss' ),
		);

		/**
		 * Filters the titles for the columns for the activity list table.
		 * Same hook as legacy BP_Activity_List_Table::get_columns().
		 *
		 * @since BuddyPress 2.4.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
		 *
		 * @param array $value Array of slugs and titles for the columns.
		 */
		$columns = apply_filters( 'bp_activity_list_table_get_columns', $default_columns );

		// Identify custom columns (not in default set).
		$custom_column_keys = array_diff( array_keys( $columns ), array_keys( $default_columns ) );

		// Check if blog/forum commenting is disabled (same as legacy list table constructor).
		$disable_blogforum_comments = bp_disable_blogforum_comments();

		// Don't truncate activity items (same as legacy prepare_items).
		remove_filter( 'bp_get_activity_content_body', 'bp_activity_truncate_entry', 5 );

		if ( ! empty( $activities['activities'] ) ) {
			// Prime user cache in bulk to avoid N+1 queries in the loop.
			$user_ids = array_unique( wp_list_pluck( $activities['activities'], 'user_id' ) );
			if ( ! empty( $user_ids ) ) {
				cache_users( $user_ids );
			}

			// Batch-fetch parent activities for comments to avoid N+1 queries.
			$parent_activity_cache = array();
			if ( ! $disable_blogforum_comments && bp_is_active( 'blogs' ) ) {
				$parent_ids = array();
				foreach ( $activities['activities'] as $act ) {
					if ( 'activity_comment' === $act->type && ! empty( $act->item_id ) ) {
						$parent_ids[] = (int) $act->item_id;
					}
				}
				if ( ! empty( $parent_ids ) ) {
					$parent_ids     = array_unique( $parent_ids );
					$parent_results = bp_activity_get(
						array(
							'in'               => $parent_ids,
							'display_comments' => false,
							'show_hidden'      => true,
							'per_page'         => count( $parent_ids ),
						)
					);
					if ( ! empty( $parent_results['activities'] ) ) {
						foreach ( $parent_results['activities'] as $parent ) {
							$parent_activity_cache[ (int) $parent->id ] = $parent;
						}
					}
				}
			}

			// Buffer all stray HTML output from legacy filters inside the loop.
			ob_start();

			foreach ( $activities['activities'] as $activity_obj ) {
				$user_id   = (int) $activity_obj->user_id;
				$item_data = (array) $activity_obj;

				// Apply content filters (same as legacy column_comment()).
				$display_content = '';
				if ( ! empty( $activity_obj->content ) ) {
					/** This filter is documented in bp-activity/bp-activity-template.php */
					$display_content = apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity_obj->content, &$activity_obj ) );
				} else {
					$r = array( 'no_timestamp' => false );
					/** This filter is documented in bp-activity/bp-activity-template.php */
					$display_content = apply_filters_ref_array( 'bp_get_activity_action', array( $activity_obj->action, &$activity_obj, $r ) );
				}

				/**
				 * Filter activity content for admin display.
				 * Same hook as legacy column_comment().
				 *
				 * @since BuddyPress 2.4.0
				 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
				 *
				 * @param string $display_content The activity content.
				 * @param array  $item_data       The activity object converted into an array.
				 */
				$display_content = apply_filters( 'bp_activity_admin_comment_content', $display_content, $item_data );

				// Check if activity can be commented on (same as legacy can_comment()).
				$can_comment = bp_activity_type_supports( $activity_obj->type, 'comment-reply' );

				if ( ! $disable_blogforum_comments && bp_is_active( 'blogs' ) ) {
					$parent_activity = false;

					if ( bp_activity_type_supports( $activity_obj->type, 'post-type-comment-tracking' ) ) {
						$parent_activity = $activity_obj;
					} elseif ( 'activity_comment' === $activity_obj->type ) {
						$parent_activity = isset( $parent_activity_cache[ (int) $activity_obj->item_id ] )
							? $parent_activity_cache[ (int) $activity_obj->item_id ]
							: new BP_Activity_Activity( $activity_obj->item_id );
						$can_comment     = bp_activity_can_comment_reply( $activity_obj );
					}

					if ( isset( $parent_activity->type ) && bp_activity_post_type_get_tracking_arg( $parent_activity->type, 'post_type' ) ) {
						bp_blogs_setup_activity_loop_globals( $parent_activity );
						$can_comment = bp_blogs_can_comment_reply( true, $item_data );
					}
				}

				/**
				 * Filters if an activity item can be commented on or not.
				 * Same hook as legacy BP_Activity_List_Table::can_comment().
				 *
				 * @since BuddyPress 2.0.0
				 * @since BuddyPress 2.5.0 Add a second parameter to include the activity item into the filter.
				 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
				 *
				 * @param bool  $can_comment Whether an activity item can be commented on or not.
				 * @param array $item_data   An array version of the BP_Activity_Activity object.
				 */
				$can_comment = apply_filters( 'bp_activity_list_table_can_comment', $can_comment, $item_data );

				// Row actions (same as legacy column_comment() - filter so plugins can add/alter).
				$row_actions = array(
					'edit'   => __( 'Edit', 'buddyboss' ),
					'spam'   => __( 'Spam', 'buddyboss' ),
					'unspam' => __( 'Not Spam', 'buddyboss' ),
					'delete' => __( 'Delete Permanently', 'buddyboss' ),
				);
				if ( (int) $activity_obj->is_spam ) {
					unset( $row_actions['spam'] );
				} else {
					unset( $row_actions['unspam'] );
				}
				/**
				 * Filters available row actions (same as legacy BP_Activity_List_Table::column_comment()).
				 *
				 * @since BuddyPress 1.6.0
				 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
				 *
				 * @param array $actions Array of action key => label.
				 * @param array $item_data Current item (array version of activity object).
				 */
				$row_actions = apply_filters( 'bp_activity_admin_comment_row_actions', $row_actions, $item_data );

				/**
				 * Filters the activity types considered "root" (not comment responses).
				 *
				 * Used in the "In Response To" column to determine which activities
				 * are top-level vs. replies (same logic as legacy column_response()).
				 *
				 * @since BuddyBoss [BBVERSION]
				 *
				 * @param array $root_activity_types Activity type slugs treated as root activities.
				 * @param array $item_data           The current activity item data array.
				 */
				$root_activity_types = apply_filters( 'bp_activity_admin_root_activity_types', array( 'activity_comment' ), $item_data );
				$is_root_activity    = empty( $item_data['item_id'] ) || ! in_array( $activity_obj->type, $root_activity_types, true );

				$item = array(
					'id'                => (int) $activity_obj->id,
					'user_id'           => $user_id,
					'action'            => $activity_obj->action,
					'content'           => $activity_obj->content,
					'display_content'   => $display_content,
					'type'              => $activity_obj->type,
					'date_recorded'     => $activity_obj->date_recorded,
					'is_spam'           => (int) $activity_obj->is_spam,
					'primary_link'      => $activity_obj->primary_link,
					'permalink'         => bp_activity_get_permalink( $activity_obj->id, $activity_obj ),
					'item_id'           => (int) $activity_obj->item_id,
					'secondary_item_id' => (int) $activity_obj->secondary_item_id,
					'component'         => $activity_obj->component,
					'post_title'        => isset( $activity_obj->post_title ) ? $activity_obj->post_title : '',
					'can_comment'       => (bool) $can_comment,
					'row_actions'       => $row_actions,
					'is_root_activity'  => $is_root_activity,
					'author'            => array(
						'id'          => $user_id,
						'name'        => bp_core_get_user_displayname( $user_id ),
						'avatar_url'  => bp_core_fetch_avatar(
							array(
								'item_id' => $user_id,
								'object'  => 'user',
								'type'    => 'thumb',
								'width'   => 32,
								'height'  => 32,
								'html'    => false,
							)
						),
						'profile_url' => bp_core_get_user_domain( $user_id ),
					),
				);

				// Custom columns (same as legacy column_default()).
				if ( ! empty( $custom_column_keys ) ) {
					$custom_columns = array();
					foreach ( $custom_column_keys as $col_key ) {
						/**
						 * Filters a string to allow plugins to add custom column content.
						 * Same hook as legacy BP_Activity_List_Table::column_default().
						 *
						 * @since BuddyPress 2.4.0
						 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
						 *
						 * @param string $value       Empty string.
						 * @param string $column_name Name of the column being rendered.
						 * @param array  $item_data   The current activity item in the loop.
						 */
						$custom_columns[ $col_key ] = apply_filters( 'bp_activity_admin_get_custom_column', '', $col_key, $item_data );
					}
					$item['custom_columns'] = $custom_columns;
				}

				$items[] = $item;
			}

			// End output buffer started before the loop.
			ob_end_clean();
		}

		// Static meta (actions, bulk_actions, columns) only computed on first request.
		// Subsequent paginated/filtered requests pass include_meta=false to skip this.
		$activity_actions         = array();
		$activity_actions_grouped = array();
		$bulk_actions             = array();
		$columns_response         = array();

		if ( $include_meta ) {
			// Get activity action types for filter dropdown (flat list for backward compat).
			if ( function_exists( 'bp_activity_admin_get_activity_actions' ) ) {
				$activity_actions = bp_activity_admin_get_activity_actions();
			}

			// Build grouped activity actions for optgroup-style dropdown.
			if ( function_exists( 'bp_activity_get_actions' ) ) {
				foreach ( bp_activity_get_actions() as $component => $actions ) {
					// Resolve component display name (same as legacy list table).
					if ( 'profile' === $component ) {
						$component = 'xprofile';
					}

					if ( bp_is_active( $component ) ) {
						if ( 'xprofile' === $component ) {
							$component_name = buddypress()->profile->name;
						} else {
							$component_name = buddypress()->$component->name;
						}
					} else {
						$component_name = ucfirst( $component );
					}

					$component_name = ( 'Bbpress' === $component_name ) ? __( 'Forums', 'buddyboss' ) : $component_name;

					$group_options = array();
					$actions_arr   = (array) $actions;
					foreach ( $actions_arr as $action_key => $action_values ) {
						if ( 'friends_register_activity_action' === $action_key ) {
							continue;
						}
						$group_options[] = array(
							'label' => $action_values['value'],
							'value' => $action_key,
						);
					}

					if ( ! empty( $group_options ) ) {
						$activity_actions_grouped[] = array(
							'label'   => $component_name,
							'options' => $group_options,
						);
					}
				}
			}

			// Get bulk actions (same as legacy BP_Activity_List_Table::get_bulk_actions()).
			$bulk_actions = array(
				'bulk_spam'   => __( 'Mark as Spam', 'buddyboss' ),
				'bulk_ham'    => __( 'Not Spam', 'buddyboss' ),
				'bulk_delete' => __( 'Delete Permanently', 'buddyboss' ),
			);

			/**
			 * Filters the default bulk actions so plugins can add custom actions.
			 * Same hook as legacy BP_Activity_List_Table::get_bulk_actions().
			 *
			 * @since BuddyPress 1.6.0
			 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
			 *
			 * @param array $bulk_actions Default available actions for bulk operations.
			 */
			$bulk_actions = apply_filters( 'bp_activity_list_table_get_bulk_actions', $bulk_actions );

			// Build columns response (exclude 'cb' checkbox - handled by React).
			foreach ( $columns as $col_key => $col_label ) {
				if ( 'cb' === $col_key ) {
					continue;
				}
				$columns_response[ $col_key ] = $col_label;
			}
		}

		// Build views (All / Spam tabs) with new structured filter.
		$current_view = 'spam_only' === $spam ? 'spam' : 'all';
		$views        = array(
			'all'  => array(
				'label' => __( 'All', 'buddyboss' ),
				'count' => $ham_count,
			),
			'spam' => array(
				'label' => __( 'Spam', 'buddyboss' ),
				'count' => $spam_count,
			),
		);

		/**
		 * Filters the activity list views (tab filters like All, Spam).
		 *
		 * Replaces legacy `bp_activity_list_table_get_views` action which outputs HTML.
		 * This filter returns structured data for the React UI instead.
		 *
		 * Each view is an array with 'label' (string) and 'count' (int).
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array  $views        Array of view_key => array( 'label' => string, 'count' => int ).
		 * @param string $current_view Current active view ('all' or 'spam').
		 */
		$views = apply_filters( 'bb_admin_activity_list_views', $views, $current_view );

		/**
		 * Fires inside listing of views so plugins can add their own.
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
		 *
		 * @deprecated BuddyBoss [BBVERSION] Use {@see 'bb_admin_activity_list_views'} filter instead.
		 *             This action outputs HTML which is not compatible with the React-based Settings 2.0 UI.
		 *             It is fired here for backwards compatibility only.
		 *
		 * @param string $url_base Current URL base for view (empty in AJAX context).
		 * @param string $view     Current view being displayed.
		 */
		ob_start();
		do_action( 'bp_activity_list_table_get_views', '', $current_view );
		ob_end_clean();

		/**
		 * Fires before the activity admin index screen is displayed.
		 * Same hook as legacy bp_activity_admin_index().
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity listing.
		 *
		 * @param array $messages Array of messages to display at top of page (empty in AJAX context).
		 */
		do_action( 'bp_activity_admin_index', array() );

		$response_data = array(
			'activities'       => $items,
			'total'            => $total,
			'per_page'         => $per_page,
			'page'             => $page,
			'spam_count'       => $spam_count,
			'views'            => $views,
			'activity_actions'         => $activity_actions,
			'activity_actions_grouped' => $activity_actions_grouped,
			'bulk_actions'             => $bulk_actions,
			'columns'                  => $columns_response,
		);

		/**
		 * Filters the full response data for the admin activities list AJAX endpoint.
		 *
		 * Allows third-party plugins to add extra data to the activities listing response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array $response_data Response data array.
		 */
		$response_data = apply_filters( 'bb_admin_get_activities_response', $response_data );

		wp_send_json_success( $response_data );
	}

	/**
	 * Get a single activity for editing.
	 *
	 * Expects POST parameters:
	 * - activity_id: The activity ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_get_activity() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'activity' ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$activity_id = isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		$activity = bp_activity_get(
			array(
				'in'               => $activity_id,
				'max'              => 1,
				'show_hidden'      => true,
				'spam'             => 'all',
				'display_comments' => 0,
			)
		);

		if ( empty( $activity['activities'][0] ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		$item = $activity['activities'][0];

		/**
		 * Fires after the registration of all of the default activity meta boxes.
		 * Same hook as legacy bp_activity_admin_load() edit screen setup.
		 * Allows plugins to register additional data for the activity edit form.
		 *
		 * @since BuddyPress 2.4.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX get activity.
		 */
		ob_start();
		do_action( 'bp_activity_admin_meta_boxes' );
		ob_end_clean();

		/**
		 * Fires before activity edit form is displayed so plugins can modify the activity.
		 * Same hook as legacy bp_activity_admin_edit().
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX get activity.
		 *
		 * @param array $value Array holding single activity object that was passed by reference.
		 */
		ob_start();
		do_action_ref_array( 'bp_activity_admin_edit', array( &$item ) );
		ob_end_clean();

		// Get activity action types.
		$activity_actions = array();
		if ( function_exists( 'bp_activity_admin_get_activity_actions' ) ) {
			$activity_actions = bp_activity_admin_get_activity_actions();
		}

		// Build activity data — non-editable metadata plus all fields from registry.
		$activity_data = array(
			'id'                => (int) $item->id,
			'date_recorded'     => $item->date_recorded,
			'is_spam'           => (int) $item->is_spam,
			'permalink'         => bp_activity_get_permalink( $item->id, $item ),
			'component'         => $item->component,
			'registered_fields' => bb_admin_meta_field_registry()->get_fields_data( 'activity', $item ),
		);

		/**
		 * Filters the single activity data returned by the admin AJAX endpoint.
		 *
		 * Allows third-party plugins to add or modify data in the activity edit response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array              $activity_data Activity data array.
		 * @param BP_Activity_Activity $item         Activity object.
		 */
		$activity_data = apply_filters( 'bb_admin_get_activity_data', $activity_data, $item );

		// Build response data.
		$response_data = array(
			'activity'         => $activity_data,
			'activity_actions' => $activity_actions,
		);

		/**
		 * Filters the full response data for the admin get activity AJAX endpoint.
		 *
		 * Allows third-party plugins to add extra data to the response.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param array              $response_data Response data array.
		 * @param BP_Activity_Activity $item         Activity object.
		 */
		$response_data = apply_filters( 'bb_admin_get_activity_response', $response_data, $item );

		wp_send_json_success( $response_data );
	}

	/**
	 * Save/update an activity.
	 *
	 * Expects POST parameters:
	 * - activity_id: The activity ID.
	 * - action_text: Activity action (rich text).
	 * - content: Activity content (rich text).
	 * - post_title: Activity title.
	 * - type: Activity type.
	 * - primary_link: Activity link.
	 * - user_id: Author user ID.
	 * - item_id: Primary item ID.
	 * - secondary_item_id: Secondary item ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_save_activity() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'activity' ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$activity_id = isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		// Get the activity from the database.
		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->component ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		// Phase 1: Set core object properties from registry fields before save.
		// This includes spam/ham status via the is_spam registry field.
		bb_admin_meta_field_registry()->save_fields_data( 'activity', $activity, 'before' );

		// Prevent title validation from blocking save in admin context.
		$activity->title_required = false;

		// Set link embed data so bp_activity_after_save can update link preview metadata.
		// Same as legacy bp_activity_admin_load() save handler.
		if ( ! empty( $activity->content ) ) {
			$urls = wp_extract_urls( $activity->content );
			if ( ! empty( $urls[0] ) ) {
				$_POST['link_url']   = esc_url_raw( $urls[0] );
				$_POST['link_embed'] = true;
			}
		}

		// Save.
		$result = $activity->save();

		// Clear the activity feed cache (same as legacy).
		wp_cache_delete( 'bp_activity_sitewide_front', 'bp' );

		$error = ( false === $result ) ? $activity->id : 0;

		/**
		 * Fires after activity admin save (same as legacy admin).
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX save activity.
		 *
		 * @param BP_Activity_Activity $activity Activity object.
		 * @param int                  $error    Activity ID on failure, 0 on success.
		 */
		do_action_ref_array( 'bp_activity_admin_edit_after', array( &$activity, $error ) );

		if ( 0 !== $error ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save activity.', 'buddyboss' ) ) );
		}

		// Phase 2: Save meta fields via the field registry after save.
		bb_admin_meta_field_registry()->save_fields_data( 'activity', $activity, 'after' );

		wp_send_json_success(
			array(
				'message' => __( 'Activity saved successfully.', 'buddyboss' ),
			)
		);
	}

	/**
	 * Perform action on activities (spam, ham, delete).
	 *
	 * Expects POST parameters:
	 * - activity_ids: Comma-separated activity IDs or JSON array.
	 * - do_action: The action to perform ('spam', 'ham', 'delete').
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_activity_action() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'activity' ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$raw_ids   = isset( $_POST['activity_ids'] ) ? sanitize_text_field( wp_unslash( $_POST['activity_ids'] ) ) : '';
		$do_action = isset( $_POST['do_action'] ) ? sanitize_text_field( wp_unslash( $_POST['do_action'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $raw_ids ) || empty( $do_action ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity IDs and action are required.', 'buddyboss' ) ) );
		}

		if ( ! in_array( $do_action, array( 'spam', 'ham', 'delete' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid action.', 'buddyboss' ) ) );
		}

		// Parse activity IDs.
		$activity_ids = array_map( 'absint', explode( ',', $raw_ids ) );
		$activity_ids = array_filter( $activity_ids );

		if ( empty( $activity_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No valid activity IDs provided.', 'buddyboss' ) ) );
		}

		/**
		 * Filters list of IDs being spammed/un-spammed/deleted (same as legacy admin).
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity action.
		 *
		 * @param array $activity_ids Activity IDs to spam/un-spam/delete.
		 */
		$activity_ids = apply_filters( 'bp_activity_admin_action_activity_ids', $activity_ids );

		$processed = 0;
		$spammed   = 0;
		$unspammed = 0;
		$deleted   = 0;
		$errors    = array();

		foreach ( $activity_ids as $activity_id ) {
			$activity = new BP_Activity_Activity( $activity_id );

			if ( empty( $activity->component ) ) {
				$errors[] = $activity_id;
				continue;
			}

			$activity->title_required = false;

			switch ( $do_action ) {
				case 'delete':
					if ( 'activity_comment' === $activity->type ) {
						$result = bp_activity_delete_comment( $activity->item_id, $activity->id );
					} else {
						$result = bp_activity_delete( array( 'id' => $activity->id ) );
					}
					if ( false !== $result ) {
						++$processed;
						++$deleted;
					} else {
						$errors[] = $activity_id;
					}
					break;

				case 'ham':
					remove_action( 'bp_activity_before_save', 'bp_activity_check_moderation_keys', 2 );
					remove_action( 'bp_activity_before_save', 'bp_activity_check_blacklist_keys', 2 );
					bp_activity_mark_as_ham( $activity );
					$result = $activity->save();
					if ( false !== $result ) {
						++$processed;
						++$unspammed;
					} else {
						$errors[] = $activity_id;
					}
					break;

				case 'spam':
					bp_activity_mark_as_spam( $activity );
					$result = $activity->save();
					if ( false !== $result ) {
						++$processed;
						++$spammed;
					} else {
						$errors[] = $activity_id;
					}
					break;
			}

			unset( $activity );
		}

		/**
		 * Fires after activity admin action (same as legacy admin).
		 *
		 * @since BuddyPress 1.6.0
		 * @since BuddyBoss [BBVERSION] Added to Settings 2.0 AJAX activity action.
		 *
		 * @param array  $value        Array holding spam, not spam, deleted counts, error IDs.
		 * @param string $redirect_to  URL to redirect to (empty for AJAX).
		 * @param array  $activity_ids Original array of activity IDs.
		 */
		do_action( 'bp_activity_admin_action_after', array( $spammed, $unspammed, $deleted, $errors ), '', $activity_ids );

		if ( ! empty( $errors ) && 0 === $processed ) {
			wp_send_json_error( array( 'message' => __( 'Failed to process activities.', 'buddyboss' ) ) );
		}

		$messages = array(
			'spam'   => sprintf(
				/* translators: %d: number of activities */
				_n( '%d activity marked as spam.', '%d activities marked as spam.', $processed, 'buddyboss' ),
				$processed
			),
			'ham'    => sprintf(
				/* translators: %d: number of activities */
				_n( '%d activity marked as not spam.', '%d activities marked as not spam.', $processed, 'buddyboss' ),
				$processed
			),
			'delete' => sprintf(
				/* translators: %d: number of activities */
				_n( '%d activity deleted.', '%d activities deleted.', $processed, 'buddyboss' ),
				$processed
			),
		);

		wp_send_json_success(
			array(
				'message'   => $messages[ $do_action ],
				'processed' => $processed,
				'errors'    => $errors,
			)
		);
	}
	/**
	 * Add a comment to an activity.
	 *
	 * Expects POST parameters:
	 * - activity_id: The activity ID to comment on.
	 * - content: Comment content (HTML).
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_add_activity_comment() {
		$this->bb_verify_request();

		if ( ! bp_is_active( 'activity' ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity component is not active.', 'buddyboss' ) ) );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$activity_id = isset( $_POST['activity_id'] ) ? absint( $_POST['activity_id'] ) : 0;
		$content     = isset( $_POST['content'] ) ? wp_kses_post( wp_unslash( $_POST['content'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		if ( empty( $activity_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity ID is required.', 'buddyboss' ) ) );
		}

		if ( empty( trim( $content ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Comment content is required.', 'buddyboss' ) ) );
		}

		// Get the activity to determine the root activity ID.
		$activity = new BP_Activity_Activity( $activity_id );

		if ( empty( $activity->component ) ) {
			wp_send_json_error( array( 'message' => __( 'Activity not found.', 'buddyboss' ) ) );
		}

		// Determine root activity ID: if this is already a comment, use its item_id.
		$root_activity_id = $activity_id;
		if ( 'activity_comment' === $activity->type ) {
			$root_activity_id = (int) $activity->item_id;
		}

		$comment_id = bp_activity_new_comment(
			array(
				'activity_id' => $root_activity_id,
				'parent_id'   => $activity_id,
				'content'     => $content,
				'error_type'  => 'wp_error',
			)
		);

		if ( is_wp_error( $comment_id ) ) {
			wp_send_json_error( array( 'message' => $comment_id->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'message'    => __( 'Comment posted successfully.', 'buddyboss' ),
				'comment_id' => $comment_id,
			)
		);
	}
}

// Initialize.
new BB_Activity_Admin_Ajax();
