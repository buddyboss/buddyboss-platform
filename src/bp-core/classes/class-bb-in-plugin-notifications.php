<?php
/**
 * BuddyBoss In-Plugin Notifications.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss/Core
 */

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MoshService;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_In_Plugin_Notifications' ) ) {

	/**
	 * BB_In_Plugin_Notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * Class for logging in-plugin notifications.
	 * Includes:
	 *     Notifications from our remote feed
	 *     Plugin-related notifications (i.e. - recent sales performances)
	 */
	class BB_In_Plugin_Notifications extends BB_Base_Ctrl implements StaticContainerAwareness {

		use HasStaticContainer;
		use Hookable;

		/**
		 * Instance.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bool
		 */
		private static $instance = null;

		/**
		 * Source URL.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public $source_url = '';

		/**
		 * Source URL Args.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public $source_url_args = array();

		/**
		 * Option value.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bool|array
		 */
		public $option = false;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return null|BB_In_Plugin_Notifications|Controller|object
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * BB_In_Plugin_Notifications constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->source_url      = defined( 'BB_TEST_IN_PLUGIN_NOTIFICATION_URL' ) ? BB_TEST_IN_PLUGIN_NOTIFICATION_URL : 'https://buddyboss.com/inpns/notifications.json';
			$this->source_url_args = array(
				'sslverify' => false,
			);

			add_action( 'in_admin_header', array( $this, 'bb_admin_notification_header' ), 0 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'bb_admin_notification_enqueues' ) );
		}

		/**
		 * Returns an array of Hooks that should be added by the class.
		 *
		 * @return array
		 */
		protected function configureHooks(): array {
			return array(
				new Hook( Hook::TYPE_ACTION, 'init', __CLASS__ . '::init', 5 ),
			);
		}

		/**
		 * Loads the hooks for the controller.
		 */
		public function load_hooks() {
			$this->addHooks();
		}

		/**
		 * Initializes a GroundLevel container and dependent services.
		 *
		 * @param boolean $force_init If true, forces notifications to load even if notifications are disabled.
		 *                            Used during database migrations when we cannot determine if the current
		 *                            user has access to notifications.
		 */
		public static function init( bool $force_init = false ): void {
			/**
			 * Currently we're loading a container, mothership, and ipn services in order
			 * to power IPN functionality. We don't need the container or mothership
			 * for anything other than IPN so we can skip the whole load if notifications
			 * are disabled or unavailable for the user.
			 *
			 * Later we'll want to move this condition to be only around the {@see self::init_ipn()}
			 * load method.
			 */
			if ( bp_current_user_can( 'bp_moderate' ) || $force_init ) {
				self::setContainer( new Container() );

				/**
				 * @todo: Later we'll want to "properly" bootstrap a container via a
				 * plugin bootstrap via GrdLvl package.
				 */

				// self::init_mothership();
				self::init_ipn();
			}
		}

		/**
		 * Initializes and configures the IPN Service.
		 */
		private static function init_ipn(): void {
			// Set IPN Service parameters.
			self::$container->addParameter( IPNService::PRODUCT_SLUG, 'buddyboss-platform' );
			self::$container->addParameter( IPNService::PREFIX, 'bbglvl' );
			self::$container->addParameter( IPNService::MENU_SLUG, 'buddyboss-platform' );
			self::$container->addParameter(
				IPNService::USER_CAPABILITY,
				apply_filters( 'bb_admin_capability', 'remove_users' )
			);
			self::$container->addParameter(
				IPNService::RENDER_HOOK,
				'bb_admin_header_actions'
			);
			self::$container->addParameter(
				IPNService::THEME,
				array(
					'primaryColor'       => '#2271b1',
					'primaryColorDarker' => '#0a4b78',
				)
			);

			self::$container->addService(
				IPNService::class,
				static function ( Container $container ): IPNService {
					return new IPNService( $container );
				},
				true
			);
		}

		/**
		 * Initializes the Mothership Service.
		 */
		private static function init_mothership(): void {
			self::$container->addService(
				MoshService::class,
				static function ( Container $container ): MoshService {
					return new MoshService(
						$container,
						new MeprMothershipPluginConnector()
					);
				},
				true
			);
		}

		/**
		 * Load the admin notification header template.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_admin_notification_header() {
			global $bp;
			// If a user is not on a relevant screen, don't show the notice.
			$current_screen = get_current_screen();

			if (
				! $current_screen ||
				(
					false === strpos( $current_screen->base, 'buddyboss' ) &&
					false === strpos( $current_screen->id, 'edit-bpm_category' ) &&
					false === strpos( $current_screen->id, 'buddyboss_fonts' ) &&
					! in_array( $current_screen->post_type, array( 'forum', 'topic', 'reply' ), true )
				)
			) {
				unset( $current_screen );

				return;
			}

			include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/bb-in-plugin-notifications.php';
		}

		/**
		 * Admin area enqueues.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_notification_enqueues() {

			wp_enqueue_style(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/css/bb-admin-notifications.css',
				array( 'bp-admin-common-css' ),
				bp_get_version()
			);

			wp_enqueue_script(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/js/bb-admin-notifications.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-in-plugin-admin-notifications',
				'BBInPluginAdminNotifications',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bb-in-plugin-admin-notifications' ),
				)
			);
		}
	}
}
