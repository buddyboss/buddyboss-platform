<?php
/**
 * BuddyBoss Platform - Mothership Loader
 *
 * Main loader class for BuddyBoss Mothership functionality.
 * Handles initialization of licensing and In-Product Notifications services.
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
use BuddyBossPlatform\GroundLevel\Mothership\MothershipServiceProvider;
use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;
use BuddyBossPlatform\GroundLevel\InProductNotifications\IPNServiceProvider;

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
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	private const UPDATE_CACHE_KEY = 'bb_platform_update_check';

	/**
	 * TTL, in seconds, for the Platform update-check cache. A long ceiling is safe because
	 * the cache is event-invalidated (update fetch + license change); the TTL is only a
	 * backstop for sites where neither event fires.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var int
	 */
	private const UPDATE_CACHE_TTL = 12 * HOUR_IN_SECONDS;

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
	 * Whether the native vendor update filter was actually registered.
	 *
	 * Set only after `UpdateService::plugin()` returns without throwing. The fallback
	 * injector in {@see self::inject_platform_update()} stands down only when this is
	 * true, so a boot failure leaves the fallback active rather than disabling both paths.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var bool
	 */
	private $native_update_registered = false;

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

		try {
			// Create the container.
			$this->container = new Container();

			// Create the plugin connector.
			$this->pluginConnector = new \BuddyBoss\Core\Admin\Mothership\BB_Plugin_Connector(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Register the BuddyBoss plugin connection so that every GroundLevel service
			// (Credentials, View, AdminNotices, LicenseManager, ...) can resolve it.
			$plugin_connector = $this->pluginConnector; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$this->container->singleton(
				AbstractPluginConnection::class,
				static function () use ( $plugin_connector ) {
					return $plugin_connector;
				}
			);
		} catch ( \Throwable $e ) {
			$message = 'BuddyBoss Mothership container setup failed: ' . $e->getMessage();
			if ( function_exists( 'bb_error_log' ) ) {
				bb_error_log( $message, true );
			} else {
				error_log( $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			return;
		}

		// Register and boot the Mothership + In-Product Notifications service providers.
		$this->register_services();

		// Set up hooks.
		$this->setup_hooks();
	}

	/**
	 * Register and boot the GroundLevel service providers.
	 *
	 * GroundLevel 7.4.0 replaced the old static-container `Service` classes with the
	 * dependency-injection `ServiceProvider` pattern. Booting the providers wires the
	 * vendor hooks (twice-daily license-status cron, `auto_update_plugin`, add-on AJAX,
	 * plugin/theme update injection, and the In-Product Notifications UI). None of these
	 * duplicate BuddyBoss's own hooks — BuddyBoss wires its own license controller and
	 * admin pages separately in {@see self::setup_hooks()}.
	 *
	 * @since BuddyBoss [BBVERSION]
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

		try {
			// MothershipServiceProvider is auto-registered as an IPN dependency; register
			// it explicitly so intent and ordering are obvious.
			$this->container->provider( MothershipServiceProvider::class );
			$this->container->provider( IPNServiceProvider::class );

			// Boot registers the vendor WordPress hooks.
			$this->container->boot();

			// Register the Platform's fixed slug against the dynamic Mothership product id.
			//
			// The vendor UpdateService self-registers `plugin( $pluginId, '' )` on `init`, which
			// hooks `update_plugins_{$pluginId}` — a filter WordPress never fires, because it
			// derives that suffix from the HOST of a plugin's `Update URI` header, while
			// $pluginId is the dynamic product id (e.g. `bb-web-plus`). Registering the fixed
			// `buddyboss-platform` slug keeps the mapping correct for anything keyed by slug.
			//
			// BuddyBoss Platform deliberately ships WITHOUT an `Update URI` header, so the
			// `update_plugins_{host}` filter this registers is inert and updates are served by
			// {@see self::inject_platform_update()} instead. The call is kept because it also
			// registers the vendor's `plugins_api` handler, which is header-independent.
			$this->container->get( \BuddyBossPlatform\GroundLevel\Mothership\UpdateService::class )->plugin( 'buddyboss-platform', $plugin_id );

			// Record that the native registration itself succeeded. The injector additionally
			// requires an `Update URI` header before standing down — see
			// {@see self::inject_platform_update()}.
			$this->native_update_registered = true;
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
	 * Setup WordPress hooks.
	 */
	private function setup_hooks(): void {
		if ( is_admin() ) {
			// Register admin pages.
			add_action( 'admin_menu', array( $this, 'register_admin_pages' ), 99 );

			// Register license controller using BuddyBoss custom manager.
			add_action( 'admin_init', array( \BuddyBoss\Core\Admin\Mothership\BB_License_Manager::class, 'controller' ), 20 );

			// Register AJAX handlers.
			add_action( 'wp_ajax_bb_get_free_license', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_get_free_license' ) );
			add_action( 'wp_ajax_bb_reset_license_settings', array( 'BuddyBoss\Core\Admin\Mothership\BB_License_Manager', 'ajax_reset_license_settings' ) );
		}

		// Plugin updates. BuddyBoss Platform ships without an `Update URI` header, so WordPress
		// never fires `update_plugins_{host}` for it and this injector is the ACTIVE update
		// path. It stands down automatically if a header is ever added AND the native filter
		// registered successfully, so the two can never double-fire — see
		// {@see self::inject_platform_update()}.
		add_filter( 'site_transient_update_plugins', array( $this, 'inject_platform_update' ) );

		// Invalidate the Platform update-check cache whenever WordPress writes a fresh
		// `update_plugins` transient. That set only happens after a genuine update fetch (cron,
		// "Check again", or a completed install), so clearing here guarantees the next injector
		// run re-derives the payload from a fresh version check rather than a stale 12h cache.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'flush_platform_update_cache' ) );

		$plugin_id = $this->pluginConnector->getDynamicPluginId(); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

		// Invalidate the update-check cache on any license change. These fire from
		// BB_Plugin_Connector::setLicenseActivationStatus()/storeLicenseKey() (and BuddyBoss's
		// own license manager), covering activate, validate, deactivate and key entry regardless
		// of which code path triggered it. Hooking the option writes keeps this decoupled from the
		// vendor's event names. NOTE: BuddyBoss overrides the activation-status option name to
		// `_license_activation_status` (not the GroundLevel base default `_license_active`), so the
		// hook MUST bind to the overridden name or it would never fire — see BB_Plugin_Connector.
		$license_active_option = $plugin_id . '_license_activation_status';
		$license_key_option    = $plugin_id . '_license_key';
		add_action( 'add_option_' . $license_active_option, array( $this, 'clear_platform_update_cache' ) );
		add_action( 'update_option_' . $license_active_option, array( $this, 'clear_platform_update_cache' ) );
		add_action( 'add_option_' . $license_key_option, array( $this, 'clear_platform_update_cache' ) );
		add_action( 'update_option_' . $license_key_option, array( $this, 'clear_platform_update_cache' ) );

		// A dynamic-plugin-ID change (edition/tier switch) repoints the version check at a
		// different product, so the cached item is stale. This option name is fixed (not ID
		// dependent), so it stays correct even though the license-option hooks above are bound
		// to the ID resolved at init.
		add_action( 'add_option_buddyboss_dynamic_plugin_id', array( $this, 'clear_platform_update_cache' ) );
		add_action( 'update_option_buddyboss_dynamic_plugin_id', array( $this, 'clear_platform_update_cache' ) );

		// Handle license status changes. GroundLevel 9.1.2's periodic license check fires
		// `{plugin_id}_active_license_invalidated` / `_active_license_expired` when the
		// license is revoked or expired (the old `_license_status_changed` event no longer
		// fires, but the hook is kept for backward compatibility with custom callers).
		add_action( $plugin_id . '_active_license_invalidated', array( $this, 'handle_license_revoked' ) );
		add_action( $plugin_id . '_active_license_expired', array( $this, 'handle_license_revoked' ) );
		add_action( $plugin_id . '_license_status_changed', array( $this, 'handle_license_status_change' ), 10, 2 );

		// For local development - disable SSL verification if needed.
		if ( defined( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) && constant( 'BUDDYBOSS_DISABLE_SSL_VERIFY' ) ) {
			add_filter( 'https_ssl_verify', '__return_false' );
		}
	}

	/**
	 * Register admin pages.
	 */
	public function register_admin_pages(): void {
		// Only show to users with manage_options capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Register License page.
		\BuddyBoss\Core\Admin\Mothership\BB_License_Page::register();

		// Register Addons page.
		\BuddyBoss\Core\Admin\Mothership\BB_Addons_Page::register();
	}

	/**
	 * Handle a license revocation/expiry reported by GroundLevel's periodic check.
	 *
	 * Bridges the GroundLevel 7.4.0 `{plugin_id}_active_license_invalidated` /
	 * `_active_license_expired` actions to BuddyBoss's existing deactivation handler.
	 * Accepts no arguments so it is safe regardless of how many the action passes.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * This is the ACTIVE update path. BuddyBoss Platform ships without an `Update URI` header,
	 * so WordPress never fires `update_plugins_{host}` for it and the vendor UpdateService's
	 * native filter, although registered, is inert. Dropping the header is safe here because
	 * `buddyboss-platform` is not a wordpress.org slug, so no w.org listing can claim the
	 * plugin; the header's usual job of fencing w.org off has nothing to fence.
	 *
	 * It stands down only if BOTH the native filter registered successfully AND the plugin
	 * declares an `Update URI` — so adding the header later hands ownership back to the native
	 * path without the two ever double-firing, and losing either one keeps this injector live
	 * rather than leaving the site with no update path at all.
	 *
	 * It uses the same data source as the vendor ({@see Products::getVersionCheck()}), runs only
	 * when licensed — leaving wordpress.org updates intact for unlicensed installs — and keys
	 * the entry by plugin file so the vendor's auto-update / dev-block policy applies.
	 *
	 * @since BuddyBoss [BBVERSION]
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
			// Only offer updates when the license is active.
			if ( ! $this->pluginConnector->getLicenseActivationStatus() ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				return $transient;
			}

			if ( ! function_exists( 'get_plugins' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin.php';
			}
			$plugins = get_plugins();

			// Defer to the native vendor UpdateService only when it actually registered.
			//
			// Testing the Update URI header instead would conflate two different facts: the
			// header is static, but the native filter is registered by the LAST statement of
			// register_services()'s try{} block. Any throw before it (DI drift, an @inject
			// regression, a fatal in a service constructor) is swallowed by that catch, so
			// keying off the header alone would disable this fallback at exactly the moment
			// it is the only remaining update path — leaving the site with no updates at all
			// and nothing but a debug.log line.
			//
			// Both conditions have to hold for the native path to actually serve an update:
			// WordPress only fires `update_plugins_{host}` for a plugin that declares an
			// `Update URI`, and the filter behind it only exists if UpdateService::plugin()
			// registered without throwing. Standing down on either one alone leaves a gap —
			// drop the header and this injector would go quiet while no native filter ever
			// fires, leaving only the vendor's @deprecated LegacyUpdateService.
			$has_update_uri = ! empty( $plugins[ $plugin_file ]['UpdateURI'] );

			if ( $this->native_update_registered && $has_update_uri ) {
				return $transient;
			}

			$installed = isset( $transient->checked[ $plugin_file ] )
				? (string) $transient->checked[ $plugin_file ]
				: (string) ( $plugins[ $plugin_file ]['Version'] ?? '' );

			if ( '' === $installed ) {
				return $transient;
			}

			// Resolve the update item from the BuddyBoss-side cache (or fetch + cache on miss).
			// Null means "no update info available" — leave the transient untouched.
			$item = $this->get_platform_update_item( $plugins, $plugin_file );
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
	 * {@see self::clear_platform_update_cache()}). Errors are intentionally NOT cached so a
	 * transient API failure retries on the next request rather than suppressing updates for 12h.
	 *
	 * The cached payload shape is `array( 'item' => array|null )`: the prepared WordPress update
	 * entry, or null meaning "checked, no update info". The two states are distinguished from a
	 * cache miss by {@see get_site_transient()} returning `false` only when nothing is stored.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param array<string, array<string, mixed>> $plugins     Installed plugins ({@see get_plugins()}).
	 * @param string                              $plugin_file The Platform plugin file (basename).
	 * @return object|null The update item object, or null when no update info is available.
	 */
	private function get_platform_update_item( array $plugins, string $plugin_file ): ?object {
		$cached = get_site_transient( self::UPDATE_CACHE_KEY );
		if ( is_array( $cached ) && array_key_exists( 'item', $cached ) ) {
			return is_array( $cached['item'] ) ? (object) $cached['item'] : null;
		}

		$version_check = $this->container->get( \BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products::class )->getVersionCheck(
			$this->pluginConnector->pluginId, // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			array(
				'prerelease' => $this->pluginConnector->allowPrereleaseVersions(), // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				'_embed'     => 'version,product',
			)
		);

		// Do not cache errors — let the next request retry.
		if ( $version_check->isError() ) {
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
	 * Flush the Platform update-check cache when WordPress writes a fresh `update_plugins`
	 * transient (a genuine update fetch). Passes the value through unchanged.
	 *
	 * @since BuddyBoss [BBVERSION]
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
	 * @since BuddyBoss [BBVERSION]
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
						// updateLicenseActivationStatus() clears the add-ons cache via the connector.
						$plugin_connector->updateLicenseActivationStatus( true );

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
