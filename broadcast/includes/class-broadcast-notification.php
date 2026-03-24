<?php
/**
 * Broadcast_Notification — BuddyBoss notification bell integration.
 *
 * Extends BP_Core_Notification_Abstract to register:
 * - Notification group: 'broadcast'
 * - Notification type: 'broadcast_announcement'
 * - Format callback: human-readable text for the bell dropdown
 *
 * Required for ANN-06 — without this class, bell notifications show
 * raw component_name/component_action strings.
 */
defined( 'ABSPATH' ) || exit;

// Guard: BP_Core_Notification_Abstract only exists when BuddyBoss is loaded.
if ( ! class_exists( 'BP_Core_Notification_Abstract' ) ) {
    return;
}

class Broadcast_Notification extends BP_Core_Notification_Abstract {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Must call start() on bp_init — same as all BB components.
        add_action( 'bp_init', array( $this, 'start' ), 5 );
    }

    /**
     * Register notification group, type, and format callback.
     * Called by BP_Core_Notification_Abstract::start().
     */
    public function load() {
        $this->register_notification_group(
            'broadcast',
            esc_html__( 'Broadcast', 'broadcast' ),
            esc_html__( 'Broadcast Announcements', 'broadcast' ),
            50
        );

        $this->register_notification_type(
            'broadcast_announcement',
            esc_html__( 'You receive a new announcement', 'broadcast' ),
            esc_html__( 'A broadcast announcement is sent', 'broadcast' ),
            'broadcast'
        );

        $this->register_notification(
            'broadcast',
            'broadcast_announcement',
            'broadcast_announcement'
        );

        add_filter(
            'bp_broadcast_broadcast_announcement_notification',
            array( $this, 'format_notification' ),
            10, 7
        );
    }

    /**
     * Format the notification text for the bell dropdown.
     *
     * @param string $content            Default content.
     * @param int    $item_id            Announcement ID.
     * @param int    $secondary_item_id  Not used.
     * @param int    $total_items        Total unread.
     * @param string $component_action_name  'broadcast_announcement'.
     * @param string $component_name     'broadcast'.
     * @param int    $notification_id    Notification row ID.
     * @return array {text, link}
     */
    public function format_notification( $content, $item_id, $secondary_item_id, $total_items, $component_action_name, $component_name, $notification_id, $screen = '' ) {
        if ( ! class_exists( 'Broadcast_Announcement' ) ) {
            require_once BROADCAST_DIR . 'includes/class-broadcast-announcement.php';
        }
        $ann = Broadcast_Announcement::get( (int) $item_id );
        if ( ! $ann ) {
            return $content;
        }

        return array(
            'text' => sprintf(
                /* translators: %s: announcement name */
                esc_html__( 'New announcement: %s', 'broadcast' ),
                esc_html( $ann->name )
            ),
            'link' => home_url( '/' ),
        );
    }
}
