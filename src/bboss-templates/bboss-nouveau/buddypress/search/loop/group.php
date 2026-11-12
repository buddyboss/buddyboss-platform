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
				<span class="item-meta">
					<?php bboss_group_type(); ?>
				</span>
				<span class="middot">&middot;</span>
				<span class="item-meta group-details">
					<?php
					esc_html_e( 'Last active ', 'buddyboss' );
					bboss_group_last_active();
					?>
				</span>
			</span>
		</div>
	</div>
</li>
