<?php
/**
 * Template for displaying the search results of the member ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/member-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>
<div class="bp-search-ajax-item bboss_ajax_search_member">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bp_get_member_permalink() ) ); ?>">
		<div class="item-avatar">
			<?php bp_member_avatar( 'type=thumb&width=60&height=60' ); ?>
		</div>

		<div class="item">
			<div class="item-title"><?php bp_member_name(); ?></div>

			<?php if ( bp_nouveau_member_has_meta() ) : ?>
				<p class="item-meta last-activity">
					<?php bp_nouveau_member_meta(); ?>
				</p><!-- #item-meta -->
			<?php endif; ?>
		</div>

	</a>
</div>
