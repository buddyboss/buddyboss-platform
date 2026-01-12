/**
 * BuddyBoss Admin Settings 2.0 - Side Navigation Component
 *
 * Displays side panels in the left sidebar navigation.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { __ } from '@wordpress/i18n';

/**
 * Side Navigation Component
 *
 * @param {Object} props Component props
 * @param {string} props.featureId Feature ID
 * @param {Array} props.sidePanels Side panels array
 * @param {string} props.currentPanel Current panel ID
 * @param {Function} props.onNavigate Navigation callback
 * @param {Function} props.onBack Back button callback
 * @returns {JSX.Element} Side navigation component
 */
export function SideNavigation({ featureId, sidePanels, currentPanel, onNavigate, onBack }) {
	const handlePanelClick = (panelId) => {
		if (typeof onNavigate === 'function') {
			onNavigate(panelId);
		}
	};

	const handleBackClick = () => {
		if (typeof onBack === 'function') {
			onBack();
		}
	};

	return (
		<nav className="bb-admin-side-nav" aria-label={__('Settings Navigation', 'buddyboss')}>
			{/* Back button */}
			<button className="bb-admin-side-nav__back-btn" onClick={handleBackClick}>
				<span className="dashicons dashicons-arrow-left-alt2"></span>
				{__('Back to Settings', 'buddyboss')}
			</button>

			{/* Menu list - Side Panels */}
			<ul className="bb-admin-side-nav__list">
				{(sidePanels || []).map((panel) => (
					<li key={panel.id} className="bb-admin-side-nav__item">
						<button
							className={`bb-admin-side-nav__link ${
								currentPanel === panel.id ? 'bb-admin-side-nav__link--active' : ''
							}`}
							onClick={() => handlePanelClick(panel.id)}
							aria-current={currentPanel === panel.id ? 'page' : undefined}
						>
							{panel.icon && (
								<span className="bb-admin-side-nav__icon">
									{panel.icon.type === 'dashicon' && (
										<span className={`dashicons ${panel.icon.slug || 'dashicons-admin-generic'}`}></span>
									)}
									{(panel.icon.type === 'svg' || panel.icon.type === 'image') && panel.icon.url && (
										<img src={panel.icon.url} alt={panel.title} className="bb-admin-side-nav__icon-img" />
									)}
								</span>
							)}
							<span className="bb-admin-side-nav__text">{panel.title}</span>
						</button>
					</li>
				))}
			</ul>
		</nav>
	);
}
