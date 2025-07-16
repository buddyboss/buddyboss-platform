import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

export const SplashScreen = ({ stepData, onNext, onSkip }) => {
    const { title, description, image } = stepData;
    
    // Get the image URL from the assets
    const imageUrl = window.bbRlOnboarding?.assets?.assetsUrl 
        ? `${window.bbRlOnboarding.assets.assetsUrl}${image}`
        : '';

    const handleGetStarted = (e) => {
        e.preventDefault();
        e.stopPropagation();

        if (onNext) {
            onNext();
        }
    };

    return (
        <div className="bb-rl-splash-screen">
            <div className="bb-rl-splash-content">
                <div className="bb-rl-splash-content-product-container">
                    <div className="bb-rl-splash-content-product">
                        <p className="bb-rl-splash-content-product-name">{__('BuddyBoss Theme', 'buddyboss')}</p>
                        <p className="bb-rl-splash-content-product-baseline">{__('Customizable WordPress theme', 'buddyboss')}</p>
                        <img 
                            src={`${window.bbRlOnboarding.assets.assetsUrl}buddyboss-theme-preview.jpg`}
                            alt="BuddyBoss Theme Preview" 
                            className="bb-rl-product-preview"
                        />
                        <p className="bb-rl-splash-content-product-description">{__('Our crafted theme made just for the BuddyBoss Platform giving you full control to design a community that feels truly yours.', 'buddybos s')}</p>
                        <ul className="bb-rl-splash-content-product-features">
                            <li className="bb-rl-splash-content-product-feature">
                                <i className="bb-icons-rl-check"></i>
                                {__('Advanced customization', 'buddyboss')}
                            </li>
                            <li className="bb-rl-splash-content-product-feature">
                                <i className="bb-icons-rl-check"></i>
                                
                                {__('Deep integration support', 'buddyboss')}
                            </li>
                        </ul>
                        <div className="bb-rl-splash-content-product-button-container">
                            <a className="bb-rl-button bb-rl-button--disabled">
                                {__('Configure BuddyBoss Theme', 'buddyboss')}
                            </a>
                            <a className="bb-rl-button">
                                {__('Buy Theme', 'buddyboss')}
                            </a>
                        </div>
                    </div>

                    <div className="bb-rl-splash-content-product">
                        <p className="bb-rl-splash-content-product-name">{__('ReadyLaunch', 'buddyboss')}</p>
                        <p className="bb-rl-splash-content-product-baseline">{__('Community features for any WordPress theme', 'buddyboss')}</p>
                        <img 
                            src={`${window.bbRlOnboarding.assets.assetsUrl}readylaunch-preview.jpg`}
                            alt="BuddyBoss Theme Preview" 
                            className="bb-rl-product-preview"
                        />
                        <p className="bb-rl-splash-content-product-description">{__('Get your community up and running in no time with our easy to use template system.', 'buddybos s')}</p>
                        <ul className="bb-rl-splash-content-product-features">
                            <li className="bb-rl-splash-content-product-feature">
                                <i className="bb-icons-rl-check"></i>
                                {__('Minimal configuration', 'buddyboss')}
                            </li>
                            <li className="bb-rl-splash-content-product-feature">
                                <i className="bb-icons-rl-check"></i>
                                {__('Supports any WordPress Theme', 'buddyboss')}
                            </li>
                        </ul>
                        <a className="bb-rl-button" onClick={handleGetStarted}>
                            {__('Configure ReadyLaunch', 'buddyboss')}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}; 