<?php

/**
 * Forums Admin Tools Page
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Repair ********************************************************************/

/**
 * Admin repair page
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_admin_repair_list() To get the recount list
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 * @uses do_action() Calls 'admin_notices' to display the notices
 * @uses wp_nonce_field() To add a hidden nonce field
 */
function bbp_admin_repair() {
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
			</ul>
		</div>
	</div>
	<div class="wrap">

		<div class="bp-admin-card section-repair_forums">

			<h2>
				<?php
				$meta_icon = bb_admin_icons( 'repair_forums' );
				if ( ! empty( $meta_icon ) ) {
					echo '<i class="' . esc_attr( $meta_icon ) . ' "></i>';
				}
				esc_html_e( 'Repair Forums', 'buddyboss' );
				?>
			</h2>

			<p><?php esc_html_e( 'BuddyBoss keeps track of relationships between forums, discussions, replies, and discussion tags, and users. Occasionally these relationships become out of sync, most often after an import or migration. Use the tools below to manually recalculate these relationships.', 'buddyboss' ); ?></p>

			<form class="settings" method="post" action="">

				<?php
				if ( is_multisite() && is_network_admin() ) {
					$bbp_network_sites = bbp_get_network_sites();
					?>
					<fieldset>
						<legend>
							<?php
							esc_html_e( 'Sites:', 'buddyboss' );
							?>
						</legend>
						<label for="select-site">
							<?php

							if ( ! empty( $bbp_network_sites ) ) {
								?>
								<select name="bbp-network-site" id="bbp-network-site" required>
									<option value="0">
										<?php
										esc_html_e( 'Select a site to repair forums', 'buddyboss' );
										?>
									</option>
									<?php
									foreach ( $bbp_network_sites as $bbp_network_site ) {
										?>
										<option value="<?php echo esc_attr( $bbp_network_site->blog_id ); ?>">
											<?php
											echo esc_html( $bbp_network_site->domain . '/' . $bbp_network_site->path );
											?>
										</option>
										<?php
									}
									?>
								</select>
								<?php
							}
							?>
						</label>
					</fieldset>
					<?php
				}
				?>

				<fieldset>
					<legend><?php esc_html_e( 'Relationships to Repair:', 'buddyboss' ); ?></legend>
					<div class="checkbox">
					<?php foreach ( bbp_admin_repair_list() as $item ) : ?>
						<label for="<?php echo esc_html( $item[0] ); ?>"><input type="checkbox" class="checkbox" name="<?php echo esc_attr( $item[0] ) . '" id="' . esc_attr( str_replace( '_', '-', $item[0] ) ); ?>" value="<?php echo esc_attr( $item[0] ); ?>" /> <?php echo esc_html( $item[1] ); ?></label>
					<?php endforeach; ?>
					</div>
					<p class="submit">
						<?php wp_nonce_field( 'bbpress-do-counts' ); ?>
						<a class="button-primary" id="bp-tools-forum-submit"><?php echo esc_html__( 'Repair Items', 'buddyboss' ); ?></a>
					</p>
				</fieldset>
			</form>
		</div>
	</div>

	<?php
}

/**
 * Handle the processing and feedback of the admin tools page
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_admin_repair_list() To get the recount list
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 * @uses do_action() Calls 'admin_notices' to display the notices
 */
function bbp_admin_repair_handler() {

	if ( ! bbp_is_post_request() ) {
		return;
	}

	check_admin_referer( 'bbpress-do-counts' );

	// Stores messages
	$messages = array();

	wp_cache_flush();

	foreach ( (array) bbp_admin_repair_list() as $item ) {
		if ( isset( $item[2] ) && isset( $_POST[ $item[0] ] ) && 1 === absint( $_POST[ $item[0] ] ) && is_callable( $item[2] ) ) {
			$messages[] = call_user_func( $item[2] );
		}
	}

	if ( count( $messages ) ) {
		foreach ( $messages as $message ) {
			bbp_admin_tools_feedback( $message[1] );
		}
	}
}

/**
 * Assemble the admin notices
 *
 * @since bbPress (r2613)
 *
 * @param string|WP_Error $message A message to be displayed or {@link WP_Error}
 * @param string          $class Optional. A class to be added to the message div
 * @uses WP_Error::get_error_messages() To get the error messages of $message
 * @uses add_action() Adds the admin notice action with the message HTML
 * @return string The message HTML
 */
function bbp_admin_tools_feedback( $message, $class = false ) {

	// One message as string.
	if ( is_string( $message ) ) {
		$message = '<p>' . $message . '</p>';
		$class   = $class ? $class : 'updated';

	// Messages as objects.
	} elseif ( is_wp_error( $message ) ) {
		$errors = $message->get_error_messages();

		switch ( count( $errors ) ) {
			case 0:
				return false;
				break;

			case 1:
				$message = '<p>' . $errors[0] . '</p>';
				break;

			default:
				$message = '<ul>' . "\n\t" . '<li>' . implode( '</li>' . "\n\t" . '<li>', $errors ) . '</li>' . "\n" . '</ul>';
				break;
		}

		$class = $class ? $class : 'error';
	} else {
		return false;
	}

	$message = '<div id="message" class="' . esc_attr( $class ) . '">' . $message . '</div>';
	$message = str_replace( "'", "\'", $message );
	$lambda  = function () use ( $message ) {
		echo $message;
	};

	add_action( 'admin_notices', $lambda );

	return $lambda;
}

/**
 * Get the array of the repair list
 *
 * @since bbPress (r2613)
 *
 * @uses apply_filters() Calls 'bbp_repair_list' with the list array
 * @return array Repair list of options
 */
function bbp_admin_repair_list() {
	$repair_list = array(
		0   => array( 'bbp-sync-topic-meta', __( 'Recalculate the parent discussion for each post', 'buddyboss' ), 'bbp_admin_repair_topic_meta' ),
		5   => array( 'bbp-sync-forum-meta', __( 'Recalculate the parent forum for each post', 'buddyboss' ), 'bbp_admin_repair_forum_meta' ),
		10  => array( 'bbp-sync-forum-visibility', __( 'Recalculate private and hidden forums', 'buddyboss' ), 'bbp_admin_repair_forum_visibility' ),
		15  => array( 'bbp-sync-all-topics-forums', __( 'Recalculate last activity in each discussion and forum', 'buddyboss' ), 'bbp_admin_repair_freshness' ),
		20  => array( 'bbp-sync-all-topics-sticky', __( 'Recalculate the sticky relationship of each discussion', 'buddyboss' ), 'bbp_admin_repair_sticky' ),
		25  => array( 'bbp-sync-all-reply-positions', __( 'Recalculate the position of each reply', 'buddyboss' ), 'bbp_admin_repair_reply_menu_order' ),
		30  => array( 'bbp-group-forums', __( 'Repair social group forum relationships', 'buddyboss' ), 'bbp_admin_repair_group_forum_relationship' ),
		35  => array( 'bbp-forum-topics', __( 'Count discussions in each forum', 'buddyboss' ), 'bbp_admin_repair_forum_topic_count' ),
		40  => array( 'bbp-forum-replies', __( 'Count replies in each forum', 'buddyboss' ), 'bbp_admin_repair_forum_reply_count' ),
		45  => array( 'bbp-topic-replies', __( 'Count replies in each discussion', 'buddyboss' ), 'bbp_admin_repair_topic_reply_count' ),
		50  => array( 'bbp-topic-members', __( 'Count members in each discussion', 'buddyboss' ), 'bbp_admin_repair_topic_voice_count' ),
		55  => array( 'bbp-topic-hidden-replies', __( 'Count spammed & trashed replies in each discussion', 'buddyboss' ), 'bbp_admin_repair_topic_hidden_reply_count' ),
		60  => array( 'bbp-user-topics', __( 'Count discussions for each user', 'buddyboss' ), 'bbp_admin_repair_user_topic_count' ),
		65  => array( 'bbp-user-replies', __( 'Count replies for each user', 'buddyboss' ), 'bbp_admin_repair_user_reply_count' ),
		70  => array( 'bbp-user-favorites', __( 'Remove trashed discussions from user favorites', 'buddyboss' ), 'bbp_admin_repair_user_favorites' ),
		75  => array( 'bbp-user-topic-subscriptions', __( 'Remove trashed discussions from user subscriptions', 'buddyboss' ), 'bbp_admin_repair_user_topic_subscriptions' ),
		80  => array( 'bbp-user-forum-subscriptions', __( 'Remove trashed forums from user subscriptions', 'buddyboss' ), 'bbp_admin_repair_user_forum_subscriptions' ),
		85  => array( 'bbp-user-role-map', __( 'Remap existing users to default forum roles', 'buddyboss' ), 'bbp_admin_repair_user_roles' ),
		90  => array( 'bbp-wp-role-restore', __( 'Remove and restore Wordpress default role capabilities', 'buddyboss' ), 'bbp_restore_caps_from_wp_roles' ),
		95  => array( 'bbp-migrate-buddyboss-forum-topic-subscription', __( 'Migrate BBPress (up to v2.5.14) forum and discussion subscriptions to BuddyBoss', 'buddyboss' ), 'bbp_migrate_forum_topic_subscription' ),
		100 => array( 'bbp-migrate-bbpress-forum-topic-subscription', __( 'Migrate BBPress (v2.6+) forum and discussion subscriptions to BuddyBoss', 'buddyboss' ), 'bbp_migrate_forum_topic_subscription' ),
		105 => array( 'bb-migrate-bbpress-user-topic-favorites', __( 'Migrate members discussions \'marked as favorites\' data to improve performance', 'buddyboss' ), 'bb_migrate_user_topic_favorites' ),
	);
	ksort( $repair_list );

	return (array) apply_filters( 'bbp_repair_list', $repair_list );
}

/**
 * Function get network sites.
 *
 * @since BuddyBoss 1.2.10
 *
 * @return mixed|void
 */
function bbp_get_network_sites() {
	$sites = get_sites();

	return apply_filters( 'bbp_get_network_sites', ( $sites ) ? $sites : array() );
}

/**
 * Recount topic replies
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses bbp_db() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_topic_reply_count() {

	$bbp_db    = bbp_db();
	$statement = __( 'Counting the number of replies in each discussion &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	// Post types and status
	$tpt = bbp_get_topic_post_type();
	$rpt = bbp_get_reply_post_type();
	$pps = bbp_get_public_status_id();
	$cps = bbp_get_closed_status_id();

	// Delete the meta key _bbp_reply_count for each topic
	$sql_delete = "DELETE `postmeta` FROM `{$bbp_db->postmeta}` AS `postmeta`
						LEFT JOIN `{$bbp_db->posts}` AS `posts` ON `posts`.`ID` = `postmeta`.`post_id`
						WHERE `posts`.`post_type` = '{$tpt}'
						AND `postmeta`.`meta_key` = '_bbp_reply_count'";

	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Recalculate the meta key _bbp_reply_count for each topic
	$sql = "INSERT INTO `{$bbp_db->postmeta}` (`post_id`, `meta_key`, `meta_value`) (
			SELECT `topics`.`ID` AS `post_id`, '_bbp_reply_count' AS `meta_key`, COUNT(`replies`.`ID`) As `meta_value`
				FROM `{$bbp_db->posts}` AS `topics`
					LEFT JOIN `{$bbp_db->posts}` as `replies`
						ON  `replies`.`post_parent` = `topics`.`ID`
						AND `replies`.`post_status` = '{$pps}'
						AND `replies`.`post_type`   = '{$rpt}'
				WHERE `topics`.`post_type` = '{$tpt}'
					AND `topics`.`post_status` IN ( '{$pps}', '{$cps}' )
				GROUP BY `topics`.`ID`);";

	if ( is_wp_error( $bbp_db->query( $sql ) ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recount topic members
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses bbp_db() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_topic_voice_count() {

	$bbp_db    = bbp_db();
	$statement = __( 'Counting the number of members in each discussion &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	$sql_delete = "DELETE FROM `{$bbp_db->postmeta}` WHERE `meta_key` = '_bbp_voice_count';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Post types and status
	$tpt = bbp_get_topic_post_type();
	$rpt = bbp_get_reply_post_type();
	$pps = bbp_get_public_status_id();
	$cps = bbp_get_closed_status_id();

	$sql = "INSERT INTO `{$bbp_db->postmeta}` (`post_id`, `meta_key`, `meta_value`) (
			SELECT `postmeta`.`meta_value`, '_bbp_voice_count', COUNT(DISTINCT `post_author`) as `meta_value`
				FROM `{$bbp_db->posts}` AS `posts`
				LEFT JOIN `{$bbp_db->postmeta}` AS `postmeta`
					ON `posts`.`ID` = `postmeta`.`post_id`
					AND `postmeta`.`meta_key` = '_bbp_topic_id'
				WHERE `posts`.`post_type` IN ( '{$tpt}', '{$rpt}' )
					AND `posts`.`post_status` IN ( '{$pps}', '{$cps}' )
					AND `posts`.`post_author` != '0'
				GROUP BY `postmeta`.`meta_value`);";

	if ( is_wp_error( $bbp_db->query( $sql ) ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recount topic hidden replies (spammed/trashed)
 *
 * @since bbPress (r2747)
 *
 * @uses bbp_db() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_topic_hidden_reply_count() {

	$bbp_db    = bbp_db();
	$statement = __( 'Counting the number of spammed and trashed replies in each discussion &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	$sql_delete = "DELETE FROM `{$bbp_db->postmeta}` WHERE `meta_key` = '_bbp_reply_count_hidden';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Post types and status.
	$rpt = bbp_get_reply_post_type();
	$sta = bbp_get_non_public_topic_statuses();

	// Status.
	$sql_status = "'" . implode( "','", $sta ) . "'";

	$sql = "INSERT INTO `{$bbp_db->postmeta}` (`post_id`, `meta_key`, `meta_value`) (SELECT `post_parent`, '_bbp_reply_count_hidden', COUNT(`post_status`) as `meta_value` FROM `{$bbp_db->posts}` WHERE `post_type` = '{$rpt}' AND `post_status` IN ({$sql_status}) GROUP BY `post_parent`)";
	if ( is_wp_error( $bbp_db->query( $sql ) ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Repair group forum ID mappings after a bbPress 1.1 to Forums 2.2 conversion
 *
 * @since bbPress (r4395)
 *
 * @return If a wp_error() occurs and no converted forums are found
 */
function bbp_admin_repair_group_forum_relationship() {

	$bbp_db    = bbp_db();
	$statement = __( 'Repairing social group forum relationships &hellip; %s', 'buddyboss' );
	$g_count   = 0;
	$f_count   = 0;
	$s_count   = 0;

	// Copy the BuddyBoss filter here, incase BuddyBoss is not active
	$prefix            = apply_filters( 'bp_core_get_table_prefix', $bbp_db->base_prefix );
	$groups_table      = $prefix . 'bp_groups';
	$groups_meta_table = $prefix . 'bp_groups_groupmeta';

	// Get the converted forum IDs
	$forum_ids = $bbp_db->query(
		"SELECT `forum`.`ID`, `forummeta`.`meta_value`
								FROM `{$bbp_db->posts}` AS `forum`
									LEFT JOIN `{$bbp_db->postmeta}` AS `forummeta`
										ON `forum`.`ID` = `forummeta`.`post_id`
										AND `forummeta`.`meta_key` = '_bbp_old_forum_id'
								WHERE `forum`.`post_type` = 'forum'
								GROUP BY `forum`.`ID`;"
	);

	// Bail if forum IDs returned an error
	if ( is_wp_error( $forum_ids ) || empty( $bbp_db->last_result ) ) {
		return array(
			2,
			sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
		);
	}

	// Stash the last results
	$results = $bbp_db->last_result;

	// Update each group forum
	foreach ( $results as $group_forums ) {

		// Only update if is a converted forum
		if ( ! isset( $group_forums->meta_value ) ) {
			continue;
		}

		// Attempt to update group meta
		$updated = $bbp_db->query( "UPDATE `{$groups_meta_table}` SET `meta_value` = '{$group_forums->ID}' WHERE `meta_key` = 'forum_id' AND `meta_value` = '{$group_forums->meta_value}';" );

		// Bump the count
		if ( ! empty( $updated ) && ! is_wp_error( $updated ) ) {
			++$g_count;
		}

		// Update group to forum relationship data
		$group_id = (int) $bbp_db->get_var( "SELECT `group_id` FROM `{$groups_meta_table}` WHERE `meta_key` = 'forum_id' AND `meta_value` = '{$group_forums->ID}';" );
		if ( ! empty( $group_id ) ) {

			// Update the group to forum meta connection in forums
			update_post_meta( $group_forums->ID, '_bbp_group_ids', array( $group_id ) );

			// Get the group status
			$group_status = $bbp_db->get_var( "SELECT `status` FROM `{$groups_table}` WHERE `id` = '{$group_id}';" );

			// Sync up forum visibility based on group status
			switch ( $group_status ) {

				// Public groups have public forums
				case 'public':
					bbp_publicize_forum( $group_forums->ID );

					// Bump the count for output later
					++$s_count;
					break;

				// Private/hidden groups have hidden forums
				case 'private':
				case 'hidden':
					bbp_hide_forum( $group_forums->ID );

					// Bump the count for output later
					++$s_count;
					break;
			}

			// Bump the count for output later
			++$f_count;
		}
	}

	// Make some logical guesses at the old group root forum
	if ( function_exists( 'bp_forums_parent_forum_id' ) ) {
		$old_default_forum_id = bp_forums_parent_forum_id();
	} elseif ( defined( 'BP_FORUMS_PARENT_FORUM_ID' ) ) {
		$old_default_forum_id = (int) BP_FORUMS_PARENT_FORUM_ID;
	} else {
		$old_default_forum_id = 1;
	}

	// Try to get the group root forum
	$posts = get_posts(
		array(
			'post_type'              => bbp_get_forum_post_type(),
			'meta_key'               => '_bbp_old_forum_id',
			'meta_type'              => 'NUMERIC',
			'meta_value'             => $old_default_forum_id,
			'numberposts'            => 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	// Found the group root forum
	if ( ! empty( $posts ) ) {

		// Rename 'Default Forum'  since it's now visible in sitewide forums
		if ( 'Default Forum' === $posts[0]->post_title ) {
			wp_update_post(
				array(
					'ID'         => $posts[0]->ID,
					'post_title' => __( 'Group Forums', 'buddyboss' ),
					'post_name'  => __( 'group-forums', 'buddyboss' ),
				)
			);
		}

		// Update the group forums root metadata
		update_option( '_bbp_group_forums_root_id', $posts[0]->ID );
	}

	// Remove old bbPress 1.1 roles (BuddyBoss)
	remove_role( 'member' );
	remove_role( 'inactive' );
	remove_role( 'blocked' );
	remove_role( 'moderator' );
	remove_role( 'keymaster' );

	// Complete results
	$result = sprintf( __( 'Complete! %1$s groups updated; %2$s forums updated; %3$s forum statuses synced.', 'buddyboss' ), bbp_number_format( $g_count ), bbp_number_format( $f_count ), bbp_number_format( $s_count ) );
	return array(
		0,
		sprintf( $statement, $result ),
		'status'  => 1,
		'message' => sprintf( $statement, $result ),
	);
}

/**
 * Recount forum topics
 *
 * @since bbPress (r2613)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses get_posts() To get the forums
 * @uses bbp_update_forum_topic_count() To update the forum topic count
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_forum_topic_count() {

	$bbp_db    = bbp_db();
	$statement = __( 'Counting the number of discussions in each forum &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	$sql_delete = "DELETE FROM {$bbp_db->postmeta} WHERE meta_key IN ( '_bbp_topic_count', '_bbp_total_topic_count' );";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$forums = get_posts(
		array(
			'post_type'              => bbp_get_forum_post_type(),
			'numberposts'            => - 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( ! empty( $forums ) ) {
		foreach ( $forums as $forum ) {
			bbp_update_forum_topic_count( $forum->ID );
		}
	} else {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recount forum replies
 *
 * @since bbPress (r2613)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @uses bbp_get_forum_post_type() To get the forum post type
 * @uses get_posts() To get the forums
 * @uses bbp_update_forum_reply_count() To update the forum reply count
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_forum_reply_count() {

	$bbp_db    = bbp_db();
	$statement = __( 'Counting the number of replies in each forum &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	// Post type
	$fpt = bbp_get_forum_post_type();

	// Delete the meta keys _bbp_reply_count and _bbp_total_reply_count for each forum
	$sql_delete = "DELETE `postmeta` FROM `{$bbp_db->postmeta}` AS `postmeta`
						LEFT JOIN `{$bbp_db->posts}` AS `posts` ON `posts`.`ID` = `postmeta`.`post_id`
						WHERE `posts`.`post_type` = '{$fpt}'
						AND `postmeta`.`meta_key` = '_bbp_reply_count'
						OR `postmeta`.`meta_key` = '_bbp_total_reply_count'";

	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Recalculate the metas key _bbp_reply_count and _bbp_total_reply_count for each forum
	$forums = get_posts(
		array(
			'post_type'              => bbp_get_forum_post_type(),
			'numberposts'            => - 1,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);
	if ( ! empty( $forums ) ) {
		foreach ( $forums as $forum ) {
			bbp_update_forum_reply_count( $forum->ID );
		}
	} else {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recount topics by the users
 *
 * @since bbPress (r3889)
 *
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses bbp_db() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_user_topic_count() {

	$bbp_db      = bbp_db();
	$statement   = __( 'Counting the number of discussions for each user &hellip; %s', 'buddyboss' );
	$result      = __( 'Failed!', 'buddyboss' );
	$sql_select  = "SELECT `post_author`, COUNT(DISTINCT `ID`) as `_count` FROM `{$bbp_db->posts}` WHERE `post_type` = '" . bbp_get_topic_post_type() . "' AND `post_status` = '" . bbp_get_public_status_id() . "' GROUP BY `post_author`;";
	$insert_rows = $bbp_db->get_results( $sql_select );

	if ( is_wp_error( $insert_rows ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$key           = $bbp_db->prefix . '_bbp_topic_count';
	$insert_values = array();
	foreach ( $insert_rows as $insert_row ) {
		$insert_values[] = "('{$insert_row->post_author}', '{$key}', '{$insert_row->_count}')";
	}

	if ( ! count( $insert_values ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			3,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	foreach ( array_chunk( $insert_values, 10000 ) as $chunk ) {
		$chunk      = "\n" . implode( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$bbp_db->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";

		if ( is_wp_error( $bbp_db->query( $sql_insert ) ) ) {
			return array(
				4,
				sprintf( $statement, $result ),
				'status'  => 0,
				'message' => sprintf( $statement, $result ),
			);
		}
	}

	return array(
		0,
		sprintf( $statement, $result ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recount topic replied by the users
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_user_reply_count() {

	$bbp_db      = bbp_db();
	$statement   = __( 'Counting the number of replies for each user &hellip; %s', 'buddyboss' );
	$result      = __( 'Failed!', 'buddyboss' );
	$sql_select  = "SELECT `post_author`, COUNT(DISTINCT `ID`) as `_count` FROM `{$bbp_db->posts}` WHERE `post_type` = '" . bbp_get_reply_post_type() . "' AND `post_status` = '" . bbp_get_public_status_id() . "' GROUP BY `post_author`;";
	$insert_rows = $bbp_db->get_results( $sql_select );

	if ( is_wp_error( $insert_rows ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$key           = $bbp_db->prefix . '_bbp_reply_count';
	$insert_values = array();
	foreach ( $insert_rows as $insert_row ) {
		$insert_values[] = "('{$insert_row->post_author}', '{$key}', '{$insert_row->_count}')";
	}

	if ( ! count( $insert_values ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			3,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	foreach ( array_chunk( $insert_values, 10000 ) as $chunk ) {
		$chunk      = "\n" . implode( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$bbp_db->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";

		if ( is_wp_error( $bbp_db->query( $sql_insert ) ) ) {
			return array(
				4,
				sprintf( $statement, $result ),
				'status'  => 0,
				'message' => sprintf( $statement, $result ),
			);
		}
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Clean the users' favorites
 *
 * @since bbPress (r2613)
 *
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_user_favorites() {

	$bbp_db    = bbp_db();
	$statement = __( 'Removing trashed discussions from user favorites &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );
	$key       = $bbp_db->prefix . '_bbp_favorites';
	$users     = $bbp_db->get_results( "SELECT `user_id`, `meta_value` AS `favorites` FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';" );

	if ( is_wp_error( $users ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$topics = $bbp_db->get_col( "SELECT `ID` FROM `{$bbp_db->posts}` WHERE `post_type` = '" . bbp_get_topic_post_type() . "' AND `post_status` = '" . bbp_get_public_status_id() . "';" );

	if ( is_wp_error( $topics ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$values = array();
	foreach ( $users as $user ) {
		if ( empty( $user->favorites ) || ! is_string( $user->favorites ) ) {
			continue;
		}

		$favorites = array_intersect( $topics, explode( ',', $user->favorites ) );
		if ( empty( $favorites ) || ! is_array( $favorites ) ) {
			continue;
		}

		$favorites_joined = implode( ',', $favorites );
		$values[]         = "('{$user->user_id}', '{$key}', '{$favorites_joined}')";

		// Cleanup
		unset( $favorites, $favorites_joined );
	}

	if ( ! count( $values ) ) {
		$result = __( 'Nothing to remove!', 'buddyboss' );
		return array(
			0,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	foreach ( array_chunk( $values, 10000 ) as $chunk ) {
		$chunk      = "\n" . implode( ",\n", $chunk );
		$sql_insert = "INSERT INTO `$bbp_db->usermeta` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";
		if ( is_wp_error( $bbp_db->query( $sql_insert ) ) ) {
			return array(
				5,
				sprintf( $statement, $result ),
				'status'  => 0,
				'message' => sprintf( $statement, $result ),
			);
		}
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Clean the users' topic subscriptions
 *
 * @since bbPress (r2668)
 *
 * @uses bbp_get_topic_post_type() To get the topic post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_user_topic_subscriptions() {

	$bbp_db    = bbp_db();
	$statement = __( 'Removing trashed discussions from user subscriptions &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );
	$key       = $bbp_db->prefix . '_bbp_subscriptions';
	$users     = $bbp_db->get_results( "SELECT `user_id`, `meta_value` AS `subscriptions` FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';" );

	if ( is_wp_error( $users ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$topics = $bbp_db->get_col( "SELECT `ID` FROM `{$bbp_db->posts}` WHERE `post_type` = '" . bbp_get_topic_post_type() . "' AND `post_status` = '" . bbp_get_public_status_id() . "';" );
	if ( is_wp_error( $topics ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$values = array();
	foreach ( $users as $user ) {
		if ( empty( $user->subscriptions ) || ! is_string( $user->subscriptions ) ) {
			continue;
		}

		$subscriptions = array_intersect( $topics, explode( ',', $user->subscriptions ) );
		if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
			continue;
		}

		$subscriptions_joined = implode( ',', $subscriptions );
		$values[]             = "('{$user->user_id}', '{$key}', '{$subscriptions_joined}')";

		// Cleanup
		unset( $subscriptions, $subscriptions_joined );
	}

	if ( ! count( $values ) ) {
		$result = __( 'Nothing to remove!', 'buddyboss' );
		return array(
			0,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	foreach ( array_chunk( $values, 10000 ) as $chunk ) {
		$chunk      = "\n" . implode( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$bbp_db->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";
		if ( is_wp_error( $bbp_db->query( $sql_insert ) ) ) {
			return array(
				5,
				sprintf( $statement, $result ),
				'status'  => 0,
				'message' => sprintf( $statement, $result ),
			);
		}
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Clean the users' forum subscriptions
 *
 * @since bbPress (r5155)
 *
 * @uses bbp_get_forum_post_type() To get the topic post type
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_user_forum_subscriptions() {

	$bbp_db    = bbp_db();
	$statement = __( 'Removing trashed forums from user subscriptions &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );
	$key       = $bbp_db->prefix . '_bbp_forum_subscriptions';
	$users     = $bbp_db->get_results( "SELECT `user_id`, `meta_value` AS `subscriptions` FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';" );

	if ( is_wp_error( $users ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$forums = $bbp_db->get_col( "SELECT `ID` FROM `{$bbp_db->posts}` WHERE `post_type` = '" . bbp_get_forum_post_type() . "' AND `post_status` = '" . bbp_get_public_status_id() . "';" );
	if ( is_wp_error( $forums ) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	$values = array();
	foreach ( $users as $user ) {
		if ( empty( $user->subscriptions ) || ! is_string( $user->subscriptions ) ) {
			continue;
		}

		$subscriptions = array_intersect( $forums, explode( ',', $user->subscriptions ) );
		if ( empty( $subscriptions ) || ! is_array( $subscriptions ) ) {
			continue;
		}

		$subscriptions_joined = implode( ',', $subscriptions );
		$values[]             = "('{$user->user_id}', '{$key}', '{$subscriptions_joined}')";

		// Cleanup
		unset( $subscriptions, $subscriptions_joined );
	}

	if ( ! count( $values ) ) {
		$result = __( 'Nothing to remove!', 'buddyboss' );
		return array(
			0,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '{$key}';";
	if ( is_wp_error( $bbp_db->query( $sql_delete ) ) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	foreach ( array_chunk( $values, 10000 ) as $chunk ) {
		$chunk      = "\n" . implode( ",\n", $chunk );
		$sql_insert = "INSERT INTO `{$bbp_db->usermeta}` (`user_id`, `meta_key`, `meta_value`) VALUES $chunk;";
		if ( is_wp_error( $bbp_db->query( $sql_insert ) ) ) {
			return array(
				5,
				sprintf( $statement, $result ),
				'status'  => 0,
				'message' => sprintf( $statement, $result ),
			);
		}
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * This repair tool will map each user of the current site to their respective
 * forums role. By default, Admins will be Key Masters, and every other role
 * will be the default role defined in Settings > Forums (Participant).
 *
 * @since bbPress (r4340)
 *
 * @uses bbp_get_user_role_map() To get the map of user roles
 * @uses get_editable_roles() To get the current WordPress roles
 * @uses get_users() To get the users of each role (limited to ID field)
 * @uses bbp_set_user_role() To set each user's forums role
 */
function bbp_admin_repair_user_roles() {

	$statement    = __( 'Remapping existing users to default forum roles &hellip; %s', 'buddyboss' );
	$changed      = 0;
	$role_map     = bbp_get_user_role_map();
	$default_role = bbp_get_default_role();

	// Bail if no role map exists
	if ( empty( $role_map ) ) {
		return array(
			1,
			sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
		);
	}

	// Iterate through each role...
	foreach ( array_keys( bbp_get_blog_roles() ) as $role ) {

		// Reset the offset
		$offset = 0;

		// If no role map exists, give the default forum role (bbp-participant)
		$new_role = isset( $role_map[ $role ] ) ? $role_map[ $role ] : $default_role;

		// Get users of this site, limited to 1000
		while ( $users = get_users(
			array(
				'role'   => $role,
				'fields' => 'ID',
				'number' => 1000,
				'offset' => $offset,
			)
		) ) {

			// Iterate through each user of $role and try to set it
			foreach ( (array) $users as $user_id ) {
				if ( bbp_set_user_role( $user_id, $new_role ) ) {
					++$changed; // Keep a count to display at the end
				}
			}

			// Bump the offset for the next query iteration
			$offset = $offset + 1000;
		}
	}

	$result = sprintf( __( 'Complete! %s users updated.', 'buddyboss' ), bbp_number_format( $changed ) );
	return array(
		0,
		sprintf( $statement, $result ),
		'status'  => 1,
		'message' => sprintf( $statement, $result ),
	);
}

/**
 * Recaches the last post in every topic and forum
 *
 * @since bbPress (r3040)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_freshness() {

	$bbp_db    = bbp_db();
	$statement = __( 'Recalculating last activity in each discussion and forum &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	// First, delete everything.
	if ( is_wp_error( $bbp_db->query( "DELETE FROM `$bbp_db->postmeta` WHERE `meta_key` IN ( '_bbp_last_reply_id', '_bbp_last_topic_id', '_bbp_last_active_id', '_bbp_last_active_time' );" ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `topic`.`ID`, '_bbp_last_reply_id', MAX( `reply`.`ID` )
			FROM `$bbp_db->posts` AS `topic` INNER JOIN `$bbp_db->posts` AS `reply` ON `topic`.`ID` = `reply`.`post_parent`
			WHERE `reply`.`post_status` IN ( '" . bbp_get_public_status_id() . "' ) AND `topic`.`post_type` = 'topic' AND `reply`.`post_type` = 'reply'
			GROUP BY `topic`.`ID` );"
		)
	) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// For any remaining topics, give a reply ID of 0.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `ID`, '_bbp_last_reply_id', 0
			FROM `$bbp_db->posts` AS `topic` LEFT JOIN `$bbp_db->postmeta` AS `reply`
			ON `topic`.`ID` = `reply`.`post_id` AND `reply`.`meta_key` = '_bbp_last_reply_id'
			WHERE `reply`.`meta_id` IS NULL AND `topic`.`post_type` = 'topic' );"
		)
	) ) {
		return array(
			3,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Now we give all the forums with topics the ID their last topic.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `forum`.`ID`, '_bbp_last_topic_id', `topic`.`ID`
			FROM `$bbp_db->posts` AS `forum` INNER JOIN `$bbp_db->posts` AS `topic` ON `forum`.`ID` = `topic`.`post_parent`
			WHERE `topic`.`post_status` IN ( '" . bbp_get_public_status_id() . "' ) AND `forum`.`post_type` = 'forum' AND `topic`.`post_type` = 'topic'
			GROUP BY `forum`.`ID` );"
		)
	) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// For any remaining forums, give a topic ID of 0.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `ID`, '_bbp_last_topic_id', 0
			FROM `$bbp_db->posts` AS `forum` LEFT JOIN `$bbp_db->postmeta` AS `topic`
			ON `forum`.`ID` = `topic`.`post_id` AND `topic`.`meta_key` = '_bbp_last_topic_id'
			WHERE `topic`.`meta_id` IS NULL AND `forum`.`post_type` = 'forum' );"
		)
	) ) {
		return array(
			5,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// After that, we give all the topics with replies the ID their last reply (again, this time for a different reason).
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `topic`.`ID`, '_bbp_last_active_id', MAX( `reply`.`ID` )
			FROM `$bbp_db->posts` AS `topic` INNER JOIN `$bbp_db->posts` AS `reply` ON `topic`.`ID` = `reply`.`post_parent`
			WHERE `reply`.`post_status` IN ( '" . bbp_get_public_status_id() . "' ) AND `topic`.`post_type` = 'topic' AND `reply`.`post_type` = 'reply'
			GROUP BY `topic`.`ID` );"
		)
	) ) {
		return array(
			6,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// For any remaining topics, give a reply ID of themself.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `ID`, '_bbp_last_active_id', `ID`
			FROM `$bbp_db->posts` AS `topic` LEFT JOIN `$bbp_db->postmeta` AS `reply`
			ON `topic`.`ID` = `reply`.`post_id` AND `reply`.`meta_key` = '_bbp_last_active_id'
			WHERE `reply`.`meta_id` IS NULL AND `topic`.`post_type` = 'topic' );"
		)
	) ) {
		return array(
			7,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Give topics with replies their last update time.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `topic`.`ID`, '_bbp_last_active_time', MAX( `reply`.`post_date` )
			FROM `$bbp_db->posts` AS `topic` INNER JOIN `$bbp_db->posts` AS `reply` ON `topic`.`ID` = `reply`.`post_parent`
			WHERE `reply`.`post_status` IN ( '" . bbp_get_public_status_id() . "' ) AND `topic`.`post_type` = 'topic' AND `reply`.`post_type` = 'reply'
			GROUP BY `topic`.`ID` );"
		)
	) ) {
		return array(
			8,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Give topics without replies their last update time.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `ID`, '_bbp_last_active_time', `post_date`
			FROM `$bbp_db->posts` AS `topic` LEFT JOIN `$bbp_db->postmeta` AS `reply`
			ON `topic`.`ID` = `reply`.`post_id` AND `reply`.`meta_key` = '_bbp_last_active_time'
			WHERE `reply`.`meta_id` IS NULL AND `topic`.`post_type` = 'topic' );"
		)
	) ) {
		return array(
			9,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Forums need to know what their last active item is as well. Now it gets a bit more complex to do in the database.
	$forums = $bbp_db->get_col( "SELECT `ID` FROM `$bbp_db->posts` WHERE `post_type` = 'forum' and `post_status` != 'auto-draft';" );
	if ( is_wp_error( $forums ) ) {
		return array(
			10,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Loop through forums
	foreach ( $forums as $forum_id ) {
		if ( ! bbp_is_forum_category( $forum_id ) ) {
			bbp_update_forum( array( 'forum_id' => $forum_id ) );
		}
	}

	// Loop through categories when forums are done
	foreach ( $forums as $forum_id ) {
		if ( bbp_is_forum_category( $forum_id ) ) {
			bbp_update_forum( array( 'forum_id' => $forum_id ) );
		}
	}

	// Complete results
	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Repairs the relationship of sticky topics to the actual parent forum
 *
 * @since bbPress (r4695)
 *
 * @uses wpdb::get_col() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_sticky() {

	$bbp_db    = bbp_db();
	$statement = __( 'Recalculating the sticky relationship of each discussion &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );
	$forums    = $bbp_db->get_col( "SELECT ID FROM `{$bbp_db->posts}` WHERE `post_type` = 'forum';" );

	// Bail if no forums found
	if ( empty( $forums ) || is_wp_error( $forums ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Loop through forums and get their sticky topics
	foreach ( $forums as $forum ) {
		$forum_stickies[ $forum ] = get_post_meta( $forum, '_bbp_sticky_topics', true );
	}

	// Cleanup
	unset( $forums, $forum );

	// Loop through each forum with sticky topics
	foreach ( $forum_stickies as $forum_id => $stickies ) {

		// Skip if no stickies
		if ( empty( $stickies ) ) {
			continue;
		}

		// Loop through each sticky topic
		foreach ( $stickies as $id => $topic_id ) {

			// If the topic is not a super sticky, and the forum ID does not
			// match the topic's forum ID, unset the forum's sticky meta.
			if ( ! bbp_is_topic_super_sticky( $topic_id ) && $forum_id !== bbp_get_topic_forum_id( $topic_id ) ) {
				unset( $forum_stickies[ $forum_id ][ $id ] );
			}
		}

		// Get sticky topic ID's, or use empty string
		$stickers = empty( $forum_stickies[ $forum_id ] ) ? '' : array_values( $forum_stickies[ $forum_id ] );

		// Update the forum's sticky topics meta
		update_post_meta( $forum_id, '_bbp_sticky_topics', $stickers );
	}

	// Complete results
	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recaches the private and hidden forums
 *
 * @since bbPress (r4104)
 *
 * @uses delete_option() to delete private and hidden forum pointers
 * @uses WP_Query() To query post IDs
 * @uses is_wp_error() To return if error occurred
 * @uses update_option() To update the private and hidden post ID pointers
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_forum_visibility() {
	$statement = __( 'Recalculating private and hidden forums &hellip; %s', 'buddyboss' );

	// Bail if queries returned errors
	if ( ! bbp_repair_forum_visibility() ) {
		return array(
			2,
			sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss' ) ),
		);

		// Complete results
	} else {
		return array(
			0,
			sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Recaches the forum for each post
 *
 * @since bbPress (r3876)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_forum_meta() {

	$bbp_db    = bbp_db();
	$statement = __( 'Recalculating the parent forum for each post &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	// First, delete everything.
	if ( is_wp_error( $bbp_db->query( "DELETE FROM `$bbp_db->postmeta` WHERE `meta_key` = '_bbp_forum_id';" ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `forum`.`ID`, '_bbp_forum_id', `forum`.`post_parent`
			FROM `$bbp_db->posts`
				AS `forum`
			WHERE `forum`.`post_type` = 'forum'
			GROUP BY `forum`.`ID` );"
		)
	) ) {
		return array(
			2,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `topic`.`ID`, '_bbp_forum_id', `topic`.`post_parent`
			FROM `$bbp_db->posts`
				AS `topic`
			WHERE `topic`.`post_type` = 'topic'
			GROUP BY `topic`.`ID` );"
		)
	) ) {
		return array(
			3,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `reply`.`ID`, '_bbp_forum_id', `topic`.`post_parent`
			FROM `$bbp_db->posts`
				AS `reply`
			INNER JOIN `$bbp_db->posts`
				AS `topic`
				ON `reply`.`post_parent` = `topic`.`ID`
			WHERE `topic`.`post_type` = 'topic'
				AND `reply`.`post_type` = 'reply'
			GROUP BY `reply`.`ID` );"
		)
	) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Complete results
	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recaches the topic for each post
 *
 * @since bbPress (r3876)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_topic_meta() {

	$bbp_db    = bbp_db();
	$statement = __( 'Recalculating the parent discussion for each post &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	// First, delete everything.
	if ( is_wp_error( $bbp_db->query( "DELETE FROM `$bbp_db->postmeta` WHERE `meta_key` = '_bbp_topic_id';" ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `topic`.`ID`, '_bbp_topic_id', `topic`.`ID`
			FROM `$bbp_db->posts`
				AS `topic`
			WHERE `topic`.`post_type` = 'topic'
			GROUP BY `topic`.`ID` );"
		)
	) ) {
		return array(
			3,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Next, give all the topics with replies the ID their last reply.
	if ( is_wp_error(
		$bbp_db->query(
			"INSERT INTO `$bbp_db->postmeta` (`post_id`, `meta_key`, `meta_value`)
			( SELECT `reply`.`ID`, '_bbp_topic_id', `topic`.`ID`
			FROM `$bbp_db->posts`
				AS `reply`
			INNER JOIN `$bbp_db->posts`
				AS `topic`
				ON `reply`.`post_parent` = `topic`.`ID`
			WHERE `topic`.`post_type` = 'topic'
				AND `reply`.`post_type` = 'reply'
			GROUP BY `reply`.`ID` );"
		)
	) ) {
		return array(
			4,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Complete results
	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recalculate reply menu order
 *
 * @since bbPress (r5367)
 *
 * @uses wpdb::query() To run our recount sql queries
 * @uses is_wp_error() To check if the executed query returned {@link WP_Error}
 * @uses bbp_get_reply_post_type() To get the reply post type
 * @uses bbp_update_reply_position() To update the reply position
 * @return array An array of the status code and the message
 */
function bbp_admin_repair_reply_menu_order() {

	$bbp_db    = bbp_db();
	$statement = __( 'Recalculating the position of each reply &hellip; %s', 'buddyboss' );
	$result    = __( 'No reply positions to recalculate!', 'buddyboss' );

	// Delete cases where `_bbp_reply_to` was accidentally set to itself
	if ( is_wp_error( $bbp_db->query( "DELETE FROM `{$bbp_db->postmeta}` WHERE `meta_key` = '_bbp_reply_to' AND `post_id` = `meta_value`;" ) ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 0,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Post type
	$rpt = bbp_get_reply_post_type();

	// Get an array of reply id's to update the menu oder for each reply
	$replies = $bbp_db->get_results(
		"SELECT `a`.`ID` FROM `{$bbp_db->posts}` AS `a`
										INNER JOIN (
											SELECT `menu_order`, `post_parent`
											FROM `{$bbp_db->posts}`
											GROUP BY `menu_order`, `post_parent`
											HAVING COUNT( * ) >1
										)`b`
										ON `a`.`menu_order` = `b`.`menu_order`
										AND `a`.`post_parent` = `b`.`post_parent`
										WHERE `post_type` = '{$rpt}';",
		OBJECT_K
	);

	// Bail if no replies returned
	if ( empty( $replies ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Recalculate the menu order position for each reply
	foreach ( $replies as $reply ) {
		bbp_update_reply_position( $reply->ID );
	}

	// Cleanup
	unset( $replies, $reply );

	// Flush the cache; things are about to get ugly.
	wp_cache_flush();

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/** Reset ********************************************************************/

/**
 * Admin reset page
 *
 * @since bbPress (r2613)
 *
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses do_action() Calls 'admin_notices' to display the notices
 * @uses wp_nonce_field() To add a hidden nonce field
 */
function bbp_admin_reset() {
	?>

	<div class="wrap">
		<h2 class="nav-tab-wrapper"><?php bp_core_admin_tabs( __( 'Tools', 'buddyboss' ) ); ?></h2>
		<div class="nav-settings-subsubsub">
			<ul class="subsubsub">
				<?php bp_core_tools_settings_admin_tabs(); ?>
			</ul>
		</div>
	</div>
	<div class="wrap">

		<p><?php esc_html_e( 'Revert your forums back to a brand new installation. This process cannot be undone.', 'buddyboss' ); ?></p>
		<p><strong><?php esc_html_e( 'Backup your database before proceeding.', 'buddyboss' ); ?></strong></p>

		<form class="settings" method="post" action="">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'The following data will be removed:', 'buddyboss' ); ?></th>
						<td>
							<?php esc_html_e( 'Forums', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Discussions', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Replies', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Discussion Tags', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Related Meta Data', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Forum Settings', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Forum Activity', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Forum User Roles', 'buddyboss' ); ?><br />
							<?php esc_html_e( 'Importer Helper Data', 'buddyboss' ); ?><br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete imported users?', 'buddyboss' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( "Say it ain't so!", 'buddyboss' ); ?></span></legend>
								<label><input type="checkbox" class="checkbox" name="bbpress-delete-imported-users" id="bbpress-delete-imported-users" value="1" /> <?php esc_html_e( 'This option will delete all previously imported users, and cannot be undone.', 'buddyboss' ); ?></label>
								<p class="description"><?php esc_html_e( 'Note: Resetting without this checked will delete the meta-data necessary to delete these users.', 'buddyboss' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Are you sure you want to do this?', 'buddyboss' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( "Say it ain't so!", 'buddyboss' ); ?></span></legend>
								<label><input type="checkbox" class="checkbox" name="bbpress-are-you-sure" id="bbpress-are-you-sure" value="1" /> <?php esc_html_e( 'This process cannot be undone.', 'buddyboss' ); ?></label>
								<p class="description"><?php esc_html_e( 'Human sacrifice, dogs and cats living together&hellip;mass hysteria!', 'buddyboss' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="submit">
				<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Reset Forums', 'buddyboss' ); ?>" />
				<?php wp_nonce_field( 'bbpress-reset' ); ?>
			</fieldset>
		</form>
	</div>

	<?php
}

/**
 * Handle a bbPress admin area reset request.
 *
 * @since bbPress (r2613)
 *
 * @uses check_admin_referer() To verify the nonce and the referer
 * @uses wp_cache_flush() To flush the cache
 */
function bbp_admin_reset_handler() {

	// Bail if not resetting
	if ( ! bbp_is_post_request() || empty( $_POST['bbpress-are-you-sure'] ) ) {
		return;
	}

	// Only keymasters can proceed
	if ( ! bbp_is_user_keymaster() ) {
		return;
	}

	check_admin_referer( 'bbpress-reset' );

	bbp_admin_reset_database();
}

/**
 * Perform a bbPress database reset.
 *
 * @since bbPress 2.6.0
 * @since BuddyBoss 2.4.00
 */
function bbp_admin_reset_database() {
	// Stores messages
	$messages = array();
	$failed   = __( 'Failed', 'buddyboss' );
	$success  = __( 'Success!', 'buddyboss' );

	// Flush the cache; things are about to get ugly.
	wp_cache_flush();

	/** Posts */
	$bbp_db     = bbp_db();
	$statement  = __( 'Deleting Posts&hellip; %s', 'buddyboss' );
	$sql_posts  = $bbp_db->get_results( "SELECT `ID` FROM `{$bbp_db->posts}` WHERE `post_type` IN ('forum', 'topic', 'reply')", OBJECT_K );
	$sql_delete = "DELETE FROM `{$bbp_db->posts}` WHERE `post_type` IN ('forum', 'topic', 'reply')";
	$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
	$messages[] = sprintf( $statement, $result );

	/** Post Meta */

	if ( ! empty( $sql_posts ) ) {
		$sql_meta = array();
		foreach ( $sql_posts as $key => $value ) {
			$sql_meta[] = $key;
		}
		$statement  = __( 'Deleting Post Meta&hellip; %s', 'buddyboss' );
		$sql_meta   = implode( "', '", $sql_meta );
		$sql_delete = "DELETE FROM `{$bbp_db->postmeta}` WHERE `post_id` IN ('{$sql_meta}');";
		$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );
	}

	/** Topic Tags */

	$statement  = __( 'Deleting Discussions Tags&hellip; %s', 'buddyboss' );
	$sql_delete = "DELETE a,b,c FROM `{$bbp_db->terms}` AS a LEFT JOIN `{$bbp_db->term_taxonomy}` AS c ON a.term_id = c.term_id LEFT JOIN `{$bbp_db->term_relationships}` AS b ON b.term_taxonomy_id = c.term_taxonomy_id WHERE c.taxonomy = 'topic-tag';";
	$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
	$messages[] = sprintf( $statement, $result );

	/** User */

	// Delete users
	if ( ! empty( $_POST['bbpress-delete-imported-users'] ) ) {
		$sql_users = $bbp_db->get_results( "SELECT `user_id` FROM `{$bbp_db->usermeta}` WHERE `meta_key` = '_bbp_user_id'", OBJECT_K );
		if ( ! empty( $sql_users ) ) {
			$sql_meta = array();
			foreach ( $sql_users as $key => $value ) {
				$sql_meta[] = $key;
			}
			$statement  = __( 'Deleting User&hellip; %s', 'buddyboss' );
			$sql_meta   = implode( "', '", $sql_meta );
			$sql_delete = "DELETE FROM `{$bbp_db->users}` WHERE `ID` IN ('{$sql_meta}');";
			$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
			$messages[] = sprintf( $statement, $result );
			$statement  = __( 'Deleting User Meta&hellip; %s', 'buddyboss' );
			$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `user_id` IN ('{$sql_meta}');";
			$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
			$messages[] = sprintf( $statement, $result );
		}

		// Delete imported user metadata
	} else {
		$statement  = __( 'Deleting User Meta&hellip; %s', 'buddyboss' );
		$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` LIKE '%%_bbp_%%';";
		$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );
	}

	/** Converter */

	$statement  = __( 'Deleting Conversion Table&hellip; %s', 'buddyboss' );
	$table_name = $bbp_db->prefix . 'bbp_converter_translator';
	if ( $bbp_db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
		$bbp_db->query( "DROP TABLE {$table_name}" );
		$result = $success;
	} else {
		$result = $failed;
	}
	$messages[] = sprintf( $statement, $result );

	/** Options */

	$statement = __( 'Deleting Settings&hellip; %s', 'buddyboss' );
	bbp_delete_options();
	$messages[] = sprintf( $statement, $success );

	/** Roles */

	$statement = __( 'Deleting Roles and Capabilities&hellip; %s', 'buddyboss' );
	bbp_remove_roles();
	bbp_remove_caps();
	$messages[] = sprintf( $statement, $success );

	/** Output */

	if ( count( $messages ) ) {
		foreach ( $messages as $message ) {
			bbp_admin_tools_feedback( $message );
		}
	}
}

/**
 * Wrapper function to handle Repair Forums all the actions.
 *
 * @since BuddyBoss 1.1.8
 */
function bp_admin_forum_repair_tools_wrapper_function() {

	$type    = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
	$site_id = isset( $_POST['site_id'] ) ? sanitize_text_field( wp_unslash( $_POST['site_id'] ) ) : 0;

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce.
	$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );
	$check = 'bbpress-do-counts';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	if ( is_multisite() && bp_is_network_activated() ) {
		switch_to_blog( $site_id );
	}

	if ( 'bbp-sync-topic-meta' === $type ) {
		$status = bbp_admin_repair_topic_meta();
	} elseif ( 'bbp-sync-forum-meta' === $type ) {
		$status = bbp_admin_repair_forum_meta();
	} elseif ( 'bbp-sync-forum-visibility' === $type ) {
		$status = bbp_admin_repair_forum_visibility();
	} elseif ( 'bbp-sync-all-topics-forums' === $type ) {
		$status = bbp_admin_repair_freshness();
	} elseif ( 'bbp-sync-all-topics-sticky' === $type ) {
		$status = bbp_admin_repair_sticky();
	} elseif ( 'bbp-sync-all-reply-positions' === $type ) {
		$status = bbp_admin_repair_reply_menu_order();
	} elseif ( 'bbp-group-forums' === $type ) {
		$status = bbp_admin_repair_group_forum_relationship();
	} elseif ( 'bbp-forum-topics' === $type ) {
		$status = bbp_admin_repair_forum_topic_count();
	} elseif ( 'bbp-forum-replies' === $type ) {
		$status = bbp_admin_repair_forum_reply_count();
	} elseif ( 'bbp-topic-replies' === $type ) {
		$status = bbp_admin_repair_topic_reply_count();
	} elseif ( 'bbp-topic-members' === $type ) {
		$status = bbp_admin_repair_topic_voice_count();
	} elseif ( 'bbp-topic-hidden-replies' === $type ) {
		$status = bbp_admin_repair_topic_hidden_reply_count();
	} elseif ( 'bbp-user-topics' === $type ) {
		$status = bbp_admin_repair_user_topic_count();
	} elseif ( 'bbp-user-replies' === $type ) {
		$status = bbp_admin_repair_user_reply_count();
	} elseif ( 'bbp-user-favorites' === $type ) {
		$status = bbp_admin_repair_user_favorites();
	} elseif ( 'bbp-user-topic-subscriptions' === $type ) {
		$status = bbp_admin_repair_user_topic_subscriptions();
	} elseif ( 'bbp-user-forum-subscriptions' === $type ) {
		$status = bbp_admin_repair_user_forum_subscriptions();
	} elseif ( 'bbp-user-role-map' === $type ) {
		$status = bbp_admin_repair_user_roles();
	} elseif ( 'bbp-wp-role-restore' === $type ) {
		$status = bbp_restore_caps_from_wp_roles();
	} elseif ( 'bbp-migrate-buddyboss-forum-topic-subscription' === $type ) {
		$status = bb_subscriptions_migrate_users_forum_topic();
	} elseif ( 'bbp-migrate-bbpress-forum-topic-subscription' === $type ) {
		$status = bb_subscriptions_migrate_bbpress_users_forum_topic( false, $site_id );
	} elseif ( 'bb-migrate-bbpress-user-topic-favorites' === $type ) {
		$status = bb_admin_upgrade_user_favorites( false, $site_id );
	}

	if ( is_multisite() && bp_is_network_activated() ) {
		restore_current_blog();
	}

	if ( 0 === $status['status'] ) {
		wp_send_json_error( $status );
	} else {
		wp_send_json_success( $status );
	}
}
add_action( 'wp_ajax_bp_admin_forum_repair_tools_wrapper_function', 'bp_admin_forum_repair_tools_wrapper_function' );

/**
 * Migration to update user favorites to post meta table.
 *
 * @since BuddyBoss 2.3.4
 *
 * @param bool $is_background The current process is background or not.
 * @param int  $blog_id       The blog ID to migrate for this blog.
 *
 * @return array|void An array of the status code and the message.
 */
function bb_admin_upgrade_user_favorites( $is_background, $blog_id ) {
	global $bp_background_updater;

	$bbp_db = bbp_db();

	if ( $is_background ) {
		$offset = get_site_option( 'bb_upgrade_user_favorites_offset', 0 );
	} else {
		$offset = (int) filter_input( INPUT_POST, 'offset', FILTER_SANITIZE_NUMBER_INT );
		$offset = ! empty( $offset ) ? ( $offset - 1 ) : 0;
	}

	$results = $bbp_db->get_col( $bbp_db->prepare( "SELECT DISTINCT( u.ID ) FROM $bbp_db->users AS u INNER JOIN $bbp_db->usermeta AS um ON ( u.ID = um.user_id ) WHERE um.meta_key = %s GROUP BY u.ID ORDER BY u.ID ASC LIMIT %d OFFSET %d", $bbp_db->prefix . '_bbp_favorites', 20, $offset ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( ! empty( $results ) ) {
		$min_count = (int) apply_filters( 'bb_user_favorites_queue_min_count', 10 );

		if ( $is_background ) {
			$chunk_results = array_chunk( $results, $min_count );
			if ( ! empty( $chunk_results ) ) {
				foreach ( $chunk_results as $chunk_result ) {
					$bp_background_updater->data(
						array(
							array(
								'callback' => 'bb_migrate_users_topic_favorites',
								'args'     => array( $chunk_result, $blog_id ),
							),
						)
					);

					$bp_background_updater->save();
				}
			}

			$bp_background_updater->dispatch();
		} else {
			bb_migrate_users_topic_favorites( $results, $blog_id );
			$offset ++;
		}

		// Update the offset.
		$final_offset = $offset + count( $results );

		if ( ! $is_background ) {
			// The current process is in progress!
			return array(
				'status'  => 'running',
				'offset'  => $final_offset,
				'records' => sprintf(
				/* translators: total members */
					__( 'Copies %s favorites from user meta to topic meta.', 'buddyboss' ),
					bp_core_number_format( $final_offset )
				),
			);
		} else {
			update_site_option( 'bb_upgrade_user_favorites_offset', $final_offset );
			bb_admin_upgrade_user_favorites( $is_background, $blog_id );
		}
	} else {
		delete_site_option( 'bb_upgrade_user_favorites_offset' );

		if ( ! $is_background ) {
			// All done!
			return array(
				'status'  => 1,
				'message' => __( 'Copies favorites from user meta to topic meta&hellip; Complete!', 'buddyboss' ),
			);
		}
	}
}

/**
 * Upgrading user favorites to post meta table.
 *
 * @since BuddyBoss 2.3.4
 *
 * @param array $user_ids Array of user IDs.
 * @param int   $blog_id  The blog ID to migrate for this blog.
 *
 * @return void
 */
function bb_migrate_users_topic_favorites( $user_ids, $blog_id ) {
	$bbp_db = bbp_db();
	$switch = false;
	if ( is_multisite() && get_current_blog_id() !== $blog_id ) {
		$switch = true;
		switch_to_blog( $blog_id );
	}

	if ( ! empty( $user_ids ) ) {
		foreach ( $user_ids as $user_id ) {

			$new_favorite_key = '_bbp_favorite';
			$favorite_key     = $bbp_db->prefix . '_bbp_favorites';
			$favorite_topics  = get_user_meta( $user_id, $favorite_key, true );
			$favorite_topics  = array_filter( wp_parse_id_list( $favorite_topics ) );
			if ( ! empty( $favorite_topics ) ) {
				foreach ( $favorite_topics as $post_id ) {
					// Skip if already exists.
					if ( $bbp_db->get_var( $bbp_db->prepare( "SELECT COUNT(*) FROM {$bbp_db->postmeta} WHERE post_id = %d AND meta_key = %s AND meta_value = %s", $post_id, $new_favorite_key, $user_id ) ) ) { // phpcs:ignore
						continue;
					}

					// Add the post meta.
					add_post_meta( $post_id, $new_favorite_key, $user_id, false );
				}
			}
		}
	}

	if ( $switch ) {
		restore_current_blog();
	}
}
