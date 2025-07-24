export const Feed = () => {
    return (
        <div className="bb-rl-preview-feed">
            {/* Feed Form */}
            <div className="bb-rl-preview-feed-form">
                <div className="bb-rl-preview-feed-form-avatar">
                    <img src="http://localhost:8888/bb/wp-content/uploads/avatars/2/67b8591eb4224-bpthumb.jpg" alt="User Avatar" />
                </div>
                <div className="bb-rl-preview-feed-form-input">
                    <span className="bb-rl-preview-feed-placeholder">Share what's on your mind</span>
                </div>
            </div>

            {/* Feed Filters */}
            <div className="bb-rl-preview-feed-filters">
                <div className="bb-rl-preview-filter-item">
                    <i className="bb-icons-rl-funnel-simple"></i>
                    <span>All Posts</span>
                    <i className="bb-icons-rl-caret-down"></i>
                </div>
                <div className="bb-rl-preview-filter-item">
                    <i className="bb-icons-rl-funnel-simple"></i>
                    <span>Recent</span>
                    <i className="bb-icons-rl-caret-down"></i>
                </div>
            </div>

            {/* Activity Post */}
            <div className="bb-rl-preview-activity-post">
                <div className="bb-rl-preview-post-header">
                    <div className="bb-rl-preview-post-avatar">
                        <img src="http://localhost:8888/bb/wp-content/uploads/avatars/2/67b8591eb4224-bpthumb.jpg" alt="Mira Zai" />
                    </div>
                    <div className="bb-rl-preview-post-meta">
                        <div className="bb-rl-preview-post-author">Mira Zai</div>
                        <div className="bb-rl-preview-post-time">
                            <span>33 minutes</span>
                            <i className="bb-icons-rl-globe"></i>
                        </div>
                    </div>
                    <div className="bb-rl-preview-post-menu">
                        <i className="bb-icons-rl-dots-three"></i>
                    </div>
                </div>

                <div className="bb-rl-preview-post-content">
                    <div className="bb-rl-preview-post-text">
                        <h3>Embrace cutting-edge technology to transform your business.</h3>
                        <p>Unlock your potential through immersive educational journeys. Gain skills that empower you to thrive in an ever-changing world.</p>
                    </div>
                    <div className="bb-rl-preview-post-image">
                        <div className="bb-rl-preview-post-image-placeholder"></div>
                    </div>
                </div>

                <div className="bb-rl-preview-post-actions">
                    <div className="bb-rl-preview-action-reactions">
                        <div className="bb-rl-preview-reaction-item">
                            <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}emoji.png`} alt="emoji" />
                            <span>Wow</span>
                        </div>
                        <div className="bb-rl-preview-reaction-item bb-rl-preview-reaction-item-comment">
                            <i className="bb-icons-rl-chat-circle"></i>
                            <span>Comment</span>
                        </div>
                    </div>
                    <div className="bb-rl-preview-action-stats">
                        <div className="bb-rl-preview-reaction-icons">
                            <span className="bb-rl-preview-reaction-icon bb-rl-preview-like">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}thumb-up.png`} alt="emoji" />
                            </span>
                            <span className="bb-rl-preview-reaction-icon bb-rl-preview-love">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}emoji.png`} alt="emoji" />
                            </span>
                            <span className="bb-rl-preview-reaction-icon bb-rl-preview-wow">
                                <img src={`${window.bbRlOnboarding?.assets?.assetsUrl || ''}heart.png`} alt="emoji" />
                            </span>
                        </div>
                        <span className="bb-rl-preview-reaction-count">12 reactions</span>
                        <span className="bb-rl-preview-comment-count">5 comments</span>
                    </div>
                </div>
            </div>
        </div>
    );
};