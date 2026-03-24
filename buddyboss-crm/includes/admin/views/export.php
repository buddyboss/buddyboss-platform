<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix = $wpdb->prefix;

// Handle CSV download.
if ( isset( $_POST['bb_crm_export'] ) ) {
    check_admin_referer( 'bb_crm_export' );

    $filter_type = sanitize_text_field( $_POST['export_filter'] ?? 'all' );
    $filter_id   = absint( $_POST['export_filter_id'] ?? 0 );
    $columns     = array_map( 'sanitize_text_field', (array) ( $_POST['export_columns'] ?? array( 'id', 'email', 'display_name', 'tags' ) ) );

    // Build user list.
    if ( 'tag' === $filter_type && $filter_id ) {
        $user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$prefix}bb_user_tags WHERE tag_id = %d", $filter_id
        ) );
    } elseif ( 'list' === $filter_type && $filter_id ) {
        $user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT user_id FROM {$prefix}bb_user_list_assignments WHERE list_id = %d", $filter_id
        ) );
    } else {
        $user_ids = get_users( array( 'fields' => 'ID', 'number' => -1 ) );
    }

    if ( ! empty( $user_ids ) ) {
        // Pre-load tags for all users.
        $in  = implode( ',', array_map( 'absint', $user_ids ) );
        $tag_rows = $wpdb->get_results( "SELECT ut.user_id, t.name FROM {$prefix}bb_user_tags ut JOIN {$prefix}bb_tags t ON t.id = ut.tag_id WHERE ut.user_id IN ($in) ORDER BY t.name ASC" );
        $user_tag_names = array();
        foreach ( $tag_rows as $tr ) {
            $user_tag_names[ $tr->user_id ][] = $tr->name;
        }

        $users = get_users( array( 'include' => $user_ids, 'number' => -1 ) );

        // Output CSV.
        $filename = 'buddyboss-crm-export-' . date( 'Y-m-d' ) . '.csv';
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Pragma: no-cache' );
        $out = fopen( 'php://output', 'w' );

        // Header row.
        $header_map = array( 'id' => 'ID', 'email' => 'Email', 'display_name' => 'Display Name', 'username' => 'Username', 'first_name' => 'First Name', 'last_name' => 'Last Name', 'registered' => 'Registered', 'tags' => 'Tags', 'lists' => 'Lists' );
        $header = array();
        foreach ( $columns as $col ) {
            $header[] = $header_map[ $col ] ?? $col;
        }
        fputcsv( $out, $header );

        // Pre-load list memberships if needed.
        $user_list_names = array();
        if ( in_array( 'lists', $columns, true ) ) {
            $list_rows = $wpdb->get_results( "SELECT a.user_id, l.name FROM {$prefix}bb_user_list_assignments a JOIN {$prefix}bb_user_lists l ON l.id = a.list_id WHERE a.user_id IN ($in) ORDER BY l.name ASC" );
            foreach ( $list_rows as $lr ) {
                $user_list_names[ $lr->user_id ][] = $lr->name;
            }
        }

        foreach ( $users as $user ) {
            $row = array();
            foreach ( $columns as $col ) {
                switch ( $col ) {
                    case 'id':         $row[] = $user->ID; break;
                    case 'email':      $row[] = $user->user_email; break;
                    case 'display_name': $row[] = $user->display_name; break;
                    case 'username':   $row[] = $user->user_login; break;
                    case 'first_name': $row[] = get_user_meta( $user->ID, 'first_name', true ); break;
                    case 'last_name':  $row[] = get_user_meta( $user->ID, 'last_name', true ); break;
                    case 'registered': $row[] = $user->user_registered; break;
                    case 'tags':       $row[] = implode( ', ', $user_tag_names[ $user->ID ] ?? array() ); break;
                    case 'lists':      $row[] = implode( ', ', $user_list_names[ $user->ID ] ?? array() ); break;
                    default:           $row[] = ''; break;
                }
            }
            fputcsv( $out, $row );
        }
        fclose( $out );
        exit;
    }
}

$all_tags  = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tags ORDER BY name ASC" );
$all_lists = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_user_lists ORDER BY name ASC" );
$total_users = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
?>

<div class="wrap bb-crm-wrap">
    <h1><?php esc_html_e( 'Export Users', 'buddyboss-crm' ); ?></h1>
    <hr class="wp-header-end">

    <div class="postbox" style="max-width:580px">
        <div class="postbox-header"><h2><?php esc_html_e( 'Create Export', 'buddyboss-crm' ); ?></h2></div>
        <div class="inside">
            <form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-export' ) ); ?>">
                <?php wp_nonce_field( 'bb_crm_export' ); ?>
                <input type="hidden" name="bb_crm_export" value="1">
                <table class="form-table">
                    <tr>
                        <th><label><?php esc_html_e( 'Filter By', 'buddyboss-crm' ); ?></label></th>
                        <td>
                            <select name="export_filter" id="export_filter" onchange="document.getElementById('export-filter-id-row').style.display=this.value==='all'?'none':''">
                                <option value="all"><?php printf( esc_html__( 'All users (%s)', 'buddyboss-crm' ), number_format( $total_users ) ); ?></option>
                                <option value="tag"><?php esc_html_e( 'Users with a specific tag', 'buddyboss-crm' ); ?></option>
                                <option value="list"><?php esc_html_e( 'Members of a specific list', 'buddyboss-crm' ); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="export-filter-id-row" style="display:none">
                        <th><label for="export_filter_id"><?php esc_html_e( 'Tag / List', 'buddyboss-crm' ); ?></label></th>
                        <td>
                            <select name="export_filter_id" id="export_filter_id">
                                <optgroup label="<?php esc_attr_e( 'Tags', 'buddyboss-crm' ); ?>">
                                    <?php foreach ( $all_tags as $t ) : ?>
                                        <option value="<?php echo absint( $t->id ); ?>" data-type="tag"><?php echo esc_html( $t->name ); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                                <optgroup label="<?php esc_attr_e( 'Lists', 'buddyboss-crm' ); ?>">
                                    <?php foreach ( $all_lists as $l ) : ?>
                                        <option value="<?php echo absint( $l->id ); ?>" data-type="list"><?php echo esc_html( $l->name ); ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e( 'Columns', 'buddyboss-crm' ); ?></th>
                        <td>
                            <?php
                            $col_options = array(
                                'id'           => 'User ID',
                                'email'        => 'Email',
                                'display_name' => 'Display Name',
                                'username'     => 'Username',
                                'first_name'   => 'First Name',
                                'last_name'    => 'Last Name',
                                'registered'   => 'Registration Date',
                                'tags'         => 'Tags',
                                'lists'        => 'Lists',
                            );
                            $defaults = array( 'id', 'email', 'display_name', 'tags' );
                            foreach ( $col_options as $val => $label ) : ?>
                                <label style="display:block;margin-bottom:4px">
                                    <input type="checkbox" name="export_columns[]" value="<?php echo esc_attr( $val ); ?>" <?php checked( in_array( $val, $defaults, true ) ); ?>>
                                    <?php echo esc_html( $label ); ?>
                                </label>
                            <?php endforeach; ?>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="submit" class="button button-primary button-large">
                        <span class="dashicons dashicons-download" style="margin-top:3px"></span>
                        <?php esc_html_e( 'Download CSV', 'buddyboss-crm' ); ?>
                    </button>
                </p>
            </form>
        </div>
    </div>

    <script>
    document.getElementById('export_filter').addEventListener('change', function(){
        document.getElementById('export-filter-id-row').style.display = this.value === 'all' ? 'none' : '';
    });
    </script>
</div>
