<?php
/**
 * BuddyBoss DRM Lockout
 *
 * Handles complete admin lockout when DRM_LOCKED status is reached.
 * Implements modal overlay, menu replacement, and AJAX license activation.
 *
 * @package BuddyBoss\Core\Admin\DRM
 * @since 3.0.0
 */

namespace BuddyBoss\Core\Admin\DRM;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * DRM Lockout class.
 */
class BB_DRM_Lockout {

	/**
	 * Initialize lockout system.
	 */
	public static function init() {
		$instance = new self();
		$instance->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 */
	private function setup_hooks() {
		// Only run in admin area.
		if ( ! is_admin() ) {
			return;
		}

		// Check if we're in locked state.
		if ( ! $this->is_locked() ) {
			return;
		}

		// Replace admin menu with locked version.
		add_action( 'admin_menu', array( $this, 'lock_menu' ), 999 );

		// Display lockout screen.
		add_action( 'admin_notices', array( $this, 'render_lockout_screen' ), 1 );

		// Block access to admin pages except allowed ones.
		add_action( 'admin_init', array( $this, 'maybe_block_access' ) );

		// Enqueue lockout assets.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_lockout_assets' ) );

		// AJAX handler for license activation.
		add_action( 'wp_ajax_bb_drm_activate_license', array( $this, 'ajax_activate_license' ) );
	}

	/**
	 * Check if system is in locked state.
	 *
	 * NOTE: Platform itself should NEVER lock out.
	 * Platform only provides centralized DRM infrastructure for add-ons.
	 * Only add-on plugins should be locked when their licenses are invalid.
	 *
	 * @return bool Always false - Platform never locks itself.
	 */
	private function is_locked() {
		// Platform itself never locks out.
		// Only add-on plugins (Pro, Gamification, etc.) should be locked.
		return false;
	}

	/**
	 * Replace admin menu with locked version.
	 *
	 * Removes all menu items except BuddyBoss settings page
	 * where users can activate their license.
	 */
	public function lock_menu() {
		// Only lock menu if system is actually locked.
		if ( ! $this->is_locked() ) {
			return;
		}

		global $menu, $submenu;

		// Store original menu for restoration (if needed).
		$original_menu = $menu;

		// Remove all menu items.
		$menu = array();

		// Add only BuddyBoss settings menu.
		$menu[2] = array(
			__( 'BuddyBoss', 'buddyboss' ),
			'manage_options',
			'buddyboss-settings',
			__( 'BuddyBoss', 'buddyboss' ),
			'menu-top',
			'buddyboss-settings',
			'dashicons-buddyboss',
		);

		// Add license activation submenu.
		$submenu['buddyboss-settings'] = array(
			array(
				__( 'Activate License', 'buddyboss' ),
				'manage_options',
				'buddyboss-settings',
			),
		);

		// Allow profile editing.
		$menu[70] = array(
			__( 'Profile' ),
			'read',
			'profile.php',
			'',
			'menu-top',
			'',
			'dashicons-admin-users',
		);
	}

	/**
	 * Maybe block access to admin pages.
	 *
	 * Redirects users away from locked pages to the license activation page.
	 */
	public function maybe_block_access() {
		// Only block if system is actually locked.
		if ( ! $this->is_locked() ) {
			return;
		}

		// Get current screen.
		global $pagenow;

		// Allowed pages that don't require redirection.
		$allowed_pages = array(
			'admin-ajax.php',
			'profile.php',
			'user-edit.php',
			'admin.php?page=buddyboss-settings',
		);

		// Allow AJAX requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Check if current page is allowed.
		$current_page = $pagenow;
		if ( isset( $_GET['page'] ) ) {
			$current_page .= '?page=' . sanitize_text_field( wp_unslash( $_GET['page'] ) );
		}

		foreach ( $allowed_pages as $allowed ) {
			if ( strpos( $current_page, $allowed ) !== false ) {
				return;
			}
		}

		// Redirect to license activation page.
		wp_safe_redirect( admin_url( 'admin.php?page=buddyboss-settings' ) );
		exit;
	}

	/**
	 * Enqueue lockout screen assets.
	 */
	public function enqueue_lockout_assets() {
		// Only enqueue if locked.
		if ( ! $this->is_locked() ) {
			return;
		}

		// Enqueue CSS.
		wp_enqueue_style(
			'bb-drm-lockout',
			plugins_url( 'assets/css/drm-lockout.css', dirname( __FILE__ ) ),
			array(),
			bb_get_version(),
			'all'
		);

		// Enqueue JavaScript.
		wp_enqueue_script(
			'bb-drm-lockout',
			plugins_url( 'assets/js/drm-lockout.js', dirname( __FILE__ ) ),
			array( 'jquery' ),
			bb_get_version(),
			true
		);

		// Localize script.
		wp_localize_script(
			'bb-drm-lockout',
			'bbDrmLockout',
			array(
				'ajaxUrl'              => admin_url( 'admin-ajax.php' ),
				'nonce'                => wp_create_nonce( 'bb_drm_activate_license' ),
				'activatingText'       => __( 'Activating license...', 'buddyboss' ),
				'successText'          => __( 'License activated successfully! Reloading...', 'buddyboss' ),
				'errorText'            => __( 'License activation failed. Please check your license key.', 'buddyboss' ),
				'invalidKeyText'       => __( 'Please enter a valid license key.', 'buddyboss' ),
				'networkErrorText'     => __( 'Network error. Please try again.', 'buddyboss' ),
				'purchaseUrl'          => 'https://www.buddyboss.com/pricing/',
				'supportUrl'           => 'https://www.buddyboss.com/support/',
				'renewUrl'             => 'https://www.buddyboss.com/my-account/',
			)
		);
	}

	/**
	 * Render lockout screen.
	 *
	 * Displays modal overlay with blurred background and license activation form.
	 */
	public function render_lockout_screen() {
		// Only render if locked.
		if ( ! $this->is_locked() ) {
			return;
		}

		// Determine lockout reason.
		$reason      = 'no_license';
		$days        = 0;
		$grace_end   = '';

		$no_license_event = BB_DRM_Event::latest( BB_DRM_Helper::NO_LICENSE_EVENT );
		if ( $no_license_event ) {
			$days = BB_DRM_Helper::days_elapsed( $no_license_event->created_at );
			if ( $days >= 30 ) {
				$reason     = 'no_license';
				$grace_end  = gmdate( 'F j, Y', strtotime( $no_license_event->created_at . ' +30 days' ) );
			}
		}

		$invalid_license_event = BB_DRM_Event::latest( BB_DRM_Helper::INVALID_LICENSE_EVENT );
		if ( $invalid_license_event ) {
			$days = BB_DRM_Helper::days_elapsed( $invalid_license_event->created_at );
			if ( $days >= 21 ) {
				$reason     = 'invalid_license';
				$grace_end  = gmdate( 'F j, Y', strtotime( $invalid_license_event->created_at . ' +21 days' ) );
			}
		}

		// Get messaging.
		$messages = $this->get_lockout_messages( $reason, $grace_end );

		?>
		<div id="bb-drm-lockout-overlay" class="bb-drm-lockout-overlay">
			<div class="bb-drm-lockout-modal">
				<div class="bb-drm-lockout-header">
					<span class="bb-drm-lockout-icon dashicons dashicons-lock"></span>
					<h2><?php echo esc_html( $messages['title'] ); ?></h2>
				</div>

				<div class="bb-drm-lockout-body">
					<div class="bb-drm-lockout-message">
						<p class="bb-drm-lockout-primary"><?php echo wp_kses_post( $messages['message'] ); ?></p>
						<?php if ( ! empty( $messages['submessage'] ) ) : ?>
							<p class="bb-drm-lockout-secondary"><?php echo wp_kses_post( $messages['submessage'] ); ?></p>
						<?php endif; ?>
					</div>

					<div class="bb-drm-lockout-form">
						<h3><?php esc_html_e( 'Activate Your License', 'buddyboss' ); ?></h3>
						<div class="bb-drm-lockout-input-group">
							<input
								type="text"
								id="bb-drm-license-key"
								class="bb-drm-license-input"
								placeholder="<?php esc_attr_e( 'Enter your license key', 'buddyboss' ); ?>"
								autocomplete="off"
							/>
							<button
								type="button"
								id="bb-drm-activate-btn"
								class="button button-primary bb-drm-activate-btn"
							>
								<?php esc_html_e( 'Activate License', 'buddyboss' ); ?>
							</button>
						</div>
						<div id="bb-drm-activation-message" class="bb-drm-activation-message"></div>
					</div>

					<div class="bb-drm-lockout-actions">
						<?php if ( 'no_license' === $reason ) : ?>
							<a href="https://www.buddyboss.com/pricing/" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'Purchase a License', 'buddyboss' ); ?>
							</a>
						<?php else : ?>
							<a href="https://www.buddyboss.com/my-account/" target="_blank" class="button button-secondary">
								<?php esc_html_e( 'Renew License', 'buddyboss' ); ?>
							</a>
						<?php endif; ?>
						<a href="https://www.buddyboss.com/support/" target="_blank" class="button button-secondary">
							<?php esc_html_e( 'Contact Support', 'buddyboss' ); ?>
						</a>
					</div>
				</div>
			</div>
		</div>

		<style>
			/* Blur everything except the lockout modal */
			body.bb-drm-locked > *:not(#bb-drm-lockout-overlay) {
				filter: blur(5px);
				pointer-events: none;
			}
		</style>

		<script>
			// Add locked class to body
			document.body.classList.add('bb-drm-locked');
		</script>
		<?php
	}

	/**
	 * Get lockout messages based on reason.
	 *
	 * @param string $reason    Lockout reason ('no_license' or 'invalid_license').
	 * @param string $grace_end Grace period end date.
	 * @return array Messages array with title, message, submessage.
	 */
	private function get_lockout_messages( $reason, $grace_end ) {
		if ( 'no_license' === $reason ) {
			return array(
				'title'      => __( 'License Required', 'buddyboss' ),
				'message'    => sprintf(
					/* translators: %s: grace period end date */
					__( 'Your grace period ended on <strong>%s</strong>. A valid license is required to continue using BuddyBoss Platform.', 'buddyboss' ),
					$grace_end
				),
				'submessage' => __( 'Please activate your license key below or purchase a new license to restore full functionality.', 'buddyboss' ),
			);
		} else {
			return array(
				'title'      => __( 'License Invalid or Expired', 'buddyboss' ),
				'message'    => sprintf(
					/* translators: %s: grace period end date */
					__( 'Your license expired or was invalidated. The grace period ended on <strong>%s</strong>.', 'buddyboss' ),
					$grace_end
				),
				'submessage' => __( 'Please renew your license or enter a valid license key to restore access to your site.', 'buddyboss' ),
			);
		}
	}

	/**
	 * Handle AJAX license activation.
	 */
	public function ajax_activate_license() {
		// Verify nonce.
		check_ajax_referer( 'bb_drm_activate_license', 'nonce' );

		// Check user capability.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'You do not have permission to activate licenses.', 'buddyboss' ),
				)
			);
		}

		// Get license key.
		if ( ! isset( $_POST['license_key'] ) || empty( $_POST['license_key'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please provide a license key.', 'buddyboss' ),
				)
			);
		}

		$license_key = sanitize_text_field( wp_unslash( $_POST['license_key'] ) );

		// Validate license key format.
		if ( strlen( $license_key ) < 10 ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid license key format.', 'buddyboss' ),
				)
			);
		}

		// Attempt activation using existing GroundLevel connector.
		$plugin_connector = new \BB_Plugin_Connector();

		// Store license key.
		$plugin_id = defined( 'PLATFORM_EDITION' ) ? PLATFORM_EDITION : 'buddyboss-platform';
		update_option( $plugin_id . '_license_key', $license_key );

		// Attempt activation.
		$result = $plugin_connector->activateLicense( $license_key );

		if ( $result && isset( $result['success'] ) && $result['success'] ) {
			// Activation successful - trigger license activated hook.
			do_action( $plugin_id . '_license_activated' );

			wp_send_json_success(
				array(
					'message' => __( 'License activated successfully!', 'buddyboss' ),
				)
			);
		} else {
			// Activation failed.
			$error_message = __( 'License activation failed. Please check your license key and try again.', 'buddyboss' );

			if ( isset( $result['message'] ) ) {
				$error_message = $result['message'];
			}

			wp_send_json_error(
				array(
					'message' => $error_message,
				)
			);
		}
	}
}
