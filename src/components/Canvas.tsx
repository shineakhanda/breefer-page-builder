import {
  createElement,
  useEffect,
  useRef,
  useState,
  type MouseEvent as ReactMouseEvent,
} from 'react';

import { startMove } from '../lib/gestures';
import {
  buttonStyle,
  dividerStyle,
  headingStyle,
  iconBoxStyles,
  iconListStyles,
  iconStyle,
  imageStyle,
  isHiddenOn,
  nodeStyle,
  progressStyles,
  resolveSetting,
  starStyles,
  textStyle,
  videoStyle,
} from '../lib/styles';
import { useEditorStore } from '../store/editorStore';
import type { BuilderNode, Viewport } from '../types';
import { widgetMap } from '../widgets/definitions';
import { IconSvg } from './Icons';
import { SelectionOverlay } from './SelectionOverlay';
import { ServerPreview } from './ServerPreview';

const STAR_PATH = 'm12 3 2.7 5.6 6.1.9-4.4 4.3 1 6.1-5.4-2.9-5.4 2.9 1-6.1L3.2 9.5l6.1-.9L12 3Z';

function text(settings: Record<string, unknown>, key: string, fallback = ''): string {
  const value = settings[key];

  return typeof value === 'string' ? value : typeof value === 'number' ? String(value) : fallback;
}

function number(
  settings: Record<string, unknown>,
  key: string,
  viewport: Viewport,
  fallback: number,
): number {
  const value = resolveSetting(settings, key, viewport);

  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : fallback;
  }

  return fallback;
}

function videoEmbed(url: string): string {
  try {
    const parsed = new URL(url);
    const host = parsed.hostname.toLowerCase();

    if (host.includes('youtu.be')) {
      const id = parsed.pathname.replace('/', '');

      return id ? `https://www.youtube-nocookie.com/embed/${id}` : '';
    }

    if (host.includes('youtube.com')) {
      const id = parsed.searchParams.get('v');

      if (id) {
        return `https://www.youtube-nocookie.com/embed/${id}`;
      }

      const match = parsed.pathname.match(/\/(?:embed\/)?([A-Za-z0-9_-]{6,})/);

      return match?.[1] ? `https://www.youtube-nocookie.com/embed/${match[1]}` : '';
    }

    if (host.includes('vimeo.com')) {
      const match = parsed.pathname.match(/\/(\d+)/);

      return match?.[1] ? `https://player.vimeo.com/video/${match[1]}` : '';
    }
  } catch {
    return '';
  }

  return '';
}

function splitItems(raw: string): Array<{ title: string; content: string }> {
  return raw
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)
    .map((line) => {
      const parts = line.split('|');
      const title = (parts.shift() ?? '').trim();

      return { title, content: parts.join('|').trim() };
    });
}

function TabsPreview({ node }: { node: BuilderNode }): JSX.Element {
  const items = splitItems(text(node.settings, 'items'));
  const [active, setActive] = useState(0);

  if (!items.length) {
    return <p className="breefer-hint">Add one tab per line as Title | Content.</p>;
  }

  return (
    <div className="br-tabs">
      <div className="br-tabs-nav" role="tablist">
        {items.map((item, index) => (
          <button
            key={`${item.title}-${index}`}
            type="button"
            role="tab"
            aria-selected={index === active}
            className={index === active ? 'is-active' : ''}
            onClick={(event) => {
              event.stopPropagation();
              setActive(index);
            }}
          >
            {item.title || 'Tab'}
          </button>
        ))}
      </div>
      <div className="br-tab-panel">{items[active]?.content || 'Tab content'}</div>
    </div>
  );
}

function AccordionPreview({ node }: { node: BuilderNode }): JSX.Element {
  const items = splitItems(text(node.settings, 'items'));
  const openFirst = Boolean(node.settings.openFirst);

  return (
    <div className="br-accordion">
      {items.map((item, index) => (
        <details
          key={`${item.title}-${index}`}
          className="br-accordion-item"
          open={openFirst && index === 0}
        >
          <summary>{item.title || 'Accordion item'}</summary>
          <div className="br-accordion-content">{item.content || 'Content'}</div>
        </details>
      ))}
    </div>
  );
}

function WidgetPreview({
  node,
  viewport,
}: {
  node: BuilderNode;
  viewport: Viewport;
}): JSX.Element | null {
  const settings = node.settings;

  // Widgets backed by live site data render on the server.
  if (widgetMap[node.type]?.server) {
    return <ServerPreview type={node.type} settings={settings} />;
  }

  switch (node.type) {
    case 'heading': {
      const level = text(settings, 'level', 'h2');
      const tag = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'].includes(level) ? level : 'h2';

      return createElement(
        tag,
        { className: 'br-heading', style: headingStyle(node, viewport) },
        text(settings, 'text', 'Your headline') || 'Your headline',
      );
    }

    case 'text':
      return (
        <div className="br-text" style={textStyle(node, viewport)}>
          {text(settings, 'content', 'Write something useful.')}
        </div>
      );

    case 'button':
      return (
        <span className="br-btn" style={buttonStyle(node, viewport)}>
          {text(settings, 'text', 'Button')}
        </span>
      );

    case 'image': {
      const url = text(settings, 'url');

      return url ? (
        <img src={url} alt={text(settings, 'alt')} style={imageStyle(node, viewport)} />
      ) : (
        <div className="breefer-image-placeholder">
          <span className="dashicons dashicons-format-image" aria-hidden="true" />
          Add an image URL
        </div>
      );
    }

    case 'divider':
      return <hr style={dividerStyle(node, viewport)} />;

    case 'spacer':
      return (
        <div
          style={{ height: `${number(settings, 'height', viewport, 48)}px` }}
          aria-hidden="true"
        />
      );

    case 'iconbox': {
      const styles = iconBoxStyles(node, viewport);

      return (
        <div className="br-iconbox" style={styles.box}>
          <IconSvg icon={text(settings, 'icon', 'star')} style={styles.icon} />
          <h3 style={styles.title}>{text(settings, 'title', 'Icon box')}</h3>
          <p style={styles.text}>{text(settings, 'description')}</p>
        </div>
      );
    }

    case 'icon':
      return (
        <span style={iconStyle(node, viewport)}>
          <IconSvg icon={text(settings, 'icon', 'bolt')} />
        </span>
      );

    case 'iconlist': {
      const styles = iconListStyles(node, viewport);
      const icon = text(settings, 'icon', 'check-circle');
      const items = text(settings, 'items')
        .split(/\r?\n/)
        .map((item) => item.trim())
        .filter(Boolean);

      return (
        <ul style={styles.list}>
          {items.map((item, index) => (
            <li key={`${item}-${index}`} style={styles.item}>
              <IconSvg icon={icon} style={styles.icon} />
              <span>{item}</span>
            </li>
          ))}
        </ul>
      );
    }

    case 'video': {
      const source = text(settings, 'url');
      const embed = videoEmbed(source);
      const styles = videoStyle(node, viewport);

      if (embed) {
        return (
          <div style={styles}>
            <iframe
              src={embed}
              title={text(settings, 'title', 'Video')}
              style={{ width: '100%', height: '100%', border: 0 }}
              allowFullScreen
            />
          </div>
        );
      }

      return (
        <div className="breefer-video-placeholder" style={styles}>
          <span className="dashicons dashicons-video-alt3" aria-hidden="true" />
          Add a YouTube, Vimeo or MP4 URL
        </div>
      );
    }

    case 'stars': {
      const rating = number(settings, 'rating', viewport, 5);
      const styles = starStyles(node, viewport);

      return (
        <div style={styles.container} role="img" aria-label={`Rated ${rating} out of 5`}>
          {[1, 2, 3, 4, 5].map((index) => {
            const filled = index <= rating;

            return (
              <svg
                key={index}
                viewBox="0 0 24 24"
                style={styles.star}
                fill={filled ? 'currentColor' : 'none'}
                stroke={filled ? 'none' : 'currentColor'}
                strokeWidth={filled ? undefined : 1.5}
                aria-hidden="true"
              >
                <path d={STAR_PATH} />
              </svg>
            );
          })}
        </div>
      );
    }

    case 'progress': {
      const value = Math.max(0, Math.min(100, number(settings, 'value', viewport, 60)));
      const showValue = Boolean(settings.showValue);
      const label = text(settings, 'label');
      const styles = progressStyles(node, viewport);

      return (
        <div>
          {label || showValue ? (
            <div style={styles.head}>
              <span>{label}</span>
              {showValue ? <span>{Math.round(value)}%</span> : null}
            </div>
          ) : null}
          <div style={styles.track}>
            <div style={{ ...styles.fill, width: `${value}%` }} />
          </div>
        </div>
      );
    }

    case 'shape':
      return <div className="br-shape" style={{ width: '100%', height: '100%', minHeight: 8 }} />;

    case 'tabs':
      return <TabsPreview node={node} />;

    case 'accordion':
      return <AccordionPreview node={node} />;

    case 'wooproducts': {
      const columns = Math.max(1, Math.min(6, number(settings, 'columns', viewport, 4)));
      const cards = Math.min(12, columns * 2);

      return (
        <div className="breefer-woo-preview">
          <div
            className="breefer-woo-preview-grid"
            style={{ gridTemplateColumns: `repeat(${columns}, minmax(0, 1fr))` }}
          >
            {Array.from({ length: cards }, (_, index) => (
              <div className="breefer-woo-card" key={index}>
                <span className="breefer-woo-thumb" />
                <strong>Product name</strong>
                <span className="breefer-woo-price">$29.00</span>
                <span className="breefer-woo-button">Add to cart</span>
              </div>
            ))}
          </div>
          <p className="breefer-woo-note">Live WooCommerce products render on the frontend.</p>
        </div>
      );
    }

    case 'woofield': {
      const field = text(settings, 'field', 'title');

      return (
        <div className="breefer-woo-field-preview">
          {field === 'title' ? <h1 className="br-product-title">Product name</h1> : null}
          {field === 'price' ? <div className="br-product-price">$29.00 – $39.00</div> : null}
          {field === 'image' ? <div className="breefer-woo-image">Product image</div> : null}
          {field === 'add_to_cart' ? (
            <div className="breefer-woo-cart-row">
              <span className="breefer-woo-qty">1</span>
              <span className="breefer-woo-button">Add to cart</span>
            </div>
          ) : null}
          {field === 'short_description' || field === 'description' ? (
            <p>Product description pulled from WooCommerce at render time.</p>
          ) : null}
          {field === 'gallery' ? (
            <div className="breefer-woo-gallery">
              <span className="breefer-woo-thumb" />
              <span className="breefer-woo-thumb" />
              <span className="breefer-woo-thumb" />
            </div>
          ) : null}
          {field === 'sku' ? <code>SKU-001</code> : null}
          {field === 'rating' ? <span className="breefer-woo-price">★★★★★</span> : null}
          {field === 'meta' ? (
            <span className="breefer-woo-price">Clothing, Accessories</span>
          ) : null}
          <p className="breefer-woo-note">Live product data on the frontend.</p>
        </div>
      );
    }

    case 'woocart':
      return (
        <div className="breefer-woo-field-preview">
          <div className="breefer-woo-cart-preview">
            <strong>Cart</strong>
            <span>2 items · $58.00</span>
            <span className="breefer-woo-button">View cart</span>
          </div>
          <p className="breefer-woo-note">Live WooCommerce cart on the frontend.</p>
        </div>
      );

    case 'woocategories':
      return (
        <div className="breefer-woo-field-preview">
          <ul className="breefer-woo-category-list">
            <li>Clothing (12)</li>
            <li>Accessories (8)</li>
            <li>Home (5)</li>
          </ul>
          <p className="breefer-woo-note">Live WooCommerce categories on the frontend.</p>
        </div>
      );

    default:
      return null;
  }
}

/** Which setting holds the text a widget shows, for double-click editing. */
const INLINE_FIELDS: Record<string, string> = { heading: 'text', text: 'content', button: 'text' };

interface NodeViewProps {
  node: BuilderNode;
  viewport: Viewport;
  scale: number;
}

function NodeView({ node, viewport, scale }: NodeViewProps): JSX.Element {
  const selected = useEditorStore((state) => state.selectedId === node.id);
  const select = useEditorStore((state) => state.select);
  const updateSettings = useEditorStore((state) => state.updateSettings);
  const ref = useRef<HTMLDivElement>(null);
  const [editing, setEditing] = useState(false);
  const definition = widgetMap[node.type];
  const isContainer = node.type === 'container';
  const hidden = isHiddenOn(node.settings, viewport);
  const style = nodeStyle(node, viewport);
  const floating = resolveSetting(node.settings, 'position', viewport) === 'absolute';
  const free = node.settings.layout === 'free';
  const field = INLINE_FIELDS[node.type];

  function beginEdit(event: ReactMouseEvent): void {
    const target = ref.current?.querySelector<HTMLElement>('.br-heading, .br-text, .br-btn');

    if (!field || !target || node.settings.locked) {
      return;
    }

    event.stopPropagation();
    setEditing(true);

    const original = target.innerText;
    target.contentEditable = 'true';
    target.focus();

    const range = document.createRange();
    range.selectNodeContents(target);
    window.getSelection()?.removeAllRanges();
    window.getSelection()?.addRange(range);

    const finish = (save: boolean): void => {
      target.removeEventListener('blur', onBlur);
      target.removeEventListener('keydown', onKey);
      target.removeAttribute('contenteditable');
      setEditing(false);

      const value = target.innerText.replace(new RegExp(String.fromCharCode(160), 'g'), ' ').trim();

      if (!save) {
        target.innerText = original;
      } else if (value !== original.trim()) {
        updateSettings(node.id, { [field]: value });
      }
    };

    const onBlur = (): void => finish(true);

    const onKey = (key: KeyboardEvent): void => {
      if (key.key === 'Escape') {
        key.preventDefault();
        finish(false);
      } else if (key.key === 'Enter' && !key.shiftKey && node.type !== 'text') {
        key.preventDefault();
        target.blur();
      }
    };

    target.addEventListener('blur', onBlur);
    target.addEventListener('keydown', onKey);
  }

  return (
    <div
      ref={ref}
      data-node-id={node.id}
      className={`breefer-node breefer-node-${node.type}${selected ? ' is-selected' : ''}${
        hidden ? ' is-hidden-device' : ''
      }${floating ? ' is-floating' : ''}${editing ? ' is-editing' : ''}${
        node.settings.locked ? ' is-locked' : ''
      }`}
      style={style}
      onPointerDown={(event) => {
        if (editing || event.button !== 0) {
          return;
        }

        event.stopPropagation();
        select(node.id);

        // Widgets and floating containers move by dragging their body.
        // Containers in the flow are moved with the toolbar handle instead.
        if (!isContainer || floating) {
          startMove(event, node.id, viewport, scale);
        }
      }}
      onClick={(event) => event.stopPropagation()}
      onDoubleClick={field ? beginEdit : undefined}
    >
      {hidden ? <span className="breefer-hidden-badge">Hidden on {viewport}</span> : null}

      {isContainer ? (
        <>
          {node.children.map((child) => (
            <NodeView key={child.id} node={child} viewport={viewport} scale={scale} />
          ))}

          {node.children.length === 0 ? (
            <div className="breefer-empty">
              <span className="dashicons dashicons-plus-alt2" aria-hidden="true" />
              <strong>
                {free ? 'Free canvas' : selected ? 'Add a widget from the left' : 'Empty container'}
              </strong>
              <p>
                {free
                  ? 'Add widgets, then drag them anywhere.'
                  : 'Click a widget in the library to place it here.'}
              </p>
            </div>
          ) : null}
        </>
      ) : (
        <WidgetPreview node={node} viewport={viewport} />
      )}

      {definition ? null : (
        <span className="breefer-hidden-badge">Unknown widget: {node.type}</span>
      )}
    </div>
  );
}

const ZOOM_STEPS = [0.25, 0.5, 0.75, 1, 1.25, 1.5, 2];

export function Canvas({ width }: { width: number }): JSX.Element {
  const root = useEditorStore((state) => state.root);
  const viewport = useEditorStore((state) => state.viewport);
  const select = useEditorStore((state) => state.select);
  const zoom = useEditorStore((state) => state.zoom);
  const setZoom = useEditorStore((state) => state.setZoom);
  const snap = useEditorStore((state) => state.snap);
  const toggleSnap = useEditorStore((state) => state.toggleSnap);
  const stageRef = useRef<HTMLDivElement>(null);
  const wrapRef = useRef<HTMLDivElement>(null);
  const [available, setAvailable] = useState(0);
  const [innerHeight, setInnerHeight] = useState(600);

  // "Fit" shrinks the page so it never needs a horizontal scrollbar.
  const fit = available > 0 ? Math.min(1, (available - 56) / width) : 1;
  const scale = zoom === 0 ? Math.max(0.25, fit) : zoom;

  useEffect(() => {
    const stage = stageRef.current;
    const wrap = wrapRef.current;

    if (!stage || !wrap || !('ResizeObserver' in window)) {
      return undefined;
    }

    const observer = new ResizeObserver(() => {
      setAvailable(stage.clientWidth);
      setInnerHeight(wrap.offsetHeight);
    });

    observer.observe(stage);
    observer.observe(wrap);

    return () => observer.disconnect();
  }, []);

  // Ctrl/Cmd + wheel zooms like a design tool.
  useEffect(() => {
    const stage = stageRef.current;

    if (!stage) {
      return undefined;
    }

    const onWheel = (event: WheelEvent): void => {
      if (!event.ctrlKey && !event.metaKey) {
        return;
      }

      event.preventDefault();
      setZoom(Math.round(scale * (event.deltaY < 0 ? 1.1 : 0.9) * 100) / 100);
    };

    stage.addEventListener('wheel', onWheel, { passive: false });

    return () => stage.removeEventListener('wheel', onWheel);
  }, [scale, setZoom]);

  const stepZoom = (direction: 1 | -1): void => {
    const next =
      direction > 0
        ? ZOOM_STEPS.find((step) => step > scale + 0.001)
        : [...ZOOM_STEPS].reverse().find((step) => step < scale - 0.001);

    setZoom(next ?? scale);
  };

  return (
    <div className="breefer-stage" ref={stageRef} onPointerDown={() => select(null)}>
      <div className="breefer-stage-meta" onPointerDown={(event) => event.stopPropagation()}>
        <span>{width}px</span>
        <span>{viewport}</span>
        <span>{root.children.length === 0 ? 'Empty canvas' : 'Drag widgets anywhere'}</span>
        <span className="breefer-stage-spacer" />
        <button
          type="button"
          className={`breefer-meta-button${snap ? ' is-on' : ''}`}
          title="Snap to edges and centres (hold Alt to bypass)"
          onClick={toggleSnap}
        >
          Snap
        </button>
        <button
          type="button"
          className="breefer-meta-button"
          onClick={() => stepZoom(-1)}
          aria-label="Zoom out"
        >
          −
        </button>
        <button
          type="button"
          className="breefer-meta-button is-wide"
          title="Fit to width"
          onClick={() => setZoom(0)}
        >
          {Math.round(scale * 100)}%
        </button>
        <button
          type="button"
          className="breefer-meta-button"
          onClick={() => stepZoom(1)}
          aria-label="Zoom in"
        >
          +
        </button>
      </div>

      <div
        className="breefer-zoom-box"
        style={{ width: width * scale, height: innerHeight * scale }}
      >
        <div
          ref={wrapRef}
          className="breefer-canvas-wrap"
          style={{ width, transform: `scale(${scale})`, transformOrigin: '0 0' }}
        >
          <div className="breefer-canvas-frame">
            <div
              data-node-id={root.id}
              className={`breefer-node breefer-node-root${root.children.length === 0 ? ' is-empty' : ''}`}
              style={nodeStyle(root, viewport)}
              onPointerDown={(event) => {
                event.stopPropagation();
                select(root.id);
              }}
            >
              {root.children.map((child) => (
                <NodeView key={child.id} node={child} viewport={viewport} scale={scale} />
              ))}

              {root.children.length === 0 ? (
                <div className="breefer-empty is-root">
                  <span className="dashicons dashicons-welcome-add-page" aria-hidden="true" />
                  <strong>Start with a widget</strong>
                  <p>
                    Pick one from the library, insert a section, or add a free canvas container.
                  </p>
                </div>
              ) : null}
            </div>
          </div>

          <SelectionOverlay scale={scale} />
        </div>
      </div>
    </div>
  );
}
