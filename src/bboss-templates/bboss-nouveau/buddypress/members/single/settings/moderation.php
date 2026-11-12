<?php
/**
 * The template for user moderation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/settings/moderation.php.
 *
 * @since   BuddyBoss 1.5.6
 * @package BuddyBoss
 * @version 1.5.6
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
