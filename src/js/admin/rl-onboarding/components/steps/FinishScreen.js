import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const FinishScreen = ({ stepData, onFinish, onViewSite }) => {
    const { title, description, image } = stepData;
    const [isFinishing, setIsFinishing] = useState(false);
    
    // Get the image URL from the assets
    const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl 
        ? `${window.bbRlOnboarding.assets.assetsUrl}${image}`
        : '';

    const handleFinish = async () => {
        setIsFinishing(true);
        
        try {
            if (onFinish) {
                await onFinish();
            }
        } catch (error) {
            console.error('Error finishing onboarding:', error);
        } finally {
            setIsFinishing(false);
        }
    };

    const handleViewSite = () => {
        if (onViewSite) {
            onViewSite();
        } else {
            // Default to homepage
            window.open(window.bbRlOnboarding?.readylaunch?.site_url || window.location.origin, '_blank');
        }
    };

    const handleGoToAdmin = () => {
        // Navigate to BuddyBoss settings
        window.location.href = window.bbRlOnboarding?.readylaunch?.admin_url + 'admin.php?page=buddyboss-platform';
    };

    return (
        <div className="bb-rl-finish-screen">
            <div className="bb-rl-finish-content">
                {/* Success Animation/Icon */}
                <div className="bb-rl-success-animation">
                    <div className="bb-rl-checkmark">
                        <svg className="bb-rl-checkmark-circle" viewBox="0 0 52 52">
                            <circle className="bb-rl-checkmark-circle-path" cx="26" cy="26" r="25" fill="none"/>
                        </svg>
                        <svg className="bb-rl-checkmark-check" viewBox="0 0 52 52">
                            <path className="bb-rl-checkmark-check-path" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
                        </svg>
                    </div>
                </div>

                {/* Main finish image */}
                {imageUrl && (
                    <div className="bb-rl-finish-image">
                        <img src={imageUrl} alt={title} />
                    </div>
                )}

                {/* Congratulations content */}
                <div className="bb-rl-finish-text">
                    <h1 className="bb-rl-finish-title">{title}</h1>
                    <p className="bb-rl-finish-description">{description}</p>
                    <p className="bb-rl-finish-subtitle">
                        {__('Your community is now configured and ready for members to join!', 'buddyboss')}
                    </p>
                </div>

                {/* Setup Summary */}
                <div className="bb-rl-setup-summary">
                    <h3>{__('What we\'ve set up for you:', 'buddyboss')}</h3>
                    <div className="bb-rl-summary-grid">
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">🏠</div>
                            <span>{__('Community structure', 'buddyboss')}</span>
                        </div>
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">🎨</div>
                            <span>{__('Site appearance', 'buddyboss')}</span>
                        </div>
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">🏷️</div>
                            <span>{__('Branding elements', 'buddyboss')}</span>
                        </div>
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">📄</div>
                            <span>{__('Essential pages', 'buddyboss')}</span>
                        </div>
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">🧭</div>
                            <span>{__('Navigation menus', 'buddyboss')}</span>
                        </div>
                        <div className="bb-rl-summary-item">
                            <div className="bb-rl-summary-icon">🔧</div>
                            <span>{__('Widget areas', 'buddyboss')}</span>
                        </div>
                    </div>
                </div>

                {/* Action buttons */}
                <div className="bb-rl-finish-actions">
                    <Button 
                        className="bb-rl-finish-button bb-rl-primary-action"
                        onClick={handleViewSite}
                        variant="primary"
                        size="large"
                    >
                        {__('View Your Site', 'buddyboss')}
                    </Button>
                    
                    <Button 
                        className="bb-rl-finish-button bb-rl-secondary-action"
                        onClick={handleGoToAdmin}
                        variant="secondary"
                        size="large"
                    >
                        {__('Go to Settings', 'buddyboss')}
                    </Button>

                    <Button 
                        className="bb-rl-finish-button bb-rl-close-action"
                        onClick={handleFinish}
                        variant="link"
                        disabled={isFinishing}
                    >
                        {isFinishing ? __('Finishing...', 'buddyboss') : __('Close Setup', 'buddyboss')}
                    </Button>
                </div>

                {/* Next Steps */}
                <div className="bb-rl-next-steps">
                    <h3>{__('What\'s next?', 'buddyboss')}</h3>
                    <div className="bb-rl-next-steps-list">
                        <div className="bb-rl-next-step">
                            <div className="bb-rl-step-number">1</div>
                            <div className="bb-rl-step-content">
                                <h4>{__('Invite members', 'buddyboss')}</h4>
                                <p>{__('Start building your community by inviting the first members', 'buddyboss')}</p>
                            </div>
                        </div>
                        <div className="bb-rl-next-step">
                            <div className="bb-rl-step-number">2</div>
                            <div className="bb-rl-step-content">
                                <h4>{__('Create content', 'buddyboss')}</h4>
                                <p>{__('Add some initial posts and groups to get conversations started', 'buddyboss')}</p>
                            </div>
                        </div>
                        <div className="bb-rl-next-step">
                            <div className="bb-rl-step-number">3</div>
                            <div className="bb-rl-step-content">
                                <h4>{__('Customize further', 'buddyboss')}</h4>
                                <p>{__('Fine-tune your settings and explore advanced features', 'buddyboss')}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Support Resources */}
                <div className="bb-rl-support-resources">
                    <h3>{__('Need help?', 'buddyboss')}</h3>
                    <div className="bb-rl-resource-links">
                        <a 
                            href="https://www.buddyboss.com/resources/docs/" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="bb-rl-resource-link"
                        >
                            📚 {__('Documentation', 'buddyboss')}
                        </a>
                        <a 
                            href="https://www.buddyboss.com/contact/" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="bb-rl-resource-link"
                        >
                            💬 {__('Get Support', 'buddyboss')}
                        </a>
                        <a 
                            href="https://www.buddyboss.com/community/" 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="bb-rl-resource-link"
                        >
                            👥 {__('Join Community', 'buddyboss')}
                        </a>
                    </div>
                </div>

                {/* Thank you message */}
                <div className="bb-rl-thank-you">
                    <p>{__('Thank you for choosing BuddyBoss! We\'re excited to see what you build with your new community.', 'buddyboss')}</p>
                </div>
            </div>
        </div>
    );
}; 