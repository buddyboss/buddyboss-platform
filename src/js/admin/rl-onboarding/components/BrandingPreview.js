import { __ } from '@wordpress/i18n';

export const BrandingPreview = ({ formData = {} }) => {
    // Get the logo URLs from form data (now stored as objects with url property)
    const lightLogo = formData.bb_rl_light_logo?.url || (typeof formData.bb_rl_light_logo === 'string' ? formData.bb_rl_light_logo : null);
    const darkLogo = formData.bb_rl_dark_logo?.url || (typeof formData.bb_rl_dark_logo === 'string' ? formData.bb_rl_dark_logo : null);
    const primaryColor = formData.bb_rl_color_light || formData.brand_colors || '#e57e3a';
    
    // Get the current theme's default styling
    const previewStyle = {
        '--preview-primary-color': primaryColor,
        '--preview-logo-light': lightLogo ? `url(${lightLogo})` : 'none',
        '--preview-logo-dark': darkLogo ? `url(${darkLogo})` : 'none'
    };

    return (
        <div className="bb-rl-branding-preview" style={previewStyle}>
            <div className="bb-rl-preview-header">
                <h4>{__('Live Preview', 'buddyboss')}</h4>
                <p>{__('See how your branding will look', 'buddyboss')}</p>
            </div>

            {/* Light Mode Preview */}
            <div className="bb-rl-preview-section">
                <div className="bb-rl-preview-label">
                    <span className="bb-rl-preview-mode-icon">☀️</span>
                    {__('Light Mode', 'buddyboss')}
                </div>
                <div className="bb-rl-preview-mockup bb-rl-preview-light">
                    <div className="bb-rl-preview-header-bar" style={{ backgroundColor: primaryColor }}>
                        <div className="bb-rl-preview-logo">
                            {lightLogo ? (
                                <img src={lightLogo} alt={__('Site Logo Light', 'buddyboss')} />
                            ) : (
                                <div className="bb-rl-preview-logo-placeholder">
                                    {__('Your Logo', 'buddyboss')}
                                </div>
                            )}
                        </div>
                        <div className="bb-rl-preview-nav">
                            <div className="bb-rl-preview-nav-item"></div>
                            <div className="bb-rl-preview-nav-item"></div>
                            <div className="bb-rl-preview-nav-item"></div>
                        </div>
                    </div>
                    <div className="bb-rl-preview-content">
                        <div className="bb-rl-preview-activity">
                            <div className="bb-rl-preview-activity-item"></div>
                            <div className="bb-rl-preview-activity-item"></div>
                            <div className="bb-rl-preview-activity-item"></div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Dark Mode Preview */}
            <div className="bb-rl-preview-section">
                <div className="bb-rl-preview-label">
                    <span className="bb-rl-preview-mode-icon">🌙</span>
                    {__('Dark Mode', 'buddyboss')}
                </div>
                <div className="bb-rl-preview-mockup bb-rl-preview-dark">
                    <div className="bb-rl-preview-header-bar" style={{ backgroundColor: primaryColor }}>
                        <div className="bb-rl-preview-logo">
                            {darkLogo ? (
                                <img src={darkLogo} alt={__('Site Logo Dark', 'buddyboss')} />
                            ) : lightLogo ? (
                                <img src={lightLogo} alt={__('Site Logo', 'buddyboss')} />
                            ) : (
                                <div className="bb-rl-preview-logo-placeholder">
                                    {__('Your Logo', 'buddyboss')}
                                </div>
                            )}
                        </div>
                        <div className="bb-rl-preview-nav">
                            <div className="bb-rl-preview-nav-item"></div>
                            <div className="bb-rl-preview-nav-item"></div>
                            <div className="bb-rl-preview-nav-item"></div>
                        </div>
                    </div>
                    <div className="bb-rl-preview-content">
                        <div className="bb-rl-preview-activity">
                            <div className="bb-rl-preview-activity-item"></div>
                            <div className="bb-rl-preview-activity-item"></div>
                            <div className="bb-rl-preview-activity-item"></div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Color Preview */}
            {primaryColor && primaryColor !== '#e57e3a' && (
                <div className="bb-rl-preview-section">
                    <div className="bb-rl-preview-label">
                        {__('Primary Color', 'buddyboss')}
                    </div>
                    <div className="bb-rl-preview-color-demo">
                        <div className="bb-rl-preview-color-swatch" style={{ backgroundColor: primaryColor }}></div>
                        <span className="bb-rl-preview-color-value">{primaryColor}</span>
                    </div>
                </div>
            )}

            {/* Tips */}
            <div className="bb-rl-preview-tips">
                <h5>{__('Pro Tips:', 'buddyboss')}</h5>
                <ul>
                    <li>{__('Upload different logos for light and dark modes for best visibility', 'buddyboss')}</li>
                    <li>{__('Use transparent PNG files for logos with the best results', 'buddyboss')}</li>
                    <li>{__('Recommended logo size: 280x80px or larger', 'buddyboss')}</li>
                </ul>
            </div>
        </div>
    );
}; 