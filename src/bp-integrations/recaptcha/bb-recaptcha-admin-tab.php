<?php
/**
 * Recaptcha integration admin tab
 *
 * @since   BuddyBoss [BBVERSION]
 * @package BuddyBoss\Recaptcha
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Recaptcha integration admin tab class.
 *
 * @since BuddyBoss [BBVERSION]
 */
class BB_Recaptcha_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function initialize() {
		$this->tab_order       = 53;
		$this->current_section = 'bb_recaptcha-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'admin_setting_icons' ), 10, 2 );
	}

	/**
	 * Recaptcha Integration is active?
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bb_recaptcha_integration_is_active', true );
	}

	/**
	 * Recaptcha integration tab scripts.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_admin_script() {

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bb-recaptcha' === $active_tab ) {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( 'bb-recaptcha-admin', bb_recaptcha_integration_url( '/assets/css/bb-recaptcha-admin' . $rtl_css . $min . '.css' ), false, buddypress()->version );

			wp_enqueue_script( 'bb-recaptcha-admin', bb_recaptcha_integration_url( '/assets/js/bb-recaptcha-admin' . $min . '.js' ), false, buddypress()->version );
			wp_localize_script(
				'bb-recaptcha-admin',
				'bbRecaptcha',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
				)
			);
		}

		parent::register_admin_script();
	}

	/**
	 * Register setting fields for recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;
			$notice            = ! empty( $section['notice'] ) ? $section['notice'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback, $notice );

			// Loop through fields for this section.
			foreach ( (array) $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array $settings Settings sections for recaptcha integration.
	 */
	public function get_settings_sections() {

		$status      = 'not-connected';
		$status_text = __( 'Not Connected', 'buddyboss' );
		$html        = '<div class="bb-recaptcha-status">' .
			'<span class="status-line ' . esc_attr( $status ) . '">' . esc_html( $status_text ) . '</span>' .
		'</div>';

		$settings = array(
			'bb_recaptcha_versions' => array(
				'page'              => 'recaptcha',
				'title'             => __( 'reCAPTCHA', 'buddyboss' ) . $html,
				'tutorial_callback' => array( $this, 'setting_callback_recaptcha_tutorial' ),
				'notice'            => sprintf(
				/* translators: recaptcha link */
					__( 'Check reCAPTCHA %s for usage statistics and monitor its performance. Adjust settings if necessary to maintain security.', 'buddyboss' ),
					'<a href="#" target="_blank">' . esc_html__( 'Admin Console', 'buddyboss' ) . '</a>'
				),
			),
			'bb_recaptcha_settings' => array(
				'page'              => 'recaptcha',
				'title'             => __( 'reCAPTCHA Settings', 'buddyboss' ),
				'tutorial_callback' => array( $this, 'setting_callback_recaptcha_tutorial' ),
			),
		);

		return $settings;
	}

	/**
	 * Get setting fields for section in recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in recaptcha integration false otherwise.
	 */
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return array $fields setting fields for pusher integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bb_recaptcha_versions'] = array(
			'information'             => array(
				'title'             => esc_html__( 'Information', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_information' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header' ),
			),
			'versions'                => array(
				'title'             => esc_html__( 'Versions', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_versions' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-site-key'   => array(
				'title'             => __( 'Site Key', 'buddyboss' ),
				'callback'          => array( $this, 'settings_callback_recaptcha_site_key' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-secret-key' => array(
				'title'             => __( 'Secret Key', 'buddyboss' ),
				'callback'          => array( $this, 'settings_callback_recaptcha_secret_key' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-verify'     => array(
				'title'             => esc_html__( 'Verify', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_recaptcha_verify' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header field-button' ),
			),
		);

		$fields['bb_recaptcha_settings'] = array(
			'bb-recaptcha-score-threshold' => array(
				'title'             => esc_html__( 'Score Threshold', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_score_threshold' ),
				'sanitize_callback' => 'absint',
				'args'              => array(),
			),
			'bb-recaptcha-enabled-for'     => array(
				'title'    => esc_html__( 'Enabled For', 'buddyboss' ),
				'callback' => array( $this, 'setting_callback_enabled_for' ),
				'args'     => array(),
			),
			'bb-recaptcha-allow-bypass'    => array(
				'title'             => ' ',
				'callback'          => array( $this, 'setting_callback_allow_bypass' ),
				'sanitize_callback' => 'absint',
				'args'              => array(),
			),
			'bb-recaptcha-language-code'   => array(
				'title'             => esc_html__( 'Language Code', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_language_code' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-recaptcha-conflict-mode'   => array(
				'title'    => esc_html__( 'No-Conflict Mode', 'buddyboss' ),
				'callback' => array( $this, 'setting_callback_conflict_mode' ),

				'args' => array(),
			),
			'bb-recaptcha-exclude-ip'      => array(
				'title'             => esc_html__( 'Exclude IP', 'buddyboss' ),
				'callback'          => array( $this, 'setting_callback_exclude_ip' ),
				'sanitize_callback' => 'sanitize_textarea_field',
				'args'              => array(),
			),
		);

		return $fields;
	}

	/**
	 * Link to Recaptcha Settings tutorial.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setting_callback_recaptcha_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php
				echo esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'    => 'bp-help',
								'article' => '125826',
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
	 * Callback fields for recaptcha information.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @return void
	 */
	public function setting_callback_recaptcha_information() {
		echo '<div class="show-full-width">' .
		     sprintf(
		     /* translators: recaptcha link */
			     esc_html__( 'Enter your %s to integrate fraud, spam, and abuse protection into your website.', 'buddyboss' ),
			     '<a href="#" target="_blank">' . esc_html__( 'Google reCAPTCHA API keys', 'buddyboss' ) . '</a>'
		     ) .
		     '</div>';
	}

	public function setting_callback_score_threshold() {
		?>
		<input name="bb_recaptcha[score_threshold]" id="bb-recaptcha-score-threshold" type="number" min="0" max="10" value="<?php echo esc_attr( bb_recaptcha_score_threshold() ); ?>" required />
		<p class="description">
			<?php
			esc_html_e( 'reCAPTCHA v3 provides a score for every request seamlessly, without causing user friction. Input a risk score between 1 and 10 in the field above to evaluate the probability of being identified as a bot.', 'buddyboss' );
			?>
		</p>
		<?php
	}

	public function setting_callback_enabled_for() {
		$actions = bb_recaptcha_actions();

		foreach ( $actions as $action => $setting ) {

			$disabled = ! empty( $setting['disabled'] ) ? ' disabled="disabled"' : '';
			$checked  = ! empty( $setting['enabled'] ) ? ' checked="checked"' : '';

			echo '<input id="recaptcha_' . esc_attr( $action ) . '" name="bb_recaptcha[enabled_for][' . esc_attr( $action ) . ']" type="checkbox" value="1" ' . $disabled . $checked . '/>' .
				'<label for="recaptcha_' . esc_attr( $action ) . '">' . esc_html( $setting['label'] ) . '</label><br /><br />';
		}

		echo '<p class="description">' . esc_html__( 'Select the pages to include in the reCAPTCHA submission. Make sure to Enable Registration if both registration and account activation are disabled.', 'buddyboss' ) . '</p>';
	}

	public function setting_callback_allow_bypass() {
		?>
		<input id="bb_recaptcha_allow_bypass" name="bb_recaptcha[allow_bypass]" type="checkbox" value="1" <?php checked( bp_get_option( 'bb_recaptcha_allow_bypass' ) ); ?> />
		<label for="bb_recaptcha_allow_bypass"><?php esc_html_e( 'Allow bypass, enter a 6 to 10-character string to customize your URL', 'buddyboss' ); ?></label>
		<input type="text" name="bb_recaptcha[bypass_text]" value="<?php esc_attr( bp_get_option( 'bb_recaptcha_bypass_text', '' ) ); ?>" placeholder="<?php esc_attr_e( 'stringxs', 'buddyboss' ); ?>">
		<p class="description"><?php esc_html_e( 'The bypass URL enables you to bypass reCAPTCHA in case of issues. We recommend keeping the link below securely stored for accessing your site.', 'buddyboss' ); ?></p>
		<div class="copy-toggle">
			<input type="text" readonly class="zoom-group-instructions-main-input is-disabled" value="domain.com/wp-login.php/?bypass_captcha=xxUNIQUE_STRINGXS">
			<span role="button" class="bb-copy-button hide-if-no-js" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Copy', 'buddyboss' ); ?>" data-copied-text="<?php esc_attr_e( 'Copied', 'buddyboss' ); ?>">
				<i class="bb-icon-f bb-icon-copy"></i>
			</span>
		</div>
		<?php
	}

	public function setting_callback_language_code() {
		$languages = bb_recaptcha_languages();
		$language  = bp_get_option( 'bb_recaptcha_language_code', 'en' );
		?>
		<select name="bb_recaptcha[language_code]" id="bb-recaptcha-language-code">
			<?php
			foreach ( $languages as $code => $label ) {
				echo '<option value="' . esc_attr( $code ) . '" ' . selected( $language, $code, false ) . '>' . esc_html( $label ) . '</option>';
			}
			?>
		</select>
		<p class="description"><?php esc_html_e( 'Select a language for reCAPTCHA when it is displayed.', 'buddyboss' ); ?></p>
		<?php
	}

	public function setting_callback_conflict_mode() {
		?>
		<input id="bb-recaptcha-conflict-mode" name="bb_recaptcha[conflict_mode]" type="checkbox" value="1" <?php checked( bp_get_option( 'bb_recaptcha_conflict_mode' ) ); ?> />
		<label for="bb-recaptcha-conflict-mode"><?php esc_html_e( 'Allow no-conflict mode to prevent compatibility conflicts', 'buddyboss' ); ?></label>
		<p class="description"><?php esc_html_e( 'When checked, other instances of reCAPTCHA are forcefully removed to prevent conflicts. Only enable this option if your site is experiencing compatibility issues or if instructed to do so by support.', 'buddyboss' ); ?></p>
		<?php
	}

	public function setting_callback_exclude_ip() {
		?>
		<label for="bb-recaptcha-exclude-ip"><?php esc_html_e( 'Enter the IP addresses that you want to skip from captcha submission. Enter one IP per line.', 'buddyboss' ); ?></label>
		<textarea rows="3" cols="50" name="bb_recaptcha[exclude_ip]" id="bb-recaptcha-exclude-ip"><?php echo esc_textarea( bp_get_option( 'bb_recaptcha_exclude_ip' ) ); ?></textarea>
		<?php
	}

	/**
	 * Added icon for the recaptcha admin settings.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return mixed|string
	 */
	public function admin_setting_icons( $meta_icon, $id = '' ) {
		if ( 'bb_recaptcha_versions' === $id || 'bb_recaptcha_settings' === $id ) {
			$meta_icon = 'bb-icon-i bb-icon-brand-google';
		}

		return $meta_icon;
	}

	/**
	 * Callback function for versions in Recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setting_callback_recaptcha_versions() {
		$enabled_for = 'recaptcha_v3';
		?>
		<div class="show-full-width">
			<input type="radio" name="bb_recaptcha[recaptcha_version]" id="recaptcha_v3" value="recaptcha_v3" <?php checked( $enabled_for, 'recaptcha_v3' ); ?>>
			<label for="recaptcha_v3"><?php esc_html_e( 'reCAPTCHA v3 (Recommended)', 'buddyboss' ); ?></label>
			<input type="radio" name="bb_recaptcha[recaptcha_version]" id="recaptcha_v2" value="recaptcha_v2" <?php checked( $enabled_for, 'recaptcha_v2' ); ?>>
			<label for="recaptcha_v2"><?php esc_html_e( 'reCAPTCHA v2', 'buddyboss' ); ?></label>
		</div>
		<?php
	}

	/**
	 * Callback function for site key in Recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function settings_callback_recaptcha_site_key() {
		?>
		<div class="password-toggle">
			<input name="bb_recaptcha[site_key]" id="bb-recaptcha-site-key" type="password" value="" aria-label="<?php esc_html_e( 'Site Key', 'buddyboss' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for secret key in Recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function settings_callback_recaptcha_secret_key() {
		?>
		<div class="password-toggle">
			<input name="bb_recaptcha[secret_key]" id="bb-recaptcha-secret-key" type="password" value="" aria-label="<?php esc_html_e( 'Secret Key', 'buddyboss' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for verify button in Recaptcha integration.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	public function setting_callback_recaptcha_verify() {
		?>
		<div class="show-verify">
			<button class="button recaptcha-verification"> <?php esc_html_e( 'Verify', 'buddyboss' ); ?></button>
		</div>
		<?php
	}
}
