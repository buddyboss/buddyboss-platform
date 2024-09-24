<?php
/**
 * Add admin performance settings page in Dashboard->BuddyBoss->Settings
 *
 * @package BuddyBoss\Core
 *
 * @since BuddyBoss 2.5.80
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Main performance settings class.
 *
 * @since BuddyBoss 2.5.80
 */
class BB_Admin_Setting_Performance extends BP_Admin_Setting_tab {

	/**
	 * Initial method for this class.
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function initialize() {
		$this->tab_label = esc_html__( 'Advanced', 'buddyboss' );
		$this->tab_name  = 'bp-performance';
		$this->tab_order = 90;
	}

	public function settings_save() {

		// Get old values for cpt and check if it disabled then keep it and later will save it.
		$bb_activity_load_type = isset( $_POST['bb_activity_load_type'] ) ? sanitize_text_field( wp_unslash( $_POST['bb_activity_load_type'] ) ) : '';
		bp_update_option( 'bb_activity_load_type', $bb_activity_load_type );

		parent::settings_save();
	}

	/**
	 * Register setting fields
	 *
	 * @since BuddyBoss 2.5.80
	 *
	 * @return void
	 */
	public function register_fields() {
		$this->add_section( 'bb_performance_general', __( 'General', 'buddyboss' ), '', 'bb_admin_performance_general_setting_tutorial' );
		$this->add_field( 'bb_ajax_request_page_load', __( 'Page requests', 'buddyboss' ), 'bb_admin_performance_setting_general_callback', 'intval' );


		if ( bp_is_active( 'activity' ) ) {
			$this->add_section( 'bb_performance_activity', __( 'Activity', 'buddyboss' ), '', 'bb_admin_performance_activity_setting_tutorial' );
			$this->add_field( 'bb_load_activity_per_request', __( 'Activity loading', 'buddyboss' ), 'bb_admin_performance_setting_activity_callback', 'intval' );
		}

		$this->add_section( 'bb_performance_usage', __( 'Usage', 'buddyboss' ), '', array( $this, 'bb_admin_performance_usage_setting_tutorial' ) );
		$this->add_field( 'bb_performance_usage_reporting', __( 'Disable usage reporting', 'buddyboss' ), array( $this, 'bb_admin_performance_setting_usage_callback' ), 'string' );

		/**
		 * Fires to register Performance tab settings fields and section.
		 *
		 * @since BuddyBoss 2.5.80
		 *
		 * @param Object $this BB_Admin_Setting_Performance.
		 */
		do_action( 'bb_admin_setting_performance_register_fields', $this );
	}

	/**
	 * Displays a tutorial link for usage settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_performance_usage_setting_tutorial() {
		?>
		<p>
			<a class="button" href="
		<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => 127427,
						),
						'admin.php'
					)
				)
			);
			?>
		"><?php esc_html_e( 'View Tutorial', 'buddyboss' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Outputs the usage reporting setting fields.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function bb_admin_performance_setting_usage_callback() {
		$bb_performance_usage_reporting = bp_get_option( 'bb_performance_usage_reporting', 'full' );
		?>
		<fieldset>
			<legend class="screen-reader-text">
				<span><?php echo __( 'Disable usage reporting', 'buddyboss' ); ?></span>
			</legend>
			<label for="full_reporting_feedback" class="">
				<input name="bb_performance_usage_reporting" id="full_reporting_feedback" type="radio" value="full" <?php checked( $bb_performance_usage_reporting, 'full' ); ?>/>
				<?php esc_html_e( 'Full reporting feedback', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'Full reporting allows us to pull your site setup and health data so we can
				 see what features and functions you are using including your information so that we can connect 
				 with you in the future about new upcoming features.', 'buddyboss' ); ?>
			</p>
			<br>
			<label for="anonymous_reporting" class="">
				<input name="bb_performance_usage_reporting" id="anonymous_reporting" type="radio" value="anonymous" <?php checked( $bb_performance_usage_reporting, 'anonymous' ); ?>/>
				<?php esc_html_e( 'Anonymous reporting', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'This allows us just to pull your site setup and health data so we can see 
				what features and functions you are using only with no contact information.', 'buddyboss' ); ?>
			</p>
			<br>
			<label for="no_reporting" class="">
				<input name="bb_performance_usage_reporting" id="no_reporting" type="radio" value="none" <?php checked( $bb_performance_usage_reporting, 'none' ); ?>/>
				<?php esc_html_e( 'None', 'buddyboss' ); ?>
			</label>
			<p class="description">
				<?php esc_html_e( 'This will turn off reporting completely, sharing no information back 
				to BuddyBoss.', 'buddyboss' ); ?>
			</p>
		</fieldset>
		<?php
	}
}

// Class instance.
return new BB_Admin_Setting_Performance();
