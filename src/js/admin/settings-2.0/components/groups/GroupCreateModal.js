/**
 * BuddyBoss Admin Settings 2.0 - Group Create Modal
 *
 * Simple modal with Name, Permalink, Description, Privacy fields for creating a group.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState } from '@wordpress/element';
import {
	Modal,
	Button,
	TextControl,
	SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { createGroup } from '../../utils/ajax';
import { RichTextEditor } from '../common/RichTextEditor';

/**
 * Sanitize a string into a URL-friendly slug.
 *
 * Note: Non-Latin characters (e.g. Arabic, Chinese) are stripped by the
 * `/[^a-z0-9\s-]/g` regex, resulting in an empty slug for fully non-Latin
 * names. The server (`sanitize_title()`) will also adjust the slug and may
 * produce a different result, so this preview slug is indicative only.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {string} str Input string.
 * @returns {string} Slug.
 */
function toSlug( str ) {
	return str
		.toLowerCase()
		.replace( /[^a-z0-9\s-]/g, '' )
		.replace( /[\s]+/g, '-' )
		.replace( /-+/g, '-' )
		.replace( /^-|-$/g, '' );
}

/**
 * Group Create Modal Component
 *
 * @param {Object}   props           Component props.
 * @param {boolean}  props.isOpen    Whether the modal is open.
 * @param {Function} props.onClose   Close handler.
 * @param {Function} props.onCreated Success handler (receives group_id).
 * @returns {JSX.Element|null} Modal component or null.
 */
export function GroupCreateModal( { isOpen, onClose, onCreated } ) {
	var nameState = useState( '' );
	var name = nameState[ 0 ];
	var setName = nameState[ 1 ];

	var permalinkState = useState( '' );
	var permalink = permalinkState[ 0 ];
	var setPermalink = permalinkState[ 1 ];

	var permalinkEditedState = useState( false );
	var permalinkEdited = permalinkEditedState[ 0 ];
	var setPermalinkEdited = permalinkEditedState[ 1 ];

	var descriptionState = useState( '' );
	var description = descriptionState[ 0 ];
	var setDescription = descriptionState[ 1 ];

	var statusState = useState( 'public' );
	var status = statusState[ 0 ];
	var setStatus = statusState[ 1 ];

	var isSavingState = useState( false );
	var isSaving = isSavingState[ 0 ];
	var setIsSaving = isSavingState[ 1 ];

	var errorState = useState( '' );
	var error = errorState[ 0 ];
	var setError = errorState[ 1 ];

	if ( ! isOpen ) {
		return null;
	}

	/**
	 * Handle name change — auto-generate permalink if not manually edited.
	 *
	 * @param {string} val New name value.
	 */
	var handleNameChange = function ( val ) {
		setName( val );
		if ( ! permalinkEdited ) {
			setPermalink( toSlug( val ) );
		}
	};

	/**
	 * Handle permalink change — mark as manually edited.
	 *
	 * @param {string} val New permalink value.
	 */
	var handlePermalinkChange = function ( val ) {
		setPermalink( toSlug( val ) );
		setPermalinkEdited( true );
	};

	/**
	 * Handle group creation form submission.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleCreate = function () {
		if ( ! name.trim() ) {
			setError( __( 'Group name is required.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		// Pull latest content from TinyMCE editor.
		var descriptionVal = description;
		if ( window.tinymce ) {
			var editorInstance = window.tinymce.get( 'bb-admin-create-group-description' );
			if ( editorInstance ) {
				descriptionVal = editorInstance.getContent();
			}
		}

		createGroup( {
			name: name.trim(),
			slug: permalink,
			description: descriptionVal,
			status: status,
		} ).then( function ( response ) {
			setIsSaving( false );
			if ( response.success ) {
				// Reset form.
				setName( '' );
				setPermalink( '' );
				setPermalinkEdited( false );
				setDescription( '' );
				setStatus( 'public' );
				if ( onCreated ) {
					onCreated( response.data.group_id );
				}
			} else {
				setError( response.data?.message || __( 'Failed to create group.', 'buddyboss' ) );
			}
		} ).catch( function () {
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	/**
	 * Handle modal close and reset form state.
	 *
	 * @since BuddyBoss [BBVERSION]
	 */
	var handleClose = function () {
		setName( '' );
		setPermalink( '' );
		setPermalinkEdited( false );
		setDescription( '' );
		setStatus( 'public' );
		setError( '' );
		onClose();
	};

	var privacyOptions = [
		{ value: 'public', label: __( 'Public', 'buddyboss' ) },
		{ value: 'private', label: __( 'Private', 'buddyboss' ) },
		{ value: 'hidden', label: __( 'Hidden', 'buddyboss' ) },
	];

	return (
		<Modal
			title={ __( 'Create New Group', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-group-create-modal bb-admin-settings-modal"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-group-create-modal__body">
				{ error && (
					<p className="bb-group-create-modal__error">{ error }</p>
				) }

				<TextControl
					label={ __( 'Name', 'buddyboss' ) }
					value={ name }
					onChange={ handleNameChange }
					placeholder={ __( 'Enter group name', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>

				<TextControl
					label={ __( 'Permalink', 'buddyboss' ) }
					value={ permalink }
					onChange={ handlePermalinkChange }
					placeholder={ __( 'group-slug', 'buddyboss' ) }
					__nextHasNoMarginBottom
				/>

				<RichTextEditor
					id="bb-admin-create-group-description"
					label={ __( 'Description (Optional)', 'buddyboss' ) }
					value={ description }
					onChange={ setDescription }
				/>

				<SelectControl
					label={ __( 'Group Privacy', 'buddyboss' ) }
					value={ status }
					options={ privacyOptions }
					onChange={ setStatus }
					__nextHasNoMarginBottom
				/>
			</div>

			<div className="bb-group-create-modal__footer bb-admin-settings-modal__footer">
				<Button
					variant="secondary"
					onClick={ handleClose }
					disabled={ isSaving }
				>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button
					variant="primary"
					onClick={ handleCreate }
					isBusy={ isSaving }
					disabled={ isSaving || ! name.trim() }
				>
					{ __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
