<?php
/**
 * Holds Background process log functionality.
 *
 * @since   BuddyBoss 2.5.60
 * @package BuddyBoss/Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_API_Ratelimit' ) ) {
	class BB_API_Ratelimit {

		public static $instance = null;

		static $table_name = null;

		static $enabled_rate_limit = true;

		static $allowed_attempts = 10;

		static $attempts_time_limit = 600; // 10min

		static $attempts_reset_limit = 3600; // 1 hours

		protected $actions = array();

		public function __construct() {
			if ( ! self::$enabled_rate_limit ) {
				return;
			}

			$this->create_db();
			add_filter( 'authenticate', array( $this, 'bb_authenticate' ), 99999, 3 );
		}

		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function create_db() {
			$sql             = array();
			$wpdb            = $GLOBALS['wpdb'];
			$charset_collate = $wpdb->get_charset_collate();

			$table_name       = "{$wpdb->base_prefix}bb_api_rate_limit_status";
			self::$table_name = $table_name;

			// Table already exists, so maybe upgrade instead?
			$table_exists = $wpdb->query( "SHOW TABLES LIKE '{$table_name}';" ); // phpcs:ignore
			if ( ! $table_exists ) {
				$sql[] = "CREATE TABLE {$table_name} (
					id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					identity_type varchar(25) NOT NULL,
					ip_address varchar(40),
					user_id bigint(20) UNSIGNED,
					action varchar(60) NOT NULL,
					user_agent varchar(255),
					ip_ua_hash varchar(255),
					no_of_attempts int(3) NOT NULL DEFAULT 0,
					is_blocked tinyint(1) NOT NULL DEFAULT 0,
					block_expiry_date datetime NULL default '0000-00-00 00:00:00',
					last_attempt_date datetime NOT NULL,
					PRIMARY KEY  (id),
					KEY identity_type (identity_type),
					KEY ip_address (ip_address),
					KEY user_id (user_id),
					KEY user_agent (user_agent),
					KEY is_blocked (is_blocked),
					KEY ip_ua_hash (ip_ua_hash),
					KEY action (action),
					KEY block_expiry_date (block_expiry_date),
					KEY last_attempt_date (last_attempt_date)
				) {$charset_collate};";
			}

			if ( ! empty( $sql ) ) {
				// Ensure that dbDelta() is defined.
				if ( ! function_exists( 'dbDelta' ) ) {
					require_once ABSPATH . 'wp-admin/includes/upgrade.php';
				}

				dbDelta( $sql );
			}
		}

		public function get_ip() {
			$ip = '';
			if ( isset( $_SERVER ) ) {
				$sever_vars = array( 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' );
				foreach ( $sever_vars as $var ) {
					if ( isset( $_SERVER[ $var ] ) && ! empty( $_SERVER[ $var ] ) ) {
						if ( filter_var( $_SERVER[ $var ], FILTER_VALIDATE_IP ) ) {
							$ip = $_SERVER[ $var ];
							break;
						} else { /* if proxy */
							$ip_array = explode( ',', $_SERVER[ $var ] );
							if ( is_array( $ip_array ) && ! empty( $ip_array ) && filter_var( $ip_array[0], FILTER_VALIDATE_IP ) ) {
								$ip = $ip_array[0];
								break;
							}
						}
					}
				}
			}

			return $ip;
		}

		public function get_ua() {
			return ! empty( $_SERVER['HTTP_USER_AGENT'] )
				? mb_substr( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ), 0, 254 )
				: '';
		}

		public function register_action( $args ) {
			$r = wp_parse_args(
				$args,
				array(
					'action'               => '',
					'action_label'         => '',
					'identity_type'        => 'ip_address',
					'allowed_attempts'     => self::$allowed_attempts,
					'attempts_time_limit'  => self::$attempts_time_limit,
					'attempts_reset_limit' => self::$attempts_reset_limit,
				)
			);

			$r['action'] = str_replace( '-', '_', sanitize_title( $r['action'] ) );

			if (
				empty( $r['action'] ) ||
				empty( $r['identity_type'] ) ||
				! in_array( $r['identity_type'], array( 'ip_address', 'user_id' ), true ) ||
				array_key_exists( $r['action'], $this->actions )
			) {
				return;
			}

			$this->actions[ $r['action'] ] = $r;
		}

		public function get_action( $action ) {
			return $this->actions[ $action ] ?? false;
		}

		public function get_identity_type( $action ) {
			$action = $this->get_action( $action );
			if ( empty( $action ) ) {
				return false;
			}

			return $action['identity_type'];
		}

		public function get_allowed_attempts( $action ) {
			$action = $this->get_action( $action );
			if ( empty( $action ) ) {
				return false;
			}

			return ! empty( $action['allowed_attempts'] ) ? $action['allowed_attempts'] : self::$allowed_attempts;
		}

		public function get_attempts_time_limit( $action ) {
			$action = $this->get_action( $action );
			if ( empty( $action ) ) {
				return false;
			}

			return ! empty( $action['attempts_time_limit'] ) ? $action['attempts_time_limit'] : self::$attempts_time_limit;
		}

		public function get_attempts_reset_limit( $action ) {
			$action = $this->get_action( $action );
			if ( empty( $action ) ) {
				return false;
			}

			return ! empty( $action['attempts_reset_limit'] ) ? $action['attempts_reset_limit'] : self::$attempts_reset_limit;
		}

		public function update_attempt( $action, $value, $data = array() ) {
			global $wpdb;

			if (
				empty( $value ) ||
				empty( $action )
			) {
				return;
			}

			$action_data = $this->get_action( $action );

			if ( empty( $action_data ) ) {
				return;
			}

			$identity_type = $this->get_identity_type( $action );

			$agent      = $this->get_ua();
			$ip_ua_hash = md5( $value . $agent );

			$table       = self::$table_name;
			$sql         = "SELECT * FROM `{$table}` WHERE `identity_type` = %s AND `action` = %s";
			$placeholder = array(
				$identity_type,
				$action,
			);

			if ( $identity_type === 'ip_address' ) {
				$sql          .= ' AND `{$identity_type}` = %s';
				$sql          .= ' AND `ip_ua_hash` = %s';
				$placeholder[] = $value;
				$placeholder[] = $ip_ua_hash;
			} else {
				$value         = (int) $value;
				$sql          .= ' AND `{$identity_type}` = %d';
				$placeholder[] = $value;
			}

			$attempt = $wpdb->get_row(
				$wpdb->prepare( $sql, $placeholder ),
				ARRAY_A
			);

			if ( ! empty( $attempt ) ) {
				$last_attempt    = $attempt['last_attempt_date'];
				$last_attempt    = strtotime( $last_attempt );
				$current_attempt = bp_core_current_time();
				$current_attempt = strtotime( $current_attempt );

				if ( ( $current_attempt - $last_attempt ) > $this->get_attempts_time_limit( $action ) ) {
					$this->reset_attempt( $attempt['id'] );
					$attempt = array();
				}
			}

			if ( empty( $attempt ) ) {
				$data_args = array(
					'identity_type'     => $identity_type,
					$identity_type      => $value,
					'action'            => $action,
					'user_agent'        => $agent,
					'ip_ua_hash'        => $ip_ua_hash,
					'no_of_attempts'    => 1,
					'is_blocked'        => 0,
					'last_attempt_date' => bp_core_current_time(),
				);

				if ( $identity_type === 'ip_address' ) {
					$data_args['user_agent'] = $agent;
					$data_args['ip_ua_hash'] = $ip_ua_hash;
				}

				$wpdb->insert(
					$table,
					$data_args,
				);
			} else {
				$no_of_attempts        = $attempt['no_of_attempts'] + 1;
				$block                 = $no_of_attempts >= $this->get_allowed_attempts( $action );
				$block_expiration_time = $attempt['block_expiry_date'];
				if ( $block ) {
					$block_expiration_time = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $this->get_attempts_reset_limit( $action ) );
				}

				$wpdb->update(
					$table,
					array(
						'no_of_attempts'    => $no_of_attempts,
						'is_blocked'        => $block,
						'block_expiry_date' => $block_expiration_time,
						'last_attempt_date' => bp_core_current_time(),
					),
					array(
						'id' => $attempt['id'],
					)
				);
			}
		}

		public function reset_attempt( $limit_status_id ) {
			if ( empty( $limit_status_id ) ) {
				return;
			}

			global $wpdb;

			$table = self::$table_name;
			$wpdb->delete(
				$table,
				array(
					'id' => $limit_status_id,
				)
			);
		}

		public function ip_blocked( $ip, $action, $ua ) {
			$table = self::$table_name;

			$ip_ua_hash = md5( $ip . $ua );
			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `identity_type` = %s AND `ip_address` = %s AND `action` = %s AND `is_blocked` = 1 AND `ip_ua_hash` = %s",
				'ip_address',
				$ip,
				$action,
				$ip_ua_hash
			);

			return $wpdb->get_row( $sql );
		}

		public function is_user_blocked( $user_id, $action ) {
			$table = self::$table_name;

			global $wpdb;
			$sql = $wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `identity_type` = %s AND `user_id` = %d AND `action` = %s AND `is_blocked` = 1",
				'user_id',
				$user_id,
				$action
			);

			return $wpdb->get_row( $sql );
		}

		public function bb_whitelist_ips() {
			if ( ! defined( 'BB_RATE_WHITELIST_IPS' ) ) {
				return array();
			}

			return array_filter(
				BB_RATE_WHITELIST_IPS,
				function ( $ip ) {
					return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
				}
			);
		}

		public function bb_whitelist_user_ids() {
		}

		public function bb_blacklist_ips() {
			if ( ! defined( 'BB_RATE_BLACKLIST_IPS' ) ) {
				return array();
			}

			return array_filter(
				BB_RATE_BLACKLIST_IPS,
				function ( $ip ) {
					return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
				}
			);
		}

		public function bb_blacklist_user_ids() {
		}

		public function get_attempt_reset_time( $unblock_date ) {
			/* time difference */

			$time_diff = strtotime( $unblock_date ) - current_time( 'timestamp' );

			/* time limit for blocking has been exhausted or unidentified */
			if ( $time_diff <= 0 ) {
				$string = __( 'some time. Try to reload the page. Perhaps, you already have been unlocked', 'buddyboss' );
				lmtttmpts_reset_block();

				return $string;
			}

			/* less then 1 months */
			if ( $time_diff > 0 && $time_diff < 2635200 ) {
				$weeks  = intval( $time_diff / 604800 );
				$string = ( 0 < $weeks ? '&nbsp;' . $weeks . '&nbsp;' . _n( 'week', 'weeks', $weeks, 'buddyboss' ) : '' );
				$sum    = $weeks * 604800;

				$days    = intval( ( $time_diff - $sum ) / 86400 );
				$string .= ( 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'buddyboss' ) : '' );
				$sum    += $days * 86400;

				$hours   = intval( ( $time_diff - $sum ) / 3600 );
				$string .= ( 0 < $hours ? '&nbsp;' . $hours . '&nbsp;' . _n( 'hour', 'hours', $hours, 'buddyboss' ) : '' );
				$sum    += $hours * 3600;

				$minutes = intval( ( $time_diff - $sum ) / 60 );
				$string .= ( 0 < $minutes ? '&nbsp;' . $minutes . '&nbsp;' . _n( 'minute', 'minutes', $minutes, 'buddyboss' ) : '' );
				$sum    += $minutes * 60;

				$seconds = $time_diff - $sum;
				$string .= ( 0 < $seconds ? '&nbsp;' . $seconds . '&nbsp;' . _n( 'second', 'seconds', $seconds, 'buddyboss' ) : '' );

				return $string;
			}

			/* from 1 to 6 months */
			if ( $time_diff >= 2635200 && $time_diff < 15768000 ) {
				$months      = intval( $time_diff / 2635200 );
				$days        = $time_diff % 2635200;
				$days_string = 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'buddyboss' ) : '';

				return $months . '&nbsp;' . _n( 'month', 'months', $months, 'buddyboss' ) . $days;
			}

			/* from 6 to 12 months */
			if ( $time_diff >= 15768000 && $time_diff < 31536000 ) {
				return round( $time_diff / 15768000, 2 ) . '&nbsp;' . __( 'months', 'buddyboss' );
			}

			/* more than one year */
			if ( $time_diff >= 31536000 ) {
				$years = round( $time_diff / 31536000, 2 );

				return $years . '&nbsp;' . _n( 'year', 'years', $years, 'buddyboss' );
			}

			return false;
		}

		public function bb_authenticate( $user, $username, $password ) {
			/* get user`s IP */
			$ip = $this->get_ip();

			// check its white listed.
			if ( $ip && in_array( $ip, $this->bb_whitelist_ips(), true ) ) {
				return $user;
			}

			// check its black listed.
			if ( $ip && in_array( $ip, $this->bb_blacklist_ips(), true ) ) {
				$user  = new WP_Error();
				$error = __( "You've been added to deny list. Please contact website administrator.", 'buddyboss' );
				$error = wp_specialchars_decode( $error, ENT_COMPAT );
				$user->add( 'bb_blacklisted_ip', $error );

				return $user;
			}

			$ua     = $this->get_ua();
			$action = 'login';

			/* check if ip in blocked list */
			$blocked = $this->ip_blocked( $ip, $action, $ua );
			if ( ! empty( $blocked ) ) {
				$block_till =
					! isset( $blocked->block_expiry_date ) ||
					is_null( $blocked->block_expiry_date )
						?
						date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + $this->get_attempts_reset_limit( $action ) )
						:
						$blocked->block_expiry_date;

				if ( ! is_wp_error( $user ) ) {
					$user = new WP_Error();
				}

				$error = sprintf(
					__( 'Too many failed attempts. You have been blocked until %s.', 'buddyboss' ),
					$this->get_attempt_reset_time( $block_till )
				);
				$error = wp_specialchars_decode( $error, ENT_COMPAT );
				$user->add( 'bb_attempt_blocked', $error );

				return $user;
			}

			$this->update_attempt( $action, $ip );

			return $user;
		}
	}
}
