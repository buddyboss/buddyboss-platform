/* BuddyBoss CRM Automations — Admin JS */
(function($) {
	'use strict';

	var conditionIndex = $('.bb-crm-condition-row').length;
	var actionIndex    = $('.bb-crm-action-row').length;

	// ── Trigger category picker ─────────────────────────────────────────────

	function triggerState( n ) {
		$('#bb-crm-trigger-state-0, #bb-crm-trigger-state-1, #bb-crm-trigger-state-2').hide();
		$('#bb-crm-trigger-state-' + n).show();
	}

	function triggersByCategory( catKey ) {
		var out = {};
		$.each( bbCrmAuto.triggers || {}, function( type, t ) {
			if ( t.category === catKey ) out[ type ] = t;
		} );
		return out;
	}

	function renderTriggerList( catKey ) {
		var catData = ( bbCrmAuto.triggerCategories || {} )[ catKey ] || {};
		$('#bb-crm-step2-cat-label').text( catData.label || catKey );

		var $list = $( '#bb-crm-trigger-list' ).empty();
		$.each( triggersByCategory( catKey ), function( type, t ) {
			var $item = $( '<div class="bb-crm-trigger-item">' ).attr( 'data-type', type );
			$item.append( $( '<strong>' ).text( t.label ) );
			if ( t.description ) {
				$item.append( $( '<small>' ).text( t.description ) );
			}
			$list.append( $item );
		} );
	}

	function setSelectedTrigger( type ) {
		var t       = ( bbCrmAuto.triggers || {} )[ type ];
		if ( ! t ) return;
		var catData = ( bbCrmAuto.triggerCategories || {} )[ t.category ] || {};

		$( '#bb-crm-trigger-value' ).val( type );
		$( '#bb-crm-sel-cat' )
			.text( catData.label || t.category )
			.css( 'background', catData.color || '#2271b1' );
		$( '#bb-crm-sel-trigger' ).text( t.label );
		triggerState( 2 );
	}

	// Initialise state on page load (handles edit mode).
	var _existingTrigger = $( '#bb-crm-trigger-value' ).val();
	if ( _existingTrigger && ( bbCrmAuto.triggers || {} )[ _existingTrigger ] ) {
		setSelectedTrigger( _existingTrigger );
	}

	// Category card click → show trigger list.
	$( document ).on( 'click', '.bb-crm-trigger-cat-card', function() {
		renderTriggerList( $( this ).data( 'category' ) );
		triggerState( 1 );
	} );

	// Back to category cards.
	$( document ).on( 'click', '#bb-crm-back-to-cats', function() {
		triggerState( 0 );
	} );

	// Trigger item click → set selection.
	$( document ).on( 'click', '.bb-crm-trigger-item', function() {
		setSelectedTrigger( $( this ).data( 'type' ) );
	} );

	// Change trigger → back to category picker.
	$( document ).on( 'click', '#bb-crm-change-trigger', function() {
		$( '#bb-crm-trigger-value' ).val( '' );
		triggerState( 0 );
	} );

	// ── Add Condition ──────────────────────────────────────────────────────
	$('#bb-crm-add-condition').on('click', function() {
		var i   = conditionIndex++;
		var row = $('<div class="bb-crm-condition-row" data-index="' + i + '">');

		var select = $('<select name="condition_type[' + i + ']" class="bb-crm-condition-type">');
		select.append('<option value="">— Select condition —</option>');
		$.each(bbCrmAuto.conditions, function(type, label) {
			select.append('<option value="' + type + '">' + label + '</option>');
		});

		var configDiv = $('<div class="bb-crm-condition-config">');
		var notLabel  = $('<label style="white-space:nowrap">').html(
			'<input type="checkbox" name="condition_negate[' + i + ']" value="1"> NOT'
		);
		var removeBtn = $('<button type="button" class="button button-small bb-crm-remove-condition">').text('Remove');

		row.append(select).append(configDiv).append(notLabel).append(removeBtn);
		$('#bb-crm-conditions-list').append(row);

		select.on('change', function() {
			renderConditionConfig(i, $(this).val(), configDiv);
		});
	});

	// ── Add Action ─────────────────────────────────────────────────────────
	$('#bb-crm-add-action').on('click', function() {
		var i   = actionIndex++;
		var row = $('<div class="bb-crm-action-row" data-index="' + i + '">');

		var handle    = $('<span class="bb-crm-action-handle dashicons dashicons-menu">');
		var select    = $('<select name="action_type[' + i + ']" class="bb-crm-action-type">');
		var configDiv = $('<div class="bb-crm-action-config">');
		var removeBtn = $('<button type="button" class="button button-small bb-crm-remove-action">').text('Remove');

		select.append('<option value="">— Select action —</option>');
		$.each(bbCrmAuto.actions, function(type, label) {
			if (type === 'check_condition') return; // Added via "+ Add Condition" button.
			select.append('<option value="' + type + '">' + label + '</option>');
		});

		row.append(handle).append(select).append(configDiv).append(removeBtn);
		$('#bb-crm-actions-list').append(row);

		select.on('change', function() {
			renderActionConfig(i, $(this).val(), configDiv);
		});
	});

	// ── Add Condition (inline step) ─────────────────────────────────────────
	$('#bb-crm-add-condition-step').on('click', function() {
		var i = actionIndex++;
		var row = $('<div class="bb-crm-action-row bb-crm-condition-step" data-index="' + i + '">');

		var handle    = $('<span class="bb-crm-action-handle dashicons dashicons-menu">');
		var hidden    = $('<input type="hidden" name="action_type[' + i + ']" value="check_condition">');
		var badge     = $('<span class="bb-crm-condition-step-badge">IF</span>');
		var condType  = $('<select name="action_config[' + i + '][condition_type]" class="bb-crm-inline-condition-type" data-action-index="' + i + '">');
		var notLabel  = $('<label style="white-space:nowrap;margin-top:4px">').html('<input type="checkbox" name="action_config[' + i + '][negate]" value="1"> NOT');
		var cfgDiv    = $('<div class="bb-crm-inline-condition-config" data-action-index="' + i + '">');
		var removeBtn = $('<button type="button" class="button button-small bb-crm-remove-action">').text('Remove');

		condType.append('<option value="">— Select condition —</option>');
		$.each(bbCrmAuto.conditions, function(type, label) {
			condType.append('<option value="' + type + '">' + label + '</option>');
		});

		row.append(handle).append(hidden).append(badge).append(condType).append(notLabel).append(cfgDiv).append(removeBtn);
		$('#bb-crm-actions-list').append(row);

		condType.on('change', function() {
			renderInlineConditionConfig(i, $(this).val(), cfgDiv.empty());
		});
	});

	// ── Remove rows ────────────────────────────────────────────────────────
	$(document).on('click', '.bb-crm-remove-condition', function() {
		$(this).closest('.bb-crm-condition-row').remove();
	});
	$(document).on('click', '.bb-crm-remove-action', function() {
		$(this).closest('.bb-crm-action-row').remove();
	});

	// ── Existing selects: wire up change handler ───────────────────────────
	$(document).on('change', '.bb-crm-action-type', function() {
		var row   = $(this).closest('.bb-crm-action-row');
		var i     = row.data('index');
		renderActionConfig(i, $(this).val(), row.find('.bb-crm-action-config').empty());
	});

	$(document).on('change', '.bb-crm-condition-type', function() {
		var row   = $(this).closest('.bb-crm-condition-row');
		var i     = row.data('index');
		renderConditionConfig(i, $(this).val(), row.find('.bb-crm-condition-config').empty());
	});

	// ── Render helpers ─────────────────────────────────────────────────────
	function renderActionConfig(i, type, $container) {
		$container.empty();
		switch (type) {
			case 'assign_tag':
			case 'remove_tag':
				$container.html(ajaxTagSelect('action_config[' + i + '][tag_id]'));
				break;
			case 'add_to_list':
			case 'remove_from_list':
				$container.html(ajaxListSelect('action_config[' + i + '][list_id]'));
				break;
			case 'send_email':
				$container.html(
					'<input type="text" name="action_config[' + i + '][subject]" placeholder="Subject — use {{user_name}}" class="regular-text"><br>' +
					'<textarea name="action_config[' + i + '][body]" rows="3" class="large-text" placeholder="Email body..."></textarea>' +
					'<p class="description">Merge tags: {{user_name}} {{user_email}} {{first_name}} {{site_name}} {{site_url}}</p>'
				);
				break;
			case 'call_webhook':
				$container.html('<input type="url" name="action_config[' + i + '][url]" class="regular-text" placeholder="https://...">');
				break;
			case 'log_activity':
				$container.html('<input type="text" name="action_config[' + i + '][note]" class="regular-text" placeholder="Activity note...">');
				break;
			case 'wait':
				$container.html(
					'<input type="number" name="action_config[' + i + '][amount]" value="1" min="1" style="width:70px"> ' +
					'<select name="action_config[' + i + '][unit]">' +
					'<option value="minutes">Minutes</option>' +
					'<option value="hours" selected>Hours</option>' +
					'<option value="days">Days</option>' +
					'<option value="weeks">Weeks</option>' +
					'</select>' +
					' <span style="color:#6b7280;font-size:12px">— then continue to the next action</span>'
				);
				break;
			case 'loop_repeat':
				$container.html(
					'<input type="number" name="action_config[' + i + '][amount]" value="3" min="1" style="width:70px"> ' +
					'<select name="action_config[' + i + '][unit]">' +
					'<option value="minutes">Minutes</option><option value="hours">Hours</option>' +
					'<option value="days" selected>Days</option><option value="weeks">Weeks</option>' +
					'</select>' +
					' <span style="color:#6b7280;font-size:12px">then restart — max</span> ' +
					'<input type="number" name="action_config[' + i + '][max_loops]" value="10" min="1" max="50" style="width:55px"> ' +
					'<span style="color:#6b7280;font-size:12px">loops</span>'
				);
				break;
			case 'send_campaign_email':
				$container.html('<select name="action_config[' + i + '][campaign_id]" class="bb-crm-campaign-select"><option value="">Loading campaigns…</option></select>');
				loadCampaigns($container.find('select'));
				break;
			case 'cancel_sequence':
				$container.html('<select name="action_config[' + i + '][automation_id]" class="bb-crm-automation-select"><option value="0">Loading…</option></select> <span style="color:#6b7280;font-size:12px">— cancels pending queued steps for this user</span>');
				loadAutomations($container.find('select'));
				break;
			case 'subscribe_email':
				$container.html('<span style="color:#16a34a;font-size:13px">✓ Re-subscribes the user to email campaigns.</span>');
				break;
			case 'unsubscribe_email':
				$container.html('<span style="color:#dc2626;font-size:13px">✗ Unsubscribes the user from email campaigns.</span>');
				break;
			case 'check_condition':
				var html = '<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">';
				html += '<select name="action_config[' + i + '][condition_type]" class="bb-crm-inline-condition-type" data-action-index="' + i + '">';
				html += '<option value="">— Select condition —</option>';
				$.each(bbCrmAuto.conditions, function(type, label) {
					html += '<option value="' + type + '">' + label + '</option>';
				});
				html += '</select>';
				html += '<label style="white-space:nowrap"><input type="checkbox" name="action_config[' + i + '][negate]" value="1"> NOT (stop if condition IS true)</label>';
				html += '</div>';
				html += '<div class="bb-crm-inline-condition-config" data-action-index="' + i + '"></div>';
				html += '<p class="description bb-crm-cond-hint" style="margin:4px 0 0;color:#6b7280;font-size:12px">⚠ Sequence stops if condition is false.</p>';
				$container.html(html);
				break;
		}
	}

	function renderConditionConfig(i, type, $container) {
		$container.empty();
		switch (type) {
			case 'has_tag':
			case 'not_has_tag':
				$container.html(ajaxTagSelect('condition_config[' + i + '][tag_id]'));
				break;
			case 'in_list':
			case 'not_in_list':
				$container.html(ajaxListSelect('condition_config[' + i + '][list_id]'));
				break;
			case 'user_role':
				var s = '<select name="condition_config[' + i + '][role]"><option value="">— Select role —</option>';
				// Roles populated via PHP on page load; fetch via AJAX or inline.
				s += '</select>';
				$container.html(s);
				loadRoles($container.find('select'));
				break;
			case 'registration_days':
				$container.html(
					'<input type="number" name="condition_config[' + i + '][days]" min="0" style="width:70px"> days ' +
					'<select name="condition_config[' + i + '][operator]">' +
					'<option value="greater_than">ago or more</option>' +
					'<option value="less_than">ago or less</option>' +
					'</select>'
				);
				break;
			case 'tag_count':
				$container.html(
					'<input type="number" name="condition_config[' + i + '][count]" min="0" style="width:70px"> tags ' +
					'<select name="condition_config[' + i + '][operator]">' +
					'<option value="greater_than">or more</option>' +
					'<option value="less_than">or fewer</option>' +
					'<option value="equals">exactly</option>' +
					'</select>'
				);
				break;
			case 'profile_field':
				$container.html(
					'<input type="text" name="condition_config[' + i + '][field]" placeholder="field_name" style="width:120px"> ' +
					'<select name="condition_config[' + i + '][operator]">' +
					'<option value="equals">equals</option><option value="contains">contains</option>' +
					'<option value="not_empty">not empty</option><option value="empty">is empty</option>' +
					'</select> ' +
					'<input type="text" name="condition_config[' + i + '][value]" placeholder="value" style="width:100px">'
				);
				break;
			case 'has_opened_email':
				$container.html('<select name="condition_config[' + i + '][campaign_id]" class="bb-crm-campaign-select"><option value="">Loading campaigns…</option></select>');
				loadCampaigns($container.find('select'));
				break;
		}
	}

	// Update condition hint text when NOT checkbox is toggled.
	$(document).on('change', '.bb-crm-condition-step input[type=checkbox]', function() {
		var $hint = $(this).closest('.bb-crm-action-row').find('.bb-crm-cond-hint');
		if ($(this).is(':checked')) {
			$hint.text('⚠ Sequence stops if condition IS true (NOT checked).');
		} else {
			$hint.text('⚠ Sequence stops if condition is false.');
		}
	});

	// ── Inline condition config (inside a check_condition action) ─────────
	$(document).on('change', '.bb-crm-inline-condition-type', function() {
		var i = $(this).data('action-index');
		var type = $(this).val();
		var $cfg = $(this).closest('.bb-crm-action-config').find('.bb-crm-inline-condition-config');
		renderInlineConditionConfig(i, type, $cfg.empty());
	});

	function renderInlineConditionConfig(i, type, $container) {
		var p = 'action_config[' + i + '][condition_config]';
		switch (type) {
			case 'has_tag':
			case 'not_has_tag':
				$container.html('<select name="' + p + '[tag_id]" class="bb-crm-tag-select"><option value="">Loading...</option></select>');
				break;
			case 'in_list':
			case 'not_in_list':
				$container.html('<select name="' + p + '[list_id]" class="bb-crm-list-select"><option value="">Loading...</option></select>');
				break;
			case 'user_role':
				$container.html('<select name="' + p + '[role]"><option value="">— Select role —</option></select>');
				loadRoles($container.find('select'));
				break;
			case 'has_opened_email':
				$container.html('<select name="' + p + '[campaign_id]" class="bb-crm-campaign-select"><option value="">Loading campaigns…</option></select>');
				loadCampaigns($container.find('select'));
				break;
			case 'in_group':
				$container.html('<input type="number" name="' + p + '[group_id]" placeholder="Group ID" style="width:100px">');
				break;
			case 'registration_days':
				$container.html(
					'<input type="number" name="' + p + '[days]" min="0" style="width:70px"> days ' +
					'<select name="' + p + '[operator]"><option value="greater_than">ago or more</option><option value="less_than">ago or less</option></select>'
				);
				break;
			case 'tag_count':
				$container.html(
					'<input type="number" name="' + p + '[count]" min="0" style="width:70px"> tags ' +
					'<select name="' + p + '[operator]"><option value="greater_than">or more</option><option value="less_than">or fewer</option><option value="equals">exactly</option></select>'
				);
				break;
			case 'profile_field':
				$container.html(
					'<input type="text" name="' + p + '[field]" placeholder="field_name" style="width:120px"> ' +
					'<select name="' + p + '[operator]"><option value="equals">equals</option><option value="contains">contains</option><option value="not_empty">not empty</option><option value="empty">is empty</option></select> ' +
					'<input type="text" name="' + p + '[value]" placeholder="value" style="width:100px">'
				);
				break;
		}
	}

	function loadAutomations($select) {
		$.post(bbCrmAuto.ajax_url, { action: 'bb_crm_auto_get_automations', nonce: bbCrmAuto.nonce }, function(res) {
			if (res.success) {
				$select.empty();
				$.each(res.data, function(id, label) {
					$select.append('<option value="' + id + '">' + label + '</option>');
				});
			}
		});
	}

	$(document).on('focus', '.bb-crm-automation-select', function() {
		if (!$(this).data('loaded')) {
			loadAutomations($(this));
			$(this).data('loaded', true);
		}
	});

	function loadCampaigns($select) {
		$.post(bbCrmAuto.ajax_url, { action: 'bb_crm_auto_get_campaigns', nonce: bbCrmAuto.nonce }, function(res) {
			if (res.success) {
				$select.empty().append('<option value="">— Select campaign —</option>');
				$.each(res.data, function(id, label) {
					$select.append('<option value="' + id + '">' + label + '</option>');
				});
			}
		});
	}

	$(document).on('focus', '.bb-crm-campaign-select', function() {
		if (!$(this).data('loaded')) {
			loadCampaigns($(this));
			$(this).data('loaded', true);
		}
	});

	function ajaxTagSelect(name) {
		return '<select name="' + name + '" class="bb-crm-tag-select"><option value="">Loading...</option></select>';
	}

	function ajaxListSelect(name) {
		return '<select name="' + name + '" class="bb-crm-list-select"><option value="">Loading...</option></select>';
	}

	// Load tags/lists via AJAX.
	function loadSelectData(selector, ajaxAction) {
		var $select = $(selector);
		if (!$select.length || $select.data('loaded')) return;
		$.post(bbCrmAuto.ajax_url, { action: ajaxAction, nonce: bbCrmAuto.nonce }, function(res) {
			if (res.success && res.data) {
				$select.empty().append('<option value="">— Select —</option>');
				$.each(res.data, function(id, name) {
					$select.append('<option value="' + id + '">' + name + '</option>');
				});
				$select.data('loaded', true);
			}
		});
	}

	function loadRoles($select) {
		$.post(bbCrmAuto.ajax_url, { action: 'bb_crm_auto_get_roles', nonce: bbCrmAuto.nonce }, function(res) {
			if (res.success) {
				$select.empty().append('<option value="">— Select role —</option>');
				$.each(res.data, function(key, label) {
					$select.append('<option value="' + key + '">' + label + '</option>');
				});
			}
		});
	}

	// Lazy-load tag/list dropdowns when they appear in DOM.
	$(document).on('focus', '.bb-crm-tag-select', function() {
		loadSelectData(this, 'bb_crm_auto_get_tags');
	});
	$(document).on('focus', '.bb-crm-list-select', function() {
		loadSelectData(this, 'bb_crm_auto_get_lists');
	});

	// ── Sortable actions ───────────────────────────────────────────────────
	if ($.fn.sortable) {
		$('#bb-crm-actions-list').sortable({ handle: '.bb-crm-action-handle', axis: 'y' });
	}

})(jQuery);
