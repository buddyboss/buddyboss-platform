/**
 * Drives the "Register your email and get Memberships & Courses" banner's
 * free-license registration modal on the BuddyBoss Settings page.
 *
 * The banner markup is server-rendered by bb_admin_render_register_banner()
 * inside .bb-admin-settings-wrap, before the React mount — so this script
 * only binds behaviour; it never injects markup. Only enqueued in 'pitch'
 * mode (see bb_admin_register_banner_enqueue()).
 *
 * The modal collects First/Last/Email and posts to Platform's own
 * `bb_get_free_license` AJAX action — the existing Mothership issuance flow.
 *
 * @since BuddyBoss [BBVERSION]
 */
( function () {
	'use strict';

	var cfg = window.bbRegBanner || {};

	function i18n( key ) {
		return ( cfg.i18n && cfg.i18n[ key ] ) || '';
	}

	function bind() {
		var banner = document.querySelector( '.bb-reg-banner-holder' );
		if ( ! banner ) {
			return;
		}

		var openBtn = banner.querySelector( '[data-bb-reg-open]' );
		var modal = banner.querySelector( '[data-bb-reg-modal]' );
		if ( ! openBtn || ! modal ) {
			return;
		}

		var form = modal.querySelector( '[data-bb-reg-form]' );
		var inlineError = modal.querySelector( '[data-bb-reg-inline-error]' );
		var reqState = { controller: null };

		function abort() {
			if ( reqState.controller ) {
				reqState.controller.abort();
				reqState.controller = null;
			}
		}

		openBtn.addEventListener( 'click', function () {
			openModal( modal, form );
		} );

		// Close via overlay, header ✕, or Cancel — aborts any in-flight request
		// and just hides the modal (the banner stays; nothing to reload).
		modal.querySelectorAll( '[data-bb-reg-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				abort();
				closeModal( modal );
			} );
		} );

		// Result "Close" (success, already-registered, error) — close the modal.
		// The pitch → notice flip already happened in markRegistered(); the
		// banner disappears entirely only on real license activation, which the
		// server-side mode resolver handles on the next load.
		modal.querySelectorAll( '[data-bb-reg-done]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				closeModal( modal );
			} );
		} );

		// Cancel during loading — abort the request and return to the form.
		var cancelLoading = modal.querySelector( '[data-bb-reg-cancel-loading]' );
		if ( cancelLoading ) {
			cancelLoading.addEventListener( 'click', function () {
				abort();
				showState( modal, 'form' );
			} );
		}

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key && ! modal.hidden ) {
				abort();
				closeModal( modal );
			}
		} );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			submit( modal, form, inlineError, reqState );
		} );
	}

	function openModal( modal, form ) {
		if ( form ) {
			form.reset();
		}
		// Clear any stale inline validation error from a previous failed submit.
		hide( modal.querySelector( '[data-bb-reg-inline-error]' ) );
		showState( modal, 'form' );
		modal.hidden = false;
		var first = modal.querySelector( '[data-bb-reg-first]' );
		if ( first ) {
			first.focus();
		}
	}

	function closeModal( modal ) {
		modal.hidden = true;
		showState( modal, 'form' );
	}

	function submit( modal, form, inlineError, reqState ) {
		hide( inlineError );

		var first = value( form, '[data-bb-reg-first]' );
		var last = value( form, '[data-bb-reg-last]' );
		var email = value( form, '[data-bb-reg-email]' );

		if ( ! first || ! last || ! email ) {
			return showInline( inlineError, i18n( 'requiredFields' ) );
		}
		if ( email.indexOf( '@' ) === -1 ) {
			return showInline( inlineError, i18n( 'invalidEmail' ) );
		}

		showState( modal, 'loading' );

		var body = new window.URLSearchParams();
		body.append( 'action', cfg.action || 'bb_get_free_license' );
		body.append( 'nonce', cfg.nonce || '' );
		body.append( 'first_name', first );
		body.append( 'last_name', last );
		body.append( 'email', email );

		reqState.controller = new window.AbortController();

		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString(),
			signal: reqState.controller.signal
		} ).then( function ( r ) {
			return r.json();
		} ).then( function ( res ) {
			reqState.controller = null;
			var msg = messageOf( res && res.data, '' );
			// An already-registered email gets its own state (static copy +
			// account link). Platform forwards the mothership's response
			// verbatim and can return success:true with an "already registered"
			// message for an existing email, so check the message on BOTH
			// branches — not just the error path — before falling through to
			// success / generic error.
			if ( /already/i.test( msg ) ) {
				markRegistered();
				showState( modal, 'already' );
				return;
			}
			if ( res && res.success ) {
				markRegistered();
				showState( modal, 'success' );
				return;
			}
			setText( modal, '[data-bb-reg-error-msg]', msg || i18n( 'genericError' ) );
			showState( modal, 'error' );
		} ).catch( function ( err ) {
			reqState.controller = null;
			// User cancelled — the close/cancel handler already reset the view.
			if ( err && 'AbortError' === err.name ) {
				return;
			}
			setText( modal, '[data-bb-reg-error-msg]', i18n( 'genericError' ) );
			showState( modal, 'error' );
		} );
	}

	// The admin has registered (or already had a registration): persist the
	// flag server-side so the notice mode renders on future loads, and flip
	// the banner from pitch to notice in the current page (reveal the
	// activate-license strip, hide the register CTA — the banner itself stays)
	// — the same DOM the server renders for 'notice' mode. Fire-and-forget —
	// the modal result UI does not depend on this write. The banner disappears
	// entirely only on real license activation.
	function markRegistered() {
		var notice = document.querySelector( '[data-bb-reg-notice]' );
		if ( notice ) {
			notice.hidden = false;
		}
		document.querySelectorAll( '.bb-reg-cta' ).forEach( function ( el ) {
			el.hidden = true;
		} );
		if ( ! cfg.markAction || ! cfg.markNonce ) {
			return;
		}
		var body = new window.URLSearchParams();
		body.append( 'action', cfg.markAction );
		body.append( 'nonce', cfg.markNonce );
		window.fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: body.toString()
		} ).catch( function () {} );
	}

	// Platform's handler returns either a string or an object with a message;
	// normalise both to a display string, falling back to our i18n default.
	function messageOf( data, fallback ) {
		if ( 'string' === typeof data && data ) {
			return data;
		}
		if ( data && 'string' === typeof data.message && data.message ) {
			return data.message;
		}
		return fallback;
	}

	function showState( modal, state ) {
		var form = modal.querySelector( '[data-bb-reg-form]' );
		if ( form ) {
			form.hidden = ( 'form' !== state );
		}
		modal.querySelectorAll( '[data-bb-reg-state]' ).forEach( function ( el ) {
			el.hidden = ( el.getAttribute( 'data-bb-reg-state' ) !== state );
		} );
	}

	function value( scope, selector ) {
		var el = scope.querySelector( selector );
		return el ? String( el.value ).trim() : '';
	}

	function setText( scope, selector, text ) {
		var el = scope.querySelector( selector );
		if ( el ) {
			el.textContent = text || '';
		}
	}

	function showInline( el, msg ) {
		if ( el ) {
			el.textContent = msg || '';
			el.hidden = false;
		}
	}

	function hide( el ) {
		if ( el ) {
			el.hidden = true;
		}
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', bind );
	} else {
		bind();
	}
} )();
