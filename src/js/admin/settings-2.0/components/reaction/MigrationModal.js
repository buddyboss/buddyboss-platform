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

        const handleFromAllEmotionsChange = (e) => {
            if (e.target.name === 'from_all_emotions') {
                const reactionInputs = document.querySelectorAll('input[name="from_reactions[]"]');
                reactionInputs.forEach(input => {
                    input.checked = e.target.checked;
                    input.disabled = e.target.checked;
                });
            }

            const emotionInputs = document.querySelectorAll('input.migrate_emotion_input');
            const isAnyEmotionChecked = Array.from(emotionInputs).some(input => input.checked);

            if(isAnyEmotionChecked) {
                document.querySelector('button.footer_next_wizard_screen').classList.remove( 'disabled' );
            } else {
                document.querySelector('button.footer_next_wizard_screen').classList.add( 'disabled' );
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

        document.addEventListener('change', handleFromAllEmotionsChange);
        document.addEventListener('click', handleFooterNextWizardScreenClick);
        document.addEventListener('click', handleCloseMigrationWizard);

        return () => {
            document.removeEventListener('change', handleFromAllEmotionsChange);
            document.removeEventListener('click', handleFooterNextWizardScreenClick);
            document.removeEventListener('click', handleCloseMigrationWizard);
        };
    }, [wizardContent]);

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
