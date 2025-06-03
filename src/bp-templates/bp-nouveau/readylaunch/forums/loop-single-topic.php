<?php

/**
 * Topics Loop - Single
 *
 * @package BuddyBoss\ReadyLaunch
 */

$forum_id = bbp_get_topic_forum_id();
$group_avatar = '';
if ( function_exists( 'bbp_is_forum_group_forum' ) && bbp_is_forum_group_forum( $forum_id ) ) {
	$group_ids = bbp_get_forum_group_ids( $forum_id );
	
	if ( ! empty( $group_ids ) && function_exists( 'groups_get_group' ) ) {
		$group_id = $group_ids[0]; // Get the first group ID
		$group = groups_get_group( $group_id );
		
		if ( $group && ! empty( $group->name ) ) {
			// Get group avatar
			if ( function_exists( 'bp_core_fetch_avatar' ) && ! bp_disable_group_avatar_uploads() ) {
				$group_avatar = bp_core_fetch_avatar(
					array(
						'item_id'    => $group_id,
						'object'     => 'group',
						'type'       => 'thumb',
						'width'      => 20,
						'height'     => 20,
						'html'       => true,
						'alt'        => sprintf( __( '%s logo', 'buddyboss' ), $group->name ),
						'class'      => 'bb-rl-group-avatar',
					)
				);
			}
		}
	}
}

?>

<li id="bbp-topic-<?php bbp_topic_id(); ?>" <?php bbp_topic_class(); ?>>

	<div class="bb-rl-topic-avatar">
		<?php bbp_topic_author_link( array( 'size' => '48' ) ); ?>
	</div>

	<div class="bb-rl-topic-content">

		<div class="bb-rl-topic-status-wrapper">

				<?php if ( ! bbp_is_topic_open() ) { ?>
					<span class="bb-rl-topic-state" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Closed', 'buddyboss' ); ?>"><i class="bb-icons-rl-lock-simple bb-rl-topic-status closed"></i></span>
					<?php
				}

				if ( bbp_is_topic_super_sticky() ) {
					?>
					<span class="bb-rl-topic-state" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Super Sticky', 'buddyboss' ); ?>"><i class="bb-icons-rl-push-pin bb-rl-topic-status super-sticky"></i></span>
				<?php } elseif ( bbp_is_topic_sticky() ) { ?>
					<span class="bb-rl-topic-state" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Sticky', 'buddyboss' ); ?>"><i class="bb-icons-rl-push-pin bb-rl-topic-status sticky"></i></span>
					<?php
				}

				if ( is_user_logged_in() ) {
					$is_subscribed = bbp_is_user_subscribed_to_topic( get_current_user_id(), bbp_get_topic_id() );
					if ( $is_subscribed ) {
						?>
						<span class="bb-rl-topic-state" data-balloon-pos="up" data-balloon="<?php esc_attr_e( 'Subscribed', 'buddyboss' ); ?>"><i class="bb-icons-rl-rss"></i></span>
						<?php
					}
				}
			?>
		</div>
		<?php do_action( 'bbp_theme_before_topic_started_in' ); ?>
		<div class="bb-rl-topic-started-in">
			<?php printf( __( '<a href="%1$s">%2$s%3$s</a>', 'buddyboss' ), bbp_get_forum_permalink( bbp_get_topic_forum_id() ), $group_avatar, bbp_get_forum_title( bbp_get_topic_forum_id() )  ); ?>
		</div>
		<?php do_action( 'bbp_theme_after_topic_started_in' ); ?>

		<div class="bb-rl-topic-title">
			<a class="bb-rl-topic-permalink" href="<?php bbp_topic_permalink(); ?>"><?php bbp_topic_title(); ?></a>
		</div>

		<p class="bb-rl-topic-description">
			<?php echo get_the_excerpt( bbp_get_topic_id() ); ?>
		</p>

		<?php
			$terms = bbp_get_topic_tag_list( bbp_get_topic_id() );
			if ( $terms && bbp_allow_topic_tags() ) {
				echo $terms;
			} else {
				?>
				<div class="item-tags" style="display: none;">
					<i class="bb-icon-l bb-icon-tag"></i>
				</div>
				<?php
			}
		?>

		<div class="bb-rl-topic-footer">
			<div class="bb-rl-topic-meta">

			<?php if ( bbp_is_user_home() ) : ?>

				<?php if ( bbp_is_favorites() ) : ?>

					<span class="bb-rl-row-actions">

						<?php do_action( 'bbp_theme_before_topic_favorites_action' ); ?>

						<?php
						bbp_topic_favorite_link(
							array(
								'before'    => '',
								'favorite'  => '+',
								'favorited' => '&times;',
							)
						);
						?>

						<?php do_action( 'bbp_theme_after_topic_favorites_action' ); ?>

					</span>

				<?php elseif ( bbp_is_subscriptions() ) : ?>

					<span class="bb-rl-row-actions">

						<?php do_action( 'bbp_theme_before_topic_subscription_action' ); ?>

						<?php
						bbp_topic_subscription_link(
							array(
								'before'      => '',
								'subscribe'   => '+',
								'unsubscribe' => '&times;',
							)
						);
						?>

						<?php do_action( 'bbp_theme_after_topic_subscription_action' ); ?>

					</span>

				<?php endif; ?>

			<?php endif; ?>

			<?php do_action( 'bbp_theme_before_topic_title' ); ?>

			<?php do_action( 'bbp_theme_after_topic_title' ); ?>

			<?php bbp_topic_pagination(); ?>

			<?php do_action( 'bbp_theme_before_topic_meta' ); ?>

			<div class="bb-rl-topic-meta-item">

				<?php do_action( 'bbp_theme_before_topic_started_by' ); ?>

				<span class="bb-rl-topic-started-by"><?php printf( __( 'By:%1$s', 'buddyboss' ), bbp_get_topic_author_link( array( 'size' => '14' ) ) ); ?></span>

				<?php do_action( 'bbp_theme_after_topic_started_by' ); ?>

			</div>

			<div class="bb-rl-topic-meta-item">

				<div class="bb-rl-topic-freshness">

					<?php do_action( 'bbp_theme_before_topic_freshness_link' ); ?>

					<?php bbp_topic_freshness_link(); ?>

					<?php do_action( 'bbp_theme_after_topic_freshness_link' ); ?>

				</div>

			</div>

			<?php do_action( 'bbp_theme_after_topic_meta' ); ?>

			<?php bbp_topic_row_actions(); ?>

			</div>
			
			
			<div class="bb-rl-topic-stats">
				<div class="bb-rl-topic-voice-count"><i class="bb-icons-rl-user"></i> <?php bbp_topic_voice_count(); ?></div>
				<div class="bb-rl-topic-reply-count"><i class="bb-icons-rl-chat"></i> <?php bbp_show_lead_topic() ? bbp_topic_reply_count() : bbp_topic_post_count(); ?></div>
			</div>
		</div>

	</div>

</li><!-- #bbp-topic-<?php bbp_topic_id(); ?> -->
