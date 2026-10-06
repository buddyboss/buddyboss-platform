(function() {
    'use strict';

    /**
     * Insights class for handling NPS (Net Promoter Score) notifications and feedback collection.
     * Manages score submission, modal interactions, and feedback submission via REST API.
     *
     * @class Insights
     */
    class Insights {
        /**
         * Creates an instance of Insights.
         *
         * @param {Object} cfg - Configuration object
         * @param {string} cfg.notificationId - Unique identifier for the notification
         * @param {string} cfg.rootElementId - ID of the root element containing the shadow DOM
         * @param {string} cfg.endpointUrl - REST API endpoint URL for submissions
         * @param {string} cfg.restNonce - WordPress REST API nonce for authentication
         * @param {Object} [cfg.i18n={}] - Internationalization strings
         */
        constructor(cfg) {

            this.notificationId = cfg.notificationId;
            this.rootElementId = cfg.rootElementId;
            this.endpointUrl = cfg.endpointUrl;
            this.restNonce = cfg.restNonce;
            this.i18n = cfg.i18n || {};
            this.score = null;
            this.submitBtn = null;
            this.modalContainer = null;
            this.notificationElement = null;
            this.feedback = '';
            this.followUp = false;
            this.modalError = null;

            // Restore score submission timestamp from localStorage if it exists
            const stored = localStorage.getItem(`nps_score_submitted_${this.notificationId}`);
            this.scoreSubmittedAt = stored ? parseInt(stored, 10) : null;

            this.bindEvents();
        }

        /**
         * Binds event listeners to the notification element.
         * Sets up a MutationObserver to detect when the notification is loaded in the shadow DOM.
         *
         * @returns {void}
         */
        bindEvents() {
            const rootElement = document.getElementById(this.rootElementId);
            if (!rootElement || !rootElement.shadowRoot) {
                return;
            }

            const shadowRoot = rootElement.shadowRoot;
            const notificationId = `notification-${this.notificationId}`;

            const existingEl = shadowRoot.getElementById(notificationId);
            if (existingEl) {
                this.onNotificationLoaded(existingEl);
                return;
            }

            const observer = new MutationObserver((mutations, obs) => {
                const el = shadowRoot.getElementById(notificationId);
                if (el) {
                    obs.disconnect();
                    this.onNotificationLoaded(el);
                }
            });

            observer.observe(shadowRoot, {
                childList: true,
                subtree: true
            });

            setTimeout(() => {
                observer.disconnect();
            }, 10000);
        }

        /**
         * Handles click events on the notification element.
         * Manages score button selection and submit button clicks.
         *
         * @param {Event} e - The click event object
         * @returns {void}
         */
        onClick(e) {
            if (e.target.classList.contains('nps-score-btn')) {
                const el = e.target,
                    parent = el.parentElement,
                    selectedScore = parseInt(e.target.dataset.score, 10);

                this.toggleSubmitBtn(true);
                parent.querySelectorAll('.nps-score-btn').forEach(btn => {
                    btn.style.fontWeight = 'normal';
                    btn.style.boxShadow = 'none';
                });

                if (selectedScore !== this.score) {
                    el.style.fontWeight = 'bold';
                    el.style.boxShadow = 'inset 0 0 8px #aaa';
                    this.toggleSubmitBtn(false);
                    this.score = selectedScore;
                } else {
                    this.score = null;
                }
            } else if (e.target === this.submitBtn || e.target.closest('.btn[href="#nps-submit"]')) {
                e.preventDefault();
                if (this.score !== null) {
                    this.submitScore();
                }
            }
        }

        /**
         * Called when the notification element is loaded in the shadow DOM.
         * Initializes the submit button and attaches click event listener.
         *
         * @param {HTMLElement} el - The notification element
         * @returns {void}
         */
        onNotificationLoaded(el) {
            this.notificationElement = el;
            this.submitBtn = el.querySelector('.btn[href="#nps-submit"]');
            this.toggleSubmitBtn();
            el.addEventListener('click', this.onClick.bind(this));
        }

        /**
         * Toggles the submit button's disabled state and visual appearance.
         *
         * @param {boolean} [disabled=true] - Whether the button should be disabled
         * @returns {void}
         */
        toggleSubmitBtn(disabled = true) {
            this.submitBtn.disabled = disabled;
            this.submitBtn.style.opacity = disabled ? '0.5' : '1';
            this.submitBtn.style.cursor = disabled ? 'not-allowed' : 'pointer';
        }

        /**
         * Displays an error message in the notification area.
         * The error will auto-dismiss after 10 seconds.
         *
         * @param {string} message - The error message to display
         * @param {string[]} [errors=null] - Optional array of detailed error messages
         * @returns {void}
         */
        showNotificationError(message, errors = null) {
            const existingError = document.querySelector(`#nps-error-${this.notificationId}`);
            if (existingError) {
                existingError.remove();
            }

            const errorDiv = document.createElement('div');
            errorDiv.id = `nps-error-${this.notificationId}`;
            errorDiv.style.cssText = 'color: #d63638; display: inline-block; margin-top: 8px; font-size: 13px; line-height: 1.5;';

            let errorText = `${this.i18n.errorTitle || 'Error'}: ${message}`;

            // Add detailed errors if available
            if (errors && Array.isArray(errors) && errors.length > 0) {
                errorText += '\n' + errors.join('\n');
            }

            errorDiv.textContent = errorText;

            // Insert after submit button
            if (this.submitBtn && this.submitBtn.parentElement) {
                this.submitBtn.parentElement.appendChild(errorDiv);

                // Auto-dismiss after 10 seconds
                setTimeout(() => {
                    if (errorDiv.parentNode) {
                        errorDiv.remove();
                    }
                }, 10000);
            }
        }

        /**
         * Displays an error message in the modal.
         * Stores the error and triggers a modal re-render.
         *
         * @param {string} message - The error message to display
         * @param {string[]} [errors=null] - Optional array of detailed error messages
         * @returns {void}
         */
        showModalError(message, errors = null) {
            // Store error and re-render modal to show it
            this.modalError = { message, errors };
            this.updateModal();
        }

        /**
         * Clears any error message displayed in the modal.
         * Triggers a modal re-render.
         *
         * @returns {void}
         */
        clearModalError() {
            this.modalError = null;
            this.updateModal();
        }

        /**
         * Submits the selected NPS score to the REST API endpoint.
         * On success, stores the submission timestamp and opens the feedback modal.
         *
         * @returns {void}
         */
        submitScore() {
            // Disable submit button during request
            this.toggleSubmitBtn(true);

            const data = {
                score: this.score
            };

            fetch(this.endpointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.restNonce
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Store timestamp when score was submitted
                    this.scoreSubmittedAt = Date.now();
                    localStorage.setItem(`nps_score_submitted_${this.notificationId}`, this.scoreSubmittedAt.toString());

                    // Clear any existing error
                    const existingError = document.querySelector(`#nps-error-${this.notificationId}`);
                    if (existingError) {
                        existingError.remove();
                    }

                    // Open modal for feedback
                    this.openModal();
                } else {
                    // API returned error
                    const errorMessage = result.message || this.i18n.submitError || 'An error occurred while submitting your score.';
                    this.showNotificationError(errorMessage, result.errors);
                    this.toggleSubmitBtn(false); // Re-enable button
                }
            })
            .catch(error => {
                const errorMessage = error.message || this.i18n.networkError || 'Network error: Unable to submit your score. Please try again.';
                this.showNotificationError(errorMessage);
                this.toggleSubmitBtn(false); // Re-enable button
            });
        }

        /**
         * Opens the feedback modal using WordPress components.
         * The modal allows users to provide optional feedback and indicate interest in follow-up.
         *
         * @returns {void}
         */
        openModal() {
            // Ensure WordPress components are available
            if (typeof wp === 'undefined' || !wp.element || !wp.components) {
                console.error('WordPress components not available');
                return;
            }

            const { createElement: el } = wp.element;
            const { Modal, TextareaControl, ToggleControl, Button } = wp.components;

            // Create container for modal if it doesn't exist
            if (!this.modalContainer) {
                this.modalContainer = document.createElement('div');
                this.modalContainer.id = 'nps-modal-container';
                document.body.appendChild(this.modalContainer);
            }

            // Determine follow-up question based on score
            const isPromoter = this.score >= 9;
            const followUpQuestion = isPromoter
                ? (this.i18n.promoterQuestion || "What's the main reason for your score?")
                : (this.i18n.detractorQuestion || "What could we do to improve your score?");

            // Render modal
            const modalChildren = [];

            // Add error message if it exists
            if (this.modalError) {
                modalChildren.push(
                    el('div', {
                        className: 'nps-modal-error',
                        style: {
                            background: '#fcf0f1',
                            borderLeft: '4px solid #d63638',
                            padding: '12px',
                            marginBottom: '16px',
                            color: '#d63638'
                        }
                    },
                        el('strong', {}, `${this.i18n.errorTitle || 'Error'}: `),
                        this.modalError.message,
                        this.modalError.errors && this.modalError.errors.length > 0
                            ? el('ul', {
                                style: { margin: '8px 0 0 20px' }
                            },
                                ...this.modalError.errors.map(err => el('li', {}, err))
                            )
                            : null
                    )
                );
            }

            modalChildren.push(
                el(TextareaControl, {
                    value: this.feedback,
                    onChange: (value) => {
                        this.feedback = value;
                        this.updateModal();
                    },
                    rows: 4,
                    help: this.i18n.feedbackHelp || 'Please share your thoughts (optional)',
                    'aria-label': this.i18n.feedbackLabel || 'Feedback'
                }),
                el(ToggleControl, {
                    label: this.i18n.followUpLabel || 'Would you be interested in having someone from the team reach out for a follow up?',
                    checked: this.followUp,
                    onChange: (value) => {
                        this.followUp = value;
                        this.updateModal();
                    }
                }),
                el('div', {
                    style: {
                        display: 'flex',
                        justifyContent: 'flex-end',
                        gap: '8px',
                        marginTop: '20px'
                    }
                },
                    el(Button, {
                        variant: 'secondary',
                        onClick: () => this.closeModal()
                    }, this.i18n.cancelButton || 'Cancel'),
                    el(Button, {
                        variant: 'primary',
                        onClick: () => this.submitFeedback()
                    }, this.i18n.submitButton || 'Submit')
                )
            );

            // Render modal
            wp.element.render(
                el(Modal, {
                    title: followUpQuestion,
                    onRequestClose: () => this.closeModal(),
                    isDismissible: true,
                    className: 'nps-feedback-modal'
                },
                    el('div', { className: 'nps-modal-content' },
                        ...modalChildren
                    )
                ),
                this.modalContainer
            );
        }

        /**
         * Updates the modal by re-rendering it with the current state.
         * Preserves any modal error messages.
         *
         * @returns {void}
         */
        updateModal() {
            // Re-render modal with updated state (preserves modalError)
            this.openModal();
        }

        /**
         * Closes the feedback modal by clearing its rendered content.
         *
         * @returns {void}
         */
        closeModal() {
            if (this.modalContainer) {
                wp.element.render(null, this.modalContainer);
            }
        }

        /**
         * Submits the feedback form data to the REST API endpoint.
         * Includes the score, feedback text, and follow-up preference.
         * On success, closes the modal and removes the notification.
         *
         * @returns {void}
         */
        submitFeedback() {
            // Submit feedback update - always include score and email (email added server-side)
            if (!this.endpointUrl || !this.restNonce) {
                this.showModalError(this.i18n.configError || 'Configuration error: REST API URL or nonce not available.');
                return;
            }

            // Clear any existing error
            this.clearModalError();
            this.modalError = null;

            // Disable submit button during request
            const submitButton = document.querySelector('.nps-feedback-modal .components-button.is-primary');
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = this.i18n.submitting || 'Submitting...';
            }

            const data = {
                score: this.score,
                feedback: this.feedback,
                follow_up: this.followUp
            };

            fetch(this.endpointUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.restNonce
                },
                body: JSON.stringify(data)
            })
            .then(response => {
                // Check if response is ok
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || `HTTP error! status: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    this.closeModal();
                    localStorage.removeItem(`nps_score_submitted_${this.notificationId}`);
                    this.removeNotification();
                } else {
                    // API returned error - store for re-render
                    const errorMessage = result.message || this.i18n.feedbackError || 'An error occurred while submitting your feedback.';
                    this.modalError = { message: errorMessage, errors: result.errors };
                    this.showModalError(errorMessage, result.errors);

                    // Re-enable submit button
                    if (submitButton) {
                        submitButton.disabled = false;
                        submitButton.textContent = this.i18n.submitButton || 'Submit';
                    }
                }
            })
            .catch(error => {
                const errorMessage = error.message || this.i18n.networkError || 'Network error: Unable to submit your feedback. Please try again.';
                this.modalError = { message: errorMessage, errors: null };
                this.showModalError(errorMessage);

                // Re-enable submit button
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = this.i18n.submitButton || 'Submit';
                }
            });
        }

        /**
         * Removes the notification element from the shadow DOM.
         * Applies a fade-out animation before removal.
         *
         * @returns {void}
         */
        removeNotification() {
            // Remove notification from shadow DOM
            if (this.notificationElement && this.notificationElement.parentElement) {
                // Add fade-out animation
                this.notificationElement.style.transition = 'opacity 0.3s ease-out';
                this.notificationElement.style.opacity = '0';

                // Remove from DOM after animation
                setTimeout(() => {
                    if (this.notificationElement && this.notificationElement.parentElement) {
                        this.notificationElement.remove();
                    }
                }, 300);
            } else {
                // Fallback: try to find and remove via shadowRoot
                try {
                    const shadowRoot = document.getElementById(this.rootElementId)?.shadowRoot;
                    if (shadowRoot) {
                        const notification = shadowRoot.getElementById(`notification-${this.notificationId}`);
                        if (notification) {
                            notification.style.transition = 'opacity 0.3s ease-out';
                            notification.style.opacity = '0';
                            setTimeout(() => {
                                if (notification.parentElement) {
                                    notification.remove();
                                }
                            }, 300);
                        }
                    }
                } catch (e) {
                    console.error('Error removing notification:', e);
                }
            }
            }
    }

    /**
     * Factory function to create a new Insights instance.
     * Exposed globally to allow initialization from external code.
     *
     * @param {Object} cfg - Configuration object for Insights instance
     * @param {string} cfg.notificationId - Unique identifier for the notification
     * @param {string} cfg.rootElementId - ID of the root element containing the shadow DOM
     * @param {string} cfg.endpointUrl - REST API endpoint URL for submissions
     * @param {string} cfg.restNonce - WordPress REST API nonce for authentication
     * @param {Object} [cfg.i18n={}] - Internationalization strings
     * @returns {Insights} A new Insights instance
     */
    window.createGrdLvlInsights = window.createGrdLvlInsights || function(cfg) {
        return new Insights(cfg);
    }
})();
