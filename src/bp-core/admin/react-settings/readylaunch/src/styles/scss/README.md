# SASS Styling for BuddyBoss ReadyLaunch Settings

This directory contains SCSS files used to style the ReadyLaunch settings interface.

## Directory Structure

```
scss/
  ├── _variables.scss    # Global variables (colors, dimensions, etc.)
  ├── _mixins.scss       # Reusable mixins
  ├── _buttons.scss      # Button component styles
  ├── settings.scss      # Main SCSS file that imports all partials
  ├── build.js           # Node.js script for compiling SCSS
  └── compile.sh         # Helper bash script to compile SCSS
```

## Working with SCSS

### Development Workflow

1. Make changes to the SCSS files in this directory
2. Compile to CSS using one of the methods below
3. The compiled CSS will be available in the parent directory (`../settings.css`)

### Compiling SCSS to CSS

#### Method 1: Using npm scripts (Recommended)

From the `react-settings` directory, run:

```bash
# One-time build (compile SCSS and build JS)
npm run build

# Watch for changes during development
npm run start
```

This will:
- Compile your SCSS files to CSS
- Start webpack in watch mode
- Automatically recompile when changes are detected

#### Method 2: Using dedicated SCSS scripts

From the `react-settings` directory, run:

```bash
# Just compile SCSS to CSS
npm run build:scss

# Watch SCSS files for changes
npm run watch:scss
```

## Adding New SCSS Files

1. Create a new SCSS file in this directory (use underscore prefix for partials)
2. Import it in `settings.scss` using `@import 'filename';`
3. Compile to CSS as described above

## SCSS Organization

The SCSS files are organized following a component-based approach:

- **_variables.scss**: Global variables for colors, dimensions, etc.
- **_mixins.scss**: Reusable mixins for common patterns
- **_buttons.scss**: Styles for button components
- **settings.scss**: Main file that imports all partials

## Best Practices

1. Use variables from `_variables.scss` for consistent styling
2. Leverage mixins for reusable code patterns
3. Maintain semantic nesting (don't nest too deeply)
4. Keep files organized by component or functionality
5. Use the BEM naming convention for classes

## Troubleshooting

If you encounter build errors:

1. Make sure all SCSS syntax is valid
2. Check that imports are correctly specified
3. Run `npm run build:scss` separately to isolate SCSS compilation issues
4. Check the Node.js version (requires Node.js 14+)

## Reference

- [SASS Documentation](https://sass-lang.com/documentation)
- [SASS Guidelines](https://sass-guidelin.es/) 