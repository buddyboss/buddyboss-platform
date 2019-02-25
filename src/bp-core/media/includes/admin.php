<?php
/**
 * @package WordPress
 * @subpackage BuddyBoss Media
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BuddyBoss_Media_Admin' ) ):
/**
 *
 * BuddyBoss Media Admin
 * ********************
 *
 *
 */
class BuddyBoss_Media_Admin
{
	/* Options/Load
	 * ===================================================================
	 */

	/**
	 * Plugin options
	 *
	 * @var array
	 */
	public	$options = array();
	private $plugin_settings_tabs = array();

	private $network_activated = false,
			$plugin_slug = 'bb-buddyboss-media',
			$menu_hook = 'admin_menu',
			$settings_page = 'buddyboss-settings',
			$capability = 'manage_options',
			$form_action = 'options.php',
			$plugin_settings_url;

	/**
	 * Empty constructor function to ensure a single instance
	 */
	public function __construct()
	{
		// ... leave empty, see Singleton below
	}


	/* Singleton
	 * ===================================================================
	 */

	/**
	 * Admin singleton
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @param  array  $options [description]
	 *
	 * @uses BuddyBoss_Media_Admin::setup() Init admin class
	 *
	 * @return object Admin class
	 */
	public static function instance()
	{
		static $instance = null;

		if ( null === $instance )
		{
			$instance = new BuddyBoss_Media_Admin;
			$instance->setup();
		}

		return $instance;
	}


	/* Utility functions
	 * ===================================================================
	 */

	/**
	 * Get option
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @param  string $key Option key
	 *
	 * @uses BuddyBoss_Media_Plugin::option() Get option
	 *
	 * @return mixed      Option value
	 */
	public function option( $key )
	{
		$value = buddyboss_media()->option( $key );
		return $value;
	}

	/* Actions/Init
	 * ===================================================================
	 */

	/**
	 * Setup admin class
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses buddyboss_media() Get options from main BuddyBoss_Media_Plugin class
	 * @uses is_admin() Ensures we're in the admin area
	 * @uses curent_user_can() Checks for permissions
	 * @uses add_action() Add hooks
	 */
	public function setup()
	{
		if ( ( ! is_admin() && ! is_network_admin() ) || ! current_user_can( 'manage_options' ) )
		{
			return;
		}

		$this->plugin_settings_url = admin_url( 'admin.php?page=' . $this->plugin_slug );

		$this->network_activated = $this->is_network_activated();

		//if the plugin is activated network wide in multisite, we need to override few variables
		if ( $this->network_activated ) {
			// Main settings page - menu hook
			$this->menu_hook = 'network_admin_menu';

			// Main settings page - parent page
			//$this->settings_page = 'settings.php';

			// Main settings page - Capability
			$this->capability = 'manage_network_options';

			// Settins page - form's action attribute
			$this->form_action = 'edit.php?action=' . $this->plugin_slug;

			// Plugin settings page url
			$this->plugin_settings_url = network_admin_url('admin.php?page=' . $this->plugin_slug);
		}

		//if the plugin is activated network wide in multisite, we need to process settings form submit ourselves
		if ( $this->network_activated ) {
			add_action('network_admin_edit_' . $this->plugin_slug, array( $this, 'save_network_settings_page' ));
		}

		add_action( 'admin_init', array( $this, 'admin_init' ) );
		add_action( 'admin_init', array($this, 'register_support_settings' ) );
		add_action( $this->menu_hook, array( $this, 'register_buddyboss_menu_page' ) );
		add_action( $this->menu_hook, array( $this, 'admin_menu' ) );

		add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
		add_filter( 'network_admin_plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );

		// EXIF extension not available notice
		add_action( 'admin_notices', array( $this, 'exif_extension_admin_notice' ) );
	}

	/**
		* Check if the plugin is activated network wide(in multisite).
		*
		* @return boolean
		*/
		private function is_network_activated() {
		   $network_activated = false;
		   if ( is_multisite() ) {

			   if ( is_plugin_active_for_network(basename( constant( 'BUDDYBOSS_MEDIA_PLUGIN_DIR' ) ).'/buddyboss-media.php') ) {
				   $network_activated = true;
			   }
		   }
		   return $network_activated;
		}

	/**
	 * Register admin settings
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses register_setting() Register plugin options
	 * @uses add_settings_section() Add settings page option sections
	 * @uses add_settings_field() Add settings page option
	 */
	public function admin_init()
	{
		$this->plugin_settings_tabs['buddyboss_media_plugin_options'] = 'General';

		register_setting( 'buddyboss_media_plugin_options', 'buddyboss_media_plugin_options', array( $this, 'plugin_options_validate' ) );

		//general options
		add_settings_section( 'general_section', __( 'General Settings', 'buddyboss-media' ), array( $this, 'section_general' ), __FILE__ );
		add_settings_field( 'component-slug', __( 'User Photos Slug', 'buddyboss-media' ), array( $this, 'setting_component_slug' ), __FILE__, 'general_section');
		add_settings_field( 'all-media-page', __( 'Global Photos Page', 'buddyboss-media' ), array( $this, 'setting_all_media_page' ), __FILE__, 'general_section');
		add_settings_field( 'activity-photo-size', __( 'Activity Photo Size', 'buddyboss-media' ), array( $this, 'setting_activity_photo_size' ), __FILE__, 'general_section');
		add_settings_field( 'activity-custom-template', __( 'Photo Layout', 'buddyboss-media' ), array( $this, 'setting_activity_custom_template' ), __FILE__, 'general_section');
		add_settings_field( 'enable-tagging', __( 'Friend Tagging', 'buddyboss-media' ), array( $this, 'setting_enable_tagging' ), __FILE__, 'general_section');
		add_settings_field( 'files-per-batch', __( 'Max. Files per Batch', 'buddyboss-media' ), array( $this, 'setting_files_per_batch' ), __FILE__, 'general_section');
		add_settings_field( 'rotation-fix', __( 'Mobile Rotation Fix', 'buddyboss-media' ), array( $this, 'setting_rotation_fix' ), __FILE__, 'general_section' );
		add_settings_field( 'delete-media-permanently', __( 'Media Management', 'buddyboss-media' ), array( $this, 'setting_delete_media_permanently' ), __FILE__, 'general_section' );
		add_settings_field( 'enable-js-debug', __( 'Enable Unminified JS', 'buddyboss-media' ), array( $this, 'setting_enable_js_debug' ), __FILE__, 'general_section');
		add_settings_field( 'show-uploadbox', __( 'Disable Upload Box', 'buddyboss-media' ), array( $this, 'setting_show_uploadbox' ), __FILE__, 'general_section' );

		//bbpress options
		if ( is_plugin_active('bbpress/bbpress.php') ) {
			add_settings_section( 'bbpress_section', __( 'Forums', 'buddyboss-media' ), array( $this, 'section_forums' ), __FILE__ );
			add_settings_field( 'bbpress-media-support', __( 'bbPress Media', 'buddyboss-media' ), array( $this, 'setting_enable_bbpress_media_support' ), __FILE__, 'bbpress_section');
		}

		//groups options
		add_settings_section( 'groups_section', __( 'Groups', 'buddyboss-media' ), array( $this, 'section_groups' ), __FILE__ );
		add_settings_field( 'enable-group-media', __( 'Group Media', 'buddyboss-media' ), array( $this, 'setting_enable_group_media_support' ), __FILE__, 'groups_section' );
		add_settings_field( 'enable-group-albums', __( 'Group Albums', 'buddyboss-media' ), array( $this, 'setting_enable_group_albums' ), __FILE__, 'groups_section' );
	}

	function register_support_settings() {
		$this->plugin_settings_tabs[ 'buddyboss_media_support_options' ] = __('Support','buddyboss-media');

		register_setting( 'buddyboss_media_support_options', 'buddyboss_media_support_options' );
		add_settings_section( 'section_support', ' ', array( &$this, 'section_support_desc' ), 'buddyboss_media_support_options' );
	}

	function section_support_desc() {
		if ( file_exists( dirname( __FILE__ ) . '/help-support.php' ) ) {
			require_once( dirname( __FILE__ ) . '/help-support.php' );
		}
	}

	/**
	 * Add plugin settings page
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses add_options_page() Add plugin settings page
	 */
	public function admin_menu()
	{
		add_submenu_page(
				$this->settings_page, 'BuddyBoss Media', 'Media', $this->capability, $this->plugin_slug, array( $this, 'options_page' )
		);
	}

	/**
	 * Add plugin settings page
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses BuddyBoss_Media_Admin::admin_menu() Add settings page option sections
	 */
	public function network_admin_menu()
	{
		return $this->admin_menu();
	}

	/**
	 * Resister BuddyBoss Menu Page
	 */
	public function register_buddyboss_menu_page() {

	    if ( ! empty( $GLOBALS['admin_page_hooks']['buddyboss-settings'] ) ) return;

		// Set position with odd number to avoid confict with other plugin/theme.
		add_menu_page( 'BuddyBoss', 'BuddyBoss', 'manage_options', 'buddyboss-settings', '', buddyboss_media()->assets_url . '/img/logo.svg', 60 );

		// To remove empty parent menu item.
		add_submenu_page( 'buddyboss-settings', 'BuddyBoss', 'BuddyBoss', 'manage_options', 'buddyboss-settings' );
		remove_submenu_page( 'buddyboss-settings', 'buddyboss-settings' );
	}

	/**
	 * Register admin scripts
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses wp_enqueue_script() Enqueue admin script
	 * @uses wp_enqueue_style() Enqueue admin style
	 * @uses buddyboss_media()->assets_url Get plugin URL
	 */
	public function admin_enqueue_scripts()
	{
		$js  = buddyboss_media()->assets_url . 'js/';
		$css = buddyboss_media()->assets_url . 'css/';

		// wp_enqueue_script( 'wp-color-picker' );
		// load the minified version of custom script
		// wp_enqueue_script( 'buddyboss-media-custom', $js . 'color-pick.js', array( 'jquery', 'wp-color-picker' ), '1.1', true );
		// wp_enqueue_style( 'wp-color-picker' );
	}

	/* Settings Page + Sections
	 * ===================================================================
	 */

	/**
	 * Render settings page
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses do_settings_sections() Render settings sections
	 * @uses settings_fields() Render settings fields
	 * @uses esc_attr_e() Escape and localize text
	 */
	public function options_page()
	{
		$tab = isset( $_GET['tab'] ) ? $_GET['tab'] : __FILE__;
	?>
		<div class="wrap">
			<h2><?php _e( 'BuddyBoss Media', 'buddyboss-media' ); ?></h2>
			<?php $this->plugin_options_tabs(); ?>
			<form action="<?php echo $this->form_action; ?>" method="post">

				<?php
					if ( $this->network_activated && isset($_GET['updated']) ) {
						echo "<div class='updated'><p>" . __('Settings updated.', 'buddyboss-media') . "</p></div>";
					}
				?>
				<?php
				if ( 'buddyboss_media_plugin_options' == $tab || empty($_GET['tab']) ) {
					settings_fields( 'buddyboss_media_plugin_options' );
					do_settings_sections( __FILE__ ); ?>
					<p class="submit">
						<input name="bboss_g_s_settings_submit" type="submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'buddyboss-media' ); ?>" />
					</p><?php
				} else {
					settings_fields( $tab );
					do_settings_sections( $tab );
				} ?>

                <script type="text/javascript">
                    jQuery(document).ready(function($){
                        //the setting 'enable group photo albums' is void if 'group media support' is disabled.
                        //Therefore, to make that clear to webmaster, lets uncheck 'enable group photo albums' option if 'group media support' is unchecked
                        $('input[name="buddyboss_media_plugin_options[group_media_support]"]').change(function(){
                            if( !$(this).is(':checked') ){
                                $('input[name="buddyboss_media_plugin_options[group_albums]"]').removeAttr('checked').attr('disabled','');
                            } else {
                                $('input[name="buddyboss_media_plugin_options[group_albums]"]').removeAttr('disabled');
                            }
                        });
                    });
                </script>
			</form>
		</div>

	<?php
	}

	function plugin_options_tabs() {
		$current_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'buddyboss_media_plugin_options';

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab == $tab_key ? 'nav-tab-active' : '';
			echo '<a class="nav-tab ' . $active . '" href="?page=' . 'bb-buddyboss-media' . '&tab=' . $tab_key . '">' . $tab_caption . '</a>';
		}
		echo '</h2>';
	}

	public function add_action_links( $links, $file ) {
		// Return normal links if not this plugin
		if ( plugin_basename( basename( constant( 'BUDDYBOSS_MEDIA_PLUGIN_DIR' ) ) . '/buddyboss-media.php' ) != $file ) {
			return $links;
		}

		$mylinks = array(
			'<a href="' . esc_url( $this->plugin_settings_url ) . '">' . __( "Settings", "buddyboss-media" ) . '</a>',
		);
		return array_merge( $links, $mylinks );
	}

	public function save_network_settings_page() {
		if ( ! check_admin_referer( 'buddyboss_media_plugin_options-options' ) )
			return;

		if ( ! current_user_can( $this->capability ) )
			die( 'Access denied!' );

		if ( isset( $_POST[ 'bboss_g_s_settings_submit' ] ) ) {
			$submitted = stripslashes_deep( $_POST[ 'buddyboss_media_plugin_options' ] );
			$submitted = $this->plugin_options_validate( $submitted );

			update_site_option( 'buddyboss_media_plugin_options', $submitted );
		}

		// Where are we redirecting to?
		$base_url = trailingslashit( network_admin_url() ) . 'admin.php';
		$redirect_url = esc_url_raw(add_query_arg( array( 'page' => $this->plugin_slug, 'updated' => 'true' ), $base_url ));

		// Redirect
		// Redirect
		wp_redirect( $redirect_url );
		die();
	}

	/**
	 * General settings section
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function section_general()
	{

	}

	/**
	 * Forums settings section
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function section_forums()
	{

	}

	/**
	 * Groups settings section
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function section_groups()
	{

	}

	/**
	 * Validate plugin option
	 *
	 * @since BuddyBoss Media (1.0.0)
	 */
	public function plugin_options_validate( $input )
	{
		$input['group_media_support'] = isset( $input['group_media_support'] ) ? sanitize_text_field( $input['group_media_support'] ) : '';
		$input['group_albums'] = isset( $input['group_albums'] ) ? sanitize_text_field( $input['group_albums'] ) : '';

		return $input; // return validated input
	}

	/* Settings Page Options
	 * ===================================================================
	 */

	/**
	 * Setting > Photos Url
	 *
	 * @since BuddyBoss Media (1.0.1)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_component_slug()
	{
		$slug = $this->option( 'component-slug' );
		if( !$slug ){
			$slug = buddyboss_media_default_component_slug();
		}

		echo "<input id='component-slug' name='buddyboss_media_plugin_options[component-slug]' type='text' value='" . esc_attr( $slug ) . "' />";
		echo '<p class="description">' . __( 'Example: ', 'buddyboss-media' ) . '<a href="'.bp_loggedin_user_domain() . $slug .'"profile/edit">' . bp_loggedin_user_domain() . '<strong>' . $slug . '</strong>/</a>' . '</p>';
	}

	/**
	 * Setting > BuddyBoss Media Rotation Fixer Enabled
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_rotation_fix()
	{
		$value = $this->option( 'rotation_fix' );

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		$memory_limit = @ini_get( 'memory_limit' );

		if ( empty( $memory_limit ) )
		{
			$memory_limit = 'N/A';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[rotation_fix]' type='checkbox' />  ";

		_e( "Enable fix for mobile uploads rotating.", 'buddyboss-media' );

		echo '<p class="description">';
		_e( "It's recommended that you have at least 256M-512M of RAM allocated to PHP, otherwise photo uploads may fail.", "buddyboss-media" );
		echo "<br/>";
		_e( "Your current memory limit is ", "buddyboss-media" );
		echo "<strong>$memory_limit</strong>. ";
		_e( "You can contact your web host to increase the memory limit.", "buddyboss-media" );
		echo '</p>';
	}

	/**
	 * Setting > all media page
	 *
	 * @since BuddyBoss Media (1.0.1)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_all_media_page()
	{
		$all_media_page = $this->option( 'all-media-page' );

		echo wp_dropdown_pages( array(
			'name'             => 'buddyboss_media_plugin_options[all-media-page]',
			'echo'             => false,
			'show_option_none' => __( '- None -', 'buddyboss-media' ),
			'selected'         => $all_media_page
		) );
		echo '<a href="' . admin_url( esc_url(add_query_arg( array( 'post_type' => 'page' ), 'post-new.php' ) )) . '" class="button-secondary">' . __( 'New Page', 'buddyboss-media' ) .'</a>';
		if ( !empty( $all_media_page ) ) {
			echo '<a href="' . get_permalink( $all_media_page ) .'" class="button-secondary" target="_bp" style="margin-left: 5px;">' . __( 'View', 'buddyboss-media' ) . '</a>';
		}
		echo '<p class="description">' . __( 'Use a WordPress page to display all media uploaded by all users.<br /> You may need to reset your permalinks after changing this setting. Go to Settings > Permalinks.', 'buddyboss-media' ) . '</p>';
	}

	/**
	 * Setting > activity photo size
	 *
	 * @since BuddyBoss Media (1.1.2)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_activity_photo_size(){
		$activity_photo_size = $this->option( 'activity-photo-size' );

		echo '<select name="buddyboss_media_plugin_options[activity-photo-size]">';

		$options = array(
			'medium'						=> __( 'Medium', 'buddyboss-media' ),
			'buddyboss_media_photo_wide'	=> __( 'Large', 'buddyboss-media' ),
		);
		foreach( $options as $option=>$label ){
			$selected = $option==$activity_photo_size ? ' selected' : '';
			echo '<option value="' . esc_attr( $option ).  '" ' . $selected . '>' . $label . '</option>';
		}

		echo '</select>';

		echo '<p class="description">' . __( 'Image size displayed in activity posts.', 'buddyboss-media' ) . '</p>';
	}

	/**
	 * Setting > activity custom template
	 *
	 * @since BuddyBoss Media (1.1.2)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_activity_custom_template(){
		$activity_custom_template = $this->option( 'activity-custom-template' );
		if( !$activity_custom_template ){
			$activity_custom_template = 'yes';
		}

		$options = array(
			'yes'	=> __( 'Grid', 'buddyboss-media' ),
			'no'	=> __( 'Activity Posts', 'buddyboss-media' )
		);
		foreach( $options as $option=>$label ){
			$checked = $activity_custom_template == $option ? ' checked' : '';
			echo '<input type="radio" name="buddyboss_media_plugin_options[activity-custom-template]" value="'. $option . '" '. $checked . '>' . $label . '&nbsp;&nbsp;';
		}

		echo '<p class="description">' . __( 'In your albums, you can display photos in a grid or as activity posts.', 'buddyboss-media' ) . '</p>';
	}

	/**
	 * Setting > Friends Tagging Enabled
	 *
	 * @since BuddyBoss Media (1.1.2)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_enable_tagging()
	{
		$value = $this->option( 'enable_tagging' );

		$checked = '';

		if ( $value=='yes' ){
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[enable_tagging]' type='checkbox' value='yes' />" . __( 'Enable Tagging.', 'buddyboss-media' ) . "";
		echo '<p class="description">' . __( 'Allow members to tag friends in media uploads.', 'buddyboss-media' ) . '</p>';
	}

	/**
	 * Setting > Files Per Batch
	 *
	 * @since BuddyBoss Media (2.1)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_files_per_batch()
	{
		$files_per_batch = $this->option( 'files-per-batch' );
		if( !$files_per_batch ){
			$files_per_batch = 4;
		}

		echo "<input id='files-per-batch' name='buddyboss_media_plugin_options[files-per-batch]' min='1' type='number' value='" . esc_attr( $files_per_batch ) . "' />";
		echo '<p class="description">' . __( 'Maximum number of images that can be uploaded in one batch.', 'buddyboss-media' ) . '</p>';
	}

	/**
	 * Setting > BuddyBoss Media > Delete Media Permanently
	 *
	 * @since BuddyBoss Media (3.2.0)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_delete_media_permanently()
	{
		$value = $this->option( 'delete_media_permanently' );

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[delete_media_permanently]' type='checkbox' value='yes' />" . __( 'When a photo upload is removed, permanently delete the associated media file.', 'buddyboss-media' ) . "";
	}

	/**
	 * Setting > BuddyBoss Media > Load minified version of the js
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_enable_js_debug()
	{
		$value = $this->option( 'enable_js_debug' );

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[enable_js_debug]' type='checkbox' value='yes' />" . __( 'Load the Unminified version of Javascript, for compatibility with certain plugins.', 'buddyboss-media' ) . "";
	}

	/**
	 * Setting > BuddyBoss Media > Show light box
	 *
	 * @since BuddyBoss Media (3.2.0)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_show_uploadbox() {
		$value = $this->option( 'show_uploadbox' );

		$checked = '';

		if ( $value ) {
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[show_uploadbox]' type='checkbox' value='yes' />" . __( "When adding a photo, use browser's default file selector instead of our popup upload box.", "buddyboss-media" ) . "";
	}

	/**
	 * Setting > BuddyBoss Media bbPress Enabled
	 *
	 * @since BuddyBoss Media (1.0.0)
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_enable_bbpress_media_support()
	{


		$value = $this->option( 'bbpress_media_support' );

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[bbpress_media_support]' type='checkbox' value='yes' />" . __( 'Allow photo posting in bbPress groups and forums.', 'buddyboss-media' ) . "";
	}

	/**
	 * Setting > BuddyBoss Media Group Albums
	 *
	 * @since BuddyBoss Media ()
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_enable_group_media_support()
	{


		$value = buddyboss_media()->is_group_media_enabled();

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[group_media_support]' type='checkbox'  />" . __( 'Allow photo posting in BuddyPress group activity updates and comments.', 'buddyboss-media' ) . "";
	}


	/**
	 * Setting > BuddyBoss Media Group Albums
	 *
	 * @since BuddyBoss Media ()
	 *
	 * @uses BuddyBoss_Media_Admin::option() Get plugin option
	 */
	public function setting_enable_group_albums()
	{
		$value = buddyboss_media()->is_group_albums_enabled();

		$checked = '';

		if ( $value )
		{
			$checked = ' checked="checked" ';
		}

		echo "<input ".$checked." id='enabled' name='buddyboss_media_plugin_options[group_albums]' type='checkbox'  />" . __( 'Enable BuddyPress group photo albums.', 'buddyboss-media' ) . "";
	}

	/**
	 * Display admin notice if Mobile Rotation Fix is enabled but EXIF php extension not loaded
	 */
	public function exif_extension_admin_notice() {

		$enabled = buddyboss_media()->option( 'rotation_fix' ) === 'on';

		if ( $enabled && ! extension_loaded( 'exif' ) ) {
			$notice = __( "Mobile Rotation Fix requires PHP EXIF extension to be loaded. Please contact your server administrator.", 'buddyboss-media' );
			echo "<div class='error'><p><strong>BuddyBoss Media: </strong>{$notice}</p></div>";
		}
	}

}
// End class BuddyBoss_Media_Admin

endif;
