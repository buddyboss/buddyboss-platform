<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix   = $wpdb->prefix;
$page_url = admin_url( 'admin.php?page=buddyboss-crm-import' );
$notice   = '';
$errors   = array();
$imported = 0;

if ( isset( $_POST['bb_crm_import'] ) ) {
    check_admin_referer( 'bb_crm_import' );

    $assign_tag_id  = absint( $_POST['import_tag'] ?? 0 );
    $assign_list_id = absint( $_POST['import_list'] ?? 0 );
    $import_type    = sanitize_text_field( $_POST['import_type'] ?? 'email' );

    if ( empty( $_FILES['import_csv']['tmp_name'] ) ) {
        $errors[] = 'No file uploaded.';
    } else {
        $file = $_FILES['import_csv']['tmp_name'];
        $handle = fopen( $file, 'r' );
        if ( ! $handle ) {
            $errors[] = 'Could not read uploaded file.';
        } else {
            $header = fgetcsv( $handle ); // skip/read header row
            $row_num = 0;

            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $row_num++;
                if ( empty( $row[0] ) ) continue;
                $identifier = sanitize_text_field( trim( $row[0] ) );

                // Find WP user.
                if ( 'email' === $import_type ) {
                    $user = get_user_by( 'email', $identifier );
                } else {
                    $user = get_user_by( 'login', $identifier );
                }

                if ( ! $user ) {
                    $errors[] = "Row {$row_num}: User not found — " . esc_html( $identifier );
                    continue;
                }

                // Assign tag.
                if ( $assign_tag_id ) {
                    $exists = $wpdb->get_var( $wpdb->prepare(
                        "SELECT id FROM {$prefix}bb_user_tags WHERE user_id = %d AND tag_id = %d",
                        $user->ID, $assign_tag_id
                    ) );
                    if ( ! $exists ) {
                        $wpdb->insert( $prefix . 'bb_user_tags', array(
                            'user_id'    => $user->ID,
                            'tag_id'     => $assign_tag_id,
                            'applied_by' => get_current_user_id(),
                            'applied_at' => current_time( 'mysql' ),
                            'source'     => 'import',
                        ) );
                    }
                }

                // Add to list.
                if ( $assign_list_id ) {
                    $wpdb->replace( $prefix . 'bb_user_list_assignments', array(
                        'list_id'     => $assign_list_id,
                        'user_id'     => $user->ID,
                        'assigned_at' => current_time( 'mysql' ),
                    ) );
                }

                $imported++;
            }
            fclose( $handle );
            $notice = 'imported';
        }
    }
}

$all_tags  = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tags ORDER BY name ASC" );
$all_lists = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_user_lists WHERE list_type = 'static' ORDER BY name ASC" );
?>

<div class="wrap bb-crm-wrap">
    <h1><?php esc_html_e( 'Import Users', 'buddyboss-crm' ); ?></h1>
    <hr class="wp-header-end">

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p>
            <?php printf( esc_html__( 'Import complete. %d user(s) processed.', 'buddyboss-crm' ), $imported ); ?>
        </p></div>
    <?php endif; ?>
    <?php foreach ( $errors as $err ) : ?>
        <div class="notice notice-warning is-dismissible"><p><?php echo esc_html( $err ); ?></p></div>
    <?php endforeach; ?>

    <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap">

        <div class="postbox" style="max-width:560px;flex:1">
            <div class="postbox-header"><h2><?php esc_html_e( 'Upload CSV', 'buddyboss-crm' ); ?></h2></div>
            <div class="inside">
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url( $page_url ); ?>">
                    <?php wp_nonce_field( 'bb_crm_import' ); ?>
                    <input type="hidden" name="bb_crm_import" value="1">
                    <table class="form-table">
                        <tr>
                            <th><label for="import_csv"><?php esc_html_e( 'CSV File', 'buddyboss-crm' ); ?> *</label></th>
                            <td>
                                <input type="file" id="import_csv" name="import_csv" accept=".csv,text/csv" required>
                                <p class="description"><?php esc_html_e( 'One user per row. First column = identifier.', 'buddyboss-crm' ); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="import_type"><?php esc_html_e( 'Identify By', 'buddyboss-crm' ); ?></label></th>
                            <td>
                                <select id="import_type" name="import_type">
                                    <option value="email"><?php esc_html_e( 'Email address', 'buddyboss-crm' ); ?></option>
                                    <option value="login"><?php esc_html_e( 'Username', 'buddyboss-crm' ); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="import_tag"><?php esc_html_e( 'Assign Tag', 'buddyboss-crm' ); ?></label></th>
                            <td>
                                <select id="import_tag" name="import_tag">
                                    <option value="0"><?php esc_html_e( '— None —', 'buddyboss-crm' ); ?></option>
                                    <?php foreach ( $all_tags as $t ) : ?>
                                        <option value="<?php echo absint( $t->id ); ?>"><?php echo esc_html( $t->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><label for="import_list"><?php esc_html_e( 'Add to List', 'buddyboss-crm' ); ?></label></th>
                            <td>
                                <select id="import_list" name="import_list">
                                    <option value="0"><?php esc_html_e( '— None —', 'buddyboss-crm' ); ?></option>
                                    <?php foreach ( $all_lists as $l ) : ?>
                                        <option value="<?php echo absint( $l->id ); ?>"><?php echo esc_html( $l->name ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php esc_html_e( 'Only static lists shown.', 'buddyboss-crm' ); ?></p>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Import', 'buddyboss-crm' ); ?></button>
                    </p>
                </form>
            </div>
        </div>

        <div class="postbox" style="max-width:300px">
            <div class="postbox-header"><h2><?php esc_html_e( 'CSV Format', 'buddyboss-crm' ); ?></h2></div>
            <div class="inside">
                <p style="font-size:12px;color:#555"><?php esc_html_e( 'Your CSV should have one column. An optional header row is skipped automatically.', 'buddyboss-crm' ); ?></p>
                <pre style="background:#f6f7f7;padding:10px;font-size:11px;border-radius:4px">email
john@example.com
jane@example.com
alice@example.com</pre>
                <p style="font-size:12px;color:#555"><?php esc_html_e( 'Or by username:', 'buddyboss-crm' ); ?></p>
                <pre style="background:#f6f7f7;padding:10px;font-size:11px;border-radius:4px">username
johndoe
janedoe</pre>
            </div>
        </div>

    </div>
</div>
