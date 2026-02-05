/**
 * BuddyBoss Admin Settings 2.0 - Reaction Info Component
 *
 * Displays an informational text notice with inline link for reaction migration.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { __ } from '@wordpress/i18n';

/**
 * Reaction Info Component
 *
 * @param {Object} props Component props
 * @param {Object} props.field Field configuration
 * @returns {JSX.Element|null} Info notice or null
 */
export function ReactionInfo({ field }) {
    // Get the description text and link from field config
    const description = field.description || '';
    const link = field.link || {};
    const linkUrl = link.url || '';
    const linkText = link.text || __('migration wizard', 'buddyboss');

    if (!description) {
        return null;
    }

    // Split description by placeholder to insert link
    // Expected format: "Text before {link} text after"
    const parts = description.split('{link}');

    return (
        <div className="bb-admin-reaction-info-wrapper">
            <div className="bb-admin-reaction-info">
                <p className="bb-admin-reaction-info__text">
                    {parts[0]}
                    {linkUrl ? (
                        <a
                            href={linkUrl}
                            className="bb-admin-reaction-info__link"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            {linkText}
                        </a>
                    ) : (
                        <span className="bb-admin-reaction-info__link">{linkText}</span>
                    )}
                    {parts[1] || ''}
                </p>
            </div>
        </div>
    );
}
