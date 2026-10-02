<?php
/**
 * BuddyBoss Platform - Mothership Loader
 *
 * Main loader class for BuddyBoss Mothership functionality.
 * Handles initialization of licensing, In-Product Notifications and Insights (NPS) services.
 *
 * @package BuddyBoss\Core\Admin\Mothership
 * @since   BuddyBoss 2.14.0
 */

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Container\Container;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\LicenseActivations;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Response;
use BuddyBossPlatform\GroundLevel\Mothership\Credentials;
use BuddyBossPlatform\GroundLevel\Mothership\Util;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;
use BuddyBossPlatform\GroundLevel\Mothership\LegacyUpdateService;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\AddonsManager;
use BuddyBossPlatform\GroundLevel\Mothership\Manager\LicenseManager;
use BuddyBossPlatform\GroundLevel\Support\View;
use BuddyBossPlatform\GroundLevel\Mothership\MothershipServiceProvider;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\InProductNotifications\IPNServiceProvider;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store as IPNStore;
use BuddyBossPlatform\GroundLevel\Insights\InsightsServiceProvider;
use BuddyBossPlatform\GroundLevel\Insights\Services\NetPromoterScore;

/**
 * Main loader class for BuddyBoss Mothership functionality.
 *
 * This class follows the GroundLevel framework patterns for service registration,
 * container awareness, and hook configuration.
 */
class BB_Mothership_Loader {

	/**
	 * Site-transient key caching the Platform's own update-check payload used by
	 * {@see self::inject_platform_update()}.
	 *
	 * Stored as a site transient so it is shared network-wide (the `update_plugins`
	 * transient it feeds is itself a site transient). Invalidated on a genuine WordPress
	 * update fetch and on any license change — see {@see self::setup_hooks()}.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var string
	 */
	private const UPDATE_CACHE_KEY = 'bb_platform_update_check';

	/**
	 * TTL, in seconds, for the Platform update-check cache. A long ceiling is safe because
	 * the cache is event-invalidated (update fetch + license change); the TTL is only a
	 * backstop for sites where neither event fires.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var int
	 */
	private const UPDATE_CACHE_TTL = 12 * HOUR_IN_SECONDS;

	/**
	 * TTL, in seconds, for a failed Platform update check.
	 *
	 * A failed check (outage, 429, 401) is cached briefly so every read of the
	 * `update_plugins` transient does not make its own blocking HTTP request. Matches the
	 * vendor's own error TTL for the add-ons list.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var int
	 */
	private const UPDATE_ERROR_CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/**
	 * Fixed prefix for the GroundLevel Insights (NPS survey) service.
	 *
	 * Deliberately NOT derived from the dynamic plugin ID, unlike the IPN prefix. The package
	 * builds the survey-state option, the daily cron hook, the opt-out filter and the script
	 * handle from this prefix; keying those by license edition would restart the 14-day grace
	 * period (and re-survey the admin) on every license activation or tier change and leave
	 * one orphaned daily cron per previous edition. MemberPress uses the same fixed-prefix
	 * model (`mepr_insights_`). The Mothership product slug stays dynamic — it comes from the
	 * IPN product-slug parameter, not from this prefix.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public const INSIGHTS_PREFIX = 'buddyboss_insights_';

	/**
	 * Daily cron hook the Insights package schedules to decide whether to file the survey.
	 *
	 * Equals `Str::toSnakeCase( self::INSIGHTS_PREFIX . 'nps_check' )`.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public const INSIGHTS_NPS_CRON_HOOK = 'buddyboss_insights_nps_check';

	/**
	 * Filter the Insights package applies to its "should the survey be shown" decision.
	 *
	 * Stable across license editions, so sites and white-label integrations can opt out with
	 * `add_filter( 'buddyboss_insights_should_show_nps_notification', '__return_false' )`.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public const INSIGHTS_SHOULD_SHOW_FILTER = 'buddyboss_insights_should_show_nps_notification';

	/**
	 * REST namespace for the survey submission endpoint (`{namespace}/nps/submit`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	public const INSIGHTS_REST_NAMESPACE = 'buddyboss/insights';

	/**
	 * Singleton instance.
	 *
	 * @var BB_Mothership_Loader|null
	 */
	private static $instance = null;

	/**
	 * Container for dependency injection.
	 *
	 * @var Container
	 */
	private $container;

	/**
	 * Plugin connector instance.
	 *
	 * @var \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector
	 */
	private $pluginConnector; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase

	/**
	 * Whether the Insights (NPS) provider was registered with the container.
	 *
	 * False when the package is absent from the vendor tree; the Insights-specific hooks in
	 * {@see self::setup_hooks()} are only attached when this is true.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var bool
	 */
	private $insights_registered = false;

	/**
	 * Get singleton instance.
	 *
	 * @return BB_Mothership_Loader
	 */
	public static function instance(): BB_Mothership_Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor (private to enforce singleton).
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the mothership functionality.
	 */
	private function init(): void {
		// The vendor tree is gitignored and is NOT refreshed by a branch switch, so a
		// checkout without `composer install` leaves GroundLevel 2.2.1 (or older) on disk
		// while this code targets 9.1.2. Everything below — Container::singleton(),
		// Container::parameters(), the ServiceProvider classes — is 9.x-only, and this file
		// is required unconditionally from BuddyPress::includes(), so a mismatch fatals on
		// the FRONT END with no wp-admin recovery path. Degrade to "licensing unavailable"
		// instead.
		if ( ! class_exists( MothershipServiceProvider::class ) || ! method_exists( Container::class, 'singleton' ) ) {
			$message = 'BuddyBoss: the GroundLevel vendor tree is out of date (run `composer install`) — Mothership licensing is disabled for this request.';
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( $message, true );
			} else {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return;
		}

		// In network mode the license lives in site options. Copy the main site's license up
		// once, before the connector reads its plugin ID, so an install that was already
		// network-activated keeps its activation.
		BB_Plugin_Connector::maybe_move_license_to_network();

		// Keep direct get_option() readers of the license rows (older add-ons, custom code)
		// seeing the network license.
		BB_Plugin_Connector::register_legacy_option_bridge();

		try {
			// Create the container.
			$this->container = new Container();

			// Create the plugin connector.
			$this->pluginConnector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Register the BuddyBoss plugin connection so that every GroundLevel service
			// (Credentials, View, AdminNotices, LicenseManager, ...) can resolve it.
			$plugin_connector  = $this->pluginConnector; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$connector_factory = static function () use ( $plugin_connector ) {
				return $plugin_connector;
			};
			$this->container->singleton( AbstractPluginConnection::class, $connector_factory );

			// Container IDs are plain strings, so the alias name above only matches what the
			// vendor services ask for in a php-scoper build. In a non-scoped (dev) checkout
			// the vendor resolves the un-prefixed `GroundLevel\...\AbstractPluginConnection`,
			// which ReflectionClass yields for the alias; register the same factory under
			// that name too, or auto-wiring tries to instantiate the abstract class and every
			// provider fails to boot.
			$real_connection_class = ( new \ReflectionClass( AbstractPluginConnection::class ) )->getName();
			if ( AbstractPluginConnection::class !== $real_connection_class ) {
				$this->container->singleton( $real_connection_class, $connector_factory );
			}
		} catch ( \Throwable $e ) {
			$message = 'BuddyBoss Mothership container setup failed: ' . $e->getMessage();
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( $message, true );
			} else {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return;
		}

		// Register and boot the Mothership + In-Product Notifications + Insights service providers.
		$this->register_services();

		// Set up hooks.
		$this->setup_hooks();
	}

	/**
	 * Register and boot the GroundLevel service providers.
	 *
	 * GroundLevel 9.1.2 uses the dependency-injection `ServiceProvider` pattern. Booting
	 * the providers wires the vendor hooks (twice-daily license-status cron, add-on AJAX,
	 * add-on update injection, the In-Product Notifications UI and the Insights NPS
	 * survey). None of these
	 * duplicate BuddyBoss's own hooks — BuddyBoss wires its own license controller, admin
	 * pages and the Platform's own update entry separately in {@see self::setup_hooks()}.
	 *
	 * The providers are always registered, so services still resolve from the container on
	 * every request, but they are only booted where their hooks do something — see
	 * {@see self::should_boot_services()}.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	private function register_services(): void {
		$plugin_id = $this->pluginConnector->getDynamicPluginId(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// IPN parameter overrides MUST be set before provider() — ServiceProvider::register()
		// only registers a default when the container does not already have the key.
		$this->container->parameters(
			array(
				IPNServiceProvider::PARAM_PRODUCT_SLUG => $plugin_id,
				IPNServiceProvider::PARAM_PREFIX       => sanitize_title( $plugin_id ),
				IPNServiceProvider::PARAM_MENU_SLUG    => 'buddyboss-platform',
				IPNServiceProvider::PARAM_RENDER_HOOK  => 'bb_admin_header_actions',
				IPNServiceProvider::PARAM_THEME        => array(
					'primaryColor'       => '#2f2f2f',
					'primaryColorDarker' => '#0a4b78',
					'inboxBtnIcon'       => 'bell',
					'inboxBtnVariant'    => 'icon',
					'inboxBtnSize'       => '1.8rem',
				),
			)
		);

		// Must precede boot(): the vendor LicenseManager schedules its status cron in its
		// constructor.
		$this->limit_license_cron_to_main_site( $plugin_id );

		try {
			// MothershipServiceProvider is auto-registered as an IPN dependency; register
			// it explicitly so intent and ordering are obvious.
			$this->container->provider( MothershipServiceProvider::class );
			$this->container->provider( IPNServiceProvider::class );
			$this->register_insights_provider();

			if ( ! $this->should_boot_services() ) {
				return;
			}

			// Boot registers the vendor WordPress hooks.
			//
			// The Platform is deliberately NOT registered with the vendor UpdateService
			// (`UpdateService::plugin( 'buddyboss-platform', ... )`). BuddyBoss Platform ships
			// without an `Update URI` header, so its `update_plugins_{host}` filter would never
			// fire, while its `plugins_api` handler would replace BuddyBoss's own "View details"
			// modal and its `auto_update_plugin` filter would force background updates over the
			// admin's per-plugin choice. Updates are served by {@see self::inject_platform_update()}.
			$this->container->boot();

			$this->disable_overwrite_guard_for_per_site_licenses();
		} catch ( \Throwable $e ) {
			// A resolution/boot failure must never white-screen wp-admin. Log and degrade
			// gracefully — license activation falls back to BuddyBoss's own controller.
			// bb_error_log() may not be loaded this early in the boot sequence, so guard it.
			$message = 'BuddyBoss Mothership bootstrap failed: ' . $e->getMessage();
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( $message, true );
			} else {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Register the GroundLevel Insights provider — the in-product NPS survey.
	 *
	 * Mirrors MemberPress/MemberCore: the package files a "How are we doing?" notification in
	 * the IPN inbox 14 days after install and every 90 days after the last survey event, and
	 * posts the score/feedback to Mothership at `products/{product_slug}/insights/nps`. It
	 * therefore depends on IPN (inbox store + view) and Mothership (API request), so it is
	 * registered after both and before {@see Container::boot()} wires the provider hooks.
	 *
	 * Guarded on the provider class (a Platform addition) so a build that drops the package
	 * from composer degrades to "no survey" instead of handing the container a missing class.
	 *
	 * The prefix is the fixed {@see self::INSIGHTS_PREFIX}. The package snake-cases it for the
	 * survey-state option `buddyboss_insights_nps_data`, the daily cron hook
	 * {@see self::INSIGHTS_NPS_CRON_HOOK} and the opt-out filter
	 * {@see self::INSIGHTS_SHOULD_SHOW_FILTER}, and kebab-cases it for the script handle
	 * (`buddyboss-insights-insights`). The survey notification row itself is filed in the IPN
	 * inbox store, which IS keyed by license edition (`{plugin_id}_ipn_store`), so a pending
	 * survey disappears with an edition switch while the timing state carries over — the next
	 * survey then arrives at the normal recurrence rather than after a fresh 14-day grace.
	 * The product slug, capability and inbox come from the IPN parameters set in
	 * {@see self::register_services()}.
	 *
	 * Note the "14 days after install" grace runs from the first time the package's cron fires
	 * on the site, i.e. from the upgrade to the release that ships this, not from the original
	 * plugin install.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private function register_insights_provider(): void {
		if ( ! class_exists( InsightsServiceProvider::class ) ) {
			return;
		}

		// Parameter overrides MUST be set before provider() — see register_services().
		$this->container->parameters(
			array(
				InsightsServiceProvider::PARAM_PRODUCT_NAME => 'BuddyBoss',
				InsightsServiceProvider::PARAM_PREFIX => self::INSIGHTS_PREFIX,
				InsightsServiceProvider::PARAM_REST_NAMESPACE => self::INSIGHTS_REST_NAMESPACE,
			)
		);

		$this->container->provider( InsightsServiceProvider::class );
		$this->insights_registered = true;
	}

	/**
	 * Build an IPN-prefixed identifier the way the package's `Util::prefixId()` does.
	 *
	 * Reads the `ipn.prefix` container PARAMETER rather than resolving the `Util` service:
	 * parameter keys are plain strings that are identical in a php-scoper build and in a
	 * non-scoped (dev) checkout, whereas a class-name service key only matches in one of the
	 * two, so a `has( Util::class )` guard silently fails in dev.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $id Identifier to prefix, e.g. `store`, `clean`, `remote_fetch`.
	 * @return string The prefixed id (e.g. `bb-web-plus_ipn_store`), or '' if IPN is not registered.
	 */
	private function get_ipn_prefixed_id( string $id ): string {
		if ( ! $this->container || ! $this->container->has( IPNServiceProvider::PARAM_PREFIX ) ) {
			return '';
		}

		$prefix = (string) $this->container->get( IPNServiceProvider::PARAM_PREFIX );
		$sep    = substr( $prefix, -1 );
		if ( ! in_array( $sep, array( '_', '-' ), true ) ) {
			$sep     = '_';
			$prefix .= $sep;
		}

		return $prefix . 'ipn' . $sep . $id;
	}

	/**
	 * Resolve the IPN Store singleton under whichever id the container actually holds.
	 *
	 * In a php-scoper build the vendor registers the prefixed class name; in a non-scoped
	 * checkout it registers the un-prefixed one. Resolving by exact string keeps both working.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return object|null The Store service, or null when IPN is not registered.
	 */
	private function get_ipn_store() {
		foreach ( array( IPNStore::class, 'GroundLevel\InProductNotifications\Services\Store' ) as $id ) {
			if ( $this->container->has( $id ) ) {
				return $this->container->get( $id );
			}
		}

		return null;
	}

	/**
	 * Sort the IPN store option so the inbox lists the newest notification first.
	 *
	 * Bound to `option_{prefix}_ipn_store`. Notification rows are ordered by publish date
	 * descending; the package's `__lastId` pagination cursor (and any other scalar entry) is
	 * kept and re-appended untouched. Mothership rows carry ISO-8601 dates with an offset and
	 * the NPS row carries `Y-m-d H:i:s` in PHP's default (UTC) timezone — strtotime() handles
	 * both, so no format normalization is needed.
	 *
	 * Every store consumer addresses rows by id, so persisting the re-sorted order (which the
	 * vendor Store does on its next write) has no functional effect.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param mixed $value The raw option value.
	 * @return mixed The sorted store, or the original value if it is not a multi-row array.
	 */
	public function sort_ipn_store_newest_first( $value ) {
		if ( ! is_array( $value ) || count( $value ) < 2 ) {
			return $value;
		}

		$rows   = array_filter( $value, 'is_array' );
		$others = array_diff_key( $value, $rows );

		uasort(
			$rows,
			static function ( array $a, array $b ): int {
				return (int) strtotime( $b['publishesAt'] ?? $b['publishes_at'] ?? '' ) <=> (int) strtotime( $a['publishesAt'] ?? $a['publishes_at'] ?? '' );
			}
		);

		return $rows + $others;
	}

	/**
	 * Delete an expired NPS survey row before the package decides whether to file a new one.
	 *
	 * Bound to {@see self::INSIGHTS_NPS_CRON_HOOK} at priority 5, ahead of the vendor's
	 * `NetPromoterScore::maybeAddNotification()` at 10. That method returns early while the
	 * store still holds a `nps_survey` row, and the IPN `Cleaner` can never remove an expired
	 * row because it iterates `Store::notifications()`, which already filters expired rows out
	 * before the `isExpired()` check. Without this, a survey that was ignored past its 45-day
	 * expiry (or scored but not completed) blocks every future survey, defeating the 90-day
	 * recurrence. Only the expired survey row is touched; unexpired and read rows are left to
	 * the vendor.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function purge_expired_nps_survey(): void {
		try {
			$store = $this->get_ipn_store();
			if ( ! $store ) {
				return;
			}

			$survey = $store->fetch( true )->get( NetPromoterScore::NOTIFICATION_ID );
			if ( $survey && $survey->isExpired() ) {
				$store->delete( NetPromoterScore::NOTIFICATION_ID )->persist();
			}
		} catch ( \Throwable $e ) {
			$message = 'BuddyBoss NPS survey cleanup failed: ' . $e->getMessage();
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( $message, true );
			} else {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Clear the daily event the Insights package schedules for the NPS survey.
	 *
	 * Bound to `bp_deactivation`. The package schedules on every `init` but never unschedules,
	 * so without this {@see self::INSIGHTS_NPS_CRON_HOOK} keeps firing as a no-op after
	 * deactivation.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function clear_scheduled_events(): void {
		wp_clear_scheduled_hook( self::INSIGHTS_NPS_CRON_HOOK );
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setup_hooks(): void {
		if ( is_admin() ) {
			// Register admin pages. When network-activated the network owns the license, so the
			// pages live in Network Admin only and are absent from every subsite dashboard.
			add_action( BB_Plugin_Connector::is_network_mode() ? 'network_admin_menu' : 'admin_menu', array( $this, 'register_admin_pages' ), 99 );

			// Register license controller using BuddyBoss custom manager.
			add_action( 'admin_init', array( \BuddyBoss\Core\Admin\Mothership\BB_License_Manager::class, 'controller' ), 20 );

			// Register AJAX handlers.
			add_action( 'wp_ajax_bb_get_free_license', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_get_free_license' ) );
			add_action( 'wp_ajax_bb_reset_license_settings', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_reset_license_settings' ) );
		}

		// Plugin updates. BuddyBoss Platform ships without an `Update URI` header, so WordPress
		// never fires `update_plugins_{host}` for it and this injector is the only update path.
		// Like the vendor update hooks, the injectors only run where updates are read — never
		// on anonymous front-end page loads, where a cache miss would block the page on a
		// remote version check.
		if ( $this->should_boot_services() ) {
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_platform_update' ) );

			// Add-on (and add-on theme) updates for Network Admin when only another site is licensed.
			add_filter( 'site_transient_update_plugins', array( $this, 'inject_addon_updates_via_licensed_site' ) );
			add_filter( 'site_transient_update_themes', array( $this, 'inject_addon_updates_via_licensed_site' ) );
		}

		// Tell the network admin when a subsite license could not be carried over to the network.
		if ( is_admin() && BB_Plugin_Connector::is_network_mode() && get_site_option( BB_Plugin_Connector::NETWORK_MOVE_SKIPPED_OPTION, false ) ) {
			add_action( 'network_admin_notices', array( $this, 'render_network_move_skipped_notice' ) );
		}

		// Invalidate the Platform update-check cache whenever WordPress writes a fresh
		// `update_plugins` transient. That set only happens after a genuine update fetch (cron,
		// "Check again", or a completed install), so clearing here guarantees the next injector
		// run re-derives the payload from a fresh version check rather than a stale 12h cache.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'flush_platform_update_cache' ) );

		// Links hard-coded to the License/Add-ons page in the other admin (e.g. `admin_url()` on a
		// network-activated install) would otherwise hit "Sorry, you are not allowed".
		add_action( 'admin_page_access_denied', array( $this, 'redirect_misrouted_license_pages' ) );

		// Hand the network license back to the main site when Platform is network-deactivated.
		add_action( 'deactivated_plugin', array( $this, 'handle_network_deactivation' ), 10, 2 );

		$plugin_id = $this->pluginConnector->getDynamicPluginId(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Show the IPN inbox newest-first. The GroundLevel Store renders notifications in raw
		// option order (Mothership delivers oldest-first and the NPS survey is appended last)
		// and neither Store::notifications() nor the React inbox sorts, so the survey always
		// sits at the bottom. The Store reads through get_option(), so sorting the option on
		// read reorders what the inbox receives without touching vendor code. The option name
		// is rebuilt from the IPN prefix parameter so it tracks the license edition in both
		// scoped and non-scoped builds — see get_ipn_prefixed_id().
		$ipn_store_option = $this->get_ipn_prefixed_id( 'store' );
		if ( '' !== $ipn_store_option ) {
			add_filter( 'option_' . $ipn_store_option, array( $this, 'sort_ipn_store_newest_first' ) );
		}

		if ( $this->insights_registered ) {
			// Free the survey slot when the previous survey expired un-completed (vendor Cleaner
			// never deletes expired rows), ahead of the vendor's decision at priority 10.
			add_action( self::INSIGHTS_NPS_CRON_HOOK, array( $this, 'purge_expired_nps_survey' ), 5 );

			// Clear the survey cron when BuddyBoss is deactivated.
			add_action( 'bp_deactivation', array( $this, 'clear_scheduled_events' ) );
		}

		// Invalidate the update-check cache on any license change. These fire from
		// BB_Plugin_Connector::setLicenseActivationStatus()/storeLicenseKey() (and BuddyBoss's
		// own license manager), covering activate, validate, deactivate and key entry regardless
		// of which code path triggered it. Hooking the option writes keeps this decoupled from the
		// vendor's event names. NOTE: BuddyBoss overrides the activation-status option name to
		// `_license_activation_status` (not the GroundLevel base default `_license_active`), so the
		// hook MUST bind to the overridden name or it would never fire — see BB_Plugin_Connector.
		$license_active_option = $plugin_id . '_license_activation_status';
		$license_key_option    = $plugin_id . '_license_key';
		// Both scopes are hooked: in network mode the connector writes site options, which fire
		// the `*_site_option_*` actions instead.
		foreach ( array( $license_active_option, $license_key_option, 'buddyboss_dynamic_plugin_id' ) as $license_option ) {
			add_action( 'add_option_' . $license_option, array( $this, 'clear_platform_update_cache' ) );
			add_action( 'update_option_' . $license_option, array( $this, 'clear_platform_update_cache' ) );
			add_action( 'add_site_option_' . $license_option, array( $this, 'clear_platform_update_cache' ) );
			add_action( 'update_site_option_' . $license_option, array( $this, 'clear_platform_update_cache' ) );
		}

		// Handle license status changes. GroundLevel 9.1.2's periodic license check fires
		// `{plugin_id}_active_license_invalidated` / `_active_license_expired` when the
		// license is revoked or expired (the old `_license_status_changed` event no longer
		// fires, but the hook is kept for backward compatibility with custom callers).
		add_action( $plugin_id . '_active_license_invalidated', array( $this, 'handle_license_revoked' ) );
		add_action( $plugin_id . '_active_license_expired', array( $this, 'handle_license_revoked' ) );
		add_action( $plugin_id . '_license_status_changed', array( $this, 'handle_license_status_change' ), 10, 2 );

		// Add-on buttons on the Network Admin add-ons page must act network-wide.
		if ( is_admin() ) {
			BB_Addons_Manager::register_ajax_slug_normalizer( $plugin_id );
			BB_Addons_Manager::register_network_ajax_handlers( $plugin_id );
		}

		// For local development - disable SSL verification if needed.
		if ( defined( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) && constant( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) ) {
			add_filter( 'https_ssl_verify', '__return_false' );
		}
	}

	/**
	 * Whether the vendor service hooks are needed on this request.
	 *
	 * Booting the GroundLevel providers hooks `site_transient_update_plugins` /
	 * `site_transient_update_themes` (the vendor add-on update injection), which fetch the
	 * add-ons list from the licensing API on a cold cache. The theme reads the
	 * `update_themes` transient on every request, the front end included, so an anonymous
	 * page load could block on that remote call. None of the vendor hooks does anything
	 * useful on a public page: they serve wp-admin, admin AJAX, cron (license status check,
	 * background auto-updates, notification fetches), WP-CLI and REST.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @return bool
	 */
	private function should_boot_services(): bool {
		// ALTERNATE_WP_CRON runs cron inside an ordinary front-end request, after plugins have
		// loaded, so wp_doing_cron() is still false here while the vendor cron callbacks
		// (license status check, notification fetches) will be needed later in the request.
		// Such sites therefore keep the pre-gate behaviour of booting on every request.
		if ( is_admin() || wp_doing_cron() || wp_doing_ajax() || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON ) ) {
			$should_boot = true;
		} else {
			// REST_REQUEST is only defined once the request is parsed, long after this runs, so
			// detect both the pretty (`/wp-json/`) and the plain-permalink (`?rest_route=`) form.
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$should_boot = ! empty( $_GET['rest_route'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request routing check.
				|| ( '' !== $request_uri && false !== strpos( $request_uri, '/' . rest_get_url_prefix() . '/' ) );
		}

		/**
		 * Filters whether the GroundLevel Mothership services are booted on this request.
		 *
		 * @since BuddyBoss 3.5.1
		 *
		 * @param bool $should_boot Whether to boot the services.
		 */
		return (bool) apply_filters( 'bb_mothership_boot_services', $should_boot );
	}

	/**
	 * Remove GroundLevel's license-key overwrite guard when licenses are held per site.
	 *
	 * The vendor's {@see LicenseManager::onLicenseKeyOverwritten()} compares the current
	 * license key against the `{pluginId}_activation` transient. That transient is a SITE
	 * transient named only by plugin ID, so on a multisite network where Platform is
	 * activated per site it is shared by every site of the same edition, while each site
	 * keeps its own key. Sites with different keys would then deactivate each other on
	 * their next admin page load. The guard cannot give a correct answer in that mode, so
	 * it is removed; in network mode (one shared license) and on single sites it stays.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	private function disable_overwrite_guard_for_per_site_licenses(): void {
		if ( ! is_multisite() || BB_Plugin_Connector::is_network_mode() ) {
			return;
		}

		remove_action( 'admin_init', array( $this->container->get( LicenseManager::class ), 'onLicenseKeyOverwritten' ) );
	}

	/**
	 * Render the notice for a subsite license that could not be moved to the network.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public function render_network_move_skipped_notice(): void {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}

		// The network has its own license now; the notice has done its job.
		if ( $this->pluginConnector->getLicenseActivationStatus() ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			delete_site_option( BB_Plugin_Connector::NETWORK_MOVE_SKIPPED_OPTION );

			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses(
				sprintf(
					/* translators: %s: URL of the network License page. */
					__( 'BuddyBoss Platform is now network-activated, so the network shares one license. The license previously activated on a subsite could not be carried over. Please <a href="%s">activate your license</a> for the network.', 'buddyboss' ),
					esc_url( network_admin_url( 'admin.php?page=' . BB_License_Page::SLUG ) )
				),
				array( 'a' => array( 'href' => array() ) )
			)
		);
	}

	/**
	 * Register admin pages.
	 */
	public function register_admin_pages(): void {
		$capability = BB_Plugin_Connector::license_capability();

		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// The BuddyBoss network menu is absent in multiblog mode; fall back to Network Settings.
		$parent = 'buddyboss-platform';
		if ( is_network_admin() && empty( $GLOBALS['admin_page_hooks']['buddyboss-platform'] ) ) {
			$parent = 'settings.php';
		}

		// Register License page.
		\BuddyBoss\Core\Admin\Mothership\BB_License_Page::register( $parent, $capability );

		// Register Addons page.
		\BuddyBoss\Core\Admin\Mothership\BB_Addons_Page::register( $parent, $capability );
	}

	/**
	 * Keep the license status cron on the main site only when network-activated.
	 *
	 * The vendor LicenseManager schedules a twice-daily status check on every site that boots
	 * it. In network mode all sites share one license, so each extra run is a duplicate API
	 * call that can also revoke the shared license. Blocks scheduling on subsites and clears
	 * any event scheduled before network activation.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param string $plugin_id The dynamic plugin ID the vendor names the cron hook after.
	 */
	private function limit_license_cron_to_main_site( string $plugin_id ): void {
		if ( ! BB_Plugin_Connector::is_network_mode() || is_main_site() ) {
			return;
		}

		$cron_hook = $plugin_id . '_check_license_activation_status_event';

		add_filter(
			'pre_schedule_event',
			static function ( $pre, $event ) use ( $cron_hook ) {
				return ( is_object( $event ) && isset( $event->hook ) && $cron_hook === $event->hook ) ? false : $pre;
			},
			10,
			2
		);

		if ( wp_next_scheduled( $cron_hook ) ) {
			wp_clear_scheduled_hook( $cron_hook );
		}
	}

	/**
	 * Redirect License/Add-ons page requests made in the admin that does not host them.
	 *
	 * In network mode the pages exist only in Network Admin, so a subsite link built with
	 * `admin_url()` is sent there (for users who can manage the network license). In per-site
	 * mode the pages exist only on sites, so a Network Admin link built with
	 * `network_admin_url()` is sent to the main site when Platform runs there.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public function redirect_misrouted_license_pages(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing of a page slug.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $page, array( BB_License_Page::SLUG, BB_Addons_Page::SLUG ), true ) || ! is_multisite() ) {
			return;
		}

		$target = '';

		if ( BB_Plugin_Connector::is_network_mode() ) {
			if ( ! is_network_admin() && current_user_can( 'manage_network_options' ) ) {
				$target = network_admin_url( 'admin.php?page=' . $page );
			}
		} elseif ( is_network_admin() && function_exists( 'buddypress' ) ) {
			$main_site_id = get_main_site_id();
			$active       = (array) get_blog_option( $main_site_id, 'active_plugins', array() );

			if ( in_array( buddypress()->basename, $active, true ) ) {
				$target = get_admin_url( $main_site_id, 'admin.php?page=' . $page );
			}
		}

		if ( '' !== $target ) {
			wp_safe_redirect( $target );
			exit;
		}
	}

	/**
	 * Move the network license back to the main site when Platform is network-deactivated.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param string $plugin              Basename of the deactivated plugin.
	 * @param bool   $network_deactivating Whether it was deactivated network-wide.
	 */
	public function handle_network_deactivation( $plugin, $network_deactivating ): void {
		if ( ! $network_deactivating || ! function_exists( 'buddypress' ) || buddypress()->basename !== $plugin ) {
			return;
		}

		BB_Plugin_Connector::move_license_to_main_site();
		$this->clear_platform_update_cache();
	}

	/**
	 * Handle a license revocation/expiry reported by GroundLevel's periodic check.
	 *
	 * Bridges the GroundLevel 7.4.0 `{plugin_id}_active_license_invalidated` /
	 * `_active_license_expired` actions to BuddyBoss's existing deactivation handler.
	 * Accepts no arguments so it is safe regardless of how many the action passes.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public function handle_license_revoked(): void {
		$this->handle_license_status_change( false, null );
	}

	/**
	 * Handle license status changes.
	 *
	 * @param bool  $is_active License active status.
	 * @param mixed $response API response.
	 */
	public function handle_license_status_change( bool $is_active, $response ): void {
		if ( ! $is_active ) {
			// License is no longer active. updateLicenseActivationStatus() also clears the
			// add-ons cache via the plugin connector, so no explicit cache purge is needed here.
			$this->pluginConnector->updateLicenseActivationStatus( false ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Log the deactivation (sanitized - no sensitive data).
			$log_message = 'BuddyBoss license deactivated';
			if ( $response instanceof Response ) {
				$error_code = $response->statusCode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				if ( $error_code ) {
					$log_message .= sprintf( ' - Error code: %d', $error_code );
				}
			}
			bb_error_log( $log_message, true );
		} else {
			// License is active - ensure status is updated.
			$this->pluginConnector->updateLicenseActivationStatus( true ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		}
	}

	/**
	 * Injects the Platform's own plugin update into the update_plugins transient.
	 *
	 * This is the only update path. BuddyBoss Platform ships without an `Update URI` header,
	 * so WordPress never fires `update_plugins_{host}` for it, and the Platform is not
	 * registered with the vendor UpdateService (see {@see self::register_services()}).
	 * Dropping the header is safe because `buddyboss-platform` is not a wordpress.org slug,
	 * so no w.org listing can claim the plugin.
	 *
	 * It uses the same data source as the vendor ({@see Products::getVersionCheck()}), runs only
	 * when licensed — leaving wordpress.org updates intact for unlicensed installs — and keys
	 * the entry by plugin file.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param mixed $transient The update_plugins transient (object) or false.
	 * @return mixed The (possibly modified) transient.
	 */
	public function inject_platform_update( $transient ) {
		if ( ! is_object( $transient ) ) {
			return $transient;
		}

		$plugin_file = ( function_exists( 'buddypress' ) && isset( buddypress()->basename ) )
			? buddypress()->basename
			: 'buddyboss-platform/bp-loader.php';

		try {
			// Only offer updates when the license is active. On a multisite network with Platform
			// activated per site, updates are applied once from Network Admin (which runs as the
			// main site) to files every site shares, so a license on any site that runs Platform
			// entitles the network to them.
			$license_site = 0;
			if ( ! $this->pluginConnector->getLicenseActivationStatus() ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$license_site = BB_Plugin_Connector::find_licensed_site();

				if ( ! $license_site ) {
					return $transient;
				}
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();

			$installed = isset( $transient->checked[ $plugin_file ] )
				? (string) $transient->checked[ $plugin_file ]
				: (string) ( $plugins[ $plugin_file ]['Version'] ?? '' );

			if ( '' === $installed ) {
				return $transient;
			}

			// Resolve the update item from the BuddyBoss-side cache (or fetch + cache on miss).
			// Null means "no update info available" — leave the transient untouched.
			$item = $this->get_platform_update_item( $plugins, $plugin_file, $license_site );
			if ( null === $item ) {
				return $transient;
			}

			if ( ! isset( $transient->response ) || ! is_array( $transient->response ) ) {
				$transient->response = array();
			}
			if ( ! isset( $transient->no_update ) || ! is_array( $transient->no_update ) ) {
				$transient->no_update = array(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			}

			if ( version_compare( $installed, (string) $item->new_version, '>=' ) ) {
				$transient->no_update[ $plugin_file ] = $item; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.PropertyNotSnakeCase
			} else {
				$transient->response[ $plugin_file ] = $item;
			}
		} catch ( \Throwable $e ) {
			// Never break the update transient — degrade silently.
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( 'BuddyBoss platform update injection failed: ' . $e->getMessage(), true );
			}
		}

		return $transient;
	}

	/**
	 * Resolve the Platform's update item, caching the result in a site transient.
	 *
	 * On a cache hit the stored payload is returned with zero API/HTTP work. On a miss the
	 * Mothership version check runs once and the outcome is cached for {@see self::UPDATE_CACHE_TTL}
	 * (or until invalidated by {@see self::flush_platform_update_cache()} /
	 * {@see self::clear_platform_update_cache()}). A failed check is cached as "no update info"
	 * for only {@see self::UPDATE_ERROR_CACHE_TTL}, so an outage neither suppresses updates for
	 * 12h nor makes every read of the `update_plugins` transient issue its own blocking request.
	 *
	 * The cached payload shape is `array( 'item' => array|null )`: the prepared WordPress update
	 * entry, or null meaning "checked, no update info". The two states are distinguished from a
	 * cache miss by {@see get_site_transient()} returning `false` only when nothing is stored.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @since BuddyBoss 3.5.1 Added the `$license_site` parameter.
	 *
	 * @param array<string, array<string, mixed>> $plugins      Installed plugins ({@see get_plugins()}).
	 * @param string                              $plugin_file  The Platform plugin file (basename).
	 * @param int                                 $license_site Blog ID whose license authorizes the check, or 0 for the current site.
	 * @return object|null The update item object, or null when no update info is available.
	 */
	private function get_platform_update_item( array $plugins, string $plugin_file, int $license_site = 0 ): ?object {
		$cached = get_site_transient( self::UPDATE_CACHE_KEY );
		if ( is_array( $cached ) && array_key_exists( 'item', $cached ) ) {
			return is_array( $cached['item'] ) ? (object) $cached['item'] : null;
		}

		$version_check = $license_site
			? $this->version_check_for_site( $license_site )
			: $this->container->get( Products::class )->getVersionCheck(
				$this->pluginConnector->pluginId, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				array(
					'prerelease' => $this->pluginConnector->allowPrereleaseVersions(), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					'_embed'     => 'version,product',
				)
			);

		// A licensed-site check skipped by the re-entry guard never ran: nothing to cache.
		if ( null === $version_check && $this->in_licensed_site ) {
			return null;
		}

		// Cache a failure briefly: retried soon, but not on every transient read meanwhile.
		if ( null === $version_check || $version_check->isError() ) {
			set_site_transient(
				self::UPDATE_CACHE_KEY,
				array(
					'item'  => null,
					'error' => true,
				),
				self::UPDATE_ERROR_CACHE_TTL
			);

			return null;
		}

		$latest = (string) $version_check->getData( 'number', '' );
		$item   = null;

		if ( '' !== $latest ) {
			$version_obj = $version_check->getEmbed( 'version' );

			$item = array(
				'id'          => $plugin_file,
				'slug'        => dirname( $plugin_file ),
				'plugin'      => $plugin_file,
				'new_version' => $latest,
				'url'         => $plugins[ $plugin_file ]['PluginURI'] ?? '',
				'package'     => $version_obj->url ?? '',
			);

			// Match the native vendor UpdateService: surface the plugin icon so it renders on
			// the Dashboard > Updates screen. The `product` embed (already requested above via
			// `_embed=version,product`) exposes a single image URL, which WordPress accepts for
			// both the 1x and 2x icon slots.
			$product   = $version_check->getEmbed( 'product' );
			$image_url = isset( $product->image ) ? (string) $product->image : '';
			if ( '' !== $image_url ) {
				$item['icons'] = array(
					'2x' => $image_url,
					'1x' => $image_url,
				);
			}
		}

		// Cache the resolved outcome (item array or null) for the TTL backstop.
		set_site_transient( self::UPDATE_CACHE_KEY, array( 'item' => $item ), self::UPDATE_CACHE_TTL );

		return null !== $item ? (object) $item : null;
	}

	/**
	 * Guards {@see self::with_licensed_site()} against re-entry from filters it triggers.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @var bool
	 */
	private $in_licensed_site = false;

	/**
	 * Run a callback inside another site with Mothership services bound to that site's license.
	 *
	 * The container's services are bound to the current site's connector, whose plugin ID and
	 * credentials belong to that site. The callback therefore runs inside the licensed site with
	 * a connector, credentials and request built there; credentials are read at request time, so
	 * the switch covers the whole call.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param int      $blog_id  The licensed site.
	 * @param callable $callback Receives ( BB_Plugin_Connector $connector, Credentials $credentials, Products $products ).
	 * @return mixed The callback's return value, or null when it could not run.
	 */
	private function with_licensed_site( int $blog_id, callable $callback ) {
		if ( $this->in_licensed_site ) {
			return null;
		}

		$this->in_licensed_site = true;
		switch_to_blog( $blog_id );

		try {
			$connector   = new BB_Plugin_Connector();
			$util        = $this->container->get( Util::class );
			$credentials = new Credentials( $connector, $util );
			$products    = new Products( new Request( $connector, $credentials, $util, 0 ) );

			return $callback( $connector, $credentials, $products );
		} catch ( \Throwable $e ) {
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( 'BuddyBoss: update check via licensed site failed: ' . $e->getMessage(), true );
			}

			return null;
		} finally {
			restore_current_blog();
			$this->in_licensed_site = false;
		}
	}

	/**
	 * Run the Mothership version check with another site's license.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param int $blog_id The licensed site.
	 * @return Response|null The version-check response, or null when it could not be made.
	 */
	private function version_check_for_site( int $blog_id ): ?Response {
		return $this->with_licensed_site(
			$blog_id,
			static function ( BB_Plugin_Connector $connector, Credentials $credentials, Products $products ) {
				return $products->getVersionCheck(
					$connector->pluginId, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					array(
						'prerelease' => $connector->allowPrereleaseVersions(),
						'_embed'     => 'version,product',
					)
				);
			}
		);
	}

	/**
	 * Inject add-on updates when the current site is unlicensed but another site is licensed.
	 *
	 * The vendor LegacyUpdateService adds add-on updates only when the CURRENT site's license
	 * is active. With Platform activated per site, Network Admin runs as the main site, so a
	 * license held by any other site never produced add-on updates there — although plugin
	 * files are shared by every site and are updated once, from Network Admin. This runs the
	 * same vendor service inside the licensed site, so the add-on list, version comparison,
	 * signed download URLs and `Update URI` handling stay the vendor's.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param mixed $transient The update_plugins / update_themes transient.
	 * @return mixed The (possibly modified) transient.
	 */
	public function inject_addon_updates_via_licensed_site( $transient ) {
		if ( ! is_object( $transient ) || $this->in_licensed_site || ! $this->pluginConnector instanceof BB_Plugin_Connector ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return $transient;
		}

		// Licensed here: the vendor service already handled it.
		if ( $this->pluginConnector->getLicenseActivationStatus() ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			return $transient;
		}

		$license_site = BB_Plugin_Connector::find_licensed_site();
		if ( ! $license_site ) {
			return $transient;
		}

		$is_themes = 'site_transient_update_themes' === current_filter();
		$container = $this->container;

		$result = $this->with_licensed_site(
			$license_site,
			static function ( BB_Plugin_Connector $connector, Credentials $credentials, Products $products ) use ( $transient, $is_themes, $container ) {
				$util    = $container->get( Util::class );
				$addons  = new AddonsManager( $connector, $credentials, $products, $container->get( View::class ), $util );
				$service = new LegacyUpdateService( $connector, $addons, $util );

				return $is_themes ? $service->addonsUpdateThemes( $transient ) : $service->addonsUpdatePlugins( $transient );
			}
		);

		return is_object( $result ) ? $result : $transient;
	}

	/**
	 * Flush the Platform update-check cache when WordPress writes a fresh `update_plugins`
	 * transient (a genuine update fetch). Passes the value through unchanged.
	 *
	 * @since BuddyBoss 3.5.1
	 *
	 * @param mixed $value The value WordPress is about to store. Returned unmodified.
	 * @return mixed The unmodified value.
	 */
	public function flush_platform_update_cache( $value ) {
		delete_site_transient( self::UPDATE_CACHE_KEY );
		return $value;
	}

	/**
	 * Clear the Platform update-check cache. Used as an action callback on license changes, so
	 * a newly activated/validated/revoked license is reflected on the next update check.
	 *
	 * @since BuddyBoss 3.5.1
	 */
	public function clear_platform_update_cache(): void {
		delete_site_transient( self::UPDATE_CACHE_KEY );
	}

	/**
	 * Get the container.
	 *
	 * @return Container The container instance.
	 */
	public function get_container(): Container {
		// init() bails before building the container when the GroundLevel vendor tree is
		// stale, so this can be reached with nothing set. The declared return type forbids
		// null, and several callers resolve the container without a try/catch, so hand back
		// an empty container instead of raising a TypeError: an unresolved service throws a
		// catchable container exception, which is a far better failure than a fatal.
		if ( ! $this->container instanceof Container ) {
			$this->container = new Container();
		}

		return $this->container;
	}

	/**
	 * Get the container (backward-compatibility wrapper).
	 *
	 * Retained for any external/third-party code that may resolve services via the
	 * camelCase accessor. New code should call {@see self::get_container()}.
	 *
	 * @deprecated Use get_container() instead.
	 *
	 * @return Container The container instance.
	 */
	public function getContainer(): Container { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		return $this->get_container();
	}

	/**
	 * Migrate legacy license data from old storage to Mothership.
	 *
	 * This method checks for legacy license data stored in options
	 * and attempts to migrate it to the new Mothership system.
	 */
	public static function migrate_legacy_license(): void {
		if ( ! is_admin() ) {
			return; // Only run migration in admin context.
		}

		$network_activated = false;
		/**
		 * This is added to give the backward compatibility.
		 */
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once ABSPATH . '/wp-admin/includes/plugin.php';
			}

			if ( is_plugin_active_for_network( buddypress()->basename ) ) {
				$network_activated = true;
			}
		}

		if ( $network_activated && true === (bool) get_site_option( 'bb_mothership_licenses_migrated', false ) ) {
			return;
		} elseif ( ! $network_activated && true === (bool) get_option( 'bb_mothership_licenses_migrated', false ) ) {
			return;
		}

		$legacy_licences = get_option( 'bboss_updater_saved_licenses', array() );

		if ( $network_activated ) {
			$legacy_licences = get_site_option( 'bboss_updater_saved_licenses', array() );
		}

		if ( empty( $legacy_licences ) ) {
			return;
		}

		$migrated_licence = array();

		if ( isset( $legacy_licences['buddyboss_theme'] ) ) {
			$migrated_licence['buddyboss_theme'] = $legacy_licences['buddyboss_theme'];
		}

		if ( isset( $legacy_licences['bb_platform_pro'] ) ) {
			$migrated_licence['bb_platform_pro'] = $legacy_licences['bb_platform_pro'];
		}

		if ( empty( $migrated_licence ) ) {
			return;
		}

		// Reuse the already-booted singleton container — a fresh `new self()` would
		// re-register and re-boot the service providers, duplicating their hooks.
		$instance         = self::instance();
		$container        = $instance->get_container();
		$plugin_connector = $container->get( AbstractPluginConnection::class );
		$plugin_id        = $plugin_connector->getDynamicPluginId();

		$current_status = $plugin_connector->getLicenseActivationStatus();

		if ( $current_status ) {
			return;
		}

		foreach ( $migrated_licence as $plugin_key => $license_data ) {
			if (
				empty( $license_data['license_key'] ) ||
				empty( $license_data['status'] ) ||
				empty( $license_data['software_product_id'] )
			) {
				continue;
			}

			$current_status = $plugin_connector->getLicenseActivationStatus();

			if ( $current_status ) {
				break;
			}

			$software_id = $license_data['software_product_id'];
			$plugin_id   = self::map_software_id_to_plugin_id( $software_id );

			if ( PLATFORM_EDITION !== $plugin_id ) {
				$plugin_connector->setDynamicPluginId( $plugin_id );
				$domain = $container->get( Credentials::class )->getDomain();

				// Check if we're being rate limited before attempting migration activation.
				if ( self::is_rate_limited_for_migration( $network_activated ) ) {
					continue; // Skip this license migration.
				}

				// Translators: %s is the response error message.
				$error_html = esc_html__( 'Migrate License activation failed: %s', 'buddyboss' );

				try {
					$response = $container->get( LicenseActivations::class )->activate( $plugin_id, $license_data['license_key'], $domain );
				} catch ( \Exception $e ) {
					bb_error_log( sprintf( $error_html, $e->getMessage() ), true );
					// Clear the dynamic plugin ID on exception to prevent orphaned state.
					$plugin_connector->clearDynamicPluginId();
					continue;
				}

				if ( $response instanceof Response && ! $response->isError() ) {
					try {
						$container->get( Credentials::class )->setLicenseKey( $license_data['license_key'] );

						// Pin the identifier this activation was created with, before the status
						// flips. resolveDomain() returns host/path while the license is inactive,
						// so without this the next request would fall back to the bare host and
						// the status check would 404 and revoke a subdirectory install.
						$plugin_connector->storeActivationDomain( $domain );

						// updateLicenseActivationStatus() clears the add-ons cache via the connector.
						$plugin_connector->updateLicenseActivationStatus( true );

						// Drop the stale vendor activation cache and announce the valid license.
						BB_License_Manager::after_license_activated();

						if ( $network_activated ) {
							update_site_option( 'bb_mothership_licenses_migrated', true );
						} else {
							update_option( 'bb_mothership_licenses_migrated', true );
						}
					} catch ( \Exception $e ) {
						// Log the exception.
						bb_error_log( 'Error storing migrated license key: ' . $e->getMessage(), true );
					}
				} else {
					// Migration failed - clear the dynamic plugin ID to prevent orphaned state.
					$error_code    = $response->statusCode; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$error_message = $response->getErrorMessage();

					bb_error_log( sprintf( 'BuddyBoss License Migration Failed (Code: %d): %s', $error_code, $error_message ), true );

					// If it's a 422 product mismatch, definitely clear the dynamic plugin ID.
					if ( 422 === $error_code ) {
						$plugin_connector->clearDynamicPluginId();
						bb_error_log( 'BuddyBoss: Cleared dynamic plugin ID due to 422 product mismatch during migration', true );
					} elseif ( 429 !== $error_code ) {
						// For errors other than rate limiting, also clear the dynamic plugin ID.
						// (Rate limit might be temporary, so we keep the plugin ID for retry).
						$plugin_connector->clearDynamicPluginId();
					}
				}
			}
		}
	}

	/**
	 * Check if migration is currently rate limited.
	 *
	 * @param bool $network_activated Whether the plugin is network activated.
	 *
	 * @return bool True if rate limited, false otherwise.
	 */
	private static function is_rate_limited_for_migration( bool $network_activated ): bool {
		$rate_limit_data = $network_activated ? get_site_transient( 'bb_license_rate_limit' ) : get_transient( 'bb_license_rate_limit' );

		if ( ! $rate_limit_data || ! is_array( $rate_limit_data ) ) {
			return false;
		}

		$reset_time   = isset( $rate_limit_data['reset'] ) ? (int) $rate_limit_data['reset'] : 0;
		$current_time = time();

		if ( $reset_time > 0 && $current_time < $reset_time ) {
			$wait_minutes = ceil( ( $reset_time - $current_time ) / 60 );
			bb_error_log(
				sprintf(
					'BuddyBoss: Skipping migration activation - rate limited for %d more minutes (reset: %s)',
					$wait_minutes,
					gmdate( 'Y-m-d H:i:s', $reset_time )
				),
				true
			);
			return true;
		}

		return false;
	}

	/**
	 * Map legacy software product ID to new plugin ID.
	 *
	 * @param string $software_id The legacy software product ID.
	 *
	 * @return string The mapped plugin ID.
	 */
	private static function map_software_id_to_plugin_id( string $software_id ): string {
		$mapping = array(
			'BB_PLATFORM_PRO_1S'  => 'bb-platform-pro-1-site',
			'BB_PLATFORM_PRO_2S'  => 'bb-platform-pro-2-sites',
			'BB_PLATFORM_PRO_5S'  => 'bb-platform-pro-5-sites',
			'BB_PLATFORM_FREE'    => 'bb-platform-free',
			'BB_PLATFORM_PRO_10S' => 'bb-platform-pro-10-sites',
			'BB_THEME_1S'         => 'bb-web',
			'BUDDYBOSS_THEME_1S'  => 'bb-web',
			'BB_THEME_2S'         => 'bb-web-2-sites',
			'BB_THEME_5S'         => 'bb-web-5-sites',
			'BUDDYBOSS_THEME_5S'  => 'bb-web-5-sites',
			'BB_THEME_10S'        => 'bb-web-10-sites',
			'BB_THEME_20S'        => 'bb-web-20-sites',
			'BUDDYBOSS_THEME_20S' => 'bb-web-20-sites',
		);

		return isset( $mapping[ $software_id ] ) ? $mapping[ $software_id ] : PLATFORM_EDITION;
	}
}
