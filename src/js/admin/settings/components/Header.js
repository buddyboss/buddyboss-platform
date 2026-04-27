/**
 * BuddyBoss Admin Settings 2.0 - Header Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss [BBVERSION]
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ajaxFetch } from '../utils/ajax';


/**
 * Header Component
 *
 * @param {Object} props Component props
 * @param {Function} props.onNavigate Navigation callback
 * @returns {JSX.Element} Header component
 */
export function Header({ onNavigate }) {
	const [searchQuery, setSearchQuery] = useState('');
	const [searchResults, setSearchResults] = useState([]);
	const [showSearchResults, setShowSearchResults] = useState(false);
	const [isSearching, setIsSearching] = useState(false);
	const searchTimeoutRef = useRef(null);
	const searchRef = useRef(null);
	const ipnSlotRef = useRef(null);

	// Relocate the live Mothership IPN inbox into our header slot.
	//
	// The IPN React app (Caseproof GroundLevel) attaches a Shadow DOM to
	// the IPN root <div> synchronously when ipn-inbox.js runs, so it must
	// exist in DOM before the bundle fires. We render it outside the React
	// tree (in bb-admin-settings-page.php via do_action('bb_admin_header_actions')),
	// then move the live node into our slot here. appendChild() detaches
	// and re-attaches without unmounting, so the Shadow DOM stays intact.
	//
	// The IPN root ID is dynamic — derived from the active plugin_id
	// (e.g. "bb-web-plus_ipn_root", "bb-platform-pro-1-site_ipn_root"). We
	// read the resolved ID from PHP's wp_localize_script (bbAdminData.ipnRootId)
	// and fall back to a structural [id$="_ipn_root"] query if missing.
	useEffect(() => {
		if (!ipnSlotRef.current) {
			return;
		}
		const ipnRootId = (typeof bbAdminData !== 'undefined' && bbAdminData?.ipnRootId) || '';
		const ipnNode = ipnRootId
			? document.getElementById(ipnRootId)
			: document.querySelector('[id$="_ipn_root"]');
		if (ipnNode && ipnNode.parentElement !== ipnSlotRef.current) {
			// Inherit our visual classes so it aligns with sibling icons.
			ipnNode.classList.add(
				'bb-admin-header__icon-button',
				'bb-admin-header__icon-button--notifications',
				'bb-admin-header__ipn-root'
			);
			ipnSlotRef.current.appendChild(ipnNode);
		}
	}, []);

	// Debounced search (300ms) with AbortController to cancel stale requests.
	useEffect(() => {
		if (searchTimeoutRef.current) {
			clearTimeout(searchTimeoutRef.current);
		}

		if (searchQuery.length < 2) {
			setSearchResults([]);
			setShowSearchResults(false);
			return;
		}

		const abortController = new AbortController();

		setIsSearching(true);
		searchTimeoutRef.current = setTimeout(() => {
			ajaxFetch('bb_admin_search_settings', { query: searchQuery }, { signal: abortController.signal })
				.then((response) => {
					if (response.success) {
						setSearchResults(response.data?.results || []);
						setShowSearchResults(true);
					} else {
						setSearchResults([]);
						setShowSearchResults(false);
					}
					setIsSearching(false);
				})
				.catch((error) => {
					if (error && 'AbortError' === error.name) {
						return;
					}
					setSearchResults([]);
					setShowSearchResults(false);
					setIsSearching(false);
				});
		}, 300);

		return () => {
			if (searchTimeoutRef.current) {
				clearTimeout(searchTimeoutRef.current);
			}
			abortController.abort();
		};
	}, [searchQuery]);

	// Close search results when clicking outside
	useEffect(() => {
		const handleClickOutside = (event) => {
			if (searchRef.current && !searchRef.current.contains(event.target)) {
				setShowSearchResults(false);
			}
		};

		document.addEventListener('mousedown', handleClickOutside);
		return () => {
			document.removeEventListener('mousedown', handleClickOutside);
		};
	}, []);

	const handleSearchResultClick = (result) => {
		onNavigate(result.route);
		setSearchQuery('');
		setShowSearchResults(false);
	};

	return (
		<header className="bb-admin-header">
			<div className="bb-admin-header__container">
				<div className="bb-admin-header__left">
					{/* BuddyBoss Logo */}
					<div className="bb-admin-header__logo">
						<a href="#/dashboard" onClick={(e) => { e.preventDefault(); onNavigate('/dashboard'); }}>
							<img
								src={bbAdminData?.logoUrl || ''}
								alt={__('BuddyBoss', 'buddyboss')}
								className="bb-admin-header__logo-img"
							/>
						</a>
					</div>
				</div>

				<div className="bb-admin-header__center">
					{/* Global Settings Search */}
					<div className="bb-admin-header__search" ref={searchRef}>
						<div className="bb-admin-header__search-wrapper">
							<input
								type="text"
								value={searchQuery}
								onChange={(e) => setSearchQuery(e.target.value)}
								placeholder={__('Search for settings...', 'buddyboss')}
								aria-label={__('Search for settings', 'buddyboss')}
								className="bb-admin-header__search-input"
							/>
							<i className="bb-icon-search bb-admin-header__search-icon"></i>
						</div>
						{isSearching && (
							<span className="bb-admin-header__search-spinner">
								<span className="spinner is-active"></span>
							</span>
						)}
						{showSearchResults && searchResults.length > 0 && (
							<div className="bb-admin-header__search-results">
								{searchResults.map((result, index) => (
									<button
										key={index}
										className="bb-admin-header__search-result"
										onClick={() => handleSearchResultClick(result)}
									>
										<div className="bb-admin-header__search-result-icon">
											{result.feature_icon && (
												<i className={result.feature_icon.class || 'bb-icon-settings'}></i>
											)}
										</div>
										<div className="bb-admin-header__search-result-content">
											<div className="bb-admin-header__search-result-label">
												{result.feature_label} / {result.section_title} / <span className="bb-admin-header__search-result-label-field">{result.field_label}</span>
											</div>
										</div>
									</button>
								))}
							</div>
						)}
						{showSearchResults && searchResults.length === 0 && !isSearching && searchQuery.length >= 2 && (
							<div className="bb-admin-header__search-results">
								<div className="bb-admin-header__search-result bb-admin-header__search-result--no-results">
									{__('No settings found', 'buddyboss')}
								</div>
							</div>
						)}
					</div>
				</div>

				<div className="bb-admin-header__right">
					{/* Documentation/Help Icon */}
					<a
						href="https://www.buddyboss.com/resources/docs/"
						target="_blank"
						rel="noopener noreferrer"
						className="bb-admin-header__icon-button"
						aria-label={__('Documentation', 'buddyboss')}
					>
						<i className="bb-icons-rl-graduation-cap"></i>
					</a>

					{/*
					 * Notifications — slot for the live Mothership IPN inbox.
					 *
					 * The actual <div id="bb-web-plus_ipn_root"> is rendered
					 * outside the React tree by PHP (do_action('bb_admin_header_actions')
					 * in bb-admin-settings-page.php) so the IPN bundle can attach
					 * its Shadow DOM synchronously without a race against React
					 * mount. The useEffect above relocates that live node into
					 * this slot via appendChild — preserving the Shadow DOM.
					 */}
					<span
						ref={ipnSlotRef}
						className="bb-admin-header__ipn-slot"
						aria-label={__('Notifications', 'buddyboss')}
					/>
				</div>
			</div>
		</header>
	);
}
