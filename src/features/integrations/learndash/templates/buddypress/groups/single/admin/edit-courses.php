<?php
/**
 * Available Variables
 *
 * @version BuddyBoss 1.0.0
 *
 * $groupId - (int) Current social group id
 * $hasLdGroup - (bool) Current social group has an associated LearnDash group
 * $ldGroupId - (int) The associated LearnDash group id
 */
?>
<h4 class="bb-section-title"><?php esc_html_e( 'Group Course Settings', 'buddyboss' ); ?></h4>
<p class="bb-section-info">
	<?php esc_html_e( 'Create and associate to a LearnDash group, allowing courses to be managed within the group.', 'buddyboss' ); ?>
</p>

<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
	<input type="checkbox" name="bp-ld-sync-enable" id="bp-ld-sync-enable" class="bs-styled-checkbox" value="1" <?php checked( $hasLdGroup ); ?> />
	<label for="bp-ld-sync-enable"><?php esc_html_e( 'Yes, I want this group to sync with a LearnDash group.', 'buddyboss' ); ?></label>
</p>
