<?php
/**
 * ReadyLaunch - Search Loop Group template.
 *
 * The template for search results for groups.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<li <?php bp_group_class( array( 'item-entry bp-search-item bp-search-item_group' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
	<div class="list-wrap">
		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div class="item-avatar">
				<a href="<?php bp_group_permalink(); ?>" data-bb-hp-group="<?php echo esc_attr( bp_get_group_id() ); ?>"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
			</div>
		<?php endif; ?>
		<div class="item">
			<h2 class="item-title groups-title"><?php bp_group_link(); ?></h2>
			<div class="group-description">
				<?php bp_group_description(); ?>
			</div><!-- //.group_description -->
			<span class="entry-meta">
				<span class="item-meta-type">
					<?php bp_group_type(); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="item-meta-details group-details">
					<?php
					esc_html_e( 'Last active ', 'buddyboss' );
					bp_group_last_active();
					?>
				</span>
			</span>
		</div>
	</div>
</li>
