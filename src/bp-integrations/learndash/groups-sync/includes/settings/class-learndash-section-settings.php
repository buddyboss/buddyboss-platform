<?php

class LearnDash_Settings_Section_BuddyPress_Groups_Sync extends LearnDash_Settings_Section
{
    public function __construct()
    {
        $this->settings_page_id       = 'learndash_lms_settings_buddypress_groups_sync';
        $this->setting_option_key     = 'learndash_settings_buddypress_groups_sync';
        $this->settings_section_key   = 'settings_buddypress_groups_sync';
        $this->settings_section_label = esc_html_x('Groups Sync Global Settings', 'Settings Section Label', 'ld_bp_groups_sync');

        parent::__construct();
    }

    public function load_settings_fields()
    {
        $fields = [];

        $fields['auto_create_bp_group'] = [
            'name'        =>  'auto_create_bp_group',
            'type'        =>  'toggle',
            'label'       =>  esc_html__('Generate BuddyPress Group', 'learndash'),
            'desc_before' =>  $this->sub_section_heading(__('On LearnDash Group Created...', 'ld_bp_groups_sync')),
            'help_text'   =>  esc_html__('Automatically generate and associate a BuddyPress group upon LearnDash group creation. Uncheck this to create and associate the group manually. This is a global setting and can be overwritten on individual group.', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_create_bp_group', '1'),
            'options'     =>  ['1' => esc_html__('Yes', 'learndash')]
        ];

        $fields['auto_bp_group_privacy'] = [
            'name'        =>  'auto_bp_group_privacy',
            'type'        =>  'select',
            'label'       =>  esc_html__('Generated BuddyPress Group Privacy', 'learndash'),
            'help_text'   =>  esc_html__('When a BuddyPress group is generated, set the group privacy to...', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_bp_group_privacy', 'private'),
            'options'     =>  [
                'public'  => __('Public', 'learndash'),
                'private' => __('Private', 'learndash'),
                'hidden'  => __('Hidden', 'learndash')
            ]
        ];

        $fields['auto_bp_group_invite_status'] = [
            'name'        =>  'auto_bp_group_invite_status',
            'type'        =>  'select',
            'label'       =>  esc_html__('Generated BuddyPress Group Invite Status', 'learndash'),
            'help_text'   =>  esc_html__('When a BuddyPress group is generated, set the group invite status to...', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_bp_group_invite_status', 'mods'),
            'options'     =>  [
                'members' => esc_html__('All group members', 'learndash'),
                'mods'    => esc_html__('Group admins and mods only', 'learndash'),
                'admins'  => esc_html__('Group admins only', 'learndash')
            ]
        ];

        $fields['auto_sync_leaders'] = [
            'name'        =>  'auto_sync_leaders',
            'type'        =>  'toggle',
            'label'       =>  esc_html__('Sync LearnDash Group Leaders', 'learndash'),
            'desc_before' =>  $this->sub_section_heading(__('On LearnDash Group User Changed...', 'ld_bp_groups_sync')),
            'help_text'   =>  esc_html__('Automatically sync LearnDash group leaders to BuddyPress group admins/mods when LearnDash group is saved. Uncheck this to associate the BuddyPress group admins/mods manually. This is a global setting and can be overwritten on individual group.', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_sync_leaders', '1'),
            'options'     =>  ['1' => esc_html__('Yes', 'learndash')]
        ];

        $fields['auto_sync_leaders_role'] = [
            'name'        =>  'auto_sync_leaders_role',
            'type'        =>  'select',
            'label'       =>  esc_html__('LearnDash Group Leaders Role', 'learndash'),
            'help_text'   =>  esc_html__('When a LearnDash leaders is synced, their role in BuddyPress group should be...', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_sync_leaders_role', 'admin'),
            'options'     =>  [
                'admin' => __('Administrator', 'learndash'),
                'mod'   => __('Moderator', 'learndash'),
            ]
        ];

        $fields['auto_sync_students'] = [
            'name'        =>  'auto_sync_students',
            'type'        =>  'toggle',
            'label'       =>  esc_html__('Sync LearnDash Group Students', 'learndash'),
            'help_text'   =>  esc_html__('Automatically sync LearnDash group students to BuddyPress group members when LearnDash group is saved. Uncheck this to associate the BuddyPress group members manually. This is a global setting and can be overwritten on individual group.', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_sync_students', '1'),
            'options'     =>  ['1' => esc_html__('Yes', 'learndash')]
        ];

        $fields['auto_delete_bp_group'] = [
            'name'        =>  'auto_delete_bp_group',
            'type'        =>  'toggle',
            'label'       =>  esc_html__('Delete BuddyPress Group', 'learndash'),
            'desc_before' =>  $this->sub_section_heading(__('On LearnDash Group Deleted...', 'ld_bp_groups_sync')),
            'help_text'   =>  esc_html__('Automatically delete the associated BuddyPress group upon LearnDash group deletion. Uncheck this to delete the group manually.', 'ld_bp_groups_sync'),
            'value'       =>  $this->get_value('auto_delete_bp_group', '0'),
            'options'     =>  ['1' => esc_html__('Yes', 'learndash')]
        ];

        $this->setting_option_fields = apply_filters('learndash_settings_fields', $fields, $this->settings_section_key);

        parent::load_settings_fields();
    }

    public function load_settings_values()
    {
        $this->settings_values_loaded = true;
        $this->setting_option_values = ld_bp_groups_sync_get_settings();
    }

    protected function sub_section_heading($text)
    {
        $style = 'letter-spacing: .025em; margin-top: 2.5em; margin-bottom: .5em; text-transform: uppercase;';

        return sprintf('<h4 style="%s">%s</h4>', $style, $text);
    }

    protected function get_value($key, $default = null)
    {
        return isset($this->setting_option_values[$key])? $this->setting_option_values[$key] : $default;
    }
}
