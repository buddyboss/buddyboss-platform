<?php
/**
 * ReadyLaunch - No document template.
 *
 * This template displays a message when no documents are found
 * and provides action buttons for adding documents or folders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bb-rl-media-none">
	<div class="bb-rl-media-none-figure"><i class="bb-icons-rl-file-doc"></i></div>
	<?php
		bp_nouveau_user_feedback( 'media-loop-document-none' );
	?>
	<div class="bb-rl-media-none-actions">
		<?php
		bp_get_template_part( 'document/add-folder' );
		bp_get_template_part( 'document/add-document' );
		?>
	</div>
</div>
