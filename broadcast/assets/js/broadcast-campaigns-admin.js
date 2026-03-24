/**
 * Broadcast Campaigns — Admin JS
 */
/* global broadcastCampaigns */
( function ( $ ) {
	'use strict';

	var currentStep = 1;

	function goToStep( step ) {
		$( '.bb-camp-step' ).hide();
		$( '#bb-step-' + step ).show();
		$( '.bb-camp-step-btn' ).removeClass( 'is-active is-done' );
		$( '.bb-camp-step-btn' ).each( function () {
			var n = parseInt( $( this ).data( 'step' ), 10 );
			if ( n < step ) { $( this ).addClass( 'is-done' ); }
			else if ( n === step ) { $( this ).addClass( 'is-active' ); }
		} );
		if ( 3 === step ) { populateReview(); }
		currentStep = step;
		var navTop = $( '#bb-camp-wizard-nav' ).offset();
		if ( navTop ) { $( 'html, body' ).animate( { scrollTop: navTop.top - 32 }, 200 ); }
	}

	$( document ).on( 'click', '.bb-camp-next', function () { goToStep( parseInt( $( this ).data( 'next' ), 10 ) ); } );
	$( document ).on( 'click', '.bb-camp-prev', function () { goToStep( parseInt( $( this ).data( 'prev' ), 10 ) ); } );
	$( document ).on( 'click', '.bb-camp-step-btn', function () { goToStep( parseInt( $( this ).data( 'step' ), 10 ) ); } );

	function populateReview() {
		var name      = $( '#bb-camp-name' ).val() || '—';
		var subject   = $( '#bb-camp-subject' ).val() || '—';
		var preheader = $( '#bb-camp-preheader' ).val() || '—';
		var fromName  = $( '#bb-camp-from-name' ).val() || '';
		var fromEmail = $( '#bb-camp-from-email' ).val() || '';
		var fromStr   = fromName && fromEmail ? fromName + ' <' + fromEmail + '>' : ( fromEmail || '—' );
		var hasContent = $( '.bb-camp-body-status.is-has-content' ).length > 0;
		var bodyStatus = hasContent ? '✓ Email body built in block editor' : '⚠ No email body yet — open the Email Builder';
		$( '#review-name' ).text( name );
		$( '#review-subject' ).text( subject );
		$( '#review-preheader' ).text( preheader );
		$( '#review-from' ).text( fromStr );
		$( '#review-body-status' ).text( bodyStatus );
	}

	$( document ).on( 'click', '.bb-camp-merge-tags-toggle', function () {
		var $list = $( this ).siblings( '.bb-camp-merge-tags-list' );
		var $ind  = $( this ).find( '.toggle-indicator' );
		$list.slideToggle( 150, function () { $ind.text( $list.is( ':visible' ) ? '▲' : '▼' ); } );
	} );

	$( document ).on( 'click', '.bb-camp-send-btn', function ( e ) {
		var msg = $( this ).data( 'confirm' ) || ( typeof broadcastCampaigns !== 'undefined' ? broadcastCampaigns.confirm_send : 'Send this campaign now?' );
		if ( ! window.confirm( msg ) ) { e.preventDefault(); return false; }
	} );

	$( document ).on( 'click', '.bb-camp-delete-btn', function ( e ) {
		var msg = $( this ).data( 'confirm' ) || ( typeof broadcastCampaigns !== 'undefined' ? broadcastCampaigns.confirm_delete : 'Delete?' );
		if ( ! window.confirm( msg ) ) { e.preventDefault(); return false; }
	} );

	$( document ).on( 'click', '#bb-send-test-btn', function () {
		var email      = $( '#bb-test-email-addr' ).val().trim();
		var campaignId = $( this ).data( 'campaign' );
		var $result    = $( '#bb-test-email-result' );
		var $btn       = $( this );
		if ( ! email ) { $result.text( 'Please enter an email address.' ).css( 'color', '#ef4444' ).show(); return; }
		$btn.prop( 'disabled', true ).text( typeof broadcastCampaigns !== 'undefined' ? broadcastCampaigns.sending : 'Sending…' );
		$result.hide();
		$.post( broadcastCampaigns.ajax_url, {
			action: 'broadcast_camp_send_test', nonce: broadcastCampaigns.nonce,
			campaign_id: campaignId, test_email: email,
		}, function ( response ) {
			if ( response.success ) { $result.text( response.data ).css( 'color', '#10b981' ).show(); }
			else { $result.text( response.data ).css( 'color', '#ef4444' ).show(); }
		} ).fail( function () {
			$result.text( typeof broadcastCampaigns !== 'undefined' ? broadcastCampaigns.sent_fail : 'Request failed.' ).css( 'color', '#ef4444' ).show();
		} ).always( function () { $btn.prop( 'disabled', false ).text( 'Send Test' ); } );
	} );

	var progressPollers = {};

	function startProgressPolling( campaignId ) {
		if ( progressPollers[ campaignId ] ) return;
		progressPollers[ campaignId ] = setInterval( function () {
			if ( typeof broadcastCampaigns === 'undefined' || ! broadcastCampaigns.progress_nonce ) return;
			$.post( broadcastCampaigns.ajax_url, {
				action: 'broadcast_camp_send_progress', nonce: broadcastCampaigns.progress_nonce, campaign_id: campaignId,
			}, function ( response ) {
				if ( ! response.success ) return;
				var data   = response.data;
				var $row   = $( '.bb-camp-progress-row[data-campaign-id="' + campaignId + '"]' );
				$row.find( '.bb-camp-progress-bar-fill' ).css( 'width', data.pct + '%' );
				$row.find( '.bb-camp-progress-label' ).text( data.pct + '% (' + data.done + '/' + data.total + ' batches)' );
				if ( data.status === 'sent' || data.status === 'failed' ) {
					clearInterval( progressPollers[ campaignId ] );
					delete progressPollers[ campaignId ];
					window.location.reload();
				}
			} );
		}, 5000 );
	}

	$( function () {
		$( '.bb-camp-progress-row' ).each( function () {
			var status = $( this ).data( 'status' ), campaignId = $( this ).data( 'campaign-id' );
			if ( campaignId && ( status === 'queued' || status === 'sending' ) ) { startProgressPolling( campaignId ); }
		} );
	} );

} )( jQuery );
