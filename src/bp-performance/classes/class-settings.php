<?php
/**
 * BuddyBoss Performance Settings.
 *
 * @package BuddyBoss\Performance\Settings
 */

namespace BuddyBoss\Performance;

/**
 * Settings class.
 */
class Settings {

	/**
	 * Class instance.
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Purge Nonce.
	 *
	 * @var string
	 */
	private static $purge_nonce;

	/**
	 * Setting key.
	 *
	 * @var string
	 */
	private static $option = '_bb_performance_settings';

	/**
	 * Class Instance.
	 *
	 * @return Settings
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Get purge cache nonce.
	 *
	 * @return false|string
	 */
	public static function get_purge_nonce() {
		if ( ! isset( self::$purge_nonce ) ) {
			self::$purge_nonce = wp_create_nonce( 'bbapp_cache_purge' );
		}

		return self::$purge_nonce;
	}

	/**
	 * Get Purge setting page url so we can use that to redirection back to that page after purge.
	 *
	 * @return mixed|void
	 */
	public static function get_performance_purge_url() {
		/**
		 * This filter allow us to support purge in different page.
		 */
		return apply_filters( 'performance_purge_url', admin_url( 'admin.php?1=1' ) );
	}

	/**
	 * Get components list for cache enabled.
	 *
	 * @return array[]
	 */
	public static function get_performance_components() {
		self::get_purge_nonce();

		$purge_url = self::get_performance_purge_url();

		return array(
			'buddyboss' => array(
				'title'     => __( 'BuddyBoss Platform', 'buddyboss' ),
				'purge_url' => $purge_url . '&group=bbplatform&component=all&nonce=' . self::$purge_nonce,
				'settings'  => array(
					'cache_bb_activity_feeds'     => array(
						'label'          => __( 'Activity Feeds', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Activity Feeds', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-activity&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_members'            => array(
						'label'          => __( 'Member Profiles', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Member Profiles', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-members&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_member_connections' => array(
						'label'          => __( 'Member Connections', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Member Connections', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-friends&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_social_groups'      => array(
						'label'          => __( 'Social Groups', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Social Groups', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-groups&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_private_messaging'  => array(
						'label'          => __( 'Private Messaging', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Private Messaging', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-messages&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_forum_discussions'  => array(
						'label'          => __( 'Forum Discussions', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Forum Discussions', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bbp-forums,bbp-topics,bbp-replies&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_notifications'      => array(
						'label'          => __( 'Notifications', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Notifications', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-notifications&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_media'              => array(
						'label'          => __( 'Photos', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Photos/Albums', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-media&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_document'           => array(
						'label'          => __( 'Documents', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Document Files/Folders', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-document&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_video'              => array(
						'label'          => __( 'Videos', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Videos', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bp-video&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
					'cache_bb_subscription'       => array(
						'label'          => __( 'Subscriptions', 'buddyboss' ),
						'label_checkbox' => __( 'Cache Subscriptions', 'buddyboss' ),
						'purge_url'      => $purge_url . '&group=bbplatform&component=bb-subscription&nonce=' . self::$purge_nonce,
						'type'           => 'checkbox',
						'value'          => true,
					),
				),
			),
		);
	}

	/**
	 * Get performance setting.
	 *
	 * @param string $group Cache group.
	 *
	 * @return array|mixed
	 */
	public static function get_settings( $group = 'default' ) {
		$settings      = get_option( self::$option, array() );
		$group_setting = isset( $settings[ $group ] ) ? $settings[ $group ] : array();
		if ( 'default' !== $group && empty( $group_setting ) ) {
			$group_setting = isset( $settings['default'] ) ? $settings['default'] : array();
		}

		return $group_setting;
	}

	/**
	 * Allow to store performance setting.
	 *
	 * @param array  $group_setting Settings.
	 * @param string $group         Setting Group name.
	 *
	 * @return bool
	 */
	public static function save_settings( $group_setting, $group = 'default' ) {
		$settings = get_option( self::$option, array() );
		if ( empty( $settings ) ) {
			$settings = array();
		}
		$settings[ $group ] = $group_setting;

		return update_option( self::$option, $settings );
	}

	/**
	 * Get Purge actions by group.
	 *
	 * @param string $group Setting Group name.
	 *
	 * @return array
	 */
	public function get_group_purge_actions( $group ) {
		$actions = array();
		switch ( $group ) {
			case 'bbplatform':
				$actions = array(
					'bp-members',
					'bp-notifications',
					'bp-groups',
					'bbp-forums',
					'bbp-topics',
					'bbp-replies',
					'bp-activity',
					'bp-messages',
					'bp-friends',
					'bp-media',
					'bp-document',
					'bp-video',
				);
				break;
		}

		/**
		 * Filter to set cache action by giving component.
		 * It'll help us to extent it in custom code or support BuddyBoss App related group purging
		 * $actions : Cache groups actions
		 * $group: component like platofrm, Learndash, BuddyBoss App etc
		 */
		return apply_filters( 'performance_group_purge_actions', $actions, $group );
	}

	/**
	 * Handle Purge cache event.
	 */
	public static function handle_purge_cache() {

		if ( ! empty( $_GET['cache_purge'] ) && 1 === (int) $_GET['cache_purge'] && empty( $_POST ) ) {
			add_action(
				'admin_notices',
				function () {
					echo '<div class="notice notice-success"><p>' . esc_html__( 'Cache Purge Successfully', 'buddyboss' ) . '</p></div>';
				}
			);
		}

		$purge_nonce = ( ! empty( $_GET['nonce'] ) ) ? wp_unslash( $_GET['nonce'] ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( wp_verify_nonce( $purge_nonce, 'bbapp_cache_purge' ) ) {

			$group      = ( ! empty( $_GET['group'] ) ) ? self::input_clean( wp_unslash( $_GET['group'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$components = ( ! empty( $_GET['component'] ) ) ? self::input_clean( wp_unslash( $_GET['component'] ) ) : ''; //phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			/**
			 * Handle group purge action.
			 */
			if ( 'all' === $components ) {
				$components = self::$instance->get_group_purge_actions( $group );
			} else {
				$components = explode( ',', $components );
			}

			if ( ! empty( $components ) ) {
				foreach ( $components as $component ) {
					Cache::instance()->purge_by_component( $component );
				}
				Cache::instance()->purge_by_component( 'bbapp-deeplinking' );
				$purge_url = self::get_performance_purge_url();
				wp_safe_redirect( $purge_url . '&cache_purge=1' );
				exit();
			}
		}
	}

	/**
	 * Clean variables using sanitize_text_field. Arrays are cleaned recursively.
	 * Non-scalar values are ignored.
	 *
	 * @param string|array $var Data to sanitize.
	 *
	 * @return string|array
	 */
	public static function input_clean( $var ) {
		if ( is_array( $var ) ) {
			return array_map( 'self::input_clean', $var );
		} else {
			return is_scalar( $var ) ? sanitize_text_field( $var ) : $var;
		}
	}
}
