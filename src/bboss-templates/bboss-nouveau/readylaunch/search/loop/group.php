<?php
/**
 * ReadyLaunch - Search Loop Group template.
 *
 * The template for search results for groups.
 *
 * @package BuddyBoss\Template
 * @subpackage BBoss_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<li <?php bboss_group_class( array( 'item-entry bp-search-item bp-search-item_group' ) ); ?> data-bp-item-id="<?php bboss_group_id(); ?>" data-bp-item-component="groups">
	<div class="list-wrap">
		<?php if ( ! bboss_disable_group_avatar_uploads() ) : ?>
			<div class="item-avatar">
				<a href="<?php bboss_group_permalink(); ?>" data-bb-hp-group="<?php echo esc_attr( bboss_get_group_id() ); ?>"><?php bboss_group_avatar( bboss_nouveau_avatar_args() ); ?></a>
			</div>
		<?php endif; ?>
		<div class="item">
			<h2 class="item-title groups-title"><?php bboss_group_link(); ?></h2>
			<div class="group-description">
				<?php bboss_group_description(); ?>
			</div><!-- //.group_description -->
			<span class="entry-meta">
				<span class="item-meta-type">
					<?php bboss_group_type(); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="item-meta-details group-details">
					<?php
					esc_html_e( 'Last active ', 'buddyboss-platform' );
					bboss_group_last_active();
					?>
				</span>
			</span>
		</div>
	</div>
</li>
