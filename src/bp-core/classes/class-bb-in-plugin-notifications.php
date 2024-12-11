<?php
/**
 * BuddyBoss In-Plugin Notifications.
 *
 * @since   BuddyBoss [BBVERSION]
 *
 * @package BuddyBoss/Core
 */

use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Retriever as IPNRetrieverService;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\Store;
use BuddyBossPlatform\GroundLevel\InProductNotifications\Services\View as IPNViewService;
use BuddyBossPlatform\GroundLevel\Mothership\Api\Request\Products;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'BB_In_Plugin_Notifications' ) ) {

	/**
	 * BB_In_Plugin_Notifications.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * Class for logging in-plugin notifications.
	 * Includes:
	 *     Notifications from our remote feed
	 *     Plugin-related notifications (i.e. - recent sales performances)
	 */
	class BB_In_Plugin_Notifications {

		/**
		 * Instance.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bool
		 */
		private static $instance = null;

		/**
		 * Source URL.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public $source_url = '';

		/**
		 * Source URL Args.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var string
		 */
		public $source_url_args = array();

		/**
		 * Option value.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @var bool|array
		 */
		public $option = false;

		/**
		 * Get the instance of this class.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return null|BB_In_Plugin_Notifications|Controller|object
		 */
		public static function instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * BB_In_Plugin_Notifications constructor.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function __construct() {
			$this->source_url      = defined( 'BB_TEST_IN_PLUGIN_NOTIFICATION_URL' ) ? BB_TEST_IN_PLUGIN_NOTIFICATION_URL : 'https://buddyboss.com/inpns/notifications.json';
			$this->source_url_args = array(
				'sslverify' => false,
			);
			if ( self::has_access() ) {
				BB_Grd_Lvl_Ctrl::init( true );
				BB_Grd_Lvl_Ctrl::getContainer()->get( IPNRetrieverService::class )->performEvent();
				$container = BB_Grd_Lvl_Ctrl::getContainer()->get( IPNViewService::class )->getContainer();
				BB_Grd_Lvl_Ctrl::getContainer()->get( IPNViewService::class )->load( $container );
			}

//			add_action( 'admin_footer', array( $this, 'bb_admin_notification_menu_append_count' ) );
			add_action( 'in_admin_header', array( $this, 'bb_admin_notification_header' ), 0 );
			add_action( 'bp_admin_enqueue_scripts', array( $this, 'bb_admin_notification_enqueues' ) );
		}

		/**
		 * Check if user has access and is enabled.
		 *
		 * @return boolean
		 */
		public static function has_access() {
			$has_access = BB_Utils::is_bb_admin() && ! get_option( 'bb_hide_announcements' );
			/**
			 * Filters whether or not the user has access to notifications.
			 *
			 * @param boolean $has_access Whether or not the user has access to notifications.
			 */
			return apply_filters( 'bb_admin_notifications_has_access', $has_access );
		}

		/**
		 * Admin script for adding notification count to the BuddyBoss admin menu list item.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_notification_menu_append_count() {
			if ( self::has_access() ) {
				$notifications       = BB_Grd_Lvl_Ctrl::getContainer()->get( Store::class )->fetch()->notifications( false, Store::FILTER_UNREAD );
				$notifications_count = count( $notifications );
			} else {
				$notifications_count = 0;
			}
			ob_start();

			?>

			<span class="awaiting-mod">
				<span class="pending-count" id="bb_in_plugin_admin_menu_unread_count" aria-hidden="true">
					<?php echo esc_html( $notifications_count ); ?>
				</span>
				<span class="comments-in-moderation-text screen-reader-text">
					<?php
					printf(
						esc_html(
						// Translators: %s is the number of unread messages in the user's notifications.
							_n(
								'%s unread message',
								'%s unread messages',
								$notifications_count,
								'buddyboss'
							)
						),
						esc_html( $notifications_count )
					);
					?>
				</span>
			</span>

			<?php $output = ob_get_clean(); ?>

			<script>
				jQuery( document ).ready( function ( $ ) {
					$( 'li.toplevel_page_buddyboss-platform .wp-menu-name' ).append( `<?php echo wp_kses_post( $output ); ?>` );
				} );
			</script>

			<?php
		}

		/**
		 * Load the admin notification header template.
		 *
		 * @since BuddyBoss [BBVERSION]
		 *
		 * @return void
		 */
		public function bb_admin_notification_header() {
			global $bp;
			// If a user is not on a relevant screen, don't show the notice.
			$current_screen = get_current_screen();

			if (
				! $current_screen ||
				(
					false === strpos( $current_screen->base, 'buddyboss' ) &&
					false === strpos( $current_screen->id, 'edit-bpm_category' ) &&
					false === strpos( $current_screen->id, 'buddyboss_fonts' ) &&
					! in_array( $current_screen->post_type, array( 'forum', 'topic', 'reply' ), true )
				)
			) {
				unset( $current_screen );

				return;
			}

			include trailingslashit( $bp->plugin_dir . 'bp-core/admin' ) . 'templates/bb-in-plugin-notifications.php';
		}

		/**
		 * Admin area enqueues.
		 *
		 * @since BuddyBoss [BBVERSION]
		 */
		public function bb_admin_notification_enqueues() {

			wp_enqueue_style(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/css/bb-admin-notifications.css',
				array( 'bp-admin-common-css' ),
				bp_get_version()
			);

			wp_enqueue_script(
				'bb-in-plugin-admin-notifications',
				buddypress()->plugin_url . 'bp-core/admin/js/bb-admin-notifications.js',
				array( 'jquery' ),
				bp_get_version(),
				true
			);

			wp_localize_script(
				'bb-in-plugin-admin-notifications',
				'BBInPluginAdminNotifications',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'bb-in-plugin-admin-notifications' ),
				)
			);
		}
	}
}
