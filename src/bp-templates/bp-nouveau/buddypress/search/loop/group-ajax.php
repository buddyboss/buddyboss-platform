<?php
/**
 * Template for displaying the search results of the group ajax
 *
 * This template can be overridden by copying it to yourtheme/buddypress/search/loop/group-ajax.php.
 *
 * @package BuddyBoss\Core
 * @since   BuddyBoss 1.0.0
 * @version 1.0.0
 */
?>
<div class="bp-search-ajax-item bboss_ajax_search_group">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bp_get_group_permalink() ) ); ?>">
		<div class="item-avatar">
			<?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>
		<p class="item">
			<div class="item-title"><?php bp_group_link(); ?></div>
			<p class="item-meta group-details">
				<?php bp_group_description(); ?>
			</p><!-- //.group_description -->
			<?php bp_group_type(); ?>
			<span class="middot">&middot;</span>
			<p class="item-meta group-details"><?php esc_html_e( 'Last active ', 'buddyboss' ); ?><?php bp_group_last_active(); ?></p>
		</div>
	</a>
</div>
