import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function ReactionMigration({ field, onStartConversion }) {
    const [isDismissed, setIsDismissed] = useState(false);

    const migrationData = field.migration_data || {};
    const migrationStatus = field.migration_status || '';

    // Only show for pending migrations (not in-progress or completed)
    const hasPendingMigration =
        migrationData &&
        migrationData.action &&
        migrationData.total_reactions > 0 &&
        migrationStatus !== 'inprogress' &&
        migrationStatus !== 'completed';

    if (isDismissed || !hasPendingMigration) {
        return null;
    }

    const totalReactions = migrationData.total_reactions || 0;
    const fromMode = migrationData.action === 'like_to_emotions_action' ? 'Likes' : 'Reactions';

    const formatNumber = (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    const handleDismiss = (e) => {
        e.preventDefault();
        setIsDismissed(true);

        // Call "Do Later" endpoint
        if (window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url) {
            jQuery.ajax({
                url: window.bbReactionAdminVars.ajax_url,
                method: 'POST',
                data: {
                    action: 'bb_pro_reaction_migration_do_later',
                    nonce: window.bbReactionAdminVars.nonce?.migration_do_later || '',
                },
            });
        }
    };

    const handleStartConversion = (e) => {
        e.preventDefault();
        if (onStartConversion) {
            onStartConversion(migrationData);
        }
    };

    return (
        <div className="bb-admin-reaction-migration-wrapper">
            <div className="bb-admin-notice bb-admin-notice--warning">
                <div className="bb-admin-notice__icon">
                    <span className="bb-icons-rl bb-icons-rl-warning-circle" />
                </div>
                <div className="bb-admin-notice__content">
                    <p>
                        {__('You have ', 'buddyboss')}
                        <strong>{formatNumber(totalReactions)}</strong>
                        {' '}
                        {fromMode}
                        {' '}
                        {__('previously submitted on your site which can be converted to', 'buddyboss')}
                        {' '}
                        {migrationData.action === 'like_to_emotions_action'
                            ? __('an Emotion', 'buddyboss')
                            : __('Likes', 'buddyboss')}
                        .
                    </p>
                </div>
                <div className="bb-admin-notice__actions">
                    <button
                        type="button"
                        className="bb-admin-notice__button"
                        onClick={handleStartConversion}
                    >
                        {__('Start Conversion', 'buddyboss')}
                    </button>
                </div>
                <button
                    type="button"
                    className="bb-admin-notice__close"
                    onClick={handleDismiss}
                    aria-label={__('Dismiss', 'buddyboss')}
                >
                    <span className="bb-icons-rl bb-icons-rl-x" />
                </button>
            </div>
        </div>
    );
}
