<?php

add_action ('wp', 'bps_set_request');
function bps_set_request ()
{
	global $post;
	global $shortcode_tags;

	if (isset ($post->post_type) && $post->post_type == 'page')
	{
		$saved_shortcodes = $shortcode_tags;
		$shortcode_tags = array ();
		add_shortcode ('bps_directory', 'bps_save_hidden_filters');
		do_shortcode ($post->post_content);
		$shortcode_tags = $saved_shortcodes;
	}

	$filters = bps_hidden_filters ();
	if (!empty ($filters))
	{
		$cookie = apply_filters ('bps_cookie_name', 'bps_filters');
		setcookie ($cookie, http_build_query ($filters), 0, COOKIEPATH);
	}

	if (isset ($_REQUEST['bps_debug']))
	{
		$cookie = apply_filters ('bps_cookie_name', 'bps_debug');
		setcookie ($cookie, 1, 0, COOKIEPATH);
	}

	$persistent = bps_get_option ('persistent', '1');
	$new_search = isset ($_REQUEST[bp_core_get_component_search_query_arg ('members')]);

	if ($new_search || !$persistent)
		if (!isset ($_REQUEST[BPS_FORM]))  $_REQUEST[BPS_FORM] = 'clear';

	if (isset ($_REQUEST[BPS_FORM]))
	{
		$cookie = apply_filters ('bps_cookie_name', 'bps_request');
		if ($_REQUEST[BPS_FORM] != 'clear')
		{
			$_REQUEST['bps_directory'] = bps_current_page ();
			setcookie ($cookie, http_build_query ($_REQUEST), 0, COOKIEPATH);
		}
		else
		{
			setcookie ($cookie, '', 0, COOKIEPATH);
		}
	}
}

function bps_get_request ($type, $form=0)
{
	$current = bps_current_page ();

	$cookie = apply_filters ('bps_cookie_name', 'bps_request');
	$request = isset ($_REQUEST[BPS_FORM])? $_REQUEST: array ();
	if (empty ($request) && isset ($_COOKIE[$cookie]))
		parse_str (stripslashes ($_COOKIE[$cookie]), $request);

	$cookie = apply_filters ('bps_cookie_name', 'bps_filters');
	$filters = bps_hidden_filters ();
	if (empty ($filters) && isset ($_COOKIE[$cookie]))
		parse_str (stripslashes ($_COOKIE[$cookie]), $filters);

	switch ($type)
	{
	case 'form':
		if (isset ($request[BPS_FORM]) && $request[BPS_FORM] != $form)  $request = array ();
		break;

	case 'filters':
		if (isset ($request['bps_directory']) && $request['bps_directory'] != $current)  $request = array ();
		break;

	case 'search':
		if (isset ($request['bps_directory']) && $request['bps_directory'] != $current)  $request = array ();
		if (isset ($filters['bps_directory']) && $filters['bps_directory'] != $current)  $filters = array ();
		foreach ($filters as $key => $value)  $request[$key] = $value;
		break;
	}

	return apply_filters ('bps_request', $request, $type, $form);
}

function bps_save_hidden_filters ($attr, $content)
{
	global $bps_hidden_filters;

	$bps_hidden_filters = array ('bps_directory' => bps_current_page ());

	list (, $fields) = bps_get_fields ();
	$split = isset ($attr['split'])? $attr['split']: ',';

	if (is_array ($attr))  foreach ($attr as $key => $value)
	{
		$k = bps_match_key ($key, $fields);
		if ($k === false)  continue;

		$f = $fields[$k];
		$filter = ($key == $f->code)? '': substr ($key, strlen ($f->code) + 1);
		if (!bps_Fields::is_filter ($f, $filter))  continue;

		$selector = $filter. (count ($f->options)? '/e': '');
		switch ($selector)
		{
		case 'contains':
		case '':
		case 'like':
			$value = trim (addslashes ($value));
			if ($value !== '')  $bps_hidden_filters[$key] = $value;
			break;

		case 'range':
		case 'age_range':
			list ($min, $max) = explode ($split, $value);
			$values = array ();
			if (($min = trim ($min)) !== '')  $values['min'] = $min;
			if (($max = trim ($max)) !== '')  $values['max'] = $max;
			if (!empty ($values))  $bps_hidden_filters[$key] = $values;
			break;

		case 'match_any/e':
		case 'match_all/e':
		case '/e':
		case 'one_of/e':
			$flipped = array_flip ($f->options);
			$values = explode ($split, $value);
			$keys = array ();
			foreach ($values as $value)
			{
				$value = trim ($value);
				if (isset ($flipped[$value]))  $keys[] = addslashes ($flipped[$value]);
			}
			if (!empty ($keys))  $bps_hidden_filters[$key] = $keys;
			break;
		}
	}

	add_filter ('body_class', function ($classes) {return array_merge (array ('directory', 'members', 'buddypress'), $classes);});
}

function bps_hidden_filters ()
{
	global $bps_hidden_filters;

	$filters = isset ($bps_hidden_filters)? $bps_hidden_filters: array ();
	return apply_filters ('bps_hidden_filters', $filters);
}

function bps_current_page ()
{
	$current = defined ('DOING_AJAX')?
		parse_url ($_SERVER['HTTP_REFERER'], PHP_URL_PATH):
		parse_url ($_SERVER['REQUEST_URI'], PHP_URL_PATH);

	return $current;
}

function bps_debug ()
{
	$cookie = apply_filters ('bps_cookie_name', 'bps_debug');
	return isset ($_REQUEST['bps_debug'])? true: isset ($_COOKIE[$cookie])? true: false;
}

add_action ('bp_before_directory_members_content', 'bps_display_filters');
function bps_display_filters ()
{
	$request = bps_get_request ('filters');
	if (!empty ($request))
		bps_call_template ('members/bps-filters', array ($request, true));
}

add_filter ('bp_ajax_querystring', 'bps_filter_members', 99, 2);
function bps_filter_members ($qs, $object)
{
	if (!in_array ($object, array ('members', 'group_members')))  return $qs;

	$request = bps_get_request ('search');
	if (empty ($request))  return $qs;

	$results = bps_search ($request);
	if ($results['validated'])
	{
		$args = wp_parse_args ($qs);
		$users = $results['users'];

		if (isset ($args['include']))
		{
			$included = explode (',', $args['include']);
			$users = array_intersect ($users, $included);
			if (count ($users) == 0)  $users = array (0);
		}

		$users = apply_filters ('bps_search_results', $users);
		$args['include'] = implode (',', $users);
		$qs = build_query ($args);
	}

	return $qs;
}

function bps_search ($request, $users=null)
{
	$results = array ('users' => array (0), 'validated' => true);

	$fields = bps_parse_request ($request);
	foreach ($fields as $f)
	{
		if (!isset ($f->filter))  continue;
		if (!is_callable ($f->search))  continue;

		do_action ('bps_field_before_query', $f);
		$found = call_user_func ($f->search, $f);
		$found = apply_filters ('bps_field_search_results', $found, $f);

		$match_all = apply_filters ('bps_match_all', true);
		if ($match_all)
		{
			$users = isset ($users)? array_intersect ($users, $found): $found;
			if (count ($users) == 0)  return $results;
		}
		else
		{
			$users = isset ($users)? array_merge ($users, $found): $found;
		}
	}

	if (isset ($users))
		$results['users'] = $users;
	else
		$results['validated'] = false;

	return $results;
}

function bps_esc_like ($text)
{
    return addcslashes ($text, '_%\\');
}
