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
<div id="profile-card" class="bb-profile-card bb-popup-card" style="display: none;">

	<div class="skeleton-card">
		<div class="skeleton-card-body">
			<div class="skeleton-card-avatar bb-loading-bg"></div>
			<div class="skeleton-card-entity">
				<div class="skeleton-card-type bb-loading-bg"></div>
				<div class="skeleton-card-heading bb-loading-bg"></div>
				<div class="skeleton-card-meta bb-loading-bg"></div>
			</div>
		</div>
		<div class="skeleton-card-footer">
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
			<div class="bb-card-action bb-card-action-primary">
				<a href="" class="card-button send-message">
					<i class="bb-icon-l bb-icon-comment"></i>
					<?php esc_html_e( 'Message', 'buddyboss' ); ?>
				</a>
			</div>
			<div class="bb-card-action bb-card-action-secondary">
				<button class="card-button card-button-follow secondary" data-bp-nonce="" id="" data-bp-btn-action="">
					<i class="bb-icon-l bb-icon-bullhorn"></i>
					<?php esc_html_e( 'Follow', 'buddyboss' ); ?>
				</button>
			</div>
			<div class="bb-card-action bb-card-action-outline">
				<a href="" class="card-button card-button-profile"><?php esc_html_e( 'View Profile', 'buddyboss' ); ?></a>
			</div>
		</div>
	</div>

</div>
