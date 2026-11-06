<?php
/**
 * BP Nouveau Group's edit details template.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/admin/edit-details.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_group_create = bboss_is_group_create();
if ( $bp_is_group_create ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Enter Group Name &amp; Description', 'buddyboss' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Edit Group Name &amp; Description', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php esc_html_e( 'Group Name (required)', 'buddyboss' ); ?></label>
<input type="text" name="group-name" id="group-name" value="<?php echo $bp_is_group_create ? bboss_get_new_group_name() : esc_attr( bboss_get_group_name_editable() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value escaped above (create: esc_attr filter on bp_get_new_group_name(); edit: esc_attr()). ?>" aria-required="true" />

<label for="group-desc"><?php esc_html_e( 'Group Description', 'buddyboss' ); ?></label>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php $bp_is_group_create ? bboss_new_group_description() : bboss_group_description_editable(); ?></textarea>
