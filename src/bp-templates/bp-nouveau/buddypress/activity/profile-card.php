<?php
/**
 * The template for pofile/groups card.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/activity/profile-card.php.
 *
 * @since   BuddyBoss 2.5.80
 * @version 1.0.0
 */
?>
<div id="profile-card" class="bb-profile-card bb-popup-card" data-bp-item-id="" data-bp-item-component="members" style="display: none;">

	<div class="skeleton-card">
		<div class="skeleton-card-body">
			<div class="skeleton-card-avatar bb-loading-bg"></div>
			<div class="skeleton-card-entity">
				<div class="skeleton-card-type bb-loading-bg"></div>
				<div class="skeleton-card-heading bb-loading-bg"></div>
				<div class="skeleton-card-meta bb-loading-bg"></div>
			</div>
		</div>
		<?php $plain_class = !is_user_logged_in() ? 'skeleton-footer-plain' : ''; ?>
		<div class="skeleton-card-footer <?php echo $plain_class; ?>">
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
					<span class="card-meta-joined"><?php esc_html_e( 'Joined', 'buddyboss' ); ?> <span></span></span>
					<span class="card-meta-last-active"></span>
					<span class="card-meta-followers"></span>
				</div>
			</div>
		</div>
		<div class="bb-card-footer">
			<?php if ( is_user_logged_in() ) : ?>
				<?php if ( bp_is_active( 'messages' ) ) : ?>
					<div class="bb-card-action bb-card-action-primary">
						<a href="" class="card-button send-message">
							<i class="bb-icon-l bb-icon-comment"></i>
							<?php esc_html_e( 'Message', 'buddyboss' ); ?>
						</a>
					</div>
				<?php else : ?>
					<?php if ( bp_is_active( 'friends' ) ) : ?>
						<div class="bb-card-action bb-card-action-primary">
							<a href="" class="card-button connect">
								<i class="bb-icon-l bb-icon-user-plus"></i>
								<?php esc_html_e( 'Connect', 'buddyboss' ); ?>
							</a>
						</div>
					<?php endif; ?>
				<?php endif; ?>
				<div class="bb-card-action bb-card-action-secondary">
					<div class="follow-button generic-button" id="">
						<button class="card-button card-button-follow secondary follow-button" data-bp-nonce="" id="" data-bp-btn-action="not_following">
							<i class="bb-icon-l bb-icon-bullhorn"></i>
							<?php esc_html_e( 'Follow', 'buddyboss' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>
			<div class="bb-card-action bb-card-action-outline">
				<a href="" class="card-button card-button-profile"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></a>
			</div>
		</div>
	</div>

</div>
