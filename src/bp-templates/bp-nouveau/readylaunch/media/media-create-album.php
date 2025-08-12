<?php
/**
 * ReadyLaunch - Media Create Album template.
 *
 * This template handles the inline album creation functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<div class="bb-rl-create-popup-album-wrap bb-rl-popup-on-fly-create-album" style="display: none;">

	<div class="bb-rl-field-wrap">
		<label for="bb_rl_new_album_name_input" class="bb-label"><?php esc_attr_e( 'Album Title', 'buddyboss' ); ?></label>
		<input id="bb_rl_new_album_name_input" class="bb-rl-popup-on-fly-create-album-title" value="" type="text" placeholder="<?php esc_attr_e( 'Enter album title', 'buddyboss' ); ?>">
	</div>
	<?php
	if ( ! bp_is_group() ) :
		bp_get_template_part( 'media/media-privacy' );
	endif;
	?>
	<div class="db-modal-buttons">
		<a class="bb-rl-button bb-rl-button--secondaryFill bb-rl-button--small bb-rl-close-create-popup-album" href="#"><?php esc_html_e( 'Cancel', 'buddyboss' ); ?></a>
		<a class="bb-rl-button bb-rl-button--brandFill bb-rl-button--small bb-rl-media-create-popup-album-submit" href="#"><?php esc_html_e( 'Create', 'buddyboss' ); ?></a>
	</div>
</div>
