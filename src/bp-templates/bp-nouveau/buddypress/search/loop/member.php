<?php
/**
 * Template for displaying the search results of the member
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/member.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */

$member_user_id   = bp_get_member_user_id();
$member_user_link = bp_get_member_permalink();
?>
<li <?php bp_member_class( array( 'item-entry', 'bp-search-item' ) ); ?> data-bp-item-id="<?php echo esc_attr( $member_user_id ); ?>" data-bp-item-component="members">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php echo esc_url( $member_user_link ); ?>"><?php bp_member_avatar( bp_nouveau_avatar_args() ); ?></a>
		</div>

		<div class="item">
			<h2 class="item-title member-name">
				<a href="<?php echo esc_url( $member_user_link ); ?>"><?php bp_member_name(); ?></a>
			</h2>
			<?php
			echo bp_get_user_member_type( $member_user_id );
			if ( bp_nouveau_member_has_meta() ) :
				?>
				<p class="item-meta last-activity">
					<span class="middot">&middot;</span>
					<?php echo esc_html__( 'Last active', 'buddyboss' ) . ' ' . wp_kses_post( bb_get_member_last_activity_time() ); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</li>
