<?php
/**
 * BuddyBoss Help panel.
 *
 * @package BuddyBoss\Core
 * @since BuddyBoss 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Show the main index page of HELP page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help_main_page() {

	?>
	<div class="bp-help-main-menu-wrap" id="bp-help-main-menu-wrap" style="display:none" ></div>
	
	<div class="bp-help-content-wrap" style="display:none">
		<div class="bp-help-sidebar">

		</div>
		<div class="bp-help-content">
			<!-- print breadcrumbs -->
			<ul class="bp-help-menu">

			</ul>
			<!-- print content -->
			<div id="bp-help-content-area"></div>

			<!-- print submenu -->
			<div class="article-child well">
				<h3 id="article-child-title" style="display: none;"><?php _e( 'Articles', 'buddyboss' ); ?></h3>
			</div>
		</div>
	</div>
	<?php
}

/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help() {
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php bp_core_admin_tabs( __( 'Help', 'buddyboss' ) ); ?>
		</h2>
	</div>
	<div class="wrap">
	<h1>
		<?php _e( 'Documentation', 'buddyboss' ); ?>
		<a href="https://www.buddyboss.com/resources/docs/" class="page-title-action"
		   target="_blank"><?php _e( 'Resources Website', 'buddyboss' ); ?></a>
	</h1>

	<div class="wp-list-table widefat bp-help-card-grid">
		<?php
		bp_core_admin_help_main_page();
		?>
	</div>
	<?php
}

/**
 * Get the article value from GLOBAL variable
 *
 * @return string
 */
function bp_core_admin_help_get_article_value() {
	return isset( $_REQUEST['article'] ) ? $_REQUEST['article'] : '';
}
