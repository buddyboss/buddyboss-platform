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
			<?php echo bp_get_user_member_type( bp_get_member_user_id() ); ?>
			<?php if ( bp_nouveau_member_has_meta() ) : ?>
				<p class="item-meta last-activity">
					<span class="middot">&middot;</span>
					<?php esc_html_e( 'Last active', 'buddyboss' ); ?>
					<?php echo wp_kses_post( bb_get_member_last_activity_time() ); ?>
				</p><!-- #item-meta -->
			<?php endif; ?>
		</div>

	</a>
</div>
