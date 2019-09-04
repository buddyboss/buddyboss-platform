<?php
/**
 * @package WordPress
 * @subpackage BuddyPress for LearnDash
 */
// Exit if accessed directly
if (!defined('ABSPATH'))
    exit;

if (!class_exists('BuddyPress_LearnDash_Admin')):

    /**
     *
     * BuddyPress_LearnDash_Admin
     * ********************
     *
     *
     */
    class BuddyPress_LearnDash_Admin {
        /* Options/Load
         * ===================================================================
         */

        /**
         * Plugin options
         *
         * @var array
         */
        public $options = array();
		
		private $network_activated = false,
				$plugin_slug = 'buddypress-learndash',
				$menu_hook = 'admin_menu',
				$settings_page = 'options-general.php',
				$capability = 'manage_options',
				$form_action = 'options.php',
				$plugin_settings_url;

        /**
         * Empty constructor function to ensure a single instance
         */
        public function __construct() {
            // ... leave empty, see Singleton below
        }

        /* Singleton
         * ===================================================================
         */

        /**
         * Admin singleton
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @param  array  $options [description]
         *
         * @uses BuddyPress_LearnDash_Admin::setup() Init admin class
         *
         * @return object Admin class
         */
        public static function instance() {
            static $instance = null;

            if (null === $instance) {
                $instance = new BuddyPress_LearnDash_Admin;
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
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @param  string $key Option key
         *
         * @uses BuddyPress_LearnDash_Admin::option() Get option
         *
         * @return mixed      Option value
         */
        public function option($key) {
            $value = buddypress_learndash()->option($key);
            return $value;
        }

        /* Actions/Init
         * ===================================================================
         */

        /**
         * Setup admin class
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses buddypress_learndash() Get options from main BuddyPress_LearnDash_Admin class
         * @uses is_admin() Ensures we're in the admin area
         * @uses curent_user_can() Checks for permissions
         * @uses add_action() Add hooks
         */
        public function setup() {
            if ((!is_admin() && !is_network_admin() ) || !current_user_can('manage_options')) {
                return;
            }

			$this->plugin_settings_url = admin_url( 'options-general.php?page=' . $this->plugin_slug );
			
            $this->network_activated = $this->is_network_activated();

			//if the plugin is activated network wide in multisite, we need to override few variables
			if ( $this->network_activated ) {
				// Main settings page - menu hook
				$this->menu_hook = 'network_admin_menu';

				// Main settings page - parent page
				$this->settings_page = 'settings.php';

				// Main settings page - Capability
				$this->capability = 'manage_network_options';

				// Settins page - form's action attribute
				$this->form_action = 'edit.php?action=' . $this->plugin_slug;

				// Plugin settings page url
				$this->plugin_settings_url = network_admin_url('settings.php?page=' . $this->plugin_slug);
			}

            //if the plugin is activated network wide in multisite, we need to process settings form submit ourselves
			if ( $this->network_activated ) {
				add_action('network_admin_edit_' . $this->plugin_slug, array( $this, 'save_network_settings_page' ));
			}

			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( $this->menu_hook, array( $this, 'admin_menu' ) );

			add_filter( 'plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
			add_filter( 'network_admin_plugin_action_links', array( $this, 'add_action_links' ), 10, 2 );
			
        }
		
		/**
		 * Check if the plugin is activated network wide(in multisite)
		 * 
		 * @access private
		 * 
		 * @return boolean
		 */
	   private function is_network_activated(){
		   $network_activated = false;
		   if ( is_multisite() ) {
			   if ( ! function_exists( 'is_plugin_active_for_network' ) )
				   require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			   if( is_plugin_active_for_network( 'buddypress-learndash/buddypress-learndash.php' ) ){
				   $network_activated = true;
			   }
		   }
		   return $network_activated;
	   }

        /**
         * Register admin settings
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses register_setting() Register plugin options
         * @uses add_settings_section() Add settings page option sections
         * @uses add_settings_field() Add settings page option
         */
        public function admin_init() {
            register_setting( 'buddypress_learndash_plugin_options', 'buddypress_learndash_plugin_options');
            add_settings_section( 'general_section', __( 'General Settings', 'buddypress-learndash' ), array( $this, 'section_general' ), __FILE__ );

            add_settings_field('courses_visibility_option', __( 'Visibility', 'buddypress-learndash' ), array( $this, 'courses_visibility_option' ), __FILE__ , 'general_section' );
            add_settings_field('convert_subscribers_option', __( 'User Roles', 'buddypress-learndash' ), array( $this, 'convert_subscribers_option' ), __FILE__ , 'general_section' );
            add_settings_field('convert_group_leaders_option', '', array( $this, 'convert_group_leaders_option' ), __FILE__ , 'general_section' );
        }

        /**
         * Add plugin settings page
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses add_options_page() Add plugin settings page
         */
        public function admin_menu() {
			add_submenu_page(
				$this->settings_page, 'BuddyPress for LearnDash', 'BuddyPress for LearnDash', $this->capability, $this->plugin_slug, array( $this, 'options_page' )
			);
        }

        /**
         * Add plugin settings page
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses BuddyPress_LearnDash_Admin::admin_menu() Add settings page option sections
         */
        public function network_admin_menu() {
            return $this->admin_menu();
        }

        // Add settings link on plugin page
        function plugin_settings_link($links) {
            $settings_link = '<a href="'.admin_url("options-general.php?page=".__FILE__).'">'.__( 'Settings', 'buddypress-learndash' ).'</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        /**
         * Register admin scripts
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses wp_enqueue_script() Enqueue admin script
         * @uses wp_enqueue_style() Enqueue admin style
         * @uses buddypress_learndash()->assets_url Get plugin URL
         */
        public function admin_enqueue_scripts() {
            $js = buddypress_learndash()->assets_url . '/js/';
            $css = buddypress_learndash()->assets_url . '/css/';
        }

        /* Settings Page + Sections
         * ===================================================================
         */

        /**
         * Render settings page
         *
         * @since BuddyPress for LearnDash (1.0.0)
         *
         * @uses do_settings_sections() Render settings sections
         * @uses settings_fields() Render settings fields
         * @uses esc_attr_e() Escape and localize text
         */
        
        public function options_page() {
        ?>
            <div class="wrap">
				<h2><?php _e( 'Buddypress Learndash', 'buddypress-learndash' ); ?></h2>
				<form action="<?php echo $this->form_action; ?>" method="post">
					<?php
						if ( $this->network_activated && isset($_GET['updated']) ) {
							echo "<div class='updated'><p>" . __('Settings updated.', 'buddypress-learndash') . "</p></div>";
						}
					?>
					<?php settings_fields('buddypress_learndash_plugin_options'); ?>
					<?php do_settings_sections(__FILE__); ?>

					<p class="submit">
						<input name="bp_learndash_settings_submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes', 'buddypress-learndash'); ?>" />
					</p>
				</form>
            </div>

            <?php
        }
		
		public function add_action_links( $links, $file ) {
			// Return normal links if not this plugin
			if ( plugin_basename( basename( constant( 'BUDDYPRESS_LEARNDASH_PLUGIN_DIR' ) ) . '/buddypress-learndash.php' ) != $file ) {
				return $links;
			}

			$mylinks = array(
				'<a href="' . esc_url( $this->plugin_settings_url ) . '">' . __( "Settings", "buddypress-learndash" ) . '</a>',
			);
			return array_merge( $links, $mylinks );
		}

		public function save_network_settings_page() {
			if ( ! check_admin_referer( 'buddypress_learndash_plugin_options-options' ) )
				return;

			if ( ! current_user_can( $this->capability ) )
				die( 'Access denied!' );

			if ( isset( $_POST[ 'bp_learndash_settings_submit' ] ) ) {
				$submitted = stripslashes_deep( $_POST[ 'buddypress_learndash_plugin_options' ] );
				
				update_site_option( 'buddypress_learndash_plugin_options', $submitted );
			}

			// Where are we redirecting to?
			$base_url = trailingslashit( network_admin_url() ) . 'settings.php';
			$redirect_url = add_query_arg( array( 'page' => $this->plugin_slug, 'updated' => 'true' ), $base_url );

			// Redirect
			wp_redirect( $redirect_url );
			die();
		}

        public function courses_visibility_option(){
            $value = buddypress_learndash()->option('courses_visibility');
            $checked = '';
            if ( $value ){
                $checked = ' checked="checked" ';
            }
            echo "<input ".$checked." id='courses_visibility' name='buddypress_learndash_plugin_options[courses_visibility]' type='checkbox' />  ";
            printf( __( 'Display <em>Profile > %s</em> content publicly', 'buddypress-learndash' ), LearnDash_Custom_Label::get_label('course') );
        }

        public function convert_subscribers_option(){
            $value = buddypress_learndash()->option('convert_subscribers');
            $checked = '';
            if ( $value ){
                $this->convert_users_to_bp_member_type('subscriber', 'student');
                $checked = ' checked="checked" ';
            }else{
                $this->remove_convertion_users_to_bp_member_type('subscriber', 'student');
            }
            echo "<input ".$checked." id='convert_subscribers' name='buddypress_learndash_plugin_options[convert_subscribers]' type='checkbox' />  ";
            _e( 'Convert subscribers to user role Student', 'buddypress-learndash' );
        }

        public function convert_group_leaders_option(){
            $value = buddypress_learndash()->option('convert_group_leaders');
            $checked = '';
            if ( $value ){
                $this->convert_users_to_bp_member_type('group_leader', 'group_leader');
                $checked = ' checked="checked" ';
            }else{
                $this->remove_convertion_users_to_bp_member_type('group_leader', 'group_leader');
            }
            echo "<input ".$checked." id='convert_group_leaders' name='buddypress_learndash_plugin_options[convert_group_leaders]' type='checkbox' />  ";
            _e( 'Convert group leaders to user role Group Leader', 'buddypress-learndash' );
        }

        public function convert_users_to_bp_member_type($role, $bp_member_tpe){
            $all_users = get_users( 'role='.$role );
            foreach ( $all_users as $user ) {
                $member_type = bp_get_member_type( $user->ID );
                if($member_type != $bp_member_tpe){
                    bp_set_member_type( $user->ID, $bp_member_tpe );
                }
            }
        }

        public function remove_convertion_users_to_bp_member_type($role, $bp_member_tpe){
            $subscribers = get_users( 'role='.$role );
            foreach ( $subscribers as $user ) {
                $member_type = bp_get_member_type( $user->ID );
                if($member_type == $bp_member_tpe){
                    bp_set_member_type( $user->ID, '' );
                }
            }
        }

        /**
         * General settings section
         *
         * @since BuddyPress for LearnDash (1.0.0)
         */
        public function section_general(){

        }

    }

// End class BuddyPress_LearnDash_Admin
endif;