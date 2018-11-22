<div class="sfwd sfwd_options <?php echo $this->settings_section_key; ?>">
    <table id="learndash-buddypress-groups-sync-tools" class="widefat" cellspacing="0" style="border: none;">
        <tbody>
            <tr>
                <td class="learndash-data-upgrades-button-container" style="width:20%">
                    <button
                        class="ld_bp_groups_sync-scan-groups-button button button-primary"
                        data-nonce="<?php echo wp_create_nonce('ld_bp_groups_sync-scan-ld-groups'); ?>"
                        data-url="<?php echo admin_url('admin-ajax.php'); ?>"
                        data-slug=""
                    >
                        <?php _e('Scan LearnDash Groups', 'ld_bp_groups_sync'); ?>
                    </button>

                    <div class="spinner"></div>
                </td>

                <td class="learndash-data-upgrades-status-container" style="width: 80%">
                    <p><?php _e('Scan for unassociated LearnDash groups. This tool is useful if you already have LearnDash groups on your site and want to link up with new or existing BuddyPress groups.', 'ld_bp_groups_sync'); ?></p>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="ld_bp_groups_sync-scan-results" style="display: none;"></div>
</div>
