<?php
/**
 * Broadcast — main plugin class.
 *
 * Singleton. Instantiated by broadcast.php on plugins_loaded (priority 20)
 * after the BuddyBoss Platform dependency is confirmed present.
 *
 * Usage: Broadcast::instance()
 */

defined( 'ABSPATH' ) || exit;

class Broadcast {

    /**
     * Single instance of the class.
     *
     * @var Broadcast|null
     */
    private static $instance = null;

    /**
     * Get or create the singleton instance.
     *
     * @return Broadcast
     */
    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
            self::$instance->setup();
        }
        return self::$instance;
    }

    /**
     * Constructor — private to enforce singleton.
     */
    private function __construct() {}

    /**
     * Hook registration. Called once by instance().
     *
     * @return void
     */
    private function setup() {
        $this->load_queue();
        $this->load_notification();
        $this->load_email();
        $this->load_campaigns();
        $this->load_admin();
        $this->load_frontend();
    }

    /**
     * Load background queue class.
     *
     * @return void
     */
    private function load_queue() {
        require_once BROADCAST_DIR . 'includes/class-broadcast-queue.php';
        Broadcast_Queue::init();
    }

    /**
     * Load BuddyBoss notification bell integration.
     *
     * @return void
     */
    private function load_notification() {
        require_once BROADCAST_DIR . 'includes/class-broadcast-notification.php';
        if ( class_exists( 'Broadcast_Notification' ) && class_exists( 'BP_Core_Notification_Abstract' ) ) {
            Broadcast_Notification::instance();
        }
    }

    /**
     * Load email delivery and template classes.
     *
     * @return void
     */
    private function load_email() {
        require_once BROADCAST_DIR . 'includes/email/class-broadcast-email-settings.php';
        require_once BROADCAST_DIR . 'includes/email/class-broadcast-email-delivery.php';
        require_once BROADCAST_DIR . 'includes/email/class-broadcast-email-templates.php';
        Broadcast_Email_Delivery::init();
        Broadcast_Email_Templates::init();
    }

    /**
     * Load campaigns feature (email campaigns + templates).
     *
     * @return void
     */
    private function load_campaigns() {
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-campaigns-install.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-camp-cpt.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-camp-patterns.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-camp-unsubscribe.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-camp-click-track.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-campaigns-mailer.php';
        require_once BROADCAST_DIR . 'includes/campaigns/class-broadcast-campaigns-cron.php';

        add_filter( 'cron_schedules', function( $schedules ) {
            if ( ! isset( $schedules['broadcast_every_five_minutes'] ) ) {
                $schedules['broadcast_every_five_minutes'] = array(
                    'interval' => 300,
                    'display'  => __( 'Every 5 Minutes', 'broadcast' ),
                );
            }
            return $schedules;
        } );

        add_action( 'broadcast_process_campaign_queue', array( 'Broadcast_Campaigns_Cron', 'process_batch_queue' ) );

        Broadcast_Camp_CPT::init();
        Broadcast_Camp_Patterns::init();
        Broadcast_Camp_Unsubscribe::init();
        Broadcast_Camp_Click_Track::init();

        if ( get_option( 'broadcast_camp_version' ) !== BROADCAST_CAMP_VERSION ) {
            Broadcast_Campaigns_Install::install();
        }

        if ( get_option( 'broadcast_camp_flush_rules' ) ) {
            flush_rewrite_rules( false );
            delete_option( 'broadcast_camp_flush_rules' );
        }

        if ( ! wp_next_scheduled( 'broadcast_process_campaign_queue' ) ) {
            wp_schedule_event( time(), 'broadcast_every_five_minutes', 'broadcast_process_campaign_queue' );
        }

        if ( is_admin() ) {
            require_once BROADCAST_DIR . 'includes/admin/class-broadcast-campaigns-admin.php';
            Broadcast_Campaigns_Admin::instance();
        }
    }

    /**
     * Load admin-only classes.
     *
     * @return void
     */
    private function load_admin() {
        if ( ! is_admin() ) {
            return;
        }
        require_once BROADCAST_DIR . 'includes/admin/class-broadcast-admin.php';
        require_once BROADCAST_DIR . 'includes/admin/class-broadcast-email-admin.php';
        Broadcast_Admin::init();
    }

    /**
     * Load frontend classes for non-admin page requests.
     *
     * @return void
     */
    private function load_frontend() {
        if ( is_admin() ) {
            return;
        }
        require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';
        require_once BROADCAST_DIR . 'includes/class-broadcast-targeting.php';
        require_once BROADCAST_DIR . 'includes/frontend/class-broadcast-frontend.php';
        Broadcast_Frontend::init();
    }
}
