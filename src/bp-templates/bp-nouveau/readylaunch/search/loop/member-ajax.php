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
	<a href="<?php echo esc_url( bp_get_member_permalink() ); ?>">
		<div class="item-avatar">
			<?php bp_member_avatar( 'type=thumb&width=60&height=60' ); ?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<a href="<?php echo esc_url( bp_get_member_permalink() ); ?>"><?php bp_member_name(); ?></a>
			<span class="bb-rl-member-type"><?php echo bp_get_user_member_type( bp_get_member_user_id() ); ?></span>
		</div>
		<?php
		if ( bp_nouveau_member_has_meta() ) :
			?>
			<p class="entry-meta item-meta last-activity">
				<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bb_get_member_last_activity_time() ); ?>
			</p><!-- #item-meta -->
		<?php endif; ?>
	</div>
</div>
