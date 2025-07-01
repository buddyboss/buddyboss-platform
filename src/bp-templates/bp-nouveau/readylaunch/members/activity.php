<?php
/**
 * ReadyLaunch - Members Activity template.
 *
 * This template handles displaying member activity with loading placeholders.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$is_send_ajax_request = bb_is_send_ajax_request();

bp_get_template_part( 'members/single/parts/item-subnav' );
bp_nouveau_activity_member_post_form();
bp_get_template_part( 'common/search-and-filters-bar' );
bp_nouveau_member_hook( 'before', 'activity_content' );
?>
	<div id="bb-rl-activity-stream" class="activity single-user" data-bp-list="activity" data-ajax="<?php echo esc_attr( $is_send_ajax_request ? 'true' : 'false' ); ?>">
	<?php
	if ( $is_send_ajax_request ) {
		echo '<div id="bb-rl-ajax-loader">';
		for ( $i = 0; $i < 2; $i++ ) {
			?>
			<div class="bb-rl-activity-placeholder">
				<div class="bb-rl-activity-placeholder_head">
					<div class="bb-rl-activity-placeholder_avatar bb-rl-bg-animation bb-rl-loading-bg"></div>
						<div class="bb-rl-activity-placeholder_details">
						<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
						<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
					</div>
				</div>
				<div class="bb-rl-activity-placeholder_content">
					<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
					<div class="bb-rl-activity-placeholder_title bb-rl-bg-animation bb-rl-loading-bg"></div>
				</div>
				<div class="bb-rl-activity-placeholder_actions">
					<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
					<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
					<div class="bb-rl-activity-placeholder_description bb-rl-bg-animation bb-rl-loading-bg"></div>
				</div>
			</div>
			<?php
		}
		echo '</div>';
	} else {
		bp_get_template_part( 'activity/activity-loop' );
	}
	?>
	</div><!-- .activity -->

<?php
bp_nouveau_member_hook( 'after', 'activity_content' );
bp_get_template_part( 'common/js-templates/activity/comments' );
