<?php
/*
 * Allow automatic updates from BuddyBoss servers
 *
 * @since BuddyBoss 1.0.0
*/
// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BP_BuddyBoss_Platform_Updater' ) ) :
	/**
	 * Load BuddyBoss Platform Updater class
	 *
	 * @since BuddyBoss 1.0.0
	 */
	class BP_BuddyBoss_Platform_Updater {

		var $license;
		var $api_url;
		var $plugin_id = 0;
		var $plugin_path;
		var $plugin_slug;
		var $transient_name;
		var $transient_time = 8 * HOUR_IN_SECONDS;


		function __construct( $api_url, $plugin_path, $plugin_id, $license = '' ) {

			$this->api_url     = $api_url;
			$this->plugin_path = $plugin_path;
			$this->license     = $license;
			$this->plugin_id   = $plugin_id;

			if ( strstr( $plugin_path, '/' ) ) {
				list ( $part1, $part2 ) = explode( '/', $plugin_path );
			} else {
				$part2 = $plugin_path;
			}

			$this->plugin_slug    = str_replace( '.php', '', $part2 );
			$this->transient_name = 'bb_updates_' . $this->plugin_slug;

			add_filter( 'pre_set_site_transient_update_plugins', array( &$this, 'update_plugin' ), 99 );
			add_filter( 'plugins_api', array( &$this, 'plugins_api' ), 10, 3 );
		}

		function update_plugin( $transient ) {

			if ( ! isset( $transient->response ) ) {
				return $transient;
			}

			/**
			 * Get plugin version from transient. If transient return false then we will get plugin version from
			 * get_plugin_data function.
			 *
			 * @uses get_plugin_data()
			 */
			$current_version = isset( $transient->checked[ $this->plugin_path ] ) ? $transient->checked[ $this->plugin_path ] : false;
			if ( ! $current_version ) {
				$plugin_data = get_plugin_data( trailingslashit( WP_PLUGIN_DIR ) . $this->plugin_path, false, false );
				if ( ! empty( $plugin_data ) && isset( $plugin_data['Version'] ) ) {
					$current_version = $plugin_data['Version'];
				}
			}

			if ( ! $current_version ) {
				return $transient;
			}

			// Check if force check exists.
			$force_check = ! empty( $_GET['force-check'] ) ? true : false;

			// Check if response exists then return existing transient.
			// Also check if force check exists then bypass transient.
			if ( ! $force_check ) {
				$response_transient = get_transient( $this->transient_name );
				if ( ! empty( $response_transient ) ) {
					if ( isset( $response_transient->body ) ) {
						unset( $response_transient->body );
						$transient->no_update[ $this->plugin_path ] = $response_transient;
					} else {
						if ( $current_version === $response_transient->new_version ) {
							$transient->no_update[ $this->plugin_path ] = $response_transient;
							unset( $transient->response[ $this->plugin_path ] );
						} else {
							$transient->response[ $this->plugin_path ] = $response_transient;
						}
					}
					$transient->last_checked = time();
					return $transient;
				}
			}

			$request_data = array(
				'id'      => $this->plugin_id,
				'slug'    => $this->plugin_slug,
				'version' => $current_version,
			);

			if ( ! empty( $this->license ) ) {
				$request_data['license'] = $this->license;
			}

			$request_string = $this->request_call( 'update_check', $request_data );
			$raw_response   = wp_remote_post( $this->api_url, $request_string );

			$response = null;
			if ( ! is_wp_error( $raw_response ) && ( $raw_response['response']['code'] == 200 ) ) {
				if ( empty( $raw_response['body'] ) ) {
					// If we have no update then we store response in $transient->no_update variable.
					$no_update_response                         = new stdClass();
					$no_update_response->id                     = $this->plugin_id;
					$no_update_response->slug                   = $this->plugin_slug;
					$no_update_response->plugin                 = $this->plugin_path;
					$no_update_response->new_version            = $current_version;
					$no_update_response->body                   = $raw_response['body'];
					$transient->no_update[ $this->plugin_path ] = $no_update_response;
					set_transient( $this->transient_name, $no_update_response, $this->transient_time );
				}
				$response = unserialize( $raw_response['body'] );
			}

			// Feed the candy
			if ( is_object( $response ) && ! empty( $response ) ) {
				$transient->response[ $this->plugin_path ] = $response;

				// Set plugins data in transient for 8 hours to avoid multiple request to hit on server.
				set_transient( $this->transient_name, $response, $this->transient_time );
				$transient->last_checked = time();

				return $transient;
			}

			// If there is any same plugin from wordpress.org repository then unset it.
			if ( isset( $transient->response[ $this->plugin_path ] ) ) {
				if ( strpos( $transient->response[ $this->plugin_path ]->package, 'wordpress.org' ) !== false ) {
					unset( $transient->response[ $this->plugin_path ] );
				}
			}
			$transient->last_checked = time();

			return $transient;
		}

		function plugins_api( $def, $action, $args ) {

			if ( ! isset( $args->slug ) || $args->slug != $this->plugin_slug ) {
				return $def;
			}

			$plugin_info = get_site_transient( 'update_plugins' );

			$request_data = array(
				'id'      => $this->plugin_id,
				'slug'    => $this->plugin_slug,
				'version' => ( isset( $plugin_info->checked ) ) ? $plugin_info->checked[ $this->plugin_path ] : 0,
				// Current version
			);

			if ( ! empty( $this->license ) ) {
				$request_data['license'] = $this->license;
			}

			$request_string = $this->request_call( $action, $request_data );
			$raw_response   = wp_remote_post( $this->api_url, $request_string );

			if ( is_wp_error( $raw_response ) ) {
				$res = new WP_Error( 'plugins_api_failed', __( 'An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>', 'buddyboss' ), $raw_response->get_error_message() );
			} else {
				$res = unserialize( $raw_response['body'] );
				if ( $res === false ) {
					$res = new WP_Error( 'plugins_api_failed', __( 'An unknown error occurred', 'buddyboss' ), $raw_response['body'] );
				}
			}

			return $res;
		}

		function request_call( $action, $args ) {
			global $wp_version;

			return array(
				'body'       => array(
					'action'  => $action,
					'request' => serialize( $args ),
					'api-key' => md5( home_url() ),
				),
				'user-agent' => 'WordPress/' . $wp_version . '; ' . home_url(),
			);
		}

	}

endif; // End class_exists check.


