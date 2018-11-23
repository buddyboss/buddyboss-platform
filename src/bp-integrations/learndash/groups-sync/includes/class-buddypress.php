<?php

class LearnDash_BuddyPress_Groups_Sync_BuddyPress
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

        add_action('bp_groups_admin_meta_boxes', [$this, 'add_associated_group_metabox']);
        add_action('groups_before_delete_group', [$this, 'dissociate_learndash_group']);
        add_action('groups_details_updated', [$this, 'associate_learndash_group']);
    }

    public function add_associated_group_metabox()
    {
        add_meta_box(
            'buddypress-associated-learndash-group',
            __('Associated LearnDash Group', 'ld_bp_groups_sync'),
            [$this, 'associated_group_metabox_html'],
            get_current_screen()->id,
            'side'
        );
    }

    public function associated_group_metabox_html()
    {
        require bp_learndash_path('groups-sync/templates/admin/buddypress-associated-group-metabox.php');
    }

    public function dissociate_learndash_group($bp_group_id)
    {
        $ld_group = ld_bp_groups_sync_get_associated_ld_group($bp_group_id);
        $generator = new LearnDash_BuddyPress_Groups_Sync_Generator($ld_group);
        $generator->dissociate();
    }

    public function associate_learndash_group($bp_group_id)
    {
        $settings = ld_bp_groups_sync_get_settings();
        $ld_group = ld_bp_groups_sync_get_associated_ld_group($bp_group_id);

        if (! $this->request_value('ld_bp_groups_sync')) {
            return;
        }

        $request  = wp_parse_args($this->request_value('ld_bp_groups_sync'), [
            'update_leaders'      => $settings['auto_sync_leaders'],
            'update_students'     => $settings['auto_sync_students'],
            'learndash_group_id' => 0
        ]);

        if (! $request['learndash_group_id']) {
            return;
        }

        $bp_group = ld_bp_groups_sync_associate_bp_group(
            $request['learndash_group_id'],
            $bp_group_id,
            $request['update_leaders'],
            $request['update_students']
        );
    }

    protected function request_value($key, $default = null)
    {
        return isset($_REQUEST[$key])? $_REQUEST[$key] : $default;
    }
}

return new LearnDash_BuddyPress_Groups_Sync_BuddyPress;




