import type { CSSProperties } from 'react';
import type { BuilderNode, Viewport } from '../types';

function num(value: unknown): number | undefined {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : undefined;
  }

  return undefined;
}

function clampInt(value: number, min: number, max: number): number {
  return Math.max(min, Math.min(max, Math.round(value)));
}

function px(value: number): string {
  return `${value}px`;
}

function color(value: unknown): string | undefined {
  if (typeof value !== 'string') {
    return undefined;
  }

  // "global:primary" resolves to the design-system custom property.
  const token = /^global:([a-z]+)$/.exec(value);

  if (token) {
    return `var(--br-color-${token[1]})`;
  }

  return /^#[0-9a-f]{3,8}$/i.test(value) ? value : undefined;
}

function url(value: unknown): string | undefined {
  if (typeof value !== 'string' || !/^https?:\/\//i.test(value)) {
    return undefined;
  }

  return value.replace(/["']/g, '');
}

function align(value: unknown): CSSProperties['textAlign'] | undefined {
  return value === 'left' || value === 'center' || value === 'right' ? value : undefined;
}

const ALIGN_ITEMS = ['stretch', 'flex-start', 'center', 'flex-end'] as const;
const JUSTIFY = ['flex-start', 'center', 'flex-end', 'space-between'] as const;

const SHADOWS: Record<string, string> = {
  soft: '0 10px 30px -18px rgba(30,23,53,.35)',
  medium: '0 20px 50px -28px rgba(30,23,53,.45)',
  strong: '0 30px 70px -34px rgba(30,23,53,.55)',
};

/**
 * Resolve a setting for the active device, inheriting tablet -> mobile.
 */
export function resolveSetting(
  settings: Record<string, unknown>,
  key: string,
  viewport: Viewport,
): unknown {
  if (viewport === 'mobile') {
    const mobile = settings[`${key}_mobile`];

    if (mobile !== undefined) {
      return mobile;
    }

    const tablet = settings[`${key}_tablet`];

    if (tablet !== undefined) {
      return tablet;
    }
  }

  if (viewport === 'tablet') {
    const tablet = settings[`${key}_tablet`];

    if (tablet !== undefined) {
      return tablet;
    }
  }

  return settings[key];
}

function v(settings: Record<string, unknown>, key: string, viewport: Viewport): unknown {
  return resolveSetting(settings, key, viewport);
}

export function isHiddenOn(settings: Record<string, unknown>, viewport: Viewport): boolean {
  const key =
    viewport === 'desktop' ? 'hideDesktop' : viewport === 'tablet' ? 'hideTablet' : 'hideMobile';

  return Boolean(settings[key]);
}

/**
 * Styles for the renderer wrapper, mirroring the PHP StyleGenerator.
 */
export function nodeStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const style: CSSProperties = {};

  const margin = num(v(settings, 'margin', viewport));

  if (margin !== undefined) {
    style.margin = px(margin);
  }

  const textAlign = align(v(settings, 'align', viewport));

  if (textAlign) {
    style.textAlign = textAlign;
  }

  const textColor = color(v(settings, 'color', viewport));

  if (textColor) {
    style.color = textColor;
  }

  if (node.type !== 'button' && node.type !== 'iconbox') {
    const padding = num(v(settings, 'padding', viewport));

    if (padding !== undefined) {
      style.padding = px(padding);
    }

    const background = color(v(settings, 'background', viewport));

    if (background) {
      style.backgroundColor = background;
    }

    const gradientFrom = color(v(settings, 'gradientFrom', viewport));
    const gradientTo = color(v(settings, 'gradientTo', viewport));

    if (gradientFrom && gradientTo) {
      const angle = num(v(settings, 'gradientAngle', viewport)) ?? 135;
      style.backgroundImage = `linear-gradient(${angle}deg, ${gradientFrom}, ${gradientTo})`;
    } else {
      const image = url(v(settings, 'backgroundImage', viewport));

      if (image) {
        style.backgroundImage = `url("${image}")`;
        style.backgroundSize = 'cover';
        style.backgroundPosition = 'center';
        style.backgroundRepeat = 'no-repeat';
      }
    }

    const radius = num(v(settings, 'radius', viewport));

    if (radius !== undefined) {
      style.borderRadius = px(radius);
    }

    const minHeight = num(v(settings, 'minHeight', viewport));

    if (minHeight !== undefined) {
      style.minHeight = px(minHeight);
    }

    const borderWidth = num(v(settings, 'borderWidth', viewport));
    const borderColor = color(v(settings, 'borderColor', viewport));

    if (borderWidth !== undefined && borderWidth > 0 && borderColor) {
      style.border = `${borderWidth}px solid ${borderColor}`;
    }

    const shadow = v(settings, 'shadow', viewport);
    const shadowValue = typeof shadow === 'string' ? SHADOWS[shadow] : undefined;

    if (shadowValue) {
      style.boxShadow = shadowValue;
    }
  }

  if (node.type === 'container' && v(settings, 'layout', viewport) === 'free') {
    // Free canvas: children are placed absolutely, so the box only needs a height.
    style.display = 'block';
    style.position = 'relative';
  } else if (node.type === 'container' && v(settings, 'layout', viewport) === 'grid') {
    style.display = 'grid';

    // Mirrors the PHP generator: 3 columns, 2 on tablet and 1 on phones unless set.
    const columns = num(v(settings, 'columns', viewport));
    const base = columns !== undefined ? clampInt(columns, 1, 12) : 3;
    const explicit =
      settings[viewport === 'desktop' ? 'columns' : `columns_${viewport}`] !== undefined;
    const count =
      explicit || viewport === 'desktop' ? base : viewport === 'tablet' ? Math.min(base, 2) : 1;

    style.gridTemplateColumns = `repeat(${count}, minmax(0, 1fr))`;

    const gridGap = num(v(settings, 'gap', viewport));

    if (gridGap !== undefined) {
      style.gap = px(gridGap);
    }

    const gridAlign = v(settings, 'alignItems', viewport);

    style.alignItems =
      typeof gridAlign === 'string' && (ALIGN_ITEMS as readonly string[]).includes(gridAlign)
        ? (gridAlign as CSSProperties['alignItems'])
        : 'stretch';
  } else if (node.type === 'container') {
    style.display = 'flex';
    let direction: CSSProperties['flexDirection'] =
      v(settings, 'direction', viewport) === 'row' ? 'row' : 'column';

    if (viewport === 'mobile' && direction === 'row' && settings.direction_mobile === undefined) {
      direction = 'column';
    }

    style.flexDirection = direction;

    const gap = num(v(settings, 'gap', viewport));

    if (gap !== undefined) {
      style.gap = px(gap);
    }

    const alignItems = v(settings, 'alignItems', viewport);
    const justify = v(settings, 'justify', viewport);

    style.alignItems =
      typeof alignItems === 'string' && (ALIGN_ITEMS as readonly string[]).includes(alignItems)
        ? (alignItems as CSSProperties['alignItems'])
        : 'stretch';

    style.justifyContent =
      typeof justify === 'string' && (JUSTIFY as readonly string[]).includes(justify)
        ? (justify as CSSProperties['justifyContent'])
        : 'flex-start';

    const maxWidth = num(v(settings, 'maxWidth', viewport));

    if (maxWidth !== undefined && maxWidth > 0) {
      style.maxWidth = px(maxWidth);
      style.width = '100%';

      if (margin === undefined) {
        style.marginLeft = 'auto';
        style.marginRight = 'auto';
      }
    }
  }

  if (node.type === 'spacer') {
    const height = num(v(settings, 'height', viewport));

    if (height !== undefined) {
      style.height = px(height);
    }
  }

  const boxWidth = num(v(settings, 'boxWidth', viewport));

  if (boxWidth !== undefined) {
    style.width = `${Math.max(1, Math.min(200, boxWidth))}%`;
  }

  const boxHeight = num(v(settings, 'boxHeight', viewport));

  if (boxHeight !== undefined) {
    style.height = px(Math.max(0, Math.min(4000, boxHeight)));
  }

  const position = v(settings, 'position', viewport);

  if (position === 'relative' || position === 'absolute' || position === 'sticky') {
    style.position = position;
  }

  const rotate = num(v(settings, 'rotate', viewport));

  if (rotate !== undefined && rotate !== 0) {
    style.transform = `rotate(${rotate}deg)`;
  }

  const opacity = num(v(settings, 'opacity', viewport));

  if (opacity !== undefined && opacity < 1) {
    style.opacity = Math.max(0, opacity);
  }

  if (settings.overflowHidden) {
    style.overflow = 'hidden';
  }

  const offsetTop = num(v(settings, 'offsetTop', viewport));
  const offsetLeft = num(v(settings, 'offsetLeft', viewport));
  const offsetRight = num(v(settings, 'offsetRight', viewport));
  const offsetBottom = num(v(settings, 'offsetBottom', viewport));

  if (offsetTop !== undefined) {
    style.top = px(Math.max(-3000, Math.min(3000, offsetTop)));
  }

  if (offsetLeft !== undefined) {
    style.left = px(Math.max(-3000, Math.min(3000, offsetLeft)));
  }

  if (offsetRight !== undefined) {
    style.right = px(Math.max(-3000, Math.min(3000, offsetRight)));
  }

  if (offsetBottom !== undefined) {
    style.bottom = px(Math.max(-3000, Math.min(3000, offsetBottom)));
  }

  const zIndex = num(v(settings, 'zIndex', viewport));

  if (zIndex !== undefined) {
    style.zIndex = Math.max(0, Math.min(9999, Math.round(zIndex)));
  }

  return style;
}

export function headingStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const style: CSSProperties = { margin: 0 };
  const fontSize = num(v(settings, 'fontSize', viewport));
  const weight = num(v(settings, 'fontWeight', viewport));
  const lineHeight = num(v(settings, 'lineHeight', viewport));

  if (fontSize !== undefined) {
    style.fontSize = px(fontSize);
  }

  if (weight !== undefined) {
    style.fontWeight = weight;
  }

  if (lineHeight !== undefined) {
    style.lineHeight = lineHeight;
  }

  return style;
}

export function textStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const style: CSSProperties = {};
  const fontSize = num(v(settings, 'fontSize', viewport));
  const lineHeight = num(v(settings, 'lineHeight', viewport));

  if (fontSize !== undefined) {
    style.fontSize = px(fontSize);
  }

  if (lineHeight !== undefined) {
    style.lineHeight = lineHeight;
  }

  return style;
}

export function buttonStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const style: CSSProperties = {
    display: 'inline-block',
    fontWeight: 600,
    textDecoration: 'none',
    cursor: 'pointer',
  };
  const background = color(v(settings, 'background', viewport));
  const textColor = color(v(settings, 'color', viewport));
  const radius = num(v(settings, 'radius', viewport));
  const padding = num(v(settings, 'padding', viewport)) ?? 14;
  const fontSize = num(v(settings, 'fontSize', viewport));

  if (background) {
    style.backgroundColor = background;
  }

  if (textColor) {
    style.color = textColor;
  }

  if (radius !== undefined) {
    style.borderRadius = px(radius);
  }

  style.padding = `${padding}px ${padding * 2}px`;

  if (fontSize !== undefined) {
    style.fontSize = px(fontSize);
  }

  return style;
}

export function imageStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const style: CSSProperties = { display: 'block', maxWidth: '100%', height: 'auto' };
  const width = num(v(settings, 'width', viewport));
  const radius = num(v(settings, 'radius', viewport));

  if (width !== undefined) {
    style.width = `${width}%`;
    style.marginLeft = 'auto';
    style.marginRight = 'auto';
  }

  if (radius !== undefined) {
    style.borderRadius = px(radius);
  }

  return style;
}

export function dividerStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const thickness = num(v(settings, 'thickness', viewport)) ?? 1;
  const width = num(v(settings, 'width', viewport)) ?? 100;
  const lineColor = color(v(settings, 'color', viewport)) ?? '#e8e1f7';

  return {
    width: `${width}%`,
    margin: '0 auto',
    border: 0,
    borderTop: `${thickness}px solid ${lineColor}`,
  };
}

export function iconBoxStyles(
  node: BuilderNode,
  viewport: Viewport = 'desktop',
): {
  box: CSSProperties;
  icon: CSSProperties;
  title: CSSProperties;
  text: CSSProperties;
} {
  const settings = node.settings;
  const background = color(v(settings, 'background', viewport));
  const radius = num(v(settings, 'radius', viewport));
  const padding = num(v(settings, 'padding', viewport));
  const iconColor = color(v(settings, 'iconColor', viewport));
  const titleColor = color(v(settings, 'titleColor', viewport));
  const textColor = color(v(settings, 'textColor', viewport));
  const iconSize = num(v(settings, 'iconSize', viewport));
  const titleSize = num(v(settings, 'titleSize', viewport));
  const textSize = num(v(settings, 'textSize', viewport));

  return {
    box: {
      display: 'flex',
      flexDirection: 'column',
      gap: '10px',
      padding: padding !== undefined ? px(padding) : undefined,
      borderRadius: radius !== undefined ? px(radius) : undefined,
      backgroundColor: background,
    },
    icon: {
      color: iconColor,
      fontSize: iconSize !== undefined ? px(iconSize) : undefined,
      width: '1em',
      height: '1em',
    },
    title: {
      margin: 0,
      color: titleColor,
      fontSize: titleSize !== undefined ? px(titleSize) : undefined,
    },
    text: {
      margin: 0,
      color: textColor,
      fontSize: textSize !== undefined ? px(textSize) : undefined,
    },
  };
}

export function iconStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;

  return {
    color: color(v(settings, 'iconColor', viewport)),
    fontSize: px(num(v(settings, 'iconSize', viewport)) ?? 32),
    lineHeight: 1,
  };
}

export function iconListStyles(
  node: BuilderNode,
  viewport: Viewport = 'desktop',
): { list: CSSProperties; item: CSSProperties; icon: CSSProperties } {
  const settings = node.settings;

  return {
    list: {
      display: 'flex',
      flexDirection: 'column',
      gap: px(num(v(settings, 'gap', viewport)) ?? 10),
      margin: 0,
      padding: 0,
      listStyle: 'none',
      color: color(v(settings, 'textColor', viewport)),
      fontSize: px(num(v(settings, 'fontSize', viewport)) ?? 15),
    },
    item: {
      display: 'flex',
      alignItems: 'center',
      gap: px(num(v(settings, 'iconGap', viewport)) ?? 10),
    },
    icon: {
      flex: 'none',
      color: color(v(settings, 'iconColor', viewport)),
      fontSize: px(num(v(settings, 'iconSize', viewport)) ?? 18),
    },
  };
}

export function videoStyle(node: BuilderNode, viewport: Viewport = 'desktop'): CSSProperties {
  const settings = node.settings;
  const ratio = v(settings, 'ratio', viewport);
  const allowed = ['16 / 9', '4 / 3', '1 / 1', '9 / 16'];

  return {
    aspectRatio: typeof ratio === 'string' && allowed.includes(ratio) ? ratio : '16 / 9',
    borderRadius: px(num(v(settings, 'radius', viewport)) ?? 0),
    overflow: 'hidden',
    width: '100%',
  };
}

export function starStyles(
  node: BuilderNode,
  viewport: Viewport = 'desktop',
): { container: CSSProperties; star: CSSProperties } {
  const settings = node.settings;
  const size = px(num(v(settings, 'size', viewport)) ?? 22);

  return {
    container: {
      display: 'inline-flex',
      gap: '4px',
      color: color(v(settings, 'color', viewport)) ?? '#f5b301',
    },
    star: {
      width: size,
      height: size,
      flex: 'none',
    },
  };
}

export function progressStyles(
  node: BuilderNode,
  viewport: Viewport = 'desktop',
): { head: CSSProperties; track: CSSProperties; fill: CSSProperties } {
  const settings = node.settings;
  const radius = px(num(v(settings, 'radius', viewport)) ?? 99);

  return {
    head: {
      display: 'flex',
      justifyContent: 'space-between',
      gap: '12px',
      marginBottom: '8px',
      fontSize: px(num(v(settings, 'fontSize', viewport)) ?? 13),
      color: color(v(settings, 'textColor', viewport)),
    },
    track: {
      width: '100%',
      height: px(num(v(settings, 'height', viewport)) ?? 10),
      overflow: 'hidden',
      borderRadius: radius,
      backgroundColor: color(v(settings, 'trackColor', viewport)) ?? '#ece8ff',
    },
    fill: {
      height: '100%',
      borderRadius: radius,
      backgroundColor: color(v(settings, 'color', viewport)) ?? '#7c5cfc',
    },
  };
}
