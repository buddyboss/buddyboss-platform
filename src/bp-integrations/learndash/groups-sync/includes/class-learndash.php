<?php

class LearnDash_BuddyPress_Groups_Sync_LearnDash
{
    public function __construct()
    {
        $this->register_hooks();
    }

    public function register_hooks()
    {
    	// global $bp_learndash_requirement;

     //    if (! $bp_learndash_requirement->valid()) {
     //        return;
     //    }

        // add_action('wp_ajax_bp_learndash_groups_sync/ld-groups-scan', [$this, 'ajax_scan_learndash_groups']);
        // add_action('wp_ajax_bp_learndash_groups_sync/ld-group-sync', [$this, 'ajax_sync_learndash_groups']);
    }


    public function ajax_scan_learndash_groups()
    {
        $nonce = $this->request_value('_wpnonce');

        if (! wp_verify_nonce($nonce, 'bp_learndash_groups_sync-scan-ld-groups')) {
            wp_send_json_error([
                'html' => sprintf('<p>%s</p>', __('Invalid request, please refresh the page and try again.', 'buddyboss'))
            ]);
        }

        $groups = bp_learndash_groups_sync_get_unassociated_ld_groups();

        ob_start();
        require bp_learndash_path('groups-sync/templates/admin/ajax-learndash-group-scan-results.php');

        wp_send_json_success(['html' => ob_get_clean()]);
    }

    public function ajax_sync_learndash_groups()
    {
        $nonce  = $this->request_value('_wpnonce');
        $id     = $this->request_value('id');
        $action = $this->request_value('todo');

        if (! wp_verify_nonce($nonce, "bp_learndash_groups_sync-sync-{$id}") || ! $id || ! $action) {
            wp_send_json_error([
                'html' => sprintf(
                    '<td colspan="5" style="color: #a94442;">%s</td>',
                    __('Invalid request, please refresh the page and try again.', 'buddyboss')
                )
            ]);
        }

        if ($action == 'generate') {
            $result = bp_learndash_groups_sync_generate_bp_group($id);
        } else {
            list($_, $gid) = explode('_', $action);
            $result = $gid? bp_learndash_groups_sync_associate_bp_group($id, $gid) : false;
        }

        ob_start();
        require bp_learndash_path('groups-sync/templates/admin/ajax-learndash-group-sync-result.php');

        wp_send_json_success(['html' => ob_get_clean()]);
    }
}

return new LearnDash_BuddyPress_Groups_Sync_LearnDash;




