<?php
    $group_id        = isset($_REQUEST['gid'])? (int) $_REQUEST['gid'] : '';
    $post_settings   = groups_get_groupmeta($group_id, 'ld_bp_groups_sync_settings', true) ?: [];
    $global_settings = ld_bp_groups_sync_get_settings();

    $sync_leaders  = isset($post_settings['auto_sync_leaders'])? $post_settings['auto_sync_leaders'] : $global_settings['auto_sync_leaders'];
    $sync_users    = isset($post_settings['auto_sync_students'])? $post_settings['auto_sync_students'] : $global_settings['auto_sync_students'];
?>

<?php if ($ld_group = ld_bp_groups_sync_get_associated_ld_group($group_id)): ?>
    <p>
        <b><?php _e('This group is associated with:', 'buddyboss'); ?></b>
    </p>

    <a href="<?php echo get_edit_post_link($ld_group); ?>" target="_blank"><?php echo get_the_title($ld_group); ?> (ID: <?php echo $ld_group->ID; ?>)</a>
<?php else: ?>
    <p>
        <b><?php _e('Associate with: ', 'buddyboss'); ?></b>
    </p>

    <select name="ld_bp_groups_sync[learndash_group_id]" style="width: 100%; margin-bottom: 10px;">
        <option value="0"><?php _e('None', 'buddyboss'); ?></option>

        <?php if (! ld_bp_groups_sync_get_unassociated_ld_groups([], $ld_group)): ?>
            <option disabled><?php _e('No LearnDash group available.'); ?></option>
        <?php endif; ?>

        <?php foreach (ld_bp_groups_sync_get_unassociated_ld_groups([], $ld_group) as $group): ?>
            <?php $selected = $group->ID == $ld_group? 'selected' : ''; ?>
            <option value="<?php echo $group->ID; ?>" <?php echo $selected; ?>>
                <?php echo get_the_title($group); ?> (ID: <?php echo $group->ID; ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <br />
    <hr />

    <div>
        <input type="hidden" name="ld_bp_groups_sync[update_leaders]" value="0" />
        <label>
            <input type="checkbox" name="ld_bp_groups_sync[update_leaders]" value="1" <?php if ($sync_leaders) echo 'checked'; ?> />
            <?php _e('Update leaders from LearnDash group', 'buddyboss'); ?>
        </label>
    </div>

    <div>
        <input type="hidden" name="ld_bp_groups_sync[update_students]" value="0" />
        <label>
            <input type="checkbox" name="ld_bp_groups_sync[update_students]" value="1" <?php if ($sync_users) echo 'checked'; ?> />
            <?php _e('Update users from LearnDash group', 'buddyboss'); ?>
        </label>
    </div>
<?php endif; ?>
