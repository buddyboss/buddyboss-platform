/**
 * BuddyBoss Admin Settings 2.0 - Reporting Categories Screen
 *
 * Custom panel screen for managing reporting categories (bpm_category taxonomy).
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getReportingCategories, deleteReportingCategory } from '../utils/ajax';
import { Toast, useAutoDismissToast } from '../components/Toast';
import { HelpIcon } from '../components/HelpIcon';
import { ReportingCategoryModal } from '../components/modals/ReportingCategoryModal';
import { ConfirmToggleModal } from '../components/modals/ConfirmToggleModal';

/**
 * Reporting Categories Screen Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}   props             Component props.
 * @param {Function} props.onNavigate  Navigation handler.
 * @param {string}   props.helpUrl     Help URL for this panel.
 * @param {Function} props.onHelpClick Help icon click handler.
 * @param {Object}   props.feature     Feature data.
 * @param {string}   props.activePanelId Active panel ID.
 * @returns {JSX.Element} Reporting categories screen.
 */
export function ReportingCategoriesScreen( { onNavigate, helpUrl, onHelpClick, feature, activePanelId } ) {
	var categoriesState = useState( [] );
	var categories = categoriesState[ 0 ];
	var setCategories = categoriesState[ 1 ];

	var isLoadingState = useState( true );
	var isLoading = isLoadingState[ 0 ];
	var setIsLoading = isLoadingState[ 1 ];

	var isModalOpenState = useState( false );
	var isModalOpen = isModalOpenState[ 0 ];
	var setIsModalOpen = isModalOpenState[ 1 ];

	var editingCategoryState = useState( null );
	var editingCategory = editingCategoryState[ 0 ];
	var setEditingCategory = editingCategoryState[ 1 ];

	var openMenuIdState = useState( null );
	var openMenuId = openMenuIdState[ 0 ];
	var setOpenMenuId = openMenuIdState[ 1 ];

	var showWhenOptionsState = useState( [] );
	var showWhenOptions = showWhenOptionsState[ 0 ];
	var setShowWhenOptions = showWhenOptionsState[ 1 ];

	var toastState = useState( null );
	var toast = toastState[ 0 ];
	var setToast = toastState[ 1 ];
	useAutoDismissToast( toast, setToast );

	var deleteConfirmState = useState( null );
	var deleteConfirmId = deleteConfirmState[ 0 ];
	var setDeleteConfirmId = deleteConfirmState[ 1 ];

	// Load categories.
	var abortRef = useRef( null );

	var loadCategories = useCallback( function () {
		if ( abortRef.current ) {
			abortRef.current.abort();
		}
		abortRef.current = new AbortController();

		setIsLoading( true );
		getReportingCategories( { signal: abortRef.current.signal } )
			.then( function ( response ) {
				if ( response.success && response.data ) {
					setCategories( response.data.categories || [] );
					setShowWhenOptions( response.data.show_when_options || [] );
				}
				setIsLoading( false );
			} )
			.catch( function ( err ) {
				if ( ! err || 'AbortError' !== err.name ) {
					setIsLoading( false );
					setToast( { status: 'error', message: __( 'Failed to load reporting categories.', 'buddyboss' ) } );
				}
			} );
	}, [] );

	useEffect( function () {
		loadCategories();

		return function () {
			if ( abortRef.current ) {
				abortRef.current.abort();
			}
		};
	}, [ loadCategories ] );

	// Close menu on outside click or Escape key.
	useEffect( function () {
		if ( null === openMenuId ) {
			return;
		}

		function handleMouseDown( e ) {
			if ( ! e.target.closest( '.bb-admin-reporting-categories__menu-wrapper' ) ) {
				setOpenMenuId( null );
			}
		}

		function handleKeyDown( e ) {
			if ( 'Escape' === e.key ) {
				setOpenMenuId( null );
			}
		}

		document.addEventListener( 'mousedown', handleMouseDown );
		document.addEventListener( 'keydown', handleKeyDown );
		return function () {
			document.removeEventListener( 'mousedown', handleMouseDown );
			document.removeEventListener( 'keydown', handleKeyDown );
		};
	}, [ openMenuId ] );

	// Handle delete — open confirmation modal.
	var handleDelete = useCallback( function ( termId ) {
		setOpenMenuId( null );
		setDeleteConfirmId( termId );
	}, [] );

	// Perform delete after confirmation.
	var performDelete = useCallback( function () {
		var termId = deleteConfirmId;
		setDeleteConfirmId( null );

		deleteReportingCategory( termId )
			.then( function ( response ) {
				if ( response.success ) {
					setCategories( function ( prev ) {
						return prev.filter( function ( cat ) {
							return cat.id !== termId;
						} );
					} );
					setToast( { status: 'success', message: __( 'Category deleted.', 'buddyboss' ) } );
				} else {
					setToast( { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to delete category.', 'buddyboss' ) } );
				}
			} )
			.catch( function () {
				setToast( { status: 'error', message: __( 'Failed to delete category.', 'buddyboss' ) } );
			} );
	}, [ deleteConfirmId ] );

	// Handle edit.
	var handleEdit = useCallback( function ( cat ) {
		setOpenMenuId( null );
		setEditingCategory( cat );
		setIsModalOpen( true );
	}, [] );

	// Handle add new.
	var handleAddNew = useCallback( function () {
		setEditingCategory( null );
		setIsModalOpen( true );
	}, [] );

	// Handle modal save.
	var handleModalSave = useCallback( function () {
		setIsModalOpen( false );
		setEditingCategory( null );
		loadCategories();
		setToast( { status: 'success', message: __( 'Category saved.', 'buddyboss' ) } );
	}, [ loadCategories ] );

	// Handle modal close.
	var handleModalClose = useCallback( function () {
		setIsModalOpen( false );
		setEditingCategory( null );
	}, [] );

	return (
		<div className="bb-admin-reporting-categories">
			{/* Section card: Reporting Categories */}
			<div className="bb-admin-feature-settings__section">
				<div className="bb-admin-feature-settings__section-header">
					<h3 className="bb-admin-feature-settings__section-title">
						{ __( 'Reporting Categories', 'buddyboss' ) }
					</h3>
					<div className="bb-admin-reporting-categories__header-actions">
						<button
							className="bb-admin-reporting-categories__add-btn"
							onClick={ handleAddNew }
						>
							<i className="bb-icons-rl bb-icons-rl-plus"></i>
							{ __( 'Add New Category', 'buddyboss' ) }
						</button>
						{ helpUrl && (
							<HelpIcon
								onClick={ onHelpClick }
								contentId={ helpUrl }
							/>
						) }
					</div>
				</div>
				<div className="bb-admin-feature-settings__section-body bb-admin-reporting-categories__list-body">
					{ isLoading ? (
						<div className="bb-admin-loading"><Spinner /></div>
					) : categories.length > 0 ? (
						<ul className="bb-admin-reporting-categories__list">
							{ categories.map( function ( cat ) {
								return (
									<li key={ cat.id } className="bb-admin-reporting-categories__list-item">
										<div className="bb-admin-reporting-categories__list-item-content">
											<div className="bb-admin-reporting-categories__list-item-name-col">
												<span className="bb-admin-reporting-categories__list-item-name">
													{ decodeEntities( cat.name ) }
												</span>
											</div>
											<div className="bb-admin-reporting-categories__list-item-desc-col">
												<span className="bb-admin-reporting-categories__list-item-desc">
													{ decodeEntities( cat.description || '' ) }
												</span>
											</div>
											<div className="bb-admin-reporting-categories__list-item-show-when-col">
												<span className="bb-admin-reporting-categories__list-item-badge">
													{ decodeEntities( cat.show_when_reporting_label || '' ) }
												</span>
											</div>
										</div>
										<div className="bb-admin-reporting-categories__list-item-actions-col">
											<div className="bb-admin-reporting-categories__menu-wrapper">
												<button
													className="bb-admin-reporting-categories__menu-trigger"
													onClick={ function () {
														setOpenMenuId( cat.id === openMenuId ? null : cat.id );
													} }
													aria-label={ __( 'Actions', 'buddyboss' ) }
													aria-haspopup="true"
													aria-expanded={ cat.id === openMenuId ? 'true' : 'false' }
												>
													<span className="bb-icons-rl bb-icons-rl-dots-three"></span>
												</button>
												{ cat.id === openMenuId && (
													<div className="bb-admin-reporting-categories__menu-dropdown" role="menu">
														<button
															className="bb-admin-reporting-categories__menu-item"
															role="menuitem"
															onClick={ function () {
																handleEdit( cat );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-pencil-simple"></i>
															{ __( 'Edit', 'buddyboss' ) }
														</button>
														<button
															className="bb-admin-reporting-categories__menu-item bb-admin-reporting-categories__menu-item--danger"
															role="menuitem"
															onClick={ function () {
																handleDelete( cat.id );
															} }
														>
															<i className="bb-icons-rl bb-icons-rl-trash"></i>
															{ __( 'Delete', 'buddyboss' ) }
														</button>
													</div>
												) }
											</div>
										</div>
									</li>
								);
							} ) }
						</ul>
					) : (
						<div className="bb-admin-reporting-categories__empty">
							<p>{ __( 'No reporting categories found. Click "Add New Category" to create one.', 'buddyboss' ) }</p>
						</div>
					) }
				</div>
			</div>

			{/* Category Modal */}
			<ReportingCategoryModal
				isOpen={ isModalOpen }
				onClose={ handleModalClose }
				onSave={ handleModalSave }
				category={ editingCategory }
				showWhenOptions={ showWhenOptions }
			/>

			{/* Delete Confirmation Modal */}
			<ConfirmToggleModal
				isOpen={ null !== deleteConfirmId }
				title={ __( 'Delete Category', 'buddyboss' ) }
				message={ __( 'Are you sure you want to delete this reporting category?', 'buddyboss' ) }
				confirmLabel={ __( 'Delete', 'buddyboss' ) }
				cancelLabel={ __( 'Cancel', 'buddyboss' ) }
				isDestructive={ true }
				onConfirm={ performDelete }
				onCancel={ function () {
					setDeleteConfirmId( null );
				} }
			/>

			{/* Toast */}
			{ toast && (
				<div className="bb-toast-container">
					<Toast
						status={ toast.status }
						message={ toast.message }
						onDismiss={ function () { setToast( null ); } }
					/>
				</div>
			) }
		</div>
	);
}

export default ReportingCategoriesScreen;
