<?php
/**
 * The template for groups card.
 *
 * This template handles the group card popup display with skeleton loading
 * and group information including avatar, title, meta, and action buttons.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.8.20
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

?>
<script type="text/html" id="tmpl-group-card-popup">
	<div id="group-card" class="bb-rl-group-card bb-rl-popup-card" data-bp-item-id="" data-bp-item-component="groups">

		<div class="skeleton-card">
			<div class="skeleton-card-body">
				<div class="skeleton-card-avatar bb-rl-loading-bg"></div>
				<div class="skeleton-card-entity">
					<div class="skeleton-card-heading bb-rl-loading-bg"></div>
					<div class="skeleton-card-meta bb-rl-loading-bg"></div>
				</div>
			</div>
			<?php $plain_class = ! is_user_logged_in() ? 'skeleton-footer-plain' : ''; ?>
			<div class="skeleton-card-footer <?php echo esc_attr( $plain_class ); ?>">
				<div class="skeleton-card-button bb-rl-loading-bg"></div>
				<div class="skeleton-card-button bb-rl-loading-bg"></div>
			</div>
		</div>

		<div class="bb-rl-card-content">
			<div class="bb-rl-card-body">
				<div class="bb-rl-card-avatar">
					<img src="" alt="">
				</div>
				<div class="bb-rl-card-entity">
					<h4 class="bb-rl-card-heading"></h4>
					<div class="bb-rl-card-meta">
						<span class="card-meta-item card-meta-status"></span>
						<span class="card-meta-item card-meta-type"></span>
						<span class="card-meta-item card-meta-last-active"></span>
					</div>
					<div class="card-group-members">
						<span class="bs-group-members"></span>
					</div>
				</div>
			</div>
			<div class="bb-rl-card-footer">
				<?php if ( is_user_logged_in() ) : ?>
					<div class="bb-rl-card-action bb-rl-card-action-join bb-rl-card-action-primary"></div>
				<?php endif; ?>
				<div class="bb-rl-card-action bb-rl-card-action-outline">
					<a href="" class="card-button card-button-group"><?php esc_html_e( 'View Group', 'buddyboss' ); ?></a>
				</div>
			</div>
		</div>

	</div>
</script>
