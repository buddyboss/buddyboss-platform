<?php
/**
 * Available Variables
 * @version BuddyBoss 1.0.0
 *
 * $groupId - (int) Current social group id
 * $hasLdGroup - (bool) Current social group has an associated Learndash group
 * $ldGroupId - (int) The associated Learndash group id
 */
?>
<h4><?php esc_html_e('Group Courses Settings', 'buddyboss'); ?></h4>

<p>
	<?php esc_html_e('Create and associate to a Learndash group, allowing courses and reports to be managed within the group.', 'buddyboss'); ?>
</p>

<p class="checkbox">
	<label>
		<input type="checkbox" name="bp-ld-sync-enable" id="bp-ld-sync-enable" value="1" <?php checked($hasLdGroup); ?> />
		<?php esc_html_e('Yes. I want this group to have Learndash group.', 'buddyboss'); ?>
	</label>
</p>
