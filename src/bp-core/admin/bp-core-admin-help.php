<?php
/**
 * BuddyBoss Help panel.
 *
 * @package BuddyBoss\Core
 * @since BuddyPress 2.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_help_main_menu( $main_directories, $docs_path ) {
    foreach ( $main_directories  as $sub_directories ) {
        $index_file = glob($sub_directories . "/0-*.md");
        $directories = array_diff( glob($sub_directories . "/*"), $index_file );

        // converting array into string.
        $index_file = current( $index_file );
        ?>
        <div class="bp-help-card bb-help-content-wrap">
            <?php
            // print the title of the section
            printf( '<h3><a href="#">%s</a></h3>', fgets( fopen( $index_file, 'r' ) ) );
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
* @param $directories
* @param $times
* @param $docs_path
 */
function bp_core_admin_help_sub_menu( $directories, $times, $docs_path ) {
    $selected = ! empty( $_REQUEST['article'] ) ? $_REQUEST['article'] : "";
    ?>
    <ul class="loop-<?php echo $times; ?>">
        <?php

        // For showing the menu title
        foreach ( $directories as $directory ) {
            $selected = ( false !== strpos( $docs_path . $selected, $directory ) ) ? 'selected' : '';
            ?>
            <li class="<?php echo $selected;?>">
                <?php
                // check if it's has directory
                if ( is_dir( $directory ) ) {
                    // the the main file from the directory
                    $dir_index_file = glob($directory . "/0-*.md");
                    $loop_dir = array_diff( glob($directory . '/*' ) , $dir_index_file );

                    $dir_index_file = current( $dir_index_file );
                    $url = add_query_arg( 'article', str_replace($docs_path,"",$dir_index_file) );

                    if ( ! empty( $loop_dir ) ) {
                        printf( '<a href="%s" class="dir">%s (%s)</a>', $url, fgets( fopen( $dir_index_file, 'r' ) ), count( $loop_dir ) );
                        $times++;
                        bp_core_admin_help_sub_menu( $loop_dir, $times, $docs_path );
                    } else {
                        printf( '<a href="%s" class="dir">%s</a>', $url, fgets( fopen( $dir_index_file, 'r' ) ) );
                    }
                } else {
                    $url = add_query_arg( 'article', str_replace($docs_path,"",$directory) );
                    // print the title if it's a .md file
                    printf( '<a href="%s" class="file">%s</a>', $url, fgets( fopen( $directory, 'r' ) ) );
                } ?>
            </li><?php
        }
        ?>
    </ul>
    <?php
}

/**
 * Display Help Page content
 *
* @param $docs_path
* @param $vendor_path
 */
function bp_core_admin_help_display_content( $docs_path, $vendor_path ) {
    require_once $vendor_path . '/parsedown/Parsedown.php';
    $Parsedown = new Parsedown();
    $text      = file_get_contents( $docs_path . $_GET['article'] );
    ?>
    <div class="bb-help-content">
        <?php
         echo $Parsedown->text( $text );
        ?>
    </div>
    <?php
}

/**
 * Show the main index page of HELP page
 */
function bp_core_admin_help_main_page() {
    $base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs';
	$vendor_path = $base_path . '/vendors';

	$main_directories = glob($docs_path . '/*' , GLOB_ONLYDIR);

	if ( ! empty( $main_directories ) ) {
        if ( empty( $_REQUEST['article'] ) ) {
        } else {
        }
            bp_core_admin_help_main_menu( $main_directories, $docs_path );
            bp_core_admin_help_display_content( $docs_path, $vendor_path );
	}
}

/**
 * Render the BuddyBoss Help page.
 *
 * @since BuddyPress 2.0.0
 */
function bp_core_admin_help() {
	$base_path = buddypress()->plugin_dir . 'bp-help';
	$docs_path = $base_path . '/docs';
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
		    <a href="https://www.buddyboss.com/resources/docs/" class="page-title-action" target="_blank"><?php _e( 'Online Docs', 'buddyboss' ); ?></a>
		</h1>

		<!-- @mehul showing proper HTML output -->
        <div class="wp-list-table widefat bp-help-card-grid">
            <?php
            bp_core_admin_help_main_page();
            ?>
        </div>

        <div class="clear">
        <hr/>
	</div>
	<?php
}