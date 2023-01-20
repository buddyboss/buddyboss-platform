<?php
/**
 * Subscriptions Functions.
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Migration BuddyBoss forums and topics subscriptions with background/non-background to new system.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $is_background The current process is background or not.
 * @param bool $is_updater    True when function is call from updater otherwise false.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_subscriptions_migrate_users_forum_topic( $is_background = false, $is_updater = false ) {
	global $wpdb, $bp_background_updater;

	$forum_key = $wpdb->prefix . '_bbp_forum_subscriptions';
	$topic_key = $wpdb->prefix . '_bbp_subscriptions';

	if ( $is_background ) {
		delete_site_option( 'bb_subscriptions_migrate_offset' );

		$offset  = get_site_option( 'bb_subscriptions_migrate_offset', 0 );
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT( u.ID ) FROM $wpdb->users AS u INNER JOIN $wpdb->usermeta AS um ON ( u.ID = um.user_id ) WHERE ( um.meta_key = %s OR um.meta_key = %s ) GROUP BY u.ID ORDER BY um.umeta_id ASC", $forum_key, $topic_key ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			$min_count = (int) apply_filters( 'bb_subscription_queue_min_count', 10 );

			if (
				count( $results ) > $min_count
			) {
				$chunk_results = array_chunk( $results, $min_count );
				if ( ! empty( $chunk_results ) ) {
					foreach ( $chunk_results as $chunk_result ) {
						$bp_background_updater->data(
							array(
								array(
									'callback' => 'bb_migrate_users_forum_topic_subscriptions',
									'args'     => array( $chunk_result, $offset, $is_background ),
								),
							)
						);

						$bp_background_updater->save();
					}
				}

				$bp_background_updater->dispatch();
			} else {
				bb_migrate_users_forum_topic_subscriptions( $results, $offset, $is_background );
			}
		}

		// Migrate bbpress forums/topics subscription to BuddyBoss new system.
		if ( $is_updater ) {
			bb_subscriptions_migrate_bbpress_users_forum_topic( $is_background );
		}
	} else {
		$offset  = get_site_option( 'bb_subscriptions_migrate_offset', 0 );
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT( u.ID ) FROM $wpdb->users AS u INNER JOIN $wpdb->usermeta AS um ON ( u.ID = um.user_id ) WHERE ( um.meta_key = %s OR um.meta_key = %s ) GROUP BY u.ID ORDER BY um.umeta_id ASC LIMIT %d OFFSET %d", $forum_key, $topic_key, 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			return bb_migrate_users_forum_topic_subscriptions( $results, $offset, $is_background );
		} else {
			delete_site_option( 'bb_subscriptions_migrate_offset' );

			if ( ! $is_background ) {
				/* translators: Status of current action. */
				$statement = __( 'Updating forum and discussion to new BuddyBoss subscription api&hellip; %s', 'buddyboss' );
				$result    = __( 'Complete!', 'buddyboss' );

				// All done!
				return array(
					'status'  => 1,
					'message' => sprintf( $statement, $result ),
				);
			}
		}
	}
}

/**
 * Callback function to migrate BuddyBoss forums and topics subscriptions to new system.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $subscription_users Array of user subscriptions.
 * @param int   $offset             Offset value.
 * @param bool  $is_background      The current process is background or not.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_migrate_users_forum_topic_subscriptions( $subscription_users, $offset = 0, $is_background = true ) {
	global $wpdb;

	$subscription_tbl  = BP_Subscriptions::get_subscription_tbl();
	$forum_post_type   = function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
	$topic_post_type   = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );
	$spam_post_type    = function_exists( 'bbp_get_spam_status_id' ) ? bbp_get_spam_status_id() : apply_filters( 'bbp_spam_post_status', 'spam' );
	$trash_post_type   = function_exists( 'bbp_get_trash_status_id' ) ? bbp_get_trash_status_id() : apply_filters( 'bbp_trash_post_status', 'trash' );
	$pending_post_type = function_exists( 'bbp_get_pending_status_id' ) ? bbp_get_pending_status_id() : apply_filters( 'bbp_pending_post_status', 'pending' );
	$blog_id           = get_current_blog_id();

	if ( ! empty( $subscription_users ) ) {
		foreach ( $subscription_users as $user_id ) {

			// Increment the current offset.
			$offset ++;

			$place_holder_queries = array();

			$insert_query = "INSERT INTO {$subscription_tbl} ( blog_id, user_id, type, item_id, secondary_item_id, status, date_recorded ) VALUES";

			$form_key            = $wpdb->prefix . '_bbp_forum_subscriptions';
			$forum_subscriptions = get_user_meta( $user_id, $form_key, true );
			$forum_subscriptions = array_filter( wp_parse_id_list( $forum_subscriptions ) );
			if ( ! empty( $forum_subscriptions ) ) {
				foreach ( $forum_subscriptions as $forum_id ) {
					// Get the forum.
					$forum = get_post( $forum_id );

					if ( $forum_post_type !== $forum->post_type || empty( $forum->ID ) ) {
						continue;
					}

					$record_args = array(
						'user_id'           => (int) $user_id,
						'item_id'           => (int) $forum_id,
						'blog_id'           => (int) $blog_id,
						'secondary_item_id' => (int) $forum->post_parent,
						'type'              => 'forum',
						'count'             => false,
						'cache'             => false,
						'bypass_moderation' => true,
					);

					// Get subscription from new table.
					$subscription_exists = BP_Subscriptions::get( $record_args );

					if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
						continue;
					}

					$subscription_status = 1;
					if ( ! empty( $forum->post_status ) && in_array( $forum->post_status, array( $spam_post_type, $trash_post_type, $pending_post_type ), true ) ) {
						$subscription_status = 0;
					}

					$place_holder_queries[] = $wpdb->prepare( '(%d, %d, %s, %d, %d, %d, %s)', $blog_id, $record_args['user_id'], $record_args['type'], $record_args['item_id'], $record_args['secondary_item_id'], $subscription_status, bp_core_current_time() );
				}
			}

			$topic_key           = $wpdb->prefix . '_bbp_subscriptions';
			$topic_subscriptions = get_user_meta( $user_id, $topic_key, true );
			$topic_subscriptions = array_filter( wp_parse_id_list( $topic_subscriptions ) );
			if ( ! empty( $topic_subscriptions ) ) {
				foreach ( $topic_subscriptions as $topic_id ) {
					// Get the topic.
					$topic = get_post( $topic_id );

					if ( $topic_post_type !== $topic->post_type || empty( $topic->ID ) ) {
						continue;
					}

					$record_args = array(
						'user_id'           => (int) $user_id,
						'item_id'           => (int) $topic_id,
						'blog_id'           => (int) $blog_id,
						'secondary_item_id' => (int) $topic->post_parent,
						'type'              => 'topic',
						'count'             => false,
						'cache'             => false,
						'bypass_moderation' => true,
					);

					// Get subscription from new table.
					$subscription_exists = BP_Subscriptions::get( $record_args );

					if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
						continue;
					}

					$subscription_status = 1;
					if ( ! empty( $topic->post_status ) && in_array( $topic->post_status, array( $spam_post_type, $trash_post_type, $pending_post_type ), true ) ) {
						$subscription_status = 0;
					}

					$place_holder_queries[] = $wpdb->prepare( '(%d, %d, %s, %d, %d, %d, %s)', $blog_id, $record_args['user_id'], $record_args['type'], $record_args['item_id'], $record_args['secondary_item_id'], $subscription_status, bp_core_current_time() );
				}
			}

			if ( ! empty( $place_holder_queries ) ) {
				$place_holder_queries = implode( ', ', $place_holder_queries );
				$wpdb->query( "{$insert_query} {$place_holder_queries}" ); // phpcs:ignore
			}

			// Update the migration offset.
			update_site_option( 'bb_subscriptions_migrate_offset', $offset );
		}
	}

	$latest_offset = get_site_option( 'bb_subscriptions_migrate_offset', 0 );

	if ( ! $is_background ) {
		$records_updated = sprintf(
		/* translators: total members */
			__( 'The BBPress forum and discussion subscriptions successfully migrated to BuddyBoss for %s members.', 'buddyboss' ),
			bp_core_number_format( $latest_offset )
		);

		return array(
			'status'  => 'running',
			'offset'  => $latest_offset,
			'records' => $records_updated,
		);
	} else {
		// Delete migration transient.
		delete_transient( 'bb_migrate_subscriptions' );
	}
}

/**
 * Migration bbpress forums and topics subscriptions with background/non-background to new system.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $is_background The current process is background or not.
 * @param int  $blog_id       The blog ID to migrate for this blog.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_subscriptions_migrate_bbpress_users_forum_topic( $is_background = false, $blog_id = 0 ) {
	$response = array();
	if ( is_multisite() ) {

		// Run migration for all site when it's run in background.
		if ( $is_background && ! $blog_id ) {

			// Get all blog sites.
			$sites = get_sites();

			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				bb_subscriptions_migrating_bbpress_users_subscriptions( $is_background, $site->blog_id );
				restore_current_blog();
			}
		} else {
			$switch = false;

			// Switch to given blog_id if current blog is not same.
			if ( get_current_blog_id() !== $blog_id ) {
				switch_to_blog( $blog_id );
				$switch = true;
			}

			$response = bb_subscriptions_migrating_bbpress_users_subscriptions( $is_background, $blog_id );

			// Restore current blog.
			if ( $switch ) {
				restore_current_blog();
			}
		}
	} else {
		if ( ! $blog_id ) {
			$blog_id = get_current_blog_id();
		}
		$response = bb_subscriptions_migrating_bbpress_users_subscriptions( $is_background, $blog_id );
	}

	// Return the response if background process is false.
	if ( ! $is_background ) {
		return $response;
	}
}

/**
 * Processing to migration bbpress forums and topics subscriptions with background/non-background to new system.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $is_background The current process is background or not.
 * @param int  $blog_id       The blog ID to migrate for this blog.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_subscriptions_migrating_bbpress_users_subscriptions( $is_background = false, $blog_id = 0 ) {
	global $wpdb, $bp_background_updater;

	$subscription_key = '_bbp_subscription';
	$forum_post_type  = function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
	$topic_post_type  = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );

	if ( $is_background ) {
		delete_site_option( 'bb_subscriptions_migrate_bbpress_offset' );

		$offset  = get_site_option( 'bb_subscriptions_migrate_bbpress_offset', 0 );
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} mt ON mt.post_id = p.ID WHERE mt.meta_key = %s AND ( p.post_type = %s OR p.post_type = %s ) GROUP BY p.ID ORDER BY p.ID ASC", $subscription_key, $forum_post_type, $topic_post_type ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			$min_count = (int) apply_filters( 'bb_subscription_queue_min_count', 10 );

			if (
				count( $results ) > $min_count
			) {
				$chunk_results = array_chunk( $results, $min_count );
				if ( ! empty( $chunk_results ) ) {
					foreach ( $chunk_results as $chunk_result ) {
						$bp_background_updater->data(
							array(
								array(
									'callback' => 'bb_migrate_bbpress_users_post_subscriptions',
									'args'     => array( $chunk_result, $blog_id, $offset, $is_background ),
								),
							)
						);

						$bp_background_updater->save();
					}
				}

				$bp_background_updater->dispatch();
			} else {
				bb_migrate_bbpress_users_post_subscriptions( $results, $blog_id, $offset, $is_background );
			}
		}
	} else {
		$offset  = get_site_option( 'bb_subscriptions_migrate_bbpress_offset', 0 );
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} mt ON mt.post_id = p.ID WHERE mt.meta_key = %s AND ( p.post_type = %s OR p.post_type = %s ) GROUP BY p.ID ORDER BY p.ID ASC LIMIT %d OFFSET %d", $subscription_key, $forum_post_type, $topic_post_type, 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			return bb_migrate_bbpress_users_post_subscriptions( $results, $blog_id, $offset, $is_background );
		} else {
			delete_site_option( 'bb_subscriptions_migrate_bbpress_offset' );

			if ( ! $is_background ) {
				/* translators: Status of current action. */
				$statement = __( 'Migrating BBPress forum and discussion subscriptions to BuddyBoss&hellip; %s', 'buddyboss' );
				$result    = __( 'Complete!', 'buddyboss' );

				// All done!
				return array(
					'status'  => 1,
					'message' => sprintf( $statement, $result ),
				);
			}
		}
	}

}

/**
 * Callback function to migrate bbpress forums and topics subscriptions to new system.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $subscription_posts Array of user post subscriptions.
 * @param int   $blog_id            The blog ID to migrate for this blog.
 * @param int   $offset             Offset value.
 * @param bool  $is_background      The current process is background or not.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_migrate_bbpress_users_post_subscriptions( $subscription_posts, $blog_id = 0, $offset = 0, $is_background = true ) {
	global $wpdb;

	$subscription_tbl  = BP_Subscriptions::get_subscription_tbl();
	$forum_post_type   = function_exists( 'bbp_get_forum_post_type' ) ? bbp_get_forum_post_type() : apply_filters( 'bbp_forum_post_type', 'forum' );
	$topic_post_type   = function_exists( 'bbp_get_topic_post_type' ) ? bbp_get_topic_post_type() : apply_filters( 'bbp_topic_post_type', 'topic' );
	$spam_post_type    = function_exists( 'bbp_get_spam_status_id' ) ? bbp_get_spam_status_id() : apply_filters( 'bbp_spam_post_status', 'spam' );
	$trash_post_type   = function_exists( 'bbp_get_trash_status_id' ) ? bbp_get_trash_status_id() : apply_filters( 'bbp_trash_post_status', 'trash' );
	$pending_post_type = function_exists( 'bbp_get_pending_status_id' ) ? bbp_get_pending_status_id() : apply_filters( 'bbp_pending_post_status', 'pending' );

	if ( ! $blog_id ) {
		$blog_id = get_current_blog_id();
	}

	$switch = false;
	if ( is_multisite() && get_current_blog_id() !== $blog_id ) {
		switch_to_blog( $blog_id );
		$switch = true;
	}

	// Prepare query to insert subscriptions.
	$place_holder_queries = array();

	if ( ! empty( $subscription_posts ) ) {
		foreach ( $subscription_posts as $post_id ) {

			// Increment the current offset.
			$offset ++;

			// Get the forum.
			$post = get_post( $post_id );

			if ( ! in_array( $post->post_type, array( $forum_post_type, $topic_post_type ), true ) || empty( $post->ID ) ) {
				continue;
			}

			// Get subscription type by post type.
			$subscription_type = '';
			if ( $post->post_type === $forum_post_type ) {
				$subscription_type = 'forum';
			} elseif ( $post->post_type === $topic_post_type ) {
				$subscription_type = 'topic';
			}

			// Bail if subscription type is empty.
			if ( empty( $subscription_type ) ) {
				continue;
			}

			// Check the post status.
			$subscription_status = 1;
			if ( ! empty( $post->post_status ) && in_array( $post->post_status, array( $spam_post_type, $trash_post_type, $pending_post_type ), true ) ) {
				$subscription_status = 0;
			}

			// Get all subscribe users by post ID.
			$bbpress_subscriptions = get_post_meta( $post_id, '_bbp_subscription' );
			if ( ! empty( $bbpress_subscriptions ) ) {
				foreach ( $bbpress_subscriptions as $user_id ) {
					$record_args = array(
						'user_id'           => (int) $user_id,
						'item_id'           => (int) $post_id,
						'blog_id'           => (int) $blog_id,
						'secondary_item_id' => (int) $post->post_parent,
						'type'              => $subscription_type,
						'count'             => false,
						'cache'             => false,
						'bypass_moderation' => true,
					);

					// Get subscription from new table.
					$subscription_exists = BP_Subscriptions::get( $record_args );

					if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
						continue;
					}

					$place_holder_queries[] = $wpdb->prepare( '(%d, %d, %s, %d, %d, %d, %s)', $blog_id, $record_args['user_id'], $record_args['type'], $record_args['item_id'], $record_args['secondary_item_id'], $subscription_status, bp_core_current_time() );
				}
			}
		}
	}

	// Prepare query if it's not empty.
	if ( ! empty( $place_holder_queries ) ) {
		$place_holder_queries = implode( ', ', $place_holder_queries );
		$wpdb->query( "INSERT INTO {$subscription_tbl} ( blog_id, user_id, type, item_id, secondary_item_id, status, date_recorded ) VALUES {$place_holder_queries}" ); // phpcs:ignore
	}

	// Update the migration offset.
	update_site_option( 'bb_subscriptions_migrate_bbpress_offset', $offset );

	// Get latest offset.
	$latest_offset   = get_site_option( 'bb_subscriptions_migrate_bbpress_offset', 0 );
	$records_updated = sprintf(
	/* translators: total members */
		__( 'The BBPress forum and discussion subscriptions successfully migrated to BuddyBoss for %s forums/discussions.', 'buddyboss' ),
		bp_core_number_format( $latest_offset )
	);
	// Delete migration transient.
	delete_transient( 'bb_migrate_subscriptions' );

	// Restore current blog.
	if ( $switch ) {
		restore_current_blog();
	}

	// Return running process data if the process is not background.
	if ( ! $is_background ) {
		return array(
			'status'  => 'running',
			'offset'  => $latest_offset,
			'records' => $records_updated,
		);
	}
}

/**
 * Functions to get all registered subscription types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type type string.
 *
 * @return array Return subscription type if exists otherwise return all subscription types.
 */
function bb_register_subscriptions_types( $type = '' ) {

	$subscription_type = apply_filters( 'bb_register_subscriptions_types', array() );

	if ( ! empty( $type ) && isset( $subscription_type[ $type ] ) ) {
		return $subscription_type[ $type ];
	}

	return $subscription_type;
}

/**
 * Retrieve all registered subscription types.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param bool $singular Return the singular label if true otherwise plural.
 *
 * @return array
 */
function bb_get_subscriptions_types( $singular = false ) {
	$types                   = array();
	$all_subscriptions_types = bb_register_subscriptions_types();

	if ( ! bb_enabled_legacy_email_preference() && bp_is_active( 'notifications' ) ) {
		if ( ! empty( $all_subscriptions_types ) ) {
			foreach ( $all_subscriptions_types as $type ) {
				if ( bb_is_enabled_subscription( $type['subscription_type'] ) ) {
					$types[ $type['subscription_type'] ] = ( $singular ? $type['label']['singular'] : $type['label']['plural'] );
				}
			}
		}
	} else {
		if ( function_exists( 'bbp_is_subscriptions_active' ) && bbp_is_subscriptions_active() ) {
			$types['forum'] = ( $singular ? __( 'Forum', 'buddyboss' ) : __( 'Forums', 'buddyboss' ) );
			$types['topic'] = ( $singular ? __( 'Discussion', 'buddyboss' ) : __( 'Discussions', 'buddyboss' ) );
		}
	}

	return $types;
}

/**
 * Create user subscription.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of arguments.
 *     @type string        $type              The type subscription.
 *                                            'Forum', 'topic'.
 *     @type int          $blog_id            Optional. Get subscription site wise. Default current site ID.
 *     @type int          $user_id            The ID of the user who created the Subscription.
 *     @type int          $item_id            The ID of forum/topic.
 *     @type int          $secondary_item_id  ID of the parent forum/topic.
 * }
 *
 * @return int|bool|WP_Error
 */
function bb_create_subscription( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'blog_id'           => get_current_blog_id(),
			'type'              => '',
			'user_id'           => bp_loggedin_user_id(),
			'item_id'           => 0,
			'secondary_item_id' => 0,
			'date_recorded'     => bp_core_current_time(),
			'error_type'        => 'wp_error',
			'status'            => true,
		),
		'bb_create_subscription'
	);

	// Check if subscription is existed or not?.
	$subscriptions = BP_Subscriptions::get(
		array(
			'type'              => $r['type'],
			'blog_id'           => $r['blog_id'],
			'user_id'           => $r['user_id'],
			'item_id'           => $r['item_id'],
			'secondary_item_id' => $r['secondary_item_id'],
			'cache'             => false,
		)
	);

	if ( ! empty( $subscriptions['subscriptions'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			return new WP_Error(
				'bb_subscriptions_create_exists',
				__( 'The subscription is already exists.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			return false;
		}
	}

	$new_subscription                    = new BP_Subscriptions();
	$new_subscription->blog_id           = $r['blog_id'];
	$new_subscription->user_id           = $r['user_id'];
	$new_subscription->type              = $r['type'];
	$new_subscription->item_id           = $r['item_id'];
	$new_subscription->secondary_item_id = $r['secondary_item_id'];
	$new_subscription->date_recorded     = $r['date_recorded'];
	$new_subscription->error_type        = $r['error_type'];
	$new_subscription->status            = $r['status'];
	$new_subscription_created            = $new_subscription->save();

	// Return if not create a subscription.
	if ( is_wp_error( $new_subscription_created ) || ! $new_subscription_created ) {
		$error_message = is_wp_error( $new_subscription_created ) ? $new_subscription_created->get_error_message() : __( 'There is an error while adding the subscription.', 'buddyboss' );
		if ( 'wp_error' === $r['error_type'] ) {
			return new WP_Error(
				'bb_subscription_invalid_item_request',
				$error_message,
				array(
					'status' => 400,
				)
			);
		} else {
			return false;
		}
	}

	/**
	 * Fires after create a new subscription.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array             $r                        Array of argument to create a new subscription.
	 * @param int|bool|WP_Error $new_subscription_created The ID of new subscription when it's true otherwise return error.
	 */
	do_action_ref_array( 'bb_create_subscription', array( $r, $new_subscription_created ) );

	return $new_subscription_created;
}

/**
 * Retrieve user subscription by type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
 *                                            'Forum', 'topic'.
 *                                            Default: null.
 *     @type int          $user_id            Optional. If provided, results will be limited to subscriptions.
 *                                            Default: Current user ID.
 *     @type int          $blog_id            Optional. Get subscription site wise. Default current site ID.
 *     @type int          $per_page           Optional. Number of items to return per page of results.
 *                                            Default: null (no limit).
 * }
 * @param bool  $force_cache bypass the cache if true.
 *
 * @return array {
 *     @type array $subscriptions Array of subscription objects returned by the
 *                                paginated query. (IDs only if `fields` is set to `id`.)
 *     @type int   $total         Total count of all subscriptions matching non-
 *                                paginated query params.
 * }
 */
function bb_get_subscriptions( $args = array(), $force_cache = false ) {
	static $cache = array();

	$r = bp_parse_args(
		$args,
		array(
			'blog_id'  => get_current_blog_id(),
			'type'     => array(),
			'user_id'  => bp_loggedin_user_id(),
			'per_page' => null,
			'page'     => null,
			'count'    => false,
			'status'   => true,
		),
		'bb_get_subscriptions'
	);

	$cache_key = 'bb_get_subscriptions_' . md5( maybe_serialize( $r ) );
	if ( ! isset( $cache[ $cache_key ] ) || true === $force_cache ) {
		$subscriptions       = BP_Subscriptions::get( $r );
		$cache[ $cache_key ] = $subscriptions;
	} else {
		$subscriptions = $cache[ $cache_key ];
	}

	return $subscriptions;
}

/**
 * Retrieve all users by subscription type and item id.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of optional arguments.
 *     @type array|string $type               Optional. Array or comma-separated list of subscription types.
 *                                            'Forum', 'topic'.
 *                                            Default: null.
 *     @type int          $item_id            Optional. If provided, results will be limited to subscriptions.
 *                                            Default: null.
 *     @type int          $blog_id            Optional. Get subscription site wise. Default current site ID.
 *     @type int          $per_page           Optional. Number of items to return per page of results.
 *                                            Default: null (no limit).
 * }
 * @param bool  $force_cache bypass the cache if true.
 *
 * @return array {
 *     @type array $subscriptions Array of subscription objects returned by the
 *                                paginated query. (IDs only if `fields` is set to `id`.)
 *     @type int   $total         Total count of all subscriptions matching non-
 *                                paginated query params.
 * }
 */
function bb_get_subscription_users( $args = array(), $force_cache = false ) {
	static $cache = array();

	$r = bp_parse_args(
		$args,
		array(
			'blog_id'  => get_current_blog_id(),
			'type'     => array(),
			'item_id'  => 0,
			'per_page' => null,
			'page'     => null,
			'fields'   => 'user_id',
			'count'    => true,
		),
		'bb_get_subscription_users'
	);

	$cache_key = 'bb_get_subscription_users_' . md5( maybe_serialize( $r ) );
	if ( ! isset( $cache[ $cache_key ] ) || true === $force_cache ) {
		$subscriptions       = BP_Subscriptions::get( $r );
		$cache[ $cache_key ] = $subscriptions;
	} else {
		$subscriptions = $cache[ $cache_key ];
	}

	return $subscriptions;
}

/**
 * Fetch a single subscription object.
 *
 * When calling up a subscription object, you should always use this function instead
 * of instantiating BP_Subscription directly, so that you will inherit cache
 * support and pass through the bb_subscriptions_get_subscription filter.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $subscription_id ID of the subscription.
 *
 * @return BP_Subscriptions $subscription The subscription object.
 */
function bb_subscriptions_get_subscription( $subscription_id ) {
	// Backward compatibility.
	if ( ! is_numeric( $subscription_id ) ) {
		$r = bp_parse_args(
			$subscription_id,
			array(
				'subscription_id' => false,
			),
			'subscriptions_get_subscription'
		);

		$subscription_id = $r['subscription_id'];
	}

	$subscription = new BP_Subscriptions( $subscription_id );

	/**
	 * Filters a single subscription object.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param BP_Subscriptions $subscription Single subscription object.
	 */
	return apply_filters( 'bb_subscriptions_get_subscription', $subscription );
}

/**
 * Update the subscription item.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Subscription type.
 * @param int    $item_id Subscription item ID.
 * @param int    $status  Subscription item status.
 * @param int    $blog_id Optional. Get subscription site wise. Default current site ID.
 *
 * @return bool True on success, false on failure.
 */
function bb_subscriptions_update_subscriptions_status( $type, $item_id, $status, $blog_id = 0 ) {
	return BP_Subscriptions::update_status( $type, $item_id, $status, $blog_id );
}

/**
 * Delete a subscription.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param int $subscription_id ID of the subscription to delete.
 *
 * @return bool True on success, false on failure.
 */
function bb_delete_subscription( $subscription_id ) {

	/**
	 * Fires before the deletion of a subscription.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $subscription_id ID of the subscription to be deleted.
	 */
	do_action( 'bb_subscriptions_before_delete_subscription', $subscription_id );

	// Get the subscription object.
	$subscription = bb_subscriptions_get_subscription( $subscription_id );

	// Bail if subscription cannot be deleted.
	if ( ! $subscription->delete() ) {
		return false;
	}

	/**
	 * Fires after the deletion of a subscription.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $subscription_id ID of the subscription that was deleted.
	 */
	do_action( 'bb_delete_subscription', $subscription_id );

	return true;
}

/**
 * Delete user subscriptions by item ID and type.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type    Type of the subscription to delete.
 * @param int    $item_id Item ID of the subscription to delete.
 * @param int    $blog_id Optional. Get subscription site wise. Default current site ID.
 *
 * @return bool True on success, false on failure.
 */
function bb_delete_item_subscriptions( $type, $item_id, $blog_id = 0 ) {

	if ( empty( $blog_id ) ) {
		$blog_id = get_current_blog_id();
	}

	// Get the subscriptions.
	$all_subscriptions = bb_get_subscription_users(
		array(
			'type'    => $type,
			'item_id' => $item_id,
			'blog_id' => $blog_id,
			'fields'  => 'id',
			'count'   => false,
		)
	);
	$subscriptions     = ! empty( $all_subscriptions['subscriptions'] ) ? $all_subscriptions['subscriptions'] : array();

	/**
	 * Fires before the deletion of subscriptions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $subscriptions Array of subscriptions to delete.
	 * @param string $type          Type of the subscription to delete.
	 * @param int    $item_id       Item ID of the subscription to delete.
	 */
	do_action( 'bb_subscriptions_before_delete_item_subscriptions', $subscriptions, $type, $item_id );

	if ( ! empty( $subscriptions ) ) {
		foreach ( $subscriptions as $subscription ) {
			bb_delete_subscription( $subscription );
		}
	}

	/**
	 * Fires after the deletion of subscriptions.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array  $subscriptions Array of subscriptions to delete.
	 * @param string $type          Type of the subscription to delete.
	 * @param int    $item_id       Item ID of the subscription to delete.
	 */
	do_action( 'bb_subscriptions_after_delete_item_subscriptions', $subscriptions, $type, $item_id );

	return true;
}

/**
 * Enabled modern subscriptions or not.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type              Optional. The type of subscription like 'forum', topic'.
 * @param string $notification_type The type of notification.
 *
 * @return bool
 */
function bb_is_enabled_modern_subscriptions( $type = '', $notification_type = '' ) {
	$is_enabled = false;

	if ( ! bb_enabled_legacy_email_preference() ) {
		switch ( $type ) {
			case 'topic':
			case 'forum':
				$is_enabled = function_exists( 'bbp_is_subscriptions_active' ) && true === bbp_is_subscriptions_active();
				break;
			case 'group':
				$is_enabled = function_exists( 'bb_enable_group_subscriptions' ) && true === bb_enable_group_subscriptions();
				break;
			default:
				if ( ! empty( $notification_type ) ) {
					$is_enabled = bb_get_modern_notification_admin_settings_is_enabled( $notification_type );
				}
				break;
		}
	}

	return (bool) apply_filters( 'bb_is_enabled_modern_subscriptions', $is_enabled, $type, $notification_type );
}

/**
 * Check the particular subscription is enabled or not for modern or legacy.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param string $type              The type of subscription like 'forum', topic'.
 * @param string $notification_type The type of notification.
 *
 * @return bool
 */
function bb_is_enabled_subscription( $type, $notification_type = '' ) {
	$is_enabled = false;

	if ( ! bb_enabled_legacy_email_preference() ) {
		$is_enabled = bb_is_enabled_modern_subscriptions( $type, $notification_type );
	} elseif (
		( bb_enabled_legacy_email_preference() || ! bp_is_active( 'notifications' ) ) &&
		in_array( $type, array( 'forum', 'topic' ), true ) &&
		function_exists( 'bbp_is_subscriptions_active' ) && bbp_is_subscriptions_active()
	) {
		$is_enabled = true;
	}

	return (bool) apply_filters( 'bb_is_enabled_subscription', $is_enabled, $type, $notification_type );
}

/**
 * Trigger subscription notifications.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of arguments.
 *     @type string       $type               Required. The type of subscription.
 *                                            'forum', 'topic'.
 *                                            Default: null.
 *     @type int          $item_id            Required. The ID of item.
 *                                            Default: null.
 *     @type int          $blog_id            Optional. Get subscription site wise. Default current site ID.
 *     @type array        $data               Optional. Additional data for notification.
 * }
 *
 * @return void
 */
function bb_send_notifications_to_subscribers( $args ) {
	$r = bp_parse_args(
		$args,
		array(
			'type'    => '',
			'item_id' => 0,
			'blog_id' => get_current_blog_id(),
			'data'    => array(),
		)
	);

	$type    = $r['type'];
	$item_id = $r['item_id'];
	$blog_id = $r['blog_id'];

	if ( empty( $type ) || empty( $item_id ) ) {
		return;
	}

	$type_data = bb_register_subscriptions_types( $type );

	if (
		empty( $type_data ) ||
		empty( $type_data['notification_type'] ) ||
		empty( $type_data['send_callback'] ) ||
		! is_callable( $type_data['send_callback'] )
	) {
		return;
	}

	$notification_type = $type_data['notification_type'];
	$send_callback     = $type_data['send_callback'];
	$subscriptions     = bb_get_subscription_users(
		array(
			'type'    => $type,
			'item_id' => $item_id,
			'blog_id' => $blog_id,
			'status'  => true,
		)
	);

	if ( empty( $subscriptions['subscriptions'] ) ) {
		return;
	}

	$min_count = (int) apply_filters( 'bb_subscription_queue_min_count', 20 );

	$parse_args = array(
		'type'              => $type,
		'item_id'           => $item_id,
		'blog_id'           => $blog_id,
		'data'              => $r['data'],
		'notification_type' => $notification_type,
	);

	if (
		isset( $subscriptions['total'] ) &&
		$subscriptions['total'] > $min_count
	) {
		global $bp_background_updater;
		$chunk_user_ids = array_chunk( $subscriptions['subscriptions'], $min_count );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $key => $user_ids ) {
				$parse_args['user_ids'] = $user_ids;
				$bp_background_updater->data(
					array(
						array(
							'callback' => $send_callback,
							'args'     => array( $parse_args ),
						),
					)
				);

				$bp_background_updater->save();
			}
		}

		$bp_background_updater->dispatch();
	} else {
		$parse_args['user_ids'] = $subscriptions['subscriptions'];
		call_user_func(
			$send_callback,
			$parse_args
		);
	}
}
