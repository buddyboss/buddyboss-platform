<?php
/**
 * Description of BuddyBoss_Media_Migration
 */
class BBMediaMigration {

	public $bbm_table = '';

	public function __construct() {

		$this->bbm_fetch_all_photos();

		add_action( 'admin_notices', array( &$this, 'add_migration_notice' ) );

		if ( 'success' != get_option('bbm_db_migrate') ) {
			add_action( 'admin_menu', array( $this, 'bbm_migrate_menu' ) );
		}
	}

	//Updating media table
	public function bbm_updating_media_table( ) {

		//Checking if migration needed
		if ( 'success' == get_option('bbm_db_migrate') ) {
			return;
		}
		//Setting timeout limit to 0
		set_time_limit(0);

		$activity_ids = get_option('bbm_db_album_migrate_ids');
		$activity_count = count( $activity_ids );
		$progress_count = get_option('bbm_db_migrate_progress');

		if ( empty($progress_count) ) { $progress_count = 0; }

		global $wpdb;
		//Looping through each activity
		$loop_index = 0; //Activity processing loop index
		foreach ( $activity_ids as $key => $activity_id ) {

			if ( $key < $progress_count ) { continue; }

			$image_ids = buddyboss_media_compat_get_meta( $activity_id, 'activity.item_keys' );

			if ( empty( $image_ids ) ) { $progress_count++; continue; }

			if ( ! is_array( $image_ids ) ) {
				$img_info = get_post($image_ids); //Get Image info

				//Skip deleted media migration
				if ( empty( $img_info ) ) {
					continue;
				}

				//Inserting the data in buddyboss_media table
				$wpdb->insert(
					$wpdb->prefix . 'buddyboss_media', array(
						'blog_id' => get_current_blog_id(),
						'media_id' => $image_ids,
						'media_author' => $img_info->post_author,
						'media_title' => $img_info->post_title,
						'activity_id' => $activity_id,
						'upload_date' => $img_info->post_date,
					),
					array(
						'%d',
						'%d',
						'%d',
						'%s',
						'%d',
						'%d',
					)
				);
				$progress_count++;
				continue;
			}

			//Looping through each image in activity
			foreach ( $image_ids as $image_id ) {

				$img_info = get_post($image_id); //Get Image info

				//Skip deleted media migration
				if ( empty( $img_info ) ) {
					continue;
				}

				//Inserting the data in buddyboss_media table
				$wpdb->insert(
					$wpdb->prefix . 'buddyboss_media', array(
						'blog_id' => get_current_blog_id(),
						'media_id' => $image_id,
						'media_author' => $img_info->post_author,
						'media_title' => $img_info->post_title,
						'activity_id' => $activity_id,
						'upload_date' => $img_info->post_date,
					),
					array(
						'%d',
						'%d',
						'%d',
						'%s',
						'%d',
						'%d',
					)
				);
			}

			$progress_count++;
			$loop_index++;

			if ( 500 <= $loop_index ) { break; }  //Setting the no of loops run at one go

		}
		//Saving progress in DB
		update_option('bbm_db_migrate_progress', $progress_count );

		if ( $activity_count == $progress_count ) {
			//Saving confirmation in database
			update_option('bbm_db_migrate', 'success');
		}

	}

	//Updating media table with album id
	public function bbm_update_album_id() {

		//Checking if migration needed
		if ( 'success' == get_option('bbm_db_album_migrate') ) {
			return;
		}
		//Setting timeout limit to 0
		set_time_limit(0);

		global $wpdb;
		$has_album = $this->bbm_fetch_all_album_activity();
		foreach ( $has_album as $activity_id ) {

			if( empty($activity_id) ) { continue; }

			$album_id = bp_activity_get_meta( $activity_id, 'buddyboss_media_album_id', true );

			//Updating the media table
			$wpdb->update(
				$wpdb->prefix . 'buddyboss_media',
				array(
					'album_id' => $album_id,
				),
				array( 'activity_id' => $activity_id ),
				array(
					'%d'
				),
				array( '%d' )
			);
		}
		//Saving confirmation in database
		update_option('bbm_db_album_migrate', 'success');

	}

	//Fetching all the activities with photos
	public function bbm_fetch_all_photos() {

		if ( get_option('bbm_db_album_migrate_ids') ) {	return; }

		global $wpdb;
		$myrows = $wpdb->get_results( $wpdb->prepare( "SELECT activity_id FROM {$wpdb->base_prefix}bp_activity_meta WHERE (meta_key=%s OR meta_key=%s OR meta_key=%s) ", 'buddyboss_media_aid','buddyboss_pics_aid','bboss_pics_aid' ), ARRAY_A );
		$activity_ids = array();

		//Fetching all the activities id which has media uploaded
		foreach ( $myrows as $row ) {
			$activity_ids[] = $row['activity_id'];
		}
		update_option( 'bbm_db_album_migrate_ids', $activity_ids );
	}

	//Fetching all the activities with albums
	public function bbm_fetch_all_album_activity() {

		global $wpdb;
		$myrows = $wpdb->get_results( $wpdb->prepare( "SELECT activity_id FROM {$wpdb->base_prefix}bp_activity_meta WHERE meta_key=%s ", 'buddyboss_media_album_id' ), ARRAY_A );

		$activity_ids = array();

		//Fetching all the activities id with album assigned
		foreach ( $myrows as $row ) {
			$activity_ids[] = $row['activity_id'];
		}
		return $activity_ids;

	}

	function add_migration_notice(){
		$status = get_option('bbm_db_album_migrate');
		if ( current_user_can( 'manage_options' ) && empty($status) ) {
			$this->create_notice( '<p><strong>BuddyBoss Media</strong>: ' . __( 'Please Migrate your Database', 'buddyboss-media' ) . " <a href='" . admin_url( 'admin.php?page=bbm-migration&force=true' ) . "'>" . __( 'Click Here', 'buddyboss-media' ) . "</a>.  <a href='" . admin_url( 'admin.php?page=bbm-migration&hide=true' ) . "' style='float:right'>" . __( 'Hide', 'buddyboss-media' ) . '</a> </p>' );
		}
	}
	function create_notice( $message, $type = 'error' ){
		echo '<div class="' . $type . '">' . $message . '</div>';
	}

	public function bbm_migrate_menu() {
		add_submenu_page( 'options-general.php', __( 'BuddyBoss Media Migration', 'buddyboss-media' ), __( 'BBM Migration', 'buddyboss-media' ), 'manage_options', 'bbm-migration', array( $this, 'bbm_custom_migrate' ) );
	}

	function bbm_custom_migrate() {
    ?>
        <h2>BuddyBoss Media Migration</h2>
		<p style="font-size: 14px; padding: 14px 14px 14px 0;">We have made some changes to the database structure. Please click on Migrate button until you see <strong style="color: green;">Success</strong> message here.
			If you can see <strong style="color: green;">Success</strong> message you are good to go and don't need to do anything.
		</p>

        <?php
        if( is_multisite() && is_super_admin( get_current_user_id() ) ) {
            $get_all_sites = wp_get_sites();
            if( isset( $get_all_sites ) && !empty( $get_all_sites ) ){
                foreach( $get_all_sites as $single ){
                    switch_to_blog( $single['blog_id'] );
                    $status = get_option('bbm_db_migrate');
                    if ( 'success' != $status ) { $status = 'INCOMPLETE'; }
                    $site_status = 'success' != $status ? 'INCOMPLETE' : 'COMPLETE';
                    $blogname = get_bloginfo('sitename');
                    $progress_count_dis = get_option('bbm_db_migrate_progress');
                    if ( empty( $progress_count_dis ) ) { $progress_count_dis = 0; }
                    ?>
                    <h4>Progress count of [<?php echo $blogname; ?>]: <?php echo $progress_count_dis; ?> activity image posts migrated</h4>
                    <h3>Status of [<?php echo $blogname; ?>]: <span style="color: green; text-transform: uppercase;"><?php echo $site_status; ?></span></h3>
                <?php
                }
            }
            restore_current_blog();
        } else{
            $status = get_option('bbm_db_migrate');
            if ( 'success' != $status ) { $status = 'INCOMPLETE'; }
            $progress_count_dis = get_option('bbm_db_migrate_progress');
            if ( empty( $progress_count_dis ) ) { $progress_count_dis = 0; }
    		?>
            <h4>Progress count: <?php echo $progress_count_dis; ?> activity image posts migrated</h4>
            <h3>Status: <span style="color: green; text-transform: uppercase;"><?php echo $status; ?></span></h3>
        <?php } ?>

        <?php
		if ( empty( $status ) || ('INCOMPLETE' == $status)   ) { ?>
			<form action="options-general.php?page=bbm-migration" method="post">
				<p class="submit">
					<input name="bboss_settings_migrate" type="submit" class="button-primary" value="<?php esc_attr_e( 'Migrate Changes', 'buddyboss-media' ); ?>" />
					<strong style="margin-left: 10px;">Keep clicking button until you see a Success message. We are upgrading your database in batches.</strong>
				</p>
			</form>
		<?php
		}
	}


} // End of BBMediaMigration Class

/**
 * Migration script call for new buddyboss table
 */

function bbm_table_migrate() {

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
		return;
	}

	$is_migration_needed = get_option( 'buddyboss_media_migration' );
	if ( empty( $is_migration_needed ) || ! $is_migration_needed ) {
		return;
	}

	$call_me = new BBMediaMigration();



	if ( is_admin() && ! empty( $_POST[ 'bboss_settings_migrate' ] ) ) {

        if( is_multisite() && is_super_admin( get_current_user_id() ) ) {
            $get_all_sites = wp_get_sites();
            if( isset( $get_all_sites ) && !empty( $get_all_sites ) ){
                foreach( $get_all_sites as $single ){
                    switch_to_blog( $single['blog_id'] );
                    $call_me->bbm_updating_media_table();
                    $call_me->bbm_update_album_id();
                }
            }
            restore_current_blog();
        } else {
            $call_me->bbm_updating_media_table();
            $call_me->bbm_update_album_id();
        }
		delete_option( 'buddyboss_media_migration' );
	}
}

add_action( 'bp_init', 'bbm_table_migrate', 10 );