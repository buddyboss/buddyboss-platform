<?php if ($hasBpGroup): ?>
	<p>
	    <b><?php _e('Associated Group:', 'buddyboss'); ?></b>

        <a href="<?php echo bp_get_admin_url("admin.php?page=bp-groups&gid={$bpGroupId}&action=edit"); ?>" target="_blank">
            <?php echo $bpGroup->name; ?> (ID: <?php echo $bpGroup->id; ?>)
        </a>
	</p>
<?php endif; ?>

<div class="bp_learndash_groups_sync-auto_create_bp_group">
    <input type="hidden" name="bp-ld-sync-enable" value="0" />
    <label>
        <input
            type="checkbox"
            name="bp-ld-sync-enable"
            value="1"
            autocomplete="off"
            <?php checked($hasBpGroup, true); ?>
        />
        <?php _e('Yes. I want this group to have a social group', 'buddyboss'); ?>
    </label>
</div>
