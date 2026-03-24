<?php
/**
 * Broadcast_Queue — Action Scheduler dispatch helpers.
 *
 * Dispatches inbox messages (ANN-05) and notification bell alerts (ANN-06)
 * via Action Scheduler (ANN-07). One AS job per recipient user.
 */
defined( 'ABSPATH' ) || exit;

class Broadcast_Queue {

    /**
     * Register AS action hooks.
     */
    public static function init() {
        add_action( 'broadcast_send_inbox_message', array( __CLASS__, 'handle_send_inbox_message' ) );
        add_action( 'broadcast_send_notification',  array( __CLASS__, 'handle_send_notification' ) );
    }

    /**
     * Dispatch inbox messages to all matched users via AS queue.
     *
     * @param int $announcement_id Announcement ID.
     * @param int $sender_id       WP user ID of the admin dispatching.
     * @return int Number of jobs enqueued.
     */
    public static function dispatch_inbox( int $announcement_id, int $sender_id = 1 ): int {
        $ann = Broadcast_Announcement::get( $announcement_id );
        if ( ! $ann || ! empty( $ann->last_sent_inbox_at ) ) {
            return 0; // Already sent or not found.
        }

        $user_ids = self::get_matched_user_ids( $announcement_id );
        $count    = 0;

        foreach ( $user_ids as $user_id ) {
            as_enqueue_async_action(
                'broadcast_send_inbox_message',
                array(
                    'announcement_id' => $announcement_id,
                    'user_id'         => (int) $user_id,
                    'sender_id'       => $sender_id,
                ),
                'broadcast'
            );
            $count++;
        }

        if ( $count > 0 ) {
            Broadcast_Announcement::update( $announcement_id, array(
                'last_sent_inbox_at' => current_time( 'mysql' ),
            ) );
        }

        return $count;
    }

    /**
     * Dispatch notification bell alerts to all matched users via AS queue.
     *
     * @param int $announcement_id Announcement ID.
     * @return int Number of jobs enqueued.
     */
    public static function dispatch_notification( int $announcement_id ): int {
        $ann = Broadcast_Announcement::get( $announcement_id );
        if ( ! $ann || ! empty( $ann->last_sent_bell_at ) ) {
            return 0;
        }

        $user_ids = self::get_matched_user_ids( $announcement_id );
        $count    = 0;

        foreach ( $user_ids as $user_id ) {
            as_enqueue_async_action(
                'broadcast_send_notification',
                array(
                    'announcement_id' => $announcement_id,
                    'user_id'         => (int) $user_id,
                ),
                'broadcast'
            );
            $count++;
        }

        if ( $count > 0 ) {
            Broadcast_Announcement::update( $announcement_id, array(
                'last_sent_bell_at' => current_time( 'mysql' ),
            ) );
        }

        return $count;
    }

    /**
     * AS job handler: send one inbox message to one user.
     *
     * @param array $args {announcement_id, user_id, sender_id}.
     */
    public static function handle_send_inbox_message( array $args ): void {
        $announcement_id = absint( $args['announcement_id'] ?? 0 );
        $user_id         = absint( $args['user_id'] ?? 0 );
        $sender_id       = absint( $args['sender_id'] ?? 1 );

        $ann = Broadcast_Announcement::get( $announcement_id );
        if ( ! $ann || ! $user_id ) {
            return;
        }

        $subject = ! empty( $ann->title ) ? $ann->title : $ann->name;
        $content = ! empty( $ann->body ) ? $ann->body : $ann->name;

        messages_new_message( array(
            'sender_id'  => $sender_id,
            'recipients' => array( $user_id ),
            'subject'    => $subject,
            'content'    => $content,
        ) );
    }

    /**
     * AS job handler: send one notification bell alert to one user.
     *
     * @param array $args {announcement_id, user_id}.
     */
    public static function handle_send_notification( array $args ): void {
        $announcement_id = absint( $args['announcement_id'] ?? 0 );
        $user_id         = absint( $args['user_id'] ?? 0 );

        if ( ! $announcement_id || ! $user_id ) {
            return;
        }

        bp_notifications_add_notification( array(
            'user_id'           => $user_id,
            'item_id'           => $announcement_id,
            'secondary_item_id' => 0,
            'component_name'    => 'broadcast',
            'component_action'  => 'broadcast_announcement',
            'allow_duplicate'   => false,
        ) );
    }

    /**
     * Get all user IDs that match an announcement's targeting rules.
     *
     * Queries users in batches to limit memory. Uses PHP-level
     * targeting evaluation (not SQL) since targeting rules require BB API calls.
     *
     * @param int $announcement_id
     * @return array Array of user IDs.
     */
    public static function get_matched_user_ids( int $announcement_id ): array {
        global $wpdb;

        $matched = array();
        $offset  = 0;
        $batch   = 100;

        do {
            $user_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT %d OFFSET %d",
                $batch,
                $offset
            ) );

            foreach ( $user_ids as $uid ) {
                if ( Broadcast_Targeting::user_matches( (int) $uid, $announcement_id ) ) {
                    $matched[] = (int) $uid;
                }
            }

            $offset += $batch;
        } while ( count( $user_ids ) === $batch );

        return $matched;
    }
}
