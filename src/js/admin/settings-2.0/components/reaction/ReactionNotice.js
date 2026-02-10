/**
 * BuddyBoss Admin Settings 2.0 - Reaction Notice Component
 *
 * Displays status for in-progress or completed migrations.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useState, useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export function ReactionNotice({ field }) {
    const [isDismissed, setIsDismissed] = useState(false);
    const [migrationData, setMigrationData] = useState(field.migration_data || {});
    const [migrationStatus, setMigrationStatus] = useState(field.migration_status || '');
    const autoRefreshRef = useRef(null);

    // Check if migration is in-progress (either by status or by migration_data.status)
    const isInProgress = 'inprogress' === migrationStatus || 'running' === migrationData.status;
    // Check if migration is completed (either by status or by migration_data.status)
    const isCompleted = 'completed' === migrationStatus || 'completed' === migrationData.status;

    // Auto-refresh progress every 30 seconds when in-progress
    useEffect(() => {
        if (isInProgress && !isDismissed) {
            autoRefreshRef.current = setInterval(() => {
                if (window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url) {
                    jQuery.ajax({
                        url: window.bbReactionAdminVars.ajax_url,
                        method: 'POST',
                        data: {
                            action: 'bb_pro_reaction_check_migration',
                            nonce: window.bbReactionAdminVars.nonce?.check_migration || '',
                        },
                        success: (response) => {
                            if (response.success && response.data) {
                                setMigrationData(response.data.migration_data || {});
                                // Update status directly if changed to completed
                                if ('completed' === response.data.migration_status) {
                                    clearInterval(autoRefreshRef.current);
                                    setMigrationStatus('completed');
                                }
                            }
                        },
                    });
                }
            }, 30000); // 30 seconds

            return () => {
                if (autoRefreshRef.current) {
                    clearInterval(autoRefreshRef.current);
                }
            };
        }
    }, [isInProgress, isDismissed]);

    // Only show for 'inprogress' or 'completed' status
    if (isDismissed || (!isInProgress && !isCompleted)) {
        return null;
    }

    const handleDismiss = () => {
        setIsDismissed(true);

        // For completed status, call dismiss endpoint
        if ( isCompleted && window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url ) {
            jQuery.ajax({
                url: window.bbReactionAdminVars.ajax_url,
                method: 'POST',
                data: {
                    action: 'bb_pro_reaction_dismiss_migration_notice',
                    nonce: window.bbReactionAdminVars.nonce?.dismiss_migration_notice || '',
                },
            });
        }
    };

    const handleRecheckStatus = (e) => {
        e.preventDefault();

        if (window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url) {
            jQuery.ajax({
                url: window.bbReactionAdminVars.ajax_url,
                method: 'POST',
                data: {
                    action: 'bb_pro_reaction_check_migration',
                    nonce: window.bbReactionAdminVars.nonce?.check_migration || '',
                },
                success: (response) => {
                    if (response.success && response.data) {
                        setMigrationData(response.data.migration_data || {});
                        // Update status directly if changed to completed
                        if ( 'completed' === response.data.migration_status ) {
                            setMigrationStatus('completed');
                        }
                    }
                },
            });
        }
    };

    const handleStopMigration = (e) => {
        e.preventDefault();

        if (window.bbReactionAdminVars && window.bbReactionAdminVars.ajax_url) {
            if (confirm(__('Are you sure you want to stop the migration?', 'buddyboss'))) {
                jQuery.ajax({
                    url: window.bbReactionAdminVars.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'bb_pro_reaction_stop_migration',
                        nonce: window.bbReactionAdminVars.nonce?.migration_stop_conversion || '',
                    },
                    success: (response) => {
                        if (response.success) {
                            // Refetch feature data to update UI
                            window.dispatchEvent(new CustomEvent('bb-admin-refetch-feature'));
                        }
                    },
                });
            }
        }
    };

    const formatNumber = (num) => {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };

    // Render completed notice
    if ( isCompleted ) {
        const action = migrationData.action || '';
        const totalReactions = migrationData.total_reactions || 0;
        const fromEmotionsName = migrationData.from_emotions_name || '';
        const toEmotionsName = migrationData.to_emotions_name || '';

        let message = '';
        if ( 'like_to_emotions_action' === action ) {
            message = __('%1$s were successfully converted to the %2$s emotion.', 'buddyboss')
                .replace('%1$s', `<strong>${formatNumber(totalReactions)} ${fromEmotionsName}</strong>`)
                .replace('%2$s', `<strong>${toEmotionsName}</strong>`);
        } else if ( 'emotions_to_like_action' === action ) {
            message = __('%1$s reactions were successfully converted to %2$s.', 'buddyboss')
                .replace('%1$s', `<strong>${formatNumber(totalReactions)}</strong>`)
                .replace('%2$s', `<strong>${toEmotionsName}</strong>`);
        }

        return (
            <div className="bb-admin-reaction-notice-wrapper">
                <div className="bb-admin-notice bb-admin-notice--success">
                    <div className="bb-admin-notice__icon">
                        <span className="bb-icons-rl bb-icons-rl-check-circle" />
                    </div>
                    <div className="bb-admin-notice__content">
                        <p dangerouslySetInnerHTML={{ __html: message }} />
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

    // Render in-progress notice
    if ( isInProgress ) {
        const total = parseInt(migrationData.total_reactions || 0);
        const updatedEmotions = parseInt(migrationData.updated_emotions || 0);
        const percentage = total > 0 ? Math.ceil((updatedEmotions * 100) / total) : 0;

        return (
            <div className="bb-admin-reaction-notice-wrapper">
                <div className="bb-admin-notice bb-admin-notice--info bb-admin-notice--progress">
                    <div className="bb-admin-notice__icon">
                        <span className="bb-icons-rl bb-icons-rl-spinner animate-spin" />
                    </div>
                    <div className="bb-admin-notice__content">
                        <p>
                            <strong>
                                {__('%1$s out of %2$s %3$s reactions have been converted', 'buddyboss')
                                    .replace('%1$s', formatNumber(updatedEmotions))
                                    .replace('%2$s', formatNumber(total))
                                    .replace('%3$s', `(${percentage}%)`)}
                            </strong>
                        </p>
                        <p>
                            {__('This action is being performed in the background, but may take some time based on the amount of data.', 'buddyboss')}
                        </p>
                    </div>
                    <div className="bb-admin-notice__actions">
                        <button
                            type="button"
                            className="bb-admin-notice__button bb-admin-notice__button--outline"
                            onClick={handleRecheckStatus}
                        >
                            {__('Recheck status', 'buddyboss')}
                        </button>
                        <button
                            type="button"
                            className="bb-admin-notice__button bb-admin-notice__button--text"
                            onClick={handleStopMigration}
                        >
                            {__('Stop', 'buddyboss')}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return null;
}
