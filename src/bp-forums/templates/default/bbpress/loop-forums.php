<?php

/**
 * Forums Loop
 *
 * @package BuddyBoss\Theme
 */

?>

<?php do_action( 'bbp_template_before_forums_loop' ); ?>

<ul id="forums-list-<?php bbp_forum_id(); ?>" class="bbp-forums">

	<li class="bbp-header">

		<ul class="forum-titles">
			<li class="bbp-forum-info"><?php _e( 'Forum', 'buddyboss' ); ?></li>
			<li class="bbp-forum-topic-count"><?php _e( 'Discussions', 'buddyboss' ); ?></li>
			<li class="bbp-forum-reply-count"><?php bbp_show_lead_topic() ? _e( 'Replies', 'buddyboss' ) : _e( 'Posts', 'buddyboss' ); ?></li>
			<li class="bbp-forum-freshness"><?php _e( 'Last Post', 'buddyboss' ); ?></li>
		</ul>

	</li><!-- .bbp-header -->

	<li class="bbp-body">

		<?php
		while ( bbp_forums() ) :
			bbp_the_forum();
			?>

			<?php bbp_get_template_part( 'loop', 'single-forum' ); ?>

		<?php endwhile; ?>

	</li><!-- .bbp-body -->

	<li class="bbp-footer">

		<div class="tr">
			<p class="td colspan4">&nbsp;</p>
		</div><!-- .tr -->

	</li><!-- .bbp-footer -->

</ul><!-- .forums-directory -->

<?php do_action( 'bbp_template_after_forums_loop' ); ?>
