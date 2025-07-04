<?php
/**
 * ReadyLaunch - Search Loop Activity Comment template.
 *
 * The template for search results for activity comments.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

$bp_activity_user_link = bp_get_activity_user_link();
$bp_activity_url       = bp_activity_get_permalink( bp_get_activity_id() );
?>

<li class="bp-search-item bp-search-item_activity_comment">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php echo esc_url( $bp_activity_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_activity_user_id() ); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<a href="<?php echo esc_url( $bp_activity_user_link ); ?>" data-bb-hp-profile="<?php echo esc_attr( bp_get_activity_user_id() ); ?>"><?php echo wp_kses_post( bp_core_get_user_displayname( bp_get_activity_user_id() ) ); ?></a>
				<a href="<?php echo esc_url( $bp_activity_url ); ?>"><?php esc_html_e( 'replied to a post', 'buddyboss' ); ?></a>
			</div>
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner">
					<?php
					echo bp_create_excerpt(
						bp_get_activity_content_body(),
						100,
						array(
							'ending' => '&hellip;',
						)
					);
					?>
				</div>
			<?php endif; ?>
			<div class="item-meta">
				<a href="<?php echo esc_url( $bp_activity_url ); ?>">
					<?php
					$activity_comment_date_recorded = bp_get_activity_date_recorded();
					printf(
						'<time class="time-since" data-livestamp="%1$s">%2$s</time>',
						bp_core_get_iso8601_date( $activity_comment_date_recorded ),
						bp_core_time_since( $activity_comment_date_recorded )
					);
					?>
				</a>
			</div>
		</div>
	</div>
</li>
