import { useMemo, useState, type CSSProperties } from 'react';

import type { Bootstrap, IconDef } from '../types';

const rawIcons: Bootstrap['icons'] = window.breeferEditor?.icons ?? {};

export const iconLibrary: IconDef[] = Object.entries(rawIcons)
  .map(([slug, definition]) => ({
    slug,
    label: definition.label || slug,
    paths: Array.isArray(definition.paths) ? definition.paths : [],
    fill: Boolean(definition.fill),
  }))
  .filter((icon) => icon.paths.length > 0);

export const iconMap: Record<string, IconDef> = Object.fromEntries(
  iconLibrary.map((icon) => [icon.slug, icon]),
);

interface IconSvgProps {
  icon: string;
  className?: string;
  style?: CSSProperties;
}

export function IconSvg({
  icon,
  className = 'br-inline-icon',
  style,
}: IconSvgProps): JSX.Element | null {
  const definition = iconMap[icon];

  if (!definition) {
    return null;
  }

  const filled = Boolean(definition.fill);

  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      width="1em"
      height="1em"
      style={style}
      fill={filled ? 'currentColor' : 'none'}
      stroke={filled ? 'none' : 'currentColor'}
      strokeWidth={filled ? undefined : 1.7}
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden="true"
      focusable="false"
    >
      {definition.paths.map((path, index) => (
        <path key={`${definition.slug}-${index}`} d={path} />
      ))}
    </svg>
  );
}

interface IconPickerProps {
  value: unknown;
  onChange: (value: string) => void;
}

export function IconPicker({ value, onChange }: IconPickerProps): JSX.Element {
  const [query, setQuery] = useState('');
  const selected = typeof value === 'string' ? value : '';

  const filtered = useMemo(() => {
    const needle = query.trim().toLowerCase();

    if (!needle) {
      return iconLibrary;
    }

    return iconLibrary.filter(
      (icon) => icon.slug.includes(needle) || icon.label.toLowerCase().includes(needle),
    );
  }, [query]);

  return (
    <div className="breefer-icon-picker">
      <input
        className="breefer-input breefer-icon-search"
        type="search"
        value={query}
        placeholder="Search icons"
        aria-label="Search icons"
        onChange={(event) => setQuery(event.target.value)}
      />

      <div className="breefer-icon-grid" role="listbox" aria-label="Icon library">
        {filtered.map((icon) => (
          <button
            key={icon.slug}
            type="button"
            role="option"
            aria-selected={icon.slug === selected}
            className={`breefer-icon-choice${icon.slug === selected ? ' is-selected' : ''}`}
            title={icon.label}
            onClick={() => onChange(icon.slug)}
          >
            <IconSvg icon={icon.slug} />
          </button>
        ))}
      </div>

      {filtered.length === 0 ? <p className="breefer-hint">No icons match.</p> : null}

      <p className="breefer-icon-current">
        <IconSvg icon={selected} />
        <code>{selected || 'none'}</code>
      </p>
    </div>
  );
}
