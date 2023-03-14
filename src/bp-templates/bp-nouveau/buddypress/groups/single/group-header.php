<?php
/**
 * BuddyBoss - Groups Header
 *
 * This template is used to render the group header.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/single/group-header.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

$group_link       = bp_get_group_permalink();
$admin_link       = trailingslashit( $group_link . 'admin' );
$group_avatar     = trailingslashit( $admin_link . 'group-avatar' );
$group_cover_link = trailingslashit( $admin_link . 'group-cover-image' );
$tooltip_position = bp_disable_group_cover_image_uploads() ? 'down' : 'up';

add_filter( 'bp_get_group_description_excerpt', 'bb_get_group_description_excerpt_view_more', 99, 2 );
?>
<div id="cover-image-container" class="item-header-wrap <?php echo esc_attr( bb_platform_group_header_style() ); ?>">

	<div id="item-header-cover-image" class="<?php echo esc_attr( bp_disable_group_cover_image_uploads() ? 'bb-disable-cover-img' : 'bb-enable-cover-img' ); ?>">

		<?php if ( ! bp_disable_group_avatar_uploads() ) : ?>
			<div id="item-header-avatar">
				<?php if ( bp_is_item_admin() ) { ?>
					<a href="<?php echo esc_url( $group_avatar ); ?>" class="link-change-profile-image bp-tooltip" data-bp-tooltip-pos="up" data-bp-tooltip="<?php esc_attr_e( 'Change Group Photo', 'buddyboss' ); ?>">
						<i class="bb-icon-rf bb-icon-camera"></i>
					</a>
					<span class="link-change-overlay"></span>
				<?php } ?>
				<?php bp_group_avatar(); ?>
			</div><!-- #item-header-avatar -->
		<?php endif; ?>

		<?php if ( ! bp_nouveau_groups_front_page_description() ) : ?>
			<div id="item-header-content">

				<?php
				if ( function_exists( 'bp_enable_group_hierarchies' ) && bp_enable_group_hierarchies() ) {
					$parent_id = bp_get_parent_group_id();
					if ( 0 !== $parent_id ) {
						$parent_group = groups_get_group( $parent_id );
						?>
						<div class="bp-group-parent-wrap flex align-items-center">
							<?php bp_group_list_parents(); ?>
							<div class="bp-parent-group-title-wrap">
								<a class="bp-parent-group-title" href="<?php echo esc_url( bp_get_group_permalink( $parent_group ) ); ?>"><?php echo wp_kses_post( bp_get_group_name( $parent_group ) ); ?></a>
								<i class="bb-icon-l bb-icon-angle-right"></i>
								<span class="bp-current-group-title"><?php echo wp_kses_post( bp_get_group_name() ); ?></span>
							</div>
						</div>
						<?php
					}
				}
				?>

				<div class="flex align-items-center bp-group-title-wrap">
					<h2 class="bb-bp-group-title"><?php echo wp_kses_post( bp_get_group_name() ); ?></h2>
					<?php if ( bb_platform_group_headers_element_enable( 'group-type' ) ) : ?>
						<p class="bp-group-meta bp-group-type">
							<?php echo wp_kses( bp_nouveau_group_meta()->status, array( 'span' => array( 'class' => array() ) ) ); ?>
						</p>
					<?php endif; ?>
				</div>

				<?php
				do_action( 'bb_group_single_top_header_action' );

				echo isset( bp_nouveau_group_meta()->group_type_list ) ? wp_kses_post( bp_nouveau_group_meta()->group_type_list ) : '';

				bp_nouveau_group_hook( 'before', 'header_meta' );
				?>

				<?php if ( bp_nouveau_group_has_meta_extra() ) : ?>
					<div class="item-meta">
						<?php echo wp_kses_post( bp_nouveau_group_meta()->extra ); ?>
					</div><!-- .item-meta -->
				<?php endif; ?>

				<div class="bp-group-meta-wrap flex align-items-center">
					<?php
					if ( function_exists( 'bp_get_group_status_description' ) && bb_platform_group_headers_element_enable( 'group-privacy' ) ) :
						?>
							<p class="highlight bp-group-meta bp-group-status bp-tooltip" data-bp-tooltip-pos="down" data-bp-tooltip-length="large" data-bp-tooltip="<?php echo esc_attr( bp_get_group_status_description() ); ?>"><?php echo wp_kses( bp_nouveau_group_meta()->status, array( 'span' => array( 'class' => array() ) ) ); ?></p>
						<?php
					endif;

					if ( bb_platform_group_headers_element_enable( 'group-activity' ) ) :
						?>
						<p class="last-activity item-meta">
							<?php
							printf(
								/* translators: %s = last activity timestamp (e.g. "active 1 hour ago") */
								esc_html__( 'Active %s', 'buddyboss' ),
								wp_kses_post( bp_get_group_last_active() )
							);
							?>
						</p>
					<?php endif; ?>
				</div>

				<?php

				if (
					! bp_nouveau_groups_front_page_description() &&
					bp_nouveau_group_has_meta( 'description' ) &&
					bb_platform_group_headers_element_enable( 'group-description' )
				) :
					?>
					<div class="group-description">
						<?php bp_group_description_excerpt(); ?>
					</div><!-- //.group_description -->
					<?php
				endif;

				if ( bb_platform_group_headers_element_enable( 'group-type' ) ) :
					?>
					<p class="bp-group-meta bp-group-type"><?php echo wp_kses( bp_nouveau_group_meta()->status, array( 'span' => array( 'class' => array() ) ) ); ?></p>
				<?php endif; ?>

				<div class="group-actions-wrap" >
					<?php
						bp_get_template_part( 'groups/single/parts/header-item-actions' );
						do_action( 'bb_group_single_bottom_header_action' );
					?>
				</div>

			</div><!-- #item-header-content -->
		<?php endif; ?>

	</div><!-- #item-header-cover-image -->

</div><!-- .item-header-wrap -->

<!-- Group description popup -->
<div class="bb-action-popup" id="group-description-popup" style="display: none">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Group Description', 'buddyboss' ); ?></span></h4>
						<a class="bb-close-action-popup bb-model-close-button" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-action-popup-content">
						<p><?php bp_group_description(); ?></p>
					</div>
				</div>
			</div>
		</div>
	</transition>
</div> <!-- .bb-action-popup -->

<!-- Leave Group confirmation popup -->
<div class="bb-leave-group-popup bb-action-popup" style="display: none">
	<transition name="modal">
		<div class="modal-mask bb-white bbm-model-wrap">
			<div class="modal-wrapper">
				<div class="modal-container">
					<header class="bb-model-header">
						<h4><span class="target_name"><?php echo esc_html__( 'Leave Group', 'buddyboss' ); ?></span></h4>
						<a class="bb-close-leave-group bb-model-close-button" href="#">
							<span class="bb-icon-l bb-icon-times"></span>
						</a>
					</header>
					<div class="bb-leave-group-content bb-action-popup-content">
						<p><?php echo esc_html__( 'Are you sure you want to leave ', 'buddyboss' ); ?><span class="bb-group-name"></span>?</p>
					</div>
					<footer class="bb-model-footer flex align-items-center">
						<a class="bb-close-leave-group bb-close-action-popup" href="#"><?php echo esc_html__( 'Cancel', 'buddyboss' ); ?></a>
						<a class="button push-right bb-confirm-leave-group" href="#"><?php echo esc_html__( 'Confirm', 'buddyboss' ); ?></a>
					</footer>
				</div>
			</div>
		</div>
	</transition>
</div> <!-- .bb-leave-group-popup -->
<?php
remove_filter( 'bp_get_group_description_excerpt', 'bb_get_group_description_excerpt_view_more', 99, 2 );
