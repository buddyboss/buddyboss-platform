<?php
/**
 * The template for search results for the activity.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/activity.php.
 *
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<li class="bp-search-item bp-search-item_activity <?php bp_activity_css_class(); ?>" id="activity-<?php bp_activity_id(); ?>" data-bp-activity-id="<?php bp_activity_id(); ?>" data-bp-timestamp="<?php bp_nouveau_activity_timestamp(); ?>">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_activity_user_id() ); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<?php echo wp_kses_post( bp_get_activity_action( array( 'no_timestamp' => true ) ) ); ?>
			</div>
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner">
					<?php
					add_filter( 'bp_activity_allowed_tags', 'bb_network_search_allowed_tags' );
					$content = preg_replace('/<p[^>]*>(.*?)<\/p>/is', "$1 ", bp_activity_filter_kses( bp_get_activity_content_body() ) );
					echo bp_create_excerpt(
						$content,
						100,
						array(
							'ending' => '&hellip;'
						)
					);
					remove_filter( 'bp_activity_allowed_tags', 'bb_network_search_allowed_tags' );
					?>
				</div>
			<?php endif; ?>
			<div class="item-meta">
				<a href="<?php bp_activity_thread_permalink(); ?>">
					<?php
					printf(
						'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
						bp_core_get_iso8601_date( bp_get_activity_date_recorded() ),
						bp_core_time_since( bp_get_activity_date_recorded() )
					);
					?>
				</a>
			</div>
		</div>
	</div>
</li>
