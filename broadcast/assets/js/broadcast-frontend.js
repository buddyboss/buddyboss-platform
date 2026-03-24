/* global broadcastData, jQuery */
(function ($) {
	'use strict';

	if (typeof broadcastData === 'undefined' || !broadcastData.announcements || !broadcastData.announcements.length) {
		return;
	}

	var announcements  = broadcastData.announcements;
	var ajaxUrl        = broadcastData.ajaxUrl;
	var nonce          = broadcastData.nonce;
	var impressionSent = {};

	// -------------------------------------------------------
	// Analytics
	// -------------------------------------------------------
	function sendEvent(announcementId, eventType, callback) {
		$.post(ajaxUrl, {
			action:          'broadcast_event',
			nonce:           nonce,
			announcement_id: announcementId,
			event_type:      eventType
		}, function () {
			if (typeof callback === 'function') callback();
		});
	}

	function sendImpression(announcementId) {
		if (impressionSent[announcementId]) return; // client-side debounce per page load
		impressionSent[announcementId] = true;
		sendEvent(announcementId, 'impression');
	}

	// -------------------------------------------------------
	// Close helpers
	// -------------------------------------------------------
	function sendDismiss(announcementId) {
		$.post(ajaxUrl, {
			action:          'broadcast_dismiss',
			nonce:           nonce,
			announcement_id: announcementId,
		});
	}

	function fadeOutOverlay($overlay, done) {
		$overlay.css('opacity', '1').animate({ opacity: 0 }, 200, function () {
			$overlay.remove();
			if (done) done();
		});
	}

	function slideOutBanner($banner, done) {
		$banner.animate({ opacity: 0 }, 200, function () {
			$banner.remove();
			if (done) done();
		});
	}

	// -------------------------------------------------------
	// Popup
	// -------------------------------------------------------
	function renderPopup(ann) {
		var posClass = 'broadcast-pos-' + (ann.display_position || 'middle');
		var $overlay = $('<div id="broadcast-popup-overlay" class="' + posClass + '" role="dialog" aria-modal="true" aria-labelledby="broadcast-popup-title-' + ann.id + '"></div>');
		var $popup   = $('<div id="broadcast-popup"></div>');

		if (ann.image_url) {
			$popup.append('<img class="broadcast-popup-image" src="' + ann.image_url + '" alt="">');
		}
		if (ann.title) {
			$popup.append('<p class="broadcast-popup-title" id="broadcast-popup-title-' + ann.id + '">' + ann.title + '</p>');
		}
		if (ann.body) {
			$popup.append('<p class="broadcast-popup-body">' + ann.body + '</p>');
		}
		if (ann.cta_label && ann.cta_url) {
			$popup.append(
				$('<a class="broadcast-cta-btn" target="_blank" rel="noopener noreferrer">' + ann.cta_label + '</a>')
					.attr('href', ann.cta_url)
					.on('click', function (e) {
						e.preventDefault();
						sendEvent(ann.id, 'cta_click', function () {
							window.open(ann.cta_url, '_blank', 'noopener,noreferrer');
						});
						fadeOutOverlay($overlay);
					})
			);
		}
		if (ann.closeable) {
			$popup.append(
				$('<button class="broadcast-close-btn" aria-label="Dismiss announcement"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>')
					.on('click', function () {
						sendImpression(ann.id);
						sendDismiss(ann.id);
						fadeOutOverlay($overlay);
					})
			);
		}

		$overlay.append($popup);
		$('body').append($overlay);

		// Fade in after 500ms delay, then fire impression.
		setTimeout(function () {
			$overlay.addClass('broadcast-visible');
			sendImpression(ann.id);
		}, 500);
	}

	// -------------------------------------------------------
	// Banner
	// -------------------------------------------------------
	function renderBanner(ann) {
		var isBottom    = ann.display_position === 'bottom';
		var bannerClass = 'broadcast-banner' + (isBottom ? ' broadcast-banner-bottom' : '');
		var $banner     = $('<div id="broadcast-banner" class="' + bannerClass + '"></div>');

		if (ann.image_url) {
			$banner.append('<img class="broadcast-banner-image" src="' + ann.image_url + '" alt="">');
		}
		$banner.append('<span class="broadcast-banner-message">' + (ann.body || ann.title || '') + '</span>');

		if (ann.cta_label && ann.cta_url) {
			$banner.append(
				$('<a class="broadcast-cta-btn" target="_blank" rel="noopener noreferrer">' + ann.cta_label + '</a>')
					.attr('href', ann.cta_url)
					.on('click', function (e) {
						e.preventDefault();
						sendEvent(ann.id, 'cta_click', function () {
							window.open(ann.cta_url, '_blank', 'noopener,noreferrer');
						});
						slideOutBanner($banner);
					})
			);
		}
		if (ann.closeable) {
			$banner.append(
				$('<button class="broadcast-close-btn" aria-label="Dismiss announcement"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>')
					.on('click', function () {
						sendImpression(ann.id);
						sendDismiss(ann.id);
						slideOutBanner($banner);
					})
			);
		}

		$('body').append($banner);

		// Slide in after 200ms delay, then fire impression.
		setTimeout(function () {
			$banner.addClass('broadcast-visible');
			sendImpression(ann.id);
		}, 200);
	}

	// -------------------------------------------------------
	// Initialise
	// -------------------------------------------------------
	$(function () {
		$.each(announcements, function (i, ann) {
			if (ann.type === 'banner') {
				renderBanner(ann);
			} else {
				renderPopup(ann);
			}
		});
	});

}(jQuery));
