<table class="widefat" style="margin-top: 30px;">
    <thead>
        <tr>
            <th width="10%"><?php _e('Group ID', 'buddyboss'); ?></th>
            <th ><?php _e('Group Name', 'buddyboss'); ?></th>
            <th width="30%"><?php _e('Status', 'buddyboss'); ?></th>
            <th width="170px"><?php _e('Actions', 'buddyboss'); ?></th>
            <th width="110px"></th>
        </tr>
    </thead>

    <tbody>
        <?php if (! $groups): ?>
            <tr>
                <td colspan="5"><?php _e('No Results Found.', 'buddyboss'); ?></td>
            </tr>
        <?php endif; ?>

        <?php foreach ($groups as $group): ?>
            <tr>
                <td><?php echo $group->ID; ?></td>
                <td><?php echo get_the_title($group->ID); ?></td>
                <td>
                    <?php
                        if ($bp_groups = ld_bp_groups_sync_get_ld_groups_has_match_name($group->ID)) {
                            $list_html = array_map(function($bp_group) {
                                return sprintf(
                                    '<li><a href="%s" target="_blank">%s (ID: %d)</a></li>',
                                    bp_get_admin_url("admin.php?page=bp-groups&gid={$bp_group->id}&action=edit"),
                                    $bp_group->name,
                                    $bp_group->id
                                );
                            }, $bp_groups);

                            printf(
                                '<span style="color: #3c763d;">
                                    %s <br/>
                                    <ul>%s</ul>
                                </span>',
                                __('BuddyBoss group with same name found:', 'buddyboss'),
                                implode("\n", $list_html)
                            );
                        } else {
                            printf(
                                '<span style="color: #aeaeae">%s</span>',
                                __('No assoticated BuddyBoss group.', 'buddyboss')
                            );
                        }
                    ?>

                    <input
                        type="hidden"
                        name="bp_learndash_groups_sync-ajax-asso-group[<?php echo $group->ID; ?>][gid]"
                        value="<?php echo $bp_group? $bp_group->id : 0; ?>"
                    />
                </td>
                <td>
                    <select name="bp_learndash_groups_sync-ajax-asso-group[<?php echo $group->ID; ?>][action]">
                        <option value="generate"><?php _e('Generate new one', 'buddyboss'); ?></option>
                        <?php foreach ($bp_groups as $i => $bp_group): ?>
                            <option value="linkup_<?php echo $bp_group->id; ?>" <?php if (! $i) echo 'selected'; ?>>
                                <?php printf(__('Link up with group ID: %d', 'buddyboss'), $bp_group->id); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="nothing"><?php _e('Do nothing', 'buddyboss'); ?></option>
                    </select>
                </td>
                <td>
                    <button
                        class="ld_bp_groups_sync-do-action-button button button-primary"
                        data-nonce="<?php echo wp_create_nonce('ld_bp_groups_sync-sync-' . $group->ID); ?>"
                        data-url="<?php echo admin_url('admin-ajax.php'); ?>"
                        data-id="<?php echo $group->ID; ?>"
                    ><?php _e('Sync', 'buddyboss'); ?></button>
                    <div class="spinner"></div>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>

    <tfoot>
        <tr>
            <th colspan="5" style="text-align: right">
                <button class="ld_bp_groups_sync-bulk-action-button button button-primary" data-stop-text="<?php esc_attr_e('Stop', 'buddyboss'); ?>">
                    <?php _e('Sync All', 'buddyboss'); ?>
                </button>
                <div class="spinner"></div>
            </th>
        </tr>
    </tfoot>
</table>
