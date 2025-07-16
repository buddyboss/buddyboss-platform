import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const SplashScreen = ({ stepData, onNext, onSkip }) => {
    const { title, description, image } = stepData;
    
    // Get the image URL from the assets
    const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl 
        ? `${window.bbRlOnboarding.assets.assetsUrl}${image}`
        : '';

    const handleGetStarted = () => {
        if (onNext) {
            onNext();
        }
    };

    return (
        <div className="bb-rl-splash-screen">
            <div className="bb-rl-splash-content">
                {/* Logo/Branding */}
                <div className="bb-rl-splash-logo">
                    <img 
                        src={window.bbRlOnboarding?.assets?.logo || ''} 
                        alt="BuddyBoss" 
                        className="bb-rl-logo-image"
                    />
                </div>

                {/* Main splash image */}
                {imageUrl && (
                    <div className="bb-rl-splash-image">
                        <img src={imageUrl} alt={title} />
                    </div>
                )}

                {/* Welcome content */}
                <div className="bb-rl-splash-text">
                    <h1 className="bb-rl-splash-title">{title}</h1>
                    <p className="bb-rl-splash-description">{description}</p>
                    <p className="bb-rl-splash-subtitle">
                        {__('Let\'s set up your community in just a few simple steps.', 'buddyboss')}
                    </p>
                </div>

                {/* Action buttons */}
                <div className="bb-rl-splash-actions">
                    <Button 
                        className="bb-rl-splash-button bb-rl-get-started-button"
                        onClick={handleGetStarted}
                        variant="primary"
                        size="large"
                    >
                        {__('Let\'s Get Started!', 'buddyboss')}
                    </Button>
                    
                    <Button 
                        className="bb-rl-splash-button bb-rl-skip-splash-button"
                        onClick={onSkip}
                        variant="link"
                    >
                        {__('Skip setup for now', 'buddyboss')}
                    </Button>
                </div>

                {/* Features list */}
                <div className="bb-rl-splash-features">
                    <div className="bb-rl-feature-item">
                        <span className="bb-rl-feature-icon">⚡</span>
                        <span className="bb-rl-feature-text">
                            {__('Quick 5-minute setup', 'buddyboss')}
                        </span>
                    </div>
                    <div className="bb-rl-feature-item">
                        <span className="bb-rl-feature-icon">🎨</span>
                        <span className="bb-rl-feature-text">
                            {__('Customize your community', 'buddyboss')}
                        </span>
                    </div>
                    <div className="bb-rl-feature-item">
                        <span className="bb-rl-feature-icon">🚀</span>
                        <span className="bb-rl-feature-text">
                            {__('Launch with confidence', 'buddyboss')}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
}; 