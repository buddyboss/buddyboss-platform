/**
 * BuddyBoss Admin Settings 2.0 - Header Component
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { TextControl, Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';

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
			apiFetch({
				path: `/buddyboss/v1/settings/search?query=${encodeURIComponent(searchQuery)}`,
			})
				.then((response) => {
					setSearchResults(response.data?.results || []);
					setShowSearchResults(true);
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
						<TextControl
							value={searchQuery}
							onChange={setSearchQuery}
							placeholder={__('Search for settings...', 'buddyboss')}
							className="bb-admin-header__search-input"
						/>
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
												<span className={`dashicons ${result.feature_icon.slug || 'dashicons-admin-generic'}`}></span>
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
						className="bb-admin-header__icon-button"
						aria-label={__('Notifications', 'buddyboss')}
					>
						<span className="dashicons dashicons-bell"></span>
					</button>

					{/* Documentation/Help Icon */}
					<a
						href="https://www.buddyboss.com/resources/docs/"
						target="_blank"
						rel="noopener noreferrer"
						className="bb-admin-header__icon-button"
						aria-label={__('Documentation', 'buddyboss')}
					>
						<span className="dashicons dashicons-sos"></span>
					</a>
				</div>
			</div>
		</header>
	);
}
