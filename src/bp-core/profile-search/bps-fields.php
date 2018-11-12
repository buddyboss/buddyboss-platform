<?php

function bps_get_fields ()
{
	static $groups = array ();
	static $fields = array ();

	if (!empty ($groups))  return array ($groups, $fields);

	$field_list = apply_filters ('bps_add_fields', array ());
	foreach ($field_list as $f)
	{
		do_action ('bps_edit_field', $f);
		if (!bps_Fields::set_filters ($f))  continue;

		$groups[$f->group][] = array ('id' => $f->code, 'name' => $f->name);
		$fields[$f->code] = $f;
	}

	return array ($groups, $fields);
}

class bps_Fields
{
	private static $display = array
	(
		'text'			=> array ('contains' => 'textbox', '' => 'textbox', 'like' => 'textbox'),
		'integer'		=> array ('' => 'number', 'range' => 'range'),
		'decimal'		=> array ('' => 'textbox', 'range' => 'range'),
		'date'			=> array ('age_range' => 'range'),
		'location'		=> array ('distance' => 'distance', 'contains' => 'textbox', '' => 'textbox', 'like' => 'textbox'),

		'text/e'		=> array ('' => array ('selectbox', 'radio'), 'one_of' => array ('checkbox', 'multiselectbox')),
		'decimal/e'		=> array ('' => array ('selectbox', 'radio'), 'range' => 'range'),
		'set/e'			=> array ('match_any' => array ('checkbox', 'multiselectbox'), 'match_all'	=> array ('checkbox', 'multiselectbox')),
	);

	public static function get_filters ($f)
	{
		$labels = array
		(
			'contains'		=> __('contains', 'buddyboss'),
			''				=> __('is', 'buddyboss'),
			'like'			=> __('is like', 'buddyboss'),
			'range'			=> __('range', 'buddyboss'),
			'age_range'		=> __('age range', 'buddyboss'),
			'distance'		=> __('distance', 'buddyboss'),
			'one_of'		=> __('is one of', 'buddyboss'),
			'match_any'		=> __('match any', 'buddyboss'),
			'match_all'		=> __('match all', 'buddyboss'),
		);
		
		$filters = array ();
		foreach ($f->filters as $filter)
			$filters[$filter] = $labels[$filter];
		return $filters;
	}

	public static function set_filters ($f)
	{
		$format = isset ($f->format)? $f->format: 'none';
		$enum = (isset ($f->options) && is_array ($f->options))? count ($f->options): 0;
		$selector = $format. ($enum? '/e': '');
		if (!isset (self::$display[$selector]))  return false;

		$f->filters = array_keys (self::$display[$selector]);
		return true;
	}

	public static function is_filter ($f, $filter)
	{
		return in_array ($filter, $f->filters);
	}

	public static function valid_filter ($f, $filter)
	{
		return in_array ($filter, $f->filters)? $filter: $f->filters[0];
	}

	public static function set_display ($f, $filter)
	{
		$format = isset ($f->format)? $f->format: 'none';
		$enum = (isset ($f->options) && is_array ($f->options))? count ($f->options): 0;
		$selector = $format. ($enum? '/e': '');
		if (!isset (self::$display[$selector][$filter]))  return false;

		$display = self::$display[$selector][$filter];
		if (is_string ($display))
		{
			$f->display = $display;
		}
		else
		{
			$default = (isset ($f->type) && in_array ($f->type, $display))? $f->type: $display[0];
			$choice = apply_filters ('bps_field_display', $default, $f);
			$f->display = in_array ($choice, $display)? $choice: $default;
		}
		return true;
	}
}

function bps_parse_request ($request)
{
	$j = 1;

	$parsed = array ();
	list (, $fields) = bps_get_fields ();
	foreach ($fields as $key => $value)
		$parsed[$key] = clone $fields[$key];

	foreach ($request as $key => $value)
	{
		if ($value === '')  continue;

		$k = bps_match_key ($key, $parsed);
		if ($k === false)  continue;

		$f = $parsed[$k];
		$filter = ($key == $f->code)? '': substr ($key, strlen ($f->code) + 1);
		if (!bps_is_filter ($filter, $f))  continue;

		switch ($filter)
		{
		default:
			$f->filter = $filter;
			$f->value = $value;
			break;
		case 'distance':
			if (!empty ($value['location']) && !empty ($value['lat']) && !empty ($value['lng']))
			{
				if (empty ($value['distance']))  $value['distance'] = 1;
				$f->filter = $filter;
				$f->value = $value;
			}
			break;
		case 'range':
			if (is_numeric ($value['min']))
				$f->value['min'] = $value['min'];
			if (is_numeric ($value['max']))
				$f->value['max'] = $value['max'];
			if (isset ($f->value))
				$f->filter = $filter;
			break;
		case 'age_range':
			if (is_numeric ($value['min']))
				$f->value['min'] = (int)$value['min'];
			if (is_numeric ($value['max']))
				$f->value['max'] = (int)$value['max'];
			if (isset ($f->value))
				$f->filter = $filter;
			break;
		case 'range_min':
		case 'age_range_min':
			if (!is_numeric ($value))  break;
			$f->filter = rtrim ($filter, '_min');
			$f->value['min'] = $value;
			if ($filter == 'age_range_min')  $f->value['min'] = (int)$f->value['min'];
			break;
		case 'range_max':
		case 'age_range_max':
			if (!is_numeric ($value))  break;
			$f->filter = rtrim ($filter, '_max');
			$f->value['max'] = $value;
			if ($filter == 'age_range_max')  $f->value['max'] = (int)$f->value['max'];
			break;
		case 'label':
			$f->label = stripslashes ($value);
			break;
		}

		if (!isset ($f->order))  $f->order = $j++;
	}

	return $parsed;
}

function bps_match_key ($key, $fields)
{
	foreach ($fields as $k => $f)
		if ($key == $f->code || strpos ($key, $f->code. '_') === 0)  return $k;

	return false;
}

function bps_is_filter ($filter, $f)
{
	if ($filter == 'range_min' || $filter == 'range_max')  $filter = 'range';
	if ($filter == 'age_range_min' || $filter == 'age_range_max')  $filter = 'age_range';
	if ($filter == 'label')  return true;

	return bps_Fields::is_filter ($f, $filter);
}

function bps_escaped_form_data ($version = '')
{
	return bps_escaped_form_data47 ($version);
}

function bps_escaped_filters_data ($version = '4.7')
{
	if ($version == '4.7')	return bps_escaped_filters_data47 ();
	if ($version == '4.8')	return bps_escaped_filters_data48 ();

	return false;
}

function bps_set_hidden_field ($name, $value)
{
	$new = new stdClass;
	$new->display = 'hidden';
	$new->code = $name;		// to be removed
	$new->html_name = $name;
	$new->value = $value;
	$new->unique_id = bps_unique_id ($name);

	return $new;
}

function bps_sort_fields ($a, $b)
{
	return ($a->order <= $b->order)? -1: 1;
}
