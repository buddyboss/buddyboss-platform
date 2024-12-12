<?php

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\Mothership\Service as MoshService;
use BuddyBossPlatform\GroundLevel\Support\Concerns\Hookable;
use BuddyBossPlatform\GroundLevel\Support\Models\Hook;

/**
 * Initializes a GroundLevel container and dependent services.
 */
class BB_Grd_Lvl_Ctrl implements StaticContainerAwareness
{
	use HasStaticContainer;
	use Hookable;

	public function __construct() {
		// This is to ensure that the load_hooks method is
		// only ever loaded once across all instansiations.
		static $loaded;

		if ( ! isset( $loaded ) ) {
			$loaded = array();
		}

		$class_name = get_class( $this );

		if ( ! isset( $loaded[ $class_name ] ) ) {
			$this->load_hooks();
			$loaded[ $class_name ] = true;
		}
	}

	/**
	 * Returns an array of Hooks that should be added by the class.
	 *
	 * @return array
	 */
	protected function configureHooks(): array
	{
		return [
			new Hook(Hook::TYPE_ACTION, 'init', __CLASS__ . '::init', 5),
		];
	}

	/**
	 * Loads the hooks for the controller.
	 */
	public function load_hooks()
	{
		$this->addHooks();
	}

	/**
	 * Initializes a GroundLevel container and dependent services.
	 *
	 * @param boolean $force_init If true, forces notifications to load even if notifications are disabled.
	 *                            Used during database migrations when we cannot determine if the current
	 *                            user has access to notifications.
	 */
	public static function init(bool $force_init = false): void
	{
		/**
		 * Currently we're loading a container, mothership, and ipn services in order
		 * to power IPN functionality. We don't need the container or mothership
		 * for anything other than IPN so we can skip the whole load if notifications
		 * are disabled or unavailable for the user.
		 *
		 * Later we'll want to move this condition to be only around the {@see self::init_ipn()}
		 * load method.
		 */
		if (bp_current_user_can( 'manage_options' ) || $force_init) {
			self::setContainer(new Container());

			self::init_mothership();
			self::init_ipn();
		}
	}

	/**
	 * Initializes and configures the IPN Service.
	 */
	private static function init_ipn(): void
	{
		// Set IPN Service parameters.
		self::$container->addParameter(IPNService::PRODUCT_SLUG, 'bb-platform-free');
		self::$container->addParameter(IPNService::PREFIX, 'buddyboss');
		self::$container->addParameter(IPNService::MENU_SLUG, 'buddyboss-platform');
		self::$container->addParameter(
			IPNService::USER_CAPABILITY,
			'remove_users'
		);
		self::$container->addParameter(
			IPNService::RENDER_HOOK,
			'bb_admin_header_actions'
		);
		self::$container->addParameter(
			IPNService::THEME,
			[
				'primaryColor'       => '#2271b1',
				'primaryColorDarker' => '#0a4b78',
			]
		);

		self::$container->addService(
			IPNService::class,
			static function (Container $container): IPNService {
				return new IPNService($container);
			},
			true
		);
	}

	/**
	 * Initializes the Mothership Service.
	 */
	private static function init_mothership(): void
	{
		self::$container->addService(
			MoshService::class,
			static function (Container $container): MoshService {
				return new MoshService(
					$container,
					new BB_Mothership_Plugin_Connector()
				);
			},
			true
		);
	}
}
