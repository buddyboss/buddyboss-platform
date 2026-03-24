/**
 * Broadcast Campaigns — Gutenberg Email Builder Plugin
 */
/* global broadcastCampEditor */
( function () {
	'use strict';

	if ( typeof broadcastCampEditor === 'undefined' || ! broadcastCampEditor.return_url ) { return; }

	var el = wp.element.createElement, registerPlugin = wp.plugins.registerPlugin, subscribe = wp.data.subscribe, select = wp.data.select;

	var PluginDocumentSettingPanel = ( wp.editPost && wp.editPost.PluginDocumentSettingPanel ) || ( wp.editor && wp.editor.PluginDocumentSettingPanel );
	var PluginPrePublishPanel      = ( wp.editPost && wp.editPost.PluginPrePublishPanel )      || ( wp.editor && wp.editor.PluginPrePublishPanel );

	if ( PluginDocumentSettingPanel ) {
		registerPlugin( 'broadcast-camp-panel', {
			render: function () {
				return el( PluginDocumentSettingPanel, { name: 'broadcast-camp-panel', title: 'Broadcast Campaign', icon: 'email-alt', initialOpen: true },
					el( 'div', { className: 'bb-camp-builder-panel' },
						broadcastCampEditor.campaign_name ? el( 'p', { className: 'bb-camp-builder-label' }, broadcastCampEditor.campaign_name ) : null,
						el( 'p', { className: 'bb-camp-builder-hint' }, 'Save your email, then return to the campaign to continue.' ),
						el( 'a', { href: broadcastCampEditor.return_url, className: 'components-button is-secondary bb-camp-back-btn' }, '← Back to Campaign' )
					)
				);
			},
		} );
	}

	if ( PluginPrePublishPanel ) {
		registerPlugin( 'broadcast-camp-prepublish', {
			render: function () {
				return el( PluginPrePublishPanel, { name: 'broadcast-camp-prepublish', title: 'Campaign Email', initialOpen: true },
					el( 'p', { style: { margin: '0 0 8px', fontSize: '13px' } }, 'This email will be sent to your campaign recipients. After saving, return to the campaign to send.' ),
					el( 'a', { href: broadcastCampEditor.return_url, className: 'components-button is-secondary' }, '← Back to Campaign' )
				);
			},
		} );
	}

	var wasSaving = false, redirecting = false;
	subscribe( function () {
		if ( redirecting ) return;
		var editor = select( 'core/editor' ), isSaving = editor.isSavingPost(), isAutosave = editor.isAutosavingPost ? editor.isAutosavingPost() : false, isDirty = editor.isEditedPostDirty();
		if ( wasSaving && ! isSaving && ! isAutosave && ! isDirty ) {
			redirecting = true;
			setTimeout( function () { window.location.href = broadcastCampEditor.return_url; }, 1200 );
		}
		if ( isSaving && ! isAutosave ) { wasSaving = true; } else if ( ! isSaving ) { wasSaving = false; }
	} );
} )();
