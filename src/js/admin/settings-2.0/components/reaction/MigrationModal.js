/**
 * BuddyBoss Admin Settings 2.0 - Migration Modal Component
 *
 * Modal for starting and displaying migration wizard.
 *
 * @package BuddyBoss\Core\Administration
 * @since BuddyBoss 3.0.0
 */

import { useEffect, useState } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export function MigrationModal({ isOpen, onClose, migrationData }) {
    const [loading, setLoading] = useState(true);
    const [wizardLabel, setWizardLabel] = useState(__('Migration wizard', 'buddyboss'));
    const [wizardContent, setWizardContent] = useState('');
    const [error, setError] = useState('');

    useEffect(() => {
        if (isOpen) {
            loadWizardData();
        }
    }, [isOpen]);

    /**
     * Handle multiple event listeners for migration wizard.
     */
    useEffect(() => {
        if (!wizardContent) {
            return;
        }

        /**
         * Update the Continue button state based on current selections.
         * Handles both checkbox-based (emotions to likes) and dropdown-based (likes to emotion) flows.
         */
        const updateContinueButtonState = () => {
            const continueBtn = document.querySelector('button.footer_next_wizard_screen');
            if (!continueBtn) {
                return;
            }

            // Check for checkbox-based selection (emotions to likes flow).
            const emotionInputs = document.querySelectorAll('input.migrate_emotion_input');
            const isAnyEmotionChecked = Array.from(emotionInputs).some(input => input.checked);

            // Check for dropdown-based selection (likes to emotion flow).
            const toReactionSelect = document.querySelector('select[name="to_reactions"]');
            const isDropdownSelected = toReactionSelect && toReactionSelect.value && toReactionSelect.value !== '';

            // Enable button if either condition is met.
            if (isAnyEmotionChecked || isDropdownSelected) {
                continueBtn.classList.remove('disabled');
            } else {
                continueBtn.classList.add('disabled');
            }
        };

        const handleFromAllEmotionsChange = (e) => {
            if (e.target.name === 'from_all_emotions') {
                const reactionInputs = document.querySelectorAll('input[name="from_reactions[]"]');
                reactionInputs.forEach(input => {
                    input.checked = e.target.checked;
                    input.disabled = e.target.checked;
                });
            }

            updateContinueButtonState();
        };

        const handleDropdownChange = (e) => {
            if (e.target.name === 'to_reactions') {
                updateContinueButtonState();
            }
        };

        const handleFooterNextWizardScreenClick = (e) => {
            if (e.target.classList.contains('footer_next_wizard_screen') && !e.target.classList.contains('disabled')) {
                document.querySelector('.bbpro_migration_wizard_2').classList.add( 'active' );
                document.querySelector('.bbpro_migration_wizard_1').classList.remove( 'active' );
            }
        };

        const handleCloseMigrationWizard = (e) => {
            if (e.target.classList.contains('cancel_migration_wizard')) {
                onClose();
            }
        };

        const handleFromLimitChange = (e) => {
            if (e.target.name === 'to_reactions' && e.target.value !== '') {
                document.querySelector('button.footer_next_wizard_screen').classList.remove( 'disabled' );
            } else {
                document.querySelector('button.footer_next_wizard_screen').classList.add( 'disabled' );
            }
        };

        /**
         * Handle "Start conversion" button click.
         * Sends migration data via the feature settings save endpoint.
         */
        const handleStartMigration = (e) => {
            if (!e.target.classList.contains('start_migration_wizard')) {
                return;
            }

            // Get selected reaction ID from dropdown (likes to emotion flow).
            const toReactionSelect = document.querySelector('select[name="to_reactions"]');
            const toReactions = toReactionSelect ? toReactionSelect.value : '';

            // Get selected emotions from checkboxes (emotions to likes flow).
            const fromReactionsInputs = document.querySelectorAll('input[name="from_reactions[]"]:checked');
            const fromReactions = Array.from(fromReactionsInputs).map(input => input.value);

            // Disable buttons and show loading state.
            e.target.disabled = true;
            e.target.textContent = __('Converting...', 'buddyboss');
            const cancelBtn = document.querySelector('.cancel_migration_wizard');
            if (cancelBtn) {
                cancelBtn.disabled = true;
            }

            // Build settings payload for migration.
            const settings = {
                migration_action: 'switch',
            };

            if (toReactions) {
                settings.to_reactions = toReactions;
            }

            if (fromReactions.length > 0) {
                settings.from_reactions = fromReactions;
            }

            // Call feature settings save endpoint.
            const formData = new FormData();
            formData.append('action', 'bb_admin_save_feature_settings');
            formData.append('nonce', window.bbAdminData?.ajaxNonce || '');
            formData.append('feature_id', 'reactions');
            formData.append('settings', JSON.stringify(settings));

            fetch(window.bbAdminData?.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData,
            })
                .then((response) => response.json())
                .then((response) => {
                    if (response.success) {
                        e.target.textContent = __('Conversion started!', 'buddyboss');

                        setTimeout(() => {
                            onClose();
                            // Dispatch generic event to refetch feature data (no page reload needed).
                            window.dispatchEvent(new CustomEvent('bb-admin-refetch-feature'));
                        }, 1000);
                    } else {
                        e.target.disabled = false;
                        e.target.textContent = __('Start conversion', 'buddyboss');
                        if (cancelBtn) {
                            cancelBtn.disabled = false;
                        }
                        alert(response.data?.message || __('Migration failed. Please try again.', 'buddyboss'));
                    }
                })
                .catch(() => {
                    e.target.disabled = false;
                    e.target.textContent = __('Start conversion', 'buddyboss');
                    if (cancelBtn) {
                        cancelBtn.disabled = false;
                    }
                    alert(__('Migration failed. Please try again.', 'buddyboss'));
                });
        };

        document.addEventListener('change', handleFromAllEmotionsChange);
        document.addEventListener('change', handleDropdownChange);
        document.addEventListener('click', handleFooterNextWizardScreenClick);
        document.addEventListener('click', handleCloseMigrationWizard);
        document.addEventListener('change', handleFromLimitChange);
        document.addEventListener('click', handleStartMigration);

        // Initialize button state after content loads (handles pre-selected dropdown values).
        updateContinueButtonState();

        return () => {
            document.removeEventListener('change', handleFromAllEmotionsChange);
            document.removeEventListener('change', handleDropdownChange);
            document.removeEventListener('click', handleFooterNextWizardScreenClick);
            document.removeEventListener('click', handleCloseMigrationWizard);
            document.removeEventListener('change', handleFromLimitChange);
            document.removeEventListener('click', handleStartMigration);
        };
    }, [wizardContent, onClose]);

    const loadWizardData = () => {
        setLoading(true);
        setError('');

        if (!window.bbReactionAdminVars || !window.bbReactionAdminVars.ajax_url) {
            setError(__('Unable to load migration wizard.', 'buddyboss'));
            setLoading(false);
            return;
        }

        // Set hidden input for migration action
        const migrationActionInput = document.getElementById('migration_action');
        if (migrationActionInput) {
            migrationActionInput.value = 'switch';
        }

        jQuery.ajax({
            url: window.bbReactionAdminVars.ajax_url,
            method: 'POST',
            data: {
                action: 'bb_pro_reaction_migration_start_conversion',
                nonce: window.bbReactionAdminVars.nonce?.migration_start_conversion || '',
            },
            success: (response) => {
                if (response.success && response.data) {
                    if (response.data.label) {
                        setWizardLabel(response.data.label);
                    }
                    if (response.data.content) {
                        setWizardContent(response.data.content);
                    } else if (response.data.message) {
                        setError(response.data.message);
                    }
                } else if (response.data && response.data.message) {
                    setError(response.data.message);
                } else {
                    setError(__('Unable to load migration wizard.', 'buddyboss'));
                }
                setLoading(false);
            },
            error: () => {
                setError(__('Unable to load migration wizard.', 'buddyboss'));
                setLoading(false);
            },
        });
    };

    if (!isOpen) {
        return null;
    }

    return (
        <Modal
            title={wizardLabel}
            onRequestClose={onClose}
            className="bb-admin-migration-modal"
            __experimentalHideHeader={false}
        >
            <div className="bb-admin-migration-modal__content">
                {loading && (
                    <div className="bb-admin-migration-modal__loader">
                        <span className="bb-icons-rl bb-icons-rl-spinner animate-spin" />
                    </div>
                )}
                {error && !loading && (
                    <div className="bb-admin-notice bb-admin-notice--error">
                        <p>{error}</p>
                    </div>
                )}
                {!loading && !error && wizardContent && (
                    <div
                        className="bb-admin-migration-modal__wizard"
                        dangerouslySetInnerHTML={{ __html: wizardContent }}
                    />
                )}
            </div>
        </Modal>
    );
}
