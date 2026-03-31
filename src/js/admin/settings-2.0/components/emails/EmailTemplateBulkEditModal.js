/**
 * BuddyBoss Admin Settings 2.0 - Email Template Bulk Edit Modal
 *
 * Modal for bulk-editing situation and status on multiple email templates.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import {
	Modal,
	Button,
	CheckboxControl,
	SelectControl,
	TabPanel,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { bulkEditEmailTemplates, getEmailSituations } from '../../utils/ajax';

// Situations cache is managed in utils/ajax.js (shared across all modals).

/**
 * Email Template Bulk Edit Modal Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props               Component props.
 * @param {boolean}  props.isOpen        Whether the modal is open.
 * @param {Array}    props.selectedItems Selected items [{id, title}].
 * @param {Function} props.onClose       Close handler.
 * @param {Function} props.onSaved       Success handler.
 * @returns {JSX.Element|null} Modal or null.
 */
export function EmailTemplateBulkEditModal( { isOpen, selectedItems, onClose, onSaved } ) {
	var localItemsState = useState( [] );
	var localItems = localItemsState[0];
	var setLocalItems = localItemsState[1];

	var statusState = useState( '' );
	var status = statusState[0];
	var setStatus = statusState[1];

	var emailTypeState = useState( '' );
	var emailType = emailTypeState[0];
	var setEmailType = emailTypeState[1];

	var situationsState = useState( null );
	var situations = situationsState[0];
	var setSituations = situationsState[1];

	var isSavingState = useState( false );
	var isSaving = isSavingState[0];
	var setIsSaving = isSavingState[1];

	var errorState = useState( '' );
	var error = errorState[0];
	var setError = errorState[1];

	var isMountedRef = useRef( true );

	useEffect( function () {
		isMountedRef.current = true;
		return function () {
			isMountedRef.current = false;
		};
	}, [] );

	// Sync local items from parent selection when modal opens.
	useEffect( function () {
		if ( isOpen && selectedItems ) {
			setLocalItems( selectedItems );
		}
	}, [ isOpen, selectedItems ] );

	// Fetch situations.
	useEffect( function () {
		if ( ! isOpen ) {
			return;
		}

		getEmailSituations().then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			if ( response.success && response.data ) {
				setSituations( response.data );
			}
		} ).catch( function () {} );
	}, [ isOpen ] );

	if ( ! isOpen ) {
		return null;
	}

	var handleClose = function () {
		setStatus( '' );
		setEmailType( '' );
		setError( '' );
		setLocalItems( [] );
		onClose();
	};

	var handleSave = function () {
		if ( isSaving ) {
			return;
		}
		if ( ! status && ! emailType ) {
			setError( __( 'Please select at least one change to apply.', 'buddyboss' ) );
			return;
		}

		setIsSaving( true );
		setError( '' );

		bulkEditEmailTemplates( {
			email_ids: localItems.map( function ( item ) { return item.id; } ).join( ',' ),
			status: status,
			email_type: emailType,
		} ).then( function ( response ) {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			if ( response.success ) {
				setStatus( '' );
				setEmailType( '' );
				if ( onSaved ) {
					onSaved( response.data );
				}
			} else {
				setError( ( response.data && response.data.message ) || __( 'Failed to update.', 'buddyboss' ) );
			}
		} ).catch( function () {
			if ( ! isMountedRef.current ) {
				return;
			}
			setIsSaving( false );
			setError( __( 'An error occurred. Please try again.', 'buddyboss' ) );
		} );
	};

	// Check if situations are loaded.
	var hasSituations = situations && Object.keys( situations ).length > 0;

	// Build situation tabs.
	var situationTabs = [];
	if ( situations ) {
		Object.keys( situations ).forEach( function ( catKey ) {
			situationTabs.push( {
				name: catKey,
				title: situations[ catKey ].label,
				className: 'bb-email-template-modal__situation-tab',
			} );
		} );
	}

	var statusOptions = [
		{ label: __( 'No Change', 'buddyboss' ), value: '' },
		{ label: __( 'Published', 'buddyboss' ), value: 'publish' },
		{ label: __( 'Draft', 'buddyboss' ), value: 'draft' },
		{ label: __( 'Pending Review', 'buddyboss' ), value: 'pending' },
	];

	return (
		<Modal
			title={ __( 'Bulk Edit', 'buddyboss' ) }
			onRequestClose={ handleClose }
			className="bb-admin-settings-modal bb-email-template-modal bb-email-template-modal--bulk-edit"
			shouldCloseOnClickOutside={ false }
		>
			<div className="bb-admin-settings-modal__body bb-email-template-modal__body">
				{ error && (
					<p className="bb-admin-settings-modal__error">{ error }</p>
				) }

				{/* Selected items */}
				<div className="bb-admin-bulk-modal__selected-items">
					{ localItems.map( function ( item ) {
						return (
							<div key={ item.id } className="bb-admin-bulk-modal__selected-item">
								<CheckboxControl
									checked={ true }
									onChange={ function () {
										setLocalItems( function ( prev ) {
											return prev.filter( function ( i ) {
												return i.id !== item.id;
											} );
										} );
									} }
									__nextHasNoMarginBottom
								/>
								<span className="bb-admin-bulk-modal__selected-item-name">
									{ decodeEntities( item.title ) }
								</span>
							</div>
						);
					} ) }
				</div>

				{/* Situation tabs */}
				{ ! situations && (
					<div className="bb-email-template-modal__field bb-email-template-modal__situation">
						<label className="bb-email-template-modal__field-label">
							{ __( 'Situation', 'buddyboss' ) }
						</label>
						<div className="bb-email-template-modal__loading">
							<Spinner />
						</div>
					</div>
				) }
				{ situationTabs.length > 0 && (
					<div className="bb-email-template-modal__field bb-email-template-modal__situation">
						<label className="bb-email-template-modal__field-label">
							{ __( 'Situation', 'buddyboss' ) }
						</label>
						<TabPanel
							className="bb-email-template-modal__situation-tabs"
							tabs={ situationTabs }
						>
							{ function ( tab ) {
								var catTerms = situations[ tab.name ] ? situations[ tab.name ].terms : [];
								return (
									<div className="bb-email-template-modal__situation-list">
										{ catTerms.map( function ( term ) {
											return (
												<label key={ term.slug } className="bb-email-template-modal__situation-item">
													<input
														type="checkbox"
														checked={ emailType === term.slug }
														onChange={ function () {
															setEmailType( emailType === term.slug ? '' : term.slug );
														} }
													/>
													<span>{ decodeEntities( term.description || term.slug ) }</span>
												</label>
											);
										} ) }
									</div>
								);
							} }
						</TabPanel>
						<p className="bb-email-template-modal__field-help">
							{ __( 'Choose when this email will be sent.', 'buddyboss' ) }
						</p>
					</div>
				) }

				{/* Status */}
				<div className="bb-email-template-modal__field bb-email-template-modal__publish-fields">
					<SelectControl
						label={ __( 'Status', 'buddyboss' ) }
						value={ status }
						options={ statusOptions }
						onChange={ setStatus }
						__nextHasNoMarginBottom
					/>
				</div>
			</div>

			<div className="bb-admin-settings-modal__footer bb-email-template-modal__footer">
				<Button variant="secondary" onClick={ handleClose } disabled={ isSaving }>
					{ __( 'Cancel', 'buddyboss' ) }
				</Button>
				<Button variant="primary" onClick={ handleSave } isBusy={ isSaving } disabled={ isSaving || 0 === localItems.length }>
					{ isSaving ? __( 'Saving...', 'buddyboss' ) : __( 'Save', 'buddyboss' ) }
				</Button>
			</div>
		</Modal>
	);
}
