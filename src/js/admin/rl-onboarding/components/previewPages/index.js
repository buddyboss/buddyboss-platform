import { Header } from './Header';
import { Sidebar } from './LeftSidebar';
import { Feed } from './Feed';
import { RightSidebar } from './RightSidebar';

export const PreviewPages = () => {
    return (
        <div className="bb-rl-preview-activity">
            <Header />

            <div className="bb-rl-preview-content">
                <Sidebar />

                <div className="bb-rl-preview-main">
                    <Feed />
                </div>

                <RightSidebar />
            </div>
        </div>
    );
}; 