<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to call this page directly.' );}

/**
 * Generate a website "snapshot" to send to the MemberPress telemetry server.
 */
class BB_Core_Usage {

	/**
	 * Generates a unique site ID.
	 *
	 * @param bool $regenerate If true, the UUID will be recreated.
	 * @return string
	 */
	public function uuid( $regenerate = false ) {
		$uuid_key = 'bb-usage-uuid';
		$uuid     = bp_get_option( $uuid_key );

		if ( $regenerate || empty( $uuid ) ) {
			// Definitely not cryptographically secure but
			// close enough to provide an unique id.
			$uuid = md5( uniqid() . site_url() );
			update_option( $uuid_key, $uuid );
		}

		return $uuid;
	}

	/**
	 * Retrieves the current site snapshot.
	 *
	 * @return array
	 */
	public function snapshot() {
		global $wpdb;

		$query = "SELECT COUNT(ID) FROM {$wpdb->users}";

		$snap = array(
			'uuid'             => $this->uuid(),
			'bb_version'       => bp_get_version(),
			'bb_pro_version'   => '',
			'bb_theme_version' => '',
			'php_version'      => phpversion(),
			'mysql_version'    => $this->get_database_version(),
			'db_provider'      => $this->get_database_provider(),
			'os'               => ( function_exists( 'php_uname' ) ) ? php_uname( 's' ) : '',
			'webserver'        => $_SERVER['SERVER_SOFTWARE'],
			'active_license'   => '',
			'all_users'        => $wpdb->get_var( $query ),
			'timestamp'        => gmdate( 'c' ),
			'plugins'          => $this->plugins(),
			'themes'           => $this->themes(),
			'options'          => $this->options(),
			'is_multisite'     => is_multisite(),
		);

		return BB_Core_Hooks::apply_filters( 'bb_usage_snapshot', $snap );
	}

	/**
	 * Retrieves a list of plugins installed on the site.
	 *
	 * @return array[] {
	 *   An array containing one or more associative arrays of plugin data.
	 *
	 *   @type string  $name    The plugin's name.
	 *   @type string  $slug    The plugin's slug.
	 *   @type string  $version The plugin's current version.
	 *   @type boolean $active  Whether or not the plugin is a child theme.
	 * }
	 */
	private function plugins() {
		$plugin_list = get_plugins();
		wp_cache_delete( 'plugins', 'plugins' );

		$plugins = array();
		foreach ( $plugin_list as $slug => $info ) {
			$plugins[] = array(
				'name'    => $info['Name'],
				'slug'    => $slug,
				'version' => $info['Version'],
				'active'  => is_plugin_active( $slug ),
			);
		}

		return $plugins;
	}

	/**
	 * Retrieves the site's option data.
	 *
	 * @return array[]
	 */
	private function options() {
		$bb_options = BB_Core_Options::fetch();

		$options = array(
			// Add the list of options to track.
			'language_code'                   => $bb_options->language_code,
			'active_components'               => buddypress()->active_components,
			'is_pusher_enabled'               => function_exists( 'bb_pusher_is_enabled' ) && bb_pusher_is_enabled(),
			'bb_zoom_block_is_connected'      => function_exists( 'bb_onesignal_enabled_web_push' ) && bb_onesignal_enabled_web_push(),
			'bb_zoom_in_browser_is_connected' => function_exists( 'bb_onesignal_enabled_web_push' ) && bb_onesignal_enabled_web_push(),
			'bb_zoom_group_enabled'           => function_exists( 'bp_zoom_is_zoom_groups_enabled' ) && bp_zoom_is_zoom_groups_enabled(),
			'bb_recaptcha_enabled'            => ( function_exists( 'bb_recaptcha_connection_status' ) && 'verified' === bb_recaptcha_connection_status() ) ? 1 : 0,
			'bb_recaptcha_version'            => ( function_exists( 'bb_recaptcha_connection_status' ) && 'recaptcha_v2' === bb_recaptcha_recaptcha_versions() ) ? 'v2' : 'v3',
			'bb_profile_type_enabled'         => ( function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() ) ? 1 : 0,
			'bb_profile_search'               => ( function_exists( 'bp_disable_advanced_profile_search' ) && true === bp_disable_advanced_profile_search() ) ? 0 : 1,
			'bb_group_hierarchies'            => ( function_exists( 'bp_enable_group_hierarchies' ) && true === bp_enable_group_hierarchies() ) ? 1 : 0,
			'bb_web_notification_enabled'     => ( function_exists( 'bb_web_notification_enabled' ) && true === bb_web_notification_enabled() ) ? 1 : 0,

		);

		$custom_fields = $bb_options->custom_fields;

		if ( is_array( $custom_fields ) ) {
			foreach ( $custom_fields as $custom_field ) {
				$options['custom_field_count']++;

				if ( isset( $custom_field->field_type, $options[ "custom_field_{$custom_field->field_type}_count" ] ) ) {
					$options[ "custom_field_{$custom_field->field_type}_count" ]++;
				}
			}
		}

		return array( $options );
	}

	/**
	 * Retrieves information about the site's currently activated theme/s.
	 *
	 * @return array[] An array of one or more theme data arrays, {@see BB_Core_Usage::get_theme_data}.
	 *                 If the currently active theme is a child theme, the return
	 *                 will contain an additional array containing information
	 *                 about the parent theme.
	 */
	private function themes() {
		$themes = array();

		$theme    = wp_get_theme();
		$themes[] = $this->get_theme_data( $theme );

		if ( is_child_theme() ) {
			$themes[] = $this->get_theme_data(
				wp_get_theme( $theme->get( 'Template' ) )
			);
		}

		return $themes;
	}

	/**
	 * Builds a theme data array from a given WP_Theme object.
	 *
	 * @param WP_Theme $theme The theme object.
	 * @return array {
	 *   An associative array of theme data.
	 *
	 *   @type string  $name       The theme's name.
	 *   @type string  $stylesheet The theme's stylesheet / slug.
	 *   @type string  $version    The theme's current version.
	 *   @type string  $template   The theme's parent template or an empty string
	 *                             when the theme is not a child theme.
	 * }
	 */
	private function get_theme_data( $theme ) {
		return array(
			'name'       => $theme->get( 'Name' ),
			'stylesheet' => $theme->get_stylesheet(),
			'version'    => $theme->get( 'Version' ),
			'template'   => $theme->get( 'Template' ),
		);
	}

	/**
	 * Get the database version.
	 *
	 * @return string
	 */
	private function get_database_version() {
		global $wpdb;

		$db_version = $wpdb->db_version();

		if ( method_exists( $wpdb, 'db_server_info' ) ) {
			$db_server_info = $wpdb->db_server_info();

			// Account for MariaDB version being prefixed with '5.5.5-' on older PHP versions.
			if (
				'5.5.5' === $db_version &&
				is_string( $db_server_info ) &&
				strpos( $db_server_info, 'MariaDB' ) !== false &&
				PHP_VERSION_ID < 80016 // PHP 8.0.15 or older.
			) {
				// Strip the '5.5.5-' prefix and set the version to the correct value.
				$db_server_info = preg_replace( '/^5\.5\.5-(.*)/', '$1', $db_server_info );
				$db_version     = preg_replace( '/[^0-9.].*/', '', $db_server_info );
			}
		}

		return $db_version;
	}

	/**
	 * Get the database provider.
	 *
	 * @return string
	 */
	private function get_database_provider() {
		global $wpdb;

		if ( method_exists( $wpdb, 'db_server_info' ) ) {
			$db_server_info = $wpdb->db_server_info();

			if ( is_string( $db_server_info ) && strpos( $db_server_info, 'MariaDB' ) !== false ) {
				return 'MariaDB';
			}
		}

		return 'MySQL';
	}
} //End class
