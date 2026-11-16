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
function bboss_core_admin_help_main_page() {

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
				<h3 id="article-child-title" style="display: none;"><?php esc_html_e( 'Articles', 'buddyboss' ); ?></h3>
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
function bboss_core_admin_help() {
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
			<?php bboss_core_admin_tabs( __( 'Help', 'buddyboss' ) ); ?>
		</h2>
	</div>
	<div class="wrap">
	<h1>
		<?php esc_html_e( 'Documentation', 'buddyboss' ); ?>
		<a href="https://www.buddyboss.com/resources/docs/" class="page-title-action"
		   target="_blank"><?php esc_html_e( 'Resources Website', 'buddyboss' ); ?></a>
	</h1>

	<div class="wp-list-table widefat bp-help-card-grid">
		<?php
		bboss_core_admin_help_main_page();
		?>
	</div>
	<?php
}

/**
 * Get the article value from GLOBAL variable
 *
 * @return string
 */
function bboss_core_admin_help_get_article_value() {
	return isset( $_REQUEST['article'] ) ? $_REQUEST['article'] : '';
}

// @bbwt-com-start compat-layer
// bbwt-rename:compat-aliases — the pre-rename names of this file's symbols (decision 0013): declared
// here, guarded on the renamed declaration, so each old name exists exactly when the new one does.
if ( function_exists( 'bboss_core_admin_help' ) && ! function_exists( 'bp_core_admin_help' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_help()}.
	 *
	 * @deprecated Use bboss_core_admin_help() instead.
	 */
	function bp_core_admin_help() {
		return bboss_core_admin_help();
	}
}
if ( function_exists( 'bboss_core_admin_help_get_article_value' ) && ! function_exists( 'bp_core_admin_help_get_article_value' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_help_get_article_value()}.
	 *
	 * @deprecated Use bboss_core_admin_help_get_article_value() instead.
	 */
	function bp_core_admin_help_get_article_value() {
		return bboss_core_admin_help_get_article_value();
	}
}
if ( function_exists( 'bboss_core_admin_help_main_page' ) && ! function_exists( 'bp_core_admin_help_main_page' ) ) {
	/**
	 * Backward-compatible alias of {@see bboss_core_admin_help_main_page()}.
	 *
	 * @deprecated Use bboss_core_admin_help_main_page() instead.
	 */
	function bp_core_admin_help_main_page() {
		return bboss_core_admin_help_main_page();
	}
}
// @bbwt-com-end compat-layer
