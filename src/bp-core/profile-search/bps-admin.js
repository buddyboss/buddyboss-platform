
function add_field () {

	var holder = document.getElementById ('field_box');
	var theId = document.getElementById ('field_next').value;

	var newDiv = document.createElement ('div');
	newDiv.setAttribute ('id', 'field_div' + theId);
	newDiv.setAttribute ('class', 'sortable');

	var span = document.createElement ('span');
	span.setAttribute ('class', 'bps_col1');
	span.setAttribute ('title', bps_strings.drag);
	span.appendChild (document.createTextNode ("\u00A0\u21C5"));

	var $select = jQuery ("<select>", {name: 'bps_options[field_name][' + theId + ']', id: 'field_name' + theId});
	$select.addClass ('bps_col2');
	var $option = jQuery ("<option>", {text: bps_strings.field, value: 0});
	$option.appendTo ($select);

	jQuery.each (bps_groups, function (i, optgroups) {
		jQuery.each (optgroups, function (groupName, options) {
			var $optgroup = jQuery ("<optgroup>", {label: groupName});
			$optgroup.appendTo ($select);

			jQuery.each (options, function (j, option) {
				var $option = jQuery ("<option>", {text: option.name, value: option.id});
				$option.appendTo ($optgroup);
			});
		});
	});

	var toDelete = document.createElement ('a');
	toDelete.setAttribute ('href', "javascript:remove('field_div" + theId + "')");
	toDelete.setAttribute ('class', 'delete');
	toDelete.appendChild (document.createTextNode (bps_strings.remove));

	holder.appendChild (newDiv);
	newDiv.appendChild (span);
	newDiv.appendChild (document.createTextNode ("\n"));
	$select.appendTo ("#field_div" + theId);
	newDiv.appendChild (document.createTextNode ("\n"));
	newDiv.appendChild (toDelete);

	enableSortableFieldOptions ();
	document.getElementById ('field_name' + theId).focus ();
	document.getElementById ('field_next').value = ++theId;
}

function remove (id) {
	var element = document.getElementById (id);
	element.parentNode.removeChild (element);
}

function enableSortableFieldOptions () {
	jQuery ('.field_box').sortable ({
		items: 'div.sortable',
		tolerance: 'pointer',
		axis: 'y',
		handle: 'span'
	});
}

jQuery (document).ready (function () {
	enableSortableFieldOptions ();
});

jQuery(document).ready(function ($) {
	$('#template').change(function () {
		var template_spinner = $('#bps_template .spinner');
		var save_button = $('input[type=submit]');
		var data = {
			'action': 'template_options',
			'form': $('#form_id').val(),
			'template': $('#template option:selected').val()
		};

		save_button.attr('disabled', 'disabled');
		template_spinner.addClass('is-active');

		$.post (ajaxurl, data, function (new_options) {
			$('#template_options').html(new_options);
			template_spinner.removeClass('is-active');
			save_button.removeAttr('disabled');
		});
	});
});
