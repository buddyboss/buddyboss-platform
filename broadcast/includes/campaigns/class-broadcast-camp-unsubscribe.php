<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles email unsubscribes for Broadcast Campaigns.
 */
class Broadcast_Camp_Unsubscribe {

	const QUERY_VAR = 'broadcast_unsubscribe';

	public static function init() {
		add_action( 'init',              array( __CLASS__, 'add_rewrite_rules' ) );
		add_filter( 'query_vars',        array( __CLASS__, 'add_query_var' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle_unsubscribe' ) );
	}

	public static function add_rewrite_rules() {
		add_rewrite_rule(
			'^broadcast-unsubscribe/([a-z0-9]+)/?$',
			'index.php?' . self::QUERY_VAR . '=$matches[1]',
			'top'
		);
	}

	public static function add_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	public static function handle_unsubscribe() {
		$token = get_query_var( self::QUERY_VAR );
		if ( ! $token ) {
			return;
		}

		$email = self::email_from_token( sanitize_text_field( $token ) );

		if ( ! $email || ! is_email( $email ) ) {
			wp_die(
				esc_html__( 'Invalid or expired unsubscribe link.', 'broadcast' ),
				esc_html__( 'Unsubscribe', 'broadcast' ),
				array( 'response' => 400 )
			);
		}

		self::unsubscribe( $email );
		self::show_confirmation( $email );
		exit;
	}

	public static function unsubscribe( $email ) {
		global $wpdb;

		$email = sanitize_email( $email );
		if ( ! is_email( $email ) ) {
			return;
		}

		$table = $wpdb->prefix . 'broadcast_camp_unsubscribes';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM `{$table}` WHERE email = %s",
			$email
		) );

		if ( ! $exists ) {
			$wpdb->insert( $table, array(
				'email'           => $email,
				'unsubscribed_at' => current_time( 'mysql' ),
			) );
		}
	}

	public static function resubscribe( $email ) {
		global $wpdb;
		$table = $wpdb->prefix . 'broadcast_camp_unsubscribes';
		$wpdb->delete( $table, array( 'email' => sanitize_email( $email ) ) );
	}

	public static function is_unsubscribed( $email ) {
		global $wpdb;
		$table = $wpdb->prefix . 'broadcast_camp_unsubscribes';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM `{$table}` WHERE email = %s",
			sanitize_email( $email )
		) );
	}

	public static function get_url( $email ) {
		$token = self::token_for_email( $email );
		return home_url( '/broadcast-unsubscribe/' . $token . '/' );
	}

	private static function token_for_email( $email ) {
		$secret = defined( 'AUTH_KEY' ) ? AUTH_KEY : wp_salt( 'auth' );
		return substr( hash_hmac( 'sha256', strtolower( sanitize_email( $email ) ), $secret ), 0, 32 );
	}

	private static function email_from_token( $token ) {
		if ( strlen( $token ) !== 32 ) {
			return false;
		}

		$users = get_users( array( 'fields' => array( 'user_email' ), 'number' => -1 ) );
		foreach ( $users as $user_obj ) {
			if ( self::token_for_email( $user_obj->user_email ) === $token ) {
				return $user_obj->user_email;
			}
		}

		return false;
	}

	private static function show_confirmation( $email ) {
		$site_name = get_bloginfo( 'name' );
		?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width,initial-scale=1">
	<title><?php echo esc_html( sprintf( __( 'Unsubscribed — %s', 'broadcast' ), $site_name ) ); ?></title>
	<style>
		body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f3f4f6;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
		.card{background:#fff;border-radius:12px;padding:48px 40px;max-width:480px;text-align:center;box-shadow:0 4px 24px rgba(0,0,0,.08)}
		.icon{font-size:48px;margin-bottom:16px}
		h1{margin:0 0 12px;font-size:24px;color:#111827}
		p{color:#6b7280;line-height:1.6;margin:0 0 24px}
		a{color:#2271b1}
	</style>
</head>
<body>
<div class="card">
	<div class="icon">✅</div>
	<h1><?php esc_html_e( 'You\'ve been unsubscribed', 'broadcast' ); ?></h1>
	<p>
		<?php echo esc_html( sprintf(
			/* translators: %1$s: email address, %2$s: site name */
			__( '%1$s has been removed from all campaign emails from %2$s.', 'broadcast' ),
			$email,
			$site_name
		) ); ?>
	</p>
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( '← Back to site', 'broadcast' ); ?></a>
</div>
</body>
</html>
		<?php
	}
}
