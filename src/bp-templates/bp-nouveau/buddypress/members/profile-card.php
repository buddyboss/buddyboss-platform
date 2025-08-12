<?php
/**
 * The template for profile card.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/members/profile-card.php.
 *
 * @since   BuddyBoss 2.8.20
 * @version 1.0.0
 */

?>
<script type="text/html" id="tmpl-profile-card-popup">
	<div id="profile-card" class="bb-profile-card bb-popup-card" data-bp-item-id="" data-bp-item-component="members">

		<div class="skeleton-card">
			<div class="skeleton-card-body">
				<div class="skeleton-card-avatar bb-loading-bg"></div>
				<div class="skeleton-card-entity">
					<div class="skeleton-card-type bb-loading-bg"></div>
					<div class="skeleton-card-heading bb-loading-bg"></div>
					<div class="skeleton-card-meta bb-loading-bg"></div>
				</div>
			</div>
			<?php $plain_class = ! is_user_logged_in() ? 'skeleton-footer-plain' : ''; ?>
			<div class="skeleton-card-footer <?php echo esc_attr( $plain_class ); ?>">
				<div class="skeleton-card-button bb-loading-bg"></div>
				<div class="skeleton-card-button bb-loading-bg"></div>
				<div class="skeleton-card-button bb-loading-bg"></div>
			</div>
		</div>

		<div class="bb-card-content">
			<div class="bb-card-body">
				<div class="bb-card-avatar">
					<span class="card-profile-status"></span>
					<img src="" alt="">
				</div>
				<div class="bb-card-entity">
					<div class="bb-card-profile-type"></div>
					<h4 class="bb-card-heading"></h4>
					<div class="bb-card-meta">
						<span class="card-meta-item card-meta-joined"><?php esc_html_e( 'Joined', 'buddyboss' ); ?> <span></span></span>
						<span class="card-meta-item card-meta-last-active"></span>
						<?php
						if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
							?>
							<span class="card-meta-item card-meta-followers"></span>
							<?php
						}
						?>
					</div>
				</div>
			</div>
			<div class="bb-card-footer">
				<?php
				if ( is_user_logged_in() ) {
					if ( bp_is_active( 'messages' ) ) {
						?>
						<div class="bb-card-action bb-card-action-message bb-card-action-primary">
							<a href="" class="card-button send-message">
								<i class="bb-icon-l bb-icon-comment"></i>
								<?php esc_html_e( 'Message', 'buddyboss' ); ?>
							</a>
						</div>
						<?php
					}

					if ( bp_is_active( 'friends' ) ) {
						?>
						<div class="bb-card-action bb-card-action-connect bb-card-action-primary"></div>
						<?php
					}

					if ( bp_is_active( 'activity' ) && bp_is_activity_follow_active() ) {
						?>
						<div class="bb-card-action bb-card-action-follow bb-card-action-secondary"></div>
						<?php
					}
				}
				?>
				<div class="bb-card-action bb-card-action-outline">
					<a href="" class="card-button card-button-profile"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></a>
				</div>
			</div>
		</div>

	</div>
</script>
