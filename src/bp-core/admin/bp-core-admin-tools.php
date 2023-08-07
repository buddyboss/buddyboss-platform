<?php
/**
 * BuddyBoss Tools panel.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Render the BuddyBoss Tools page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_tools() {
	if ( ! defined( 'BP_DEFAULT_DATA_DIR' ) ) {
		define( 'BP_DEFAULT_DATA_DIR', buddypress()->plugin_dir . 'bp-core/' );
	}

	// Define overrides - only applicable to those running trunk
	if ( ! defined( 'BP_DEFAULT_DATA_URL' ) ) {
		define( 'BP_DEFAULT_DATA_URL', buddypress()->plugin_url . 'bp-core/' );
	}

	require_once BP_DEFAULT_DATA_DIR . 'bp-core-tools-default-data.php';

	bp_admin_tools_default_data_save();

	$users_data = require_once BP_DEFAULT_DATA_DIR . 'data/users.php';
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
		<div class="bp-admin-card section-default_data">

			<h2>
				<?php
				$meta_icon = bb_admin_icons( 'default_data' );
				if ( ! empty( $meta_icon ) ) {
					?>
					<i class="<?php echo esc_attr( $meta_icon ); ?>"></i>
					<?php
				}
				esc_html_e( 'Default Data', 'buddyboss' ); ?>
			</h2>

			<form action="" method="post" id="bp-admin-form" class="bp-admin-form">
				<fieldset>
					<legend><?php esc_html_e( 'What data do you want to import?', 'buddyboss' ); ?></legend>
					<ul class="items">
						<li class="users main">
							<label for="import-users">
								<input type="checkbox" class="main-header" name="bp[import-users]" id="import-users"
									   value="1" <?php bp_dd_imported_disabled( 'users', 'users' ); ?>/>
								<strong><?php _e( 'Members', 'buddyboss' ); ?></strong>
							</label>
							<ul>

								<?php if ( bp_is_active( 'xprofile' ) ) : ?>
									<li>
										<label for="import-profile">
											<input type="checkbox" class="checkbox" name="bp[import-profile]"
												   id="import-profile"
												   value="1" <?php bp_dd_imported_disabled( 'users', 'xprofile' ); ?>/>
											<?php _e( 'Profile fields (with data)', 'buddyboss' ); ?>
										</label>
									</li>
								<?php endif; ?>

								<?php if ( bp_is_active( 'friends' ) ) : ?>
									<li>
										<label for="import-friends">
											<input type="checkbox" class="checkbox" name="bp[import-friends]"
												   id="import-friends"
												   value="1" <?php bp_dd_imported_disabled( 'users', 'friends' ); ?>/>
											<?php _e( 'Connections', 'buddyboss' ); ?>
										</label>
									</li>
								<?php endif; ?>

								<?php if ( bp_is_active( 'activity' ) ) : ?>
									<li>
										<label for="import-activity">
											<input type="checkbox" class="checkbox" name="bp[import-activity]"
												   id="import-activity"
												   value="1" <?php bp_dd_imported_disabled( 'users', 'activity' ); ?>/>
											<?php _e( 'Activity posts', 'buddyboss' ); ?>
										</label>
									</li>
								<?php endif; ?>

								<?php if ( bp_is_active( 'messages' ) ) : ?>
									<li>
										<label for="import-messages">
											<input type="checkbox" class="checkbox" name="bp[import-messages]"
												   id="import-messages"
												   value="1" <?php bp_dd_imported_disabled( 'users', 'messages' ); ?>/>
											<?php _e( 'Private messages', 'buddyboss' ); ?>
										</label>
									</li>
								<?php endif; ?>

							</ul>
						</li>

						<?php if ( bp_is_active( 'groups' ) ) : ?>
							<li class="groups main">
								<label for="import-groups">
									<input type="checkbox" class="main-header" name="bp[import-groups]"
										   id="import-groups"
										   value="1" <?php bp_dd_imported_disabled( 'groups', 'groups' ); ?>/>
									<strong><?php _e( 'Groups', 'buddyboss' ); ?></strong>
								</label>
								<ul>

									<li>
										<label for="import-g-members">
											<input type="checkbox" class="checkbox" name="bp[import-g-members]"
												   id="import-g-members"
												   value="1" <?php bp_dd_imported_disabled( 'groups', 'members' ); ?>/>
											<?php _e( 'Members', 'buddyboss' ); ?>
										</label>
									</li>

									<?php
									if ( bp_is_active( 'activity' ) ) :
										?>
										<li>
											<label for="import-g-activity">

												<input type="checkbox" class="checkbox" name="bp[import-g-activity]"
													   id="import-g-activity"
													   value="1" <?php bp_dd_imported_disabled( 'groups', 'activity' ); ?>/>
												<?php _e( 'Activity posts', 'buddyboss' ); ?>
											</label>
										</li>
									<?php endif; ?>

									<?php
									if ( bp_is_active( 'forums' ) ) {
										?>
										<li>
											<label for="import-g-forums">

												<input type="checkbox" class="checkbox" name="bp[import-g-forums]"
													   id="import-g-forums"
													   value="1" <?php bp_dd_imported_disabled( 'groups', 'forums' ); ?>/>
												<?php _e( 'Forums in Groups (with data)', 'buddyboss' ); ?>
											</label>
										</li>
										<?php
									}
									?>

								</ul>
							</li>
						<?php endif; ?>

						<?php
						if ( bp_is_active( 'forums' ) ) {
							?>
							<li class="forums main">
								<label for="import-forums">
									<input type="checkbox" class="main-header" name="bp[import-forums]"
										   id="import-forums"
										   value="1" <?php bp_dd_imported_disabled( 'forums', 'forums' ); ?>/>
									<strong><?php _e( 'Forums', 'buddyboss' ); ?></strong>
								</label>
								<ul>
									<li>
										<label for="import-f-topics">

											<input type="checkbox" class="checkbox" name="bp[import-f-topics]"
												   id="import-f-topics"
												   value="1" <?php bp_dd_imported_disabled( 'forums', 'topics' ); ?>/>
											<?php _e( 'Discussions', 'buddyboss' ); ?>
										</label>
									</li>
									<li>
										<label for="import-f-replies">

											<input type="checkbox" class="checkbox" name="bp[import-f-replies]"
												   id="import-f-replies"
												   value="1" <?php bp_dd_imported_disabled( 'forums', 'replies' ); ?>/>
											<?php _e( 'Replies', 'buddyboss' ); ?>
										</label>
									</li>
								</ul>
							</li>
							<?php
						}
						?>

					</ul>
					<!-- .items -->

					<p class="submit">
						<input class="button-primary" type="submit" name="bp-admin-submit" id="bp-admin-submit"
							   value="<?php esc_attr_e( 'Import Selected Data', 'buddyboss' ); ?>"/>
						<input class="button" type="submit" name="bp-admin-clear" id="bp-admin-clear"
							   value="<?php esc_attr_e( 'Clear Default Data', 'buddyboss' ); ?>"/>
					</p>
				</fieldset>

				<?php wp_nonce_field( 'bp-admin-tools-default-data' ); ?>
			</form>

			<p class="description"><?php esc_html_e( 'Some of these tools utilize substantial database resources. Avoid running more than 1 import job at a time.', 'buddyboss' ); ?></p>

		</div>
	</div>
	<?php
}

/**
 * Render the BuddyBoss Repair Community page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_repair_community_submenu_page() {
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
		<div class="bp-admin-card section-repair_community">

			<h2>
				<?php
				$meta_icon = bb_admin_icons( 'repair_community' );
				if ( ! empty( $meta_icon ) ) {
					?>
					<i class="<?php echo esc_attr( $meta_icon ); ?>"></i>
					<?php
				}
				esc_html_e( 'Repair Community', 'buddyboss' );
				?>
			</h2>

			<p><?php esc_html_e( 'BuddyBoss keeps track of various relationships between members, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration. Use the tools below to manually recalculate these relationships.', 'buddyboss' ); ?></p>

			<form class="settings" method="post" action="">
				<fieldset>
					<legend><?php esc_html_e( 'Data to Repair:', 'buddyboss' ); ?></legend>

					<div class="checkbox">
						<?php
						foreach ( bp_admin_repair_list() as $item ) :
							$disabled = (bool) ( isset( $item[3] ) ? $item[3] : false );
							?>
							<label for="<?php echo esc_attr( str_replace( '_', '-', $item[0] ) ); ?>" class="<?php echo esc_attr( 'label-' . $item[0] ) . ( true === $disabled ? esc_attr( ' disabled' ) : '' ); ?>">
								<input
										type="checkbox"
										class="checkbox"
										name="<?php echo esc_attr( $item[0] ) . '" id="' . esc_attr( str_replace( '_', '-', $item[0] ) ); ?>"
										value="<?php echo esc_attr( $item[0] ); ?>"
									<?php
									if ( isset( $_GET['tool'] ) && $_GET['tool'] == esc_attr( str_replace( '_', '-', $item[0] ) ) ) {
										echo 'checked'; }
									disabled( $disabled );
									?>
								/> <?php echo esc_html( $item[1] ); ?></label>
						<?php endforeach; ?>
					</div>

					<p class="submit">
						<?php wp_nonce_field( 'bp-do-counts' ); ?>
						<a class="button-primary" id="bp-tools-submit"><?php esc_attr_e( 'Repair Items', 'buddyboss' ); ?></a>
					</p>

				</fieldset>
			</form>
		</div>
	</div>

	<?php
}

/**
 * Handle the processing and feedback of the admin tools page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_repair_handler() {
	if ( ! bp_is_post_request() || empty( $_POST['bp-tools-submit'] ) ) {
		return;
	}

	check_admin_referer( 'bp-do-counts' );

	// Bail if user cannot moderate.
	$capability = bp_core_do_network_admin() ? 'manage_network_options' : 'manage_options';
	if ( ! bp_current_user_can( $capability ) ) {
		return;
	}

	wp_cache_flush();
	$messages = array();

	foreach ( (array) bp_admin_repair_list() as $item ) {
		if ( isset( $item[2] ) && isset( $_POST[ $item[0] ] ) && 1 === absint( $_POST[ $item[0] ] ) && is_callable( $item[2] ) ) {
			$messages[] = call_user_func( $item[2] );
		}
	}

	if ( count( $messages ) ) {
		foreach ( $messages as $message ) {
			bp_admin_tools_feedback( $message[1] );
		}
	}
}

add_action( bp_core_admin_hook(), 'bp_admin_repair_handler' );

/**
 * Get the array of the repair list.
 *
 * @return array
 */
function bp_admin_repair_list() {
	$repair_list = array();

	// Members:
	// - member count
	// - last_activity migration (2.0).
	$repair_list[20] = array(
		'bp-total-member-count',
		esc_html__( 'Repair total members count', 'buddyboss' ),
		'bp_admin_repair_count_members',
	);

	$repair_list[25] = array(
		'bp-last-activity',
		esc_html__( 'Repair member "last activity" data', 'buddyboss' ),
		'bp_admin_repair_last_activity',
	);

	// Xprofile:
	// - default xprofile groups/fields
	$repair_list[35] = array(
		'bp-xprofile-fields',
		esc_html__( 'Repair default profile set and fields', 'buddyboss' ),
		'repair_default_profiles_fields',
	);

	$repair_list[36] = array(
		'bp-xprofile-wordpress-resync',
		esc_html__( 'Re-sync BuddyBoss profile fields to WordPress profile fields', 'buddyboss' ),
		'resync_xprofile_wordpress_fields',
	);

	$repair_list[37] = array(
		'bp-wordpress-xprofile-resync',
		esc_html__( 'Re-sync WordPress profile fields to BuddyBoss profile fields', 'buddyboss' ),
		'resync_wordpress_xprofile_fields',
	);

	$repair_list[38] = array(
		'bp-wordpress-update-display-name',
		esc_html__( 'Update display name to selected format in profile settings', 'buddyboss' ),
		'xprofile_update_display_names',
	);

	// Connections:
	// - user friend count.
	if ( bp_is_active( 'friends' ) ) {
		$repair_list[0] = array(
			'bp-user-friends',
			esc_html__( 'Repair total connections count for each member', 'buddyboss' ),
			'bp_admin_repair_friend_count',
		);
	}

	// Group repair actions.
	if ( bp_is_active( 'groups' ) ) {

		// User group count.
		$repair_list[10] = array(
			'bp-group-count',
			esc_html__( 'Repair total groups count for each member', 'buddyboss' ),
			'bp_admin_repair_group_count',
		);

		// Recalculate group members count for each group.
		$repair_list[124] = array(
			'bp-group-members-count',
			esc_html__( 'Recalculate the total members count for each group', 'buddyboss' ),
			'bp_admin_repair_group_member_count',
		);
	}

	// Blogs:
	// - user blog count.
	if ( bp_is_active( 'blogs' ) ) {
		$repair_list[90] = array(
			'bp-blog-records',
			esc_html__( 'Repopulate site tracking records', 'buddyboss' ),
			'bp_admin_repair_blog_records',
		);
	}

	// Emails:
	// - install missing emails.
	$repair_list[99] = array(
		'bp-missing-emails',
		esc_html__( 'Install missing emails (restore missing emails from defaults)', 'buddyboss' ),
		'bp_admin_install_emails',
		( isset( $_GET['tool'] ) && 'bp-reinstall-emails' === $_GET['tool'] ),
	);

	// - reinstall emails.
	$repair_list[100] = array(
		'bp-reinstall-emails',
		esc_html__( 'Reset emails (delete and restore from defaults)', 'buddyboss' ),
		'bp_admin_reinstall_emails',
		( isset( $_GET['tool'] ) && 'bp-missing-emails' === $_GET['tool'] ),
	);

	// Check whether member type is enabled.
	if ( true === bp_member_type_enable_disable() ) {
		$member_types      = bp_get_active_member_types();
		$existing_selected = bp_member_type_default_on_registration();
		if ( isset( $member_types ) && ! empty( $member_types ) && '' !== $existing_selected ) {
			// - Assign default member type.
			$repair_list[101] = array(
				'bp-assign-member-type',
				esc_html__( 'Assign members without a profile type to the default profile type (excludes admins)', 'buddyboss' ),
				'bp_admin_assign_member_type',
			);
		}
	}

	// Update user activity favorites data.
	if ( bp_is_active( 'activity' ) ) {
		$repair_list[85] = array(
			'bp-sync-activity-favourite',
			esc_html__( 'Update activity favorites data', 'buddyboss' ),
			'bp_admin_update_activity_favourite',
		);
	}

	// Invitations:
	// - maybe create the database table and migrate any existing group invitations.
	$repair_list[110] = array(
		'bp-invitations-table',
		esc_html__( 'Create the database table for Invitations and migrate existing group invitations if needed', 'buddyboss' ),
		'bp_admin_invitations_table',
	);

	// Sync profile completion widget.
	$repair_list[111] = array(
		'bp-sync-profile-completion-widget',
		esc_html__( 'Re-Sync Profile Completion widget profile photo status', 'buddyboss' ),
		'bb_sync_profile_completion_widget',
	);

	ksort( $repair_list );

	/**
	 * Filters the array of the repair list.
	 *
	 * @since BuddyPress 2.0.0
	 *
	 * @param array $repair_list Array of values for the Repair list options.
	 */
	return (array) apply_filters( 'bp_repair_list', $repair_list );
}

/**
 * Recalculate friend counts for each user.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array
 */
function bp_admin_repair_friend_count() {
	global $wpdb;

	if ( ! bp_is_active( 'friends' ) ) {
		return;
	}

	$statement = __( 'Repairing total connections count for each member &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	$sql_delete = "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ( 'total_friend_count' );";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) ) {
		return array( 1, sprintf( $statement, $result ) );
	}

	$bp = buddypress();

	// Walk through all users on the site.
	$total_users = $wpdb->get_row( "SELECT count(ID) as c FROM {$wpdb->users}" )->c;

	$updated = array();
	if ( $total_users > 0 ) {
		$per_query = 500;
		$offset    = 0;
		while ( $offset < $total_users ) {
			// Only bother updating counts for users who actually have friendships.
			$friendships = $wpdb->get_results( $wpdb->prepare( "SELECT initiator_user_id, friend_user_id FROM {$bp->friends->table_name} WHERE is_confirmed = 1 AND ( ( initiator_user_id > %d AND initiator_user_id <= %d ) OR ( friend_user_id > %d AND friend_user_id <= %d ) )", $offset, $offset + $per_query, $offset, $offset + $per_query ) );

			// The previous query will turn up duplicates, so we
			// filter them here.
			foreach ( $friendships as $friendship ) {
				if ( ! isset( $updated[ $friendship->initiator_user_id ] ) ) {
					BP_Friends_Friendship::total_friend_count( $friendship->initiator_user_id );
					$updated[ $friendship->initiator_user_id ] = 1;
				}

				if ( ! isset( $updated[ $friendship->friend_user_id ] ) ) {
					BP_Friends_Friendship::total_friend_count( $friendship->friend_user_id );
					$updated[ $friendship->friend_user_id ] = 1;
				}
			}

			$offset += $per_query;
		}
	} else {
		return array( 2, sprintf( $statement, $result ) );
	}

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recalculate group counts for each user.
 *
 * @since BuddyPress 2.0.0
 *
 * @return array
 */
function bp_admin_repair_group_count() {
	global $wpdb;

	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	$statement = __( 'Repairing total groups count for each member &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed!', 'buddyboss' );

	$sql_delete = "DELETE FROM {$wpdb->usermeta} WHERE meta_key IN ( 'total_group_count' );";
	if ( is_wp_error( $wpdb->query( $sql_delete ) ) ) {
		return array( 1, sprintf( $statement, $result ) );
	}

	$bp = buddypress();

	// Walk through all users on the site.
	$total_users = $wpdb->get_row( "SELECT count(ID) as c FROM {$wpdb->users}" )->c;

	if ( $total_users > 0 ) {
		$per_query = 500;
		$offset    = 0;
		while ( $offset < $total_users ) {
			// But only bother to update counts for users that have groups.
			$users = $wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$bp->groups->table_name_members} WHERE is_confirmed = 1 AND is_banned = 0 AND user_id > %d AND user_id <= %d", $offset, $offset + $per_query ) );

			foreach ( $users as $user ) {
				BP_Groups_Member::refresh_total_group_count_for_user( $user );
			}

			$offset += $per_query;
		}
	} else {
		return array( 2, sprintf( $statement, $result ) );
	}

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Recalculate user-to-blog relationships and useful blog meta data.
 *
 * @since BuddyPress 2.1.0
 *
 * @return array
 */
function bp_admin_repair_blog_records() {

	// Description of this tool, displayed to the user.
	$statement = __( 'Repopulating Blogs records&hellip; %s', 'buddyboss' );

	// Default to failure text.
	$result = __( 'Failed!', 'buddyboss' );

	// Default to unrepaired.
	$repair = false;

	// Run function if blogs component is active.
	if ( bp_is_active( 'blogs' ) ) {
		$repair = bp_blogs_record_existing_blogs();
	}

	// Setup success/fail messaging.
	if ( true === $repair ) {
		$result = __( 'Complete!', 'buddyboss' );
	}

	// All done!
	return array(
		'status'  => 1,
		'message' => sprintf( $statement, $result ),
	);
}

/**
 * Recalculate the total number of active site members.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_repair_count_members() {
	$statement = __( 'Repairing total members count &hellip; %s', 'buddyboss' );
	delete_transient( 'bp_active_member_count' );
	bp_core_get_active_member_count();

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Repair user last_activity data.
 *
 * Re-runs the migration from usermeta introduced in BP 2.0.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_repair_last_activity() {
	$statement = __( 'Repairing member "last activity" data &hellip; %s', 'buddyboss' );
	bp_last_activity_migrate();

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Repair default profile fields.
 *
 * @since BuddyBoss 1.0.0
 */
function repair_default_profiles_fields() {
	global $wpdb;

	$bp_prefix = bp_core_get_table_prefix();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';

	bp_core_install_default_profiles_fields();

	if ( ! bp_get_option( 'bp-xprofile-base-group-id' ) ) {
		bp_update_option( 'bp-xprofile-base-group-id', 1 );
	}

	if ( ! bp_get_option( 'bp-xprofile-firstname-field-id' ) ) {
		bp_update_option( 'bp-xprofile-firstname-field-id', 1 );
	}

	// First name field id.
	$first_name_id = bp_xprofile_firstname_field_id();

	// Last name field id.
	$last_name_id = bp_xprofile_lastname_field_id();

	// Nickname field id.
	$nickname_id = bp_xprofile_nickname_field_id();

	// Query to remove all duplicate first name fields.
	$first_name = $wpdb->prepare( "DELETE FROM {$bp_prefix}bp_xprofile_fields WHERE can_delete = %d AND parent_id = %d AND is_required = %d AND name = %s AND type = %s AND id != %d", 0, 0, 1, 'First Name', 'textbox', $first_name_id );

	// Query to remove all duplicate last name fields.
	$last_name = $wpdb->prepare( "DELETE FROM {$bp_prefix}bp_xprofile_fields WHERE can_delete = %d AND parent_id = %d AND is_required = %d AND name = %s AND type = %s AND id != %d", 0, 0, 1, 'Last Name', 'textbox', $last_name_id );

	// Query to remove all duplicate nick name fields.
	$nick_name = $wpdb->prepare( "DELETE FROM {$bp_prefix}bp_xprofile_fields WHERE can_delete = %d AND parent_id = %d AND is_required = %d AND name = %s AND type = %s AND id != %d", 0, 0, 1, 'Nickname', 'textbox', $nickname_id );

	// Remove all duplicate first name fields.
	$wpdb->query( $first_name );

	// Remove all duplicate last name fields.
	$wpdb->query( $last_name );

	// Remove all duplicate nick name fields.
	$wpdb->query( $nick_name );

	// Clear profile completion data
	bp_core_xprofile_clear_all_user_progress_cache();

	$statement = __( 'Repairing default profile set and fields &hellip; %s', 'buddyboss' );

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
	);
}

/**
 * Resync BuddyBoss profile data to WordPress.
 *
 * @since BuddyBoss 1.0.0
 */
function resync_xprofile_wordpress_fields() {

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$args = array(
		'number' => 50,
		'fields' => array( 'ID' ),
		'offset' => $offset,
	);

	$users = get_users( $args );

	if ( ! empty( $users ) ) {
		array_map( 'xprofile_sync_wp_profile', wp_list_pluck( $users, 'ID' ) );
		foreach ( $users as $user ) {
			$offset++;
		}

		$records_updated = sprintf( __( '%s members updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Re-syncing BuddyBoss profile fields to WordPress profile fields &hellip; %s', 'buddyboss' );
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Resync WordPress profile data to BuddyBoss.
 *
 * @since BuddyBoss 1.0.0
 */
function resync_wordpress_xprofile_fields() {

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$args = array(
		'number' => 50,
		'fields' => array( 'ID', 'user_nicename' ),
		'offset' => $offset,
	);

	$users = get_users( $args );

	if ( ! empty( $users ) ) {
		foreach ( $users as $user ) {
			xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user->ID, get_user_meta( $user->ID, 'first_name', true ) );
			xprofile_set_field_data( bp_xprofile_lastname_field_id(), $user->ID, get_user_meta( $user->ID, 'last_name', true ) );

			// make sure nickname is valid
			$nickname = get_user_meta( $user->ID, 'nickname', true );
			$nickname = sanitize_title( $nickname );
			$invalid  = bp_xprofile_validate_nickname_value( '', bp_xprofile_nickname_field_id(), $nickname, $user->ID );

			// or use the user_nicename
			if ( ! $nickname || $invalid ) {
				$nickname = $user->user_nicename;
			}

			bp_update_user_meta( $user->ID, 'nickname', $nickname );
			xprofile_set_field_data( bp_xprofile_nickname_field_id(), $user->ID, $nickname );
			$offset++;
		}
		$records_updated = sprintf( __( '%s members updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Re-syncing WordPress profile fields to BuddyBoss profile fields &hellip; %s', 'buddyboss' );
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Update member display names.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_update_display_names() {

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$args = array(
		'number' => 50,
		'fields' => array( 'ID', 'display_name' ),
		'offset' => $offset,
	);

	$users = get_users( $args );

	if ( ! empty( $users ) ) {

		foreach ( $users as $user ) {
			$display_name = bp_core_get_user_displayname( $user->ID );

			wp_update_user(
				$args = array(
					'ID'           => $user->ID,
					'display_name' => $display_name,
				)
			);
			$offset++;
		}
		$records_updated = sprintf( __( '%s members updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Updating display name to selected format in profile settings &hellip; %s', 'buddyboss' );
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Assemble admin notices relating success/failure of repair processes.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string      $message Feedback message.
 * @param string|bool $class Unused.
 *
 * @return false|Closure
 */
function bp_admin_tools_feedback( $message, $class = false ) {
	if ( is_string( $message ) ) {
		$message = '<p>' . $message . '</p>';
		$class   = $class ? $class : 'updated';
	} elseif ( is_wp_error( $message ) ) {
		$errors = $message->get_error_messages();

		switch ( count( $errors ) ) {
			case 0:
				return false;

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

	add_action( bp_core_do_network_admin() ? 'network_admin_notices' : 'admin_notices', $lambda );

	return $lambda;
}

/**
 * Render the Available Tools page.
 *
 * We register this page on Network Admin as a top-level home for our
 * BuddyPress tools. This displays the default content.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_available_tools_page() {
	?>
	<div class="wrap">
		<h1><?php esc_attr_e( 'Tools', 'buddyboss' ); ?></h1>

		<?php

		/**
		 * Fires inside the markup used to display the Available Tools page.
		 *
		 * @since BuddyPress 2.0.0
		 */
		do_action( 'bp_network_tool_box' );
		?>

	</div>
	<?php
}

/**
 * Render an introduction of BuddyPress tools on Available Tools page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_available_tools_intro() {
	$query_arg = array(
		'page' => 'bp-tools',
	);

	$page = bp_core_do_network_admin() ? 'admin.php' : 'admin.php';
	$url  = add_query_arg( $query_arg, bp_get_admin_url( $page ) );
	?>
	<div class="card tool-box">
		<h2><?php esc_html_e( 'BuddyBoss Tools', 'buddyboss' ); ?></h2>
		<p>
			<?php esc_html_e( 'BuddyBoss keeps track of various relationships between users, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration.', 'buddyboss' ); ?>
			<?php printf( esc_html__( 'Use the %s to repair these relationships.', 'buddyboss' ), '<a href="' . esc_url( $url ) . '">' . esc_html__( 'BuddyBoss Tools', 'buddyboss' ) . '</a>' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Install Missing emails from defaults.
 *
 * @since BuddyBoss 1.9.3
 *
 * @return array
 */
function bp_admin_install_emails() {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		bp_register_taxonomies();

		$switched = true;
	}

	$defaults = array(
		'post_status' => 'publish',
		'post_type'   => bp_get_email_post_type(),
	);

	$emails          = bp_email_get_schema();
	$descriptions    = bp_email_get_type_schema();
	$installed_email = 0;

	// Add these emails to the database.
	foreach ( $emails as $id => $email ) {
		if (
			term_exists( $id, bp_get_email_tax_type() ) &&
			get_terms(
				array(
					'taxonomy' => bp_get_email_tax_type(),
					'slug'     => $id,
					'fields'   => 'count',
				)
			) > 0
		) {
			continue;
		}

		// Some emails are multisite-only.
		if ( ! is_multisite() && isset( $email['args'] ) && ! empty( $email['args']['multisite'] ) ) {
			continue;
		}

		$post_id = wp_insert_post( bp_parse_args( $email, $defaults, 'install_email_' . $id ) );
		if ( ! $post_id ) {
			continue;
		}

		$tt_ids = wp_set_object_terms( $post_id, $id, bp_get_email_tax_type() );
		foreach ( $tt_ids as $tt_id ) {
			$term = get_term_by( 'term_taxonomy_id', (int) $tt_id, bp_get_email_tax_type() );
			wp_update_term(
				(int) $term->term_id,
				bp_get_email_tax_type(),
				array(
					'description' => $descriptions[ $id ],
				)
			);
		}

		$installed_email ++;
	}

	if ( $switched ) {
		restore_current_blog();
	}

	return array(
		'status'  => 1,
		'records' => sprintf(
		/* translators: %1$s for counts missing emails. */
			__( '%1$s missing emails have been installed.', 'buddyboss' ),
			$installed_email,
		),
		'message' => sprintf(
		/* translators: %1$s for view emails url. */
			__( 'Installing missing emails &hellip; Complete! %1$s', 'buddyboss' ),
			'<a href="' . get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() ) . '">' . esc_html__( 'View Emails.', 'buddyboss' ) . '</a>'
		),
	);
}

/**
 * Delete emails and restore from defaults.
 *
 * @since BuddyPress 2.5.0
 *
 * @return array
 */
function bp_admin_reinstall_emails() {
	$switched = false;

	// Switch to the root blog, where the email posts live.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		bp_register_taxonomies();

		$switched = true;
	}

	$emails = get_posts(
		array(
			'fields'                 => 'ids',
			'post_status'            => 'publish',
			'post_type'              => bp_get_email_post_type(),
			'posts_per_page'         => 200,
			'suppress_filters'       => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	if ( $emails ) {
		foreach ( $emails as $email_id ) {
			wp_trash_post( $email_id );
		}
	}

	// Make sure we have no orphaned email type terms.
	$email_types = get_terms(
		bp_get_email_tax_type(),
		array(
			'fields'                 => 'ids',
			'hide_empty'             => false,
			'update_term_meta_cache' => false,
		)
	);

	if ( $email_types ) {
		foreach ( $email_types as $term_id ) {
			wp_delete_term( (int) $term_id, bp_get_email_tax_type() );
		}
	}

	require_once buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php';
	bp_core_install_emails();

	if ( $switched ) {
		restore_current_blog();
	}

	return array(
		'status'  => 1,
		'message' => sprintf(
		/* translators: %1$s for view emails url. */
			__( 'Reseting emails &hellip; Complete! %1$s', 'buddyboss' ),
			'<a href="' . get_admin_url( bp_get_root_blog_id(), 'edit.php?post_type=' . bp_get_email_post_type() ) . '">' . esc_html__( 'View Emails.', 'buddyboss' ) . '</a>'
		),
	);
}

/**
 * Add notice on the "Tools > BuddyPress" page if more sites need recording.
 *
 * This notice only shows up in the network admin dashboard.
 *
 * @since BuddyPress 2.6.0
 */
function bp_core_admin_notice_repopulate_blogs_resume() {
	$screen = get_current_screen();
	if ( 'tools_page_bp-tools-network' !== $screen->id ) {
		return;
	}

	if ( '' === bp_get_option( '_bp_record_blogs_offset' ) ) {
		return;
	}

	echo '<div class="error"><p>' . __( 'It looks like you have more sites to record. Resume recording by checking the "Repopulate site tracking records" option.', 'buddyboss' ) . '</p></div>';
}

add_action( 'network_admin_notices', 'bp_core_admin_notice_repopulate_blogs_resume' );

/**
 * Assign members without a profile type to the default profile type (excludes admins).
 *
 * @since BuddyBoss 1.0.0
 *
 * @return array
 */
function bp_admin_assign_member_type() {

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$args = array(
		'number' => 50,
		'fields' => array( 'ID' ),
		'offset' => $offset,
	);

	$users = get_users( $args );

	if ( ! empty( $users ) ) {

		foreach ( $users as $user ) {

			$member_type = bp_get_member_type( $user->ID );

			if ( false === $member_type ) {

				// Get the user object.
				$user1 = get_userdata( $user->ID );

				if ( ! in_array( 'administrator', $user1->roles, true ) ) {

					$existing_selected = bp_member_type_default_on_registration();
					// Assign the default member type to user.
					bp_set_member_type( $user->ID, '' );
					bp_set_member_type( $user->ID, $existing_selected );
				}
			}
			$offset++;
		}
		$records_updated = sprintf( __( '%s members updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		// Description of this tool, displayed to the user.
		$statement = __( 'Assign users without a profile type to the default profile type records&hellip; %s', 'buddyboss' );
		$result    = __( 'Complete!', 'buddyboss' );
		// All done!
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

}

function bp_admin_repair_nickname_value() {

	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	$args  = array(
		'number' => 50,
		'fields' => array( 'ID' ),
		'offset' => $offset,
	);
	$users = get_users( $args );

	if ( ! empty( $users ) ) {

		foreach ( $users as $user ) {
			$nickname = xprofile_get_field_data( bp_xprofile_nickname_field_id(), $user->ID );
			if ( preg_match( '/[A-Z]/', $nickname ) ) {
				xprofile_set_field_data(
					bp_xprofile_nickname_field_id(),
					bp_loggedin_user_id(),
					strtolower( $nickname )
				);
			}
			$offset++;
		}
		$records_updated = sprintf( __( '%s members updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		// Description of this tool, displayed to the user.
		$statement = __( 'Repair Nickname&hellip; %s', 'buddyboss' );
		$result    = __( 'Complete!', 'buddyboss' );

		// All done!
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

}

/**
 * Wrapper function to handle Repair Community all the actions.
 *
 * @since BuddyBoss 1.1.8
 */
function bp_admin_repair_tools_wrapper_function() {
	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss' )
		),
	);

	$type = bb_filter_input_string( INPUT_POST, 'type' );

	if ( empty( $type ) ) {
		wp_send_json_error( $response );
	}

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	if ( empty( $_POST['nonce'] ) ) {
		wp_send_json_error( $response );
	}

	// Use default nonce
	$nonce = $_POST['nonce'];
	$check = 'bp-do-counts';

	// Nonce check!
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, $check ) ) {
		wp_send_json_error( $response );
	}

	$repair_list = bp_admin_repair_list();

	$status = array();
	foreach ( $repair_list as $repair_item ) {
		if ( $repair_item[0] === $type && is_callable( $repair_item[2] ) ) {
			$status = call_user_func( $repair_item[2] );
			break;
		}
	}

	// if ( 'bp-user-friends' === $type ) {
	// $status = bp_admin_repair_friend_count();
	// } elseif ( 'bp-group-count' === $type ) {
	// $status = bp_admin_repair_group_count();
	// } elseif ( 'bp-total-member-count' === $type ) {
	// $status = bp_admin_repair_count_members();
	// } elseif ( 'bp-last-activity' === $type ) {
	// $status = bp_admin_repair_last_activity();
	// } elseif ( 'bp-xprofile-fields' === $type ) {
	// $status = repair_default_profiles_fields();
	// } elseif ( 'bp-xprofile-wordpress-resync' === $type ) {
	// $status = resync_xprofile_wordpress_fields();
	// } elseif ( 'bp-wordpress-xprofile-resync' === $type ) {
	// $status = resync_wordpress_xprofile_fields();
	// } elseif ( 'bp-wordpress-update-display-name' === $type ) {
	// $status = xprofile_update_display_names();
	// } elseif ( 'bp-blog-records' === $type ) {
	// $status = bp_admin_repair_blog_records();
	// } elseif ( 'bp-reinstall-emails' === $type ) {
	// $status = bp_admin_reinstall_emails();
	// } elseif ( 'bp-assign-member-type' === $type ) {
	// $status = bp_admin_assign_member_type();
	// } elseif ( 'bp-sync-activity-favourite' === $type ) {
	// $status = bp_admin_update_activity_favourite();
	// } elseif ( 'bp-invitations-table' === $type ) {
	// $status = bp_admin_invitations_table();
	// } elseif ( 'bp-media-forum-privacy-repair' === $type ) {
	// $status = bp_media_forum_privacy_repair();
	// }
	wp_send_json_success( $status );
}
add_action( 'wp_ajax_bp_admin_repair_tools_wrapper_function', 'bp_admin_repair_tools_wrapper_function' );

/**
 * Check if BuddyPress activity favorites data needs upgrade & Update to BuddyBoss activity like data
 *
 * @since BuddyBoss 1.3.3
 */
function bp_admin_update_activity_favourite() {

	$bp_activity_favorites = bp_get_option( 'bp_activity_favorites', false );

	if ( ! $bp_activity_favorites ) {

		$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

		$args = array(
			'number' => 50,
			'offset' => $offset,
		);

		$users = get_users( $args );

		if ( ! empty( $users ) ) {

			foreach ( $users as $user ) {
				$user_favs = bp_get_user_meta( $user->ID, 'bp_favorite_activities', true );
				if ( empty( $user_favs ) || ! is_array( $user_favs ) ) {
					$offset ++;
					continue;
				}
				foreach ( $user_favs as $fav ) {

					// Update the users who have favorited this activity.
					$favorite_users = bp_activity_get_meta( $fav, 'bp_favorite_users', true );
					if ( empty( $favorite_users ) || ! is_array( $favorite_users ) ) {
						$favorite_users = array();
					}
					// Add to activity's favorited users.
					$favorite_users[] = $user->ID;

					// Update activity meta
					bp_activity_update_meta( $fav, 'bp_favorite_users', array_unique( $favorite_users ) );

				}
				$offset ++;
			}

			$records_updated = sprintf( __( '%s members activity favorite updated successfully.', 'buddyboss' ), bp_core_number_format( $offset ) );

			return array(
				'status'  => 'running',
				'offset'  => $offset,
				'records' => $records_updated,
			);

		} else {

			bp_update_option( 'bp_activity_favorites', true );

			$statement = __( 'Updating activity favorites data &hellip; %s', 'buddyboss' );

			return array(
				'status'  => 1,
				'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
			);
		}
	} else {
		$statement = __( 'Updating activity favorites data &hellip; %s', 'buddyboss' );

		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}


/**
 * Create the invitations database table if it does not exist.
 * Migrate outstanding group invitations if needed.
 *
 * @since BuddyBoss 1.3.5
 *
 * @return array
 */
function bp_admin_invitations_table() {
	global $wpdb;

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

	/* translators: %s: the result of the action performed by the repair tool */
	$statement = __( 'Creating the database table for Invitations if it does not exist &hellip; %s', 'buddyboss' );
	$result    = __( 'Failed to create table!', 'buddyboss' );

	bp_core_install_invitations();

	// Check for existence of invitations table.
	$table_name = BP_Invitation_Manager::get_table_name();
	$query      = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table_name ) );
	if ( ! $wpdb->get_var( $query ) == $table_name ) {
		return array(
			'status'  => 2,
			'message' => sprintf( $statement, $result ),
		);
	} else {
		$result = __( 'Complete!', 'buddyboss' );
	}

	// Migrate group invitations if needed.
	if ( bp_is_active( 'groups' ) ) {
		$bp = buddypress();

		/* translators: %s: the result of the action performed by the repair tool */
		$migrate_statement = __( 'Migrating existing group invitations &hellip; %s', 'buddyboss' );
		$migrate_result    = __( 'Failed to migrate invitations!', 'buddyboss' );

		bp_groups_migrate_invitations();

		// Check that there are no outstanding group invites in the group_members table.
		$records = $wpdb->get_results( "SELECT id FROM {$bp->groups->table_name_members} WHERE is_confirmed = 0 AND is_banned = 0" );
		if ( empty( $records ) ) {
			$migrate_result = __( 'Complete!', 'buddyboss' );

			return array(
				'status'  => 0,
				'message' => sprintf( $statement . ' ' . $migrate_statement, $result, $migrate_result ),
			);
		} else {
			return array(
				'status'  => 2,
				'message' => sprintf( $statement . ' ' . $migrate_statement, $result, $migrate_result ),
			);
		}
	}

	// Return a "create-only" success message.
	return array(
		'status'  => 0,
		'message' => sprintf( $statement, $result ),
	);
}

/**
 * Function will sync profile uploaded photo for profile completion widget data.
 *
 * @since BuddyBoss 2.0.9
 *
 * @return array
 */
function bb_sync_profile_completion_widget() {
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;

	// Users args.
	$args = array(
		'number'   => 50,
		'fields'   => array( 'ID' ),
		'meta_key' => 'bp_profile_completion_widgets',
		'offset'   => $offset,
	);

	$users = get_users( $args );
	if ( ! empty( $users ) ) {
		foreach ( $users as $user ) {
			// Get existing user meta who have profile completion widget data in DB.
			$get_user_data = bp_get_user_meta( $user->ID, 'bp_profile_completion_widgets', true );
			if ( ! empty( $get_user_data ) ) {
				$total_completed_count = isset( $get_user_data['completed_fields'] ) ? $get_user_data['completed_fields'] : 0;

				if (
					isset( $get_user_data['photo_type'] ) &&
					isset( $get_user_data['photo_type']['profile_photo'] ) &&
					isset( $get_user_data['photo_type']['profile_photo']['is_uploaded'] )
				) {
					$is_profile_photo_uploaded = ( bp_get_user_has_avatar( $user->ID ) ) ? 1 : 0;

					if ( ! $is_profile_photo_uploaded &&
					     bp_enable_profile_gravatar() &&
					     'blank' !== get_option( 'avatar_default', 'mystery' )
					) {
						/**
						 * There is not any direct way to check gravatar set for user.
						 * Need to check $profile_url is send 200 status or not.
						 */
						$profile_url = get_avatar_url( $user->ID, array( 'default' => '404' ) );

						$headers = get_headers( $profile_url, 1 );
						if ( $headers[0] === 'HTTP/1.1 200 OK' && isset( $headers['Link'] ) ) {
							$is_profile_photo_uploaded = 1;
						}
					}

					if ( (int) $get_user_data['photo_type']['profile_photo']['is_uploaded'] !== (int) $is_profile_photo_uploaded ) {
						$get_user_data['photo_type']['profile_photo']['is_uploaded'] = $is_profile_photo_uploaded;
						if ( 1 === (int) $is_profile_photo_uploaded ) {
							$total_completed_count = ++ $total_completed_count;
						} else {
							$total_completed_count = -- $total_completed_count;
						}
					}
				}

				$get_user_data['completed_fields'] = $total_completed_count;

				// Update new response for completion widget.
				bp_update_user_meta( $user->ID, 'bp_profile_completion_widgets', $get_user_data );
			}

			$offset++;
		}

		$records_updated = sprintf( __( 'Profile completion widget, profile photo status updated successfully for %s members.', 'buddyboss' ), bp_core_number_format( $offset ) );
		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => $records_updated,
		);
	} else {
		$statement = __( 'Profile Completion widget, profile photo status re-sync %s', 'buddyboss' );
		return array(
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss' ) ),
		);
	}
}

/**
 * Function will recalculate the group total members count
 * and remove the orphaned group members records.
 *
 * @since BuddyBoss 2.3.90
 *
 * @return array
 */
function bp_admin_repair_group_member_count() {
	global $wpdb;

	if ( ! bp_is_active( 'groups' ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
	$offset = isset( $_POST['offset'] ) ? (int) ( $_POST['offset'] ) : 0;
	$bp     = buddypress();

	/**
	 * Check and delete orphan group members records from wp_bp_groups_members table
	 * if user doesn't exist in users table.
	 */
	if ( 0 === $offset ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->query( "DELETE m, mm FROM {$wpdb->prefix}bp_groups_members AS m LEFT JOIN {$wpdb->users} AS u ON u.ID = m.user_id LEFT JOIN {$wpdb->prefix}bp_groups_membermeta AS mm ON m.ID = mm.member_id WHERE u.ID IS NULL" );
	}

	// Fetch all groups.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT id FROM {$wpdb->prefix}bp_groups ORDER BY id DESC LIMIT 20 OFFSET %d", $offset ) );

	if ( ! empty( $group_ids ) ) {
		foreach ( $group_ids as $group_id ) {
			// Remove cached group member count.
			$cache_key = 'bp_group_get_total_member_count_' . $group_id;
			wp_cache_delete( $cache_key, 'bp_groups' );

			$select_sql = "SELECT COUNT(u.ID) FROM {$bp->groups->table_name_members} m";
			$join_sql   = "LEFT JOIN {$wpdb->users} u ON u.ID = m.user_id";

			// Where conditions.
			$where_conditions          = array();
			$where_conditions['where'] = $wpdb->prepare( 'm.group_id = %d AND m.is_confirmed = 1 AND m.is_banned = 0', $group_id );

			/**
			 * Filters the MySQL WHERE conditions for the group members count.
			 *
			 * @since BuddyBoss 2.3.90
			 *
			 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
			 * @param string $ud_name          moderation type
			 */
			$where_conditions = apply_filters( 'bb_group_member_count_where_sql', $where_conditions, 'user_id' );

			// Join the where conditions together.
			$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

			/**
			 * Filters the MySQL JOIN conditions for the group members count.
			 *
			 * @since BuddyBoss 2.3.90
			 *
			 * @param array  $join_sql Current conditions for MySQL JOIN statement.
			 * @param string $ud_name  moderation type
			 */
			$join_sql = apply_filters( 'bb_group_member_count_join_sql', $join_sql, 'user_id' );

			$sql = "{$select_sql} {$join_sql} {$where_sql}";

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
			$member_count = $wpdb->get_var( $sql );

			groups_update_groupmeta( $group_id, 'total_member_count', absint( $member_count ) );
			wp_cache_set( $cache_key, absint( $member_count ), 'bp_groups' );

			$offset++;
		}

		return array(
			'status'  => 'running',
			'offset'  => $offset,
			'records' => sprintf(
				/* translators: %s: number of groups */
				esc_html__( '%s groups member count updated successfully.', 'buddyboss' ),
				bp_core_number_format( $offset )
			),
		);
	}

	$statement = esc_html__( 'Recalculating the total group members count for each group &hellip; %s', 'buddyboss' );

	return array(
		'status'  => 1,
		'message' => sprintf( $statement, esc_html__( 'Complete!', 'buddyboss' ) ),
	);
}
