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
