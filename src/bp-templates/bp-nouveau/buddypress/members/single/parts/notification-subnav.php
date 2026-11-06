<?php
/**
 * BuddyBoss Account Notification Navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/parts/notification-subnav.php.
 *
 * @since BuddyBoss 2.2.6
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>

<nav class="subnav_tab" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Notification administration menu', 'buddyboss' ); ?>">
	<?php
	if ( bboss_nouveau_has_nav(
		array(
			'type'   => 'secondary',
			'object' => 'account_notifications',
		)
	) ) :
		?>
		<ul class="subnav">
			<?php
			while ( bboss_nouveau_nav_items() ) :
				bboss_nouveau_nav_item();

				$bp_nouveau = bboss_nouveau();
				$nav_item   = $bp_nouveau->current_nav_item;

				$nav_class       = array( 'bp-' . $bp_nouveau->displayed_nav . '-sub-tab' );
				$current_sub_tab = bboss_action_variables() ? bboss_action_variable( 0 ) : '';
				if (
					( 'notifications' === $nav_item->slug && '' === $current_sub_tab ) ||
					( 'notifications' !== $nav_item->slug && $nav_item->slug === $current_sub_tab )
				) {
					$nav_class = array_merge( $nav_class, array( 'current', 'selected' ) );
				}
				?>
				<li id="<?php bboss_nouveau_nav_id(); ?>" class="<?php echo esc_attr( join( ' ', $nav_class ) ); ?>">
					<a href="<?php bboss_nouveau_nav_link(); ?>" id="<?php bboss_nouveau_nav_link_id(); ?>">
						<?php
						bboss_nouveau_nav_link_text();
						if ( bboss_nouveau_nav_has_count() ) :
							?>
							<span class="count"><?php bboss_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
