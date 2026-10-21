<?php
/**
 * BuddyBoss Integrations marketplace — plugin activate/deactivate AJAX.
 *
 * Plugin *installation* is done client-side via WordPress core's wp.updates
 * (the `install-plugin` action), which carries its own nonce + `install_plugins`
 * capability — we do not reimplement it. This class only adds the activate and
 * deactivate actions core's updater does not cover.
 *
 * Security model:
 *  - Capability is checked BEFORE the nonce (project convention).
 *  - The target plugin file is resolved server-side from the wordpress.org slug
 *    against the installed plugins — the client never supplies a file path, so an
 *    admin can only act on a plugin that is actually installed (no path injection).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Activate / deactivate handlers for the Integrations marketplace.
 *
 * @since BuddyBoss 3.1.0
 */
class BB_Admin_Integrations_Ajax {

	/**
	 * Nonce action shared by the integrations plugin actions.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @var string
	 */
	const NONCE = 'bb_integrations_plugin';

	/**
	 * Register the AJAX handlers.
	 *
	 * @since BuddyBoss 3.1.0
	 */
	public function __construct() {
		add_action( 'wp_ajax_bb_integrations_activate_plugin', array( $this, 'activate_plugin' ) );
		add_action( 'wp_ajax_bb_integrations_deactivate_plugin', array( $this, 'deactivate_plugin' ) );
	}

	/**
	 * Activate an installed wordpress.org plugin by slug.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function activate_plugin() {
		$file = $this->validate_request();

		// $silent = false so the plugin's activation hooks run (proper setup).
		$result = activate_plugin( $file );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success(
			array(
				'file'   => $file,
				'active' => true,
			)
		);
	}

	/**
	 * Deactivate an installed wordpress.org plugin by slug.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return void
	 */
	public function deactivate_plugin() {
		$file = $this->validate_request();

		deactivate_plugins( $file );

		wp_send_json_success(
			array(
				'file'   => $file,
				'active' => false,
			)
		);
	}

	/**
	 * Shared validation: capability, nonce, slug → installed-file resolution.
	 *
	 * Sends a JSON error and exits on any failure; otherwise returns the resolved
	 * plugin file. Capability runs before the nonce per project convention.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @return string The validated `folder/file.php` plugin path.
	 */
	private function validate_request() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Sorry, you are not allowed to manage plugins.', 'buddyboss' ) ),
				403
			);
		}

		check_ajax_referer( self::NONCE, 'nonce' );

		$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';
		if ( empty( $slug ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing plugin.', 'buddyboss' ) ), 400 );
		}

		$file = $this->resolve_plugin_file( $slug );
		if ( empty( $file ) ) {
			wp_send_json_error( array( 'message' => __( 'Plugin is not installed.', 'buddyboss' ) ), 404 );
		}

		return $file;
	}

	/**
	 * Resolve a wordpress.org slug to its installed plugin file.
	 *
	 * Matches by plugin folder name (which equals the wordpress.org slug for the
	 * vast majority of plugins). Returns '' when nothing installed matches, so the
	 * caller refuses the request.
	 *
	 * By design this resolves ANY installed plugin folder, not only marketplace
	 * entries — the `activate_plugins` capability checked in validate_request() is
	 * the real authorization gate, and a user with that capability can already
	 * toggle any plugin from the core Plugins screen. Scoping to the marketplace
	 * dataset would add no security (same capability) and isn't worth threading the
	 * upstream list through this handler.
	 *
	 * @since BuddyBoss 3.1.0
	 *
	 * @param string $slug The wordpress.org plugin slug.
	 * @return string The `folder/file.php` path, or '' if not installed.
	 */
	private function resolve_plugin_file( $slug ) {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		foreach ( get_plugins() as $file => $data ) {
			if ( dirname( $file ) === $slug ) {
				return $file;
			}
		}
		return '';
	}
}

// Initialize.
new BB_Admin_Integrations_Ajax();
