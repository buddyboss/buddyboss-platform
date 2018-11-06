<?php
/**
 * BuddyPress XProfile Repeater Fields and field sets. Tags.
 * 
 * @package BuddyBoss
 * @since BuddyPress 3.1.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * @since BuddyBoss 3.1.1
 * @return int
 */
function bp_profile_field_set_max_cap () {
    //how many maximum number of sets of a field can be added
    return 100;
}

/**
 * @since BuddyBoss 3.1.1
 * @global type $wpdb
 * @param type $field_group_id
 * @return type
 */
function bp_get_repeater_template_field_ids ( $field_group_id ) {
    global $wpdb;
    $bp = buddypress();
    
    $group_field_ids = $wpdb->get_col( "SELECT id FROM {$bp->profile->table_name_fields} WHERE group_id = {$field_group_id} AND parent_id = 0" );
    if ( empty( $group_field_ids ) || is_wp_error( $group_field_ids ) ) {
        return array();
    }
    
    $clone_field_ids = $wpdb->get_col( "SELECT object_id FROM {$bp->profile->table_name_meta} "
        . " WHERE object_type = 'field' AND object_id IN (". implode( ',', $group_field_ids ) .") AND meta_key = '_is_repeater_clone' AND meta_value = 1" 
    );
    
    if ( empty( $clone_field_ids ) || is_wp_error( $clone_field_ids ) ) {
        $template_field_ids = $group_field_ids;
    } else {
        $template_field_ids = array_diff( $group_field_ids, $clone_field_ids );
    }
    
    return $template_field_ids;
}

/**
 * @since BuddyBoss 3.1.1
 * @global type $wpdb
 * @param type $field_group_id
 * @param type $count
 * @return array
 */
function bp_get_repeater_clone_field_ids_subset ( $field_group_id, $count ) {
    global $wpdb;
    $bp = buddypress();
    
    $ids = array();
    
    $template_field_ids = bp_get_repeater_template_field_ids( $field_group_id );
    
    if ( empty( $template_field_ids ) ) {
        return $ids;
    }
    
    foreach ( $template_field_ids as $template_field_id ) {
        $sql = "select m1.object_id, m2.meta_value AS 'clone_number' FROM {$bp->profile->table_name_meta} as m1 
        JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id 
        WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d 
        AND m2.meta_key = '_clone_number' ORDER BY m2.meta_value ASC ";
        $sql = $wpdb->prepare( $sql, $template_field_id );
        
        $results = $wpdb->get_results( $sql, ARRAY_A );
        
        for ( $i = 1; $i <= $count; $i++ ) {
            //is there a clone already?
            $clone_id = false;
            
            if ( !empty( $results ) && !is_wp_error( $results ) ) {
                foreach ( $results as $row ) {
                    if ( $row['clone_number'] == $i ) {
                        $clone_id = $row['object_id'];
                        break;
                    }
                }
            }
            
            //if not create one!
            if ( ! $clone_id ) {
                $clone_id = bp_clone_field_for_repeater_sets ( $template_field_id );
            }

            if ( $clone_id ) {
                $ids[] = $clone_id;
            }
        }
    }
    
    return $ids;
}

/**
 * @since BuddyBoss 3.1.1
 * @global type $wpdb
 * @param type $field_group_id
 * @return array
 */
function bp_get_repeater_clone_field_ids_all ( $field_group_id ) {
    global $wpdb;
    $bp = buddypress();
    
    $ids = array();
    
    $template_field_ids = bp_get_repeater_template_field_ids( $field_group_id );
    
    if ( empty( $template_field_ids ) ) {
        return $ids;
    }
    
    foreach ( $template_field_ids as $template_field_id ) {
        $sql = "select m1.object_id FROM {$bp->profile->table_name_meta} as m1 
        WHERE m1.meta_key = '_cloned_from' AND m1.meta_value = %d";
        $sql = $wpdb->prepare( $sql, $template_field_id );
        
        $results = $wpdb->get_col( $sql );
        
        if ( !empty( $results ) && !is_wp_error( $results ) ) {
            $ids = array_merge( $ids, $results );
        }
    }
    
    return $ids;
}

add_action( 'xprofile_updated_profile', 'bp_profile_repeaters_update_field_data', 11, 5 );
/**
 * Update/Sort repeater fields when profile data is updated.
 * @since BuddyBoss 3.1.1
 */
function bp_profile_repeaters_update_field_data ( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {
    global $wpdb;
    $bp = buddypress();
    
    if ( !empty( $errors ) ) {
        return;
    }
    
    $field_group_id = $wpdb->get_var( $wpdb->prepare( "SELECT group_id FROM {$bp->profile->table_name_fields} WHERE id = %d", $posted_field_ids[ 0 ] ) );
    
    $is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $field_group_id, 'is_repeater_enabled' ) ? true : false;
    if ( !$is_repeater_enabled ) {
        return;
    }
    
    $field_set_sequence = wp_parse_id_list( $_POST['repeater_set_sequence'] );
    
    $counter = 1;
    foreach( (array) $field_set_sequence as $field_set_number ) {
        /**
         * Find all fields in this set, take their values and update the data for corresponding fields in set number $counter
         */
        $fields_of_current_set = $wpdb->get_col( 
            "SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = '_clone_number' AND meta_value = {$field_set_number} "
            . " AND object_id IN (". implode( ',', $posted_field_ids ) .") and object_type = 'field' " 
        );
        
        if ( !empty( $fields_of_current_set && !is_wp_error( $fields_of_current_set ) ) ) {
            foreach ( $fields_of_current_set as $field_of_current_set ) {
                $cloned_from = $wpdb->get_var( "SELECT meta_value FROM {$bp->profile->table_name_meta} WHERE object_id = {$field_of_current_set} AND meta_key = '_cloned_from' " );
                
                $sql = "SELECT m1.object_id FROM {$bp->profile->table_name_meta} AS m1 JOIN {$bp->profile->table_name_meta} AS m2 ON m1.object_id = m2.object_id " 
                    . " WHERE m1.object_type = 'field' AND m1.meta_key = '_cloned_from' AND m1.meta_value = {$cloned_from} "
                    . " AND m2.meta_key = '_clone_number' AND m2.meta_value = {$counter} ";
                $corresponding_field_id = $wpdb->get_var( $sql );
                
                if ( !empty( $corresponding_field_id ) ) {
                    $new_data = isset( $new_values[ $field_of_current_set ][ 'value' ] ) ? $new_values[ $field_of_current_set ][ 'value' ] : '';
                    $new_visibility_level = isset( $new_values[ $field_of_current_set ][ 'visibility' ] ) ? $new_values[ $field_of_current_set ][ 'visibility' ] : '';
                    xprofile_set_field_visibility_level( $corresponding_field_id, $user_id, $new_visibility_level );
                    xprofile_set_field_data( $corresponding_field_id, $user_id, $new_data );
                }
            }
        }
    
        $counter++;
    }
    
    bp_set_profile_field_set_count( $field_group_id, $user_id, count( $field_set_sequence ) );
}

/**
 * @since BuddyBoss 3.1.1
 * @global type $wpdb
 * @param type $field_id
 * @return boolean
 */
function bp_clone_field_for_repeater_sets ( $field_id ) {
    global $wpdb;
    $bp = buddypress();
    
    $db_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $field_id ), ARRAY_A );
    if ( !empty( $db_row ) && !is_wp_error( $db_row ) ) {
        $template_field_id = $db_row['id'];
        
        $new_field_column_names = array( 'group_id', 'parent_id', 'type', 'name', 'description', 'is_required', 
            'is_default_option', 'field_order', 'option_order', 'order_by', 'can_delete' );
        $new_field_column_data_types = array( '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d' );
        $new_field_column_data = array (
            'group_id' => $db_row[ 'group_id' ],
            'parent_id' => $db_row[ 'parent_id' ],
            'type' => $db_row[ 'type' ],
            'name' => $db_row[ 'name' ],
            'description' => $db_row[ 'description' ],
            'is_required' => $db_row[ 'is_required' ],
            'is_default_option' => $db_row[ 'is_default_option' ],
            'field_order' => $db_row[ 'field_order' ],
            'option_order' => $db_row[ 'option_order' ],
            'order_by' => $db_row[ 'order_by' ],
            'can_delete' => $db_row[ 'can_delete' ],
        );
        
        $inserted = $wpdb->insert(
            $bp->profile->table_name_fields,
            $new_field_column_data,
            $new_field_column_data_types
        );
        
        if ( $inserted ) {
            $new_field_id = $wpdb->insert_id;
            $metas = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = {$template_field_id} AND object_type = 'field'", ARRAY_A );
            if ( !empty( $metas ) && !is_wp_error( $metas ) ) {
                foreach ( $metas as $meta ) {
                    bp_xprofile_update_meta( $new_field_id, 'field', $meta['meta_key'], $meta['meta_value'] );
                }
            }
            
            $current_clone_number = 1;
            
            //get all clones of the template field
            $all_clones = $wpdb->get_col( "SELECT object_id FROM {$bp->profile->table_name_meta} WHERE meta_key = '_cloned_from' AND meta_value = {$template_field_id}" );
            if ( !empty( $all_clones ) && !is_wp_error( $all_clones ) ) {
                $last_max_clone_number = $wpdb->get_var( 
                    "SELECT MAX( meta_value ) FROM {$bp->profile->table_name_meta} WHERE meta_key = '_clone_number' AND object_id IN (". implode( ',', $all_clones ) .")" 
                );
                    
                $last_max_clone_number = !empty( $last_max_clone_number ) ? absint( $last_max_clone_number ) : 0;
                $current_clone_number = $last_max_clone_number + 1;
            }
            
            bp_xprofile_update_meta( $new_field_id, 'field', '_is_repeater_clone', true );
            bp_xprofile_update_meta( $new_field_id, 'field', '_cloned_from', $template_field_id );
            bp_xprofile_update_meta( $new_field_id, 'field', '_clone_number', $current_clone_number );
            
            //fix field order
            $field_order = ( $current_clone_number * bp_profile_field_set_max_cap() ) + $db_row[ 'field_order' ];
            $wpdb->update(
                $bp->profile->table_name_fields,
                array( 'field_order' => $field_order ),
                array( 'id' => $new_field_id ),
                array( '%d' ),
                array( '%d' )
            );
                
            return $new_field_id;
        }
    }
    
    return false;
}

add_action( 'xprofile_fields_saved_field', 'bp_repeaters_update_clones_on_template_update' );
/**
 * Update repeater/clone fields when the main/template field is updated.
 * 
 * @param \BP_XProfile_Field $field Description
 */
function bp_repeaters_update_clones_on_template_update ( $field ) {
    global $wpdb;
    $bp = buddypress();
    
    //get all clone field ids
    $clone_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT f.id FROM {$bp->profile->table_name_fields} AS f JOIN {$bp->profile->table_name_meta} AS fm ON f.id = fm.object_id "
        . " WHERE f.parent_id = 0 AND fm.meta_key = '_cloned_from' AND fm.meta_value = %d ",
        $field->id
    ) );
        
    if ( empty( $clone_ids ) || is_wp_error( $clone_ids ) ) {
        return;
    }
    
    $db_row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bp->profile->table_name_fields} WHERE id = %d", $field->id ), ARRAY_A );
    
    if ( !empty( $db_row ) && !is_wp_error( $db_row ) ) {
        $sql = $wpdb->prepare(
            "UPDATE {$bp->profile->table_name_fields} SET "
            . " group_id = %d, parent_id = %d, type = %s, name = %s, description = %s, is_required = %d, "
            . " is_default_option = %d, field_order = %d, option_order = %d, order_by = %d, can_delete = %d "
            . " WHERE id IN ( ". implode( ',', $clone_ids ) ." )",
            $db_row[ 'group_id' ], $db_row[ 'parent_id' ], $db_row[ 'type' ], $db_row[ 'name' ], $db_row[ 'description' ], $db_row[ 'is_required' ],
            $db_row[ 'is_default_option' ], $db_row[ 'field_order' ], $db_row[ 'option_order' ], $db_row[ 'order_by' ], $db_row[ 'can_delete' ]
        );
            
        $wpdb->query( $sql );
        
        $metas = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_meta} WHERE object_id = {$field->id} AND object_type = 'field'", ARRAY_A );
        
        if ( !empty( $metas ) && !is_wp_error( $metas ) ) {
            foreach ( $clone_ids as $clone_id ) {
                foreach ( $metas as $meta ) {
                    bp_xprofile_update_meta( $clone_id, 'field', $meta['meta_key'], $meta['meta_value'] );
                }
            }
        }
    }
}
/* ----------- User Profiles ------------------- */

/**
 * 
 * @param type $field_group_id
 * @param type $user_id
 * @since BuddyBoss 3.1.1
 * @return type
 */
function bp_get_profile_field_set_count ( $field_group_id, $user_id ) {
    $count = get_user_meta( $user_id, 'field_set_count_' . $field_group_id, true );
    return $count > 0 ? $count : 1;
}

/**
 * 
 * @param type $field_group_id
 * @param type $user_id
 * @param type $count
 * @since BuddyBoss 3.1.1
 * @return type
 */
function bp_set_profile_field_set_count ( $field_group_id, $user_id, $count ) {
    $max = bp_profile_field_set_max_cap();
    $count = $count <= $max ? $count : $max;
    
    return update_user_meta( $user_id, 'field_set_count_' . $field_group_id, $count );
}

add_action( 'bp_after_profile_field_content', 'bp_print_add_repeater_set_button' );
/**
 * @since BuddyBoss 3.1.1
 */
function bp_print_add_repeater_set_button () {
    $group_id = bp_get_current_profile_group_id();
    $is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
    if ( $is_repeater_enabled ) {
        echo "<button id='btn_add_repeater_set' data-nonce='". wp_create_nonce( 'bp_xprofile_add_repeater_set' ) ."' data-group='{$group_id}'>";
        printf(
            /* translators: %s = profile field group name */
            __( 'Add %s Set', 'buddyboss' ),
            bp_get_the_profile_group_name()
        );
        echo "</button>";
    }
}

add_action( 'wp_ajax_bp_xprofile_add_repeater_set', 'bp_xprofile_ajax_add_repeater_set' );
/**
 * @since BuddyBoss 3.1.1
 */
function bp_xprofile_ajax_add_repeater_set () {
    check_ajax_referer( 'bp_xprofile_add_repeater_set', '_wpnonce' );
    
    $user_id = bp_displayed_user_id() ? bp_displayed_user_id() : bp_loggedin_user_id();
    if ( !$user_id ) {
        die();
    }
    
    $field_group_id = isset( $_REQUEST['group'] ) ? absint( $_REQUEST['group'] ) : false;
    if ( !$field_group_id ) {
        die();
    }
    
    $count = bp_get_profile_field_set_count( $field_group_id, $user_id );
    $count++;
    bp_set_profile_field_set_count( $field_group_id, $user_id, $count );
    die( 'ok' );
}

add_action( 'bp_before_profile_field_html', 'bp_profile_repeaters_print_group_html_start' );
/**
 * @since BuddyBoss 3.1.1
 * @global type $first_xpfield_in_repeater
 */
function bp_profile_repeaters_print_group_html_start () {
    $group_id = bp_get_current_profile_group_id();
    $is_repeater_enabled = 'on' == BP_XProfile_Group::get_group_meta( $group_id, 'is_repeater_enabled' ) ? true : false;
    if ( $is_repeater_enabled ) {
        global $first_xpfield_in_repeater;
        $current_field_id = bp_get_the_profile_field_id();
        $current_set_number = bp_xprofile_get_meta( $current_field_id, 'field', '_clone_number', true );
        $template_field_id = bp_xprofile_get_meta( $current_field_id, 'field', '_cloned_from', true );
        
        if ( empty( $first_xpfield_in_repeater ) ) {
            $first_xpfield_in_repeater = $template_field_id;
            //start of first set
            ?>
            <div class="repeater_sets_sortable">
            <div class='repeater_group_outer' data-set_no='<?php echo $current_set_number;?>'>
                <div class='repeater_tools'>
                    <span style='display: inline-block;' class='set_title'></span>
                    <a class='button set_toggle'>&uarr;&darr;</a><a class='button set_edit'>Edit</a><a class='button set_delete'>Delete</a>
                </div>
                <div class='repeater_group_inner'>
            <?php 
        } else {
            if ( $first_xpfield_in_repeater == $template_field_id ) {
                //start of a new set
                ?>
                </div>
            </div><!-- .repeater_group_outer -->
            <div class='repeater_group_outer' data-set_no='<?php echo $current_set_number;?>'>
                <div class='repeater_tools'>
                    <span style='display: inline-block;' class='set_title'></span>
                    <a class='button set_toggle'>&uarr;&darr;</a><a class='button set_edit'>Edit</a><a class='button set_delete'>Delete</a>
                </div>
                <div class='repeater_group_inner'>
                <?php 
            }
        }
    }
}

add_action( 'bp_after_profile_field_content', 'bp_profile_repeaters_print_group_html_end', 4 );
/**
 * @since BuddyBoss 3.1.1
 * @global boolean $first_xpfield_in_repeater
 */
function bp_profile_repeaters_print_group_html_end () {
    global $first_xpfield_in_repeater;
    if ( !empty( $first_xpfield_in_repeater ) ) {
        echo "</div></div><!-- .repeater_group_outer -->";
        echo "</div><!-- repeater_sets_sortable -->";
        $first_xpfield_in_repeater = false;
    }
}

