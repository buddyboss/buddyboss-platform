/**
 * BuddyBoss Admin Settings 2.0 - Profile Type Redirects Field
 *
 * Renders a paginated list of profile types with per-type After Login / After Logout
 * searchable dropdowns. Uses existing bb_admin_get_member_types and
 * bb_admin_update_member_type AJAX endpoints.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import { TextControl, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { getMemberTypes, updateMemberType } from '../../utils/ajax';
import { AsyncSelectField } from './AsyncSelectField';
import { getPageNumbers } from '../../utils/pagination';
import { BB_EVENTS } from '../../utils/constants';

var PER_PAGE = 5;

/**
 * Profile Type Redirects Field Component
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object} props Component props.
 * @returns {JSX.Element} Profile type redirects list.
 */
export function ProfileTypeRedirectsField() {
	var memberTypesState = useState( [] );
	var memberTypes = memberTypesState[ 0 ];
	var setMemberTypes = memberTypesState[ 1 ];

	var loadingState = useState( true );
	var isLoading = loadingState[ 0 ];
	var setIsLoading = loadingState[ 1 ];

	var pageState = useState( 1 );
	var currentPage = pageState[ 0 ];
	var setCurrentPage = pageState[ 1 ];

	var savingState = useState( {} );
	var savingIds = savingState[ 0 ];
	var setSavingIds = savingState[ 1 ];
	var debounceTimersRef = useRef( {} );

	// Fetch member types on mount with AbortController cleanup.
	useEffect( function () {
		var controller = new AbortController();

		setIsLoading( true );
		getMemberTypes( { signal: controller.signal } )
			.then( function ( response ) {
				if ( response.success && response.data && response.data.member_types ) {
					setMemberTypes( response.data.member_types );
				}
			} )
			.catch( function () {} )
			.finally( function () {
				setIsLoading( false );
			} );

		return function () {
			controller.abort();
			// Clear all debounce timers.
			Object.keys( debounceTimersRef.current ).forEach( function ( key ) {
				clearTimeout( debounceTimersRef.current[ key ] );
			} );
		};
	}, [] );

	// Pagination.
	var totalPages = Math.ceil( memberTypes.length / PER_PAGE );
	var pagedTypes = memberTypes.slice( ( currentPage - 1 ) * PER_PAGE, currentPage * PER_PAGE );

	/**
	 * Update a single profile type's redirect field and save.
	 *
	 * @since BuddyBoss [BBVERSION]
	 *
	 * @param {number} typeId     Member type post ID.
	 * @param {string} fieldKey   Field key (login_redirection, logout_redirection, etc).
	 * @param {string} fieldValue New value.
	 */
	var handleFieldChange = useCallback( function ( typeId, fieldKey, fieldValue ) {
		// Update local state immediately.
		setMemberTypes( function ( prev ) {
			return prev.map( function ( mt ) {
				if ( mt.id !== typeId ) {
					return mt;
				}
				var updated = Object.assign( {}, mt );
				updated[ fieldKey ] = fieldValue;

				// Clear custom URL when switching away from Custom URL.
				if ( 'login_redirection' === fieldKey && '0' !== fieldValue ) {
					updated.custom_login_redirection = '';
				}
				if ( 'logout_redirection' === fieldKey && '0' !== fieldValue ) {
					updated.custom_logout_redirection = '';
				}

				return updated;
			} );
		} );

		// Debounce per type — batch rapid changes (login + logout) into one save.
		var timerKey = typeId + '-' + fieldKey;
		if ( debounceTimersRef.current[ timerKey ] ) {
			clearTimeout( debounceTimersRef.current[ timerKey ] );
		}

		debounceTimersRef.current[ timerKey ] = setTimeout( function () {
			delete debounceTimersRef.current[ timerKey ];

			// Show "Saving changes..." toast once per debounce batch (not per keystroke).
			window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
				detail: { status: 'saving', message: __( 'Saving changes...', 'buddyboss' ) },
			} ) );

			// Build save data from current + new value.
			var saveData = {};
			saveData[ fieldKey ] = fieldValue;

			// Mark as saving.
			setSavingIds( function ( prev ) {
				var next = Object.assign( {}, prev );
				next[ typeId ] = true;
				return next;
			} );

			updateMemberType( typeId, saveData )
				.then( function ( response ) {
					if ( response.success ) {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
							detail: { status: 'success', message: __( 'Settings saved.', 'buddyboss' ) },
						} ) );
					} else {
						window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
							detail: { status: 'error', message: ( response.data && response.data.message ) || __( 'Failed to save.', 'buddyboss' ) },
						} ) );
					}
				} )
				.catch( function () {
					window.dispatchEvent( new CustomEvent( BB_EVENTS.TOAST, {
						detail: { status: 'error', message: __( 'Failed to save.', 'buddyboss' ) },
					} ) );
				} )
				.finally( function () {
					setSavingIds( function ( prev ) {
						var next = Object.assign( {}, prev );
						delete next[ typeId ];
						return next;
					} );
				} );
		}, 800 );
	}, [] );

	if ( isLoading ) {
		return (
			<div className="bb-profile-type-redirects__loading">
				<Spinner />
			</div>
		);
	}

	if ( ! memberTypes.length ) {
		return (
			<p className="bb-profile-type-redirects__empty">
				{ __( 'No profile types found. Create profile types under Members > Profile Types.', 'buddyboss' ) }
			</p>
		);
	}

	return (
		<div className="bb-profile-type-redirects">
			{ pagedTypes.map( function ( mt ) {
				var isSaving = !! savingIds[ mt.id ];
				var labelStyle = {};
				if ( mt.label_color && 'custom' === mt.label_color.type ) {
					labelStyle.backgroundColor = mt.label_color.background_color || '#1e1e1e';
					labelStyle.color = mt.label_color.text_color || '#fff';
				}

				return (
					<div key={ mt.id } className={ 'bb-profile-type-redirects__row' + ( isSaving ? ' bb-profile-type-redirects__row--saving' : '' ) }>
						<div className="bb-profile-type-redirects__type">
							<span className="bb-profile-type-redirects__badge" style={ labelStyle }>
								{ decodeEntities( mt.post_title || mt.key ) }
							</span>
						</div>

						<div className="bb-profile-type-redirects__fields">
							<div className="bb-profile-type-redirects__field">
								<label className="bb-profile-type-redirects__field-label">
									{ __( 'After Login', 'buddyboss' ) }
								</label>
								<AsyncSelectField
									key={ 'login-' + mt.id }
									value={ mt.login_redirection || '' }
									onChange={ function ( val ) {
										handleFieldChange( mt.id, 'login_redirection', val );
									} }
									asyncAction="bb_admin_search_published_pages"
									placeholder={ __( 'Default', 'buddyboss' ) }
								/>
								{ '0' === mt.login_redirection && (
									<TextControl
										value={ mt.custom_login_redirection || '' }
										onChange={ function ( val ) {
											handleFieldChange( mt.id, 'custom_login_redirection', val );
										} }
										placeholder={ __( 'Paste URL', 'buddyboss' ) }
										type="url"
										__nextHasNoMarginBottom
									/>
								) }
							</div>

							<div className="bb-profile-type-redirects__field">
								<label className="bb-profile-type-redirects__field-label">
									{ __( 'After Logout', 'buddyboss' ) }
								</label>
								<AsyncSelectField
									key={ 'logout-' + mt.id }
									value={ mt.logout_redirection || '' }
									onChange={ function ( val ) {
										handleFieldChange( mt.id, 'logout_redirection', val );
									} }
									asyncAction="bb_admin_search_published_pages"
									placeholder={ __( 'Default', 'buddyboss' ) }
								/>
								{ '0' === mt.logout_redirection && (
									<TextControl
										value={ mt.custom_logout_redirection || '' }
										onChange={ function ( val ) {
											handleFieldChange( mt.id, 'custom_logout_redirection', val );
										} }
										placeholder={ __( 'Paste URL', 'buddyboss' ) }
										type="url"
										__nextHasNoMarginBottom
									/>
								) }
							</div>
						</div>
					</div>
				);
			} ) }

			{ totalPages > 1 && (
				<div className="bb-profile-type-redirects__pagination">
					<button
						type="button"
						className="bb-profile-type-redirects__page-btn"
						disabled={ 1 === currentPage }
						onClick={ function () { setCurrentPage( Math.max( 1, currentPage - 1 ) ); } }
					>
						&lsaquo;
					</button>
					{ getPageNumbers( currentPage, totalPages ).map( function ( page, index ) {
						if ( '...' === page ) {
							return <span key={ 'ellipsis-' + index } className="bb-profile-type-redirects__page-ellipsis">&hellip;</span>;
						}
						return (
							<button
								key={ page }
								type="button"
								className={ 'bb-profile-type-redirects__page-btn' + ( page === currentPage ? ' bb-profile-type-redirects__page-btn--active' : '' ) }
								onClick={ function () { setCurrentPage( page ); } }
							>
								{ page }
							</button>
						);
					} ) }
					<button
						type="button"
						className="bb-profile-type-redirects__page-btn"
						disabled={ currentPage === totalPages }
						onClick={ function () { setCurrentPage( Math.min( totalPages, currentPage + 1 ) ); } }
					>
						&rsaquo;
					</button>
				</div>
			) }
		</div>
	);
}
