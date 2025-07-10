<?php
/**
 * ReadyLaunch - Search model template.
 *
 * This template handles the search modal display and functionality
 * for advanced search features across the platform.
 *
 * @package BuddyBoss\Template
 * @subpackage BP_Nouveau\ReadyLaunch
 * @since BuddyBoss 2.9.00
 * @version 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
?>
<button class="bb-rl-button bb-rl-button--secondaryOutline bb-rl-header-search">
	<i class="bb-icons-rl-magnifying-glass"></i>
	<span class="bb-rl-header-search__label"><?php esc_html_e( 'Search community', 'buddyboss' ); ?></span>
</button>
<div id="bb-rl-network-search-modal" class="bb-rl-network-search-modal bb-rl-search-modal bp-hide">
	<transition name="modal">
		<div class="bb-rl-modal-mask">
			<div class="bb-rl-modal-wrapper">
				<div class="bp-search-form-wrapper header-search-wrap">
					<form action="<?php echo esc_url( home_url( '/' ) ); ?>" method="get" class="bp-dir-search-form search-form" id="search-form">
						<label for="search" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></label>
						<div class="bb-rl-network-search-bar">
							<input id="search" name="s" type="search" value="" placeholder="<?php esc_attr_e( 'Search community', 'buddyboss' ); ?>">
							<input type="hidden" name="bp_search" value="1">
							<button type="submit" id="search-submit" class="nouveau-search-submit">
								<span class="bb-icons-rl-magnifying-glass" aria-hidden="true"></span>
								<span id="button-text" class="bp-screen-reader-text"><?php esc_html_e( 'Search', 'buddyboss' ); ?></span>
							</button>
							<a href="javascript:;" class="bb-rl-network-search-clear bp-hide"><?php esc_html_e( 'Clear Search', 'buddyboss' ); ?></a>
							<div class="bb-rl-network-search-filter bb-rl-filter bb-rl-filter-rounded">
								<?php
								$searchable_items = BP_Search::instance()->get_available_search();
								if ( ! empty( $searchable_items ) ) {
									echo '<select class="bb-rl-search-filter-select" name="subset" data-bb-caret="caret-arrow" data-bb-border="rounded">';
									foreach ( $searchable_items as $key => $label ) {
										echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, 'all', false ) . '>' . esc_html( $label ) . '</option>';
									}
									echo '</select>';
								}
								?>
							</div>
						</div>
						<div class="bb-rl-ac-results bb-rl-search-results-container"></div>
					</form>

				</div>
			</div>
		</div>
	</transition>
</div>
