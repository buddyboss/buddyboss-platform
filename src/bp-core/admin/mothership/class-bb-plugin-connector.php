<?php

declare(strict_types=1);

namespace BuddyBoss\Core\Admin\Mothership;

use BuddyBossPlatform\GroundLevel\Mothership\AbstractPluginConnection;

/**
 * Plugin Connector class for BuddyBoss Platform.
 *
 * This class follows the GroundLevel AbstractPluginConnection pattern
 * for managing plugin-specific data and API connections.
 */
class BB_Plugin_Connector extends AbstractPluginConnection {

	/**
	 * Constructor for the BB_Plugin_Connector class.
	 */
	public function __construct() {
		$this->pluginId     = $this->getDynamicPluginId();
		$this->pluginPrefix = 'buddyboss';

		// The Mothership product ID is the dynamic plugin ID (edition) the license was
		// activated against. GroundLevel 9.x reads it directly: the add-ons manager fetches
		// add-ons as relations of this product, and the license manager uses it for
		// activation/edition checks — so it must never be left empty.
		$this->productId = $this->pluginId;

		// Plugin basename for GroundLevel update integration (per the package README's
		// connection setup). BuddyBoss Platform ships without an `Update URI` header, so the
		// update entry is injected into the update_plugins transient by
		// {@see BB_Mothership_Loader::inject_platform_update()}; this basename is the key it
		// is filed under.
		if ( function_exists( 'buddypress' ) && isset( buddypress()->basename ) ) {
			$this->pluginFile = buddypress()->basename;
		}
	}

	/**
	 * Get the dynamic plugin ID from stored option or default.
	 *
	 * @return string The plugin ID.
	 */
	public function getDynamicPluginId(): string {
		$storedPluginId = self::get_license_option( 'buddyboss_dynamic_plugin_id', PLATFORM_EDITION );
		return ! empty( $storedPluginId ) ? $storedPluginId : PLATFORM_EDITION;
	}

	/**
	 * Site option recording that the main site's license was copied to the network.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NETWORK_SCOPE_MIGRATED_OPTION = 'bb_license_network_scope_migrated';

	/**
	 * Set while license rows are moved between the network and the main site, so the
	 * legacy read bridge does not mask the per-site rows being copied.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var bool
	 */
	private static $bypass_legacy_bridge = false;

	/**
	 * Serve direct `get_option()` reads of the license rows from the network in network mode.
	 *
	 * Add-ons and custom code written before license state became network-scoped read these
	 * rows with plain `get_option()` (e.g. BuddyBoss Membership copies the Platform key for its
	 * own update checks). On a network-activated install those per-site rows are empty on
	 * subsites and stale on the main site, so without this bridge such readers silently see an
	 * unlicensed or outdated site. Reads only; writes still go through the connector.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function register_legacy_option_bridge(): void {
		if ( ! self::is_network_mode() ) {
			return;
		}

		$plugin_id = (string) get_site_option( 'buddyboss_dynamic_plugin_id', '' );
		$plugin_id = '' !== $plugin_id ? $plugin_id : PLATFORM_EDITION;

		foreach ( self::license_option_names( $plugin_id ) as $name ) {
			add_filter( "pre_option_{$name}", array( self::class, 'filter_legacy_option_read' ), 10, 3 );
		}
	}

	/**
	 * `pre_option_{$name}` callback for {@see self::register_legacy_option_bridge()}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param mixed  $pre           Short-circuit value from earlier filters.
	 * @param string $name          Option name.
	 * @param mixed  $default_value Default passed to `get_option()`.
	 * @return mixed
	 */
	public static function filter_legacy_option_read( $pre, $name = '', $default_value = false ) {
		// Respect an earlier short-circuit, and step aside while rows are being moved or
		// once Platform is no longer network-activated (e.g. mid network deactivation).
		if ( false !== $pre || self::$bypass_legacy_bridge || '' === $name || ! self::is_network_mode() ) {
			return $pre;
		}

		$value = get_site_option( $name, null );

		if ( null !== $value ) {
			return $value;
		}

		// The network is authoritative: never fall through to a dormant per-site row.
		return false === $default_value ? '' : $default_value;
	}

	/**
	 * Whether license state is stored network-wide.
	 *
	 * True only when BuddyBoss Platform is network-activated on a multisite install. In that
	 * mode the network owns a single license: it is managed from Network Admin and every
	 * subsite reads it from site options. Otherwise each site keeps its own license in its
	 * own options table, exactly as on a single-site install.
	 *
	 * Deliberately not cached: network activation/deactivation changes the answer mid-request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	public static function is_network_mode(): bool {
		if ( ! is_multisite() || ! function_exists( 'buddypress' ) || empty( buddypress()->basename ) ) {
			return false;
		}

		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active_for_network( buddypress()->basename );
	}

	/**
	 * Capability required to view and change the license.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string `manage_network_options` in network mode, otherwise `manage_options`.
	 */
	public static function license_capability(): string {
		return self::is_network_mode() ? 'manage_network_options' : 'manage_options';
	}

	/**
	 * Reads a license option from the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name          Option name.
	 * @param mixed  $default_value Value returned when the option does not exist.
	 * @return mixed
	 */
	public static function get_license_option( string $name, $default_value = false ) {
		return self::is_network_mode() ? get_site_option( $name, $default_value ) : get_option( $name, $default_value );
	}

	/**
	 * Writes a license option to the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Option value.
	 * @return bool Whether the value was updated.
	 */
	public static function update_license_option( string $name, $value ): bool {
		return (bool) ( self::is_network_mode() ? update_site_option( $name, $value ) : update_option( $name, $value ) );
	}

	/**
	 * Deletes a license option from the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name Option name.
	 * @return bool Whether the option was deleted.
	 */
	public static function delete_license_option( string $name ): bool {
		return (bool) ( self::is_network_mode() ? delete_site_option( $name ) : delete_option( $name ) );
	}

	/**
	 * Reads a license transient from the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name Transient name.
	 * @return mixed
	 */
	public static function get_license_transient( string $name ) {
		return self::is_network_mode() ? get_site_transient( $name ) : get_transient( $name );
	}

	/**
	 * Writes a license transient to the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name       Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Expiration in seconds.
	 * @return bool
	 */
	public static function set_license_transient( string $name, $value, int $expiration ): bool {
		return self::is_network_mode() ? set_site_transient( $name, $value, $expiration ) : set_transient( $name, $value, $expiration );
	}

	/**
	 * Deletes a license transient from the active storage scope.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $name Transient name.
	 * @return bool
	 */
	public static function delete_license_transient( string $name ): bool {
		return self::is_network_mode() ? delete_site_transient( $name ) : delete_transient( $name );
	}

	/**
	 * Names of every option that makes up the license state for a plugin ID.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $plugin_id The dynamic plugin ID the per-SKU options are keyed by.
	 * @return string[]
	 */
	private static function license_option_names( string $plugin_id ): array {
		return array(
			'buddyboss_dynamic_plugin_id',
			'buddyboss_web_plugin_id',
			self::STABLE_LICENSE_KEY_OPTION,
			self::ACTIVATION_DOMAIN_OPTION,
			$plugin_id . '_license_key',
			$plugin_id . '_license_activation_status',
		);
	}

	/**
	 * Copies the main site's license to the network once Platform is network-activated.
	 *
	 * Runs lazily on every load in network mode (one site-option read once migrated), which
	 * covers both a fresh network activation and installs that were network-activated before
	 * license state became network-scoped. It is a local copy — no API call — and is
	 * idempotent:
	 *
	 * - A license already stored on the network is never overwritten.
	 * - The MAIN site's license is preferred. Its activation domain is the network domain, so
	 *   it is the same activation record on the licensing server.
	 * - Only when the main site has no license is a subsite license carried over, and only if
	 *   exactly one distinct key is active among the subsites whose activation host matches
	 *   the network — see {@see self::find_subsite_license_for_network()}. Anything
	 *   else is left for the network admin to activate from Network Admin. This scan runs on
	 *   an admin, cron or WP-CLI request only, never on a front-end page view.
	 * - The per-site rows are left in place (dormant), so nothing is lost if the network
	 *   activation is later reversed.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool True when a license was copied to the network.
	 */
	public static function maybe_move_license_to_network(): bool {
		if ( ! self::is_network_mode() || get_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION, false ) ) {
			return false;
		}

		// Never overwrite a license the network already holds.
		$network_plugin_id = (string) get_site_option( 'buddyboss_dynamic_plugin_id', PLATFORM_EDITION );
		if ( '' !== (string) get_site_option( self::STABLE_LICENSE_KEY_OPTION, '' ) || '' !== (string) get_site_option( $network_plugin_id . '_license_key', '' ) ) {
			update_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION, 1 );

			return false;
		}

		$main_site_id = get_main_site_id();

		self::$bypass_legacy_bridge = true;

		$plugin_id = (string) get_blog_option( $main_site_id, 'buddyboss_dynamic_plugin_id', '' );
		$plugin_id = '' !== $plugin_id ? $plugin_id : PLATFORM_EDITION;

		$main_key = (string) get_blog_option( $main_site_id, $plugin_id . '_license_key', '' );
		if ( '' === $main_key ) {
			$main_key = (string) get_blog_option( $main_site_id, self::STABLE_LICENSE_KEY_OPTION, '' );
		}

		$source_site_id = $main_site_id;
		$source_domain  = '';

		if ( '' === $main_key ) {
			// The subsite scan below can touch many sites; keep it off front-end page views and
			// retry on the next admin, cron or WP-CLI request. Nothing is written until then.
			if ( ! ( is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) ) ) {
				self::$bypass_legacy_bridge = false;

				return false;
			}

			// The scan completes in this request, found or not, so it runs only once.
			update_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION, 1 );

			// Before license state was network-scoped, a network-activated install could only
			// enter its key from a single site's dashboard — possibly a subsite. Carry such a
			// license over rather than silently dropping it.
			$candidate = self::find_subsite_license_for_network( $main_site_id );

			if ( null === $candidate ) {
				self::$bypass_legacy_bridge = false;

				return false;
			}

			$source_site_id = $candidate['blog_id'];
			$plugin_id      = $candidate['plugin_id'];
			$main_key       = $candidate['key'];
			$source_domain  = $candidate['domain'];
		} else {
			// The main site's license is copied now, so this runs only once.
			update_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION, 1 );
		}

		foreach ( self::license_option_names( $plugin_id ) as $name ) {
			$value = get_blog_option( $source_site_id, $name, null );
			if ( null !== $value ) {
				update_site_option( $name, $value );
			}
		}

		self::$bypass_legacy_bridge = false;

		// The per-SKU key may be empty while the stable mirror holds the key.
		update_site_option( $plugin_id . '_license_key', $main_key );

		// Pin the identifier the subsite was activated with, so the network keeps using
		// that activation record instead of resolving a new one, and remember where it came
		// from so a later network deactivation does not hand it to the main site.
		if ( '' !== $source_domain ) {
			update_site_option( self::ACTIVATION_DOMAIN_OPTION, $source_domain );
			update_site_option(
				self::NETWORK_SOURCE_SITE_OPTION,
				array(
					'blog_id' => $source_site_id,
					'domain'  => $source_domain,
				)
			);
		}

		return true;
	}

	/**
	 * Site option recording the subsite a network license was carried over from.
	 *
	 * Holds `array( 'blog_id' => int, 'domain' => string )`. Read by
	 * {@see self::move_license_to_main_site()}: while the network still uses that subsite's
	 * activation, the license is not copied to the main site on network deactivation — the
	 * subsite still holds its own (dormant) copy, and two sites must not share one activation.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NETWORK_SOURCE_SITE_OPTION = 'bb_license_network_source_site';

	/**
	 * Site option naming a subsite whose license could not be moved to the network.
	 *
	 * Set by {@see self::maybe_move_license_to_network()} when the main site has no license
	 * and no subsite license can be carried over safely (activated for a different host, or
	 * several subsites hold different keys), so the network admin can be told to activate
	 * the license from Network Admin.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const NETWORK_MOVE_SKIPPED_OPTION = 'bb_license_network_move_skipped_site';

	/**
	 * Finds an active subsite license that the network can take over.
	 *
	 * Only a license whose activation domain has the network's host is returned: the
	 * network resolves its activation domain from the network home URL, so a license
	 * activated for another host (a subdomain or mapped-domain subsite) would 404 on the
	 * next status check and be revoked. When more than one distinct key is active across
	 * the subsites, none is chosen: they may belong to different customers or editions.
	 * Either way the case is recorded in {@see self::NETWORK_MOVE_SKIPPED_OPTION} so the
	 * network admin is asked to activate the license from Network Admin.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $main_site_id The main site ID, which is skipped.
	 * @return array{blog_id: int, plugin_id: string, key: string, domain: string}|null
	 */
	private static function find_subsite_license_for_network( int $main_site_id ): ?array {
		$network_host = strtolower( (string) wp_parse_url( network_home_url(), PHP_URL_HOST ) );

		if ( '' === $network_host ) {
			return null;
		}

		/** This filter is documented in src/bp-core/admin/mothership/class-bb-plugin-connector.php */
		$limit = (int) apply_filters( 'bb_license_licensed_sites_scan_limit', 1000 );

		$blog_ids = get_sites(
			array(
				'fields'       => 'ids',
				'number'       => $limit,
				'site__not_in' => array( $main_site_id ),
				'network_id'   => get_current_network_id(),
				'archived'     => 0,
				'deleted'      => 0,
				'spam'         => 0,
			)
		);

		$skipped    = 0;
		$candidates = array();

		foreach ( $blog_ids as $blog_id ) {
			$blog_id   = (int) $blog_id;
			$plugin_id = (string) get_blog_option( $blog_id, 'buddyboss_dynamic_plugin_id', '' );
			$plugin_id = '' !== $plugin_id ? $plugin_id : PLATFORM_EDITION;

			if ( ! get_blog_option( $blog_id, $plugin_id . '_license_activation_status', false ) ) {
				continue;
			}

			$key = (string) get_blog_option( $blog_id, $plugin_id . '_license_key', '' );
			if ( '' === $key ) {
				$key = (string) get_blog_option( $blog_id, self::STABLE_LICENSE_KEY_OPTION, '' );
			}

			if ( '' === $key ) {
				continue;
			}

			// A license activated before the stored-domain option existed was activated
			// against the site's bare host.
			$domain = (string) get_blog_option( $blog_id, self::ACTIVATION_DOMAIN_OPTION, '' );
			if ( '' === $domain ) {
				$domain = (string) wp_parse_url( get_home_url( $blog_id ), PHP_URL_HOST );
			}

			if ( '' === $domain || self::domain_host( $domain ) !== $network_host ) {
				$skipped = $skipped ? $skipped : $blog_id;

				continue;
			}

			// The first site found for a key is the one carried over.
			if ( ! isset( $candidates[ $key ] ) ) {
				$candidates[ $key ] = array(
					'blog_id'   => $blog_id,
					'plugin_id' => $plugin_id,
					'key'       => $key,
					'domain'    => $domain,
				);
			}
		}

		if ( 1 === count( $candidates ) ) {
			return reset( $candidates );
		}

		if ( ! empty( $candidates ) ) {
			$first   = reset( $candidates );
			$skipped = $first['blog_id'];
		}

		if ( $skipped ) {
			update_site_option( self::NETWORK_MOVE_SKIPPED_OPTION, $skipped );
		}

		return null;
	}

	/**
	 * Moves the network license back to the main site when Platform is network-deactivated.
	 *
	 * The network domain equals the main site's domain, so the main site keeps the same
	 * activation record. The network rows are removed afterwards (a move, not a copy) so a
	 * later network activation re-copies whatever the main site holds then instead of
	 * resurrecting a stale network value. Subsites fall back to their own dormant license,
	 * if any.
	 *
	 * A license that was carried over from a subsite (and is still on that subsite's
	 * activation) is not copied to the main site: the subsite keeps its own dormant copy,
	 * and two sites must not share one activation record.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public static function move_license_to_main_site(): void {
		$main_site_id = get_main_site_id();

		// Nothing was ever moved up, or it has already been handed back: a repeat run must not
		// wipe the main site's license rows.
		$network_id_for_check = (string) get_site_option( 'buddyboss_dynamic_plugin_id', '' );
		$network_id_for_check = '' !== $network_id_for_check ? $network_id_for_check : PLATFORM_EDITION;
		if (
			! get_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION, false ) &&
			'' === (string) get_site_option( self::STABLE_LICENSE_KEY_OPTION, '' ) &&
			'' === (string) get_site_option( $network_id_for_check . '_license_key', '' ) &&
			! get_site_option( $network_id_for_check . '_license_activation_status', false )
		) {
			return;
		}

		// update_blog_option() compares against get_option(); the bridge must not answer it.
		self::$bypass_legacy_bridge = true;

		$plugin_id = (string) get_site_option( 'buddyboss_dynamic_plugin_id', '' );
		$plugin_id = '' !== $plugin_id ? $plugin_id : PLATFORM_EDITION;
		$main_id   = (string) get_blog_option( $main_site_id, 'buddyboss_dynamic_plugin_id', '' );
		$main_id   = '' !== $main_id ? $main_id : PLATFORM_EDITION;

		$network_key = (string) get_site_option( $plugin_id . '_license_key', '' );
		if ( '' === $network_key ) {
			$network_key = (string) get_site_option( self::STABLE_LICENSE_KEY_OPTION, '' );
		}

		// Rows for the network ID, the main site's own ID (the one copied up) and every known
		// edition, so per-SKU rows left by an earlier ID do not survive on either side.
		$names = array();
		foreach ( array_unique( array_merge( array( $plugin_id, $main_id ), self::known_plugin_ids() ) ) as $id ) {
			$names = array_merge( $names, self::license_option_names( $id ) );
		}
		$names = array_unique( $names );

		// The network is authoritative: the main site ends up with exactly its current state. A
		// row the network no longer holds (e.g. after a license deactivation), or one keyed by a
		// superseded plugin ID, is removed rather than letting a stale copy come back.
		$current = self::license_option_names( $plugin_id );

		$source           = get_site_option( self::NETWORK_SOURCE_SITE_OPTION, array() );
		$from_other_site  = is_array( $source ) && ! empty( $source['blog_id'] ) && (int) $source['blog_id'] !== (int) $main_site_id;
		$still_its_record = $from_other_site && isset( $source['domain'] ) && (string) get_site_option( self::ACTIVATION_DOMAIN_OPTION, '' ) === (string) $source['domain'];

		if ( ! $still_its_record ) {
			foreach ( $names as $name ) {
				$value = in_array( $name, $current, true ) ? get_site_option( $name, null ) : null;

				if ( null !== $value ) {
					update_blog_option( $main_site_id, $name, $value );
				} else {
					delete_blog_option( $main_site_id, $name );
				}
			}

			if ( '' !== $network_key ) {
				update_blog_option( $main_site_id, $plugin_id . '_license_key', $network_key );
			}
		}

		self::$bypass_legacy_bridge = false;

		foreach ( $names as $name ) {
			delete_site_option( $name );
		}
		delete_site_option( self::NETWORK_SCOPE_MIGRATED_OPTION );
		delete_site_option( self::NETWORK_MOVE_SKIPPED_OPTION );
		delete_site_option( self::NETWORK_SOURCE_SITE_OPTION );
		delete_site_transient( $plugin_id . '_license_details' );
	}

	/**
	 * Site option listing the sites that hold an active license (per-site activation only).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const LICENSED_SITES_OPTION = 'bb_license_licensed_sites';

	/**
	 * Record whether the current site holds an active license.
	 *
	 * Only meaningful when Platform is activated per site on a multisite network: plugin files
	 * are shared by every site and are updated once from Network Admin, so the update check
	 * there needs to know which site's license entitles the network to updates.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param bool $status Whether the current site's license is active.
	 */
	private static function track_licensed_site( bool $status ): void {
		if ( ! is_multisite() || self::is_network_mode() ) {
			return;
		}

		$sites   = self::get_licensed_sites();
		$blog_id = get_current_blog_id();

		if ( $status ) {
			$sites[ $blog_id ] = $blog_id;
		} else {
			unset( $sites[ $blog_id ] );
		}

		update_site_option( self::LICENSED_SITES_OPTION, $sites );
	}

	/**
	 * The sites recorded as licensed, building the list once for installs that predate it.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int[] Blog IDs keyed by blog ID.
	 */
	private static function get_licensed_sites(): array {
		$sites = get_site_option( self::LICENSED_SITES_OPTION, null );

		if ( is_array( $sites ) ) {
			return $sites;
		}

		$sites = array();

		/**
		 * Filters how many sites are scanned when the licensed-sites list is first built.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @param int $limit Maximum number of sites to scan.
		 */
		$limit = (int) apply_filters( 'bb_license_licensed_sites_scan_limit', 1000 );

		$blog_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => $limit,
			)
		);

		foreach ( $blog_ids as $blog_id ) {
			if ( null !== self::get_site_license( (int) $blog_id ) ) {
				$sites[ (int) $blog_id ] = (int) $blog_id;
			}
		}

		update_site_option( self::LICENSED_SITES_OPTION, $sites );

		return $sites;
	}

	/**
	 * First site on the network whose license can authorize Platform updates.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return int Blog ID, or 0 when no site qualifies (or not in per-site multisite mode).
	 */
	public static function find_licensed_site(): int {
		if ( ! is_multisite() || self::is_network_mode() ) {
			return 0;
		}

		// Entries are validated, never pruned here: a site whose Platform is switched off for a
		// moment must still count once it is back, and re-activating Platform does not touch
		// the license status that maintains the list.
		foreach ( self::get_licensed_sites() as $blog_id ) {
			if ( null !== self::get_site_license( (int) $blog_id ) ) {
				return (int) $blog_id;
			}
		}

		// Nothing valid: rebuild once, at most every 12 hours, to catch licenses the list missed.
		if ( false === get_site_transient( 'bb_license_licensed_sites_rescan' ) ) {
			set_site_transient( 'bb_license_licensed_sites_rescan', 1, 12 * HOUR_IN_SECONDS );
			delete_site_option( self::LICENSED_SITES_OPTION );

			foreach ( self::get_licensed_sites() as $blog_id ) {
				return (int) $blog_id;
			}
		}

		return 0;
	}

	/**
	 * A site's active Platform license, read without switching the current request.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param int $blog_id Blog ID.
	 * @return array{plugin_id: string}|null Null unless Platform is active on the site with an
	 *                                       active license and a stored key.
	 */
	private static function get_site_license( int $blog_id ): ?array {
		if ( ! function_exists( 'buddypress' ) || ! get_site( $blog_id ) ) {
			return null;
		}

		if ( ! in_array( buddypress()->basename, (array) get_blog_option( $blog_id, 'active_plugins', array() ), true ) ) {
			return null;
		}

		$plugin_id = (string) get_blog_option( $blog_id, 'buddyboss_dynamic_plugin_id', '' );
		$plugin_id = '' !== $plugin_id ? $plugin_id : PLATFORM_EDITION;

		if ( ! get_blog_option( $blog_id, $plugin_id . '_license_activation_status', false ) ) {
			return null;
		}

		$key = (string) get_blog_option( $blog_id, $plugin_id . '_license_key', '' );
		if ( '' === $key ) {
			$key = (string) get_blog_option( $blog_id, self::STABLE_LICENSE_KEY_OPTION, '' );
		}

		return '' !== $key ? array( 'plugin_id' => $plugin_id ) : null;
	}

	/**
	 * Plugin IDs of every known BuddyBoss Platform edition.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string[]
	 */
	private static function known_plugin_ids(): array {
		return array(
			PLATFORM_EDITION,
			'bb-platform-free',
			'bb-platform-pro-1-site',
			'bb-platform-pro-2-sites',
			'bb-platform-pro-5-sites',
			'bb-platform-pro-10-sites',
			'bb-web',
			'bb-web-2-sites',
			'bb-web-5-sites',
			'bb-web-10-sites',
			'bb-web-20-sites',
		);
	}

	/**
	 * Clear the BuddyBoss license-details and add-ons caches for the current plugin ID.
	 *
	 * Both caches are keyed by the current dynamic plugin ID, so this is called on each side
	 * of a plugin-ID change (before, to purge the OLD ID's caches; after, to purge the NEW
	 * ID's caches) — the two calls clear different keys, they are not redundant.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	private static function clear_all_caches(): void {
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_Addons_Manager::clearProductAddOnsCache();
		}
		if ( class_exists( '\BuddyBoss\Core\Admin\Mothership\BB_License_Manager' ) ) {
			\BuddyBoss\Core\Admin\Mothership\BB_License_Manager::clearLicenseDetailsCache();
		}

		// The DRM "is this add-on licensed" decision is cached per request and is resolved
		// as early as `bp_loaded`, long before a licence is activated on `admin_init` 20.
		// Without this reset the DRM sweep at `admin_init` 25 re-reads the pre-activation
		// answer and skips cleanup, leaving the wp_bb_drm_events rows and their notices in
		// place until the next page load.
		if ( class_exists( '\BuddyBoss\Core\Admin\DRM\BB_DRM_Addon' ) ) {
			\BuddyBoss\Core\Admin\DRM\BB_DRM_Addon::reset_licensed_cache();
		}
	}

	/**
	 * Set the dynamic plugin ID.
	 *
	 * @param string $pluginId The plugin ID to store.
	 */
	public function setDynamicPluginId( string $pluginId ): void {
		// Purge caches scoped to the OLD plugin ID before changing.
		self::clear_all_caches();

		self::update_license_option( 'buddyboss_dynamic_plugin_id', $pluginId );
		$this->pluginId  = $pluginId;
		$this->productId = $pluginId;

		// Purge caches scoped to the NEW plugin ID after changing.
		self::clear_all_caches();
	}

	/**
	 * Clear the dynamic plugin ID.
	 *
	 * Deliberately does NOT clear the STABLE_LICENSE_KEY_OPTION mirror. Every
	 * caller except the explicit licence reset reaches this from a transient
	 * failure path — a rejected activation attempt or a failed legacy
	 * migration — where the previously activated key is still the customer's
	 * real licence and must survive, or the failure strands the site as
	 * unlicensed and DRM-nags a paying customer (the exact defect the mirror
	 * exists to fix). A failed activation never persists its candidate key,
	 * so the mirror only ever holds the last successfully activated one. The
	 * reset path is the one place that clears user intent, and it pairs this
	 * call with updateLicenseKey( '' ).
	 */
	public function clearDynamicPluginId(): void {
		// Purge caches scoped to the OLD plugin ID before clearing.
		self::clear_all_caches();

		self::delete_license_option( 'buddyboss_dynamic_plugin_id' );
		$this->pluginId  = PLATFORM_EDITION;
		$this->productId = PLATFORM_EDITION;

		// Purge caches scoped to the default plugin ID after clearing.
		self::clear_all_caches();
	}

	/**
	 * Get the current plugin ID.
	 *
	 * @return string The current plugin ID.
	 */
	public function getCurrentPluginId(): string {
		return $this->pluginId;
	}

	/**
	 * Gets the license activation status option.
	 *
	 * @return boolean The license activation status.
	 */
	public function getLicenseActivationStatus(): bool {
		$pluginId = $this->getCurrentPluginId();
		$status   = self::get_license_option( $pluginId . '_license_activation_status', false );
		return (bool) $status;
	}

	/**
	 * Sets the license activation status option.
	 *
	 * Overrides {@see AbstractPluginConnection::setLicenseActivationStatus()} so the
	 * BuddyBoss option name (`{pluginId}_license_activation_status`) is preserved —
	 * the GroundLevel 9.1.2 base defaults to `{pluginId}_license_active`, which would
	 * orphan every existing activation. Also clears BuddyBoss license/add-on caches.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param boolean $status The status to update.
	 * @return boolean Whether the option was updated successfully.
	 */
	public function setLicenseActivationStatus( bool $status ): bool {
		$pluginId = $this->getCurrentPluginId();
		$updated  = self::update_license_option( $pluginId . '_license_activation_status', $status );

		self::track_licensed_site( $status );

		// Clear license details + add-ons caches when activation status changes.
		self::clear_all_caches();

		return (bool) $updated;
	}

	/**
	 * Updates the license activation status option.
	 *
	 * Backward-compatible alias for {@see self::setLicenseActivationStatus()} retained
	 * for existing BuddyBoss callers that expect a void return.
	 *
	 * @param boolean $status The status to update.
	 */
	public function updateLicenseActivationStatus( bool $status ): void {
		$this->setLicenseActivationStatus( $status );
	}

	/**
	 * Resolves the license key from storage.
	 *
	 * Overrides {@see AbstractPluginConnection::resolveLicenseKey()} so the
	 * GroundLevel 9.1.2 {@see Credentials} reads the dynamic-plugin-id-scoped option.
	 * The option name (`{pluginId}_license_key`) matches the base default; the
	 * override exists only to honor the BuddyBoss dynamic plugin ID.
	 *
	 * Reads the per-SKU option first, then the stable mirror. Both hold the
	 * currently activated key. Superseded `{old_sku}_license_key` rows are
	 * deliberately never read — reporting a stale key is worse than reporting
	 * none, because it looks correct. The fallback lives here rather than in
	 * {@see self::getLicenseKey()} because `Credentials::getLicenseKey()` resolves
	 * through this method, so this is the only placement that also keeps the
	 * GroundLevel read path working across a plugin-id change.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The license key.
	 */
	public function resolveLicenseKey(): string {
		$pluginId    = $this->getCurrentPluginId();
		$license_key = (string) self::get_license_option( $pluginId . '_license_key', '' );

		if ( '' !== $license_key ) {
			return $license_key;
		}

		return (string) self::get_license_option( self::STABLE_LICENSE_KEY_OPTION, '' );
	}

	/**
	 * Stores the license key.
	 *
	 * Overrides {@see AbstractPluginConnection::storeLicenseKey()} so the GroundLevel
	 * 9.1.2 {@see Credentials} writes the dynamic-plugin-id-scoped option.
	 *
	 * Writes both the per-SKU option and the stable mirror so a later id change
	 * cannot strand the key. An empty value clears both, so resets stay clean.
	 * The return value reports the per-SKU write, because that is the option the
	 * base contract describes and the value `Credentials::setLicenseKey()` hands
	 * back to its callers; the mirror is a BuddyBoss-side durability copy.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $licenseKey The license key to store.
	 * @return boolean Whether the option was updated successfully.
	 */
	public function storeLicenseKey( string $licenseKey ): bool {
		$pluginId = $this->getCurrentPluginId();
		$updated  = self::update_license_option( $pluginId . '_license_key', $licenseKey );

		if ( '' === $licenseKey ) {
			self::delete_license_option( self::STABLE_LICENSE_KEY_OPTION );

			return (bool) $updated;
		}

		self::update_license_option( self::STABLE_LICENSE_KEY_OPTION, $licenseKey );

		return (bool) $updated;
	}

	/**
	 * Option holding the licence key under a name that never changes.
	 *
	 * The per-SKU option (`{plugin_id}_license_key`) is addressed by a mutable
	 * id, so changing or clearing that id strands the key. This mirror is the
	 * durable copy; the per-SKU option is kept in step for backwards
	 * compatibility with anything reading it directly.
	 *
	 * @since BuddyBoss 3.4.3
	 *
	 * @var string
	 */
	const STABLE_LICENSE_KEY_OPTION = 'buddyboss_license_key';

	/**
	 * Gets the license key option.
	 *
	 * Convenience accessor retained for BuddyBoss callers (e.g. the add-ons manager
	 * license gate in {@see BB_Addons_Manager::render_addons_html()}). The per-SKU
	 * option and the {@see self::STABLE_LICENSE_KEY_OPTION} mirror are both read by
	 * {@see self::resolveLicenseKey()}, so this stays a thin delegate.
	 *
	 * @return string The license key.
	 */
	public function getLicenseKey(): string {
		return $this->resolveLicenseKey();
	}

	/**
	 * Updates the license key option.
	 *
	 * Backward-compatible alias for {@see self::storeLicenseKey()} retained for
	 * existing BuddyBoss callers that expect a void return. The mirror write (and
	 * its removal on an empty key) happens there.
	 *
	 * @param string $licenseKey The license key to update.
	 */
	public function updateLicenseKey( string $licenseKey ): void {
		$this->storeLicenseKey( $licenseKey );
	}

	/**
	 * Resolves the license activation domain.
	 *
	 * Overrides {@see AbstractPluginConnection::resolveDomain()}, which returns only the
	 * host of the home URL. WordPress installs that live in a subdirectory (e.g.
	 * `example.com/community`) would otherwise share an activation identifier with a
	 * second install on the root domain, so activating both against the same license
	 * conflicts (PROD-9984). Appending the path gives each install its own identifier.
	 *
	 * The domain is the activation's identity on the licensing server: it is sent as the
	 * Basic-auth username and as a path segment of `licenses/{key}/activations/{domain}`,
	 * so `example.com` and `example.com/community` are two different activation records.
	 * Changing the format for a site that is ALREADY activated would orphan its record —
	 * the twice-daily status cron would 404 and GroundLevel would revoke the license. So
	 * the resolution order is:
	 *
	 * 1. The domain stored at activation time, when its host still matches this site (or, in
	 *    network mode, the main site).
	 * 2. The bare host, for a license activated before that option existed (legacy).
	 * 3. `host/path`, for a fresh activation only.
	 *
	 * The stored domain is ignored once its host no longer matches the site's home URL.
	 * A staging copy or database clone inherits the option, and without this check it
	 * would keep authenticating as production — and a "Deactivate" clicked on the clone
	 * would release production's activation on the licensing server.
	 *
	 * When BuddyBoss Platform is network-activated, the network home URL is used so
	 * every site in the network resolves the same domain for the shared license.
	 *
	 * A `BUDDYBOSS_DOMAIN` constant or environment variable still takes precedence via
	 * {@see \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getDomain()}.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The activation domain (`host` or `host/path`).
	 */
	public function resolveDomain(): string {
		$network  = self::is_network_mode();
		$home_url = $network ? network_home_url() : get_home_url();
		$host     = (string) wp_parse_url( $home_url, PHP_URL_HOST );
		$stored   = $this->getStoredActivationDomain();

		// In network mode the license may have been activated from the main site before it
		// moved to the network, and the main site's host can differ from the network's
		// (domain mapping). Either host belongs to this install, never to a clone.
		$hosts = array( strtolower( $host ) );
		if ( $network ) {
			$hosts[] = strtolower( (string) wp_parse_url( get_home_url( get_main_site_id() ), PHP_URL_HOST ) );
		}

		if ( '' !== $stored && ( '' === $host || in_array( self::domain_host( $stored ), $hosts, true ) ) ) {
			return $stored;
		}

		if ( '' === $host ) {
			return '';
		}

		// An already-active license predates the stored-domain option and was activated
		// against the bare host. Keep sending the bare host or the server will not
		// recognise the activation and will revoke it on the next status check.
		if ( $this->getLicenseActivationStatus() ) {
			return $host;
		}

		return $host . untrailingslashit( (string) wp_parse_url( $home_url, PHP_URL_PATH ) );
	}

	/**
	 * Gets the lower-cased host part of an activation domain (`host` or `host/path`).
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $domain Activation domain.
	 * @return string The host.
	 */
	private static function domain_host( string $domain ): string {
		$parts = explode( '/', $domain, 2 );

		return strtolower( $parts[0] );
	}

	/**
	 * Option holding the domain a license was actually activated against.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @var string
	 */
	const ACTIVATION_DOMAIN_OPTION = 'buddyboss_license_activation_domain';

	/**
	 * Gets the domain stored at activation time.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The stored domain, or an empty string when none is stored.
	 */
	public function getStoredActivationDomain(): string {
		$stored = self::get_license_option( self::ACTIVATION_DOMAIN_OPTION, '' );

		return is_string( $stored ) ? $stored : '';
	}

	/**
	 * Stores the domain a license was activated against.
	 *
	 * Called on a successful activation so the identifier stays stable for the life of
	 * that activation, whatever the site URL does afterwards.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $domain The domain the activation was performed with.
	 */
	public function storeActivationDomain( string $domain ): void {
		// A new activation is the network's own, no longer the subsite record it was
		// carried over from — see self::NETWORK_SOURCE_SITE_OPTION.
		delete_site_option( self::NETWORK_SOURCE_SITE_OPTION );

		if ( '' === $domain ) {
			self::delete_license_option( self::ACTIVATION_DOMAIN_OPTION );

			return;
		}

		self::update_license_option( self::ACTIVATION_DOMAIN_OPTION, $domain );
	}

	/**
	 * Clears the stored activation domain.
	 *
	 * Called on deactivation/reset so the next activation resolves a fresh identifier.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function clearActivationDomain(): void {
		self::delete_license_option( self::ACTIVATION_DOMAIN_OPTION );
		delete_site_option( self::NETWORK_SOURCE_SITE_OPTION );
	}

	/**
	 * Gets the license activation domain.
	 *
	 * Backward-compatible alias for {@see self::resolveDomain()} retained for existing
	 * BuddyBoss callers. Consumer code should prefer
	 * {@see \BuddyBossPlatform\GroundLevel\Mothership\Credentials::getDomain()}, which
	 * also honors constant/environment overrides.
	 *
	 * @return string The activation domain.
	 */
	public function getDomain(): string {
		return $this->resolveDomain();
	}

	/**
	 * Gets the BuddyBoss account dashboard URL.
	 *
	 * GroundLevel links here from the add-ons grid ("Upgrade" cards for add-ons the
	 * current license does not include) and from the plugin update row when an update
	 * cannot be downloaded because of the license state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return string The account URL.
	 */
	public function getAccountUrl(): string {
		return 'https://www.buddyboss.com/my-account/';
	}
}
