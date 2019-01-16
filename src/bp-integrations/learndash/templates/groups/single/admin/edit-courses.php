<h4><?php esc_html_e('Group Courses Settings', 'buddyboss'); ?></h4>

<fieldset>
    <legend class="screen-reader-text"><?php esc_html_e('Group Courses Settings', 'buddyboss'); ?></legend>

    <p>
    	<?php esc_html_e('Create a Learndash group, allowing courses and reports to be managed within the group.', 'buddyboss'); ?>
    </p>

    <div class="field-group">
        <div class="checkbox">
            <label>
            	<input type="checkbox" name="bp-ld-sync-group-ld-group" id="bp-ld-sync-group-ld-group" value="1" <?php checked($hasLdGroup); ?> />
            	<?php esc_html_e('Yes. I want this group to have Learndash group.', 'buddyboss'); ?>
            </label>
        </div>
    </div>
</fieldset>
