<li class="bb-rl-forum-list-item">
	<?php if ( function_exists( 'bbp_get_forum_thumbnail_image' ) ) { ?>
		<a href="<?php bbp_forum_permalink(); ?>" class="bb-rl-forum-cover" title="<?php bbp_forum_title(); ?>">
			<?php echo bbp_get_forum_thumbnail_image( bbp_get_forum_id(), 'large', 'full' ); ?>
		</a>
	<?php } ?>

	<div class="bb-rl-card-forum-details">
		<div class="bb-rl-sec-header">
			<?php do_action( 'bbp_theme_before_forum_title' ); ?>
			<h3><a class="bbp-forum-title" href="<?php bbp_forum_permalink(); ?>"><?php bbp_forum_title(); ?></a></h3>
			<?php do_action( 'bbp_theme_after_forum_title' ); ?>
		</div>

		<div class="bb-rl-forum-meta">
			<?php
				$forum_id = bbp_get_forum_id();
				// get discussion count
				$discussion_count = bbp_get_forum_topic_count( $forum_id );
				// get forum visibility/privacy status
				$forum_visibility = bbp_get_forum_visibility( $forum_id );
				$forum_visibilities = bbp_get_forum_visibilities( $forum_id );
				$privacy_label = isset( $forum_visibilities[ $forum_visibility ] ) ? $forum_visibilities[ $forum_visibility ] : __( 'Public', 'buddyboss' );
			?>
			<div class="bb-rl-forum-meta-item">
				<?php echo esc_html( $privacy_label ); ?>
			</div>
			<div class="bb-rl-forum-meta-item <?php echo $discussion_count == 0 ? 'bb-rl-forum-meta-item-inactive' : ''; ?>">
				<span class="bb-rl-forum-topic-count-value"><?php echo $discussion_count; ?></span>
				<span class="bb-rl-forum-topic-count-label"><?php echo _n( 'Discussion', 'Discussions', $discussion_count, 'buddyboss' ); ?></span>
			</div>
			<div class="bb-rl-forum-meta-item">
				<?php do_action( 'bbp_theme_before_forum_freshness_link' ); ?>
				<?php bbp_forum_freshness_link(); ?>
				<?php do_action( 'bbp_theme_after_forum_freshness_link' ); ?>
			</div>
		</div>

		<div class="bb-forum-content-wrap">
			<?php
				do_action( 'bbp_theme_before_forum_description' );
				remove_filter( 'bbp_get_forum_content', 'wpautop' );
			?>
			<div class="bb-forum-content"><?php echo bbp_get_forum_content_excerpt_view_more( bbp_get_forum_id(), 150, '&hellip;' ); ?></div>
			<?php
				add_filter( 'bbp_get_forum_content', 'wpautop' );
				do_action( 'bbp_theme_after_forum_description' );
			?>
		</div>

		<div class="forums-meta bb-forums-meta">
			<?php
				do_action( 'bbp_theme_before_forum_sub_forums' );

				$r = array(
						'before'            => '',
						'after'             => '',
						'link_before'       => '<span>',
						'link_after'        => '</span>',
						'count_before'      => ' (',
						'count_after'       => ')',
						'count_sep'         => ', ',
						'separator'         => ' ',
						'forum_id'          => '',
						'show_topic_count'  => false,
						'show_reply_count'  => false,
					);

				bbp_list_forums($r);

				do_action( 'bbp_theme_after_forum_sub_forums' );
			?>
		</div>
	</div>
</li>
