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
                        <p className="bb-rl-splash-content-product-name">{__('BuddyBoss Theme', 'buddyboss')}</p>
                        <p className="bb-rl-splash-content-product-baseline">{__('Customizable WordPress theme', 'buddyboss')}</p>
                        <img 
                            src={`${window.bbRlOnboarding.assets.assetsUrl}readylaunch-preview.jpg`}
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
                        <a className="bb-rl-button">
                            {__('Configure ReadyLaunch', 'buddyboss')}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    );
}; 