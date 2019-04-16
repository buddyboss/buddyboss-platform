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

			<h2><?php esc_html_e( 'Repair Community', 'buddyboss' ) ?></h2>

			<p><?php esc_html_e( 'BuddyBoss keeps track of various relationships between members, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration. Use the tools below to manually recalculate these relationships.', 'buddyboss' ); ?></p>
			</p>

			<form class="settings" method="post" action="">
				<fieldset>
					<legend><?php esc_html_e( 'Data to Repair:', 'buddyboss' ) ?></legend>

					<div class="checkbox">
					<?php foreach ( bp_admin_repair_list() as $item ) : ?>
						<label for="<?php echo esc_attr( str_replace( '_', '-', $item[0] ) ); ?>">
							<input
								type="checkbox"
								class="checkbox"
								name="<?php echo esc_attr( $item[0] ) . '" id="' . esc_attr( str_replace( '_', '-', $item[0] ) ); ?>"
								value="1"
								<?php if ( isset( $_GET['tool'] ) && $_GET['tool'] == esc_attr( str_replace( '_', '-', $item[0] ) )) {echo 'checked';} ?>
							/> <?php echo esc_html( $item[1] ); ?></label>
					<?php endforeach; ?>
					</div>

					<p class="submit">
						<input class="button-primary" type="submit" name="bp-tools-submit" value="<?php esc_attr_e( 'Repair Items', 'buddyboss' ); ?>" />
						<?php wp_nonce_field( 'bp-do-counts' ); ?>
					</p>

				</fieldset>
			</form>

			<p class="description"><?php esc_html_e( 'Some of these tools utilize substantial database resources. Avoid running more than one repair job at a time.', 'buddyboss' ); ?></p>

		</div>
	</div>

	<?php
}

/**
 * Render help list
 *
 * @param $dir
 * @param $sections
 * @param bool $parent_dir
 */
function bp_list_help_files( $dir ) {
	$ffs  = scandir( $dir );
	$path = buddypress()->plugin_dir . 'bp-help/docs';


	unset( $ffs[ array_search( '.', $ffs, true ) ] );
	unset( $ffs[ array_search( '..', $ffs, true ) ] );

	// prevent empty ordered elements
	if ( count( $ffs ) < 1 ) {
		return;
	}

	?>
	<ul>
		<?php foreach ( $ffs as $key => $ff ) :

			$is_dir = is_dir( $dir . '/' . $ff );

			if ( ! $is_dir && $key === 2 ) {
				continue;
			}
			?>
			<li>
				<?php if ( $is_dir ):
					$parent_ffs = scandir( $dir . '/' . $ff );
					unset( $parent_ffs[ array_search( '.', $parent_ffs, true ) ] );
					unset( $parent_ffs[ array_search( '..', $parent_ffs, true ) ] );
					$sub_dir    = str_replace( $path . '/', '', $dir );
					$parent_dir = $sub_dir != $dir ? $sub_dir . '/' : '';
					$article_path = $parent_dir . $ff . '/' . current( $parent_ffs );



					echo '<pre>';
					var_dump( $parent_ffs );
					var_dump( $dir . '/' . $ff . '/' . current( $parent_ffs ) );
//					var_dump(  );
					echo '</pre>';
					?>
					<span class="test1">
						<a href="<?php echo add_query_arg( 'article', $article_path ); ?>">
							<?php echo fgets( fopen( $dir . '/' . $ff . '/' . current( $parent_ffs ), 'r' ) ); ?>
						</a>
					</span>
					<?php bp_list_help_files( $dir . '/' . $ff );
				else:
					$article_path = str_replace( $path . '/', '', $dir ) . '/' . $ff;
					?>
					<span class="test2">
						<a href="<?php echo add_query_arg( 'article', $article_path ); ?>">
							<?php echo fgets( fopen( $dir . '/' . $ff, 'r' ) ); ?>
						</a>
					</span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ul>
	<?php
}

/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_help_old() {
	$base_path = buddypress()->plugin_dir . 'bp-help';
	$path = $base_path . '/docs';
	require_once $base_path . '/vendors/parsedown/Parsedown.php';
			$Parsedown = new Parsedown();

	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php bp_core_admin_tabs( __( 'Help', 'buddyboss' ) ); ?>
		</h2>
	</div>
	<div class="wrap">
		<h1>
		    <?php _e( 'Documentation', 'buddyboss' ); ?>
		    <h1><?php _e( 'Documentation', 'buddyboss' ); ?> <a href="https://www.buddyboss.com/resources/docs/" class="page-title-action" target="_blank"><?php _e( 'Online Docs', 'buddyboss' ); ?></a></h1>
		    <a href="https://www.buddyboss.com/resources/docs/" class="page-title-action"><?php _e( 'Online Docs', 'buddyboss' ); ?></a>
		</h1>


		<?php
            $dirs = array_filter(glob('*'), 'is_dir');
            $directories = glob($path . '/*' , GLOB_ONLYDIR);

		if ( isset( $_GET['article'] ) ) {
			require_once $base_path . '/vendors/parsedown/Parsedown.php';
			$Parsedown = new Parsedown();
			$text      = file_get_contents( $path . '/' . $_GET['article'] );
			$dir       =  strstr( $_GET['article'], '/', true );
			$ffs       = scandir( $path . '/' . $dir );
			unset( $ffs[ array_search( '.', $ffs, true ) ] );
			unset( $ffs[ array_search( '..', $ffs, true ) ] );

			echo fgets( fopen( $path . '/'  . $dir . '/' . current( $ffs ), 'r' ) );
			 ?>
			<div class="help-content-wrap">
				<?php bp_list_help_files( $path . '/'  . $dir ) ?>
				<div class="bp-help-doc">
					<?php echo $Parsedown->text( $text ); ?>
				</div>
			</div>
			<?php
			} else {
			?>
			<!-- @mehul showing proper HTML output -->
			<div class="wp-list-table widefat bp-help-card-grid">

				<div class="bp-help-card">
					<h3>Getting Started</h3>
					<div class="inside">
						<ul>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Upgrading from BuddyPress</a></li>
							<li><a href="#">Configure BuddyBoss Platform</a></li>
							<li><a href="#">Installing BuddyBoss Platform Themes & Plugins</a></li>
							<li><a href="#">Changing Internal Configuration Settings</a></li>
							<li><a href="#">Translating BuddyBoss Platform</a></li>
							<li><a href="#">Privacy Policy, Terms of Service, GDPR</a></li>
							<li><a href="#">BuddyBoss Platform Features</a></li>
						</ul>
					</div>
				</div>

				<div class="bp-help-card">
					<h3>BuddyBoss Theme</h3>
					<div class="inside">
						<ul>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
						</ul>
					</div>
				</div>

				<div class="bp-help-card">
					<h3>Back-End Administration</h3>
					<div class="inside">
						<ul>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
						</ul>
					</div>
				</div>

				<div class="bp-help-card">
					<h3>Components</h3>
					<div class="inside">
						<ul>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
						</ul>
					</div>
				</div>

				<div class="bp-help-card">
					<h3>Getting Started</h3>
					<div class="inside">
						<ul>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
							<li><a href="#">Installation</a></li>
						</ul>
					</div>
				</div>

			</div>

			<div class="clear">
			<hr />

			<!-- @mehul old logic -->
			<div class="help-sections-wrap">
				<?php bp_list_help_files( $path );?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
}

/**
 * Display Main Menu of BuddyBoss Help
 */
function bp_core_admin_help_main_menu( $main_directories, $docs_path ) {
    foreach ( $main_directories  as $sub_directories ) {
        $index_file = glob($sub_directories . "/0-*.md");
        $directories = array_diff( glob($sub_directories . "/*"), $index_file );

        // converting array into string.
        $index_file = current( $index_file );
        ?>
        <div class="bp-help-card bb-help-content-wrap">
            <?php
            // print the title of the section
            printf( '<h3><a href="#">%s</a></h3>', fgets( fopen( $index_file, 'r' ) ) );
            ?>

            <div class="inside bb-help-menu">
                <?php
                bp_core_admin_help_sub_menu( $directories, '1', $docs_path );
                ?>
            </div>
        </div>
        <?php
    }
}

/**
 * Display Sub menu of Main Menu
 *
* @param $directories
* @param $times
* @param $docs_path
 */
function bp_core_admin_help_sub_menu( $directories, $times, $docs_path ) {
    ?>
    <ul class="loop-<?php echo $times; ?>">
        <?php

        // For showing the menu title
        foreach ( $directories as $directory ) {
            ?>
            <li>
                <?php
                // check if it's has directory
                if ( is_dir( $directory ) ) {
                    // the the main file from the directory
                    $dir_index_file = glob($directory . "/0-*.md");
                    $loop_dir = array_diff( glob($directory . '/*' ) , $dir_index_file );

                    $dir_index_file = current( $dir_index_file );
                    $url = add_query_arg( 'article', str_replace($docs_path,"",$dir_index_file) );

                    if ( ! empty( $loop_dir ) ) {
                        printf( '<a href="%s" class="dir">%s (%s)</a>', $url, fgets( fopen( $dir_index_file, 'r' ) ), count( $loop_dir ) );
                        $times++;
                        bp_core_admin_help_sub_menu( $loop_dir, $times, $docs_path );
                    } else {
                        printf( '<a href="%s" class="dir">%s</a>', $url, fgets( fopen( $dir_index_file, 'r' ) ) );
                    }
                } else {
                    $url = add_query_arg( 'article', str_replace($docs_path,"",$directory) );
                    // print the title if it's a .md file
                    printf( '<a href="%s" class="file">%s</a>', $url, fgets( fopen( $directory, 'r' ) ) );
                } ?>
            </li><?php
        }
        ?>
    </ul>
    <?php
}

/**
 * Display Help Page content
 *
* @param $docs_path
* @param $vendor_path
 */
function bp_core_admin_help_display_content( $docs_path, $vendor_path ) {
    require_once $vendor_path . '/parsedown/Parsedown.php';
    $Parsedown = new Parsedown();
    $text      = file_get_contents( $docs_path . $_GET['article'] );
    ?>
    <div class="bb-help-content">
        <?php
         echo $Parsedown->text( $text );
        ?>
    </div>
    <?php
}

/**
 * Show the main index page of HELP page
 */
function bp_core_admin_help_main_page() {
    $base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs';
	$vendor_path = $base_path . '/vendors';

	$main_directories = glob($docs_path . '/*' , GLOB_ONLYDIR);

	if ( ! empty( $main_directories ) ) {
        if ( empty( $_REQUEST['article'] ) ) {
            bp_core_admin_help_main_menu( $main_directories, $docs_path );
        } else {
            bp_core_admin_help_display_content( $docs_path, $vendor_path );
        }
	}
}

/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_help() {
	$base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs';
	$vendor_path = $base_path . '/vendors';

	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php bp_core_admin_tabs( __( 'Help', 'buddyboss' ) ); ?>
		</h2>
	</div>
	<div class="wrap">
		<h1>
		    <?php _e( 'Documentation', 'buddyboss' ); ?>
		    <a href="https://www.buddyboss.com/resources/docs/" class="page-title-action" target="_blank"><?php _e( 'Online Docs', 'buddyboss' ); ?></a>
		</h1>

		<!-- @mehul showing proper HTML output -->
        <div class="wp-list-table widefat bp-help-card-grid">
            <?php
            bp_core_admin_help_main_page();
            ?>
        </div>

        <div class="clear">
        <hr/>
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
		if ( isset( $item[2] ) && isset( $_POST[$item[0]] ) && 1 === absint( $_POST[$item[0]] ) && is_callable( $item[2] ) ) {
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
		__( 'Repair total members count.', 'buddyboss' ),
		'bp_admin_repair_count_members',
	);

	$repair_list[25] = array(
		'bp-last-activity',
		__( 'Repair member "last activity" data.', 'buddyboss' ),
		'bp_admin_repair_last_activity',
	);

	// Xprofile:
	// - default xprofile groups/fields
	$repair_list[35] = array(
		'bp-xprofile-fields',
		__( 'Repair default profile set and fields.', 'buddyboss' ),
		'repair_default_profiles_fields',
	);

	$repair_list[36] = array(
		'bp-xprofile-wordpress-resync',
		__( 'Re-sync BuddyBoss profile fields to WordPress profile fields.', 'buddyboss' ),
		'resync_xprofile_wordpress_fields',
	);

	$repair_list[37] = array(
		'bp-wordpress-xprofile-resync',
		__( 'Re-sync WordPress profile fields to BuddyBoss profile fields.', 'buddyboss' ),
		'resync_wordpress_xprofile_fields',
	);

	$repair_list[38] = array(
		'bp-wordpress-update-display-name',
		__( 'Update display name to selected format in profile settings.', 'buddyboss' ),
		'xprofile_update_display_names',
	);

	// Connections:
	// - user friend count.
	if ( bp_is_active( 'friends' ) ) {
		$repair_list[0] = array(
			'bp-user-friends',
			__( 'Repair total connections count for each member.', 'buddyboss' ),
			'bp_admin_repair_friend_count',
		);
	}

	// Groups:
	// - user group count.
	if ( bp_is_active( 'groups' ) ) {
		$repair_list[10] = array(
			'bp-group-count',
			__( 'Repair total groups count for each member.', 'buddyboss' ),
			'bp_admin_repair_group_count',
		);
	}

	// Blogs:
	// - user blog count.
	if ( bp_is_active( 'blogs' ) ) {
		$repair_list[90] = array(
			'bp-blog-records',
			__( 'Repopulate site tracking records.', 'buddyboss' ),
			'bp_admin_repair_blog_records',
		);
	}

	// Emails:
	// - reinstall emails.
	$repair_list[100] = array(
		'bp-reinstall-emails',
		__( 'Reinstall emails (delete and restore from defaults).', 'buddyboss' ),
		'bp_admin_reinstall_emails',
	);

	// Check whether member type is enabled.
	if ( true === bp_member_type_enable_disable() ) {
		$member_types = bp_get_active_member_types();
		$existing_selected = bp_member_type_default_on_registration();
		if ( isset( $member_types ) && !empty( $member_types ) && '' !== $existing_selected ) {
			// - Assign default member type.
			$repair_list[101] = array(
				'bp-assign-member-type',
				__( 'Assign members without a profile type to the default profile type (excludes admins).', 'buddyboss' ),
				'bp_admin_assign_member_type',
			);
		}
	}

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

	$statement = __( 'Counting the number of connections for each user&hellip; %s', 'buddyboss' );
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
		$offset = 0;
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

	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
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

	$statement = __( 'Counting the number of groups for each user&hellip; %s', 'buddyboss' );
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
		$offset = 0;
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

	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
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
	$result    = __( 'Failed!',   'buddyboss' );

	// Default to unrepaired.
	$repair    = false;

	// Run function if blogs component is active.
	if ( bp_is_active( 'blogs' ) ) {
		$repair = bp_blogs_record_existing_blogs();
	}

	// Setup success/fail messaging.
	if ( true === $repair ) {
		$result = __( 'Complete!', 'buddyboss' );
	}

	// All done!
	return array( 0, sprintf( $statement, $result ) );
}

/**
 * Recalculate the total number of active site members.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_repair_count_members() {
	$statement = __( 'Counting the number of active members on the site&hellip; %s', 'buddyboss' );
	delete_transient( 'bp_active_member_count' );
	bp_core_get_active_member_count();
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Repair user last_activity data.
 *
 * Re-runs the migration from usermeta introduced in BP 2.0.
 *
 * @since BuddyPress 2.0.0
 */
function bp_admin_repair_last_activity() {
	$statement = __( 'Determining last activity dates for each user&hellip; %s', 'buddyboss' );
	bp_last_activity_migrate();
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Repair default profile fields.
 *
 * @since BuddyBoss 1.0.0
 */
function repair_default_profiles_fields() {
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );

	bp_core_install_default_profiles_fields();

	if ( ! bp_get_option( 'bp-xprofile-base-group-id' ) ) {
		bp_update_option( 'bp-xprofile-base-group-id', 1 );
	}

	if ( ! bp_get_option( 'bp-xprofile-firstname-field-id' ) ) {
		bp_update_option( 'bp-xprofile-firstname-field-id', 1 );
	}

	$statement = __( 'Repair default profile set and fields&hellip; %s', 'buddyboss' );
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Resync BuddyBoss profile data to WordPress.
 *
 * @since BuddyBoss 1.0.0
 */
function resync_xprofile_wordpress_fields() {
	$users = get_users( [
		'fields' => [ 'ID' ]
	]);

	array_map( 'xprofile_sync_wp_profile', wp_list_pluck( $users, 'ID' ) );

	$statement = __( 'Re-sync user profile data to WordPress; %s', 'buddyboss' );
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Resync WordPress profile data to BuddyBoss.
 *
 * @since BuddyBoss 1.0.0
 */
function resync_wordpress_xprofile_fields() {
	$users = get_users( [
		'fields' => [ 'ID', 'user_nicename' ]
	]);

	foreach ( $users as $user ) {
		xprofile_set_field_data( bp_xprofile_firstname_field_id(), $user->ID, get_user_meta( $user->ID, 'first_name', true ) );
		xprofile_set_field_data( bp_xprofile_lastname_field_id(),  $user->ID, get_user_meta( $user->ID, 'last_name', true ) );

		// make sure nickname is valid
		$nickname = get_user_meta( $user->ID, 'nickname', true );
		$nickname = sanitize_title( $nickname );
		$invalid = bp_xprofile_validate_nickname_value( '', bp_xprofile_nickname_field_id(), $nickname, $user->ID );

		// or use the user_nicename
		if ( ! $nickname || $invalid ) {
			$nickname = $user->user_nicename;
		}

		bp_update_user_meta( $user->ID, 'nickname', $nickname );
		xprofile_set_field_data( bp_xprofile_nickname_field_id(),  $user->ID, $nickname );
	}

	$statement = __( 'Re-sync user WordPress data to BuddyBoss profile fields; %s', 'buddyboss' );
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Update member display names.
 *
 * @since BuddyBoss 1.0.0
 */
function xprofile_update_display_names() {
	$users = get_users( [
		'fields' => [ 'ID', 'display_name' ]
	]);

	foreach ( $users as $user ) {
		$display_name = bp_custom_display_name_format( $user->display_name, $user->ID );

		wp_update_user( $args = [
			'ID' => $user->ID,
			'display_name' => $display_name
		] );
	}

	$statement = __( 'Update WordPress user display names; %s', 'buddyboss' );
	return array( 0, sprintf( $statement, __( 'Complete!', 'buddyboss' ) ) );
}

/**
 * Assemble admin notices relating success/failure of repair processes.
 *
 * @since BuddyPress 2.0.0
 *
 * @param string      $message Feedback message.
 * @param string|bool $class   Unused.
 *
 * @return false|Closure
 */
function bp_admin_tools_feedback( $message, $class = false ) {
	if ( is_string( $message ) ) {
		$message = '<p>' . $message . '</p>';
		$class = $class ? $class : 'updated';
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
	$lambda  = function() use ( $message ) { echo $message; };

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
		<h1><?php esc_attr_e( 'Tools', 'buddyboss' ) ?></h1>

		<?php

		/**
		 * Fires inside the markup used to display the Available Tools page.
		 *
		 * @since BuddyPress 2.0.0
		 */
		do_action( 'bp_network_tool_box' ); ?>

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
		'page' => 'bp-tools'
	);

	$page = bp_core_do_network_admin() ? 'admin.php' : 'admin.php' ;
	$url  = add_query_arg( $query_arg, bp_get_admin_url( $page ) );
	?>
	<div class="card tool-box">
		<h2><?php esc_html_e( 'BuddyBoss Tools', 'buddyboss' ) ?></h2>
		<p>
			<?php esc_html_e( 'BuddyBoss keeps track of various relationships between users, groups, and activity items. Occasionally these relationships become out of sync, most often after an import, update, or migration.', 'buddyboss' ); ?>
			<?php printf( esc_html__( 'Use the %s to repair these relationships.', 'buddyboss' ), '<a href="' . esc_url( $url ) . '">' . esc_html__( 'BuddyBoss Tools', 'buddyboss' ) . '</a>' ); ?>
		</p>
	</div>
	<?php
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

	$emails = get_posts( array(
		'fields'           => 'ids',
		'post_status'      => 'publish',
		'post_type'        => bp_get_email_post_type(),
		'posts_per_page'   => 200,
		'suppress_filters' => false,
	) );

	if ( $emails ) {
		foreach ( $emails as $email_id ) {
			wp_trash_post( $email_id );
		}
	}

	// Make sure we have no orphaned email type terms.
	$email_types = get_terms( bp_get_email_tax_type(), array(
		'fields'                 => 'ids',
		'hide_empty'             => false,
		'update_term_meta_cache' => false,
	) );

	if ( $email_types ) {
		foreach ( $email_types as $term_id ) {
			wp_delete_term( (int) $term_id, bp_get_email_tax_type() );
		}
	}

	require_once( buddypress()->plugin_dir . '/bp-core/admin/bp-core-admin-schema.php' );
	bp_core_install_emails();

	if ( $switched ) {
		restore_current_blog();
	}

	return array( 0, __( 'Emails have been successfully reinstalled.', 'buddyboss' ) );
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

function bp_admin_assign_member_type() {


	$users = get_users( [
		'fields' => [ 'ID' ]
	]);

	foreach ( $users as $user ) {

		$member_type = bp_get_member_type( $user->ID );

		if ( false === $member_type ) {

			// Get the user object.
			$user1 = get_userdata( $user->ID );

			if ( !in_array( 'administrator', $user1->roles, true ) ) {

				$existing_selected = bp_member_type_default_on_registration();
				// Assign the default member type to user.
				bp_set_member_type( $user->ID, '' );
				bp_set_member_type( $user->ID, $existing_selected );
			}
		}
	}

	// Description of this tool, displayed to the user.
	$statement = __( 'Assign users without a profile type to the default profile type records&hellip; %s', 'buddyboss' );


	$result = __( 'Complete!', 'buddyboss' );

	// All done!
	return array( 0, sprintf( $statement, $result ) );
}
