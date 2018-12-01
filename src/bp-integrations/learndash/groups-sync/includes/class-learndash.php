<?php

class LearnDash_BuddyPress_Groups_Sync_LearnDash
{
    public function __construct()
    {
        $this->register_hooks();
    }

    public function register_hooks()
    {
    	global $bp_learndash_requirement;

        if (! $bp_learndash_requirement->valid()) {
            return;
        }

        require_once bp_learndash_path('groups-sync/includes/class-generator.php');

        // Learndash Setting Page Ajax
        add_action('wp_ajax_bp_learndash_groups_sync/ld-groups-scan', [$this, 'ajax_scan_learndash_groups']);
        add_action('wp_ajax_bp_learndash_groups_sync/ld-group-sync', [$this, 'ajax_sync_learndash_groups']);

        // Learndash Group Edit Page
        add_action('add_meta_boxes', [$this, 'add_associated_group_metabox']);
        add_action('save_post', [$this, 'update_associated_group'], 15, 3);

        // Learndash Group Delete
        add_action('wp_trash_post', [$this, 'preserve_buddypress_group_id_and_dissociate']);
        add_action('untrash_post', [$this, 'maybe_associate_buddypress_group']);
        add_action('before_delete_post', [$this, 'maybe_delete_buddypress_group']);

        // Learndash leader update
        add_action('updated_post_meta', [$this, 'resync_leaders_on_user_changed'], 15, 3);
    }

    public function resync_leaders_on_user_changed($meta_id, $object_id, $meta_key)
    {
        if (strpos($meta_key, 'learndash_group_users_') !== 0) {
            return;
        }

        $ld_group = substr($meta_key, strlen('learndash_group_users_'));
        $generator = new LearnDash_BuddyPress_Groups_Sync_Generator($ld_group);

        if (! $generator->get_bp_group()) {
            return;
        }

        $generator->sync_all(true, true);
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

    public function add_associated_group_metabox()
    {
        add_meta_box(
            'learndash-associated-buddypress-group',
            __('Associated BuddyPress Group', 'buddyboss'),
            [$this, 'associated_group_metabox_html'],
            'groups',
            'side'
        );
    }

    public function associated_group_metabox_html()
    {
        require bp_learndash_path('groups-sync/templates/admin/learndash-associated-group-metabox.php');
    }

    public function update_associated_group($post_id, $post, $update)
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || ! $update) {
            return;
        }

        if (get_post_type($post_id) !== 'groups') {
            return;
        }

        if (get_post_status($post_id) !== 'publish') {
            return;
        }

        $settings = bp_learndash_groups_sync_get_settings();
        $bp_group = bp_learndash_groups_sync_get_associated_bp_group($post_id);
        $bp_group_id = $bp_group? $bp_group->id : false;
        $request  = wp_parse_args($this->request_value('bp_learndash_groups_sync', []), [
            'auto_create_bp_group' => $settings['auto_create_bp_group'],
            'update_leaders'       => $settings['auto_sync_leaders'],
            'update_students'      => $settings['auto_sync_students'],
            'buddypress_group_id'  => 0
        ]);

        update_post_meta($post_id, 'bp_learndash_groups_sync_settings', $request);

        if ($bp_group_id !== $request['buddypress_group_id']) {
            $bp_group = bp_learndash_groups_sync_associate_bp_group(
                $post_id,
                $request['buddypress_group_id'],
                $request['update_leaders'],
                $request['update_students']
            );
        }

        if ($request['auto_create_bp_group'] && ! $bp_group) {
            $bp_group = bp_learndash_groups_sync_generate_bp_group($post_id, $request['update_leaders'], $request['update_students']);
        }
    }

    public function preserve_buddypress_group_id_and_dissociate($post_id)
    {
        if (get_post_type($post_id) !== 'groups') {
            return;
        }

        $generator = new LearnDash_BuddyPress_Groups_Sync_Generator($post_id);

        if (! $bp_group = $generator->get_bp_group()) {
            return;
        }

        add_post_meta($post_id, '_wp_trash_meta_buddypress_group_id', $bp_group->id);
        $generator->dissociate();
    }

    public function maybe_associate_buddypress_group($post_id)
    {
        if (get_post_type($post_id) !== 'groups') {
            return;
        }

        $previous_bp_group_id = get_post_meta($post_id, '_wp_trash_meta_buddypress_group_id', true);
        delete_post_meta($post_id, '_wp_trash_meta_buddypress_group_id');

        if (bp_learndash_groups_sync_get_associated_ld_group($previous_bp_group_id)) {
            return;
        }

        remove_action('save_post_groups', [$this, 'update_associated_group'], 10, 3);

        $generator = new LearnDash_BuddyPress_Groups_Sync_Generator($post_id);
        $generator->associate($previous_bp_group_id);
    }

    public function maybe_delete_buddypress_group($post_id)
    {
        if (get_post_type($post_id) !== 'groups') {
            return;
        }

        $generator = new LearnDash_BuddyPress_Groups_Sync_Generator($post_id);

        if (! bp_learndash_groups_sync_get_settings('auto_delete_bp_group')) {
            $generator->dissociate();
            return;
        }

        if ($previous_bp_group_id = get_post_meta($post_id, '_wp_trash_meta_buddypress_group_id', true)) {
            $bp_group = groups_get_group($previous_bp_group_id);
        } else {
            $bp_group = $generator->get_bp_group();
        }

        if (! $bp_group) {
            return;
        }


        if (($ld_group = bp_learndash_groups_sync_get_associated_ld_group($bp_group->id)) && $ld_group->ID != $post_id) {
            return;
        }

        remove_action('groups_before_delete_group', [bp_learndash_groups_sync()->buddypress, 'dissociate_learndash_group']);

        $generator->dissociate();

        groups_delete_group($bp_group->id);
    }

    protected function request_value($key, $default = null)
    {
        return isset($_REQUEST[$key])? $_REQUEST[$key] : $default;
    }
}

return new LearnDash_BuddyPress_Groups_Sync_LearnDash;




