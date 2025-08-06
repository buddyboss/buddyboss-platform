/**
 * Generate color shades from base color (500 level).
 *
 * @param {string} baseColor - Hex color code (will be used as level 500).
 * @returns {Object} Object of color shades from 100 to 900.
 */
function generateColorShades(baseColor) {
    baseColor = baseColor.replace('#', '').toUpperCase();

    // If the base color is the default #4946FE, return the exact palette
    if (baseColor === '4946FE') {
        return {
            100: '#DDE4FF',
            200: '#C2CDFF',
            300: '#9DABFF',
            400: '#767EFF',
            500: '#4946FE',
            600: '#4937F4',
            700: '#3E2BD7',
            800: '#3325AE',
            900: '#2E2689'
        };
    }

    // For other colors, use HSL-based generation
    const hsl = hexToHsl(baseColor);
    const shades = {};

    const adjustments = {
        100: { h: 4, s: -0.35, l: 0.42 },
        200: { h: 2, s: -0.20, l: 0.32 },
        300: { h: 1, s: -0.10, l: 0.22 },
        400: { h: 0, s: -0.02, l: 0.12 },
        500: { h: 0, s: 0, l: 0 },
        600: { h: -1, s: 0.03, l: -0.08 },
        700: { h: -3, s: 0.08, l: -0.18 },
        800: { h: -6, s: 0.12, l: -0.32 },
        900: { h: -8, s: 0.18, l: -0.45 }
    };

    for (const [level, adj] of Object.entries(adjustments)) {
        if (level == 500) {
            shades[level] = '#' + baseColor;
        } else {
            // Apply HSL adjustments
            const newH = hsl.h + adj.h;
            const newS = Math.max(0, Math.min(1, hsl.s + adj.s));
            const newL = Math.max(0, Math.min(1, hsl.l + adj.l));

            // Convert back to hex
            shades[level] = hslToHex(newH, newS, newL);
        }
    }

    return shades;
}

/**
 * Convert hex color to HSL.
 *
 * @param {string} hex - Hex color without #.
 * @returns {Object} HSL values.
 */
function hexToHsl(hex) {
    const r = parseInt(hex.substr(0, 2), 16) / 255;
    const g = parseInt(hex.substr(2, 2), 16) / 255;
    const b = parseInt(hex.substr(4, 2), 16) / 255;

    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const diff = max - min;

    // Lightness
    const l = (max + min) / 2;

    let h, s;

    if (diff === 0) {
        h = s = 0;
    } else {
        s = l > 0.5 ? diff / (2 - max - min) : diff / (max + min);

        // Use small epsilon for floating point comparison
        const epsilon = 1e-10;
        
        if (Math.abs(max - r) < epsilon) {
            // case $r:
            h = (g - b) / diff + (g < b ? 6 : 0);
        } else if (Math.abs(max - g) < epsilon) {
            // case $g:
            h = (b - r) / diff + 2;
        } else if (Math.abs(max - b) < epsilon) {
            // case $b:
            h = (r - g) / diff + 4;
        }
        
        h /= 6;
    }

    return { h: h * 360, s: s, l: l };
}

/**
 * Convert HSL to hex color.
 * Exact replica of PHP bb_rl_hsl_to_hex()
 *
 * @param {number} h - Hue (0-360).
 * @param {number} s - Saturation (0-1).
 * @param {number} l - Lightness (0-1).
 * @returns {string} Hex color with #.
 */
function hslToHex(h, s, l) {
    h = h % 360;
    if (h < 0) {
        h += 360;
    }
    h /= 360;

    let r, g, b;

    if (s === 0) {
        r = g = b = l;
    } else {
        const hue2rgb = (p, q, t) => {
            if (t < 0) t += 1;
            if (t > 1) t -= 1;
            if (t < 1/6) return p + (q - p) * 6 * t;
            if (t < 1/2) return q;
            if (t < 2/3) return p + (q - p) * (2/3 - t) * 6;
            return p;
        };

        const q = l < 0.5 ? l * (1 + s) : l + s - l * s;
        const p = 2 * l - q;

        r = hue2rgb(p, q, h + 1/3);
        g = hue2rgb(p, q, h);
        b = hue2rgb(p, q, h - 1/3);
    }

    const rHex = Math.round(r * 255).toString(16).padStart(2, '0');
    const gHex = Math.round(g * 255).toString(16).padStart(2, '0');
    const bHex = Math.round(b * 255).toString(16).padStart(2, '0');
    
    return `#${rHex}${gHex}${bHex}`.toUpperCase();
}

/**
 * Generate dynamic CSS custom properties for ReadyLaunch colors.
 *
 * @param {string} colorLight - Light mode primary color.
 * @param {string} colorDark - Dark mode primary color.
 * @returns {string} CSS string with custom properties.
 */
function generateDynamicColorCSS(colorLight, colorDark) {
    // Generate color shades for light mode (500 is base)
    let lightShades = {};
    if( colorLight ) {
        lightShades = generateColorShades(colorLight);
    }
    
    // Generate color shades for dark mode (500 is base)
    let darkShades = {};
    if( colorDark ) {
        darkShades = generateColorShades(colorDark);
    }

    return `
        ${ colorLight ? `
        :root {
            /* Light mode color shades */
            --bb-rl-preview-background-brand-secondary-color: ${lightShades[100]};
            --bb-rl-preview-text-brand-primary-color: ${lightShades[500]};
            --bb-rl-preview-background-brand-color: ${lightShades[500]};
            --bb-rl-preview-border-brand-secondary-color: ${lightShades[500]};
            --bb-rl-preview-text-brand-secondary-color: ${lightShades[800]};
            
            /* Keep backward compatibility */
            --bb-rl-primary-color: ${colorLight};
        }` : ''}

        ${ colorDark ? `
            .bb-rl-preview-theme-dark {
                /* Dark mode color shades */
                --bb-rl-preview-text-brand-secondary-color: ${darkShades[200]};
                --bb-rl-preview-background-brand-secondary-color: ${darkShades[800]};
                --bb-rl-preview-text-brand-primary-color: ${darkShades[500]};
                --bb-rl-preview-background-brand-color: ${darkShades[500]};
                --bb-rl-preview-border-brand-secondary-color: ${darkShades[500]};
                
                /* Keep backward compatibility */
                --bb-rl-primary-color: ${colorDark};
        }` : ''}
    `.trim();
}

/**
 * Apply dynamic colors to the page by injecting CSS.
 *
 * @param {string} colorLight - Light mode primary color.
 * @param {string} colorDark - Dark mode primary color.
 */
export function applyDynamicColors(colorLight, colorDark) {
    const cssString = generateDynamicColorCSS(colorLight, colorDark);
    
    // Remove existing dynamic color styles
    const existingStyle = document.getElementById(`bb-rl-dynamic-colors-${colorLight ? 'bb-rl-dynamic-colors-light' : ''}${colorDark ? 'bb-rl-dynamic-colors-dark' : ''}`);
    if (existingStyle) {
        existingStyle.remove();
    }
    
    // Add new dynamic color styles
    const style = document.createElement('style');
    style.id = `bb-rl-dynamic-colors-${colorLight ? 'bb-rl-dynamic-colors-light' : ''}${colorDark ? 'bb-rl-dynamic-colors-dark' : ''}`;
    style.textContent = cssString;
    document.head.appendChild(style);
}