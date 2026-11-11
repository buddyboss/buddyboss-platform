<?php

// Exit if accessed directly..
defined( 'ABSPATH' ) || exit;

/**
 * Main BuddyPress Class.
 *
 * Tap tap tap... Is this thing on?
 *
 * @since BuddyPress 1.6.0
 */
class BBoss_Platform {
	/**
	 * Identify if ths BuddyPress class is from BuddyBoss Platform
	 *
	 * @since BuddyPress 1.0.0
	 * @var boolean
	 */
	public $buddyboss = true;

	/** Magic *****************************************************************/

	/**
	 * BuddyPress uses many variables, most of which can be filtered to
	 * customize the way that it works. To prevent unauthorized access,
	 * these variables are stored in a private array that is magically
	 * updated using PHP 5.2+ methods. This is to prevent third party
	 * plugins from tampering with essential information indirectly, which
	 * would cause issues later.
	 *
	 * @see BuddyPress::setup_globals()
	 * @var array
	 */
	private $data;

	/** Not Magic *************************************************************/

	/**
	 * @var array Primary BuddyPress navigation.
	 */
	public $bp_nav = array();

	/**
	 * @var array Secondary BuddyPress navigation to $bp_nav.
	 */
	public $bp_options_nav = array();

	/**
	 * @var array The unfiltered URI broken down into chunks.
	 * @see bboss_core_set_uri_globals()
	 */
	public $unfiltered_uri = array();

	/**
	 * @var array The canonical URI stack.
	 * @see bboss_redirect_canonical()
	 * @see bboss_core_new_nav_item()
	 */
	public $canonical_stack = array();

	/**
	 * @var array Additional navigation elements (supplemental).
	 */
	public $action_variables = array();

	/**
	 * @var string Current member directory type.
	 */
	public $current_member_type = '';

	/**
	 * @var array Required components (core, members).
	 */
	public $required_components = array();

	/**
	 * @var array Additional active components.
	 */
	public $loaded_components = array();

	/**
	 * @var array Active components.
	 */
	public $active_components = array();

	/**
	 * @var array Integrations.
	 */
	public $available_integrations = array();

	/**
	 * @var array Integrations.
	 */
	public $integrations = array();

	/**
	 * Whether autoload is in use.
	 *
	 * @since BuddyPress 2.5.0
	 * @var bool
	 */
	public $do_autoload = true;

	/** Option Overload *******************************************************/

	/**
	 * @var array Optional Overloads default options retrieved from get_option().
	 */
	public $options = array();

	/** Singleton *************************************************************/

	/**
	 * Main BuddyPress Instance.
	 *
	 * BuddyPress is great.
	 * Please load it only one time.
	 * For this, we thank you.
	 *
	 * Insures that only one instance of BuddyPress exists in memory at any
	 * one time. Also prevents needing to define globals all over the place.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @static object $instance
	 * @see bboss()
	 *
	 * @return BBoss_Platform|null The one true BuddyPress.
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been run previously.
		if ( null === $instance ) {
			$instance = new BBoss_Platform();
			$instance->constants();
			$instance->setup_globals();
			$instance->legacy_constants();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance.
		return $instance;

		// The last metroid is in captivity. The galaxy is at peace.
		// The powers of the metroid might be harnessed for the good of civilization.
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent BuddyPress from being loaded more than once.
	 *
	 * @since BuddyPress 1.7.0
	 * @see BuddyPress::instance()
	 * @see bboss()
	 */
	private function __construct() {
		/* Do nothing here */ }

	/**
	 * A dummy magic method to prevent BuddyPress from being cloned.
	 *
	 * @since BuddyPress 1.7.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin\' huh?', 'buddyboss' ), '1.7' ); }

	/**
	 * A dummy magic method to prevent BuddyPress from being unserialized.
	 *
	 * @since BuddyPress 1.7.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html__( 'Cheatin\' huh?', 'buddyboss' ), '1.7' ); }

	/**
	 * Magic method for checking the existence of a certain custom field.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $key Key to check the set status for.
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->data[ $key ] ); }

	/**
	 * Magic method for getting BuddyPress variables.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $key Key to return the value for.
	 *
	 * @return mixed
	 */
	public function __get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null; }

	/**
	 * Magic method for setting BuddyPress variables.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $key   Key to set a value for.
	 * @param mixed  $value Value to set.
	 */
	public function __set( $key, $value ) {
		$this->data[ $key ] = $value; }

	/**
	 * Magic method for unsetting BuddyPress variables.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $key Key to unset a value for.
	 */
	public function __unset( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			unset( $this->data[ $key ] );
		} }

	/**
	 * Magic method to prevent notices and errors from invalid method calls.
	 *
	 * @since BuddyPress 1.7.0
	 *
	 * @param string $name
	 * @param array  $args
	 *
	 * @return null
	 */
	public function __call( $name = '', $args = array() ) {
		unset( $name, $args );
		return null; }

	/** Private Methods *******************************************************/

	/**
	 * Bootstrap constants.
	 *
	 * @since BuddyPress 1.6.0
	 */
	private function constants() {

		// Place your custom code (actions/filters) in a file called
		// '/plugins/bp-custom.php' and it will be loaded before anything else.
		if ( file_exists( WP_PLUGIN_DIR . '/bp-custom.php' ) ) {
			require WP_PLUGIN_DIR . '/bp-custom.php';
		}

		// Path and URL.
		if ( ! defined( 'BBOSS_PLUGIN_DIR' ) ) {
			define( 'BBOSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'BBOSS_PLUGIN_URL' ) ) {
			define( 'BBOSS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Legacy forum constant - supported for compatibility with bbPress 2.
		if ( ! defined( 'BBOSS_FORUMS_PARENT_FORUM_ID' ) ) {
			define( 'BBOSS_FORUMS_PARENT_FORUM_ID', 1 );
		}

		// Legacy forum constant - supported for compatibility with bbPress 2.
		if ( ! defined( 'BBOSS_FORUMS_SLUG' ) ) {
			define( 'BBOSS_FORUMS_SLUG', 'forums' );
		}

		// Only applicable to those running trunk.
		if ( ! defined( 'BBOSS_SOURCE_SUBDIRECTORY' ) ) {
			define( 'BBOSS_SOURCE_SUBDIRECTORY', '' );
		}

		// Define on which blog ID BuddyPress should run.
		if ( ! defined( 'BBOSS_ROOT_BLOG' ) ) {

			// Default to use current blog ID
			// Fulfills non-network installs and BP_ENABLE_MULTIBLOG installs.
			$root_blog_id = get_current_blog_id();

			// Multisite check.
			if ( is_multisite() ) {

				// Multiblog isn't enabled.
				if ( ! defined( 'BP_ENABLE_MULTIBLOG' ) || ( defined( 'BP_ENABLE_MULTIBLOG' ) && (int) constant( 'BP_ENABLE_MULTIBLOG' ) === 0 ) ) {
					// Check to see if BP is network-activated
					// We're not using is_plugin_active_for_network() b/c you need to include the
					// /wp-admin/includes/plugin.php file in order to use that function.

					// get network-activated plugins.
					$plugins = get_site_option( 'active_sitewide_plugins' );

					// basename.
					$basename = basename( constant( 'BBOSS_PLUGIN_DIR' ) ) . '/bp-loader.php';

					// plugin is network-activated; use main site ID instead.
					if ( isset( $plugins[ $basename ] ) ) {
						$current_site = get_current_site();
						$root_blog_id = $current_site->blog_id;
					}
				}
			}

			define( 'BBOSS_ROOT_BLOG', $root_blog_id );
		}

		// The search slug has to be defined nice and early because of the way
		// search requests are loaded
		//
		// @todo Make this better
		if ( ! defined( 'BBOSS_SEARCH_SLUG' ) ) {
			define( 'BBOSS_SEARCH_SLUG', 'search' );
		}
	}

	/**
	 * Component global variables.
	 *
	 * @since BuddyPress 1.6.0
	 * @since BuddyBoss 1.0.0
	 */
	private function setup_globals() {

		/** Versions */
		$this->version    = defined( 'BBOSS_PLATFORM_VERSION' ) ? BBOSS_PLATFORM_VERSION : ( defined( 'BBOSS_VERSION' ) ? BBOSS_VERSION : '1.0.0' );
		$this->db_version = 23641;

		/** Loading */

		/**
		 * Should deprecated code be loaded?
		 *
		 * @since BuddyPress 2.0.0 Defaults to false always
		 * @since BuddyPress 2.8.0 Defaults to true on upgrades, false for new installs.
		 */
		$this->load_deprecated = false;

		/** Toolbar */

		/**
		 * @var string The primary toolbar ID.
		 */
		$this->my_account_menu_id = '';

		/** URIs */

		/**
		 * @var int The current offset of the URI.
		 * @see bboss_core_set_uri_globals()
		 */
		$this->unfiltered_uri_offset = 0;

		/**
		 * @var bool Are status headers already sent?
		 */
		$this->no_status_set = false;

		/** Components */

		/**
		 * @var string Name of the current BuddyPress component (primary).
		 */
		$this->current_component = '';

		/**
		 * @var string Name of the current BuddyPress item (secondary).
		 */
		$this->current_item = '';

		/**
		 * @var string Name of the current BuddyPress action (tertiary).
		 */
		$this->current_action = '';

		/**
		 * @var bool Displaying custom 2nd level navigation menu (I.E a group).
		 */
		$this->is_single_item = false;

		/** Root */

		/**
		 * Filters the BuddyPress Root blog ID.
		 *
		 * @since BuddyPress 1.5.0
		 *
		 * @const constant BP_ROOT_BLOG BuddyPress Root blog ID.
		 */
		$this->root_blog_id = (int) apply_filters( 'bp_get_root_blog_id', BBOSS_ROOT_BLOG );

		/** Paths*/

		// BuddyPress root directory.
		$this->file       = constant( 'BBOSS_PLUGIN_DIR' ) . 'bp-loader.php';
		$this->basename   = basename( constant( 'BBOSS_PLUGIN_DIR' ) ) . '/bp-loader.php';
		$this->plugin_dir = trailingslashit( constant( 'BBOSS_PLUGIN_DIR' ) . constant( 'BBOSS_SOURCE_SUBDIRECTORY' ) );
		$this->plugin_url = trailingslashit( constant( 'BBOSS_PLUGIN_URL' ) . constant( 'BBOSS_SOURCE_SUBDIRECTORY' ) );

		// Languages.
		$this->lang_dir = $this->plugin_dir . 'bp-languages';

		// Templates (theme compatibility).
		$this->themes_dir = $this->plugin_dir . 'bp-templates';
		$this->themes_url = $this->plugin_url . 'bp-templates';

		// Languages.
		$this->integration_dir = $this->plugin_dir . 'bp-integrations';
		$this->integration_url = $this->plugin_url . 'bp-integrations';

		// Path to add support for third party plugin.
		$this->compatibility_dir = $this->plugin_dir . 'bp-core/compatibility/';
		$this->compatibility_url = $this->plugin_url . 'bp-core/compatibility/';

		/** Theme Compat */
		$this->theme_compat = new stdClass(); // Base theme compatibility class.
		$this->filters      = new stdClass(); // Used when adding/removing filters.

		/** Users */

		$this->current_user   = new stdClass();
		$this->displayed_user = new stdClass();

		/** Post types and taxonomies */

		/**
		 * Filters the post type slug for the email component.
		 *
		 * @since 2.5.0
		 *
		 * @param string $value Email post type slug.
		 */
		$this->email_post_type = apply_filters( 'bp_email_post_type', 'bp-email' );

		/**
		 * Filters the post type slug for the profile type component.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $value Profile Type post type slug.
		 */
		$this->member_type_post_type = apply_filters( 'bp_member_type_post_type', 'bp-member-type' );

		/**
		 * Filters the post type slug for the group type component.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $value Group Type post type slug.
		 */
		$this->group_type_post_type = apply_filters( 'bp_groups_group_type_post_type', 'bp-group-type' );

		/**
		 * Filters the taxonomy slug for the email type component.
		 *
		 * @since BuddyPress 2.5.0
		 *
		 * @param string $value Email type taxonomy slug.
		 */
		$this->email_taxonomy_type = apply_filters( 'bp_email_tax_type', 'bp-email-type' );

		/**
		 * Filters the post type slug for the invites component.
		 *
		 * @since BuddyBoss 1.0.0
		 *
		 * @param string $value Group Type post type slug.
		 */
		$this->invite_post_type = apply_filters( 'bp_invite_post_type', 'bp-invite' );
	}

	/**
	 * Legacy BuddyPress constants.
	 *
	 * Try to avoid using these. Their values have been moved into variables
	 * in the instance, and have matching functions to get/set their values.
	 *
	 * @since BuddyPress 1.7.0
	 */
	private function legacy_constants() {

		// Define the BuddyPress version.
		if ( ! defined( 'BBOSS_PLATFORM_VERSION' ) ) {
			define( 'BBOSS_PLATFORM_VERSION', $this->version );
		}

		// Define the BuddyPress version.
		if ( ! defined( 'BBOSS_VERSION' ) ) {
			define( 'BBOSS_VERSION', $this->version );
		}

		// Define the database version.
		if ( ! defined( 'BBOSS_DB_VERSION' ) ) {
			define( 'BBOSS_DB_VERSION', $this->db_version );
		}

		// Define if deprecated functions should be ignored.
		if ( ! defined( 'BBOSS_IGNORE_DEPRECATED' ) ) {
			define( 'BBOSS_IGNORE_DEPRECATED', true );
		}
	}

	/**
	 * Include required files.
	 *
	 * @since BuddyPress 1.6.0
	 */
	private function includes() {
		spl_autoload_register( array( $this, 'autoload' ) );

		// @bbwt-com-start mothership-hub
		// Initialize BuddyBoss Mothership (License & Add-ons).
		require $this->plugin_dir . 'bp-core/admin/mothership/mothership-init.php';
		// @bbwt-com-end mothership-hub

		// Load the compatibility helpers for third party plugins.
		require $this->compatibility_dir . '/bboss-incompatible-plugins-helper.php';

		// Load the WP abstraction file so BuddyPress can run on all WordPress setups.
		require $this->plugin_dir . 'bp-core/bboss-core-wpabstraction.php';

		// Setup the versions (after we include multisite abstraction above).
		$this->versions();

		/** Update/Install */

		// Theme compatibility.
		require $this->plugin_dir . 'bp-core/bboss-core-template-loader.php';
		require $this->plugin_dir . 'bp-core/bboss-core-theme-compatibility.php';

		// Require all of the BuddyPress core libraries.
		require $this->plugin_dir . 'bp-core/bboss-core-dependency.php';
		require $this->plugin_dir . 'bp-core/bboss-core-actions.php';
		require $this->plugin_dir . 'bp-core/bboss-core-caps.php';
		require $this->plugin_dir . 'bp-core/bboss-core-cache.php';
		require $this->plugin_dir . 'bp-core/bboss-core-cssjs.php';
		require $this->plugin_dir . 'bp-core/bboss-core-update.php';
		require $this->plugin_dir . 'bp-core/bboss-core-options.php';
		require $this->plugin_dir . 'bp-core/bboss-core-taxonomy.php';
		require $this->plugin_dir . 'bp-core/bboss-core-filters.php';
		require $this->plugin_dir . 'bp-core/bboss-core-attachments.php';
		require $this->plugin_dir . 'bp-core/bboss-core-avatars.php';
		require $this->plugin_dir . 'bp-core/bboss-core-widgets.php';
		require $this->plugin_dir . 'bp-core/bboss-core-template.php';
		require $this->plugin_dir . 'bp-core/bboss-core-adminbar.php';
		require $this->plugin_dir . 'bp-core/bboss-core-buddybar.php';
		require $this->plugin_dir . 'bp-core/bboss-core-catchuri.php';
		require $this->plugin_dir . 'bp-core/bboss-core-functions.php';
		require $this->plugin_dir . 'bp-core/bboss-core-moderation.php';
		require $this->plugin_dir . 'bp-core/bboss-core-cron.php';
		require $this->plugin_dir . 'bp-core/bboss-core-loader.php';
		require $this->plugin_dir . 'bp-core/bboss-core-customizer-email.php';
		require $this->plugin_dir . 'bp-core/bboss-core-emails.php';
		require $this->plugin_dir . 'bp-core/bboss-core-wp-emails.php';
		require $this->plugin_dir . 'bp-core/bboss-core-profile-search.php';
		require $this->plugin_dir . 'bp-core/bboss-core-gdpr.php';
		require $this->plugin_dir . 'bp-core/bboss-core-rest-api.php';
		require $this->plugin_dir . 'bp-core/bboss-core-notification.php';
		require $this->plugin_dir . 'bp-core/bboss-core-invitation.php';
		require $this->plugin_dir . 'bp-core/bb-core-subscriptions.php';
		require $this->plugin_dir . 'bp-core/bb-core-bookmarks.php';
		require $this->plugin_dir . 'bp-core/bb-core-drafts.php';
		require $this->plugin_dir . 'bp-core/bb-core-blocks.php';

		// Maybe load deprecated buddypress functionality (this double negative is proof positive!).
		if ( ! bboss_get_option( '_bp_ignore_deprecated_code', ! $this->load_deprecated ) ) {

			require $this->plugin_dir . 'bp-core/deprecated/buddypress/1.2.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/1.5.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/1.6.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/1.7.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/1.9.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.0.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.1.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.2.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.3.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.4.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.5.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.6.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.7.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.8.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/2.9.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddypress/3.0.php';

			// Maybe load deprecated buddyboss functionality (this double negative is proof positive!).
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.0.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.1.8.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.2.2.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.2.9.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.5.3.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.6.4.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.7.0.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/1.8.6.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.1.4.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.2.6.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.3.5.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.3.6.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.3.71.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.4.90.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/2.5.10.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/3.0.0.php';
			require $this->plugin_dir . 'bp-core/deprecated/buddyboss/3.7.0.php';
		}

		// @bbwt-com-start wp-cli-namespace
		if ( defined( 'WP_CLI' ) && file_exists( $this->plugin_dir . 'cli/wp-cli-bp.php' ) ) {
			require $this->plugin_dir . 'cli/wp-cli-bp.php';
		}
		// @bbwt-com-end wp-cli-namespace

		if ( bboss_is_readylaunch_enabled() ) {
			require $this->plugin_dir . 'bp-core/bb-core-readylaunch.php';
		}
	}

	/**
	 * Autoload classes.
	 *
	 * @since BuddyPress 2.5.0
	 *
	 * @param string $class
	 */
	public function autoload( $class ) {
		$class_parts = explode( '_', strtolower( $class ) );

		// bbwt-rename:autoload-prefix — renamed classes load from files named by the same convention.
		$bb_requested = $class;
		if ( 'bboss' !== $class_parts[0] ) {
			return;
		}

		$components = array(
			'activity',
			'blogs',
			'core',
			'friends',
			'groups',
			'profiletype',
			'xprofile',
			'members',
			'messages',
			'notifications',
			'settings',
			'forums',
			'search',
			'media',
			'document',
			'video',
			'gdpr',
			'suspend',
			'invites',
			'moderation',
		);

		// These classes don't have a name that matches their component.
		$irregular_map = array(
			'BBoss_Presence'                                  => 'core',
			'BBoss_Akismet'                                   => 'activity',
			'BBoss_Admin'                                     => 'core',
			'BBoss_Background_Updater'                        => 'core',
			'BBoss_Email_Background_Updater'                  => 'core',
			'BBoss_Attachment_Avatar'                         => 'core',
			'BBoss_Attachment_Cover_Image'                    => 'core',
			'BBoss_Attachment'                                => 'core',
			'BBoss_Button'                                    => 'core',
			'BBoss_Block'                                     => 'core',
			'BBoss_Component'                                 => 'core',
			'BBoss_Integration'                               => 'core',
			'BBoss_Customizer_Control_Range'                  => 'core',
			'BBoss_Date_Query'                                => 'core',
			'BBoss_Email_Tokens'                              => 'core',
			'BBoss_Email_Delivery'                            => 'core',
			'BBoss_Email_Recipient'                           => 'core',
			'BBoss_Email'                                     => 'core',
			'BBoss_Email_Queue'                               => 'core',
			'BBoss_Embed'                                     => 'core',
			'BBoss_Media_Extractor'                           => 'core',
			'BBoss_Members_Suggestions'                       => 'core',
			'BBoss_PHPMailer'                                 => 'core',
			'BBoss_Recursive_Query'                           => 'core',
			'BBoss_Suggestions'                               => 'core',
			'BBoss_Theme_Compat'                              => 'core',
			'BBoss_User_Query'                                => 'core',
			'BBoss_Walker_Category_Checklist'                 => 'core',
			'BBoss_Walker_Nav_Menu_Checklist'                 => 'core',
			'BBoss_Walker_Nav_Menu'                           => 'core',
			'BBoss_Invitation_Manager'                        => 'core',
			'BBoss_Invitation'                                => 'core',
			'BBoss_Post_Notification'                         => 'core',
			'BBoss_Core_Gdpr'                                 => 'gdpr',
			'BBoss_Activity_Export'                           => 'gdpr',
			'BBoss_Export'                                    => 'gdpr',
			'BBoss_Friendship_Export'                         => 'gdpr',
			'BBoss_Group_Export'                              => 'gdpr',
			'BBoss_Group_Membership_Export'                   => 'gdpr',
			'BBoss_Message_Export'                            => 'gdpr',
			'BBoss_Notification_Export'                       => 'gdpr',
			'BBoss_Settings_Export'                           => 'gdpr',
			'BBoss_Xprofile_Export'                           => 'gdpr',
			'BBoss_Bbp_Gdpr_Forums'                           => 'gdpr',
			'BBoss_Bbp_Gdpr_Replies'                          => 'gdpr',
			'BBoss_Bbp_Gdpr_Topics'                           => 'gdpr',
			'BBoss_Core_Friends_Widget'                       => 'friends',
			'BBoss_Core_Network_Posts_Widget'                 => 'core',
			'BBoss_Core_Follow_Following_Widget'              => 'core',
			'BBoss_Core_Follow_Follower_Widget'               => 'core',
			'BBoss_Group_Extension'                           => 'groups',
			'BBoss_Group_Member_Query'                        => 'groups',
			'BBoss_Group_Readylaunch'                        => 'groups',
			'BBoss_Core_Members_Template'                     => 'members',
			'BBoss_Core_Members_Widget'                       => 'members',
			'BBoss_Core_Recently_Active_Widget'               => 'members',
			'BBoss_Core_Whos_Online_Widget'                   => 'members',
			'BBoss_Registration_Theme_Compat'                 => 'members',
			'BBoss_Signup'                                    => 'members',
			'BBoss_Core_Suspend'                              => 'suspend',
			'BBoss_Suspend_Abstract'                          => 'suspend',
			'BBoss_Suspend_Member'                            => 'suspend',
			'BBoss_Suspend_Activity'                          => 'suspend',
			'BBoss_Suspend_Activity_Comment'                  => 'suspend',
			'BBoss_Suspend_Group'                             => 'suspend',
			'BBoss_Suspend_Forum'                             => 'suspend',
			'BBoss_Suspend_Forum_Topic'                       => 'suspend',
			'BBoss_Suspend_Forum_Reply'                       => 'suspend',
			'BBoss_Suspend_Document'                          => 'suspend',
			'BBoss_Suspend_Folder'                            => 'suspend',
			'BBoss_Suspend_Media'                             => 'suspend',
			'BBoss_Suspend_Album'                             => 'suspend',
			'BBoss_Suspend_Comment'                           => 'suspend',
			'BBoss_Suspend_Message'                           => 'suspend',
			'BBoss_Subscriptions'                             => 'core',
			'BBoss_Bookmarks'                                 => 'core',
			'BBoss_Bookmark_Type'                             => 'core',
			'BBoss_BB_Background_Updater'                        => 'core',
			'BBoss_BG_Process_Log'                            => 'core',
			'BBoss_Telemetry'                                 => 'core',
			'BBoss_Topics_Manager'                            => 'core',
			'BBoss_Activity_Topics_Manager'                   => 'activity',
			'BBoss_Readylaunch'                               => 'core',
			'BBoss_Activity_Readylaunch'                      => 'activity',

			// BuddyBoss Platform Rest API classes.
			'BBoss_REST_Components_Endpoint'                  => 'core',
			'BBoss_BB_REST_Topics_Endpoint'                      => 'core',
			'BBoss_REST_Attachments'                          => 'core',
			'BBoss_REST_Settings_Endpoint'                    => 'core',
			'BBoss_REST_Mention_Endpoint'                     => 'core',
			'BBoss_REST_Members_Endpoint'                     => 'members',
			'BBoss_REST_Members_Permissions_Endpoint'         => 'members',
			'BBoss_REST_Members_Actions_Endpoint'             => 'members',
			'BBoss_REST_Members_Details_Endpoint'             => 'members',
			'BBoss_REST_Attachments_Member_Avatar_Endpoint'   => 'members',
			'BBoss_REST_Attachments_Member_Cover_Endpoint'    => 'members',
			'BBoss_REST_Signup_Endpoint'                      => 'members',
			'BBoss_REST_Media_Endpoint'                       => 'media',
			'BBoss_REST_Media_Albums_Endpoint'                => 'media',
			'BBoss_REST_Media_Details_Endpoint'               => 'media',
			'BP_REST_Video_Endpoint'                       => 'video',
			'BP_REST_Video_Poster_Endpoint'                => 'video',
			'BP_REST_Video_Details_Endpoint'               => 'video',
			'BP_REST_Document_Endpoint'                    => 'document',
			'BP_REST_Document_Folder_Endpoint'             => 'document',
			'BP_REST_Document_Details_Endpoint'            => 'document',
			'BBoss_REST_Activity_Endpoint'                    => 'activity',
			'BBoss_REST_Activity_Comment_Endpoint'            => 'activity',
			'BBoss_REST_Activity_Details_Endpoint'            => 'activity',
			'BBoss_REST_Activity_Link_Preview_Endpoint'       => 'activity',
			'BBoss_REST_Blogs_Endpoint'                       => 'blogs',
			'BBoss_REST_Attachments_Blog_Avatar_Endpoint'     => 'blogs',
			'BBoss_REST_XProfile_Fields_Endpoint'             => 'xprofile',
			'BBoss_REST_XProfile_Field_Groups_Endpoint'       => 'xprofile',
			'BBoss_REST_XProfile_Data_Endpoint'               => 'xprofile',
			'BBoss_REST_XProfile_Update_Endpoint'             => 'xprofile',
			'BBoss_REST_XProfile_Repeater_Endpoint'           => 'xprofile',
			'BBoss_REST_XProfile_Search_Form_Fields_Endpoint' => 'xprofile',
			'BBoss_REST_XProfile_Types_Endpoint'              => 'xprofile',
			'BBoss_REST_Groups_Endpoint'                      => 'groups',
			'BBoss_REST_Groups_Details_Endpoint'              => 'groups',
			'BBoss_REST_Group_Membership_Endpoint'            => 'groups',
			'BBoss_REST_Group_Invites_Endpoint'               => 'groups',
			'BBoss_REST_Group_Membership_Request_Endpoint'    => 'groups',
			'BBoss_REST_Group_Settings_Endpoint'              => 'groups',
			'BBoss_REST_Groups_Types_Endpoint'                => 'groups',
			'BBoss_REST_Attachments_Group_Avatar_Endpoint'    => 'groups',
			'BBoss_REST_Attachments_Group_Cover_Endpoint'     => 'groups',
			'BBoss_REST_Messages_Endpoint'                    => 'messages',
			'BBoss_REST_Group_Messages_Endpoint'              => 'messages',
			'BBoss_REST_Messages_Actions_Endpoint'            => 'messages',
			'BBoss_REST_Notifications_Endpoint'               => 'notifications',
			'BBoss_REST_Friends_Endpoint'                     => 'friends',
			'BBoss_REST_Forums_Endpoint'                      => 'forums',
			'BBoss_REST_Topics_Endpoint'                      => 'forums',
			'BBoss_REST_Topics_Actions_Endpoint'              => 'forums',
			'BBoss_REST_Reply_Endpoint'                       => 'forums',
			'BBoss_REST_Reply_Actions_Endpoint'               => 'forums',
			'BBoss_REST_Forums_Link_Preview_Endpoint'         => 'forums',
			'BBoss_REST_Invites_Endpoint'                     => 'invites',
			'BBoss_REST_Account_Settings_Endpoint'            => 'settings',
			'BBoss_REST_Account_Settings_Options_Endpoint'    => 'settings',
			'BBoss_REST_Moderation_Endpoint'                  => 'moderation',
			'BBoss_REST_Moderation_Report_Endpoint'           => 'moderation',
			'BBoss_REST_Subscriptions_Endpoint'               => 'core',
		);

		$component = null;

		// First check to see if the class is one without a properly namespaced name.
		if ( isset( $irregular_map[ $class ] ) ) {
			$component = $irregular_map[ $class ];

			// Next chunk is usually the component name.
		} elseif ( in_array( $class_parts[1], $components, true ) ) {
			$component = $class_parts[1];
		}

		if ( ! $component ) {
			return;
		}

		// Sanitize class name.
		$class = strtolower( str_replace( '_', '-', $class ) );
		if ( 'bboss-rest-attachments' === $class ) { // bbwt-rename:autoload-trait
			$path = dirname( __FILE__ ) . "/bp-{$component}/classes/trait-bboss-rest-attachments.php";
		} elseif ( 'gdpr' === $component ) {
			$path = dirname( __FILE__ ) . "/bp-core/gdpr/class-{$class}.php";
		} elseif ( 'suspend' === $component ) {
			$path = dirname( __FILE__ ) . "/bp-moderation/classes/suspend/class-{$class}.php";
		} elseif ( 'bboss-phpmailer' === $class ) { // bbwt-rename:autoload-mailer
			// File deliberately named without the "phpmailer.php" suffix so it is not mistaken
			// for the bundled PHPMailer library; the class name BP_PHPMailer is unchanged.
			$path = dirname( __FILE__ ) . "/bp-{$component}/classes/class-bboss-php-mailer.php";
		} else {
			$path = dirname( __FILE__ ) . "/bp-{$component}/classes/class-{$class}.php";
		}

		// Sanity check.
		if ( ! file_exists( $path ) ) {
			return;
		}

		/*
		 * Sanity check 2 - Check if component is active before loading class.
		 * Skip if PHPUnit is running, or BuddyPress is installing for the first time.
		 */
		if (
			! in_array( $component, array( 'core', 'members', 'xprofile', 'gdpr', 'suspend', 'profiletype' ), true ) &&
			! bboss_is_active( $component ) &&
			! function_exists( 'tests_add_filter' )
		) {
			return;
		}

		require $path;

		// @bbwt-com-start compat-layer
		// bbwt-rename:autoload-compat — give the class its old name too (instanceof OldName).
		if ( function_exists( 'bb_compat_alias_class' ) ) {
			bb_compat_alias_class( $bb_requested );
		}
		// @bbwt-com-end compat-layer
	}

	/**
	 * Set up the default hooks and actions.
	 *
	 * @since BuddyPress 1.6.0
	 */
	private function setup_actions() {

		// Add actions to plugin activation and deactivation hooks.
		add_action( 'activate_' . $this->basename, 'bp_activation' );
		add_action( 'deactivate_' . $this->basename, 'bp_deactivation' );

		// If BuddyPress is being deactivated, do not add any actions.
		if ( bboss_is_deactivation( $this->basename ) ) {
			return;
		}

		// Array of BuddyPress core actions.
		$actions = array(
			'setup_theme',              // Setup the default theme compat.
			'setup_current_user',       // Setup currently logged in user.
			'register_post_types',      // Register post types.
			'register_post_statuses',   // Register post statuses.
			'register_taxonomies',      // Register taxonomies.
			'register_views',           // Register the views.
			'register_theme_packages',  // Register bundled theme packages.
			'load_textdomain',          // Load textdomain.
			'add_rewrite_tags',         // Add rewrite tags.
			'generate_rewrite_rules',    // Generate rewrite rules.
		);

		// Add the actions.
		foreach ( $actions as $class_action ) {
			if ( method_exists( $this, $class_action ) ) {
				add_action( 'bp_' . $class_action, array( $this, $class_action ), 5 );
			}
		}

		/**
		 * Fires after the setup of all BuddyPress actions.
		 *
		 * Includes bbp-core-hooks.php.
		 *
		 * @since BuddyPress 1.7.0
		 *
		 * @param BBoss_Platform $this. Current BuddyPress instance. Passed by reference.
		 */
		do_action_ref_array( 'bp_after_setup_actions', array( &$this ) );
	}

	/**
	 * Private method to align the active and database versions.
	 *
	 * @since BuddyPress 1.7.0
	 */
	private function versions() {

		// Get the possible DB versions (boy is this gross).
		$versions               = array();
		$versions['1.6-single'] = get_blog_option( $this->root_blog_id, '_bp_db_version' );

		// 1.6-single exists, so trust it
		if ( ! empty( $versions['1.6-single'] ) ) {
			$this->db_version_raw = (int) $versions['1.6-single'];

			// If no 1.6-single exists, use the max of the others.
		} else {
			$versions['1.2']        = get_site_option( 'bp-core-db-version' );
			$versions['1.5-multi']  = get_site_option( 'bp-db-version' );
			$versions['1.6-multi']  = get_site_option( '_bp_db_version' );
			$versions['1.5-single'] = get_blog_option( $this->root_blog_id, 'bp-db-version' );

			// Remove empty array items.
			$versions             = array_filter( $versions );
			$this->db_version_raw = (int) ( ! empty( $versions ) ) ? (int) max( $versions ) : 0;
		}
	}

	/** Public Methods ********************************************************/

	/**
	 * Register bundled theme packages.
	 *
	 * @since BuddyPress 1.7.0
	 */
	public function register_theme_packages() {

		// Register the default theme compatibility package.
		bboss_register_theme_package(
			array(
				'id'      => 'nouveau',
				'name'    => 'BuddyPress Nouveau',
				'version' => bboss_get_version(),
				'dir'     => trailingslashit( $this->themes_dir . '/bp-nouveau' ),
				'url'     => trailingslashit( $this->themes_url . '/bp-nouveau' ),
			)
		);

		// Register the basic theme stack. This is really dope.
		bboss_register_template_stack( 'get_stylesheet_directory', 10 );
		bboss_register_template_stack( 'get_template_directory', 12 );
		bboss_register_template_stack( 'bboss_get_theme_compat_dir', 14 );
	}

	/**
	 * Set up the default BuddyPress theme compatibility location.
	 *
	 * @since BuddyPress 1.7.0
	 */
	public function setup_theme() {

		// Bail if something already has this under control.
		if ( ! empty( $this->theme_compat->theme ) ) {
			return;
		}

		// Setup the theme package to use for compatibility.
		bboss_setup_theme_compat( bboss_get_theme_package_id() );
	}
}
