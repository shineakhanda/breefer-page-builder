import type { Config } from 'tailwindcss';

/**
 * Tailwind is used by the editor only. It is never loaded on the frontend.
 *
 * Preflight is disabled because wp-admin already has a reset, and utilities
 * are prefixed so they cannot collide with WordPress or plugin markup.
 */
export default {
  content: ['./src/**/*.{ts,tsx}', './templates/**/*.php'],
  prefix: 'br-',
  important: '#breefer-editor-root',
  corePlugins: {
    preflight: false,
  },
  theme: {
    extend: {
      colors: {
        accent: 'var(--breefer-accent)',
        'accent-2': 'var(--breefer-accent-2)',
        'accent-soft': 'var(--breefer-accent-soft)',
        surface: 'var(--breefer-surface)',
        'surface-2': 'var(--breefer-surface-2)',
        canvas: 'var(--breefer-bg)',
        ink: 'var(--breefer-text)',
        muted: 'var(--breefer-muted)',
        line: 'var(--breefer-border)',
      },
      fontFamily: {
        sans: ['var(--breefer-font-sans)'],
        mono: ['var(--breefer-font-mono)'],
      },
      borderRadius: {
        md: '14px',
        lg: '22px',
        xl: '26px',
      },
      boxShadow: {
        card: 'var(--breefer-shadow-sm)',
        panel: 'var(--breefer-shadow-md)',
      },
    },
  },
  plugins: [],
} satisfies Config;
