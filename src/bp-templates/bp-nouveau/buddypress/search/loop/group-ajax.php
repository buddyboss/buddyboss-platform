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
	<a href="<?php echo esc_url(add_query_arg( array( 'no_frame' => '1' ), bp_get_group_permalink() )); ?>">
		<div class="item-avatar">
			<?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>

		<div class="item">
			<div class="item-title"><?php bp_group_name(); ?></div>
			<?php if ( bp_nouveau_group_has_meta() ) : ?>
				<p class="item-meta group-details"><?php
					$meta = array(
						'status' => bp_get_group_type(),
						'count'  => bp_get_group_member_count(),
					);
					echo join( ' / ', array_map( 'wp_kses', (array) $meta, array( 'span' => array( 'class' => array() ) ) ) ); ?>
				</p>
			<?php endif; ?>
		</div>
	</a>
</div>
