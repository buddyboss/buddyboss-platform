<?php
/**
 * Core component classes.
 *
 * @package BuddyBoss
 * @since BuddyBoss 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_User_Settings
 */
final class BP_User_Settings {

	protected $messages = array();

	/**
	 * Get the instance of this class.
	 *
	 * @return Controller|null
	 */
	public static function instance() {
		static $instance = null;

		if ( null === $instance ) {
			$instance = new BP_User_Settings();
			$instance->hooks();
		}

		return $instance;
	}

	/**
	 *
	 */
	function hooks() {
		// Setup navigation.
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 99 );
		add_action( 'init', array( $this, 'submit' ), 99 );
		add_filter( 'bp_settings_admin_nav', array( $this, 'bp_gdpr_export_data_admin_nav' ), 11, 1 );
	}

	/**
	 * @param $wp_admin_nav
	 *
	 * @package BuddyBoss
	 * @since BuddyBoss 3.1.1
	 *
	 * @return array
	 */
	function bp_gdpr_export_data_admin_nav( $wp_admin_nav ) {
		// Setup the logged in user variables.
		$settings_link = trailingslashit( bp_loggedin_user_domain() . bp_get_settings_slug() );

		// Add the "Group Invites" subnav item.
		$wp_admin_nav[] = array(
			'parent' => 'my-account-' . buddypress()->settings->id,
			'id'     => 'my-account-' . buddypress()->settings->id . '-export',
			'title'  => _x( 'Export Data', 'Export Data main menu title', 'buddyboss' ),
			'href'   => trailingslashit( $settings_link . 'export/' ),
		);

		return $wp_admin_nav;
	}

	/**
	 *
	 * Register nav menu.
	 *
	 * @package BuddyBoss
	 * @since BuddyBoss 3.1.1
	 */
	function setup_nav() {
		// Determine user to use.
		if ( bp_displayed_user_domain() ) {
			$user_domain = bp_displayed_user_domain();
		} elseif ( bp_loggedin_user_domain() ) {
			$user_domain = bp_loggedin_user_domain();
		} else {
			return;
		}

		$slug          = bp_get_settings_slug();
		$settings_link = trailingslashit( $user_domain . $slug );

		$access = bp_core_can_edit_settings();

		$sub_nav[] = array(
			'name'            => __( 'Export Data', 'buddyboss' ),
			'slug'            => 'export',
			'parent_url'      => $settings_link,
			'parent_slug'     => $slug,
			'screen_function' => array( $this, 'export_data_screen' ),
			'position'        => 99,
			'user_has_access' => $access,
		);

		foreach ( $sub_nav as $nav ) {
			bp_core_new_subnav_item( $nav, 'members' );
		}

	}

	/**
	 * Register title and screen functions.
	 *
	 * @package BuddyBoss
	 * @since BuddyBoss 3.1.1
	 *
	 */
	function export_data_screen() {
		add_action( 'bp_template_title',
			function () {
				return __( 'Export Data', 'buddyboss' );
			} );
		add_action( 'bp_template_content', array( $this, 'export_data_page_render' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'members/single/plugins' ) );
	}

	/**
	 * Handle form submit request.
	 *
	 * @package BuddyBoss
	 * @since BuddyBoss 3.1.1
	 *
	 * @return bool
	 */
	function submit() {

		if ( isset( $_POST["buddyboss_data_export_request"] ) ) {

			if ( ! wp_verify_nonce( $_POST['buddyboss_data_export_request'], 'buddyboss_data_export_request' ) ) {
				wp_die( __( 'Sorry something went wrong, please try again.', 'buddyboss' ) );
			}

			if ( bp_core_can_edit_settings() ) {

				$user_id = bp_loggedin_user_id();

				$user       = get_userdata( $user_id );
				$request_id = wp_create_user_request( $user->data->user_email, 'export_personal_data' );

				if ( is_wp_error( $request_id ) ) {
					$this->messages['error'] = $request_id->get_error_message();

					return false;
				} elseif ( ! $request_id ) {
					$this->messages['error'] = __( 'Unable to initiate the data export request.', 'buddyboss' );

					return false;
				}

				wp_send_user_request( $request_id );

				$this->messages['success'] = __( 'Please check your email to confirm the data export request.', 'buddyboss' );

			}

		}

	}

	/**
	 * Function to create a frontend form.
	 *
	 * @package BuddyBoss
	 * @since BuddyBoss 3.1.1
	 *
	 */
	function export_data_page_render() {

		foreach ( $this->messages as $err_type => $err ) {
			?>

			<aside class="bp-feedback bp-messages bp-template-notice <?php echo esc_attr( $err_type ); ?>">
				<span class="bp-icon" aria-hidden="true"></span>
				<p><?php esc_html_e( $err,'buddyboss' ); ?></p>
			</aside>

			<?php
		}

		$main_heading = apply_filters( 'buddyboss_gdpr_heading_text', 'Request an export of your data' );
		$main_description = apply_filters( 'buddyboss_gdpr_description', 'You can download a copy of all data you have shared on this platform. Click the button below to get started. An email will be sent to you to verify the request. Then the site admin will review your request and if approved, an export file will be generated and emailed to you.' );
		$main_button_text = apply_filters( 'buddyboss_gdpr_button_text', 'Request Data Export' );

		?>

		<h3><?php esc_html_e( $main_heading, 'buddyboss' ); ?></h3>
		<p><?php esc_html_e( $main_description,'buddyboss' ); ?></p>

		<form method="post">
			<?php wp_nonce_field( 'buddyboss_data_export_request', 'buddyboss_data_export_request' ); ?>
			<div class="submit">
				<input id="submit" type="submit" name="request-submit" value="<?php esc_attr_e( $main_button_text, 'buddyboss' ); ?>" class="auto">
			</div>
		</form>

		<?php
	}

}
