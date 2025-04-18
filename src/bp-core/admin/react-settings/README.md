# BuddyBoss Platform React Settings

This is the React-based settings interface for BuddyBoss Platform's ReadyLaunch feature.

## Development Setup

### Prerequisites

- Node.js (v14 or later)
- npm or yarn

### Installation

1. Navigate to the react-settings directory:
   ```
   cd src/bp-core/admin/react-settings
   ```

2. Install dependencies:
   ```
   npm install
   ```

### Development

To start the development server with hot reloading:

```
npm run start
```

This will compile the JavaScript files and watch for changes.

### Production Build

To build for production:

```
npm run build
```

This will create optimized files in the `readylaunch/build` directory.

## Project Structure

- `readylaunch/src/index.js` - Main entry point
- `readylaunch/src/components/` - React components
- `readylaunch/src/utils/` - Utility functions and API
- `readylaunch/src/styles/` - CSS styles

## WordPress Integration

The React app is integrated into WordPress via the hook in `readylaunch/index.php`. It targets the element with the ID `bb-readylaunch-settings`. 