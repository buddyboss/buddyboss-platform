<?php
/**
 * BuddyBoss - Groups Loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/groups-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_before_loop(); ?>

<?php if ( bp_get_current_group_directory_type() ) : ?>
	<div class="bp-feedback info">
	<span class="bp-icon" aria-hidden="true"></span>
	<p class="current-group-type"><?php bp_current_group_directory_type_message(); ?></p>
	</div>
<?php endif; ?>

<?php $cover_class = bp_disable_group_cover_image_uploads() ? 'bb-cover-disabled' : 'bb-cover-enabled'; ?>

<?php if ( bp_has_groups( bp_ajax_querystring( 'groups' ) ) ) : ?>

	<?php bp_nouveau_pagination( 'top' ); ?>

	<ul id="groups-list" class="<?php bp_nouveau_loop_classes(); ?> <?php echo $cover_class; ?>">

	<?php
	while ( bp_groups() ) :
		bp_the_group();
		?>

		<li <?php bp_group_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_group_id(); ?>" data-bp-item-component="groups">
			<div class="list-wrap">

				<?php if ( ! bp_disable_group_cover_image_uploads() ) { ?>
					<?php
					$group_cover_image_url = bp_attachments_get_attachment(
						'url',
						array(
							'object_dir' => 'groups',
							'item_id'    => bp_get_group_id(),
						)
					);
					$has_default_cover     = function_exists( 'bb_attachment_get_cover_image_class' ) ? bb_attachment_get_cover_image_class( bp_get_group_id(), 'group' ) : '';
					?>
						<div class="bs-group-cover only-grid-view <?php echo esc_attr( $has_default_cover ); ?>"><a href="<?php bp_group_permalink(); ?>"><img src="<?php echo esc_url( $group_cover_image_url ); ?>"></a></div>

				<?php } ?>

				<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
					<div class="item-avatar">
						<a href="<?php bp_group_permalink(); ?>" class="group-avatar-wrap"><?php bp_group_avatar( bp_nouveau_avatar_args() ); ?></a>
					</div>
				<?php endif; ?>

				<div class="item">

					<div class="item-block">

						<h2 class="list-title groups-title"><?php bp_group_link(); ?></h2>

						<?php if ( bp_nouveau_group_has_meta() ) : ?>

							<p class="item-meta group-details only-list-view"><?php bp_nouveau_group_meta(); ?></p>
							<p class="item-meta group-details only-grid-view">
							<?php
								$meta = bp_nouveau_get_group_meta();
								echo $meta['status'];
							?>
							</p>
						<?php endif; ?>

						<p class="last-activity item-meta">
							<?php
							printf(
								/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
								__( 'active %s', 'buddyboss' ),
								bp_get_group_last_active()
							);
							?>
						</p>

					</div>

					<div class="item-desc group-item-desc only-list-view"><?php bp_group_description_excerpt( false, 150 ); ?></div>

					<?php bp_nouveau_groups_loop_item(); ?>

					<div class="group-members-wrap">
						<?php bb_groups_loop_members(); ?>
					</div>

					<div class="groups-loop-buttons footer-button-wrap"><?php bp_nouveau_groups_loop_buttons(); ?></div>

				</div>


			</div>
		</li>

	<?php endwhile; ?>

	</ul>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php else : ?>

	<?php bp_nouveau_user_feedback( 'groups-loop-none' ); ?>

<?php endif; ?>

<?php
bp_nouveau_after_loop();
