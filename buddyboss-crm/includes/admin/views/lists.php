<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$prefix   = $wpdb->prefix;
$page_url = admin_url( 'admin.php?page=buddyboss-crm-lists' );

// ── Helper: sync dynamic list membership (defined first, used below) ─────────
function bb_crm_sync_dynamic_list( $list_id ) {
    global $wpdb;
    $prefix = $wpdb->prefix;
    $list   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}bb_user_lists WHERE id = %d", $list_id ) );
    if ( ! $list || 'dynamic' !== $list->list_type ) return;

    $conditions = json_decode( $list->conditions, true );
    if ( empty( $conditions ) ) return;

    $match_all = ( 'all' === $list->match_type );
    $matched   = null;

    foreach ( $conditions as $cond ) {
        $type  = $cond['type'] ?? '';
        $value = $cond['value'] ?? '';

        if ( 'tag' === $type ) {
            $ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT user_id FROM {$prefix}bb_user_tags WHERE tag_id = %d", absint( $value )
            ) );
        } elseif ( 'category' === $type ) {
            // Users who have any tag in this category.
            $tag_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT id FROM {$prefix}bb_tags WHERE category_id = %d", absint( $value )
            ) );
            if ( empty( $tag_ids ) ) {
                $ids = array();
            } else {
                $in  = implode( ',', array_map( 'absint', $tag_ids ) );
                $ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$prefix}bb_user_tags WHERE tag_id IN ($in)" );
            }
        } elseif ( 'role' === $type ) {
            $ids = get_users( array( 'role' => sanitize_text_field( $value ), 'fields' => 'ID', 'number' => -1 ) );
        } else {
            continue;
        }

        $ids = array_map( 'absint', (array) $ids );

        if ( $matched === null ) {
            $matched = $ids;
        } elseif ( $match_all ) {
            $matched = array_intersect( $matched, $ids );
        } else {
            $matched = array_unique( array_merge( $matched, $ids ) );
        }
    }

    if ( $matched === null ) return;

    $wpdb->delete( $prefix . 'bb_user_list_assignments', array( 'list_id' => $list_id ), array( '%d' ) );
    foreach ( $matched as $uid ) {
        $wpdb->insert( $prefix . 'bb_user_list_assignments', array(
            'list_id'     => $list_id,
            'user_id'     => absint( $uid ),
            'assigned_at' => current_time( 'mysql' ),
        ) );
    }
}

// ── Action handling ───────────────────────────────────────────────────────────
$action  = sanitize_text_field( $_POST['action'] ?? $_GET['action'] ?? 'list' );
$list_id = absint( $_GET['list_id'] ?? 0 );
$notice  = sanitize_key( $_GET['synced'] ?? '' ) === '1' ? 'synced' : '';

// Delete.
if ( 'delete' === $action && $list_id ) {
    check_admin_referer( 'delete-list-' . $list_id );
    $wpdb->delete( $prefix . 'bb_user_lists', array( 'id' => $list_id ), array( '%d' ) );
    $wpdb->delete( $prefix . 'bb_user_list_assignments', array( 'list_id' => $list_id ), array( '%d' ) );
    $action = 'list';
    $notice = 'deleted';
}

// Save.
if ( 'save' === $action && isset( $_POST['list_name'] ) ) {
    check_admin_referer( 'bb_crm_save_list' );
    $edit_id   = absint( $_POST['list_id'] ?? 0 );
    $list_type = in_array( $_POST['list_type'] ?? 'static', array( 'static', 'dynamic' ), true ) ? $_POST['list_type'] : 'static';

    $conditions = null;
    if ( 'dynamic' === $list_type && ! empty( $_POST['cond_type'] ) ) {
        $conds = array();
        foreach ( (array) $_POST['cond_type'] as $i => $ctype ) {
            if ( empty( $ctype ) ) continue;
            $conds[] = array(
                'type'  => sanitize_text_field( $ctype ),
                'value' => sanitize_text_field( $_POST['cond_value'][ $i ] ?? '' ),
            );
        }
        $conditions = wp_json_encode( $conds );
    }

    $data = array(
        'name'        => sanitize_text_field( $_POST['list_name'] ),
        'description' => sanitize_textarea_field( $_POST['list_description'] ?? '' ),
        'list_type'   => $list_type,
        'match_type'  => in_array( $_POST['match_type'] ?? 'all', array( 'any', 'all' ), true ) ? $_POST['match_type'] : 'all',
        'conditions'  => $conditions,
        'updated_at'  => current_time( 'mysql' ),
    );

    if ( $edit_id ) {
        $wpdb->update( $prefix . 'bb_user_lists', $data, array( 'id' => $edit_id ) );
        $saved_id = $edit_id;
        $notice   = 'updated';
    } else {
        $data['slug']       = sanitize_title( $_POST['list_name'] );
        $data['created_by'] = get_current_user_id();
        $data['created_at'] = current_time( 'mysql' );
        $wpdb->insert( $prefix . 'bb_user_lists', $data );
        $saved_id = $wpdb->insert_id;
        $notice   = 'created';
    }

    if ( 'dynamic' === $list_type ) {
        bb_crm_sync_dynamic_list( $saved_id );
    }

    $action = 'list';
}

// Re-sync dynamic list.
if ( 'resync' === $action && $list_id ) {
    check_admin_referer( 'resync-list-' . $list_id );
    bb_crm_sync_dynamic_list( $list_id );
    wp_safe_redirect( add_query_arg( array( 'action' => 'view', 'list_id' => $list_id, 'synced' => '1' ), $page_url ) );
    exit;
}

// Manual member add (static lists).
if ( 'add_member' === $action && isset( $_POST['list_id'], $_POST['user_id'] ) ) {
    check_admin_referer( 'bb_crm_list_member' );
    $lid = absint( $_POST['list_id'] );
    $uid = absint( $_POST['user_id'] );
    if ( $lid && $uid ) {
        $wpdb->replace( $prefix . 'bb_user_list_assignments', array(
            'list_id' => $lid, 'user_id' => $uid, 'assigned_at' => current_time( 'mysql' ),
        ) );
    }
    $action = 'view'; $list_id = $lid; $notice = 'member_added';
}

// Manual member remove (static lists).
if ( 'remove_member' === $action && isset( $_GET['list_id'], $_GET['user_id'] ) ) {
    check_admin_referer( 'remove-list-member-' . absint( $_GET['list_id'] ) . '-' . absint( $_GET['user_id'] ) );
    $wpdb->delete( $prefix . 'bb_user_list_assignments', array(
        'list_id' => absint( $_GET['list_id'] ),
        'user_id' => absint( $_GET['user_id'] ),
    ) );
    $action = 'view'; $list_id = absint( $_GET['list_id'] ); $notice = 'member_removed';
}

// Load for editing/viewing.
$editing = null;
if ( in_array( $action, array( 'edit', 'view' ), true ) && $list_id ) {
    $editing = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$prefix}bb_user_lists WHERE id = %d", $list_id ) );
}

$lists      = $wpdb->get_results( "SELECT l.*, COUNT(a.id) AS member_count FROM {$prefix}bb_user_lists l LEFT JOIN {$prefix}bb_user_list_assignments a ON a.list_id = l.id GROUP BY l.id ORDER BY l.name ASC" );
$all_tags   = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tags ORDER BY name ASC" );
$categories = $wpdb->get_results( "SELECT id, name FROM {$prefix}bb_tag_categories ORDER BY name ASC" );
$all_roles  = wp_roles()->get_names();
?>

<div class="wrap bb-crm-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'User Lists', 'buddyboss-crm' ); ?></h1>
    <?php if ( 'list' === $action ) : ?>
        <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="page-title-action">
            <?php esc_html_e( '+ Add List', 'buddyboss-crm' ); ?>
        </a>
    <?php endif; ?>
    <hr class="wp-header-end">

    <?php if ( $notice ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php
            $msgs = array( 'created' => 'List created.', 'updated' => 'List updated.', 'deleted' => 'List deleted.', 'member_added' => 'Member added.', 'member_removed' => 'Member removed.', 'synced' => 'List membership re-synced.' );
            echo esc_html( $msgs[ $notice ] ?? '' );
        ?></p></div>
    <?php endif; ?>

    <?php if ( in_array( $action, array( 'add', 'edit' ), true ) ) :
        $lst    = $editing;
        $ltype  = $lst->list_type ?? 'static';
        $conds  = $lst ? json_decode( $lst->conditions ?? '[]', true ) : array();
        if ( ! is_array( $conds ) ) $conds = array();
    ?>
        <div class="postbox" style="max-width:660px">
            <div class="postbox-header">
                <h2><?php echo $lst ? esc_html__( 'Edit List', 'buddyboss-crm' ) : esc_html__( 'Add List', 'buddyboss-crm' ); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="<?php echo esc_url( $page_url ); ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="list_id" value="<?php echo absint( $lst->id ?? 0 ); ?>">
                    <?php wp_nonce_field( 'bb_crm_save_list' ); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="list_name"><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?> *</label></th>
                            <td><input type="text" id="list_name" name="list_name" value="<?php echo esc_attr( $lst->name ?? '' ); ?>" class="regular-text" required></td>
                        </tr>
                        <tr>
                            <th><label for="list_description"><?php esc_html_e( 'Description', 'buddyboss-crm' ); ?></label></th>
                            <td><textarea id="list_description" name="list_description" rows="2" class="large-text"><?php echo esc_textarea( $lst->description ?? '' ); ?></textarea></td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e( 'List Type', 'buddyboss-crm' ); ?></th>
                            <td>
                                <label style="margin-right:20px">
                                    <input type="radio" name="list_type" value="static" <?php checked( $ltype, 'static' ); ?>>
                                    <strong><?php esc_html_e( 'Static', 'buddyboss-crm' ); ?></strong>
                                    <span style="color:#888;font-size:12px"> — <?php esc_html_e( 'Manually manage members', 'buddyboss-crm' ); ?></span>
                                </label>
                                <label>
                                    <input type="radio" name="list_type" value="dynamic" <?php checked( $ltype, 'dynamic' ); ?>>
                                    <strong><?php esc_html_e( 'Dynamic', 'buddyboss-crm' ); ?></strong>
                                    <span style="color:#888;font-size:12px"> — <?php esc_html_e( 'Auto-populate by conditions', 'buddyboss-crm' ); ?></span>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <div id="bb-crm-dynamic-panel" style="<?php echo 'dynamic' === $ltype ? '' : 'display:none'; ?>border-top:1px solid #e0e0e0;padding:16px 0 4px">
                        <p style="margin:0 0 12px;font-size:13px">
                            <?php esc_html_e( 'Include users who match', 'buddyboss-crm' ); ?>
                            <select name="match_type" style="margin:0 6px">
                                <option value="any" <?php selected( $lst->match_type ?? 'any', 'any' ); ?>><?php esc_html_e( 'ANY', 'buddyboss-crm' ); ?></option>
                                <option value="all" <?php selected( $lst->match_type ?? 'any', 'all' ); ?>><?php esc_html_e( 'ALL', 'buddyboss-crm' ); ?></option>
                            </select>
                            <?php esc_html_e( 'of the following conditions:', 'buddyboss-crm' ); ?>
                        </p>

                        <div id="bb-crm-conditions-wrap">
                            <?php foreach ( $conds as $cond ) :
                                $ctype = $cond['type'] ?? 'tag';
                                $cval  = $cond['value'] ?? ''; ?>
                                <div class="bb-crm-cond-row" style="display:flex;gap:8px;align-items:center;margin-bottom:8px">
                                    <select name="cond_type[]" class="bb-crm-cond-type" style="min-width:160px">
                                        <option value="tag"      <?php selected( $ctype, 'tag' ); ?>><?php esc_html_e( 'Has Tag',          'buddyboss-crm' ); ?></option>
                                        <option value="category" <?php selected( $ctype, 'category' ); ?>><?php esc_html_e( 'Has Tag Category', 'buddyboss-crm' ); ?></option>
                                        <option value="role"     <?php selected( $ctype, 'role' ); ?>><?php esc_html_e( 'User Role',         'buddyboss-crm' ); ?></option>
                                    </select>
                                    <div class="bb-crm-cond-value-wrap" style="flex:1">
                                        <?php if ( 'category' === $ctype ) : ?>
                                            <select name="cond_value[]" class="widefat">
                                                <option value=""><?php esc_html_e( '— Select category —', 'buddyboss-crm' ); ?></option>
                                                <?php foreach ( $categories as $cat ) : ?>
                                                    <option value="<?php echo absint( $cat->id ); ?>" <?php selected( $cval, $cat->id ); ?>><?php echo esc_html( $cat->name ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ( 'role' === $ctype ) : ?>
                                            <select name="cond_value[]" class="widefat">
                                                <option value=""><?php esc_html_e( '— Select role —', 'buddyboss-crm' ); ?></option>
                                                <?php foreach ( $all_roles as $rk => $rl ) : ?>
                                                    <option value="<?php echo esc_attr( $rk ); ?>" <?php selected( $cval, $rk ); ?>><?php echo esc_html( translate_user_role( $rl ) ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php else : ?>
                                            <select name="cond_value[]" class="widefat">
                                                <option value=""><?php esc_html_e( '— Select tag —', 'buddyboss-crm' ); ?></option>
                                                <?php foreach ( $all_tags as $t ) : ?>
                                                    <option value="<?php echo absint( $t->id ); ?>" <?php selected( $cval, $t->id ); ?>><?php echo esc_html( $t->name ); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php endif; ?>
                                    </div>
                                    <button type="button" class="button button-small bb-crm-remove-cond" title="Remove">&times;</button>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <button type="button" class="button" id="bb-crm-add-cond">+ <?php esc_html_e( 'Add Condition', 'buddyboss-crm' ); ?></button>
                        <span id="bb-crm-preview-count" style="display:none;margin-left:10px;padding:3px 10px;border-radius:12px;background:#f0f0f0;border:1px solid #e2e4e7;font-size:12px;font-weight:500;color:#1a1a1a;vertical-align:middle"></span>
                    </div>

                    <p style="margin-top:16px">
                        <button type="submit" class="button button-primary"><?php esc_html_e( 'Save List', 'buddyboss-crm' ); ?></button>
                        <a href="<?php echo esc_url( $page_url ); ?>" class="button"><?php esc_html_e( 'Cancel', 'buddyboss-crm' ); ?></a>
                    </p>
                </form>
            </div>
        </div>

        <script>
        (function(){
            // Tag/category/role select options encoded for JS.
            var tagOptions = <?php echo wp_json_encode( array_map( function($t){ return array('id'=>$t->id,'name'=>$t->name); }, $all_tags ) ); ?>;
            var catOptions = <?php echo wp_json_encode( array_map( function($c){ return array('id'=>$c->id,'name'=>$c->name); }, $categories ) ); ?>;
            var roleOptions = <?php
                $roles_js = array();
                foreach ( $all_roles as $rk => $rl ) $roles_js[] = array('id'=>$rk,'name'=>translate_user_role($rl));
                echo wp_json_encode( $roles_js );
            ?>;

            function buildSelect(name, opts, placeholder) {
                var sel = document.createElement('select');
                sel.name = name; sel.className = 'widefat';
                var blank = document.createElement('option'); blank.value=''; blank.textContent=placeholder; sel.appendChild(blank);
                opts.forEach(function(o){ var op=document.createElement('option'); op.value=o.id; op.textContent=o.name; sel.appendChild(op); });
                return sel;
            }

            function makeCondRow() {
                var row = document.createElement('div');
                row.className = 'bb-crm-cond-row';
                row.style.cssText = 'display:flex;gap:8px;align-items:center;margin-bottom:8px';

                var typeEl = document.createElement('select');
                typeEl.name = 'cond_type[]'; typeEl.className = 'bb-crm-cond-type'; typeEl.style.minWidth = '160px';
                [['tag','Has Tag'],['category','Has Tag Category'],['role','User Role']].forEach(function(pair){
                    var o=document.createElement('option'); o.value=pair[0]; o.textContent=pair[1]; typeEl.appendChild(o);
                });

                var valWrap = document.createElement('div');
                valWrap.className = 'bb-crm-cond-value-wrap'; valWrap.style.flex = '1';
                valWrap.appendChild(buildSelect('cond_value[]', tagOptions, '— Select tag —'));

                typeEl.addEventListener('change', function(){
                    valWrap.innerHTML = '';
                    if (this.value==='category') valWrap.appendChild(buildSelect('cond_value[]', catOptions, '— Select category —'));
                    else if (this.value==='role')  valWrap.appendChild(buildSelect('cond_value[]', roleOptions, '— Select role —'));
                    else                           valWrap.appendChild(buildSelect('cond_value[]', tagOptions, '— Select tag —'));
                    bbCrmRequestCount();
                });

                var removeBtn = document.createElement('button');
                removeBtn.type='button'; removeBtn.className='button button-small bb-crm-remove-cond'; removeBtn.textContent='×';
                removeBtn.addEventListener('click', function(){ row.remove(); });

                row.appendChild(typeEl); row.appendChild(valWrap); row.appendChild(removeBtn);
                return row;
            }

            // ── Live user count preview ────────────────────────────────────
            var countTimer   = null;
            var countEl      = document.getElementById('bb-crm-preview-count');
            var ajaxUrl      = (typeof bbCrmAdmin !== 'undefined') ? bbCrmAdmin.ajax_url : '';
            var ajaxNonce    = (typeof bbCrmAdmin !== 'undefined') ? bbCrmAdmin.nonce   : '';

            function bbCrmRequestCount() {
                clearTimeout(countTimer);
                var panel = document.getElementById('bb-crm-dynamic-panel');
                if (!panel || panel.style.display === 'none') { countEl.style.display = 'none'; return; }

                var types  = Array.from(document.querySelectorAll('.bb-crm-cond-type')).map(function(s){ return s.value; });
                var values = Array.from(document.querySelectorAll('.bb-crm-cond-value-wrap select')).map(function(s){ return s.value; });
                var hasValid = types.some(function(t, i){ return t && values[i]; });

                if (!hasValid) { countEl.style.display = 'none'; return; }

                countEl.textContent = 'Counting\u2026';
                countEl.style.display = 'inline';

                countTimer = setTimeout(function() {
                    var match = document.querySelector('[name="match_type"]').value;
                    var body  = new FormData();
                    body.append('action',     'bb_crm_list_preview_count');
                    body.append('nonce',      ajaxNonce);
                    body.append('match_type', match);
                    types.forEach(function(t)  { body.append('cond_type[]',  t); });
                    values.forEach(function(v) { body.append('cond_value[]', v); });

                    fetch(ajaxUrl, { method: 'POST', body: body })
                        .then(function(r){ return r.json(); })
                        .then(function(res) {
                            if (res.success) {
                                var n = parseInt(res.data.count, 10);
                                countEl.textContent = n.toLocaleString() + ' user' + (n === 1 ? '' : 's') + ' match';
                                countEl.style.display = 'inline';
                            } else {
                                countEl.style.display = 'none';
                            }
                        })
                        .catch(function(){ countEl.style.display = 'none'; });
                }, 400);
            }

            // Toggle dynamic panel.
            document.querySelectorAll('input[name="list_type"]').forEach(function(r){
                r.addEventListener('change', function(){
                    document.getElementById('bb-crm-dynamic-panel').style.display = this.value==='dynamic' ? '' : 'none';
                    bbCrmRequestCount();
                });
            });

            // match_type change.
            var matchSel = document.querySelector('[name="match_type"]');
            if (matchSel) matchSel.addEventListener('change', bbCrmRequestCount);

            // Delegate condition type/value changes to the panel.
            document.getElementById('bb-crm-dynamic-panel').addEventListener('change', function(e){
                if (e.target.classList.contains('bb-crm-cond-type') || e.target.closest('.bb-crm-cond-value-wrap')) {
                    bbCrmRequestCount();
                }
            });

            // Add condition.
            document.getElementById('bb-crm-add-cond').addEventListener('click', function(){
                document.getElementById('bb-crm-conditions-wrap').appendChild(makeCondRow());
                bbCrmRequestCount();
            });

            // Remove existing condition rows.
            document.getElementById('bb-crm-conditions-wrap').addEventListener('click', function(e){
                if (e.target.classList.contains('bb-crm-remove-cond')) {
                    e.target.closest('.bb-crm-cond-row').remove();
                    bbCrmRequestCount();
                }
            });

            // Run on page load if editing an existing dynamic list.
            bbCrmRequestCount();
        })();
        </script>

    <?php elseif ( 'view' === $action && $editing ) :
        $lst     = $editing;
        $members = $wpdb->get_results( $wpdb->prepare(
            "SELECT a.user_id, u.display_name, u.user_email FROM {$prefix}bb_user_list_assignments a JOIN {$wpdb->users} u ON u.ID = a.user_id WHERE a.list_id = %d ORDER BY u.display_name ASC",
            $lst->id
        ) );
    ?>
        <p>
            <a href="<?php echo esc_url( $page_url ); ?>" style="text-decoration:none">← <?php esc_html_e( 'All Lists', 'buddyboss-crm' ); ?></a>
        </p>
        <h2 style="margin-top:4px"><?php echo esc_html( $lst->name ); ?>
            <a href="<?php echo esc_url( add_query_arg( array('action'=>'edit','list_id'=>$lst->id), $page_url ) ); ?>" class="button button-small"><?php esc_html_e('Edit','buddyboss-crm'); ?></a>
        </h2>
        <p style="color:#666;font-size:12px">
            <span class="bb-crm-status-badge <?php echo 'dynamic'===($lst->list_type??'static') ? 'bb-crm-status-success' : ''; ?>"><?php echo esc_html( ucfirst( $lst->list_type ?? 'static' ) ); ?></span>
            &nbsp;<?php echo number_format( count( $members ) ); ?> <?php esc_html_e( 'members', 'buddyboss-crm' ); ?>
            <?php if ( 'dynamic' === ( $lst->list_type ?? 'static' ) ) : ?>
                &nbsp;<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array('action'=>'resync','list_id'=>$lst->id), $page_url ), 'resync-list-'.$lst->id ) ); ?>" class="button button-small"><?php esc_html_e('Re-sync','buddyboss-crm'); ?></a>
            <?php endif; ?>
        </p>

        <?php if ( 'static' === ( $lst->list_type ?? 'static' ) ) : ?>
            <form method="post" style="margin-bottom:16px;display:flex;gap:6px;align-items:center">
                <input type="hidden" name="action" value="add_member">
                <input type="hidden" name="list_id" value="<?php echo absint( $lst->id ); ?>">
                <?php wp_nonce_field( 'bb_crm_list_member' ); ?>
                <input type="number" name="user_id" placeholder="<?php esc_attr_e( 'User ID…', 'buddyboss-crm' ); ?>" class="small-text" min="1">
                <button type="submit" class="button"><?php esc_html_e( '+ Add Member by ID', 'buddyboss-crm' ); ?></button>
            </form>
        <?php endif; ?>

        <?php if ( empty( $members ) ) : ?>
            <p style="color:#aaa"><?php esc_html_e( 'No members yet.', 'buddyboss-crm' ); ?></p>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped" style="max-width:700px">
                <thead><tr>
                    <th><?php esc_html_e( 'User', 'buddyboss-crm' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'buddyboss-crm' ); ?></th>
                    <?php if ( 'static' === ( $lst->list_type ?? 'static' ) ) : ?><th style="width:80px"></th><?php endif; ?>
                </tr></thead>
                <tbody>
                <?php foreach ( $members as $m ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $m->display_name ); ?></strong> <small style="color:#888">#<?php echo absint( $m->user_id ); ?></small></td>
                    <td><?php echo esc_html( $m->user_email ); ?></td>
                    <?php if ( 'static' === ( $lst->list_type ?? 'static' ) ) :
                        $rm = wp_nonce_url( add_query_arg( array('action'=>'remove_member','list_id'=>$lst->id,'user_id'=>$m->user_id), $page_url ), 'remove-list-member-'.$lst->id.'-'.$m->user_id ); ?>
                    <td><a href="<?php echo esc_url($rm); ?>" class="button button-small button-link-delete" onclick="return confirm('Remove?')"><?php esc_html_e('Remove','buddyboss-crm'); ?></a></td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php else : ?>

        <?php if ( empty( $lists ) ) : ?>
            <div class="bb-crm-empty-state">
                <span class="dashicons dashicons-list-view"></span>
                <h3><?php esc_html_e( 'No lists yet', 'buddyboss-crm' ); ?></h3>
                <p><?php esc_html_e( 'Create user lists to group members for campaigns and automations.', 'buddyboss-crm' ); ?></p>
                <a href="<?php echo esc_url( add_query_arg( 'action', 'add', $page_url ) ); ?>" class="button button-primary button-large">
                    <?php esc_html_e( 'Add Your First List', 'buddyboss-crm' ); ?>
                </a>
            </div>
        <?php else : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead><tr>
                    <th><?php esc_html_e( 'Name', 'buddyboss-crm' ); ?></th>
                    <th style="width:90px"><?php esc_html_e( 'Type', 'buddyboss-crm' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'buddyboss-crm' ); ?></th>
                    <th style="width:80px"><?php esc_html_e( 'Members', 'buddyboss-crm' ); ?></th>
                    <th style="width:160px"><?php esc_html_e( 'Actions', 'buddyboss-crm' ); ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ( $lists as $lst ) : ?>
                <tr>
                    <td><strong><?php echo esc_html( $lst->name ); ?></strong></td>
                    <td><span class="bb-crm-status-badge <?php echo 'dynamic'===($lst->list_type??'static') ? 'bb-crm-status-success' : ''; ?>"><?php echo esc_html( ucfirst( $lst->list_type ?? 'static' ) ); ?></span></td>
                    <td><?php echo esc_html( wp_trim_words( $lst->description, 10, '…' ) ?: '—' ); ?></td>
                    <td><a href="<?php echo esc_url( add_query_arg( array('action'=>'view','list_id'=>$lst->id), $page_url ) ); ?>"><?php echo number_format( $lst->member_count ); ?></a></td>
                    <td>
                        <a href="<?php echo esc_url( add_query_arg( array('action'=>'edit','list_id'=>$lst->id), $page_url ) ); ?>" class="button button-small"><?php esc_html_e('Edit','buddyboss-crm'); ?></a>
                        <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( array('action'=>'delete','list_id'=>$lst->id), $page_url ), 'delete-list-'.$lst->id ) ); ?>"
                            class="button button-small button-link-delete"
                            onclick="return confirm('<?php esc_attr_e('Delete this list?','buddyboss-crm'); ?>')">
                            <?php esc_html_e('Delete','buddyboss-crm'); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>
</div>
