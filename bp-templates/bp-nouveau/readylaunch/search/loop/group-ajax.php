<?php
/**
 * ReadyLaunch - Search Loop Group AJAX template.
 *
 * The template for AJAX search results for groups.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<div class="bp-search-ajax-item bboss_ajax_search_group">
	<a href="<?php echo esc_url( bp_get_group_permalink() ); ?>">
		<div class="item-avatar">
			<?php bp_group_avatar( 'type=thumb&width=50&height=50' ); ?>
		</div>
	</a>
	<div class="item">
		<div class="item-title">
			<?php bp_group_link(); ?>
		</div>
		<div class="entry-meta">
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
	</div>
</div>
