<?php

use BuddyBossPlatform\GroundLevel\Container\Concerns\HasStaticContainer;
use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Container\Contracts\StaticContainerAwareness;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Service as IPNService;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store;
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


			// add header actions for bb_admin_notification_header function of BB_Grd_Lvl_Ctrl.
			add_action( 'in_admin_header', array( 'BB_Grd_Lvl_Ctrl','bb_admin_notification_header' ), 0 );
			// add enqueues for bb_admin_notification_enqueues function of BB_Grd_Lvl_Ctrl.
			add_action( 'bp_admin_enqueue_scripts', array( 'BB_Grd_Lvl_Ctrl','bb_admin_notification_enqueues' ) );
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

	/**
	 * Add an event notification. This is NOT for feed notifications.
	 * Event notifications are for alerting the user to something internally (e.g. recent sales performances).
	 *
	 * @param array $notification Notification data.
	 *
	 * use below variable array to register the custom notification.
	 * $notification = array(
	 *     'id'      => 'bb-platform-free-notice',
	 *     'type'    => 'notice',
	 *     'title'   => 'BuddyBoss Platform Free',
	 *     'content' => 'Get the BuddyBoss Platform Pro to unlock more features.',
	 *     'buttons' => array(
	 *         'main'    => array(
	 *             'text'   => 'Upgrade Now',
	 *             'url'    => 'BTN_URL,
	 *             'target' => '_blank',
	 *         ),
	 *         'dismiss' => array(
	 *             'text' => 'Dismiss',
	 *             'url'  => '#notification-dismiss',
	 *         ),
	 *    ),
	 *     'icon'    => 'IMAGE_ICON_URL',
	 * );
	 */
	public function add( $notification ) {
		if ( empty( $notification['id'] ) ) {
			return;
		}

		MeprGrdLvlCtrl::init( true );

		/** @var Store $store */ // phpcs:ignore
		$store = self::getContainer()->get( Store::class )->fetch();

		$btns = array();
		foreach ( $notification['buttons'] as $type => $data ) {
			$btns[] = sprintf(
				'<a class="btn btn--%1$s" href="%2$s" target="%3$s" rel="noopener">%4$s</a>',
				'main' === $type ? 'primary' : 'link',
				esc_url( $data['url'] ),
				esc_attr( $data['target'] ?? '_blank' ),
				esc_html( $data['text'] )
			);
		}
		$store->add(
			array(
				'id'           => $notification['type'] . '_' . $notification['id'],
				'subject'      => $notification['title'],
				'content'      => $notification['content'] . '<p>' . implode( ' ', $btns ) . '</p>',
				'publishes_at' => date( 'Y-m-d H:i:s', $notification['saved'] ?? time() ),
				'icon'         => sprintf(
					'<img alt="%1$s" src="%2$s" style="width: 100%%; height: auto;">',
					esc_attr__( 'Notification Icon', 'memberpress' ),
					$notification['icon'] ?? MEPR_IMAGES_URL . '/alert-icon.png'
				),
			)
		)->persist();
	}

	/**
	 * Load the admin notification header template.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public static function bb_admin_notification_header() {
		global $bp;
		// If a user is not on a relevant screen, don't show the notice.
		$current_screen = get_current_screen();

		if (
			! $current_screen ||
			false !== strpos( $current_screen->base, 'buddyboss-app' ) ||
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
	public static function bb_admin_notification_enqueues() {

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
