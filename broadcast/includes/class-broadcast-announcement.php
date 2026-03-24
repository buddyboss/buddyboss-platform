<?php
/**
 * Broadcast_Announcement — data model for the broadcast_announcements table.
 *
 * All DB access goes through $wpdb. No WordPress post types.
 */

defined( 'ABSPATH' ) || exit;

class Broadcast_Announcement {

    /**
     * Insert a new announcement.
     *
     * @param array $data Associative array of column values (excludes id, created_at, updated_at).
     * @return int|false New row ID on success, false on failure.
     */
    public static function create( array $data ) {
        global $wpdb;
        $now  = current_time( 'mysql' );
        $data = array_merge( $data, [
            'created_at' => $now,
            'updated_at' => $now,
        ] );
        $result = $wpdb->insert(
            $wpdb->prefix . 'broadcast_announcements',
            $data
        );
        return $result ? (int) $wpdb->insert_id : false;
    }

    /**
     * Retrieve a single announcement by ID.
     *
     * @param int $id
     * @return object|null
     */
    public static function get( int $id ) {
        global $wpdb;
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}broadcast_announcements WHERE id = %d LIMIT 1",
                $id
            )
        );
        return ! empty( $results ) ? $results[0] : null;
    }

    /**
     * Retrieve all currently active announcements.
     *
     * Active = enabled AND within date window (or no date set).
     * Status is derived at query time, not stored.
     *
     * @return array
     */
    public static function get_active(): array {
        global $wpdb;
        $now = current_time( 'mysql' );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}broadcast_announcements
                 WHERE enabled = 1
                   AND ( start_date IS NULL OR start_date <= %s )
                   AND ( end_date IS NULL OR end_date >= %s )",
                $now,
                $now
            )
        ) ?: [];
    }

    /**
     * Update an existing announcement.
     *
     * @param int   $id   Announcement ID.
     * @param array $data Columns to update.
     * @return bool
     */
    public static function update( int $id, array $data ): bool {
        global $wpdb;
        $data['updated_at'] = current_time( 'mysql' );
        $result = $wpdb->update(
            $wpdb->prefix . 'broadcast_announcements',
            $data,
            [ 'id' => $id ]
        );
        return false !== $result;
    }

    /**
     * Delete an announcement and all related data.
     *
     * Deletes: announcement row, targeting rules, analytics events, user dismissals.
     *
     * @param int $id Announcement ID.
     * @return bool
     */
    public static function delete( int $id ): bool {
        global $wpdb;
        // Delete dependent records first.
        $wpdb->delete(
            $wpdb->prefix . 'broadcast_targeting_rules',
            [ 'announcement_id' => $id ]
        );
        $wpdb->delete(
            $wpdb->prefix . 'broadcast_analytics_events',
            [ 'announcement_id' => $id ]
        );
        $wpdb->delete(
            $wpdb->prefix . 'broadcast_user_dismissals',
            [ 'announcement_id' => $id ]
        );
        $result = $wpdb->delete(
            $wpdb->prefix . 'broadcast_announcements',
            [ 'id' => $id ]
        );
        return false !== $result;
    }

    /**
     * Get all targeting rules for an announcement.
     *
     * @param int $announcement_id
     * @return array Array of rule objects (empty if no rules — means "show to all").
     */
    public static function get_targeting_rules( int $announcement_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}broadcast_targeting_rules
                 WHERE announcement_id = %d",
                $announcement_id
            )
        ) ?: [];
    }
}

/**
 * Derive announcement display status from stored fields.
 *
 * Status is NOT stored as a free-form field — it is computed to avoid staleness.
 *
 * @param object      $ann Announcement row object.
 * @param string|null $now MySQL datetime string for "now". Defaults to current_time('mysql').
 * @return string One of: 'active', 'scheduled', 'ended', 'disabled'.
 */
function broadcast_get_announcement_status( $ann, $now = null ) {
    if ( null === $now ) {
        $now = current_time( 'mysql' );
    }
    if ( ! $ann->enabled ) {
        return 'disabled';
    }
    if ( $ann->start_date && $ann->start_date > $now ) {
        return 'scheduled';
    }
    if ( $ann->end_date && $ann->end_date < $now ) {
        return 'ended';
    }
    return 'active';
}
