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
		<?php esc_html_e( 'Edit Group Name &amp; Description', 'buddyboss-platform' ); ?>
	</h2>

<?php endif; ?>

<label for="group-name"><?php esc_html_e( 'Group Name (required)', 'buddyboss-platform' ); ?></label>
<?php
// bp_get_new_group_name() is escaped via its 'esc_attr' filter; the editable getter returns raw, so escape it here.
$bb_group_name_value = $bp_is_group_create ? bp_get_new_group_name() : esc_attr( bp_get_group_name_editable() );
?>
<input type="text" name="group-name" id="group-name" value="<?php echo $bb_group_name_value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Value escaped above (create: esc_attr filter on bp_get_new_group_name; edit: esc_attr()). ?>" aria-required="true" />

<label for="group-desc"><?php esc_html_e( 'Group Description', 'buddyboss-platform' ); ?></label>
<?php
// bp_get_new_group_description() is escaped via its 'esc_textarea' filter; the editable getter returns raw, so escape it here.
$bb_group_desc_value = $bp_is_group_create ? bp_get_new_group_description() : esc_textarea( bp_get_group_description_editable() );
?>
<textarea name="group-desc" id="group-desc" aria-required="true"><?php echo $bb_group_desc_value; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Value escaped above (create: esc_textarea filter on bp_get_new_group_description; edit: esc_textarea()). ?></textarea>
