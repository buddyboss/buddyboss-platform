<?php
/**
 * Admin Dashboard View
 *
 * @package BuddyBossCRM
 * @since 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div class="wrap bb-crm-wrap">
    <h1 class="wp-heading-inline">
        <?php _e( 'BuddyBoss CRM Dashboard', 'buddyboss-crm' ); ?>
    </h1>

    <hr class="wp-header-end">

    <?php
    // ── Automation failure notice (REL-01) ────────────────────────────────────
    $bb_crm_failure_count = 0;
    if ( class_exists( 'BB_CRM_Auto_Engine' ) ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching
        $bb_crm_failure_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}bb_crm_automation_log WHERE status = 'failed'"
        );
    }
    if ( $bb_crm_failure_count > 0 ) :
        $bb_crm_log_url = admin_url( 'admin.php?page=buddyboss-crm-automation-log' );
    ?>
    <div class="notice notice-error" style="margin-bottom:16px">
        <p>
            <?php
            printf(
                wp_kses(
                    /* translators: %1$d: failure count, %2$s: log URL */
                    __( '%1$d automation failure(s) logged. <a href="%2$s">View automation log &rarr;</a>', 'buddyboss-crm' ),
                    array( 'a' => array( 'href' => array() ) )
                ),
                $bb_crm_failure_count,
                esc_url( $bb_crm_log_url )
            );
            ?>
        </p>
    </div>
    <?php endif; ?>

    <div class="bb-crm-dashboard">
        <!-- Stats Cards -->
        <div class="bb-crm-stats-grid">
            <div class="bb-crm-stat-card">
                <div class="bb-crm-stat-icon">
                    <span class="dashicons dashicons-tag"></span>
                </div>
                <div class="bb-crm-stat-content">
                    <h3><?php echo number_format( $total_tags ); ?></h3>
                    <p><?php _e( 'Total Tags', 'buddyboss-crm' ); ?></p>
                </div>
                <div class="bb-crm-stat-action">
                    <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-tags' ); ?>" class="button">
                        <?php _e( 'Manage Tags', 'buddyboss-crm' ); ?>
                    </a>
                </div>
            </div>

            <div class="bb-crm-stat-card">
                <div class="bb-crm-stat-icon">
                    <span class="dashicons dashicons-category"></span>
                </div>
                <div class="bb-crm-stat-content">
                    <h3><?php echo number_format( $total_categories ); ?></h3>
                    <p><?php _e( 'Categories', 'buddyboss-crm' ); ?></p>
                </div>
                <div class="bb-crm-stat-action">
                    <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-categories' ); ?>" class="button">
                        <?php _e( 'Manage Categories', 'buddyboss-crm' ); ?>
                    </a>
                </div>
            </div>

            <div class="bb-crm-stat-card">
                <div class="bb-crm-stat-icon">
                    <span class="dashicons dashicons-groups"></span>
                </div>
                <div class="bb-crm-stat-content">
                    <h3><?php echo number_format( $total_users ); ?></h3>
                    <p><?php _e( 'Tagged Users', 'buddyboss-crm' ); ?></p>
                </div>
                <div class="bb-crm-stat-action">
                    <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-users' ); ?>" class="button">
                        <?php _e( 'Manage Users', 'buddyboss-crm' ); ?>
                    </a>
                </div>
            </div>

            <div class="bb-crm-stat-card">
                <div class="bb-crm-stat-icon">
                    <span class="dashicons dashicons-admin-links"></span>
                </div>
                <div class="bb-crm-stat-content">
                    <h3><?php echo number_format( $total_assignments ); ?></h3>
                    <p><?php _e( 'Tag Assignments', 'buddyboss-crm' ); ?></p>
                </div>
                <div class="bb-crm-stat-action">
                    <span class="bb-crm-stat-note"><?php _e( 'Total relationships', 'buddyboss-crm' ); ?></span>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="bb-crm-section">
            <h2><?php _e( 'Quick Actions', 'buddyboss-crm' ); ?></h2>
            <div class="bb-crm-quick-actions">
                <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-tags&action=add' ); ?>" class="bb-crm-action-card">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <h3><?php _e( 'Create New Tag', 'buddyboss-crm' ); ?></h3>
                    <p><?php _e( 'Add a new tag to categorize users', 'buddyboss-crm' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-users' ); ?>" class="bb-crm-action-card">
                    <span class="dashicons dashicons-admin-users"></span>
                    <h3><?php _e( 'Tag Users', 'buddyboss-crm' ); ?></h3>
                    <p><?php _e( 'Assign tags to your community members', 'buddyboss-crm' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-lists' ); ?>" class="bb-crm-action-card">
                    <span class="dashicons dashicons-list-view"></span>
                    <h3><?php _e( 'Create User List', 'buddyboss-crm' ); ?></h3>
                    <p><?php _e( 'Build dynamic lists based on tags', 'buddyboss-crm' ); ?></p>
                </a>

                <a href="<?php echo admin_url( 'admin.php?page=buddyboss-automations' ); ?>" class="bb-crm-action-card">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <h3><?php _e( 'Setup Automation', 'buddyboss-crm' ); ?></h3>
                    <p><?php _e( 'Automate tag workflows based on user actions', 'buddyboss-crm' ); ?></p>
                </a>
            </div>
        </div>

        <!-- System Status -->
        <div class="bb-crm-section">
            <h2><?php _e( 'System Status', 'buddyboss-crm' ); ?></h2>
            <table class="widefat bb-crm-status-table">
                <tbody>
                    <tr>
                        <td class="bb-crm-status-label">
                            <strong><?php _e( 'Database Version', 'buddyboss-crm' ); ?></strong>
                        </td>
                        <td>
                            <span class="bb-crm-status-badge bb-crm-status-success">
                                <?php echo BB_CRM_Install::get_db_version(); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td class="bb-crm-status-label">
                            <strong><?php _e( 'BuddyBoss Platform', 'buddyboss-crm' ); ?></strong>
                        </td>
                        <td>
                            <?php if ( function_exists( 'buddypress' ) ) : ?>
                                <span class="bb-crm-status-badge bb-crm-status-success">
                                    <?php _e( 'Active', 'buddyboss-crm' ); ?> (<?php echo function_exists( 'bp_get_version' ) ? bp_get_version() : 'Unknown'; ?>)
                                </span>
                            <?php else : ?>
                                <span class="bb-crm-status-badge bb-crm-status-warning">
                                    <?php _e( 'Not Installed (Development Mode)', 'buddyboss-crm' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="bb-crm-status-label">
                            <strong><?php _e( 'Tag History', 'buddyboss-crm' ); ?></strong>
                        </td>
                        <td>
                            <?php if ( get_option( 'bb_crm_enable_tag_history', '1' ) ) : ?>
                                <span class="bb-crm-status-badge bb-crm-status-success">
                                    <?php _e( 'Enabled', 'buddyboss-crm' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="bb-crm-status-badge bb-crm-status-warning">
                                    <?php _e( 'Disabled', 'buddyboss-crm' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="bb-crm-status-label">
                            <strong><?php _e( 'Automations', 'buddyboss-crm' ); ?></strong>
                        </td>
                        <td>
                            <?php if ( class_exists( 'BB_CRM_Automations' ) ) : ?>
                                <span class="bb-crm-status-badge bb-crm-status-success">
                                    <?php _e( 'Active', 'buddyboss-crm' ); ?>
                                </span>
                            <?php else : ?>
                                <span class="bb-crm-status-badge">
                                    <?php _e( 'Add-on not installed', 'buddyboss-crm' ); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Getting Started -->
        <div class="bb-crm-section">
            <h2><?php _e( 'Getting Started', 'buddyboss-crm' ); ?></h2>
            <div class="bb-crm-getting-started">
                <div class="bb-crm-step">
                    <span class="bb-crm-step-number">1</span>
                    <div class="bb-crm-step-content">
                        <h3><?php _e( 'Create Tags', 'buddyboss-crm' ); ?></h3>
                        <p><?php _e( 'Start by creating tags to categorize your community members.', 'buddyboss-crm' ); ?></p>
                        <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-tags' ); ?>" class="button button-primary">
                            <?php _e( 'Go to Tags', 'buddyboss-crm' ); ?>
                        </a>
                    </div>
                </div>

                <div class="bb-crm-step">
                    <span class="bb-crm-step-number">2</span>
                    <div class="bb-crm-step-content">
                        <h3><?php _e( 'Assign Tags to Users', 'buddyboss-crm' ); ?></h3>
                        <p><?php _e( 'Tag your community members individually or in bulk.', 'buddyboss-crm' ); ?></p>
                        <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-users' ); ?>" class="button button-primary">
                            <?php _e( 'Manage Users', 'buddyboss-crm' ); ?>
                        </a>
                    </div>
                </div>

                <div class="bb-crm-step">
                    <span class="bb-crm-step-number">3</span>
                    <div class="bb-crm-step-content">
                        <h3><?php _e( 'Create Dynamic Lists', 'buddyboss-crm' ); ?></h3>
                        <p><?php _e( 'Build user lists based on tag combinations for targeting.', 'buddyboss-crm' ); ?></p>
                        <a href="<?php echo admin_url( 'admin.php?page=buddyboss-crm-lists' ); ?>" class="button button-primary">
                            <?php _e( 'Create Lists', 'buddyboss-crm' ); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
