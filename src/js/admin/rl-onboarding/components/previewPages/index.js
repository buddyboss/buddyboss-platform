import { Header } from './Header';
import { Sidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { FeedRightSidebar } from './FeedRightSidebar';

export const PreviewPages = () => {
    return (
        <div className="bb-rl-preview-activity">
            <Header />

            <div className="bb-rl-preview-content">
                <Sidebar />

                <div className="bb-rl-preview-main">
                    <Feed />
                </div>

                <FeedRightSidebar />
            </div>
        </div>
    );
}; 