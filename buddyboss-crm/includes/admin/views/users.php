<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix   = $wpdb->prefix;
$page_url = admin_url( 'admin.php?page=buddyboss-crm-users' );
$action   = sanitize_key( $_POST['action'] ?? $_GET['action'] ?? 'list' );
$view_uid = absint( $_GET['user_id'] ?? 0 );
$notice   = '';

// ── Bulk action (list view) — no redirect, sets $notice inline ───────────────
if ( isset( $_POST['bb_crm_bulk_action'] ) ) {
    check_admin_referer( 'bb_crm_bulk_users' );
    $bulk_action  = sanitize_key( $_POST['bulk_action'] ?? '' );
    $bulk_tag_id  = absint( $_POST['bulk_tag_id'] ?? 0 );
    $bulk_list_id = absint( $_POST['bulk_list_id'] ?? 0 );
    $selected_ids = array_filter( array_map( 'absint', (array) ( $_POST['user_ids'] ?? array() ) ) );

    if ( empty( $selected_ids ) ) {
        $notice = 'no_selection';
    } elseif ( 'add_to_list' === $bulk_action || 'remove_from_list' === $bulk_action ) {
        if ( ! $bulk_list_id ) {
            $notice = 'no_list';
        } else {
            foreach ( $selected_ids as $uid ) {
                if ( 'add_to_list' === $bulk_action ) {
                    $wpdb->replace( $prefix . 'bb_user_list_assignments', array(
                        'list_id'     => $bulk_list_id,
                        'user_id'     => $uid,
                        'assigned_at' => current_time( 'mysql' ),
                    ) );
                } else {
                    $wpdb->delete(
                        $prefix . 'bb_user_list_assignments',
                        array( 'list_id' => $bulk_list_id, 'user_id' => $uid ),
                        array( '%d', '%d' )
                    );
                }
            }
            $notice = ( 'add_to_list' === $bulk_action ) ? 'bulk_list_added' : 'bulk_list_removed';
        }
    } elseif ( 'add_tag' === $bulk_action || 'remove_tag' === $bulk_action ) {
        if ( ! $bulk_tag_id ) {
            $notice = 'no_tag';
        } else {
            if ( 'add_tag' === $bulk_action ) {
                foreach ( $selected_ids as $uid ) {
                    $exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d", $uid, $bulk_tag_id
                    ) );
                    if ( ! $exists ) {
                        $wpdb->insert( $prefix . 'bb_user_tags', array(
                            'user_id' => $uid, 'tag_id' => $bulk_tag_id,
                            'applied_by' => get_current_user_id(), 'applied_at' => current_time( 'mysql' ),
                        ) );
                        $wpdb->insert( $prefix . 'bb_tag_history', array(
                            'user_id' => $uid, 'tag_id' => $bulk_tag_id, 'action' => 'added',
                            'performed_by' => get_current_user_id(), 'performed_at' => current_time( 'mysql' ), 'source' => 'manual',
                        ) );
                    }
                }
                $notice = 'bulk_added';
            } else {
                foreach ( $selected_ids as $uid ) {
                    $wpdb->delete( $prefix . 'bb_user_tags', array( 'user_id' => $uid, 'tag_id' => $bulk_tag_id ), array( '%d', '%d' ) );
                    $wpdb->insert( $prefix . 'bb_tag_history', array(
                        'user_id' => $uid, 'tag_id' => $bulk_tag_id, 'action' => 'removed',
                        'performed_by' => get_current_user_id(), 'performed_at' => current_time( 'mysql' ), 'source' => 'manual',
                    ) );
                }
                $notice = 'bulk_removed';
            }
        }
    }
    $action = 'list';
}

// ── Notice from redirect ──────────────────────────────────────────────────────
if ( ! $notice && isset( $_GET['msg'] ) ) {
    $notice = sanitize_key( $_GET['msg'] );
}

$notice_msgs = array(
    'tag_added'    => 'Tag added.',
    'tag_removed'  => 'Tag removed.',
    'list_added'   => 'User added to list.',
    'list_removed' => 'User removed from list.',
    'bulk_added'        => 'Tag added to selected users.',
    'bulk_removed'      => 'Tag removed from selected users.',
    'bulk_list_added'   => 'Users added to list.',
    'bulk_list_removed' => 'Users removed from list.',
    'no_selection'      => 'Please select at least one user.',
    'no_tag'            => 'Please select a tag for the bulk action.',
    'no_list'           => 'Please select a list for the bulk action.',
);
$notice_is_error = in_array( $notice, array( 'no_selection', 'no_tag', 'no_list' ), true );

// ════════════════════════════════════════════════════════════════════════════
// VIEW: User profile dashboard
// ════════════════════════════════════════════════════════════════════════════
if ( 'view' === $action && $view_uid ) :
    $wp_user = get_userdata( $view_uid );
    if ( ! $wp_user ) {
        echo '<div class="wrap"><p>User not found.</p></div>';
        return;
    }

    // Current tags.
    $user_tags = $wpdb->get_results( $wpdb->prepare(
        "SELECT t.id, t.name, t.color FROM {$prefix}bb_user_tags ut
         JOIN {$prefix}bb_tags t ON t.id = ut.tag_id
         WHERE ut.user_id = %d ORDER BY t.name ASC", $view_uid
    ) );

    // All tags for the add-tag dropdown (excluding already assigned).
    $assigned_tag_ids = wp_list_pluck( $user_tags, 'id' );
    $all_tags = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tags ORDER BY name ASC" );
    $available_tags = array_filter( $all_tags, function( $t ) use ( $assigned_tag_ids ) {
        return ! in_array( $t->id, $assigned_tag_ids, false );
    } );

    // Current lists.
    $user_lists = $wpdb->get_results( $wpdb->prepare(
        "SELECT l.id, l.name, l.list_type FROM {$prefix}bb_user_list_assignments a
         JOIN {$prefix}bb_user_lists l ON l.id = a.list_id
         WHERE a.user_id = %d ORDER BY l.name ASC", $view_uid
    ) );

    // All lists for the add-to-list dropdown (excluding already in).
    $in_list_ids  = wp_list_pluck( $user_lists, 'id' );
    $all_lists = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_user_lists ORDER BY name ASC" );
    $available_lists = array_filter( $all_lists, function( $l ) use ( $in_list_ids ) {
        return ! in_array( $l->id, $in_list_ids, false );
    } );

    // Tag history.
    $history = $wpdb->get_results( $wpdb->prepare(
        "SELECT h.*, t.name AS tag_name, t.color, u.display_name AS done_by
         FROM {$prefix}bb_tag_history h
         JOIN {$prefix}bb_tags t ON t.id = h.tag_id
         LEFT JOIN {$wpdb->users} u ON u.ID = h.performed_by
         WHERE h.user_id = %d
         ORDER BY h.performed_at DESC
         LIMIT 50", $view_uid
    ) );

    // BuddyBoss / BuddyPress activity feed.
    $bp_activity   = array();
    $bp_act_prefix = function_exists( 'bp_core_get_table_prefix' ) ? bp_core_get_table_prefix() : $prefix;
    if ( function_exists( 'buddypress' ) ) {
        $bp_activity = $wpdb->get_results( $wpdb->prepare(
            "SELECT id, component, type, action, content, date_recorded
             FROM {$bp_act_prefix}bp_activity
             WHERE user_id = %d AND is_spam = 0
             ORDER BY date_recorded DESC
             LIMIT 60",
            $view_uid
        ) );
    }

    // BuddyPress extra fields (if available).
    $bp_fields = array();
    if ( function_exists( 'bp_get_profile_field_data' ) ) {
        $groups = function_exists( 'bp_xprofile_get_groups' ) ? bp_xprofile_get_groups( array( 'fetch_fields' => true, 'fetch_field_data' => true, 'user_id' => $view_uid ) ) : array();
        foreach ( $groups as $group ) {
            foreach ( $group->fields as $field ) {
                $val = xprofile_get_field_data( $field->id, $view_uid );
                if ( $val ) $bp_fields[ $field->name ] = $val;
            }
        }
    }
?>
<div class="wrap bb-crm-wrap">
    <p style="margin-bottom:4px">
        <a href="<?php echo esc_url( $page_url ); ?>" style="text-decoration:none">← <?php esc_html_e( 'All Users', 'buddyboss-crm' ); ?></a>
    </p>

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice_msgs[ $notice ] ?? '' ); ?></p></div>
    <?php endif; ?>

    <!-- ── Profile header ── -->
    <div style="display:flex;align-items:center;gap:20px;background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px 24px;margin-bottom:20px">
        <div style="flex-shrink:0">
            <?php echo get_avatar( $view_uid, 80, '', '', array( 'force_default' => false ) ); ?>
        </div>
        <div style="flex:1;min-width:0">
            <h2 style="margin:0 0 4px;font-size:22px"><?php echo esc_html( $wp_user->display_name ); ?></h2>
            <p style="margin:0;color:#666;font-size:13px">
                <strong><?php esc_html_e( 'Username:', 'buddyboss-crm' ); ?></strong> <?php echo esc_html( $wp_user->user_login ); ?>
                &nbsp;·&nbsp;
                <strong><?php esc_html_e( 'Email:', 'buddyboss-crm' ); ?></strong> <a href="mailto:<?php echo esc_attr( $wp_user->user_email ); ?>"><?php echo esc_html( $wp_user->user_email ); ?></a>
                &nbsp;·&nbsp;
                <strong><?php esc_html_e( 'Registered:', 'buddyboss-crm' ); ?></strong> <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $wp_user->user_registered ) ) ); ?>
                &nbsp;·&nbsp;
                <strong><?php esc_html_e( 'Role:', 'buddyboss-crm' ); ?></strong> <?php echo esc_html( implode( ', ', array_map( 'translate_user_role', array_map( 'ucfirst', (array) $wp_user->roles ) ) ) ); ?>
                &nbsp;·&nbsp;
                <strong><?php esc_html_e( 'ID:', 'buddyboss-crm' ); ?></strong> #<?php echo absint( $view_uid ); ?>
            </p>
            <?php if ( $wp_user->first_name || $wp_user->last_name ) : ?>
                <p style="margin:4px 0 0;color:#444;font-size:13px">
                    <strong><?php esc_html_e( 'Name:', 'buddyboss-crm' ); ?></strong> <?php echo esc_html( trim( $wp_user->first_name . ' ' . $wp_user->last_name ) ); ?>
                </p>
            <?php endif; ?>
        </div>
        <div style="flex-shrink:0;display:flex;gap:8px">
            <a href="<?php echo esc_url( get_edit_user_link( $view_uid ) ); ?>" class="button" target="_blank"><?php esc_html_e( 'Edit in WordPress', 'buddyboss-crm' ); ?></a>
            <?php if ( function_exists( 'bp_core_get_user_domain' ) ) : ?>
                <a href="<?php echo esc_url( bp_core_get_user_domain( $view_uid ) ); ?>" class="button" target="_blank"><?php esc_html_e( 'View Profile', 'buddyboss-crm' ); ?></a>
            <?php endif; ?>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">

        <!-- ── Tags card ── -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px">
            <h3 style="margin:0 0 14px;font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:#555"><?php esc_html_e( 'Tags', 'buddyboss-crm' ); ?></h3>

            <div style="min-height:32px;margin-bottom:14px">
                <?php if ( empty( $user_tags ) ) : ?>
                    <span style="color:#aaa;font-size:13px"><?php esc_html_e( 'No tags assigned.', 'buddyboss-crm' ); ?></span>
                <?php else :
                    foreach ( $user_tags as $t ) :
                        $color = $t->color ?: '#999';
                        $rm = wp_nonce_url( add_query_arg( array( 'action' => 'remove_tag', 'user_id' => $view_uid, 'tag_id' => $t->id ), $page_url ), 'remove-user-tag-' . $view_uid . '-' . $t->id ); ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:<?php echo esc_attr($color); ?>22;color:<?php echo esc_attr($color); ?>;border:1px solid <?php echo esc_attr($color); ?>55;border-radius:12px;padding:3px 10px;font-size:12px;margin:2px;white-space:nowrap">
                            <?php echo esc_html( $t->name ); ?>
                            <a href="<?php echo esc_url( $rm ); ?>" style="color:inherit;text-decoration:none;font-weight:700" title="Remove" onclick="return confirm('Remove tag?')">×</a>
                        </span>
                    <?php endforeach;
                endif; ?>
            </div>

            <?php if ( ! empty( $available_tags ) ) : ?>
            <form method="post" action="<?php echo esc_url( $page_url ); ?>" style="display:flex;gap:6px">
                <?php wp_nonce_field( 'bb_crm_assign_tag' ); ?>
                <input type="hidden" name="action" value="assign_tag">
                <input type="hidden" name="user_id" value="<?php echo absint( $view_uid ); ?>">
                <select name="tag_id" style="flex:1">
                    <option value=""><?php esc_html_e( '— Add a tag —', 'buddyboss-crm' ); ?></option>
                    <?php foreach ( $available_tags as $t ) : ?>
                        <option value="<?php echo absint( $t->id ); ?>"><?php echo esc_html( $t->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'buddyboss-crm' ); ?></button>
            </form>
            <?php elseif ( empty( $all_tags ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-tags' ) ); ?>" class="button button-small"><?php esc_html_e( 'Create tags first', 'buddyboss-crm' ); ?></a>
            <?php else : ?>
                <p style="color:#888;font-size:12px;margin:0"><?php esc_html_e( 'All tags assigned.', 'buddyboss-crm' ); ?></p>
            <?php endif; ?>
        </div>

        <!-- ── Lists card ── -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px">
            <h3 style="margin:0 0 14px;font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:#555"><?php esc_html_e( 'Lists', 'buddyboss-crm' ); ?></h3>

            <div style="min-height:32px;margin-bottom:14px">
                <?php if ( empty( $user_lists ) ) : ?>
                    <span style="color:#aaa;font-size:13px"><?php esc_html_e( 'Not in any lists.', 'buddyboss-crm' ); ?></span>
                <?php else :
                    foreach ( $user_lists as $lst ) :
                        $is_dynamic = ( 'dynamic' === ( $lst->list_type ?? 'static' ) );
                        $rm = wp_nonce_url( add_query_arg( array( 'action' => 'remove_from_list', 'user_id' => $view_uid, 'list_id' => $lst->id ), $page_url ), 'remove-from-list-' . $view_uid . '-' . $lst->id ); ?>
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#f0f6fc;color:#0073aa;border:1px solid #b8daff;border-radius:12px;padding:3px 10px;font-size:12px;margin:2px;white-space:nowrap">
                            <?php echo esc_html( $lst->name ); ?>
                            <?php if ( $is_dynamic ) : ?>
                                <em style="font-size:10px;opacity:.7"><?php esc_html_e( 'auto', 'buddyboss-crm' ); ?></em>
                            <?php else : ?>
                                <a href="<?php echo esc_url( $rm ); ?>" style="color:inherit;text-decoration:none;font-weight:700" title="Remove" onclick="return confirm('Remove from list?')">×</a>
                            <?php endif; ?>
                        </span>
                    <?php endforeach;
                endif; ?>
            </div>

            <?php if ( ! empty( $available_lists ) ) : ?>
            <form method="post" action="<?php echo esc_url( $page_url ); ?>" style="display:flex;gap:6px">
                <?php wp_nonce_field( 'bb_crm_add_to_list' ); ?>
                <input type="hidden" name="action" value="add_to_list">
                <input type="hidden" name="user_id" value="<?php echo absint( $view_uid ); ?>">
                <select name="list_id" style="flex:1">
                    <option value=""><?php esc_html_e( '— Add to list —', 'buddyboss-crm' ); ?></option>
                    <?php foreach ( $available_lists as $lst ) : ?>
                        <option value="<?php echo absint( $lst->id ); ?>"><?php echo esc_html( $lst->name ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="button button-primary"><?php esc_html_e( 'Add', 'buddyboss-crm' ); ?></button>
            </form>
            <?php elseif ( empty( $all_lists ) ) : ?>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-lists' ) ); ?>" class="button button-small"><?php esc_html_e( 'Create lists first', 'buddyboss-crm' ); ?></a>
            <?php else : ?>
                <p style="color:#888;font-size:12px;margin:0"><?php esc_html_e( 'User is in all lists.', 'buddyboss-crm' ); ?></p>
            <?php endif; ?>
        </div>

        <!-- ── WordPress / BuddyBoss info card ── -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px">
            <h3 style="margin:0 0 14px;font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:#555"><?php esc_html_e( 'Account Details', 'buddyboss-crm' ); ?></h3>
            <table style="width:100%;font-size:13px;border-collapse:collapse">
                <?php
                $details = array(
                    __( 'First Name', 'buddyboss-crm' ) => $wp_user->first_name ?: '—',
                    __( 'Last Name',  'buddyboss-crm' ) => $wp_user->last_name  ?: '—',
                    __( 'Username',   'buddyboss-crm' ) => $wp_user->user_login,
                    __( 'Email',      'buddyboss-crm' ) => $wp_user->user_email,
                    __( 'Role',       'buddyboss-crm' ) => implode( ', ', array_map( 'translate_user_role', array_map( 'ucfirst', (array) $wp_user->roles ) ) ),
                    __( 'Registered', 'buddyboss-crm' ) => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $wp_user->user_registered ) ),
                    __( 'Website',    'buddyboss-crm' ) => $wp_user->user_url ?: '—',
                );
                // Append BuddyPress fields if any.
                foreach ( $bp_fields as $label => $val ) {
                    $details[ $label ] = is_array( $val ) ? implode( ', ', $val ) : $val;
                }
                foreach ( $details as $label => $val ) : ?>
                <tr style="border-bottom:1px solid #f0f0f0">
                    <td style="padding:6px 8px 6px 0;color:#888;width:40%;vertical-align:top"><?php echo esc_html( $label ); ?></td>
                    <td style="padding:6px 0;color:#222"><?php echo esc_html( wp_strip_all_tags( $val ) ); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <!-- ── Tag history card ── -->
        <div style="background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px">
            <h3 style="margin:0 0 14px;font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:#555"><?php esc_html_e( 'Tag History', 'buddyboss-crm' ); ?></h3>
            <?php if ( empty( $history ) ) : ?>
                <p style="color:#aaa;font-size:13px"><?php esc_html_e( 'No history recorded yet.', 'buddyboss-crm' ); ?></p>
            <?php else : ?>
                <div style="max-height:320px;overflow-y:auto">
                    <?php foreach ( $history as $h ) :
                        $color   = $h->color ?: '#999';
                        $is_add  = ( 'added' === $h->action );
                        $dot_col = $is_add ? '#46b450' : '#dc3232';
                    ?>
                    <div style="display:flex;align-items:flex-start;gap:10px;padding:8px 0;border-bottom:1px solid #f5f5f5">
                        <span style="width:8px;height:8px;border-radius:50%;background:<?php echo esc_attr( $dot_col ); ?>;flex-shrink:0;margin-top:4px"></span>
                        <div style="flex:1;min-width:0">
                            <span style="font-size:13px">
                                <?php echo $is_add ? esc_html__( 'Tag added:', 'buddyboss-crm' ) : esc_html__( 'Tag removed:', 'buddyboss-crm' ); ?>
                                <strong style="color:<?php echo esc_attr( $color ); ?>"><?php echo esc_html( $h->tag_name ); ?></strong>
                            </span>
                            <br>
                            <span style="font-size:11px;color:#999">
                                <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $h->performed_at ) ) ); ?>
                                <?php if ( $h->done_by ) echo ' · ' . esc_html__( 'by', 'buddyboss-crm' ) . ' ' . esc_html( $h->done_by ); ?>
                                <?php if ( $h->source && 'manual' !== $h->source ) echo ' · ' . esc_html( $h->source ); ?>
                            </span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div><!-- /grid -->

    <?php if ( function_exists( 'buddypress' ) ) : ?>
    <!-- ── BuddyBoss activity timeline ── -->
    <div style="background:#fff;border:1px solid #e0e0e0;border-radius:4px;padding:20px;margin-top:20px">
        <h3 style="margin:0 0 16px;font-size:14px;text-transform:uppercase;letter-spacing:.05em;color:#555"><?php esc_html_e( 'BuddyBoss Activity', 'buddyboss-crm' ); ?></h3>

        <?php if ( empty( $bp_activity ) ) : ?>
            <p style="color:#aaa;font-size:13px"><?php esc_html_e( 'No activity recorded yet.', 'buddyboss-crm' ); ?></p>
        <?php else :
            // Component → [ dashicon, colour ]
            $comp_map = array(
                'groups'   => array( 'dashicons-groups',       '#7c3aed' ),
                'activity' => array( 'dashicons-admin-comments','#0073aa' ),
                'friends'  => array( 'dashicons-admin-users',  '#059669' ),
                'blogs'    => array( 'dashicons-admin-post',   '#b45309' ),
                'forums'   => array( 'dashicons-format-chat',  '#0891b2' ),
                'messages' => array( 'dashicons-email-alt',    '#db2777' ),
                'xprofile' => array( 'dashicons-id',           '#64748b' ),
                'members'  => array( 'dashicons-admin-users',  '#059669' ),
            );
            // Type → human label (fallback is formatted $activity->action HTML)
            $type_labels = array(
                'joined_group'        => __( 'Joined group',         'buddyboss-crm' ),
                'left_group'          => __( 'Left group',           'buddyboss-crm' ),
                'created_group'       => __( 'Created group',        'buddyboss-crm' ),
                'group_details_updated' => __( 'Updated group details', 'buddyboss-crm' ),
                'activity_update'     => __( 'Posted an update',     'buddyboss-crm' ),
                'activity_comment'    => __( 'Commented on a post',  'buddyboss-crm' ),
                'friendship_created'  => __( 'Connected with a member', 'buddyboss-crm' ),
                'friendship_accepted' => __( 'Accepted a connection', 'buddyboss-crm' ),
                'new_member'          => __( 'Joined the community', 'buddyboss-crm' ),
                'new_blog_post'       => __( 'Published a post',     'buddyboss-crm' ),
                'new_blog_comment'    => __( 'Commented on a post',  'buddyboss-crm' ),
                'bbp_reply_create'    => __( 'Replied in a forum',   'buddyboss-crm' ),
                'bbp_topic_create'    => __( 'Created a forum topic', 'buddyboss-crm' ),
                'updated_profile'     => __( 'Updated profile',      'buddyboss-crm' ),
            );
        ?>
        <div style="max-height:480px;overflow-y:auto;position:relative">
            <!-- vertical line -->
            <div style="position:absolute;left:15px;top:0;bottom:0;width:2px;background:#f0f0f0"></div>

            <?php foreach ( $bp_activity as $act ) :
                $comp      = $act->component ?? 'activity';
                $icon_data = $comp_map[ $comp ] ?? array( 'dashicons-clock', '#94a3b8' );
                $icon      = $icon_data[0];
                $dot_color = $icon_data[1];
                $label     = $type_labels[ $act->type ] ?? null;
                // Use pre-formatted action HTML if no simple label available.
                $use_raw   = ( $label === null );
            ?>
            <div style="display:flex;align-items:flex-start;gap:12px;padding:10px 0 10px 0;border-bottom:1px solid #f8f8f8">
                <!-- icon dot -->
                <div style="flex-shrink:0;width:30px;height:30px;border-radius:50%;background:<?php echo esc_attr( $dot_color ); ?>18;border:2px solid <?php echo esc_attr( $dot_color ); ?>44;display:flex;align-items:center;justify-content:center;position:relative;z-index:1">
                    <span class="dashicons <?php echo esc_attr( $icon ); ?>" style="color:<?php echo esc_attr( $dot_color ); ?>;font-size:14px;width:14px;height:14px"></span>
                </div>
                <!-- text -->
                <div style="flex:1;min-width:0;padding-top:4px">
                    <div style="font-size:13px;color:#222;line-height:1.5">
                        <?php if ( $use_raw ) : ?>
                            <?php echo wp_kses_post( $act->action ); ?>
                        <?php else : ?>
                            <strong><?php echo esc_html( $label ); ?></strong>
                            <?php if ( $act->action ) : ?>
                                <span style="color:#555"> — <?php echo wp_kses_post( $act->action ); ?></span>
                            <?php endif; ?>
                        <?php endif; ?>
                        <?php if ( ! empty( $act->content ) ) : ?>
                            <p style="margin:4px 0 0;color:#666;font-size:12px;white-space:pre-wrap;word-break:break-word"><?php echo wp_kses_post( wp_trim_words( strip_tags( $act->content ), 30, '…' ) ); ?></p>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:11px;color:#aaa;margin-top:2px">
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $act->date_recorded ) ) ); ?>
                        <span style="color:#ddd"> · </span>
                        <span style="color:#bbb;text-transform:capitalize"><?php echo esc_html( str_replace( '_', ' ', $act->component ) ); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; // buddypress active ?>

</div>

<?php
// End of profile view — stop here.
return;
endif; // 'view' === $action

// ════════════════════════════════════════════════════════════════════════════
// LIST VIEW: paginated users table
// ════════════════════════════════════════════════════════════════════════════

$per_page     = 20;
$current_page = max( 1, absint( $_GET['paged'] ?? 1 ) );
$offset       = ( $current_page - 1 ) * $per_page;
$search       = sanitize_text_field( $_GET['s'] ?? '' );
$sub_filter   = sanitize_key( $_GET['sub_status'] ?? '' ); // 'subscribed' | 'unsubscribed' | ''

$args = array(
    'number'  => $per_page,
    'offset'  => $offset,
    'orderby' => 'display_name',
    'order'   => 'ASC',
    'fields'  => array( 'ID', 'display_name', 'user_email' ),
);
if ( $search ) {
    $args['search']         = '*' . $search . '*';
    $args['search_columns'] = array( 'display_name', 'user_email', 'user_login' );
}

$user_query  = new WP_User_Query( $args );
$users       = $user_query->get_results();
$total_users = $user_query->get_total();
$total_pages = ceil( $total_users / $per_page );

$all_tags = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tags ORDER BY name ASC" );

// Load tag assignments for displayed users.
$user_ids  = wp_list_pluck( $users, 'ID' );
$user_tags = array();
if ( ! empty( $user_ids ) ) {
    $in_clause = implode( ',', array_map( 'absint', $user_ids ) );
    $rows = $wpdb->get_results(
        "SELECT ut.user_id, t.id AS tag_id, t.name AS tag_name, t.color
         FROM {$prefix}bb_user_tags ut
         JOIN {$prefix}bb_tags t ON t.id = ut.tag_id
         WHERE ut.user_id IN ($in_clause)
         ORDER BY t.name ASC"
    );
    foreach ( $rows as $row ) {
        $user_tags[ $row->user_id ][] = $row;
    }
}

?>

<div class="wrap bb-crm-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Manage Users', 'buddyboss-crm' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( $notice ) : ?>
        <div class="notice notice-<?php echo $notice_is_error ? 'warning' : 'success'; ?> is-dismissible">
            <p><?php echo esc_html( $notice_msgs[ $notice ] ?? '' ); ?></p>
        </div>
    <?php endif; ?>

    <!-- Search + Filter -->
    <form method="get" action="<?php echo esc_url( $page_url ); ?>" style="margin-bottom:16px;display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="page" value="buddyboss-crm-users">
        <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search users…', 'buddyboss-crm' ); ?>" class="regular-text">
        <?php if ( class_exists( 'BB_Camp_Unsubscribe' ) ) : ?>
        <select name="sub_status" style="min-width:160px">
            <option value="" <?php selected( $sub_filter, '' ); ?>><?php esc_html_e( '— All subscribers —', 'buddyboss-crm' ); ?></option>
            <option value="subscribed" <?php selected( $sub_filter, 'subscribed' ); ?>><?php esc_html_e( 'Subscribed', 'buddyboss-crm' ); ?></option>
            <option value="unsubscribed" <?php selected( $sub_filter, 'unsubscribed' ); ?>><?php esc_html_e( 'Unsubscribed', 'buddyboss-crm' ); ?></option>
        </select>
        <?php endif; ?>
        <button type="submit" class="button"><?php esc_html_e( 'Search', 'buddyboss-crm' ); ?></button>
        <?php if ( $search || $sub_filter ) : ?>
            <a href="<?php echo esc_url( $page_url ); ?>" class="button"><?php esc_html_e( 'Clear', 'buddyboss-crm' ); ?></a>
        <?php endif; ?>
    </form>

    <?php if ( empty( $users ) ) : ?>
        <div class="bb-crm-empty-state">
            <span class="dashicons dashicons-admin-users"></span>
            <h3><?php esc_html_e( 'No users found', 'buddyboss-crm' ); ?></h3>
            <p><?php esc_html_e( 'Try a different search term.', 'buddyboss-crm' ); ?></p>
        </div>
    <?php else : ?>

        <!-- Bulk action form -->
        <form method="post" action="<?php echo esc_url( $page_url ); ?>" id="bb-crm-bulk-form">
            <?php wp_nonce_field( 'bb_crm_bulk_users' ); ?>
            <input type="hidden" name="bb_crm_bulk_action" value="1">
            <?php if ( $search ) : ?><input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>"><?php endif; ?>
            <?php if ( $current_page > 1 ) : ?><input type="hidden" name="paged" value="<?php echo absint( $current_page ); ?>"><?php endif; ?>

            <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                <span style="font-size:13px;color:#666" id="bb-crm-selected-count">0 selected</span>
                <select name="bulk_action" id="bb-crm-bulk-action-select">
                    <option value=""><?php esc_html_e( '— Bulk Action —', 'buddyboss-crm' ); ?></option>
                    <option value="add_tag"><?php esc_html_e( 'Add Tag', 'buddyboss-crm' ); ?></option>
                    <option value="remove_tag"><?php esc_html_e( 'Remove Tag', 'buddyboss-crm' ); ?></option>
                    <option value="add_to_list"><?php esc_html_e( 'Add to List', 'buddyboss-crm' ); ?></option>
                    <option value="remove_from_list"><?php esc_html_e( 'Remove from List', 'buddyboss-crm' ); ?></option>
                </select>
                <?php if ( ! empty( $all_tags ) ) : ?>
                    <select name="bulk_tag_id" id="bb-crm-bulk-tag-select">
                        <option value=""><?php esc_html_e( '— Select Tag —', 'buddyboss-crm' ); ?></option>
                        <?php foreach ( $all_tags as $tag ) : ?>
                            <option value="<?php echo absint( $tag->id ); ?>"><?php echo esc_html( $tag->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <?php
                $all_lists_bulk = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_user_lists ORDER BY name ASC" );
                if ( ! empty( $all_lists_bulk ) ) : ?>
                    <select name="bulk_list_id" id="bb-crm-bulk-list-select" style="display:none">
                        <option value=""><?php esc_html_e( '— Select List —', 'buddyboss-crm' ); ?></option>
                        <?php foreach ( $all_lists_bulk as $lst ) : ?>
                            <option value="<?php echo absint( $lst->id ); ?>"><?php echo esc_html( $lst->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <button type="submit" class="button"><?php esc_html_e( 'Apply', 'buddyboss-crm' ); ?></button>
            </div>

            <p style="color:#666;margin:0 0 8px"><?php printf(
                esc_html__( 'Showing %1$d–%2$d of %3$d users', 'buddyboss-crm' ),
                $offset + 1, min( $offset + $per_page, $total_users ), $total_users
            ); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="bb-crm-select-all"></th>
                        <th style="width:200px"><?php esc_html_e( 'User', 'buddyboss-crm' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'buddyboss-crm' ); ?></th>
                        <?php if ( class_exists( 'BB_Camp_Unsubscribe' ) ) : ?>
                        <th style="width:130px"><?php esc_html_e( 'Email Status', 'buddyboss-crm' ); ?></th>
                        <?php endif; ?>
                        <th><?php esc_html_e( 'Tags', 'buddyboss-crm' ); ?></th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $users as $user ) :
                        $tags        = $user_tags[ $user->ID ] ?? array();
                        $view_url    = add_query_arg( array( 'action' => 'view', 'user_id' => $user->ID ), $page_url );
                        $is_unsub    = isset( $unsub_emails[ $user->user_email ] );
                    ?>
                    <tr>
                        <td><input type="checkbox" name="user_ids[]" value="<?php echo absint( $user->ID ); ?>" class="bb-crm-user-cb"></td>
                        <td>
                            <a href="<?php echo esc_url( $view_url ); ?>" style="font-weight:600;text-decoration:none"><?php echo esc_html( $user->display_name ); ?></a><br>
                            <small style="color:#888">#<?php echo absint( $user->ID ); ?></small>
                        </td>
                        <td><?php echo esc_html( $user->user_email ); ?></td>
                        <?php if ( class_exists( 'BB_Camp_Unsubscribe' ) ) : ?>
                        <td>
                            <?php if ( $is_unsub ) : ?>
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:10px;padding:2px 9px;font-size:11px;white-space:nowrap">
                                    <span class="dashicons dashicons-no-alt" style="font-size:12px;width:12px;height:12px"></span>
                                    <?php esc_html_e( 'Unsubscribed', 'buddyboss-crm' ); ?>
                                </span>
                            <?php else : ?>
                                <span style="display:inline-flex;align-items:center;gap:4px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:10px;padding:2px 9px;font-size:11px;white-space:nowrap">
                                    <span class="dashicons dashicons-yes" style="font-size:12px;width:12px;height:12px"></span>
                                    <?php esc_html_e( 'Subscribed', 'buddyboss-crm' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <?php if ( empty( $tags ) ) : ?>
                                <span style="color:#aaa">—</span>
                            <?php else :
                                foreach ( $tags as $t ) :
                                    $color = $t->color ?: '#999'; ?>
                                    <span style="display:inline-flex;align-items:center;background:<?php echo esc_attr($color); ?>22;color:<?php echo esc_attr($color); ?>;border:1px solid <?php echo esc_attr($color); ?>55;border-radius:10px;padding:1px 8px;font-size:11px;margin:1px;white-space:nowrap">
                                        <?php echo esc_html( $t->tag_name ); ?>
                                    </span>
                                <?php endforeach;
                            endif; ?>
                        </td>
                        <td><a href="<?php echo esc_url( $view_url ); ?>" class="button button-small"><?php esc_html_e( 'View', 'buddyboss-crm' ); ?></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <?php if ( $total_pages > 1 ) : ?>
            <div style="margin-top:12px">
                <?php echo paginate_links( array(
                    'base'      => add_query_arg( 'paged', '%#%', $page_url ),
                    'format'    => '',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ) ); ?>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<script>
(function(){
    var selectAll = document.getElementById('bb-crm-select-all');
    var cbs       = document.querySelectorAll('.bb-crm-user-cb');
    var countEl   = document.getElementById('bb-crm-selected-count');

    function updateCount() {
        var n = document.querySelectorAll('.bb-crm-user-cb:checked').length;
        if ( countEl ) countEl.textContent = n + ' selected';
        if ( selectAll ) selectAll.indeterminate = n > 0 && n < cbs.length;
    }

    if ( selectAll ) {
        selectAll.addEventListener('change', function(){
            cbs.forEach(function(cb){ cb.checked = selectAll.checked; });
            updateCount();
        });
    }
    cbs.forEach(function(cb){
        cb.addEventListener('change', updateCount);
    });

    var actionSel = document.getElementById('bb-crm-bulk-action-select');
    var tagSelEl  = document.getElementById('bb-crm-bulk-tag-select');
    var listSelEl = document.getElementById('bb-crm-bulk-list-select');
    if ( actionSel ) {
        actionSel.addEventListener('change', function(){
            var v = this.value;
            if ( tagSelEl )  tagSelEl.style.display  = ( v === 'add_tag' || v === 'remove_tag' ) ? '' : 'none';
            if ( listSelEl ) listSelEl.style.display = ( v === 'add_to_list' || v === 'remove_from_list' ) ? '' : 'none';
        });
    }

    var bulkForm = document.getElementById('bb-crm-bulk-form');
    if ( bulkForm ) {
        bulkForm.addEventListener('submit', function(e){
            var action   = document.getElementById('bb-crm-bulk-action-select').value;
            var tagSel   = document.getElementById('bb-crm-bulk-tag-select');
            var listSel  = document.getElementById('bb-crm-bulk-list-select');
            var checked  = document.querySelectorAll('.bb-crm-user-cb:checked').length;
            var isTagAction  = ( action === 'add_tag' || action === 'remove_tag' );
            var isListAction = ( action === 'add_to_list' || action === 'remove_from_list' );
            if ( ! action )                           { e.preventDefault(); alert('Please select a bulk action.'); return; }
            if ( isTagAction  && tagSel  && ! tagSel.value  ) { e.preventDefault(); alert('Please select a tag.');  return; }
            if ( isListAction && listSel && ! listSel.value ) { e.preventDefault(); alert('Please select a list.'); return; }
            if ( checked === 0 )                      { e.preventDefault(); alert('Please select at least one user.'); return; }
        });
    }
})();
</script>
