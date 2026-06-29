/**
 * Shared BuddyBoss admin header.
 *
 * Logo + a global settings search + an optional Mothership IPN bell (relocated
 * from outside the React tree) + a help icon. Consuming apps opt into the
 * search/bell/help via props, so every admin page presents one identical
 * global header.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useEffect, useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Shared admin header component.
 *
 * @since BuddyBoss [BBVERSION]
 *
 * @param {Object}        props
 * @param {string}        props.logoUrl           BuddyBoss logo image URL.
 * @param {Function}      [props.onSearch]        Async search: (query, signal) => Promise<Array>.
 *                                                When provided, the global search box renders.
 * @param {Function}      [props.onSelectResult]  Called with a result when a search row is clicked.
 * @param {string}        [props.searchPlaceholder] Search input placeholder.
 * @param {JSX.Element}   [props.centerSlot]      Center content when no onSearch is given.
 * @param {JSX.Element}   [props.rightSlot]       Extra right content after the icons.
 * @param {string}        [props.ipnRootId]       Mothership IPN root element ID. When provided,
 *                                                the live IPN node (rendered outside React by
 *                                                do_action('bb_admin_header_actions')) is moved
 *                                                into the bell slot, preserving its Shadow DOM.
 * @param {Function}      [props.onHelp]          Click handler for the help icon. Omit to hide it.
 * @returns {JSX.Element}
 */
export function BBAdminHeader( {
	logoUrl,
	onSearch,
	onSelectResult,
	searchPlaceholder,
	centerSlot,
	rightSlot,
	ipnRootId,
	onHelp,
} ) {
	const ipnSlotRef = useRef( null );
	const searchRef = useRef( null );
	const searchTimeoutRef = useRef( null );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ searchResults, setSearchResults ] = useState( [] );
	const [ showSearchResults, setShowSearchResults ] = useState( false );
	const [ isSearching, setIsSearching ] = useState( false );

	// Relocate the live Mothership IPN inbox node into the bell slot. The IPN
	// service attaches a Shadow DOM to its root <div> synchronously when its
	// bundle runs, so the node is rendered outside the React tree (by PHP) and
	// moved here with appendChild — which detaches/re-attaches without
	// unmounting, keeping the Shadow DOM intact.
	useEffect( () => {
		if ( ! ipnSlotRef.current ) {
			return;
		}
		const node = ipnRootId
			? document.getElementById( ipnRootId )
			: document.querySelector( '[id$="_ipn_root"]' );
		if ( node && node.parentElement !== ipnSlotRef.current ) {
			node.classList.add(
				'bb-admin-header__icon-button',
				'bb-admin-header__icon-button--notifications',
				'bb-admin-header__ipn-root'
			);
			ipnSlotRef.current.appendChild( node );
		}
	}, [ ipnRootId ] );

	// Debounced search (300ms) with AbortController to cancel stale requests.
	useEffect( () => {
		if ( ! onSearch ) {
			return undefined;
		}
		if ( searchTimeoutRef.current ) {
			clearTimeout( searchTimeoutRef.current );
		}
		if ( searchQuery.length < 2 ) {
			setSearchResults( [] );
			setShowSearchResults( false );
			return undefined;
		}

		const abortController = new AbortController();
		setIsSearching( true );
		searchTimeoutRef.current = setTimeout( () => {
			Promise.resolve( onSearch( searchQuery, abortController.signal ) )
				.then( ( results ) => {
					setSearchResults( Array.isArray( results ) ? results : [] );
					setShowSearchResults( true );
					setIsSearching( false );
				} )
				.catch( ( error ) => {
					if ( error && 'AbortError' === error.name ) {
						return;
					}
					setSearchResults( [] );
					setShowSearchResults( false );
					setIsSearching( false );
				} );
		}, 300 );

		return () => {
			if ( searchTimeoutRef.current ) {
				clearTimeout( searchTimeoutRef.current );
			}
			abortController.abort();
		};
	}, [ searchQuery, onSearch ] );

	// Close the results dropdown when clicking outside the search box.
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			if ( searchRef.current && ! searchRef.current.contains( event.target ) ) {
				setShowSearchResults( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => document.removeEventListener( 'mousedown', handleClickOutside );
	}, [] );

	const handleResultClick = ( result ) => {
		if ( onSelectResult ) {
			onSelectResult( result );
		}
		setSearchQuery( '' );
		setShowSearchResults( false );
	};

	const placeholder = searchPlaceholder || __( 'Search for settings…', 'buddyboss' );

	return (
		<header className="bb-admin-header">
			<div className="bb-admin-header__container">
				<div className="bb-admin-header__left">
					<div className="bb-admin-header__logo">
						<img
							src={ logoUrl || '' }
							alt={ __( 'BuddyBoss', 'buddyboss' ) }
							className="bb-admin-header__logo-img"
						/>
					</div>
				</div>

				<div className="bb-admin-header__center">
					{ onSearch ? (
						<div className="bb-admin-header__search" ref={ searchRef }>
							<div className="bb-admin-header__search-wrapper">
								<input
									type="text"
									value={ searchQuery }
									onChange={ ( e ) => setSearchQuery( e.target.value ) }
									placeholder={ placeholder }
									aria-label={ placeholder }
									className="bb-admin-header__search-input"
								/>
								<i className="bb-icon-search bb-admin-header__search-icon"></i>
							</div>
							{ isSearching && (
								<span className="bb-admin-header__search-spinner">
									<span className="spinner is-active"></span>
								</span>
							) }
							{ showSearchResults && searchResults.length > 0 && (
								<div className="bb-admin-header__search-results">
									{ searchResults.map( ( result, index ) => (
										<button
											key={ result.route || index }
											className="bb-admin-header__search-result"
											onClick={ () => handleResultClick( result ) }
										>
											<div className="bb-admin-header__search-result-icon">
												{ result.feature_icon && (
													<i className={ result.feature_icon.class || 'bb-icon-settings' }></i>
												) }
											</div>
											<div className="bb-admin-header__search-result-content">
												<div className="bb-admin-header__search-result-label">
													{ result.feature_label } / { result.section_title } / <span className="bb-admin-header__search-result-label-field">{ result.field_label }</span>
												</div>
											</div>
										</button>
									) ) }
								</div>
							) }
							{ showSearchResults && 0 === searchResults.length && ! isSearching && searchQuery.length >= 2 && (
								<div className="bb-admin-header__search-results">
									<div className="bb-admin-header__search-result bb-admin-header__search-result--no-results">
										{ __( 'No settings found', 'buddyboss' ) }
									</div>
								</div>
							) }
						</div>
					) : (
						centerSlot
					) }
				</div>

				<div className="bb-admin-header__right">
					<span
						ref={ ipnSlotRef }
						className="bb-admin-header__ipn-slot"
						role="region"
						aria-label={ __( 'Notifications', 'buddyboss' ) }
					/>
					{ onHelp && (
						<button
							type="button"
							className="bb-admin-header__icon-button"
							aria-label={ __( 'Documentation', 'buddyboss' ) }
							onClick={ onHelp }
						>
							<i className="bb-icons-rl-graduation-cap" aria-hidden="true"></i>
						</button>
					) }
					{ rightSlot }
				</div>
			</div>
		</header>
	);
}
