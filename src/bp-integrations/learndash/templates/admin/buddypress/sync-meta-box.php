<?php if ($hasLdGroup): ?>
	<p>
	    <b><?php _e('Associated Group:', 'buddyboss'); ?></b>

        <a href="<?php echo get_edit_post_link($ldGroupId); ?>" target="_blank">
            <?php echo $ldGroup->post_title; ?> (ID: <?php echo $ldGroup->ID; ?>)
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
            <?php checked($hasLdGroup, true); var_dump($hasLdGroup); ?>
        />
        <?php _e('Yes. I want this group to have a Learndash group', 'buddyboss'); ?>
    </label>
</div>
