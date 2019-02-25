<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ========================================================================
 * CONSTANTS
 * ========================================================================
 */

// Codebase version
if ( ! defined( 'BUDDYBOSS_MEDIA_PLUGIN_VERSION' ) ) {
  define( 'BUDDYBOSS_MEDIA_PLUGIN_VERSION', '3.2.6' );
}

// Database version
if ( ! defined( 'BUDDYBOSS_MEDIA_PLUGIN_DB_VERSION' ) ) {
  define( 'BUDDYBOSS_MEDIA_PLUGIN_DB_VERSION', '3.1.7' );
}

// Directory
if ( ! defined( 'BUDDYBOSS_MEDIA_PLUGIN_DIR' ) ) {
  define( 'BUDDYBOSS_MEDIA_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url
if ( ! defined( 'BUDDYBOSS_MEDIA_PLUGIN_URL' ) ) {
  $plugin_url = plugin_dir_url( __FILE__ );

  // If we're using https, update the protocol. Workaround for WP13941, WP15928, WP19037.
  if ( is_ssl() )
    $plugin_url = str_replace( 'http://', 'https://', $plugin_url );

  define( 'BUDDYBOSS_MEDIA_PLUGIN_URL', $plugin_url );
}

// File
if ( ! defined( 'BUDDYBOSS_MEDIA_PLUGIN_FILE' ) ) {
  define( 'BUDDYBOSS_MEDIA_PLUGIN_FILE', __FILE__ );
}

// Generic API files include
if ( ! function_exists( 'is_plugin_active' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

/**
 * ========================================================================
 * MAIN FUNCTIONS
 * ========================================================================
 */

/**
 * Main
 *
 * @return void
 */
function buddyboss_media_init()
{
  global $bp, $buddyboss_media;


	//Check BuddyPress is install and active
	if ( ! function_exists( 'bp_is_active' ) ) {
		add_action( 'admin_notices', 'buddyboss_media_install_buddypress_notice' );
		return;
	}

	$main_include  = BUDDYBOSS_MEDIA_PLUGIN_DIR  . 'includes/main-class.php';

  try
  {
    if ( file_exists( $main_include ) )
    {
      require( $main_include );
    }
    else{
      $msg = sprintf( __( "Couldn't load main class at:<br/>%s", 'buddyboss-media' ), $main_include );
      throw new Exception( $msg, 404 );
    }
  }
  catch( Exception $e )
  {
    $msg = sprintf( __( "<h1>Fatal error:</h1><hr/><pre>%s</pre>", 'buddyboss-media' ), $e->getMessage() );
    echo $msg;
  }

  $buddyboss_media = BuddyBoss_Media_Plugin::instance();
}
buddyboss_media_init();

/**
 * Must be called after hook 'plugins_loaded'
 *
 * @return BuddyBoss Media main/global object
 * @see  class BuddyBoss_Media
 */
function buddyboss_media()
{
  global $buddyboss_media;

  return $buddyboss_media;
}

register_activation_hook( __FILE__, 'buddyboss_media_setup_db_tables' );
/**
* Setup database table for albums.
* Runs on plugin activation.
*
* @since BuddyBoss Media (2.0.0)
*/
function buddyboss_media_setup_db_tables( $network_wide=false ){
   global $wpdb;
   if ( is_multisite() && $network_wide ) {
	   // store the current blog id
	   $current_blog = $wpdb->blogid;

	   // Get all blogs in the network and activate plugin on each one
	   $blog_ids = $wpdb->get_col( "SELECT blog_id FROM $wpdb->blogs" );
	   foreach ( $blog_ids as $blog_id ) {
		   switch_to_blog( $blog_id );
		   buddyboss_media_create_db_tables();
		   restore_current_blog();
	   }
   } else {
	   buddyboss_media_create_db_tables();
   }
}

/**
* Create database table for albums.
*
* @since BuddyBoss Media (2.0.0)
*/
function buddyboss_media_create_db_tables(){
   global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();
	$table_name1 = $wpdb->prefix . 'buddyboss_media_albums';
	$table_name2 = $wpdb->prefix . 'buddyboss_media';

   $sql1 = "CREATE TABLE $table_name1 (
	   id bigint(20) NOT NULL AUTO_INCREMENT,
	   user_id bigint(20) NOT NULL,
	   group_id bigint(20) NULL,
	   date_created datetime NULL DEFAULT '0000-00-00',
	   title text NOT NULL,
	   description text NULL,
	   total_items mediumint(9) NULL DEFAULT '0',
	   privacy varchar(50) NULL DEFAULT 'public',
	   PRIMARY KEY  (id)
   ) $charset_collate;";

   $sql2 = "CREATE TABLE $table_name2 (
		id bigint(20) NOT NULL AUTO_INCREMENT ,
		blog_id bigint(20) NULL DEFAULT NULL,
		media_id bigint(20) NOT NULL ,
		media_author bigint(20) NOT NULL,
		media_title text,
		album_id bigint(20),
		activity_id bigint(20) NULL DEFAULT NULL ,
		privacy varchar(50) NULL DEFAULT 'public',
		favorites bigint(20) NULL DEFAULT 0 ,
		upload_date datetime DEFAULT '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY media_id (media_id),
		KEY media_author (media_author),
		KEY album_id (album_id),
		KEY media_author_id (album_id,media_author),
		KEY activity_id (activity_id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql1 );
   dbDelta( $sql2 );

   update_option( 'buddyboss_media_db_version', BUDDYBOSS_MEDIA_PLUGIN_DB_VERSION );
}

/**
 * Show the admin notice to install/activate BuddyPress first
 */
function buddyboss_media_install_buddypress_notice() {
	echo '<div id="message" class="error fade"><p style="line-height: 150%">';
	_e('<strong>BuddyPress Media</strong></a> requires the BuddyPress plugin to work. Please <a href="http://buddypress.org">install BuddyPress</a> first.', 'buddypress-edit-activity');
	echo '</p></div>';
}

/**
 * Allow automatic updates via the WordPress dashboard
 */
require_once('includes/buddyboss-plugin-updater.php');

require_once('includes/buddyboss-media-wp-user-export-gdpr.php');
