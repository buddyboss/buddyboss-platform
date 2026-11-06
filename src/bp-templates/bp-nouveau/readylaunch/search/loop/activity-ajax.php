<?php
/**
 * ReadyLaunch - Search Loop Activity AJAX template.
 *
 * The template for AJAX search results for activities.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bp-search-ajax-item bp-search-ajax-item_activity">
	<a href='<?php echo esc_url( bboss_get_activity_thread_permalink() ); ?>'>
		<div class="item-avatar">
			<?php
			bboss_activity_avatar(
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
		<div class="item-title">
			<?php echo wp_kses_post( bboss_get_activity_action( array( 'no_timestamp' => true ) ) ); ?>
		</div>
		<?php if ( bboss_activity_has_content() ) : ?>
			<div class="item-desc">
				<?php echo wp_kses_post( bboss_search_activity_intro( 30 ) ); ?>
			</div>
		<?php endif; ?>
		<div class="entry-meta item-meta activity-header">
			<?php
			$activity_date_recorded = bboss_get_activity_date_recorded();
			printf(
				'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
				esc_html( bboss_core_get_iso8601_date( $activity_date_recorded ) ),
				esc_html( bboss_core_time_since( $activity_date_recorded ) )
			);
			?>
		</div>
	</div>
</div>
