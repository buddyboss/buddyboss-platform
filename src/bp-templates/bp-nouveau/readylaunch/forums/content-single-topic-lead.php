<?php

/**
 * Single Topic Lead Content Part
 *
 * @package BuddyBoss\Theme
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

<?php do_action( 'bbp_template_before_lead_topic' ); ?>
<div class="bb-rl-topic-header">

	<div class="bb-rl-topic-started-in">
		<?php printf( __( '<a href="%1$s">%2$s%3$s</a>', 'buddyboss' ), bbp_get_forum_permalink( bbp_get_topic_forum_id() ), $group_avatar, bbp_get_forum_title( bbp_get_topic_forum_id() )  ); ?>
	</div>

	<div class="bb-rl-topic-author">
		<div class="bb-rl-topic-avatar">
			<?php bbp_topic_author_link( array( 'size' => '48' ) ); ?>
		</div>
		<div class="bb-rl-topic-author-details">
			<div class="bb-rl-topic-author-name">
				<?php bbp_get_topic_author_link( array( 'size' => '14' ) ); ?><!-- TODO: Author name not showing -->
			</div>
			<div class="bb-rl-topic-time">
				<?php bbp_topic_freshness_link(); ?>
			</div>
		</div>
	</div>
	<h2 class="bb-rl-topic-title">
		<?php bbp_topic_title(); ?>
	</h2>
	<div class="bb-rl-topic-content">
		<?php bbp_topic_content(); ?>
	</div>
	<div class="bb-rl-topic-footer">
		<?php bbp_topic_tag_list(); ?>

		<div class="bb-rl-topic-stats">
			<div class="bb-rl-topic-voice-count"><i class="bb-icons-rl-user"></i> <?php bbp_topic_voice_count(); ?></div>
			<div class="bb-rl-topic-reply-count"><i class="bb-icons-rl-chat"></i> <?php bbp_show_lead_topic() ? bbp_topic_reply_count() : bbp_topic_post_count(); ?></div>
		</div>
	</div>
</div>

<ul id="bbp-topic-<?php bbp_topic_id(); ?>-lead" class="bbp-lead-topic">

	<li class="bbp-header">

		<div class="bbp-topic-author"><?php _e( 'Creator', 'buddyboss' ); ?></div><!-- .bbp-topic-author -->

		<div class="bbp-topic-content">

			<?php _e( 'Discussion', 'buddyboss' ); ?>

			<?php bbp_topic_subscription_link(); ?>

			<?php bbp_topic_favorite_link(); ?>

		</div><!-- .bbp-topic-content -->

	</li><!-- .bbp-header -->

	<li class="bbp-body">

		<div class="bbp-topic-header">

			<div class="bbp-meta">

				<span class="bbp-topic-post-date"><?php bbp_topic_post_date(); ?></span>

				<a href="<?php bbp_topic_permalink(); ?>" class="bbp-topic-permalink">#<?php bbp_topic_id(); ?></a>

				<?php do_action( 'bbp_theme_before_topic_admin_links' ); ?>

				<?php bbp_topic_admin_links(); ?>

				<?php do_action( 'bbp_theme_after_topic_admin_links' ); ?>

			</div><!-- .bbp-meta -->

		</div><!-- .bbp-topic-header -->

		<div id="post-<?php bbp_topic_id(); ?>" <?php bbp_topic_class(); ?>>

			<div class="bbp-topic-author">

				<?php do_action( 'bbp_theme_before_topic_author_details' ); ?>

				<?php
				bbp_topic_author_link(
					array(
						'sep'       => '<br />',
						'show_role' => true,
					)
				);
				?>

				<?php do_action( 'bbp_theme_after_topic_author_details' ); ?>

			</div><!-- .bbp-topic-author -->

			<div class="bbp-topic-content">

				<?php do_action( 'bbp_theme_before_topic_content' ); ?>

				<?php bbp_topic_content(); ?>

				<?php do_action( 'bbp_theme_after_topic_content' ); ?>

			</div><!-- .bbp-topic-content -->

		</div><!-- #post-<?php bbp_topic_id(); ?> -->

	</li><!-- .bbp-body -->

	<li class="bbp-footer">

		<div class="bbp-topic-author"><?php _e( 'Creator', 'buddyboss' ); ?></div>

		<div class="bbp-topic-content">

			<?php _e( 'Discussion', 'buddyboss' ); ?>

		</div><!-- .bbp-topic-content -->

	</li>

</ul><!-- #bbp-topic-<?php bbp_topic_id(); ?>-lead -->

<?php do_action( 'bbp_template_after_lead_topic' ); ?>
