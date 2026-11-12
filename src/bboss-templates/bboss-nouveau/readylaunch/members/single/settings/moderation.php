<?php
/**
 * ReadyLaunch - Member Settings Moderation template.
 *
 * This template handles the moderation settings for members.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bboss_is_send_ajax_request();

switch ( bboss_current_action() ) :
	case 'blocked-members':
		bboss_nouveau_member_hook( 'before', 'moderation_content' );
		?>
		<div class="moderation" data-bp-list="moderation">
			<?php
			if ( $is_send_ajax_request ) {
				echo '<div id="bp-ajax-loader">';
				bboss_nouveau_user_feedback( 'moderation-block-member-loading' );
				echo '</div>';
			} else {
				bboss_get_template_part( 'moderation/moderation-loop' );
			}
			?>
		</div>
		<?php
		bboss_nouveau_member_hook( 'after', 'moderation_content' );
		break;
endswitch;
