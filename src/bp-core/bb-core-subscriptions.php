<?php
/**
 * Subscriptions Functions.
 *
 * @since   BuddyBoss 2.2.6
 * @package BuddyBoss\Core
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Migration BuddyBoss forums and topics subscriptions with background/non-background to new system.
 *
 * @since BuddyBoss 2.2.6
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

		$offset = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $offset ) ) {
			$offset = -- $offset;
		} else {
			$offset = 0;
		}

		if ( 0 === $offset ) {
			$subscription_tbl = BB_Subscriptions::get_subscription_tbl();
			// phpcs:ignore
			$wpdb->query( "DELETE FROM {$subscription_tbl} WHERE type IN ( 'topic', 'forum' )" );

			// Flush the cache to delete all old cached subscriptions.
			wp_cache_flush();
		}
		$results = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT( u.ID ) FROM $wpdb->users AS u INNER JOIN $wpdb->usermeta AS um ON ( u.ID = um.user_id ) WHERE ( um.meta_key = %s OR um.meta_key = %s ) GROUP BY u.ID ORDER BY um.umeta_id ASC LIMIT %d OFFSET %d", $forum_key, $topic_key, 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			return bb_migrate_users_forum_topic_subscriptions( $results, $offset, $is_background );
		} else {
			delete_site_option( 'bb_subscriptions_migrate_offset' );

			if ( ! $is_background ) {
				/* translators: Status of current action. */
				$statement = __( 'Migrating BBPress (up to v2.5.14) forum and discussion subscriptions to BuddyBoss&hellip; %s', 'buddyboss' );
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
 * @since BuddyBoss 2.2.6
 *
 * @param array $subscription_users Array of user subscriptions.
 * @param int   $offset             Offset value.
 * @param bool  $is_background      The current process is background or not.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_migrate_users_forum_topic_subscriptions( $subscription_users, $offset = 0, $is_background = true ) {
	global $wpdb;

	$subscription_tbl  = BB_Subscriptions::get_subscription_tbl();
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

					// Skip when the post row is gone (orphaned subscription
					// pointing at a deleted forum) OR the row exists but is
					// an auto-draft/stub with no ID. The `! $forum` check
					// runs FIRST to avoid fatalling on `null->post_type` on
					// PHP 8+ — the original `empty( $forum->ID )` clause
					// alone could never have run because PHP would already
					// have raised a TypeError on the property access.
					if ( ! $forum || empty( $forum->ID ) || $forum_post_type !== $forum->post_type ) {
						continue;
					}

					// Check if forum is group forum or not?
					$group_ids = function_exists( 'bbp_get_forum_group_ids' ) ? bbp_get_forum_group_ids( $forum_id ) : array();
					if ( ! empty( $group_ids ) ) {
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
					$subscription_exists = BB_Subscriptions::get( $record_args );

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

					// Skip when the post row is gone (orphaned subscription
					// pointing at a deleted topic) or its ID is empty
					// (auto-draft / stub row). See the matching guard in
					// the forum-subscriptions loop above for the ordering
					// rationale.
					if ( ! $topic || empty( $topic->ID ) || $topic_post_type !== $topic->post_type ) {
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
					$subscription_exists = BB_Subscriptions::get( $record_args );

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

				wp_cache_flush();

				// Purge all the cache for API.
				if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
					BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bb-subscriptions' );
				}
			}

			if ( true === $is_background ) {
				// Update the migration offset.
				update_site_option( 'bb_subscriptions_migrate_offset', $offset );
			}
		}
	}

	$latest_offset = ( true === $is_background ? get_site_option( 'bb_subscriptions_migrate_offset', 0 ) : $offset + 1 );

	if ( ! $is_background ) {
		$records_updated = sprintf(
		/* translators: total members */
			__( 'The BBPress (up to v2.5.14) forum and discussion subscriptions successfully migrated to BuddyBoss for %s members.', 'buddyboss' ),
			bp_core_number_format( $latest_offset - 1 )
		);

		return array(
			'status'  => 'running',
			'offset'  => $latest_offset,
			'records' => $records_updated,
		);
	}
}

/**
 * Migration bbpress forums and topics subscriptions with background/non-background to new system.
 *
 * @since BuddyBoss 2.2.6
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
 * @since BuddyBoss 2.2.6
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

		$offset = filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );
		if ( ! empty( $offset ) ) {
			$offset = -- $offset;
		} else {
			$offset = 0;
		}

		$results = $wpdb->get_col( $wpdb->prepare( "SELECT p.ID FROM {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} mt ON mt.post_id = p.ID WHERE mt.meta_key = %s AND ( p.post_type = %s OR p.post_type = %s ) GROUP BY p.ID ORDER BY p.ID ASC LIMIT %d OFFSET %d", $subscription_key, $forum_post_type, $topic_post_type, 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! empty( $results ) ) {
			return bb_migrate_bbpress_users_post_subscriptions( $results, $blog_id, $offset, $is_background );
		} else {

			if ( ! $is_background ) {
				/* translators: Status of current action. */
				$statement = __( 'Migrating BBPress (v2.6+) forum and discussion subscriptions to BuddyBoss&hellip; %s', 'buddyboss' );
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
 * @since BuddyBoss 2.2.6
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

	$subscription_tbl  = BB_Subscriptions::get_subscription_tbl();
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

			// Skip when the post row is gone (deleted between when the
			// subscription was recorded and this migration run) or its
			// ID is empty. The `! $post` check has to come BEFORE any
			// property access — the original `empty( $post->ID )` clause
			// alone was unreachable because PHP would fatal on
			// `null->post_type` first.
			if ( ! $post || empty( $post->ID ) || ! in_array( $post->post_type, array( $forum_post_type, $topic_post_type ), true ) ) {
				continue;
			}

			// Get subscription type by post type.
			$subscription_type = '';
			$forum_id          = '';
			if ( $post->post_type === $forum_post_type ) {
				$subscription_type = 'forum';

				// Get forum id.
				$forum_id = $post_id;

			} elseif ( $post->post_type === $topic_post_type ) {
				$subscription_type = 'topic';

				// Get forum id.
				$forum_id = function_exists( 'bbp_get_topic_forum_id' ) ? bbp_get_topic_forum_id( $post_id ) : $post->post_parent;
			}

			// Bail if subscription type is empty.
			if ( empty( $subscription_type ) || empty( $forum_id ) ) {
				continue;
			}

			// Check if forum is group forum or not?
			if ( $post->post_type === $forum_post_type ) {
				$group_ids = function_exists( 'bbp_get_forum_group_ids' ) ? bbp_get_forum_group_ids( $forum_id ) : array();
				if ( ! empty( $group_ids ) ) {
					continue;
				}
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

					// Insert into the usermeta for backward compatibility.
					if ( function_exists( 'bb_forum_legacy_subscription' ) ) {
						bb_forum_legacy_subscription()->bb_create_legacy_forum_subscriptions(
							array(
								'type'    => $subscription_type,
								'user_id' => (int) $user_id,
								'item_id' => (int) $post_id,
								'blog_id' => (int) $blog_id,
							)
						);
					}

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
					$subscription_exists = BB_Subscriptions::get( $record_args );

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

		wp_cache_flush();

		// Purge all the cache for API.
		if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
			BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bb-subscriptions' );
		}
	}

	if ( true === $is_background ) {
		// Update the migration offset.
		update_site_option( 'bb_subscriptions_migrate_bbpress_offset', $offset );
	}

	// Get latest offset.
	$latest_offset   = ( true === $is_background ? get_site_option( 'bb_subscriptions_migrate_bbpress_offset', 0 ) : $offset + 1 );
	$records_updated = sprintf(
	/* translators: total members */
		__( 'The total %s BBPress (v2.6+) forum and discussion subscriptions successfully migrated to BuddyBoss.', 'buddyboss' ),
		bp_core_number_format( $latest_offset - 1 )
	);

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
 * @since BuddyBoss 2.2.6
 *
 * @param string $type type string.
 *
 * @return array Return subscription type if exists otherwise return all subscription types.
 */
function bb_register_subscriptions_types( $type = '' ) {

	$subscription_type = apply_filters( 'bb_register_subscriptions_types', array() );

	if ( ! empty( $type ) ) {
		return isset( $subscription_type[ $type ] ) ? $subscription_type[ $type ] : array();
	}

	return $subscription_type;
}

/**
 * Retrieve all registered subscription types.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param bool $singular Return the singular label if true otherwise plural.
 *
 * @return array
 */
function bb_get_subscriptions_types( $singular = false ) {
	$types                   = array();
	$all_subscriptions_types = bb_register_subscriptions_types();

	if ( ! bb_enabled_legacy_email_preference() ) {
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
 * @since BuddyBoss 2.2.6
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
			'id'                => 0,
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
	$subscriptions = BB_Subscriptions::get(
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
		if ( empty( $r['id'] ) ) {
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
		} else {
			$subscription = current( $subscriptions['subscriptions'] );
			if ( ! empty( $subscription ) && $r['id'] !== $subscription->id ) {
				if ( 'wp_error' === $r['error_type'] ) {
					return new WP_Error(
						'bb_subscriptions_id_not_match',
						__( 'The subscription ID is not match.', 'buddyboss' ),
						array(
							'status' => 400,
						)
					);
				} else {
					return false;
				}
			}
		}
	} elseif ( ! empty( $r['id'] ) ) {
		if ( 'wp_error' === $r['error_type'] ) {
			return new WP_Error(
				'bb_subscriptions_not_found',
				__( 'The subscription does\'t exists.', 'buddyboss' ),
				array(
					'status' => 400,
				)
			);
		} else {
			return false;
		}
	}

	$new_subscription                    = new BB_Subscriptions();
	$new_subscription->id                = $r['id'];
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
	 * @since BuddyBoss 2.2.6
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
 * @since BuddyBoss 2.2.6
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
		$subscriptions       = BB_Subscriptions::get( $r );
		$cache[ $cache_key ] = $subscriptions;
	} else {
		$subscriptions = $cache[ $cache_key ];
	}

	return $subscriptions;
}

/**
 * Retrieve all users by subscription type and item id.
 *
 * @since BuddyBoss 2.2.6
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
		$subscriptions       = BB_Subscriptions::get( $r );
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
 * @since BuddyBoss 2.2.6
 *
 * @param int $subscription_id ID of the subscription.
 *
 * @return BB_Subscriptions $subscription The subscription object.
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

	$subscription = new BB_Subscriptions( $subscription_id );

	/**
	 * Filters a single subscription object.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param BB_Subscriptions $subscription Single subscription object.
	 */
	return apply_filters( 'bb_subscriptions_get_subscription', $subscription );
}

/**
 * Update the subscription item.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param string $type    Subscription type.
 * @param int    $item_id Subscription item ID.
 * @param int    $status  Subscription item status.
 * @param int    $blog_id Optional. Get subscription site wise. Default current site ID.
 *
 * @return bool True on success, false on failure.
 */
function bb_subscriptions_update_subscriptions_status( $type, $item_id, $status, $blog_id = 0 ) {
	return BB_Subscriptions::update_status( $type, $item_id, $status, $blog_id );
}

/**
 * Delete a subscription.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param int $subscription_id ID of the subscription to delete.
 *
 * @return bool True on success, false on failure.
 */
function bb_delete_subscription( $subscription_id ) {

	/**
	 * Fires before the deletion of a subscription.
	 *
	 * @since BuddyBoss 2.2.6
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
	 * @since BuddyBoss 2.2.6
	 *
	 * @param int $subscription_id ID of the subscription that was deleted.
	 */
	do_action( 'bb_delete_subscription', $subscription_id );

	return true;
}

/**
 * Delete user subscriptions by item ID and type.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param string $type    Type of the subscription to delete.
 * @param int    $item_id Item ID of the subscription to delete.
 * @param int    $blog_id Optional. Get subscription site wise. Default current site ID.
 *
 * @return bool True on success, false on failure.
 */
function bb_delete_subscriptions_by_item( $type, $item_id, $blog_id = 0 ) {

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
		),
		true
	);
	$subscriptions     = ! empty( $all_subscriptions['subscriptions'] ) ? $all_subscriptions['subscriptions'] : array();

	/**
	 * Fires before the deletion of subscriptions.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array  $subscriptions Array of subscriptions to delete.
	 * @param string $type          Type of the subscription to delete.
	 * @param int    $item_id       Item ID of the subscription to delete.
	 * @param int    $blog_id       Site ID.
	 */
	do_action( 'bb_subscriptions_before_delete_item_subscriptions', $subscriptions, $type, $item_id, $blog_id );

	if ( ! empty( $subscriptions ) ) {
		foreach ( $subscriptions as $subscription ) {
			bb_delete_subscription( $subscription );
		}
	}

	/**
	 * Fires after the deletion of subscriptions.
	 *
	 * @since BuddyBoss 2.2.6
	 *
	 * @param array  $subscriptions Array of subscriptions to delete.
	 * @param string $type          Type of the subscription to delete.
	 * @param int    $item_id       Item ID of the subscription to delete.
	 * @param int    $blog_id       Site ID.
	 */
	do_action( 'bb_subscriptions_after_delete_item_subscriptions', $subscriptions, $type, $item_id, $blog_id );

	return true;
}

/**
 * Check the particular subscription is enabled or not for modern or legacy.
 *
 * @since BuddyBoss 2.2.6
 *
 * @param string $type              The type of subscription like 'forum', topic'.
 * @param string $notification_type The type of notification.
 *
 * @return bool
 */
function bb_is_enabled_subscription( $type, $notification_type = '' ) {
	if ( empty( $type ) ) {
		return false;
	}

	$is_enabled = false;
	if ( ! bb_enabled_legacy_email_preference() ) {
		switch ( $type ) {
			case 'topic':
			case 'forum':
				$is_enabled = function_exists( 'bbp_is_subscriptions_active' ) && true === bbp_is_subscriptions_active();
				break;
			case 'group':
				$is_enabled = (
					bp_is_active( 'notifications' ) &&
					function_exists( 'bb_enable_group_subscriptions' ) &&
					true === bb_enable_group_subscriptions() &&
					! empty( bb_register_subscriptions_types( 'group' ) )
				);
				break;
			default:
				if ( ! empty( $notification_type ) ) {
					$is_enabled = bb_get_modern_notification_admin_settings_is_enabled( $notification_type );
				}
				break;
		}
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
 * @since BuddyBoss 2.2.6
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
			'type'              => '',
			'item_id'           => 0,
			'notification_from' => '',
			'blog_id'           => get_current_blog_id(),
			'data'              => array(),
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

	/**
	 * Filters the subscriber count above which the fan-out itself moves to the background.
	 *
	 * Above this threshold the subscriber list is never fetched inside the
	 * originating request; a single background job paginates through the list
	 * instead. Keeps request memory flat for very large groups/forums.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int   $min_count Subscriber count threshold. Default 1000.
	 * @param array $r         Parsed arguments of the notification request.
	 */
	$fanout_min_count = (int) apply_filters( 'bb_subscription_background_fanout_min_count', 1000, $r );

	// One bounded fetch decides the delivery strategy. For very large subscriber
	// lists (e.g. a 70k-member group) fetching every subscriber inside the
	// originating web request exhausts PHP memory, so the page is capped at the
	// threshold: when the total fits under it this page IS the complete list and
	// is used as-is below; when it does not, the page is discarded and the
	// background worker paginates instead.
	$subscriptions = bb_get_subscription_users(
		array(
			'type'     => $type,
			'item_id'  => $item_id,
			'blog_id'  => $blog_id,
			'status'   => true,
			'per_page' => max( 1, $fanout_min_count ),
			'page'     => 1,
			// Explicit: the total is what picks the strategy. A filtered
			// count=>false would drop it; the fallback below then assumes a
			// large list rather than misreading a capped page as the total.
			'count'    => true,
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
		'notification_from' => $r['notification_from'],
	);

	$usernames = array();
	if ( ! empty( $r['data']['email_tokens'] ) && ! empty( $r['data']['email_tokens']['tokens'] ) ) {
		if ( ! empty( $r['data']['email_tokens']['tokens']['reply.content'] ) ) {
			$usernames = bp_find_mentions_by_at_sign( array(), $r['data']['email_tokens']['tokens']['reply.content'] );
		}
		if ( ! empty( $r['data']['email_tokens']['tokens']['discussion.content'] ) ) {
			$usernames = bp_find_mentions_by_at_sign( array(), $r['data']['email_tokens']['tokens']['discussion.content'] );
		}
	}
	if ( ! empty( $usernames ) ) {
		$parse_args['usernames'] = $usernames;
	}

	// Unknown total (count arg overridden by a third-party parse_args filter):
	// assume large. The page is capped at the threshold, so counting it cannot
	// tell a huge list from a full page; the background path is always safe.
	$total_subscribers = isset( $subscriptions['total'] ) ? (int) $subscriptions['total'] : PHP_INT_MAX;

	global $bb_background_updater;

	if ( $total_subscribers > $fanout_min_count ) {

		/**
		 * Filters the number of subscribers fetched per page by the background fan-out worker.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int   $per_page Subscribers fetched per background page. Default 1000.
		 * @param array $r        Parsed arguments of the notification request.
		 */
		$fanout_per_page = max( 1, (int) apply_filters( 'bb_subscription_background_fanout_per_page', 1000, $r ) );

		$fanout_args             = $parse_args;
		$fanout_args['last_id']  = 0;
		$fanout_args['per_page'] = $fanout_per_page;

		// The send callbacks rebuild the activity from data.activity_id; drop the
		// serialized object copy so the queue rows stay small.
		if ( isset( $fanout_args['data']['email_tokens']['tokens']['activity'] ) && is_object( $fanout_args['data']['email_tokens']['tokens']['activity'] ) ) {
			unset( $fanout_args['data']['email_tokens']['tokens']['activity'] );
		}

		$bb_background_updater->data(
			array(
				'type'     => 'notification',
				'group'    => 'send_notifications_to_subscribers',
				'data_id'  => $item_id,
				'priority' => 5,
				'callback' => 'bb_send_notifications_to_subscribers_batch',
				'args'     => array( $fanout_args ),
			)
		);
		$bb_background_updater->save();
		$bb_background_updater->dispatch();

		return;
	}

	// Small list: the total fits under the threshold, so the page fetched above
	// already holds every subscriber.
	$background_process = false;
	if (
		isset( $subscriptions['total'] ) &&
		1 < $subscriptions['total']
	) {
		$background_process = true;
	}

	if ( true === $background_process ) {
		// The queued send jobs rebuild the activity from data.activity_id; drop the
		// serialized object copy so each queue row stays small. The direct-call path
		// below keeps the object (no serialization involved).
		if ( isset( $parse_args['data']['email_tokens']['tokens']['activity'] ) && is_object( $parse_args['data']['email_tokens']['tokens']['activity'] ) ) {
			unset( $parse_args['data']['email_tokens']['tokens']['activity'] );
		}

		$chunk_user_ids = array_chunk( $subscriptions['subscriptions'], $min_count );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $user_ids ) {
				$parse_args['user_ids'] = $user_ids;

				$bb_background_updater->data(
					array(
						'type'     => 'notification',
						'group'    => 'send_notifications_to_subscribers',
						'data_id'  => $item_id,
						'priority' => 5,
						'callback' => $send_callback,
						'args'     => array( $parse_args ),
					),
				);

				$bb_background_updater->save();
			}
		}

		$bb_background_updater->dispatch();
	} else {
		$parse_args['user_ids'] = $subscriptions['subscriptions'];
		call_user_func(
			$send_callback,
			$parse_args
		);
	}

}

/**
 * Remove forum and topic subscriptions that assign to the group.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int $group_id The ID of group.
 *
 * @return void
 */
function bb_delete_group_forum_topic_subscriptions( $group_id ) {
	global $wpdb;

	if ( ! empty( $group_id ) && bp_is_active( 'forums' ) ) {
		$subscription_tbl = BB_Subscriptions::get_subscription_tbl();
		$forum_ids        = bbp_get_group_forum_ids( $group_id );
		$child_forums     = array();
		$blog_id          = get_current_blog_id();

		if ( ! empty( $forum_ids ) ) {
			foreach ( $forum_ids as $forum_id ) {
				$get_child_forums = bb_get_all_nested_subforums( $forum_id );
				if ( ! empty( $get_child_forums ) ) {
					$child_forums = array_merge( $child_forums, $get_child_forums );
				}
			}
		}

		// Merge all forums.
		$forum_ids = array_merge( $forum_ids, $child_forums );

		// Delete the group forum subscriptions.
		if ( ! empty( $forum_ids ) ) {
			$forum_ids = implode( ',', array_filter( wp_parse_id_list( $forum_ids ) ) );
			$wpdb->query( "DELETE FROM {$subscription_tbl} WHERE item_id IN ({$forum_ids}) AND type = 'forum' AND blog_id = {$blog_id}" ); // phpcs:ignore
		}

		// Clear subscription cache.
		if (
			function_exists( 'wp_cache_flush_group' ) &&
			function_exists( 'wp_cache_supports' ) &&
			wp_cache_supports( 'flush_group' )
		) {
			wp_cache_flush_group( 'bbpress_users' );
			wp_cache_flush_group( 'bb_subscriptions' );
		} else {
			wp_cache_flush();
		}
		bp_core_reset_incrementor( 'bb_subscriptions' );

		// Purge all the cache for API.
		if ( class_exists( 'BuddyBoss\Performance\Cache' ) ) {
			BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bb-subscriptions' );
			BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbp-forums' );
			BuddyBoss\Performance\Cache::instance()->purge_by_component( 'bbp-topics' );
		}
	}
}

/**
 * Migrate group subscription when update the platform to the latest version.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param bool $is_background Migration run in background or not.
 *
 * @return array|void Return array when it called directly otherwise call recursively.
 */
function bb_migrate_group_subscription( $is_background = false ) {
	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	$offset = 1;
	if ( $is_background ) {
		$offset = get_site_option( 'bb_group_subscriptions_migrate_page', 1 );
	}

	$args = array(
		'fields'      => 'ids',
		'per_page'    => 10,
		'page'        => $offset,
		'show_hidden' => true,
	);

	if ( ! $is_background ) {
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		$args['meta_query'] = array(
			array(
				'key'     => 'bb_subscription_migrated_v2',
				'compare' => 'NOT EXISTS',
			),
		);
	}

	$groups = groups_get_groups( $args );

	$all_groups = array();
	if ( ! empty( $groups['groups'] ) ) {
		$all_groups = $groups['groups'];
	}

	if ( ! empty( $all_groups ) ) {
		bb_migrating_group_member_subscriptions( $all_groups, $is_background );
		if ( ! $is_background ) {
			$total = ( (int) get_site_option( 'bb_group_subscriptions_migrated_count', 0 ) + count( $all_groups ) );
			update_site_option( 'bb_group_subscriptions_migrated_count', $total );

			$records_updated = sprintf(
			/* translators: total topics */
				_n( '%d group forum and discussion subscriptions migrated successfully', '%d groups forum and discussion subscriptions migrated successfully', bp_core_number_format( $total ), 'buddyboss' ),
				bp_core_number_format( $total )
			);

			return array(
				'status'  => 'running',
				'offset'  => $offset,
				'records' => $records_updated,
			);
		}
	} else {

		delete_site_option( 'bb_group_subscriptions_migrate_page' );
		delete_site_option( 'bb_group_subscriptions_migrated_count' );

		/* translators: Status of current action. */
		$statement = __( 'Migrating Group forum and discussion subscriptions data structure to the new subscription flow&hellip; %s', 'buddyboss' );

		// All done!
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Migrating group subscription and remove group forums and topics subscriptions.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param array $groups        Array of group IDs.
 * @param bool  $is_background Migration run in background or not.
 *
 * @return bool|void Return array when it called directly otherwise run in background.
 */
function bb_migrating_group_member_subscriptions( $groups = array(), $is_background = false ) {
	global $wpdb, $bp_background_updater;

	if ( empty( $groups ) ) {
		delete_site_option( 'bb_group_subscriptions_migrate_page' );
		delete_site_option( 'bb_group_subscriptions_migrated_count' );
		return;
	}

	$bp = buddypress();
	if ( ! empty( $groups ) ) {
		foreach ( $groups as $group_id ) {

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$member_ids = $wpdb->get_col(
				$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT DISTINCT user_id FROM {$bp->groups->table_name_members} WHERE group_id = %d AND is_confirmed = %d AND is_banned = %d",
					$group_id,
					1,
					0
				)
			);

			if ( ! empty( $member_ids ) ) {
				$min_count = (int) apply_filters( 'bb_subscription_queue_min_count', 20 );
				if ( $is_background && count( $member_ids ) > $min_count ) {
					$chunk_results = array_chunk( $member_ids, $min_count );
					if ( ! empty( $chunk_results ) ) {
						foreach ( $chunk_results as $chunk_result ) {
							$bp_background_updater->data(
								array(
									array(
										'callback' => 'bb_create_group_member_subscriptions',
										'args'     => array( $group_id, $chunk_result ),
									),
								)
							);

							$bp_background_updater->save();
						}
					}

					$bp_background_updater->dispatch();
				} else {
					bb_create_group_member_subscriptions( $group_id, $member_ids );
				}
			}

			bb_delete_group_forum_topic_subscriptions( $group_id );
		}
	}

	if ( $is_background ) {
		// Update the migration offset.
		$page = ( (int) get_site_option( 'bb_group_subscriptions_migrate_page', 1 ) + 1 );
		update_site_option( 'bb_group_subscriptions_migrate_page', $page );

		// Call recursive until group not found.
		bb_migrate_group_subscription( $is_background );
	}
}

/**
 * Create group subscriptions for groups.
 *
 * @since BuddyBoss 2.2.8
 *
 * @param int   $group_id   The group ID.
 * @param array $member_ids Array of member IDs.
 *
 * @return void|bool
 */
function bb_create_group_member_subscriptions( $group_id = 0, $member_ids = array() ) {
	global $wpdb;

	if ( ! bp_is_active( 'groups' ) || empty( $group_id ) ) {
		return;
	}

	$subscription_tbl     = BB_Subscriptions::get_subscription_tbl();
	$place_holder_queries = array();
	$insert_query         = "INSERT INTO {$subscription_tbl} ( blog_id, user_id, type, item_id, secondary_item_id, status, date_recorded ) VALUES";

	if ( ! empty( $member_ids ) ) {
		foreach ( $member_ids as $member_id ) {

			$record_args = array(
				'user_id'           => (int) $member_id,
				'item_id'           => (int) $group_id,
				'blog_id'           => get_current_blog_id(),
				'type'              => 'group',
				'count'             => false,
				'cache'             => false,
				'bypass_moderation' => true,
			);

			// Get subscription from new table.
			$subscription_exists = BB_Subscriptions::get( $record_args );

			if ( ! empty( $subscription_exists ) && ! empty( $subscription_exists['subscriptions'] ) ) {
				continue;
			}

			$secondary_item_id = bp_get_parent_group_id( $group_id );

			$place_holder_queries[] = $wpdb->prepare( '(%d, %d, %s, %d, %d, %d, %s)', 1, $record_args['user_id'], $record_args['type'], $record_args['item_id'], $secondary_item_id, 1, bp_core_current_time() );
		}
	}

	if ( ! empty( $place_holder_queries ) ) {
		$place_holder_queries = implode( ', ', $place_holder_queries );
		$wpdb->query( "{$insert_query} {$place_holder_queries}" ); // phpcs:ignore
		unset( $place_holder_queries );
	}

	groups_update_groupmeta( $group_id, 'bb_subscription_migrated_v2', 'yes' );
}

/**
 * Background worker: fan out subscription notifications for one page of subscribers.
 *
 * Queued by bb_send_notifications_to_subscribers() when an item has more
 * subscribers than the background fan-out threshold. Fetches one keyset page of
 * subscriber IDs (sc.id > last_id), queues the regular per-chunk send jobs for
 * that page, then re-queues itself for the next page until the list is
 * exhausted. Keeps the originating web request O(1) regardless of subscriber
 * count.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args {
 *     An array of arguments.
 *     @type string $type              Required. The subscription type.
 *     @type int    $item_id           Required. The ID of the item.
 *     @type int    $blog_id           Optional. The blog ID the subscriptions belong to.
 *                                     Default current site ID.
 *     @type array  $data              Optional. Additional data for the notification.
 *     @type string $notification_type Optional. The registered notification type.
 *     @type string $notification_from Optional. The notification source.
 *     @type array  $usernames         Optional. Mentioned usernames handled separately by the send callback.
 *     @type int    $last_id           Optional. Highest subscription row ID already processed
 *                                     (keyset cursor). Default 0.
 *     @type int    $per_page          Optional. Subscribers fetched per page. Default 1000.
 * }
 *
 * @return false Always false so the processed queue row is removed
 *               (a truthy return re-runs the row — see BB_Background_Updater::task()).
 */
function bb_send_notifications_to_subscribers_batch( $args ) {
	$r = bp_parse_args(
		$args,
		array(
			'type'              => '',
			'item_id'           => 0,
			'blog_id'           => get_current_blog_id(),
			'data'              => array(),
			'notification_type' => '',
			'notification_from' => '',
			'last_id'           => 0,
			'per_page'          => 1000,
		)
	);

	$type    = $r['type'];
	$item_id = $r['item_id'];

	if ( empty( $type ) || empty( $item_id ) ) {
		return false;
	}

	// Switch to the target site BEFORE resolving the type: on multisite with
	// per-site component activation, the dispatching site's registered
	// subscription types can differ from the target site's.
	$switched = false;
	if ( is_multisite() && get_current_blog_id() !== (int) $r['blog_id'] ) {
		switch_to_blog( $r['blog_id'] );
		$switched = true;
	}

	// A non-positive per_page would drop the LIMIT clause entirely and fetch the
	// whole list in one background request — clamp it.
	$per_page = max( 1, (int) $r['per_page'] );
	$last_id  = (int) $r['last_id'];

	// Durable page cursor for this fan-out chain. The queue's twin-row cleanup
	// only dedupes duplicates that still COEXIST in the queue; if this row is
	// re-run (mid-task fatal + healthcheck, or lock expiry) after its chunk
	// jobs already drained, nothing is left to dedupe against and the page
	// would be re-sent. The cursor survives queue-row deletion, so a re-run
	// RESUMES past pages whose chunk jobs were already queued.
	$fanout_cursor_key = 'bb_sub_fanout_cursor_' . md5( maybe_serialize( array( $type, $item_id, (int) $r['blog_id'], $r['notification_from'], $r['data'] ) ) );
	$fanout_done_to    = (int) get_option( $fanout_cursor_key, 0 );
	if ( $last_id < $fanout_done_to ) {
		$last_id = $fanout_done_to;
	}

	// Re-resolve the send callback at run time instead of serializing the
	// notification class instance into the queue row; bail gracefully when the
	// type has been unregistered since the job was queued.
	$type_data = bb_register_subscriptions_types( $type );
	if (
		empty( $type_data ) ||
		empty( $type_data['send_callback'] ) ||
		! is_callable( $type_data['send_callback'] )
	) {
		// The chain dies here (returning false deletes the queue row), so the
		// cursor would otherwise leak as a stale option.
		delete_option( $fanout_cursor_key );

		if ( $switched ) {
			restore_current_blog();
		}

		return false;
	}

	// The source activity can be deleted mid-chain. The per-chunk send
	// callbacks would each no-op on it, but without this check the worker
	// would keep paginating the full subscriber list queueing dead jobs.
	if ( ! empty( $r['data']['activity_id'] ) && bp_is_active( 'activity' ) && class_exists( 'BP_Activity_Activity' ) ) {
		$fanout_activity = new BP_Activity_Activity( (int) $r['data']['activity_id'] );

		if ( empty( $fanout_activity->id ) ) {
			delete_option( $fanout_cursor_key );

			if ( $switched ) {
				restore_current_blog();
			}

			return false;
		}
	}

	// Same for forum discussion/reply chains: stop paginating when the source
	// post was deleted mid-chain. Reply payloads carry both reply_id and
	// topic_id — the reply is the item being sent, so it decides.
	$fanout_forum_post_id = 0;
	if ( ! empty( $r['data']['reply_id'] ) ) {
		$fanout_forum_post_id = (int) $r['data']['reply_id'];
	} elseif ( ! empty( $r['data']['topic_id'] ) ) {
		$fanout_forum_post_id = (int) $r['data']['topic_id'];
	}

	if ( ! empty( $fanout_forum_post_id ) && empty( get_post( $fanout_forum_post_id ) ) ) {
		delete_option( $fanout_cursor_key );

		if ( $switched ) {
			restore_current_blog();
		}

		return false;
	}

	// Keyset pagination (sc.id > last processed id) instead of page/offset:
	// offsets shift when rows are deleted mid-fan-out (unsubscribes), silently
	// skipping subscribers. The ID page is the authority for advancing the
	// cursor; user IDs are resolved from it in a second bounded query, so a row
	// deleted between the two queries just drops out without derailing the
	// chain. Force-cache (second arg true) bypasses the per-args static cache,
	// which cannot see the keyset filter and would replay the first page
	// forever. cache => false additionally skips the incremented-cache read so
	// each page is fetched fresh.
	$bb_fanout_keyset_where = function ( $where_conditions ) use ( $last_id ) {
		$where_conditions['bb_fanout_keyset'] = 'sc.id > ' . $last_id;

		return $where_conditions;
	};
	add_filter( 'bb_subscriptions_get_where_conditions', $bb_fanout_keyset_where );

	$id_page = bb_get_subscription_users(
		array(
			'type'     => $type,
			'item_id'  => $item_id,
			'blog_id'  => $r['blog_id'],
			'status'   => true,
			'fields'   => 'id',
			'per_page' => $per_page,
			'page'     => 1,
			'order_by' => 'id',
			'order'    => 'ASC',
			'count'    => false,
			'cache'    => false,
		),
		true
	);

	remove_filter( 'bb_subscriptions_get_where_conditions', $bb_fanout_keyset_where );

	$subscription_ids = ! empty( $id_page['subscriptions'] ) ? $id_page['subscriptions'] : array();

	if ( empty( $subscription_ids ) ) {
		// Chain complete — the cursor is no longer needed.
		delete_option( $fanout_cursor_key );

		if ( $switched ) {
			restore_current_blog();
		}

		return false;
	}

	// Deterministic id ASC ordering (the default date_recorded DESC has heavy
	// ties) so a re-run composes byte-identical chunk rows — that is what lets
	// the queue's twin-row cleanup and the idempotency guard below dedupe them.
	$user_page = bb_get_subscription_users(
		array(
			'type'     => $type,
			'item_id'  => $item_id,
			'blog_id'  => $r['blog_id'],
			'status'   => true,
			'include'  => $subscription_ids,
			'order_by' => 'id',
			'order'    => 'ASC',
			'count'    => false,
			'cache'    => false,
		),
		true
	);

	$user_ids = ! empty( $user_page['subscriptions'] ) ? $user_page['subscriptions'] : array();

	global $bb_background_updater, $wpdb;

	$min_count  = (int) apply_filters( 'bb_subscription_queue_min_count', 20 );
	$parse_args = array(
		'type'              => $type,
		'item_id'           => $item_id,
		'blog_id'           => $r['blog_id'],
		'data'              => $r['data'],
		'notification_type' => $r['notification_type'],
		'notification_from' => $r['notification_from'],
	);

	if ( ! empty( $r['usernames'] ) ) {
		$parse_args['usernames'] = $r['usernames'];
	}

	$chunk_user_ids = array_chunk( $user_ids, $min_count );
	foreach ( $chunk_user_ids as $chunk ) {
		$parse_args['user_ids'] = $chunk;

		$bb_background_updater->data(
			array(
				'type'     => 'notification',
				'group'    => 'send_notifications_to_subscribers',
				'data_id'  => $item_id,
				'priority' => 5,
				'callback' => $type_data['send_callback'],
				'args'     => array( $parse_args ),
			)
		);

		$bb_background_updater->save();
	}

	// Advance the durable cursor now that this page's chunk jobs are queued —
	// a re-run of this row from here on resumes at the next page instead of
	// re-sending this one.
	$page_end_id = (int) end( $subscription_ids );
	update_option( $fanout_cursor_key, $page_end_id, false );

	// A full ID page means more subscribers may remain: queue the next page,
	// keyed by the last subscription row ID of this page (termination is based
	// on the ID page, not the user resolution, so a row deleted between the two
	// queries can never abandon the chain). Row ordering (priority, id) runs
	// this page's send jobs before the next page's fan-out row, which naturally
	// paces the queue.
	if ( count( $subscription_ids ) === $per_page ) {
		$next_args             = $r;
		$next_args['last_id']  = $page_end_id;
		$next_args['per_page'] = $per_page;

		// Idempotency guard: the queue runner re-runs a not-yet-deleted row on
		// lock expiry or mid-task fatal; without this check that re-run would
		// fork a second self-requeueing chain and double-notify the remainder
		// of the list. Matching the exact serialized payload mirrors the core
		// duplicate-row cleanup in
		// bb_background_remove_duplicate_async_request_batch_process().
		$next_row_data = array(
			'callback' => 'bb_send_notifications_to_subscribers_batch',
			'args'     => array( $next_args ),
		);

		$table_name    = $bb_background_updater::$table_name;
		$existing_next = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT id FROM {$table_name} WHERE `group` = %s AND `data_id` = %s AND `data` = %s LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'send_notifications_to_subscribers',
				$item_id,
				maybe_serialize( $next_row_data )
			)
		);

		// The pending-row SELECT above is check-then-act: two concurrent runs of
		// this same page row can both find nothing and both insert, forking a
		// duplicate walk of the remaining list. This atomic add makes exactly
		// one run the inserter. A crash between the add and save() leaves the
		// page row undeleted; its re-run resumes from the durable cursor, whose
		// advanced last_id yields a different claim key — the chain self-heals
		// through the cursor, not through TTL expiry. Like the chunk claim, this
		// is atomic across workers only with a persistent object cache; without
		// one it is per-process and the SELECT above is the only guard, which
		// matches the pre-existing behavior.
		$next_page_claim = 'bb_sub_fanout_next_' . md5( maybe_serialize( $next_row_data ) );

		if ( empty( $existing_next ) && wp_cache_add( $next_page_claim, microtime( true ), 'bb_subscriptions', 30 ) ) {
			$bb_background_updater->data(
				array(
					'type'     => 'notification',
					'group'    => 'send_notifications_to_subscribers',
					'data_id'  => $item_id,
					'priority' => 5,
					'callback' => 'bb_send_notifications_to_subscribers_batch',
					'args'     => array( $next_args ),
				)
			);

			$bb_background_updater->save();
		}
	} else {
		// Partial page = end of the list; no re-queue, so clean the cursor here.
		delete_option( $fanout_cursor_key );
	}

	$bb_background_updater->dispatch();

	if ( $switched ) {
		restore_current_blog();
	}

	return false;
}

/**
 * Atomically claim a subscription notification chunk before sending it.
 *
 * The shared background queue can execute the same chunk row twice when
 * workers are dispatched concurrently (cron storm, stale-lock takeover) —
 * every user in the chunk then receives the email/notification twice. The
 * claim is an atomic object-cache add keyed by the chunk payload: the first
 * worker wins and any concurrent duplicate run bails. On persistent object
 * caches (Redis/Memcached) wp_cache_add() is atomic across PHP workers;
 * without one the claim is per-process, which simply matches the
 * pre-existing behavior.
 *
 * The 30-second expiry balances two failure modes. Duplicate concurrent runs
 * of the same row land within seconds of each other (QA measured 5ms–6s
 * spreads), so the claim comfortably blocks them. A worker that fatals after
 * claiming but before sending leaves the row queued; if another live worker
 * retries it inside the TTL the chunk is dropped instead of duplicated — the
 * short expiry keeps that drop window small, and retries via the cron
 * healthcheck (minutes later) are never blocked.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param array $args Parsed send-callback arguments: type, item_id, blog_id,
 *                    notification_from, data (identifies the activity/topic/reply),
 *                    and user_ids for this chunk.
 *
 * @return bool True if this run owns the chunk, false if a concurrent duplicate run already claimed it.
 */
function bb_subscriptions_claim_notification_chunk( $args ) {
	$user_ids = ! empty( $args['user_ids'] ) ? (array) $args['user_ids'] : array();

	if ( empty( $user_ids ) ) {
		// Nothing to claim.
		return true;
	}

	$claim_key = 'bb_sub_chunk_claim_' . md5(
		maybe_serialize(
			array(
				isset( $args['type'] ) ? $args['type'] : '',
				isset( $args['item_id'] ) ? $args['item_id'] : 0,
				isset( $args['blog_id'] ) ? (int) $args['blog_id'] : get_current_blog_id(),
				isset( $args['notification_from'] ) ? $args['notification_from'] : '',
				isset( $args['data'] ) ? $args['data'] : array(),
				$user_ids,
			)
		)
	);

	/**
	 * Filters the notification chunk-claim lifetime.
	 *
	 * Raise it on sites whose mail transport can take longer than the default
	 * to deliver a chunk (slow SMTP, third-party throttling) — an in-flight
	 * send outliving the claim would let a retry re-send the chunk.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int   $claim_ttl Claim lifetime in seconds. Default 30.
	 * @param array $args      Parsed send-callback arguments.
	 */
	$claim_ttl = (int) apply_filters( 'bb_subscription_notification_chunk_claim_ttl', 30, $args );

	return wp_cache_add( $claim_key, microtime( true ), 'bb_subscriptions', max( 1, $claim_ttl ) );
}
