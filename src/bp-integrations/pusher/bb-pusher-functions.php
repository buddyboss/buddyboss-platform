<?php
/**
 * Pusher integration helpers
 *
 * @since   [BBVERSION]
 * @package BuddyBoss\Pusher
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Pusher Integration path.
 *
 * @since [BBVERSION]
 *
 * @param string $path Path to pusher integration.
 */
function bb_pusher_integration_path( $path = '' ) {
	return trailingslashit( buddypress()->integration_dir ) . 'pusher/' . trim( $path, '/\\' );
}

/**
 * Returns Pusher Integration url.
 *
 * @since [BBVERSION]
 *
 * @param string $path Path to pusher integration.
 */
function bb_pusher_integration_url( $path = '' ) {
	return trailingslashit( buddypress()->integration_url ) . 'pusher/' . trim( $path, '/\\' );
}

/**
 * Checks if Pusher is enabled.
 *
 * @since [BBVERSION]
 *
 * @param int $default Default option for pusher enable or not.
 *
 * @return bool Is pusher enabled or not.
 */
function bb_pusher_is_enabled( $default = false ) {
	return (bool) apply_filters( 'bb_pusher_is_enabled', (bool) bp_get_option( 'bb-pusher-enabled', $default ) );
}

/**
 * Return the Pusher App ID.
 *
 * @since [BBVERSION]
 *
 * @return mixed|void
 */
function bb_pusher_app_id() {
	return apply_filters( 'bb_pusher_app_id', bp_get_option( 'bb-pusher-app-id', '' ) );
}

/**
 * Return the Pusher App Key.
 *
 * @since [BBVERSION]
 *
 * @return mixed|void
 */
function bb_pusher_app_key() {
	return apply_filters( 'bb_pusher_app_key', bp_get_option( 'bb-pusher-app-key', '' ) );
}

/**
 * Return the Pusher App Secret.
 *
 * @since [BBVERSION]
 *
 * @return mixed|void
 */
function bb_pusher_app_secret() {
	return apply_filters( 'bb_pusher_app_secret', bp_get_option( 'bb-pusher-app-secret', '' ) );
}

/**
 * Return the Pusher App Cluster.
 *
 * @since [BBVERSION]
 *
 * @return mixed|void
 */
function bb_pusher_app_cluster() {
	return apply_filters( 'bb_pusher_app_cluster', bp_get_option( 'bb-pusher-app-cluster', '' ) );
}

/**
 * Link to Pusher Settings tutorial.
 *
 * @since [BBVERSION]
 */
function bb_pusher_settings_tutorial() {
	?>
	<p>
		<a class="button" href="
		<?php
		echo esc_url(
			bp_get_admin_url(
				add_query_arg(
					array(
						'page'    => 'bp-help',
						'article' => '',
					),
					'admin.php'
				)
			)
		);
		?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
	</p>
	<?php
}

/**
 * Pusher Object.
 *
 * @since [BBVERSION]
 *
 * @return mixed
 *
 * @throws \GuzzleHttp\Exception\GuzzleException Client Exception.
 * @throws \Pusher\PusherException Pusher Exception.
 */
function bb_pusher() {
	static $bb_pusher = null;
	if (
		class_exists( 'Pusher\Pusher' ) &&
		bb_pusher_app_key() &&
		bb_pusher_app_secret() &&
		bb_pusher_app_id() &&
		bb_pusher_app_cluster()
	) {
		$bb_pusher = new Pusher\Pusher( bb_pusher_app_key(), bb_pusher_app_secret(), bb_pusher_app_id(), array( 'cluster' => bb_pusher_app_cluster() ) );
	}

	return $bb_pusher;
}
