<?php
/**
 * BuddyBoss CRM Core Functions
 *
 * Tag CRUD operations and core API functions
 *
 * @package BuddyBossCRM
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create a new tag
 *
 * @since 1.0.0
 * @param array $args Tag data
 * @return int|WP_Error Tag ID on success, WP_Error on failure
 */
function bb_crm_create_tag( $args = array() ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix : $wpdb->prefix;

    // Default values
    $defaults = array(
        'name'         => '',
        'slug'         => '',
        'color'        => '#0073aa',
        'icon'         => '',
        'visibility'   => 'public',
        'priority'     => 0,
        'expires_days' => 0,
        'description'  => '',
        'category_id'  => 0,
    );

    $args = wp_parse_args( $args, $defaults );

    // Validate required fields
    if ( empty( $args['name'] ) ) {
        return new WP_Error( 'missing_name', __( 'Tag name is required.', 'buddyboss-crm' ) );
    }

    // Generate slug if not provided
    if ( empty( $args['slug'] ) ) {
        $args['slug'] = sanitize_title( $args['name'] );
    }

    // Check if slug already exists
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$bb_prefix}bb_tags WHERE slug = %s",
        $args['slug']
    ) );

    if ( $existing ) {
        return new WP_Error( 'slug_exists', __( 'A tag with this slug already exists.', 'buddyboss-crm' ) );
    }

    // Prepare data for insertion
    $data = array(
        'name'         => sanitize_text_field( $args['name'] ),
        'slug'         => sanitize_title( $args['slug'] ),
        'color'        => sanitize_hex_color( $args['color'] ),
        'icon'         => sanitize_text_field( $args['icon'] ),
        'visibility'   => in_array( $args['visibility'], array( 'public', 'members-only', 'admin-only', 'self-only' ) ) ? $args['visibility'] : 'public',
        'priority'     => absint( $args['priority'] ),
        'expires_days' => absint( $args['expires_days'] ),
        'description'  => wp_kses_post( $args['description'] ),
        'category_id'  => absint( $args['category_id'] ),
        'created_at'   => current_time( 'mysql' ),
        'updated_at'   => current_time( 'mysql' ),
    );

    // Insert into database
    $inserted = $wpdb->insert(
        $bb_prefix . 'bb_tags',
        $data,
        array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%d', '%s', '%s' )
    );

    if ( ! $inserted ) {
        return new WP_Error( 'db_error', __( 'Failed to create tag.', 'buddyboss-crm' ) );
    }

    $tag_id = $wpdb->insert_id;

    // Clear cache
    wp_cache_delete( 'all_tags', 'bb_crm' );

    /**
     * Fires after a tag is created
     *
     * @since 1.0.0
     * @param int   $tag_id Tag ID
     * @param array $args   Tag data
     */
    do_action( 'bb_crm_tag_created', $tag_id, $args );

    return $tag_id;
}

/**
 * Get a tag by ID
 *
 * @since 1.0.0
 * @param int $tag_id Tag ID
 * @return object|null Tag object or null if not found
 */
function bb_crm_get_tag( $tag_id ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    $cache_key = 'tag_' . $tag_id;
    $tag = wp_cache_get( $cache_key, 'bb_crm' );

    if ( false === $tag ) {
        $tag = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$bb_prefix}bb_tags WHERE id = %d",
            $tag_id
        ) );

        if ( $tag ) {
            wp_cache_set( $cache_key, $tag, 'bb_crm', 3600 );
        }
    }

    return $tag;
}

/**
 * Get all tags
 *
 * @since 1.0.0
 * @param array $args Query arguments
 * @return array Array of tag objects
 */
function bb_crm_get_tags( $args = array() ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    $defaults = array(
        'category_id' => 0,
        'visibility'  => '',
        'orderby'     => 'priority',
        'order'       => 'DESC',
        'limit'       => -1,
        'offset'      => 0,
    );

    $args = wp_parse_args( $args, $defaults );

    // Build query
    $where = array( '1=1' );

    if ( $args['category_id'] > 0 ) {
        $where[] = $wpdb->prepare( 'category_id = %d', $args['category_id'] );
    }

    if ( ! empty( $args['visibility'] ) ) {
        $where[] = $wpdb->prepare( 'visibility = %s', $args['visibility'] );
    }

    $where_sql = implode( ' AND ', $where );

    // Orderby
    $orderby = in_array( $args['orderby'], array( 'id', 'name', 'priority', 'created_at' ) ) ? $args['orderby'] : 'priority';
    $order = in_array( strtoupper( $args['order'] ), array( 'ASC', 'DESC' ) ) ? strtoupper( $args['order'] ) : 'DESC';

    // Limit
    $limit_sql = '';
    if ( $args['limit'] > 0 ) {
        $limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
    }

    // Execute query
    $tags = $wpdb->get_results(
        "SELECT * FROM {$bb_prefix}bb_tags
        WHERE {$where_sql}
        ORDER BY {$orderby} {$order}
        {$limit_sql}"
    );

    return $tags;
}

/**
 * Assign a tag to a user
 *
 * @since 1.0.0
 * @param int   $user_id User ID
 * @param int   $tag_id  Tag ID
 * @param array $args    Additional arguments
 * @return int|WP_Error Assignment ID on success, WP_Error on failure
 */
function bb_crm_add_user_tag( $user_id, $tag_id, $args = array() ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    // Validate user and tag
    if ( ! get_userdata( $user_id ) ) {
        return new WP_Error( 'invalid_user', __( 'Invalid user ID.', 'buddyboss-crm' ) );
    }

    $tag = bb_crm_get_tag( $tag_id );
    if ( ! $tag ) {
        return new WP_Error( 'invalid_tag', __( 'Invalid tag ID.', 'buddyboss-crm' ) );
    }

    // Check if already assigned
    $existing = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$bb_prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d",
        $user_id,
        $tag_id
    ) );

    if ( $existing ) {
        return new WP_Error( 'already_assigned', __( 'Tag already assigned to user.', 'buddyboss-crm' ) );
    }

    // Default values
    $defaults = array(
        'applied_by' => get_current_user_id(),
        'source'     => 'manual',
        'meta'       => '',
    );

    $args = wp_parse_args( $args, $defaults );

    // Calculate expiry date
    $expires_at = null;
    if ( $tag->expires_days > 0 ) {
        $expires_at = date( 'Y-m-d H:i:s', strtotime( "+{$tag->expires_days} days" ) );
    }

    // Prepare data
    $data = array(
        'user_id'    => $user_id,
        'tag_id'     => $tag_id,
        'applied_by' => absint( $args['applied_by'] ),
        'applied_at' => current_time( 'mysql' ),
        'expires_at' => $expires_at,
        'source'     => sanitize_text_field( $args['source'] ),
        'meta'       => is_array( $args['meta'] ) ? json_encode( $args['meta'] ) : $args['meta'],
    );

    // Insert assignment
    $inserted = $wpdb->insert(
        $bb_prefix . 'bb_user_tags',
        $data,
        array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
    );

    if ( ! $inserted ) {
        return new WP_Error( 'db_error', __( 'Failed to assign tag.', 'buddyboss-crm' ) );
    }

    $assignment_id = $wpdb->insert_id;

    // Add to history
    bb_crm_add_tag_history( $user_id, $tag_id, 'added', $args['applied_by'], $args['source'] );

    // Clear caches
    wp_cache_delete( 'user_tags_' . $user_id, 'bb_crm' );

    /**
     * Fires after a tag is added to a user
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $tag_id  Tag ID
     * @param int $assignment_id Assignment ID
     */
    do_action( 'bb_crm_after_tag_added', $user_id, $tag_id, $assignment_id );

    return $assignment_id;
}

/**
 * Remove a tag from a user
 *
 * @since 1.0.0
 * @param int    $user_id User ID
 * @param int    $tag_id  Tag ID
 * @param string $reason  Reason for removal
 * @return bool True on success, false on failure
 */
function bb_crm_remove_user_tag( $user_id, $tag_id, $reason = 'manual' ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    // Delete assignment
    $deleted = $wpdb->delete(
        $bb_prefix . 'bb_user_tags',
        array(
            'user_id' => $user_id,
            'tag_id'  => $tag_id,
        ),
        array( '%d', '%d' )
    );

    if ( ! $deleted ) {
        return false;
    }

    // Add to history
    bb_crm_add_tag_history( $user_id, $tag_id, 'removed', get_current_user_id(), $reason );

    // Clear caches
    wp_cache_delete( 'user_tags_' . $user_id, 'bb_crm' );

    /**
     * Fires after a tag is removed from a user
     *
     * @since 1.0.0
     * @param int    $user_id User ID
     * @param int    $tag_id  Tag ID
     * @param string $reason  Removal reason
     */
    do_action( 'bb_crm_after_tag_removed', $user_id, $tag_id, $reason );

    return true;
}

/**
 * Get tags for a user
 *
 * @since 1.0.0
 * @param int $user_id User ID
 * @return array Array of tag objects with assignment data
 */
function bb_crm_get_user_tags( $user_id ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    $cache_key = 'user_tags_' . $user_id;
    $tags = wp_cache_get( $cache_key, 'bb_crm' );

    if ( false === $tags ) {
        $tags = $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, ut.applied_at, ut.expires_at, ut.applied_by, ut.source
            FROM {$bb_prefix}bb_tags t
            INNER JOIN {$bb_prefix}bb_user_tags ut ON t.id = ut.tag_id
            WHERE ut.user_id = %d
            ORDER BY t.priority DESC, t.name ASC",
            $user_id
        ) );

        wp_cache_set( $cache_key, $tags, 'bb_crm', 3600 );
    }

    return $tags;
}

/**
 * Add entry to tag history
 *
 * @since 1.0.0
 * @param int    $user_id      User ID
 * @param int    $tag_id       Tag ID
 * @param string $action       Action (added, removed, expired)
 * @param int    $performed_by User ID who performed the action
 * @param string $source       Source of action
 * @param string $notes        Additional notes
 * @return int|false History ID on success, false on failure
 */
function bb_crm_add_tag_history( $user_id, $tag_id, $action, $performed_by = 0, $source = 'manual', $notes = '' ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    // Check if history is enabled
    if ( ! get_option( 'bb_crm_enable_tag_history', '1' ) ) {
        return false;
    }

    $data = array(
        'user_id'      => $user_id,
        'tag_id'       => $tag_id,
        'action'       => $action,
        'performed_by' => $performed_by,
        'performed_at' => current_time( 'mysql' ),
        'source'       => $source,
        'notes'        => $notes,
    );

    $inserted = $wpdb->insert(
        $bb_prefix . 'bb_tag_history',
        $data,
        array( '%d', '%d', '%s', '%d', '%s', '%s', '%s' )
    );

    return $inserted ? $wpdb->insert_id : false;
}

/**
 * Get users with a specific tag
 *
 * @since 1.0.0
 * @param int   $tag_id Tag ID
 * @param array $args   Query arguments
 * @return array Array of user IDs
 */
function bb_crm_get_tag_users( $tag_id, $args = array() ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    $defaults = array(
        'limit'  => -1,
        'offset' => 0,
    );

    $args = wp_parse_args( $args, $defaults );

    $limit_sql = '';
    if ( $args['limit'] > 0 ) {
        $limit_sql = $wpdb->prepare( 'LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );
    }

    $user_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT user_id FROM {$bb_prefix}bb_user_tags
        WHERE tag_id = %d
        ORDER BY applied_at DESC
        {$limit_sql}",
        $tag_id
    ) );

    return $user_ids;
}

/**
 * Count users with a specific tag
 *
 * @since 1.0.0
 * @param int $tag_id Tag ID
 * @return int User count
 */
function bb_crm_count_tag_users( $tag_id ) {
    global $wpdb;
    $bb_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $wpdb->prefix;

    $count = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$bb_prefix}bb_user_tags WHERE tag_id = %d",
        $tag_id
    ) );

    return absint( $count );
}

/**
 * Returns the correct admin menu parent slug.
 * When BuddyBoss Platform is active, all CRM menus live under it.
 * Otherwise they live under the standalone top-level CRM menu.
 *
 * @return string
 */
function bb_crm_menu_parent() {
    return 'buddyboss-crm';
}
