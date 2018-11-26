<?php
    $post_settings = get_post_meta(get_the_ID(), 'ld_bp_groups_sync_settings', true) ?: [];
    $global_settings = ld_bp_groups_sync_get_settings();

    $bp_group = ld_bp_groups_sync_get_associated_bp_group(get_the_ID());
    $bp_group_id = $bp_group? $bp_group->id : false;

    $auto_generate = isset($post_settings['auto_create_bp_group'])? $post_settings['auto_create_bp_group'] : $global_settings['auto_create_bp_group'];
    $sync_leaders  = isset($post_settings['auto_sync_leaders'])? $post_settings['auto_sync_leaders'] : $global_settings['auto_sync_leaders'];
    $sync_users    = isset($post_settings['auto_sync_students'])? $post_settings['auto_sync_students'] : $global_settings['auto_sync_students'];
?>

<p>
    <b><?php _e('Associated Group:', 'buddyboss'); ?></b>

    <?php if ($bp_group): ?>
        <a href="<?php echo bp_get_admin_url("admin.php?page=bp-groups&gid={$bp_group->id}&action=edit"); ?>" target="_blank">
            <?php echo $bp_group->name; ?> (ID: <?php echo $bp_group->id; ?>)
        </a>
    <?php endif; ?>
</p>

<select name="ld_bp_groups_sync[buddypress_group_id]" style="width: 100%; margin-bottom: 10px;">
    <option value="0"><?php _e('None', 'buddyboss'); ?></option>
    <?php foreach (ld_bp_groups_sync_get_unassociated_bp_groups([], get_the_ID()) as $group): ?>
        <?php $selected = $group->id == $bp_group_id? 'selected' : ''; ?>
        <option value="<?php echo $group->id; ?>" <?php echo $selected; ?>>
            <?php echo $group->name; ?> (ID: <?php echo $group->id; ?>)
        </option>
    <?php endforeach; ?>
</select>

<div class="ld_bp_groups_sync-auto_create_bp_group" style="display: none">
    <input type="hidden" name="ld_bp_groups_sync[auto_create_bp_group]" value="0" />
    <label>
        <input
            type="checkbox"
            name="ld_bp_groups_sync[auto_create_bp_group]"
            value="1"
            autocomplete="off"
            <?php if (! $bp_group_id && $auto_generate) echo 'checked'; ?>
        />
        <?php _e('Generate a new BuddyPress group', 'buddyboss'); ?>
    </label>
</div>

<br />
<hr />

<p>
    <b><?php _e('After LearnDash Group Updated:', 'buddyboss'); ?></b>
</p>

<div>
    <input type="hidden" name="ld_bp_groups_sync[update_leaders]" value="0" />
    <label>
        <input type="checkbox" name="ld_bp_groups_sync[update_leaders]" value="1" <?php if ($sync_leaders) echo 'checked'; ?> />
        <?php _e('Update leaders to BuddyPress group', 'buddyboss'); ?>
    </label>
</div>

<div>
    <input type="hidden" name="ld_bp_groups_sync[update_students]" value="0" />
    <label>
        <input type="checkbox" name="ld_bp_groups_sync[update_students]" value="1" <?php if ($sync_users) echo 'checked'; ?> />
        <?php _e('Update users to BuddyPress group', 'buddyboss'); ?>
    </label>
</div>

<script type="text/javascript">
jQuery("[name='ld_bp_groups_sync[buddypress_group_id]']").on('change', function() {
    if (jQuery(this).val() === '0') {
        jQuery('.ld_bp_groups_sync-auto_create_bp_group').show();
    } else {
        jQuery('.ld_bp_groups_sync-auto_create_bp_group').hide();
    }
}).trigger('change');
</script>
