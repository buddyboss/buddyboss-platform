import { PreviewHeader } from './PreviewHeader';
import { PreviewSidebar } from './PreviewSidebar';

export const PreviewPages = () => {
    return (
        <div className="bb-rl-preview-activity">
            <PreviewHeader />

            <div className="bb-rl-preview-content">
                <PreviewSidebar />

                <div className="bb-rl-preview-main">
                    {/* Main content area - can be populated later */}
                </div>
            </div>
        </div>
    );
}; 