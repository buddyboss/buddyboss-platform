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
		<div class="item">
			<div class="item-title"><?php bp_group_link(); ?></div>
			<div class="item-meta group-details">
				<?php
				echo bp_create_excerpt(
					bp_get_group_description(),
					255,
					array(
						'html'       => false,
						'strip_tags' => true,
						'ending'     => '&hellip;',
					)
				);
				?>
			</div><!-- //.group_description -->
			<span class="item-meta">
				<?php bp_group_type(); ?>
			</span>
			<span class="middot">&middot;</span>
			<p class="item-meta last-active">
				<?php
				esc_html_e( 'Last active ', 'buddyboss' );
				bp_group_last_active();
				?>
			</p>
		</div>
	</a>
</div>
