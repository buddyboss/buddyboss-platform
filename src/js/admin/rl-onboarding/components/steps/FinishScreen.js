import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

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
                            <a href={window.bbRlOnboarding?.readylaunch?.site_url} className="bb-rl-finish-action-button" variant="primary">
                                {__('View Site', 'buddyboss')}
                            </a>
                        </div>
                        <div className="bb-rl-finish-action">
                            <div className="bb-rl-finish-action-content">
                                <h2>{__('ReadyLaunch Settings', 'buddyboss')}</h2>
                                <p>{__('Tailor styles, pages, and widgets to match your brand.', 'buddyboss')}</p>
                            </div>
                            <a href={window.bbRlOnboarding?.readylaunch?.admin_url + 'admin.php?page=bb-readylaunch'} className="bb-rl-finish-action-button" variant="primary">
                                {__('Open Settings', 'buddyboss')}
                            </a>
                        </div>
                        <div className="bb-rl-finish-action">
                            <div className="bb-rl-finish-action-content">
                                <h2>{__('Platform Settings', 'buddyboss')}</h2>
                                <p>{__('Fine-tune features, permissions, and community rules.', 'buddyboss')}</p>
                            </div>
                            <a href={window.bbRlOnboarding?.readylaunch?.admin_url + 'admin.php?page=bp-settings'} className="bb-rl-finish-action-button" variant="primary">
                                {__('Open Settings', 'buddyboss')}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}; 