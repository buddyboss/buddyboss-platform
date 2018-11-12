<?php

add_filter ('bps_add_fields', 'bps_users_setup', 99);
function bps_users_setup ($fields)
{
	$columns = array
	(
		'ID'					=> 'integer',
		'user_login'			=> 'text',
//		'user_pass'				=> 'text',
//		'user_nicename'			=> 'text',
		'user_email'			=> 'text',
		'user_url'				=> 'text',
		'user_registered'		=> 'date',
//		'user_activation_key'	=> 'text',
//		'user_status'			=> 'integer',
		'display_name'			=> 'text',
	);

	$columns = apply_filters ('bps_users_columns', $columns);
	foreach ($columns as $column => $format)
	{
		$f = new stdClass;
		$f->group = __('Users data', 'buddyboss');
		$f->code = $column;
		$f->name = $column;
		$f->description = '';

		$f->format = $format;
		$f->options = array ();
		$f->search = 'bps_users_search';

		$fields[] = $f;
	}

	return $fields;
}

function bps_users_search ($f)
{
	global $wpdb;

	$filter = $f->format. '_'.  ($f->filter == ''? 'is': $f->filter);
	$value = $f->value;

	$sql = array ('select' => '', 'where' => array ());
	$sql['select'] = "SELECT ID FROM {$wpdb->users}";

	switch ($filter)
	{
	case 'text_contains':
		$escaped = '%'. bps_esc_like ($value). '%';
		$sql['where'][$filter] = $wpdb->prepare ("{$f->code} LIKE %s", $escaped);
		break;

	case 'text_is':
		$sql['where'][$filter] = $wpdb->prepare ("{$f->code} = %s", $value);
		break;

	case 'text_like':
		$value = str_replace ('\\\\%', '\\%', $value);
		$value = str_replace ('\\\\_', '\\_', $value);
		$sql['where'][$filter] = $wpdb->prepare ("{$f->code} LIKE %s", $value);
		break;

	case 'integer_is':
		$sql['where'][$filter] = $wpdb->prepare ("{$f->code} = %d", $value);
		break;

	case 'integer_range':
		if (isset ($value['min']))  $sql['where']['min'] = $wpdb->prepare ("{$f->code} >= %d", $value['min']);
		if (isset ($value['max']))  $sql['where']['max'] = $wpdb->prepare ("{$f->code} <= %d", $value['max']);
		break;

	case 'date_age_range':
		$day = date ('j');
		$month = date ('n');
		$year = date ('Y');

		if (isset ($value['max']))
		{
			$ymin = $year - $value['max'] - 1; 
			$sql['where']['age_min'] = $wpdb->prepare ("DATE({$f->code}) > %s", "$ymin-$month-$day");
		}
		if (isset ($value['min']))
		{
			$ymax = $year - $value['min'];
			$sql['where']['age_max'] = $wpdb->prepare ("DATE({$f->code}) <= %s", "$ymax-$month-$day");
		}
		break;

	default:
		return array ();
	}

	$sql = apply_filters ('bps_field_sql', $sql, $f);
	$query = $sql['select']. ' WHERE '. implode (' AND ', $sql['where']);

	$results = $wpdb->get_col ($query);
	return $results;
}

add_filter ('bps_add_fields', 'bps_usermeta_setup', 99);
function bps_usermeta_setup ($fields)
{
	$meta_keys = array
	(
		'first_name'			=> 'text',
		'last_name'				=> 'text',
		'role'					=> array ('text', bps_get_roles ()),
		'roles'					=> array ('set', bps_get_roles ()),
		'total_friend_count'	=> 'integer',
		'total_group_count'		=> 'integer',
	);

	$meta_keys = apply_filters ('bps_usermeta_keys', $meta_keys);
	foreach ($meta_keys as $meta_key => $format)
	{
		$f = new stdClass;
		$f->group = __('Usermeta data', 'buddyboss');
		$f->code = $meta_key;
		$f->name = $meta_key;
		$f->description = '';

		$format = (array) $format;
		$f->format = $format[0];
		$f->options = isset ($format[1])? $format[1]: array ();
		$f->search = 'bps_usermeta_search';

		$fields[] = $f;
	}

	return $fields;
}

function bps_usermeta_search ($f)
{
	global $wpdb;

	$filter = $f->format. '_'.  ($f->filter == ''? 'is': $f->filter);
	if ($f->code == 'role')  $filter = 'set_match_any';

	$value = $f->value;

	$sql = array ('select' => '', 'where' => array ());
	$sql['select'] = "SELECT user_id FROM {$wpdb->usermeta}";
	
	if (in_array ($f->code, array ('role', 'roles')))
		$sql['where']['meta_key'] = $wpdb->prepare ("meta_key = %s", $wpdb->prefix. 'capabilities');
	else
		$sql['where']['meta_key'] = $wpdb->prepare ("meta_key = %s", $f->code);

	switch ($filter)
	{
	case 'text_contains':
		$escaped = '%'. bps_esc_like ($value). '%';
		$sql['where'][$filter] = $wpdb->prepare ("meta_value LIKE %s", $escaped);
		break;

	case 'text_is':
		$sql['where'][$filter] = $wpdb->prepare ("meta_value = %s", $value);
		break;

	case 'text_like':
		$value = str_replace ('\\\\%', '\\%', $value);
		$value = str_replace ('\\\\_', '\\_', $value);
		$sql['where'][$filter] = $wpdb->prepare ("meta_value LIKE %s", $value);
		break;

	case 'integer_is':
		$sql['where'][$filter] = $wpdb->prepare ("meta_value = %d", $value);
		break;

	case 'integer_range':
		if (isset ($value['min']))  $sql['where']['min'] = $wpdb->prepare ("meta_value >= %d", $value['min']);
		if (isset ($value['max']))  $sql['where']['max'] = $wpdb->prepare ("meta_value <= %d", $value['max']);
		break;

	case 'date_age_range':
		$day = date ('j');
		$month = date ('n');
		$year = date ('Y');

		if (isset ($value['max']))
		{
			$ymin = $year - $value['max'] - 1; 
			$sql['where']['age_min'] = $wpdb->prepare ("DATE(meta_value) > %s", "$ymin-$month-$day");
		}
		if (isset ($value['min']))
		{
			$ymax = $year - $value['min'];
			$sql['where']['age_max'] = $wpdb->prepare ("DATE(meta_value) <= %s", "$ymax-$month-$day");
		}
		break;

	case 'set_match_any':
	case 'set_match_all':
		$values = (array)$value;
		$parts = array ();
		foreach ($values as $value)
		{
			$escaped = '%:"'. bps_esc_like ($value). '";%';
			$parts[] = $wpdb->prepare ("meta_value LIKE %s", $escaped);
		}
		$match = ($filter == 'set_match_any')? ' OR ': ' AND ';
		$sql['where'][$filter] = '('. implode ($match, $parts). ')';
		break;

	default:
		return array ();
	}

	$sql = apply_filters ('bps_field_sql', $sql, $f);
	$query = $sql['select']. ' WHERE '. implode (' AND ', $sql['where']);

	$results = $wpdb->get_col ($query);
	return $results;
}

function bps_get_roles ()
{
	return wp_roles()->get_names ();
}

add_filter ('bps_add_fields', 'bps_taxonomies_setup', 99);
function bps_taxonomies_setup ($fields)
{
	$taxonomies = get_object_taxonomies ('user', 'objects');
	$taxonomies = apply_filters ('bps_taxonomies', $taxonomies);

	foreach ($taxonomies as $taxonomy => $object)
	{
		$f = new stdClass;
		$f->group = __('User taxonomies', 'buddyboss');
		$f->code = $taxonomy;
		$f->name = $object->labels->singular_name;
		$f->description = $object->description;
		if ($taxonomy == 'bp_member_type')
		{
			$f->name = __('Member type', 'buddyboss');
			$f->description = __('Select the member type', 'buddyboss');
		}

		$f->format = 'text';
		$f->options = array ();
		$terms = get_terms (array ('taxonomy' => $taxonomy, 'hide_empty' => false));
		foreach ($terms as $term)
			$f->options[$term->term_id] = $term->name;

		if ($taxonomy == 'bp_member_type')
		{
			$terms = bp_get_member_types (array (), 'objects');
			foreach ($f->options as $k => $option)
			{
				if (isset ($terms[$option]))
					$f->options[$k] = $terms[$option]->labels['singular_name'];
				else
					unset ($f->options[$k]);
			}
		}

		$f->search = 'bps_taxonomies_search';
		$fields[] = $f;
	}

	return $fields;
}

function bps_taxonomies_search ($f)
{
	$results = get_objects_in_term ($f->value, $f->code);
	return is_array ($results)? $results: array ();
}
