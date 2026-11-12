<?php
/**
 * ReadyLaunch - Search Loop Activity template.
 *
 * The template for search results for the activity.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<li class="bp-search-item bp-search-item_activity <?php bboss_activity_css_class(); ?>" id="activity-<?php bboss_activity_id(); ?>" data-bp-activity-id="<?php bboss_activity_id(); ?>" data-bp-timestamp="<?php bboss_nouveau_activity_timestamp(); ?>">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bboss_activity_user_link(); ?>" data-bb-hp-profile="<?php echo esc_attr( bboss_get_activity_user_id() ); ?>">
				<?php bboss_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<?php echo wp_kses_post( bboss_get_activity_action( array( 'no_timestamp' => true ) ) ); ?>
			</div>
			<?php
			if ( bboss_activity_has_post_title() ) {
				?>
				<div class="activity-title bb-activity-search-title">
					<h2><?php echo wp_kses_post( bboss_activity_post_title() ); ?></h2>
				</div>
				<?php
			}
			?>
			<?php if ( bboss_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner">
					<?php
					add_filter( 'bp_activity_allowed_tags', 'bb_network_search_allowed_tags' );
					$content = preg_replace( '/<p[^>]*>(.*?)<\/p>/is', '$1 ', bboss_activity_filter_kses( bboss_get_activity_content_body() ) );
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content is kses-filtered on save; wp_kses_post() here turns a tag cut by the excerpt into visible text.
					echo bboss_create_excerpt(
						$content,
						100,
						array(
							'ending' => '&hellip;',
						)
					);
					remove_filter( 'bp_activity_allowed_tags', 'bb_network_search_allowed_tags' );
					?>
				</div>
			<?php endif; ?>
			<div class="item-meta">
				<a href="<?php bboss_activity_thread_permalink(); ?>">
					<?php
					printf(
						'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
						esc_html( bboss_core_get_iso8601_date( bboss_get_activity_date_recorded() ) ),
						esc_html( bboss_core_time_since( bboss_get_activity_date_recorded() ) )
					);
					?>
				</a>
			</div>
		</div>
	</div>
</li>
