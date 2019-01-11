jQuery(document).ready( function() {
	var j = jQuery;

	var invitationsLoad = [];
	var submitClicked = false;

	var onAutocompleteSelect = function(value, data) {
		j('#selection').html('<img src="\/global\/flags\/small\/' + data + '.png" alt="" \/> ' + value);
	//alert(data);
	}

	var options = {
		serviceUrl: ajaxurl,
		width: 300,
		delimiter: /(,|;)\s*/,
		onSelect: ia_on_autocomplete_select,
		deferRequestBy: 0, //miliseconds
		params: { action: 'invite_anyone_autocomplete_ajax_handler' },
		noCache: true //set to true, to disable caching
	};

	a = j('#create-group-form #send-to-input, #send-invite-form #send-to-input').devbridgeAutocomplete(options);

	// Check whether the "submit" button should be enabled on page load. Set the state.
	ia_refresh_submit_button_state();

	j("div#invite-anyone-member-list input").click(function() {
		var friend_id = j(this).val();

		if ( j(this).prop('checked') == true ) {
			var friend_action = 'invite';
		} else {
			var friend_action = 'uninvite';
		}

		j.post( ajaxurl, {
			action: 'invite_anyone_groups_invite_user',
			'friend_action': friend_action,
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
			'friend_id': friend_id,
			'group_id': j("input#group_id").val()
		},
		function(response)
		{
			if ( j("#message") )
				j("#message").hide();

			if ( friend_action == 'invite' ) {
				j('#invite-anyone-invite-list').append(response);
			} else if ( friend_action == 'uninvite' ) {
				j('#invite-anyone-invite-list li#uid-' + friend_id).remove();
			}

			ia_refresh_submit_button_state();
		});
	});

	j("#invite-anyone-invite-list").on( 'click', 'li a.remove', function() {
		var friend_id = j(this).prop('id');

		friend_id = friend_id.split('-');
		friend_id = friend_id[1];

		j.post( ajaxurl, {
			action: 'invite_anyone_groups_invite_user',
			'friend_action': 'uninvite',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
			'friend_id': friend_id,
			'group_id': j("input#group_id").val()
		},
		function(response)
		{
			j('#invite-anyone-invite-list li#uid-' + friend_id).remove();
			j('#invite-anyone-member-list input#f-' + friend_id).prop('checked', false);
			ia_refresh_submit_button_state();
		});

		return false;
	});

	j("#invite-anyone-link").click(
		function() {

			j('.ajax-loader').toggle();

			var friend_id = j(this).val();

			if ( j(this).prop('checked') == true ) {
				var friend_action = 'invite';
			} else {
				var friend_action = 'uninvite';
			}


			j.post( ajaxurl, {
				action: 'invite_anyone_groups_invite_user',
				'friend_action': friend_action,
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
				'friend_id': friend_id,
				'group_id': j("input#group_id").val()
			},
			function(response)
			{
				if ( j("#message") )
					j("#message").hide();

				j('.ajax-loader').toggle();

				if ( friend_action == 'invite' ) {
					j('#invite-anyone-member-list').append(response);
				} else if ( friend_action == 'uninvite' ) {
					j('#invite-anyone-member-list li#uid-' + friend_id).remove();
				}
			});
		}
	);

	j("#send-invite-form").on( 'focus', '#send-to-input', function() {
		j( '#submit' ).prop('disabled', true);
	});

	j("#send-invite-form").on( 'blur', '#send-to-input', function() {
		ia_refresh_submit_button_state();
	});

	// Watch the invitation list to see if it changes (then we'll need to prompt the user for confimration before leaving the page).
	// Set up an array of the list of invitations visible at page load.
	j('#invite-anyone-invite-list').find('li').each(function(index,value) {
	    invitationsLoad.push( j(this).attr('id') );
	});

	jq('#send-invite-form input:submit,#create-group-form input:submit').on( 'click', function() {
		submitClicked = true;
	});

	window.onbeforeunload = function(e) {
		if ( submitClicked == false ) {
			// Set up the current invitations list (and empty it).
			var invitationsCurrent = [];

			j('#invite-anyone-invite-list').find('li').each(function(index,value) {
			    invitationsCurrent.push( j(this).attr('id') );
			});

			// See if the current invite list contains objects not in the load list
			if ( j( invitationsCurrent ).not( invitationsLoad ).length != 0 ) {
				return IA_js_strings.unsent_invites;
			}
		}
	};

});

function ia_on_autocomplete_select( value, data ) {
	var j = jQuery;

	// Check the right checkbox
	j('#invite-anyone-member-list input#f-' + data).prop('checked',true);

	// Put the item in the invite list
	j('div.item-list-tabs li.selected').addClass('loading');

	j.post( ajaxurl, {
		action: 'invite_anyone_groups_invite_user',
		'friend_action': 'invite',
		'cookie': encodeURIComponent(document.cookie),
		'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
		'friend_id': data,
		'group_id': j("input#group_id").val()
	},
	function(response)
	{
		if ( j("#message") )
			j("#message").hide();

		j('.ajax-loader').toggle();

		j('#invite-anyone-invite-list').append(response);

		j('div.item-list-tabs li.selected').removeClass('loading');

		// Refresh the submit button state
		ia_refresh_submit_button_state();
	});

	// Remove the value from the send-to-input box
	j('#send-to-input').val('');
}

function ia_refresh_submit_button_state(){
	var j = jQuery;

	var invites = j( '#invite-anyone-invite-list li' ).length;

	if ( invites ) {
		j( '#submit' ).prop( 'disabled', false ).removeClass( 'submit-disabled' );
	} else {
		j( '#submit' ).prop( 'disabled', true ).addClass( 'submit-disabled' );
	}
}
