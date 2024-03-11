<?php
class BB_Ratelimit {

	public static $instance = null;

	static $table_name = null;

	static $enabled_rate_limit = true;

	static $bb_rate_limit = 10;

	static $bb_rate_limit_seconds = 600; // 10min

	static $bb_rate_limit_reset_seconds = 3600; // 1 hours

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

		$table_name =  "{$wpdb->base_prefix}bb_security_lockouts";
		self::$table_name = $table_name;

			// Table already exists, so maybe upgrade instead?
		$table_exists = $wpdb->query( "SHOW TABLES LIKE '{$table_name}';" ); // phpcs:ignore
		if ( ! $table_exists ) {
			$sql[] = "CREATE TABLE {$table_name} (
					id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
					type varchar(25) NOT NULL,
					host varchar(40),
					user bigint(20) UNSIGNED,
					email varchar(60),
					request varchar(60) NOT NULL,
					attempts int(3) NOT NULL DEFAULT 0,
					block tinyint(1) NOT NULL DEFAULT 0,
					block_expire datetime NULL default '0000-00-00 00:00:00',
					last_attempt datetime NOT NULL,
					PRIMARY KEY  (id),
					KEY type (type),
					KEY host (host),
					KEY user (user),
					KEY email (email),
					KEY block (block),
					KEY request (request),
					KEY block_expire (block_expire),
					KEY last_attempt (last_attempt)
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
			foreach( $sever_vars as $var ) {
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
	public function add_lockout_to_db( $type, $value, $request ) {
		global $wpdb;

		$table = self::$table_name;
		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM `{$table}` WHERE `type` = %s AND `{$type}` = %s AND `request` = %s",
				$type,
				$value,
				$request,
			),
			ARRAY_A
		);

		if ( ! empty( $attempt ) ) {
			$last_attempt = $attempt['last_attempt'];
			$last_attempt = strtotime( $last_attempt );
			$current_attempt = bp_core_current_time();
			$current_attempt = strtotime( $current_attempt );

			if ( ( $current_attempt - $last_attempt ) > self::$bb_rate_limit_seconds ) {
				$this->delete_lockout_to_db( $attempt['id'] );
				$attempt = array();
			}
		}

		if ( empty( $attempt ) ) {
			$wpdb->insert(
				$table,
				array(
					'type'         => $type,
					$type          => $value,
					'request'      => $request,
					'attempts'     => 1,
					'block'        => 0,
					'last_attempt' => bp_core_current_time(),
				),
			);
		} else {
			$block = $attempt['attempts'] >= self::$bb_rate_limit;
			$block_expire = $attempt['block_expire'];
			if ( $block ) {
				$block_expire = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + self::$bb_rate_limit_reset_seconds );
			}

			$wpdb->update(
				$table,
				array(
					'attempts'     => $attempt['attempts'] + 1,
					'block'        => $block,
					'block_expire' => $block_expire,
					'last_attempt' => bp_core_current_time(),
				),
				array(
					'id' => $attempt['id'],
				)
			);
		}
	}

	public function delete_lockout_to_db( $lockout_id ) {
		if ( empty( $lockout_id ) ) {
			return;
		}

		global $wpdb;
		$table = self::$table_name;
		$wpdb->delete(
			$table,
			array(
				'id' => $lockout_id,
			)
		);
	}

	public function bb_is_ip_blocked( $ip, $request ) {
		$table = self::$table_name;
		global $wpdb;
		$sql = $wpdb->prepare(
			"SELECT * FROM `{$table}` WHERE `type` = %s AND `host` = %s AND `request` = %s AND `block` = 1",
			'host',
			$ip,
			$request
		);

		return $wpdb->get_row( $sql );
	}

	public function bb_authenticate($user, $username, $password) {

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

		/* check if ip in blocked list */
		$blocked = $this->bb_is_ip_blocked( $ip, 'login' );
		if ( ! empty( $blocked ) ) {
			$block_till =
				! isset( $blocked->block_expire ) ||
				is_null( $blocked->block_expire )
					?
					date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + self::$bb_rate_limit_reset_seconds )
					:
					$blocked->block_expire;

			if ( ! is_wp_error( $user ) ) {
				$user = new WP_Error();
			}

			$error = sprintf(
				__( 'Too many failed attempts. You have been blocked until %s.', 'buddyboss' ),
				$this->bb_block_time( $block_till )
			);
			$error = wp_specialchars_decode( $error, ENT_COMPAT );
			$user->add( 'bb_attempt_blocked', $error );

			return $user;
		}

		$this->add_lockout_to_db( 'host', $ip, 'login' );

		return $user;
	}

	public function bb_whitelist_ips() {
		if ( ! defined( 'BB_RATE_WHITELIST_IPS' ) ) {
			return array();
		}

		return array_filter( BB_RATE_WHITELIST_IPS, function ( $ip ) {
			return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
		} );
	}

	public function bb_whitelist_users() {
	}

	public function bb_whitelist_emails() {
	}

	public function bb_blacklist_ips() {
		if ( ! defined( 'BB_RATE_BLACKLIST_IPS' ) ) {
			return array();
		}

		return array_filter( BB_RATE_BLACKLIST_IPS, function ( $ip ) {
			return filter_var( $ip, FILTER_VALIDATE_IP ) !== false;
		} );
	}

	public function bb_blacklist_users() {
	}

	public function bb_blacklist_emails() {
	}

	public function bb_block_time( $unblock_date ) {
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

			$weeks   = intval( $time_diff / 604800 );
			$string  = ( 0 < $weeks ? '&nbsp;' . $weeks . '&nbsp;' . _n( 'week', 'weeks', $weeks, 'buddyboss' ) : '' );
			$sum     = $weeks * 604800;

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
			$months = intval( $time_diff / 2635200 );
			$days   = $time_diff % 2635200;
			$days_string = 0 < $days ? '&nbsp;' . $days . '&nbsp;' . _n( 'day', 'days', $days, 'buddyboss' ) : '';
			return $months .'&nbsp;' . _n( 'month', 'months', $months, 'buddyboss' ) . $days;
		}

		/* from 6 to 12 months */
		if ( $time_diff >= 15768000 && $time_diff < 31536000 )
			return round( $time_diff / 15768000, 2 ) . '&nbsp;' . __( 'months', 'buddyboss' );

		/* more than one year */
		if ( $time_diff >= 31536000 ) {
			$years = round( $time_diff / 31536000, 2 );
			return $years . '&nbsp;' . _n( 'year', 'years', $years, 'buddyboss' );
		}

		return false;
	}
}

