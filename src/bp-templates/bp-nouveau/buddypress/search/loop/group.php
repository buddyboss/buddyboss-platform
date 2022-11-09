<?php
/**
 * Template for displaying the search results of the group
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/group.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>
<li <?php bp_group_class( array( 'item-entry bp-search-item bp-search-item_group' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
	<div class="list-wrap">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div class="item-avatar">
				<a href="<?php bp_group_permalink(); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
			</div>
		<?php endif; ?>
		<div class="item">
			<h2 class="item-title groups-title"><?php bp_group_link(); ?></h2>
			<div class="group-description">
				<?php bp_group_description(); ?>
			</div><!-- //.group_description -->
			<span class="entry-meta">
				<span class="item-meta">
					<?php bp_group_type(); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="item-meta group-details">
					<?php
					esc_html_e( 'Last active ', 'buddyboss' );
					bp_group_last_active();
					?>
				</span>
			</span>
		</div>
	</div>
</li>
