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

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<div class="bp-search-ajax-item bboss_ajax_search_group">
	<a href="<?php echo esc_url( add_query_arg( array( 'no_frame' => '1' ), bboss_get_group_permalink() ) ); ?>">
		<div class="item-avatar">
			<?php bboss_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>
		<div class="item">
			<div class="item-title"><?php bboss_group_link(); ?></div>
			<div class="item-meta group-details">
				<?php
				echo bboss_create_excerpt( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- content is kses-filtered on save; wp_kses_post() here turns a tag cut by the excerpt into visible text.
					bboss_get_group_description(),
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
				<?php bboss_group_type(); ?>
			</span>
			<span class="middot">&middot;</span>
			<p class="item-meta last-active">
				<?php
				esc_html_e( 'Last active ', 'buddyboss-platform' );
				bboss_group_last_active();
				?>
			</p>
		</div>
	</a>
</div>
