import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

/**
 * Allowlist a URL against `http://`, `https://`, or protocol-relative `//`
 * prefixes. Everything else — including `javascript:` / `data:` / `vbscript:`
 * payloads that could slip through if `window.bbRlOnboarding` ever gets
 * poisoned — falls back to `'#'`.
 *
 * Stricter than this codebase's general `safeUrl()` helper (rejects
 * `mailto:`, hash-only, and relative URLs) because the only URLs that flow
 * into this screen are absolute admin / site URLs — any relative form is
 * already suspicious.
 *
 * @param {string} url Candidate URL.
 * @returns {string} Trimmed URL when allowed, `'#'` otherwise.
 */
const allowlistUrl = ( url ) => {
    if ( typeof url !== 'string' || '' === url ) {
        return '#';
    }
    const trimmed = url.trim();
    const lower   = trimmed.toLowerCase();
    if (
        0 === lower.indexOf( 'http://' ) ||
        0 === lower.indexOf( 'https://' ) ||
        0 === lower.indexOf( '//' )
    ) {
        return trimmed;
    }
    return '#';
};

/**
 * Compose an admin URL by appending `suffix` to the localized `admin_url`.
 * Guards against an undefined `admin_url` concatenating into
 * `"undefinedadmin.php?..."` and falls back to `'#'` if the resolved URL fails
 * the scheme allowlist.
 *
 * @param {string} [suffix] Query/path appended to `admin_url`.
 * @returns {string} Allowlisted URL or `'#'`.
 */
const safeAdminUrl = ( suffix ) => {
    const adminUrl = window.bbRlOnboarding?.readylaunch?.admin_url;
    if ( typeof adminUrl !== 'string' || '' === adminUrl ) {
        return '#';
    }
    return allowlistUrl( adminUrl + ( suffix || '' ) );
};

/**
 * Resolve and allowlist the localized `site_url`.
 *
 * @returns {string} Allowlisted URL or `'#'`.
 */
const safeSiteUrl = () => allowlistUrl( window.bbRlOnboarding?.readylaunch?.site_url );

export const FinishScreen = ({ stepData, onFinish, onViewSite }) => {
    const { title, description } = stepData;
    // Note: Completion is now handled by OnboardingModal when navigating to this screen

    return (
        <div className="bb-rl-finish-screen bb-rl-fullscreen-finish">
            <div className="bb-rl-finish-container">
                {/* Main Content */}

                <div className="bb-rl-finish-content">
                    <p className='bb-rl-finish-icon'>
                        <i className='bb-icons-rl-check-circle'></i>
                    </p>
                    <h1 className="bb-rl-finish-title">{title}</h1>
                    <p className="bb-rl-finish-description">{description}</p>

                    <div class="bb-rl-step-progress">
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                        <div class="bb-rl-progress-step bb-rl-step-active"></div>
                    </div>

                    <div className="bb-rl-finish-actions">
                        <div className="bb-rl-finish-action">
                            <div className="bb-rl-finish-action-content">
                                <h2>{__('View Community', 'buddyboss')}</h2>
                                <p>{__('See a live preview of your community\'s front-end.', 'buddyboss')}</p>
                            </div>
                            <a href={safeSiteUrl()} className="bb-rl-finish-action-button" variant="primary">
                                {__('View Site', 'buddyboss')}
                            </a>
                        </div>
                        <div className="bb-rl-finish-action">
                            <div className="bb-rl-finish-action-content">
                                <h2>{__('ReadyLaunch Settings', 'buddyboss')}</h2>
                                <p>{__('Tailor styles, pages, and widgets to match your brand.', 'buddyboss')}</p>
                            </div>
                            <a href={safeAdminUrl( 'admin.php?page=bb-settings&tab=appearance&panel=general' )} className="bb-rl-finish-action-button" variant="primary">
                                {__('Open Settings', 'buddyboss')}
                            </a>
                        </div>
                        <div className="bb-rl-finish-action">
                            <div className="bb-rl-finish-action-content">
                                <h2>{__('Platform Settings', 'buddyboss')}</h2>
                                <p>{__('Fine-tune features, permissions, and community rules.', 'buddyboss')}</p>
                            </div>
                            <a href={safeAdminUrl( 'admin.php?page=bb-settings' )} className="bb-rl-finish-action-button" variant="primary">
                                {__('Open Settings', 'buddyboss')}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}; 