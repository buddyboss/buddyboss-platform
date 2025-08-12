<?php
/**
 * The template for groups card.
 *
 * This template can be overridden by copying it to yourtheme/buddypress/groups/group-card.php.
 *
 * @since   BuddyBoss 2.8.20
 * @version 1.0.0
 */
?>
<script type="text/html" id="tmpl-group-card-popup">
	<div id="group-card" class="bb-group-card bb-popup-card" data-bp-item-id="" data-bp-item-component="groups">

		<div class="skeleton-card">
			<div class="skeleton-card-body">
				<div class="skeleton-card-avatar bb-loading-bg"></div>
				<div class="skeleton-card-entity">
					<div class="skeleton-card-heading bb-loading-bg"></div>
					<div class="skeleton-card-meta bb-loading-bg"></div>
				</div>
			</div>
			<?php $plain_class = ! is_user_logged_in() ? 'skeleton-footer-plain' : ''; ?>
			<div class="skeleton-card-footer <?php echo esc_attr( $plain_class ); ?>">
				<div class="skeleton-card-button bb-loading-bg"></div>
				<div class="skeleton-card-button bb-loading-bg"></div>
			</div>
		</div>

		<div class="bb-card-content">
			<div class="bb-card-body">
				<div class="bb-card-avatar">
					<img src="" alt="">
				</div>
				<div class="bb-card-entity">
					<h4 class="bb-card-heading"></h4>
					<div class="bb-card-meta">
						<span class="card-meta-item card-meta-status"></span>
						<span class="card-meta-item card-meta-type"></span>
						<span class="card-meta-item card-meta-last-active"></span>
					</div>
					<div class="card-group-members">
						<span class="bs-group-members"></span>
					</div>
				</div>
			</div>
			<div class="bb-card-footer">
				<?php if ( is_user_logged_in() ) : ?>
					<div class="bb-card-action bb-card-action-join bb-card-action-primary"></div>
				<?php endif; ?>
				<div class="bb-card-action bb-card-action-outline">
					<a href="" class="card-button card-button-group"><?php esc_html_e( 'View Group', 'buddyboss' ); ?></a>
				</div>
			</div>
		</div>

	</div>
</script>
