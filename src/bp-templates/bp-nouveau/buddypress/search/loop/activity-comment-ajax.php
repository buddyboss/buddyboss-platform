<?php
/**
 * This template is used to print the activity comment.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/activity-comment-ajax.php.
 *
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>
<div class="bp-search-ajax-item bp-search-ajax-item_activity_comment">
	<a href='<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bp_activity_thread_permalink() ) ); ?>'>
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
		<div class="item">
			<h3 class="entry-title item-title">
				<a href="<?php bp_activity_user_link(); ?>"><?php echo wp_kses_post( bp_core_get_user_displayname( bp_get_activity_user_id() ) ); ?></a>
				<?php esc_html_e( 'replied to a post', 'buddyboss' ); ?>
			</h3>
			<?php if ( bp_activity_has_content() ) : ?>
				<div class="item-desc">
					<?php echo wp_kses_post( bp_search_activity_intro( 30 ) ); ?>
				</div>
			<?php endif; ?>
			<div class="item-meta activity-header">
				<?php
				printf(
					'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
					bp_core_get_iso8601_date( bp_get_activity_date_recorded() ),
					bp_core_time_since( bp_get_activity_date_recorded() )
				);
				?>
			</div>
		</div>
	</a>
</div>
