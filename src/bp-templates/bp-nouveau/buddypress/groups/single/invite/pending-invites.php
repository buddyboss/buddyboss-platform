<?php
/**
 * BuddyBoss - Groups Pending Invites
 *
 * @since BuddyBoss 1.2.3
 */

?>
<div id="group-invites-container">
	<?php bp_get_template_part( 'groups/single/parts/invite-subnav' ); ?>
	<div class="group-invites-column">
		<h2 class="bb-title"><?php _e( 'Pending Invites', 'buddyboss' ); ?></h2>
		<div class="subnav-filters group-subnav-filters bp-invites-filters">
			<div>
				<div class="group-invites-search subnav-search clearfix" role="search">
					<div class="bp-search">
						<form action="" method="get" id="group_invites_search_form" class="bp-invites-search-form" data-bp-search="group-invites">
							<label for="group_invites_search" class="bp-screen-reader-text"><?php bp_nouveau_search_default_text( __( 'Search Members', 'buddyboss' ), false ); ?></label>
							<input type="search" id="group_invites_search" placeholder="<?php esc_attr_e( 'Search Members', 'buddyboss' ); ?>"/>
							<button type="submit" id="group_invites_search_submit" class="nouveau-search-submit">
								<span class="bb-icons bb-icon-search" aria-hidden="true"></span>
								<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search Members', 'buddyboss' ); ?></span>
							</button>
						</form>
					</div>
				</div>
				<div class="last"></div>
			</div>
		</div>

		<div class="bp-invites-feedback" style="display: none;">
			<div class="bp-invites-feedback">
				<div class="bp-feedback loading">
					<span class="bp-icon" aria-hidden="true"></span>
					<p><?php esc_html_e( 'Loading Members. Please Wait.', 'buddyboss' ); ?></p>
				</div>
			</div>
		</div>

		<div id="bp-pending-invites-loader" class="bp-pending-invites-loader-hide">
			<i class="dashicons dashicons-update animate-spin"></i>
		</div>

		<div class="members bp-invites-content">
			<ul id="members-list" class="item-list bp-list"></ul>
		</div>


	</div>
</div>

