<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix = $wpdb->prefix;

// Handle form submissions inline (add/edit/delete via GET nonce for simplicity).
$action = sanitize_text_field( $_POST['action'] ?? $_GET['action'] ?? 'list' );
$cat_id = absint( $_GET['cat_id'] ?? 0 );
$notice = '';

if ( 'delete' === $action && $cat_id ) {
    check_admin_referer( 'delete-category-' . $cat_id );
    $wpdb->delete( $prefix . 'bb_tag_categories', array( 'id' => $cat_id ), array( '%d' ) );
    // Move orphaned tags to uncategorised.
    $wpdb->update( $prefix . 'bb_tags', array( 'category_id' => 0 ), array( 'category_id' => $cat_id ), array( '%d' ), array( '%d' ) );
    $action = 'list';
    $notice = 'deleted';
}

if ( 'save' === $action && isset( $_POST['cat_name'] ) ) {
    check_admin_referer( 'bb_crm_save_category' );
    $edit_id = absint( $_POST['cat_id'] ?? 0 );
    $data = array(
        'name'        => sanitize_text_field( $_POST['cat_name'] ),
        'slug'        => sanitize_title( $_POST['cat_name'] ),
        'description' => sanitize_textarea_field( $_POST['cat_description'] ?? '' ),
        'parent_id'   => absint( $_POST['cat_parent'] ?? 0 ),
    );
    if ( $edit_id ) {
        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->update( $prefix . 'bb_tag_categories', $data, array( 'id' => $edit_id ) );
        $notice = 'updated';
    } else {
        $data['created_at'] = current_time( 'mysql' );
        $data['updated_at'] = current_time( 'mysql' );
        $wpdb->insert( $prefix . 'bb_tag_categories', $data );
        $notice = 'created';
    }
    $action = 'list';
}

// Load data for edit.
$editing = null;
if ( 'edit' === $action && $cat_id ) {
    $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}bb_tag_categories WHERE id = %d", $cat_id ) );
}

$categories  = $wpdb->get_results( "SELECT c.*, COUNT(t.id) AS tag_count FROM {$prefix}bb_tag_categories c LEFT JOIN {$prefix}bb_tags t ON t.category_id = c.id GROUP BY c.id ORDER BY c.name ASC" );
$page_url    = admin_url( 'admin.php?page=buddyboss-crm-categories' );
?>

<div class="wrap bb-crm-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Tag Categories', 'buddyboss-crm' ); ?></h1>
    <?php if ( 'list' === $action ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="page-title-action">
            <?php esc_html_e( '+ Add Category', 'buddyboss-crm' ); ?>
        </a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php
            $msgs = array( 'created' => 'Category created.', 'updated' => 'Category updated.', 'deleted' => 'Category deleted.' );
            echo esc_html( $msgs[ $notice ] ?? '' );
        ?></p></div>
    <?php endif; ?>

    <?php if ( in_array( $action, array( 'add', 'edit' ), true ) ) :
        $cat = $editing; ?>
        <div class="postbox" style="max-width:600px">
            <div class="postbox-header">
                <h2><?php echo $cat ? esc_html__( 'Edit Category', 'buddyboss-crm' ) : esc_html__( 'Add Category', 'buddyboss-crm' ); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="<?php echo esc_url( $page_url ); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="cat_id" value="<?php echo absint( $cat->id ?? 0 ); ?>">
                    <?php wp_nonce_field( 'bb_crm_save_category' ); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="cat_name"><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?> *</label></th>
                            <td><input type="text" id="cat_name" name="cat_name" value="<?php echo esc_attr( $cat->name ?? '' ); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="cat_description"><?php esc_html_e( 'Description', 'buddyboss-crm' ); ?></label></th>
                            <td><textarea id="cat_description" name="cat_description" rows="3" class="large-text"><?php echo esc_textarea( $cat->description ?? '' ); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><label for="cat_parent"><?php esc_html_e( 'Parent Category', 'buddyboss-crm' ); ?></label></th>
                            <td>
                                <select id="cat_parent" name="cat_parent">
                                    <option value="0"><?php esc_html_e( '— None —', 'buddyboss-crm' ); ?></option>
                                    <?php foreach ( $categories as $c ) :
                                        if ( $cat && $c->id === $cat->id ) continue; ?>
                                        <option value="<?php echo absint( $c->id ); ?>" <?php selected( $cat->parent_id ?? 0, $c->id ); ?>>
                                            <?php echo esc_html( $c->name ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save Category', 'buddyboss-crm' ); ?></button>
                        <a href="<?php echo esc_url( $page_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'buddyboss-crm' ); ?></a>
                    </p>
                </form>
            </div>
        </div>

    <?php else : ?>

        <?php if ( empty( $categories ) ) : ?>
            <div class="bb-crm-empty-state">
                <span class="dashicons dashicons-category"></span>
                <h3><?php esc_html_e( 'No categories yet', 'buddyboss-crm' ); ?></h3>
                <p><?php esc_html_e( 'Create categories to group related tags together.', 'buddyboss-crm' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Add Your First Category', 'buddyboss-crm' ); ?>
                </a>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'buddyboss-crm' ); ?></th>
                        <th><?php esc_html_e( 'Tags', 'buddyboss-crm' ); ?></th>
                        <th><?php esc_html_e( 'Actions', 'buddyboss-crm' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $categories as $cat ) : ?>
                    <tr>
                        <td><strong><?php echo esc_html( $cat->name ); ?></strong><br><small><?php echo esc_html( $cat->slug ); ?></small></td>
                        <td><?php echo esc_html( wp_trim_words( $cat->description, 12, '…' ) ?: '—' ); ?></td>
                        <td><?php echo number_format( $cat->tag_count ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( add_query_arg( array( 'action' => 'edit', 'cat_id' => $cat->id ), $page_url ) ); ?>" class="button button-small">
                                <?php esc_html_e( 'Edit', 'buddyboss-crm' ); ?>
                            </a>
                            <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array( 'action' => 'delete', 'cat_id' => $cat->id ), $page_url ), 'delete-category-' . $cat->id ) ); ?>"
                                class="button button-small button-link-delete"
                                onclick="return confirm('<?php esc_attr_e( 'Delete this category? Tags in it will become uncategorised.', 'buddyboss-crm' ); ?>')">
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
