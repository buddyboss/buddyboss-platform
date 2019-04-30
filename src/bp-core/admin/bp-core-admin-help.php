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

		$index_file  = glob( $sub_directories . "/0-*.md" );
		$directories = array_diff( glob( $sub_directories . "/*" ), $index_file );

		// converting array into string.
		$index_file = current( $index_file );
		?>
        <div class="bp-help-card bb-help-menu-wrap">
			<?php

			$url = add_query_arg( 'article', str_replace( $docs_path, "", $index_file ) );

			// print the title of the section
			printf( '<h3><a href="%s">%s</a></h3>', $url, fgets( fopen( $index_file, 'r' ) ) );
			?>

            <div class="inside bb-help-menu">
				<?php
				bp_core_admin_help_sub_menu( $directories, '1', $docs_path );
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
function bp_core_admin_help_sub_menu( $directories, $times, $docs_path, $level_hide = 1, $show_as_heading = false ) {
	$article = ! empty( $_GET['article'] ) ? $_GET['article'] : '';

	if ( empty( $show_as_heading ) ) {
		$ul_classed = $times > $level_hide ? 'hidden' : '';
		$ul_classed .= ' loop-' . $times;
		?>
        <ul class="<?php echo $ul_classed; ?> ">
		<?php
	}


	do_action( 'bp_core_admin_help_sub_menu_before', $directories, $times, $docs_path, $level_hide, $show_as_heading );

	// For showing the menu title
	foreach ( $directories as $directory ) {
		$dir_pos = false !== strpos( $docs_path . $article, $directory ) ? true : false;

		// use in breadcrumb
		if ( empty( $dir_pos ) && ! empty( $show_as_heading ) ) {
			continue;
		}

		// add class to menu and sub menu level
		$slug = bp_core_get_post_slug_by_index( $directory );
		$selected = $dir_pos ? 'selected main' : 'main';
		$selected .= ' level-' . $times . ' ' . $slug;

		$dir_index_file = $directory;
		$is_dir = is_dir( $directory );
		if ( $is_dir ) {
			// the the main file from the directory
			$dir_index_file = glob( $directory . "/0-*.md" );
			$loop_dir       = array_diff( glob( $directory . '/*' ), $dir_index_file );

			$dir_index_file = current( $dir_index_file );
			$url            = add_query_arg( 'article', str_replace( $docs_path, "", $dir_index_file ) );
        }

        // check condition on file deleted
		$file_delete = false !== strpos( $dir_index_file, 'delete-' ) ? true : false;
		if ( ! empty( $file_delete ) ) {
			continue;
		}

		?>
        <li class="<?php echo $selected; ?>">
            <?php
            // check if it's has directory
            if ( $is_dir ) {
                if ( ! empty( $loop_dir ) ) {

	                /**
	                 * Count variable is getting updated via JS
	                 */
                    $count_html = sprintf( '<span class="sub-menu-count">(%s)</span>',0 );

                    $action     = '<span class="actions"><span class="open">+</span></span>';
                    if ( ( $article && 1 == $times ) || ! empty( $show_as_heading ) ) {
                        $action     = '';
                        $count_html = '';
                    }

                    printf( '<a href="%s" class="dir">%s %s</a>%s', $url, fgets( fopen( $dir_index_file, 'r' ) ), $count_html, $action );
                    $times ++;
                    if ( ! empty( $show_as_heading ) ) {
                        ?>
                        </li>
                        <?php
                    }
                    bp_core_admin_help_sub_menu( $loop_dir, $times, $docs_path, $level_hide, $show_as_heading );
                } else {
                    printf( '<a href="%s" class="dir">%s</a>', $url, fgets( fopen( $dir_index_file, 'r' ) ) );
                }
            } else {
                $url = add_query_arg( 'article', str_replace( $docs_path, "", $directory ) );
                // print the title if it's a .md file
                printf( '<a href="%s" class="file">%s</a>', $url, fgets( fopen( $directory, 'r' ) ) );
            }
            ?>
        </li>
		<?php
	}

	add_action( 'bp_core_admin_help_sub_menu_after', $directories, $times, $docs_path, $level_hide, $show_as_heading );

	if ( empty( $show_as_heading ) ) {
		?>
        </ul>
		<?php
	}
}

/**
 * Display Help Page content
 *
 * @since BuddyBoss 1.0.0
 *
 * @param $docs_path
 * @param $vendor_path
 */
function bp_core_admin_help_display_content( $docs_path, $vendor_path ) {
	require_once $vendor_path . '/parsedown/Parsedown.php';
	$Parsedown = new Parsedown();
	$text      = file_get_contents( $docs_path . $_GET['article'] );


//	error_log( print_r( apply_filters( 'the_content', $Parsedown->text( $text ) ), true ) . "\n", 3, WP_CONTENT_DIR . '/debug_new.log' );
	return apply_filters( 'the_content', $Parsedown->text( $text ) );
}

/**
 * Show the main index page of HELP page
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help_main_page() {
	$base_path   = buddypress()->plugin_dir . 'bp-help';
	$docs_path   = $base_path . '/docs';
	$vendor_path = $base_path . '/vendors';

	$main_directories = glob( $docs_path . '/*', GLOB_ONLYDIR );

	if ( ! empty( $main_directories ) ) {
		if ( empty( $_REQUEST['article'] ) ) {
			?>
            <div class="bb-help-main-menu-wrap">
				<?php
				bp_core_admin_help_main_menu( $main_directories, $docs_path );
				?>
            </div>
			<?php
		} else {
			/**
			 * Sidebar main dir path
			 */
			$article_dir_array = explode( "/", $_REQUEST['article'] );
			$content_main_dir  = $docs_path . '/' . $article_dir_array[1];

			/**
			 * Show display sidebar or not
			 */
			$sidebar = false !== strpos( $_REQUEST['article'], 'miscellaneous' ) ? false : true;
			?>

            <div class="bb-help-content-wrap">
                
				<?php
				if ( $sidebar ) {
					?>
                    <div class="bb-help-sidebar">
						<?php
						bp_core_admin_help_sub_menu( (array) $content_main_dir, '1', $docs_path, 2 );
						?>
                    </div>
					<?php
				}
				?>

				<div class="bb-help-content">
					<ul class="bb-help-menu">
						<?php
						add_action( 'bp_core_admin_help_sub_menu_before', 'bp_core_admin_help_sub_menu_before_callback', 10, 5 );
						bp_core_admin_help_sub_menu( (array) $content_main_dir, '1', $docs_path, 2, true );
						remove_action( 'bp_core_admin_help_sub_menu_before', 'bp_core_admin_help_sub_menu_before_callback', 10, 5 );
						?>
                    </ul>

					<?php
					echo bp_core_admin_help_display_content( $docs_path, $vendor_path );
					?>
                </div>

            </div>
			<?php
		}
	}
}

/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyBoss 1.0.0
 */
function bp_core_admin_help() {
	$base_path   = buddypress()->plugin_dir . 'bp-help';
	$docs_path   = $base_path . '/docs';
	$vendor_path = $base_path . '/vendors';

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
           target="_blank"><?php _e( 'Online Docs', 'buddyboss' ); ?></a>
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
		printf( '<li class="selected main"><a href="%s" class="dir">%s</a></li>', $url, __( 'Help', 'buddyboss' ) );
	}
}