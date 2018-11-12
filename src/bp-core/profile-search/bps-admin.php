<?php

add_action( 'current_screen', 'bp_profile_search_redirect_admin_screens' );
function bp_profile_search_redirect_admin_screens () {
    $current_screen = get_current_screen();
    if ( 'edit-bp_ps_form' == $current_screen->id ) {
        $main_form = bp_profile_search_main_form();
        //create a form if not created already
        if ( !$main_form ) {
            bp_profile_search_add_main_form();
            $main_form = bp_profile_search_main_form();
        }
        
        //redirect to edit that form
        if ( $main_form ) {
            wp_redirect( admin_url( 'post.php?post=' . $main_form . '&action=edit' ) );
            die();
        }
    }
}

add_action ('add_meta_boxes', 'bps_add_meta_boxes');
function bps_add_meta_boxes () {
	add_meta_box ('bp_ps_fields_box', __('Form Fields', 'buddyboss'), 'bp_ps_fields_box', 'bp_ps_form', 'normal');
}

function bp_ps_fields_box ( $post ) {
	$bps_options = bps_meta ( $post->ID );

	list ($groups, $fields) = bps_get_fields ();
	echo '<script>var bps_groups = ['. json_encode ($groups). '];</script>';
?>

	<div id="field_box" class="field_box">
		<p>
			<span class="bps_col1"></span>
			<span class="bps_col2"><strong>&nbsp;<?php _e('Field', 'buddyboss'); ?></strong></span>&nbsp;
			<span class="bps_col3"><strong>&nbsp;<?php _e('Label', 'buddyboss'); ?></strong></span>&nbsp;
			<span class="bps_col4"><strong>&nbsp;<?php _e('Description', 'buddyboss'); ?></strong></span>&nbsp;
			<span class="bps_col5"><strong>&nbsp;<?php _e('Search Mode', 'buddyboss'); ?></strong></span>
		</p>
<?php

	foreach ($bps_options['field_code'] as $k => $id)
	{
		if (empty ($fields[$id]))  continue;

		$field = $fields[$id];
		$label = esc_attr ($bps_options['field_label'][$k]);
		$default = esc_attr ($field->name);
		$showlabel = empty ($label)? "placeholder=\"$default\"": "value=\"$label\"";
		$desc = esc_attr ($bps_options['field_desc'][$k]);
		$default = esc_attr ($field->description);
		$showdesc = empty ($desc)? "placeholder=\"$default\"": "value=\"$desc\"";
?>

		<div id="field_div<?php echo $k; ?>" class="sortable">
			<span class="bps_col1" title="<?php _e('drag to reorder fields', 'buddyboss'); ?>">&nbsp;&#x21C5;</span>
<?php
			_bps_field_select ($groups, "bps_options[field_name][$k]", "field_name$k", $id);
?>
			<input class="bps_col3" type="text" name="bps_options[field_label][<?php echo $k; ?>]" id="field_label<?php echo $k; ?>" <?php echo $showlabel; ?> />
			<input class="bps_col4" type="text" name="bps_options[field_desc][<?php echo $k; ?>]" id="field_desc<?php echo $k; ?>" <?php echo $showdesc; ?> />
<?php
			_bps_filter_select ($field, "bps_options[field_mode][$k]", "field_mode$k", $bps_options['field_mode'][$k]);
?>
			<a href="javascript:remove('field_div<?php echo $k; ?>')" class="delete"><?php _e('Remove', 'buddyboss'); ?></a>
		</div>
<?php
	}
?>
	</div>
	<input type="hidden" id="field_next" value="<?php echo count ($bps_options['field_code']); ?>" />
	<p><a href="javascript:add_field()"><?php _e('Add Field', 'buddyboss'); ?></a></p>
<?php
}

function _bps_field_select ($groups, $name, $id, $value)
{
	echo "<select class='bps_col2' name='$name' id='$id'>\n";
	foreach ($groups as $group => $fields)
	{
		$group = esc_attr ($group);
		echo "<optgroup label='$group'>\n";
		foreach ($fields as $field)
		{
			$selected = $field['id'] == $value? " selected='selected'": '';
			echo "<option value='$field[id]'$selected>$field[name]</option>\n";
		}
		echo "</optgroup>\n";
	}
	echo "</select>\n";
}

function _bps_filter_select ($f, $name, $id, $value)
{
	$filters = bps_Fields::get_filters ($f);

	echo "<select class='bps_col5' name='$name' id='$id'>\n";
	foreach ($filters as $key => $label)
	{
		$selected = $value == $key? " selected='selected'": '';
		echo "<option value='$key'$selected>$label</option>\n";
	}
	echo "</select>\n";
}

add_action ('save_post', 'bps_update_meta', 10, 2);
function bps_update_meta ($form, $post)
{
	if ($post->post_type != 'bp_ps_form' || $post->post_status != 'publish')  return false;
	if (empty ($_POST['options']) && empty ($_POST['bps_options']))  return false;

	$old_meta = bps_meta ($form);

	$meta = array ();
	$meta['field_code'] = array ();
	$meta['field_label'] = array ();
	$meta['field_desc'] = array ();
	$meta['field_mode'] = array ();

	list ($x, $fields) = bps_get_fields ();

	$codes = array ();
	$posted = isset ($_POST['bps_options'])? $_POST['bps_options']: array ();
	if (isset ($posted['field_name']))  foreach ($posted['field_name'] as $k => $code)
	{
		if (empty ($fields[$code]))  continue;
		if (in_array ($code, $codes))  continue;

		$codes[] = $code;
		$meta['field_code'][] = $code;
		$meta['field_label'][] = isset ($posted['field_label'][$k])? stripslashes ($posted['field_label'][$k]): '';
		$meta['field_desc'][] = isset ($posted['field_desc'][$k])? stripslashes ($posted['field_desc'][$k]): '';
		$meta['field_mode'][] = bps_Fields::valid_filter ($fields[$code], isset ($posted['field_mode'][$k])? $posted['field_mode'][$k]: 'none');

		bps_set_wpml ($form, $code, 'label', end ($meta['field_label']));
		bps_set_wpml ($form, $code, 'comment', end ($meta['field_desc']));
	}

	bps_set_wpml ($form, '-', 'title', $post->post_title);
	update_post_meta ($form, 'bps_options', $meta);

	return true;
}

function bps_set_option ($name, $value){
	$settings = get_option ('bps_settings');
	if ($settings === false)
		$settings = new stdClass;

	$settings->{$name} = $value;
	update_option ('bps_settings', $settings);
}

function bps_get_option ($name, $default)
{
	$settings = get_option ('bps_settings');
	return isset ($settings->{$name})? $settings->{$name}: $default;
}
