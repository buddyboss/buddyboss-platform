<?php
/**
 * BuddyBoss In-Plugin Notifications.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss/Core
 */

use \BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use \BuddyBossPlatform\GroundLevel\Container\Container;
use \BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
use \BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use \BuddyBossPlatform\GroundLevel\Mothership\Service as MoshService;
use \BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use \BuddyBossPlatform\GroundLevel\Support\Models\Hook;

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

//				self::init_mothership();
				self::init_ipn();
			}
		}

		/**
		 * Initializes and configures the IPN Service.
		 */
		private static function init_ipn(): void {
			// Set IPN Service parameters.
			self::$container->addParameter( IPNService::PRODUCT_SLUG, 'buddyboss-platform' );
			self::$container->addParameter( IPNService::PREFIX, 'bp' );
			self::$container->addParameter( IPNService::MENU_SLUG, 'buddyboss-platform' );
			self::$container->addParameter(
				IPNService::USER_CAPABILITY,
				apply_filters('bb-admin-capability', 'remove_users')
			);
			self::$container->addParameter(
				IPNService::RENDER_HOOK,
				'mepr_admin_header_actions'
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
	}
}
