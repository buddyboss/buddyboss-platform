<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix  = $wpdb->prefix;
$page_url = admin_url( 'admin.php?page=buddyboss-crm-tags' );
$action  = sanitize_text_field( $_POST['action'] ?? $_GET['action'] ?? 'list' );
$tag_id  = absint( $_GET['id'] ?? $_POST['tag_id'] ?? 0 );
$notice  = '';
$error   = '';

// Handle delete.
if ( 'delete' === $action && $tag_id ) {
    check_admin_referer( 'delete-tag-' . $tag_id );
    $wpdb->delete( $prefix . 'bb_tags', array( 'id' => $tag_id ), array( '%d' ) );
    $wpdb->delete( $prefix . 'bb_user_tags', array( 'tag_id' => $tag_id ), array( '%d' ) );
    wp_cache_delete( 'all_tags', 'bb_crm' );
    $action = 'list';
    $notice = 'deleted';
}

// Handle save (create or update).
if ( 'save' === $action && isset( $_POST['tag_name'] ) ) {
    check_admin_referer( 'bb_crm_save_tag' );
    $edit_id = absint( $_POST['tag_id'] ?? 0 );

    if ( $edit_id ) {
        // Update existing — direct DB update to avoid slug uniqueness check on same record.
        $wpdb->update(
            $prefix . 'bb_tags',
            array(
                'name'        => sanitize_text_field( $_POST['tag_name'] ),
                'slug'        => sanitize_title( $_POST['tag_name'] ),
                'color'       => sanitize_hex_color( $_POST['tag_color'] ?? '#0073aa' ),
                'description' => sanitize_textarea_field( $_POST['tag_description'] ?? '' ),
                'category_id' => absint( $_POST['tag_category'] ?? 0 ),
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'id' => $edit_id )
        );
        wp_cache_delete( 'all_tags', 'bb_crm' );
        $notice = 'updated';
    } else {
        // Create new.
        $result = bb_crm_create_tag( array(
            'name'        => sanitize_text_field( $_POST['tag_name'] ),
            'color'       => sanitize_hex_color( $_POST['tag_color'] ?? '#0073aa' ),
            'description' => sanitize_textarea_field( $_POST['tag_description'] ?? '' ),
            'category_id' => absint( $_POST['tag_category'] ?? 0 ),
        ) );
        if ( is_wp_error( $result ) ) {
            $error = $result->get_error_message();
            $action = 'add'; // Stay on form.
        } else {
            $notice = 'created';
        }
    }
    if ( ! $error ) $action = 'list';
}

// Load for editing.
$editing = null;
if ( 'edit' === $action && $tag_id ) {
    $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}bb_tags WHERE id = %d", $tag_id ) );
}

$filter_cat = absint( $_GET['cat'] ?? 0 );
$search     = sanitize_text_field( $_GET['s'] ?? '' );
$categories = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tag_categories ORDER BY name ASC" );

// Build filtered tag query.
$where  = array( '1=1' );
$params = array();
if ( $filter_cat ) {
    $where[]  = 'category_id = %d';
    $params[] = $filter_cat;
}
if ( $search ) {
    $where[]  = '(name LIKE %s OR description LIKE %s)';
    $params[] = '%' . $wpdb->esc_like( $search ) . '%';
    $params[] = '%' . $wpdb->esc_like( $search ) . '%';
}
$sql  = 'SELECT * FROM ' . $prefix . 'bb_tags WHERE ' . implode( ' AND ', $where ) . ' ORDER BY name ASC';
$tags = $params ? $wpdb->get_results( $wpdb->prepare( $sql, $params ) ) : $wpdb->get_results( $sql );
?>

<div class="wrap bb-crm-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Tags', 'buddyboss-crm' ); ?></h1>
    <?php if ( 'list' === $action ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="page-title-action">
            <?php esc_html_e( '+ Add New Tag', 'buddyboss-crm' ); ?>
        </a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php
            $msgs = array( 'created' => 'Tag created.', 'updated' => 'Tag updated.', 'deleted' => 'Tag deleted.' );
            echo esc_html( $msgs[ $notice ] ?? '' );
        ?></p></div>
    <?php endif; ?>
    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <?php if ( in_array( $action, array( 'add', 'edit' ), true ) ) :
        $t = $editing; ?>
        <div class="postbox" style="max-width:640px">
            <div class="postbox-header">
                <h2><?php echo $t ? esc_html__( 'Edit Tag', 'buddyboss-crm' ) : esc_html__( 'Add New Tag', 'buddyboss-crm' ); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="<?php echo esc_url( $page_url ); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="tag_id" value="<?php echo absint( $t->id ?? 0 ); ?>">
                    <?php wp_nonce_field( 'bb_crm_save_tag' ); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="tag_name"><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?> *</label></th>
                            <td><input type="text" id="tag_name" name="tag_name" value="<?php echo esc_attr( $t->name ?? '' ); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="tag_color"><?php esc_html_e( 'Color', 'buddyboss-crm' ); ?></label></th>
                            <td><input type="color" id="tag_color" name="tag_color" value="<?php echo esc_attr( $t->color ?? '#0073aa' ); ?>"></td>
                        </tr>
                        <tr>
                            <th><label for="tag_description"><?php esc_html_e( 'Description', 'buddyboss-crm' ); ?></label></th>
                            <td><textarea id="tag_description" name="tag_description" rows="3" class="large-text"><?php echo esc_textarea( $t->description ?? '' ); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="tag_category"><?php esc_html_e( 'Category', 'buddyboss-crm' ); ?></label></th>
                            <td>
                                <?php if ( ! empty( $categories ) ) : ?>
                                    <select id="tag_category" name="tag_category">
                                        <option value="0"><?php esc_html_e( '— None —', 'buddyboss-crm' ); ?></option>
                                        <?php foreach ( $categories as $cat ) : ?>
                                            <option value="<?php echo absint( $cat->id ); ?>" <?php selected( $t->category_id ?? 0, $cat->id ); ?>>
                                                <?php echo esc_html( $cat->name ); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else : ?>
                                    <span style="color:#888;font-size:13px">
                                        <?php esc_html_e( 'No categories yet.', 'buddyboss-crm' ); ?>
                                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=buddyboss-crm-categories&action=add' ) ); ?>">
                                            <?php esc_html_e( 'Create one first', 'buddyboss-crm' ); ?>
                                        </a>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Tag', 'buddyboss-crm' ); ?></button>
                        <a href="<?php echo esc_url( $page_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'buddyboss-crm' ); ?></a>
                    </p>
                </form>
            </div>
        </div>

    <?php else : ?>

        <form method="get" action="<?php echo esc_url( $page_url ); ?>" style="margin-bottom:8px;display:flex;gap:6px;align-items:center">
            <input type="hidden" name="page" value="buddyboss-crm-tags">
            <?php if ( $filter_cat ) : ?>
                <input type="hidden" name="cat" value="<?php echo absint( $filter_cat ); ?>">
            <?php endif; ?>
            <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search tags…', 'buddyboss-crm' ); ?>" class="regular-text">
            <button type="submit" class="button"><?php esc_html_e( 'Search', 'buddyboss-crm' ); ?></button>
            <?php if ( $search ) : ?>
                <a href="<?php echo esc_url( $filter_cat ? add_query_arg( 'cat', $filter_cat, $page_url ) : $page_url ); ?>" class="button"><?php esc_html_e( 'Clear', 'buddyboss-crm' ); ?></a>
            <?php endif; ?>
        </form>

        <?php if ( ! empty( $categories ) ) : ?>
            <ul class="subsubsub" style="margin-bottom:8px">
                <li>
                    <a href="<?php echo esc_url( $page_url ); ?>" <?php echo ! $filter_cat ? 'class="current" aria-current="page"' : ''; ?>>
                        <?php esc_html_e( 'All', 'buddyboss-crm' ); ?>
                    </a>
                </li>
                <?php foreach ( $categories as $cat ) : ?>
                    <li> | <a href="<?php echo esc_url( add_query_arg( 'cat', $cat->id, $page_url ) ); ?>" <?php echo $filter_cat === (int) $cat->id ? 'class="current" aria-current="page"' : ''; ?>>
                        <?php echo esc_html( $cat->name ); ?>
                    </a></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ( empty( $tags ) ) : ?>
            <div class="bb-crm-empty-state">
                <span class="dashicons dashicons-tag"></span>
                <h3><?php esc_html_e( 'No tags yet', 'buddyboss-crm' ); ?></h3>
                <p><?php esc_html_e( 'Create your first tag to start organising your community members.', 'buddyboss-crm' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Create Your First Tag', 'buddyboss-crm' ); ?>
                </a>
            </div>
        <?php else :
            // Build a lookup for category names.
            $cat_names = array();
            foreach ( $categories as $cat ) {
                $cat_names[ $cat->id ] = $cat->name;
            }
        ?>
            <table class="wp-list-table widefat fixed striped bb-crm-tags-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?></th>
                        <th style="width:100px"><?php esc_html_e( 'Color', 'buddyboss-crm' ); ?></th>
                        <th><?php esc_html_e( 'Category', 'buddyboss-crm' ); ?></th>
                        <th style="width:70px"><?php esc_html_e( 'Users', 'buddyboss-crm' ); ?></th>
                        <th style="width:120px"><?php esc_html_e( 'Actions', 'buddyboss-crm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $tags as $tag ) : ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html( $tag->name ); ?></strong>
                            <?php if ( $tag->description ) : ?>
                                <br><small style="color:#888"><?php echo esc_html( wp_trim_words( $tag->description, 8, '…' ) ); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="display:inline-block;background:<?php echo esc_attr( $tag->color ); ?>;color:#fff;border-radius:10px;padding:2px 10px;font-size:11px;font-weight:600;white-space:nowrap;">
                                <?php echo esc_html( $tag->name ); ?>
                            </span>
                        </td>
                        <td><?php echo $tag->category_id && isset( $cat_names[ $tag->category_id ] ) ? esc_html( $cat_names[ $tag->category_id ] ) : '—'; ?></td>
                        <td><?php echo number_format( bb_crm_count_tag_users( $tag->id ) ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'id' => $tag->id ), $page_url ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit', 'buddyboss-crm' ); ?>
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'id' => $tag->id ), $page_url ), 'delete-tag-' . $tag->id ) ); ?>"
                                class="button button-small button-link-delete"
                                onclick="return confirm('<?php esc_attr_e( 'Delete this tag? It will be removed from all users.', 'buddyboss-crm' ); ?>')">
                                <?php esc_html_e( 'Delete', 'buddyboss-crm' ); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
