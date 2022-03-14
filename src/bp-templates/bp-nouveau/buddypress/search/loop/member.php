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
?>
<li <?php bp_member_class( array( 'item-entry', 'bp-search-item' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
	<div class="list-wrap">
		<div class="item-avatar">
			<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar( bp_nouveau_avatar_args() ); ?></a>
		</div>

		<div class="item">
			<h2 class="item-title member-name">
				<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
			</h2>

			<?php if ( bp_nouveau_member_has_meta() ) : ?>
				<p class="item-meta last-activity">
					<?php bp_nouveau_member_meta(); ?>
				</p>
			<?php endif; ?>
		</div>
	</div>
</li>
