/**
 * BuddyBoss Admin Settings 2.0 - Header Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * AJAX request helper.
 *
 * @param {string} action AJAX action name.
 * @param {Object} data   Additional data.
 * @returns {Promise} Promise resolving to response data.
 */
const ajaxFetch = (action, data = {}) => {
	const formData = new FormData();
	formData.append('action', action);
	formData.append('nonce', window.bbAdminData?.ajaxNonce || '');
	
	Object.keys(data).forEach((key) => {
		formData.append(key, data[key]);
	});
	
	return fetch(window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
		method: 'POST',
		credentials: 'same-origin',
		body: formData,
	}).then((response) => response.json());
};

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

	// Debounced search (300ms)
	useEffect(() => {
		if (searchTimeoutRef.current) {
			clearTimeout(searchTimeoutRef.current);
		}

		if (searchQuery.length < 2) {
			setSearchResults([]);
			setShowSearchResults(false);
			return;
		}

		setIsSearching(true);
		searchTimeoutRef.current = setTimeout(() => {
			ajaxFetch('bb_admin_search_settings', { query: searchQuery })
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
				.catch(() => {
					setSearchResults([]);
					setShowSearchResults(false);
					setIsSearching(false);
				});
		}, 300);

		return () => {
			if (searchTimeoutRef.current) {
				clearTimeout(searchTimeoutRef.current);
			}
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
												<i className={result.feature_icon.slug || 'bb-icon-settings'}></i>
											)}
										</div>
										<div className="bb-admin-header__search-result-content">
											<div className="bb-admin-header__search-result-label">
												{result.feature_label} → {result.section_title} → {result.field_label}
											</div>
											<div className="bb-admin-header__search-result-breadcrumb">
												{result.breadcrumb}
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
					{/* Notifications Icon */}
					<button
						className="bb-admin-header__icon-button bb-admin-header__icon-button--notifications"
						aria-label={__('Notifications', 'buddyboss')}
					>
						<i className="bb-icon-bell"></i>
						<span className="bb-admin-header__notification-badge">2</span>
					</button>

					{/* Documentation/Help Icon */}
					<a
						href="https://www.buddyboss.com/resources/docs/"
						target="_blank"
						rel="noopener noreferrer"
						className="bb-admin-header__icon-button"
						aria-label={__('Documentation', 'buddyboss')}
					>
						<i className="bb-icon-book-open"></i>
					</a>
				</div>
			</div>
		</header>
	);
}
