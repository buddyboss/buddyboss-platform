<?php
/**
 * ReadyLaunch - The template for document create folder
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="bb-rl-create-popup-folder-wrap bb-rl-popup-on-fly-create-folder" style="display: none;">
	<div class="bb-rl-field-wrap">
		<label for="bb_rl_new_folder_name_input" class="bb-label"><?php esc_html_e( 'Title', 'buddyboss' ); ?></label>
		<input class="bb-rl-popup-on-fly-create-folder-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter folder title', 'buddyboss' ); ?>">
		<small class="error-box"><?php esc_html_e( 'Following special characters are not supported: \ / ? % * : | " < >', 'buddyboss' ); ?></small>
	</div>
	<?php
	if ( ! bp_is_group() ) :
		bp_get_template_part( 'document/document-privacy' );
	endif;
	?>
	<div class="db-modal-buttons">
		<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-close-create-popup-folder" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-document-create-popup-folder-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
