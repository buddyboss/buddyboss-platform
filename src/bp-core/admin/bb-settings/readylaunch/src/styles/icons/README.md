# BB Icons for ReadyLaunch

This directory contains the BB Icons font files and CSS for use in the BuddyBoss Platform ReadyLaunch React Settings.

## Usage

The font icons are already imported in the main `settings.scss` file, so they are available throughout the ReadyLaunch React Settings components.

### Basic Usage

```jsx
// Using an icon with default weight (400)
<span className="bb-icons-rl bb-icons-rl-user"></span>
```

### Icon Weights

BB Icons come in 6 different weights:

```jsx
// Thin (200)
<span className="bb-icons-rl bb-icons-rl-user bb-icons-rl-thin"></span>

// Light (300)
<span className="bb-icons-rl bb-icons-rl-user bb-icons-rl-light"></span>

// Regular (400) - default
<span className="bb-icons-rl bb-icons-rl-user"></span>

// Fill (500)
<span className="bb-icons-rl bb-icons-rl-user bb-icons-rl-fill"></span>

// Duotone (600)
<span className="bb-icons-rl bb-icons-rl-user bb-icons-rl-duotone"></span>

// Bold (700)
<span className="bb-icons-rl bb-icons-rl-user bb-icons-rl-bold"></span>
```

### Sizing and Styling

Icons inherit font size and color from their parent element, but you can override these with CSS:

```jsx
// Using inline styles
<span 
  className="bb-icons-rl bb-icons-rl-user" 
  style={{ fontSize: '24px', color: 'blue' }}
></span>

// Using CSS classes
<span className="bb-icons-rl bb-icons-rl-user my-custom-icon-class"></span>
```

```css
.my-custom-icon-class {
  font-size: 24px;
  color: blue;
}
```

## Original Source

These icons are sourced from the BuddyBoss Platform ReadyLaunch theme and utilize Phosphor Icons (MIT License). 