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
 * Render the BuddyBoss Help page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help_main_menu( $main_directories, $docs_path ) {
	foreach ( $main_directories as $sub_directories ) {

		$dir_pos = false !== strpos( $sub_directories, 'miscellaneous' ) ? true : false;

		if ( ! empty( $dir_pos ) ) {
			continue;
		}

		$index_file  = glob( $sub_directories . '/0-*.md' );
		$directories = array_diff( glob( $sub_directories . '/*' ), $index_file );

		// converting array into string.
		$index_file = current( $index_file );
		?>
		<div class="bp-help-card bp-help-menu-wrap">
			<div class="inside">
				<?php

				$url = add_query_arg( 'article', str_replace( $docs_path, '', $index_file ) );

				// print the title of the section
				printf( '<h2><a href="%s">%s</a></h2>', $url, bp_core_admin_help_get_file_title( $index_file ) );

				// print the article content
				$content = bp_core_admin_help_display_content( $index_file );

				$content_array = explode( '<p>', $content );

				if ( ! empty( $content_array[1] ) ) {
					echo $content_array[1];
				} else {
					$content = bp_core_strip_header_tags( $content );
					echo wp_trim_words( $content, 30, null );
				}
				?>
			</div>
		</div>
		<?php
	}
}

/**
 * Display Sub menu of Main Menu
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $directories
 * @param $times
 * @param $docs_path
 */
function bp_core_admin_help_sub_menu( $directories, $times, $docs_path, $level_hide = 1, $show_as_heading = false, $hide_parent = false ) {
	$article      = bp_core_admin_help_get_article_value();
	$article_path = $docs_path . $article;

	if ( empty( $show_as_heading ) ) {
		$ul_classed  = $times > $level_hide ? 'hidden' : '';
		$ul_classed .= ' loop-' . $times;
		?>
		<ul class="<?php echo $ul_classed; ?> ">
		<?php
	}

	do_action( 'bp_core_admin_help_sub_menu_before', $directories, $times, $docs_path, $level_hide, $show_as_heading, $hide_parent );

	// For showing the menu title
	foreach ( $directories as $directory ) {
		$dir_pos = false !== strpos( $article_path, $directory ) ? true : false;

		// use in breadcrumb
		if ( empty( $dir_pos ) && ! empty( $show_as_heading ) ) {
			continue;
		}

		// add class to menu and sub menu level
		$slug = bp_core_get_post_slug_by_index( $directory );

		$dir_index_file = $directory;
		$is_dir         = is_dir( $directory );
		if ( $is_dir ) {
			// the the main file from the directory
			$dir_index_file = glob( $directory . '/0-*.md' );
			$loop_dir       = array_diff( glob( $directory . '/*' ), $dir_index_file );

			$dir_index_file = current( $dir_index_file );
			$url            = add_query_arg( 'article', str_replace( $docs_path, '', $dir_index_file ) );
		}

		// check condition on file deleted
		$file_delete = false !== strpos( $dir_index_file, 'delete-' ) ? true : false;
		if ( ! empty( $file_delete ) ) {
			continue;
		}

		$selected  = $dir_index_file == $article_path ? 'selected main' : 'main';
		$selected .= ' level-' . $times . ' ' . $slug;
		?>
	<li class="<?php echo $selected; ?>">
		<?php
		// check if it's has directory
		if ( $is_dir ) {
			if ( ! empty( $loop_dir ) ) {

				/**
				 * Count variable is getting updated via JS
				 */
				$count_html = sprintf( '<span class="sub-menu-count">(%s)</span>', 0 );

				$action = '<span class="actions"><span class="open">+</span></span>';

				if ( ( $article && 1 == $times ) || ! empty( $show_as_heading ) ) {
					$action     = '';
					$count_html = '';
				}

				if ( empty( $hide_parent ) && ! empty( $dir_index_file ) ) {
					printf( '<a href="%s" class="dir">%s %s</a>%s', $url, bp_core_admin_help_get_file_title( $dir_index_file ), $count_html, $action );
				}
				if ( ! empty( $show_as_heading ) ) {
					?>
					</li>
					<?php
				}
				bp_core_admin_help_sub_menu( $loop_dir, $times + 1, $docs_path, $level_hide, $show_as_heading );
			} elseif ( empty( $hide_parent ) ) {
				printf( '<a href="%s" class="dir">%s</a>', $url, bp_core_admin_help_get_file_title( $dir_index_file ) );
			}
		} elseif ( empty( $hide_parent ) ) {
			$url = add_query_arg( 'article', str_replace( $docs_path, '', $directory ) );
			// print the title if it's a .md file
			printf( '<a href="%s" class="file">%s</a>', $url, bp_core_admin_help_get_file_title( $directory ) );
		}
		?>
		</li>
		<?php
	}

	do_action( 'bp_core_admin_help_sub_menu_after', $directories, $times, $docs_path, $level_hide, $show_as_heading, $hide_parent );

	if ( empty( $show_as_heading ) ) {
		?>
		</ul>
		<?php
	}
}

/**
 * Get the title from the .md file
 *
 * @since BuddyBoss 1.0.0
 *
 * @return string
 */
function bp_core_admin_help_get_file_title( $file_path ) {
	return substr( fgets( fopen( $file_path, 'r' ) ), 1 );
}

/**
 * Display Help Page content
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $docs_path
 */
function bp_core_admin_help_display_content( $docs_path ) {
	$base_path   = buddypress()->plugin_dir . 'bp-help';
	$vendor_path = $base_path . '/vendors';
	require_once $vendor_path . '/parsedown/Parsedown.php';

	$Parsedown = new Parsedown();
	$text      = file_get_contents( $docs_path );

	return bp_core_help_wrap_the_content_filter( $Parsedown->text( $text ) );
}

/**
 * Show the main index page of HELP page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help_main_page() {

	?>
	<div class="bp-help-main-menu-wrap" id="bp-help-main-menu-wrap">

	</div>
	<?php

	$base_path   = buddypress()->plugin_dir . 'bp-help';
	$docs_path   = $base_path . '/docs/';
	$vendor_path = $base_path . '/vendors';

	$main_directories = glob( $docs_path . '*', GLOB_ONLYDIR );

	$article_value = bp_core_admin_help_get_article_value();

	if ( ! empty( $main_directories ) ) {
		if ( empty( $article_value ) ) {
			?>
			<div class="bp-help-main-menu-wrap">
				<?php
				bp_core_admin_help_main_menu( $main_directories, $docs_path );
				?>
			</div>
			<?php
		} else {
			/**
			 * Sidebar main dir path
			 */
			$article_dir_array = explode( '/', $article_value );
			$content_main_dir  = $docs_path . $article_dir_array[0];

			/**
			 * Show display sidebar or not
			 */
			$sidebar = false !== strpos( $article_value, 'miscellaneous' ) ? false : true;
			?>

			<div class="bp-help-content-wrap">

				<?php
				if ( $sidebar ) {
					?>
					<div class="bp-help-sidebar">
						<?php
						bp_core_admin_help_sub_menu( (array) $content_main_dir, '1', $docs_path, 2 );
						?>
					</div>
					<?php
				}
				?>

				<div class="bp-help-content">
					<ul class="bp-help-menu">
						<?php
						add_action( 'bp_core_admin_help_sub_menu_before', 'bp_core_admin_help_sub_menu_before_callback', 10, 5 );
						bp_core_admin_help_sub_menu( (array) $content_main_dir, '1', $docs_path, 2, true );
						remove_action( 'bp_core_admin_help_sub_menu_before', 'bp_core_admin_help_sub_menu_before_callback', 10 );
						?>
					</ul>


					<?php

					// print file content
					echo bp_core_admin_help_display_content( $docs_path . $article_value );

					// print submenu
					if ( bp_core_admin_help_had_more_directory( $docs_path . $article_value ) ) {
						unset( $article_dir_array[ ( count( $article_dir_array ) - 1 ) ] );
						$article_dir_array  = implode( '/', $article_dir_array );
						$current_doc_path[] = $docs_path . $article_dir_array;
						?>
						<div class="article-child well">
							<?php
							printf( '<h3>%s</h3>', __( 'Articles', 'buddyboss' ) );
							bp_core_admin_help_sub_menu( $current_doc_path, '1', $docs_path, 3, false, true );
							?>
						</div>
						<?php
					}
					?>
				</div>

			</div>
			<?php
		}
	}
}

/**
 * Check if the given path has more directory. True if has more directory else false
 *
 * @since BuddyBoss 1.0.0
 *
 * @param string $path
 *
 * @return bool $more_dir True if has more directory else false
 */
function bp_core_admin_help_had_more_directory( $path ) {
	$more_dir   = false;
	$path_array = explode( '/', $path );
	$file_name  = end( $path_array );

	if ( strpos( $file_name, '0-overview.md' ) !== false ) {
		$more_dir = true;
	}

	return $more_dir;
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
		   target="_blank"><?php _e( 'Online Resources', 'buddyboss' ); ?></a>
	</h1>

	<div class="wp-list-table widefat bp-help-card-grid">
		<?php
		bp_core_admin_help_main_page();
		?>
	</div>
	<?php
}

/**
 * Add Help Menu
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $directories
 * @param $times
 * @param $docs_path
 * @param $level_hide
 * @param $show_as_heading
 */
function bp_core_admin_help_sub_menu_before_callback( $directories, $times, $docs_path, $level_hide, $show_as_heading ) {
	if ( 1 == $times ) {
		$url = bp_get_admin_url( add_query_arg( array( 'page' => 'bp-help' ), 'admin.php' ) );
		printf( '<li class="main"><a href="%s" class="dir">%s</a></li>', $url, __( 'Docs', 'buddyboss' ) );
	}
}

/**
 * Get the article value from GLOBAL variable
 *
 * @return string
 */
function bp_core_admin_help_get_article_value() {
	return isset( $_REQUEST['article'] ) ? $_REQUEST['article'] : '';
}
