<?php

/**
 * Forums Admin Tools Page
 *
 * @package BuddyBoss\Administration
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Repair ********************************************************************/

/** Converter Helpers *********************************************************/

/**
 * Output settings API option
 *
 * @since bbPress (r3203)
 *
 * @uses bbp_get_form_option()
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 */
function bbp_form_option( $option, $default = '', $slug = false ) {
	echo bbp_get_form_option( $option, $default, $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- bbp_get_form_option() escapes its return value with esc_attr().
}

/**
 * Return settings API option
 *
 * @since bbPress (r3203)
 *
 * @uses get_option()
 * @uses esc_attr()
 * @uses apply_filters()
 *
 * @param string $option
 * @param string $default
 * @param bool   $slug
 *
 * @return string
 */
function bbp_get_form_option( $option, $default = '', $slug = false ) {

	// Get the option and sanitize it.
	$value = get_option( $option, $default );

	// Slug?
	if ( true === $slug ) {
		$value = esc_attr( apply_filters( 'editable_slug', $value ) );

		// Not a slug.
	} else {
		$value = esc_attr( $value );
	}

	// Fallback to default.
	if ( empty( $value ) ) {
		$value = $default;
	}

	// Allow plugins to further filter the output.
	return apply_filters( 'bbp_get_form_option', $value, $option );
}

/** Converter Fields **********************************************************/

/**
 * Main settings section description for the settings page
 *
 * @since bbPress (r3813)
 *
 * @param array $args Array of section data.
 */
function bbp_converter_setting_callback_main_section( $args ) {
	?>
	<h2>
		<?php
		if ( isset( $args['icon'] ) && ! empty( $args['icon'] ) ) {
			?>
			<i class="<?php echo esc_attr( $args['icon'] ); ?>"></i>
			<?php
		}
		esc_html_e( 'Import Forums', 'buddyboss-platform' );
		?>
	</h2>
	<h3><?php esc_html_e( 'Database Settings', 'buddyboss-platform' ); ?></h3>
	<p><?php echo wp_kses_post( __( 'Information about your previous forums database so that they can be converted. <strong>Backup your database before proceeding.</strong>', 'buddyboss-platform' ) ); ?></p>

	<?php
}

/**
 * Edit Platform setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_platform() {

	$current          = bbp_get_form_option( '_bbp_converter_platform' );
	$platform_options = '';

	if ( ! file_exists( bbpress()->admin->admin_dir . 'converters/' ) ) {
		return;
	}

	$curdir = opendir( bbpress()->admin->admin_dir . 'converters/' );

	// Bail if no directory was found (how did this happen?)
	if ( empty( $curdir ) ) {
		return;
	}

	// Loop through files in the converters folder and assemble some options.
	while ( $file = readdir( $curdir ) ) {
		if ( ( stristr( $file, '.php' ) ) && ( stristr( $file, 'index' ) === false ) ) {
			$file              = preg_replace( '/.php/', '', $file );
			$platform_options .= '<option value="' . $file . '"' . selected( $file, $current, false ) . '>' . esc_html( $file ) . '</option>';
		}
	}

	closedir( $curdir );
	?>

	<select name="_bbp_converter_platform" id="_bbp_converter_platform"><?php echo $platform_options; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns form-control markup (<select>/<option>/<input>) pre-escaped per value at construction; wp_kses_post strips the controls. ?></select>
	<label for="_bbp_converter_platform"><?php esc_html_e( 'is the previous forum software', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Database Server setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbserver() {
	?>

	<input name="_bbp_converter_db_server" id="_bbp_converter_db_server" type="text" value="<?php bbp_form_option( '_bbp_converter_db_server', 'localhost' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_server"><?php esc_html_e( 'IP or hostname', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Database Server Port setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbport() {
	?>

	<input name="_bbp_converter_db_port" id="_bbp_converter_db_port" type="text" value="<?php bbp_form_option( '_bbp_converter_db_port', '3306' ); ?>" class="small-text" />
	<label for="_bbp_converter_db_port"><?php esc_html_e( 'Use default 3306 if unsure', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Database User setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbuser() {
	?>

	<input name="_bbp_converter_db_user" id="_bbp_converter_db_user" type="text" value="<?php bbp_form_option( '_bbp_converter_db_user' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_user"><?php esc_html_e( 'User for your database connection', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Database Pass setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbpass() {
	?>

	<div class="_bbp_converter_db_pass_wrap">
		<input name="_bbp_converter_db_pass" id="_bbp_converter_db_pass" type="password" value="<?php bbp_form_option( '_bbp_converter_db_pass' ); ?>" class="medium-text" />
		<i class="bb-icon-l bb-icon-eye bbp-db-pass-toggle"></i>
	</div>
	<label for="_bbp_converter_db_pass"><?php esc_html_e( 'Password to access the database', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Database Name setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbname() {
	?>

	<input name="_bbp_converter_db_name" id="_bbp_converter_db_name" type="text" value="<?php bbp_form_option( '_bbp_converter_db_name' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_name"><?php esc_html_e( 'Name of the database with your old forum data', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Options settings section description for the settings page
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_options_section() {
	?>
	<h3><?php esc_html_e( 'Options', 'buddyboss-platform' ); ?></h3>
	<p><?php esc_html_e( 'Some optional parameters to help tune the conversion process.', 'buddyboss-platform' ); ?></p>

	<?php
}

/**
 * Edit Table Prefix setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_dbprefix() {
	?>

	<input name="_bbp_converter_db_prefix" id="_bbp_converter_db_prefix" type="text" value="<?php bbp_form_option( '_bbp_converter_db_prefix' ); ?>" class="medium-text" />
	<label for="_bbp_converter_db_prefix"><?php esc_html_e( '(If converting from BuddyBoss Forums, use "wp_bb_" or your custom prefix)', 'buddyboss-platform' ); ?></label>

	<?php
}

/**
 * Edit Rows Limit setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_rows() {
	?>

	<input name="_bbp_converter_rows" id="_bbp_converter_rows" type="text" value="<?php bbp_form_option( '_bbp_converter_rows', '100' ); ?>" class="small-text" />
	<label for="_bbp_converter_rows"><?php esc_html_e( 'rows to process at a time', 'buddyboss-platform' ); ?></label>
	<p class="description"><?php esc_html_e( 'Keep this low if you experience out-of-memory issues.', 'buddyboss-platform' ); ?></p>

	<?php
}

/**
 * Edit Delay Time setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_delay_time() {
	?>

	<input name="_bbp_converter_delay_time" id="_bbp_converter_delay_time" type="text" value="<?php bbp_form_option( '_bbp_converter_delay_time', '1' ); ?>" class="small-text" />
	<label for="_bbp_converter_delay_time"><?php esc_html_e( 'second(s) delay between each group of rows', 'buddyboss-platform' ); ?></label>
	<p class="description"><?php esc_html_e( 'Keep this high to prevent too-many-connection issues.', 'buddyboss-platform' ); ?></p>

	<?php
}

/**
 * Edit Restart setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_restart() {
	?>

	<input name="_bbp_converter_restart" id="_bbp_converter_restart" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_restart', false ) ); ?> />
	<label for="_bbp_converter_restart"><?php esc_html_e( 'Start a fresh conversion from the beginning', 'buddyboss-platform' ); ?></label>
	<p class="description"><?php esc_html_e( 'You should clean old conversion information before starting over.', 'buddyboss-platform' ); ?></p>

	<?php
}

/**
 * Edit Clean setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_clean() {
	?>

	<input name="_bbp_converter_clean" id="_bbp_converter_clean" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_clean', false ) ); ?> />
	<label for="_bbp_converter_clean"><?php esc_html_e( 'Purge all information from a previously attempted import', 'buddyboss-platform' ); ?></label>
	<p class="description"><?php esc_html_e( 'Use this if an import failed and you want to remove that incomplete data.', 'buddyboss-platform' ); ?></p>

	<?php
}

/**
 * Edit Convert Users setting field
 *
 * @since bbPress (r3813)
 */
function bbp_converter_setting_callback_convert_users() {
	?>

	<input name="_bbp_converter_convert_users" id="_bbp_converter_convert_users" type="checkbox" value="1" <?php checked( get_option( '_bbp_converter_convert_users', false ) ); ?> />
	<label for="_bbp_converter_convert_users"><?php esc_html_e( 'Attempt to import user accounts from previous forums', 'buddyboss-platform' ); ?></label>
	<p class="description"><?php esc_html_e( 'Non-Forums passwords cannot be automatically converted. They will be converted as each user logs in.', 'buddyboss-platform' ); ?></p>

	<?php
}

/** Repair Handler ************************************************************/

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
		0   => array( 'bbp-sync-topic-meta', __( 'Recalculate the parent discussion for each post', 'buddyboss-platform' ), 'bbp_admin_repair_topic_meta' ),
		5   => array( 'bbp-sync-forum-meta', __( 'Recalculate the parent forum for each post', 'buddyboss-platform' ), 'bbp_admin_repair_forum_meta' ),
		10  => array( 'bbp-sync-forum-visibility', __( 'Recalculate private and hidden forums', 'buddyboss-platform' ), 'bbp_admin_repair_forum_visibility' ),
		15  => array( 'bbp-sync-all-topics-forums', __( 'Recalculate last activity in each discussion and forum', 'buddyboss-platform' ), 'bbp_admin_repair_freshness' ),
		20  => array( 'bbp-sync-all-topics-sticky', __( 'Recalculate the sticky relationship of each discussion', 'buddyboss-platform' ), 'bbp_admin_repair_sticky' ),
		25  => array( 'bbp-sync-all-reply-positions', __( 'Recalculate the position of each reply', 'buddyboss-platform' ), 'bbp_admin_repair_reply_menu_order' ),
		30  => array( 'bbp-group-forums', __( 'Repair social group forum relationships', 'buddyboss-platform' ), 'bbp_admin_repair_group_forum_relationship' ),
		35  => array( 'bbp-forum-topics', __( 'Count discussions in each forum', 'buddyboss-platform' ), 'bbp_admin_repair_forum_topic_count' ),
		40  => array( 'bbp-forum-replies', __( 'Count replies in each forum', 'buddyboss-platform' ), 'bbp_admin_repair_forum_reply_count' ),
		45  => array( 'bbp-topic-replies', __( 'Count replies in each discussion', 'buddyboss-platform' ), 'bbp_admin_repair_topic_reply_count' ),
		50  => array( 'bbp-topic-members', __( 'Count members in each discussion', 'buddyboss-platform' ), 'bbp_admin_repair_topic_voice_count' ),
		55  => array( 'bbp-topic-hidden-replies', __( 'Count spammed & trashed replies in each discussion', 'buddyboss-platform' ), 'bbp_admin_repair_topic_hidden_reply_count' ),
		60  => array( 'bbp-user-topics', __( 'Count discussions for each user', 'buddyboss-platform' ), 'bbp_admin_repair_user_topic_count' ),
		65  => array( 'bbp-user-replies', __( 'Count replies for each user', 'buddyboss-platform' ), 'bbp_admin_repair_user_reply_count' ),
		70  => array( 'bbp-user-favorites', __( 'Remove trashed discussions from user favorites', 'buddyboss-platform' ), 'bbp_admin_repair_user_favorites' ),
		75  => array( 'bbp-user-topic-subscriptions', __( 'Remove trashed discussions from user subscriptions', 'buddyboss-platform' ), 'bbp_admin_repair_user_topic_subscriptions' ),
		80  => array( 'bbp-user-forum-subscriptions', __( 'Remove trashed forums from user subscriptions', 'buddyboss-platform' ), 'bbp_admin_repair_user_forum_subscriptions' ),
		85  => array( 'bbp-user-role-map', __( 'Remap existing users to default forum roles', 'buddyboss-platform' ), 'bbp_admin_repair_user_roles' ),
		90  => array( 'bbp-wp-role-restore', __( 'Remove and restore Wordpress default role capabilities', 'buddyboss-platform' ), 'bbp_restore_caps_from_wp_roles' ),
		95  => array( 'bbp-migrate-buddyboss-forum-topic-subscription', __( 'Migrate BBPress (up to v2.5.14) forum and discussion subscriptions to BuddyBoss', 'buddyboss-platform' ), 'bbp_migrate_forum_topic_subscription' ),
		100 => array( 'bbp-migrate-bbpress-forum-topic-subscription', __( 'Migrate BBPress (v2.6+) forum and discussion subscriptions to BuddyBoss', 'buddyboss-platform' ), 'bbp_migrate_forum_topic_subscription' ),
		105 => array( 'bb-migrate-bbpress-user-topic-favorites', __( 'Migrate members discussions \'marked as favorites\' data to improve performance', 'buddyboss-platform' ), 'bb_migrate_user_topic_favorites' ),
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
	/* translators: %s: result message. */
	$statement = __( 'Counting the number of replies in each discussion &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Counting the number of members in each discussion &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Counting the number of spammed and trashed replies in each discussion &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Repairing social group forum relationships &hellip; %s', 'buddyboss-platform' );
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
	if ( is_wp_error( $forum_ids ) ) {
		return array(
			2,
			sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
		);
	}

	// Nothing to repair when there are no converted forums on the site.
	if ( empty( $bbp_db->last_result ) ) {
		$nothing = __( 'Nothing to repair!', 'buddyboss-platform' );
		return array(
			0,
			sprintf( $statement, $nothing ),
			'status'  => 1,
			'message' => sprintf( $statement, $nothing ),
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
					'post_title' => __( 'Group Forums', 'buddyboss-platform' ),
					'post_name'  => __( 'group-forums', 'buddyboss-platform' ),
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
	/* translators: 1: number of groups updated, 2: number of forums updated, 3: number of forum statuses synced. */
	$result = sprintf( __( 'Complete! %1$s groups updated; %2$s forums updated; %3$s forum statuses synced.', 'buddyboss-platform' ), bbp_number_format( $g_count ), bbp_number_format( $f_count ), bbp_number_format( $s_count ) );
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
	/* translators: %s: result message. */
	$statement = __( 'Counting the number of discussions in each forum &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		$result = __( 'No forums to count!', 'buddyboss-platform' );
		return array(
			0,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Counting the number of replies in each forum &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		$result = __( 'No forums to count!', 'buddyboss-platform' );
		return array(
			0,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	return array(
		0,
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement   = __( 'Counting the number of discussions for each user &hellip; %s', 'buddyboss-platform' );
	$result      = __( 'Failed!', 'buddyboss-platform' );
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
		$result = __( 'No discussions to count!', 'buddyboss-platform' );
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
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement   = __( 'Counting the number of replies for each user &hellip; %s', 'buddyboss-platform' );
	$result      = __( 'Failed!', 'buddyboss-platform' );
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
		$result = __( 'No replies to count!', 'buddyboss-platform' );
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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Removing trashed discussions from user favorites &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );
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
		$result = __( 'Nothing to remove!', 'buddyboss-platform' );
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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Removing trashed discussions from user subscriptions &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );
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
		$result = __( 'Nothing to remove!', 'buddyboss-platform' );
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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Removing trashed forums from user subscriptions &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );
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
		$result = __( 'Nothing to remove!', 'buddyboss-platform' );
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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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

	/* translators: %s: result message. */
	$statement    = __( 'Remapping existing users to default forum roles &hellip; %s', 'buddyboss-platform' );
	$changed      = 0;
	$role_map     = bbp_get_user_role_map();
	$default_role = bbp_get_default_role();

	// Bail if no role map exists
	if ( empty( $role_map ) ) {
		return array(
			1,
			sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
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

	/* translators: %s: number of users updated. */
	$result = sprintf( __( 'Complete! %s users updated.', 'buddyboss-platform' ), bbp_number_format( $changed ) );
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating last activity in each discussion and forum &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating the sticky relationship of each discussion &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );
	$forums    = $bbp_db->get_col( "SELECT ID FROM `{$bbp_db->posts}` WHERE `post_type` = 'forum';" );

	// Bail if the query errored.
	if ( is_wp_error( $forums ) ) {
		return array(
			1,
			sprintf( $statement, $result ),
			'status'  => 1,
			'message' => sprintf( $statement, $result ),
		);
	}

	// Nothing to recalculate when no forums exist on the site.
	if ( empty( $forums ) ) {
		$result = __( 'No stickies to recalculate!', 'buddyboss-platform' );
		return array(
			0,
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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating private and hidden forums &hellip; %s', 'buddyboss-platform' );

	// Bail if queries returned errors
	if ( ! bbp_repair_forum_visibility() ) {
		return array(
			2,
			sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
			'status'  => 0,
			'message' => sprintf( $statement, __( 'Failed!', 'buddyboss-platform' ) ),
		);

		// Complete results
	} else {
		return array(
			0,
			sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
			'status'  => 1,
			'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating the parent forum for each post &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating the parent discussion for each post &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'Failed!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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
	/* translators: %s: result message. */
	$statement = __( 'Recalculating the position of each reply &hellip; %s', 'buddyboss-platform' );
	$result    = __( 'No reply positions to recalculate!', 'buddyboss-platform' );

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
		sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
		'status'  => 1,
		'message' => sprintf( $statement, __( 'Complete!', 'buddyboss-platform' ) ),
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

		<p><?php esc_html_e( 'Revert your forums back to a brand new installation. This process cannot be undone.', 'buddyboss-platform' ); ?></p>
		<p><strong><?php esc_html_e( 'Backup your database before proceeding.', 'buddyboss-platform' ); ?></strong></p>

		<form class="settings" method="post" action="">
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'The following data will be removed:', 'buddyboss-platform' ); ?></th>
						<td>
							<?php esc_html_e( 'Forums', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Discussions', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Replies', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Discussion Tags', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Related Meta Data', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Forum Settings', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Forum Activity', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Forum User Roles', 'buddyboss-platform' ); ?><br />
							<?php esc_html_e( 'Importer Helper Data', 'buddyboss-platform' ); ?><br />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Delete imported users?', 'buddyboss-platform' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( "Say it ain't so!", 'buddyboss-platform' ); ?></span></legend>
								<label><input type="checkbox" class="checkbox" name="bbpress-delete-imported-users" id="bbpress-delete-imported-users" value="1" /> <?php esc_html_e( 'This option will delete all previously imported users, and cannot be undone.', 'buddyboss-platform' ); ?></label>
								<p class="description"><?php esc_html_e( 'Note: Resetting without this checked will delete the meta-data necessary to delete these users.', 'buddyboss-platform' ); ?></p>
							</fieldset>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Are you sure you want to do this?', 'buddyboss-platform' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><span><?php esc_html_e( "Say it ain't so!", 'buddyboss-platform' ); ?></span></legend>
								<label><input type="checkbox" class="checkbox" name="bbpress-are-you-sure" id="bbpress-are-you-sure" value="1" /> <?php esc_html_e( 'This process cannot be undone.', 'buddyboss-platform' ); ?></label>
								<p class="description"><?php esc_html_e( 'Human sacrifice, dogs and cats living together&hellip;mass hysteria!', 'buddyboss-platform' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</tbody>
			</table>

			<fieldset class="submit">
				<input class="button-primary" type="submit" name="submit" value="<?php esc_attr_e( 'Reset Forums', 'buddyboss-platform' ); ?>" />
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
	$failed   = __( 'Failed', 'buddyboss-platform' );
	$success  = __( 'Success!', 'buddyboss-platform' );

	// Flush the cache; things are about to get ugly.
	wp_cache_flush();

	/** Posts */
	$bbp_db     = bbp_db();
	/* translators: %s: result message. */
	$statement  = __( 'Deleting Posts&hellip; %s', 'buddyboss-platform' );
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
		/* translators: %s: result message. */
		$statement  = __( 'Deleting Post Meta&hellip; %s', 'buddyboss-platform' );
		$sql_meta   = implode( "', '", $sql_meta );
		$sql_delete = "DELETE FROM `{$bbp_db->postmeta}` WHERE `post_id` IN ('{$sql_meta}');";
		$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );
	}

	/** Topic Tags */

	/* translators: %s: result message. */
	$statement  = __( 'Deleting Discussions Tags&hellip; %s', 'buddyboss-platform' );
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
			/* translators: %s: result message. */
			$statement  = __( 'Deleting User&hellip; %s', 'buddyboss-platform' );
			$sql_meta   = implode( "', '", $sql_meta );
			$sql_delete = "DELETE FROM `{$bbp_db->users}` WHERE `ID` IN ('{$sql_meta}');";
			$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
			$messages[] = sprintf( $statement, $result );
			/* translators: %s: result message. */
			$statement  = __( 'Deleting User Meta&hellip; %s', 'buddyboss-platform' );
			$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `user_id` IN ('{$sql_meta}');";
			$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
			$messages[] = sprintf( $statement, $result );
		}

		// Delete imported user metadata
	} else {
		/* translators: %s: result message. */
		$statement  = __( 'Deleting User Meta&hellip; %s', 'buddyboss-platform' );
		$sql_delete = "DELETE FROM `{$bbp_db->usermeta}` WHERE `meta_key` LIKE '%%_bbp_%%';";
		$result     = is_wp_error( $bbp_db->query( $sql_delete ) ) ? $failed : $success;
		$messages[] = sprintf( $statement, $result );
	}

	/** Converter */

	/* translators: %s: result message. */
	$statement  = __( 'Deleting Conversion Table&hellip; %s', 'buddyboss-platform' );
	$table_name = $bbp_db->prefix . 'bbp_converter_translator';
	if ( $bbp_db->get_var( "SHOW TABLES LIKE '{$table_name}'" ) === $table_name ) {
		$bbp_db->query( "DROP TABLE {$table_name}" );
		$result = $success;
	} else {
		$result = $failed;
	}
	$messages[] = sprintf( $statement, $result );

	/** Options */

	/* translators: %s: result message. */
	$statement = __( 'Deleting Settings&hellip; %s', 'buddyboss-platform' );
	bbp_delete_options();
	$messages[] = sprintf( $statement, $success );

	/** Roles */

	/* translators: %s: result message. */
	$statement = __( 'Deleting Roles and Capabilities&hellip; %s', 'buddyboss-platform' );
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
	$site_id = isset( $_POST['site_id'] ) ? absint( wp_unslash( $_POST['site_id'] ) ) : 0;

	$response = array(
		'feedback' => sprintf(
			'<div class="bp-feedback error bp-ajax-message"><span class="bp-icon" aria-hidden="true"></span><p>%s</p></div>',
			esc_html__( 'There was a problem performing this action. Please try again.', 'buddyboss-platform' )
		),
	);

	// Capability check — repair tools require admin privileges.
	if ( ! bp_current_user_can( 'bp_moderate' ) ) {
		wp_send_json_error( $response );
	}

	// Bail if not a POST action.
	if ( ! bp_is_post_request() ) {
		wp_send_json_error( $response );
	}

	// Capability gate: these repair tools run destructive maintenance (role
	// remapping, capability restore, subscription/favorite deletion, multisite
	// switch_to_blog). Mirror the admin-page gate so a non-privileged user can't
	// invoke them via admin-ajax with a guessable static nonce.
	if ( ! current_user_can( 'bbp_tools_repair_page' ) ) {
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

	// Additive enrichment for the Settings 2.0 Repair Platform React UI —
	// see bb_admin_repair_extract_count_summary() in bp-core-admin-tools.php.
	// Pass the whole $status array so the helper can scan `message`, `records`,
	// and `feedback` for a count. LOCKED-BC preserved (additive).
	//
	// @since BuddyBoss 3.1.0
	if ( is_array( $status ) && function_exists( 'bb_admin_repair_extract_count_summary' ) ) {
		$enrichment = bb_admin_repair_extract_count_summary( $status );
		$status     = array_merge( $status, $enrichment );
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
			++$offset;
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
					__( 'Copies %s favorites from user meta to topic meta.', 'buddyboss-platform' ),
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
				'message' => __( 'Copies favorites from user meta to topic meta&hellip; Complete!', 'buddyboss-platform' ),
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
