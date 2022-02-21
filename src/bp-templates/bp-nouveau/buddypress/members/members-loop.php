<?php
/**
 * The template for members loop
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/members-loop.php.
 *
 * @since   BuddyPress 3.0.0
 * @version 1.0.0
 */

bp_nouveau_before_loop(); ?>

<?php
$message_button_args = array(
		'link_text'   => '<i class="bb-icon-mail-small"></i>',
		'button_attr' => array(
				'data-balloon-pos' => 'down',
				'data-balloon'     => __( 'Message', 'buddyboss-theme' ),
		),
);

$footer_buttons_class = ( bp_is_active( 'friends' ) && bp_is_active( 'messages' ) ) ? 'footer-buttons-on' : '';

$is_follow_active = bp_is_active( 'activity' ) && function_exists( 'bp_is_activity_follow_active' ) && bp_is_activity_follow_active();
$follow_class     = $is_follow_active ? 'follow-active' : '';

// Member directories elements.
$enabled_online_status = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'online-status' );
$enabled_profile_type  = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'profile-type' );
$enabled_followers     = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'followers' );
$enabled_last_active   = ! function_exists( 'bb_enabled_member_directory_element' ) || bb_enabled_member_directory_element( 'last-active' );

?>

<?php if ( bp_get_current_member_type() ) : ?>
	<div class="bp-feedback info">
		<span class="bp-icon" aria-hidden="true"></span>
		<p><?php bp_current_member_type_message(); ?></p>
	</div>
<?php endif; ?>

<?php if ( bp_has_members( bp_ajax_querystring( 'members' ) ) ) : ?>

	<ul id="members-list" class="<?php bp_nouveau_loop_classes(); ?>">

		<?php
		while ( bp_members() ) :
			bp_the_member();
			?>
			<?php
			$member_id           = bp_get_member_user_id();
			$show_message_button = buddyboss_theme()->buddypress_helper()->buddyboss_theme_show_private_message_button( $member_id, bp_loggedin_user_id() );

			// Check if members_list_item has content.
			ob_start();
			bp_nouveau_member_hook( '', 'members_list_item' );
			$members_list_item_content = ob_get_contents();
			ob_end_clean();
			$member_loop_has_content = empty( $members_list_item_content ) ? false : true;
			?>
			<li <?php bp_member_class( array( 'item-entry' ) ); ?> data-bp-item-id="<?php bp_member_user_id(); ?>" data-bp-item-component="members">
				<div class="list-wrap <?php echo esc_attr( $footer_buttons_class ); ?> <?php echo esc_attr( $follow_class ); ?> <?php echo $member_loop_has_content ? ' has_hook_content' : ''; ?>">

					<div class="list-wrap-inner">
						<div class="item-avatar">
							<a href="<?php bp_member_permalink(); ?>">
								<?php
								if ( $enabled_online_status ) {
									bb_user_status( bp_get_member_user_id() );
								}
								bp_member_avatar( bp_nouveau_avatar_args() );
								?>
							</a>
						</div>

						<div class="item">

							<div class="item-block">
								<h2 class="list-title member-name">
									<a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>
								</h2>

								<?php
								if ( $enabled_profile_type && function_exists( 'bp_member_type_enable_disable' ) && true === bp_member_type_enable_disable() && true === bp_member_type_display_on_profile() ) {
									echo '<p class="item-meta last-activity">' . wp_kses_post( bp_get_user_member_type( bp_get_member_user_id() ) ) . '</p>';
								}
								?>

								<?php if ( $enabled_last_active ) : ?>
									<p class="item-meta last-activity">
										<?php echo wp_kses_post( bp_get_last_activity( bp_get_member_user_id() ) ); ?>
									</p>
								<?php endif; ?>
							</div>

							<div class="button-wrap member-button-wrap only-list-view">
								<?php
								if ( $enabled_followers && function_exists( 'bb_get_followers_count' ) ) {
									bb_get_followers_count( bp_get_member_user_id() );
								}

								if ( bp_is_active( 'friends' ) ) {
									bp_add_friend_button( bp_get_member_user_id() );
								}

								if ( bp_is_active( 'messages' ) ) {
									if ( 'yes' === $show_message_button ) {
										add_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
										add_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
										bp_send_message_button( $message_button_args );
										remove_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
										remove_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
									}
								}

								if ( $is_follow_active ) {
									bp_add_follow_button( bp_get_member_user_id(), bp_loggedin_user_id() );
								}
								?>
							</div>

							<?php
							if ( $is_follow_active ) {
								$justify_class = ( bp_get_member_user_id() == bp_loggedin_user_id() ) ? 'justify-center' : '';
								?>
								<div class="flex only-grid-view align-items-center follow-container <?php echo esc_attr( $justify_class ); ?>">
									<?php
									if ( $enabled_followers && function_exists( 'bb_get_followers_count' ) ) {
										bb_get_followers_count( bp_get_member_user_id() );
									}

									bp_add_follow_button( bp_get_member_user_id(), bp_loggedin_user_id() );
									?>
								</div>
							<?php } ?>
						</div><!-- // .item -->

						<?php if ( bp_is_active( 'friends' ) && bp_is_active( 'messages' ) && ( bp_get_member_user_id() != bp_loggedin_user_id() ) ) { ?>
							<div class="flex only-grid-view button-wrap member-button-wrap footer-button-wrap">
								<?php
								bp_add_friend_button( bp_get_member_user_id() );
								if ( 'yes' === $show_message_button ) {
									add_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
									add_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
									bp_send_message_button( $message_button_args );
									remove_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
									remove_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
								}
								?>
							</div>
						<?php } ?>

						<?php if ( bp_is_active( 'friends' ) && ! bp_is_active( 'messages' ) ) { ?>
							<div class="only-grid-view button-wrap member-button-wrap on-top">
								<?php bp_add_friend_button( bp_get_member_user_id() ); ?>
							</div>
						<?php } ?>

						<?php if ( ! bp_is_active( 'friends' ) && bp_is_active( 'messages' ) ) { ?>
							<div class="only-grid-view button-wrap member-button-wrap on-top">
								<?php
								if ( 'yes' === $show_message_button ) {
									add_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
									add_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
									bp_send_message_button( $message_button_args );
									remove_filter( 'bp_displayed_user_id', 'buddyboss_theme_member_loop_set_member_id' );
									remove_filter( 'bp_is_my_profile', 'buddyboss_theme_member_loop_set_my_profile' );
								}
								?>
							</div>
						<?php } ?>
					</div>

					<div class="bp-members-list-hook">
						<?php if ( $member_loop_has_content ) { ?>
							<a class="more-action-button" href="#"><i class="bb-icon-menu-dots-h"></i></a>
						<?php } ?>
						<div class="bp-members-list-hook-inner">
							<?php bp_nouveau_member_hook( '', 'members_list_item' ); ?>
						</div>
					</div>
				</div>
			</li>

		<?php endwhile; ?>

	</ul>

	<?php bp_nouveau_pagination( 'bottom' ); ?>

<?php
else :

	bp_nouveau_user_feedback( 'members-loop-none' );

endif;
?>

<?php bp_nouveau_after_loop(); ?>
