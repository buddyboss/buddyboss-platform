<?php
/**
 * This template is used to print a single activity comment.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/activity-comment.php.
 *
 * @package BuddyBoss\Core
 * @version 1.0.0
 */

?>

<li class="bp-search-item bp-search-item_activity_comment">
	<div class="list-wrap">
		<div class="activity-avatar item-avatar">
			<a href="<?php bp_activity_user_link(); ?>">
				<?php bp_activity_avatar( array( 'type' => 'full' ) ); ?>
			</a>
		</div>

		<div class="item activity-content">
			<div class="activity-header">
				<a href="<?php bp_activity_user_link(); ?>"><?php echo wp_kses_post( bp_core_get_user_displayname( bp_get_activity_user_id() ) ); ?></a>
				<?php esc_html_e( 'replied to a post', 'buddyboss' ); ?>
			</div>
			<?php if ( bp_nouveau_activity_has_content() ) : ?>
				<div class="activity-inner">
					<?php
					echo bp_create_excerpt(
						bp_get_activity_content_body(),
						100,
						array(
							'ending' => '&hellip;'
						)
					);
					?>
				</div>
			<?php endif; ?>
			<div class="item-meta">
				<a href="<?php echo esc_url( bp_activity_get_permalink( bp_get_activity_id() ) ); ?>">
					<time><?php echo wp_kses_post( human_time_diff( bp_nouveau_get_activity_timestamp() ) ) . '&nbsp;' . esc_html__( 'ago', 'buddyboss' ); ?></time>
				</a>
			</div>
		</div>
	</div>
</li>
