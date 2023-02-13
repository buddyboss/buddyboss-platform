<?php
/**
 * BuddyBoss Account Notification Navigation
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/single/parts/notification-subnav.php.
 *
 * @since BuddyBoss 2.2.6
 * @version 1.0.0
 */
?>

<nav class="subnav_tab" id="subnav" role="navigation" aria-label="<?php esc_attr_e( 'Notification administration menu', 'buddyboss' ); ?>">
	<?php
	if ( bp_nouveau_has_nav(
		array(
			'type'   => 'secondary',
			'object' => 'account_notifications',
		)
	) ) :
		?>
		<ul class="subnav">
			<?php
			while ( bp_nouveau_nav_items() ) :
				bp_nouveau_nav_item();

				$bp_nouveau = bp_nouveau();
				$nav_item   = $bp_nouveau->current_nav_item;

				$nav_class = array( 'bp-' . $bp_nouveau->displayed_nav . '-sub-tab' );
				if ( 'subscriptions' === $nav_item->slug && bp_action_variables() && 'subscriptions' === bp_action_variable( 0 ) ) {
					$nav_class = array_merge( $nav_class, array( 'current', 'selected' ) );
				} elseif ( 'notifications' === $nav_item->slug && ! bp_action_variables() ) {
					$nav_class = array_merge( $nav_class, array( 'current', 'selected' ) );
				}
				?>
				<li id="<?php bp_nouveau_nav_id(); ?>" class="<?php echo esc_attr( join( ' ', $nav_class ) ); ?>">
					<a href="<?php bp_nouveau_nav_link(); ?>" id="<?php bp_nouveau_nav_link_id(); ?>">
						<?php bp_nouveau_nav_link_text(); ?>

						<?php if ( bp_nouveau_nav_has_count() ) : ?>
							<span class="count"><?php bp_nouveau_nav_count(); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endwhile; ?>
		</ul>
	<?php endif; ?>
</nav><!-- #isubnav -->
