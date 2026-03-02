<?php
/**
 * ReadyLaunch - Search Loop Activity Comment AJAX template.
 *
 * The template for AJAX search results for activity comments.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bp-search-ajax-item bp-search-ajax-item_activity_comment">
	<a href='<?php echo esc_url( bp_get_activity_thread_permalink() ); ?>'>
		<div class="item-avatar">
			<?php
			bp_activity_avatar(
				array(
					'type'   => 'thumb',
					'height' => 50,
					'width'  => 50,
				)
			);
			?>
		</div>
	</a>
	<div class="item">
		<h3 class="entry-title item-title">
			<a href="<?php bp_activity_user_link(); ?>"><?php echo wp_kses_post( bp_core_get_user_displayname( bp_get_activity_user_id() ) ); ?></a>
			<a href='<?php echo esc_url( bp_get_activity_thread_permalink() ); ?>'><?php esc_html_e( 'replied to a post', 'buddyboss' ); ?></a>
		</h3>
		<?php if ( bp_activity_has_content() ) : ?>
			<div class="item-desc">
				<?php echo wp_kses_post( bp_search_activity_intro( 30 ) ); ?>
			</div>
		<?php endif; ?>
		<div class="entry-meta item-meta activity-header">
			<?php
			$activity_comment_date_recorded = bp_get_activity_date_recorded();
			printf(
				'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
				bp_core_get_iso8601_date( $activity_comment_date_recorded ),
				bp_core_time_since( $activity_comment_date_recorded )
			);
			?>
		</div>
	</div>
</div>
