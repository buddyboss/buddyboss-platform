<?php
/**
 * ReadyLaunch - The template for activity loop.
 *
 * This template handles the activity loop display with pagination support.
 * It renders activities in a list format with load more functionality.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

bboss_nouveau_before_loop();

$args = bboss_ajax_querystring( 'activity' );
if ( ! bboss_is_single_activity() ) {
	$args .= '&display_comments=false';
}

if ( bboss_has_activities( $args ) ) :

	// Check if this is the first page (used for AJAX pagination, nonce verification handled by BuddyPress core).
	$is_first_page = empty( $_POST['page'] ) || 1 === (int) $_POST['page']; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	if ( $is_first_page ) :
		?>
		<ul class="bb-rl-activity-list bb-rl-item-list bb-rl-list">
		<?php
	endif;

	while ( bboss_activities() ) :
		bboss_the_activity();
		bboss_get_template_part( 'activity/entry' );
	endwhile;

	if ( bboss_activity_has_more_items() ) :
		?>
		<li class="bb-rl-load-more">
			<a class="bb-rl-button bb-rl-button--brandFill" href="<?php bboss_activity_load_more_link(); ?>">
				<?php esc_html_e( 'Load More', 'buddyboss' ); ?>
			</a>
		</li>
		<?php
	endif;
	?>

	<li class="activity activity_update activity-item bb-rl-activity-popup"></li>

	<?php if ( $is_first_page ) : ?>
		</ul>
		<?php
	endif;

else :
	bboss_nouveau_user_feedback( 'activity-loop-none' );
endif;

bboss_nouveau_after_loop();
