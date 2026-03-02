<?php
/**
 * ReadyLaunch - Group's edit details template.
 *
 * This template provides form fields for editing group name and description
 * during group creation or when managing existing groups.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_is_group_create = bp_is_group_create();
if ( $bp_is_group_create ) : ?>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Edit Group Name &amp; Description', 'buddyboss' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php esc_html_e( 'Group Name (required)', 'buddyboss' ); ?></label>
<input type="text" name="group-name" id="group-name" value="<?php echo $bp_is_group_create ? bp_new_group_name() : bp_group_name_editable(); ?>" aria-required="true" />

<label for="group-desc"><?php esc_html_e( 'Group Description', 'buddyboss' ); ?></label>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php echo $bp_is_group_create ? bp_new_group_description() : bp_group_description_editable(); ?></textarea>
