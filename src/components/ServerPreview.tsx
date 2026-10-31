import { useEffect, useMemo, useState } from 'react';

import { renderWidget } from '../lib/api';

/** Rendered HTML per widget configuration, shared across the session. */
const cache = new Map<string, string>();

const LOADING = '<div class="br-placeholder"><strong>Loading preview…</strong></div>';
const FAILED =
  '<div class="br-placeholder"><strong>Preview unavailable</strong><span>The widget still renders on your site.</span></div>';

interface ServerPreviewProps {
  type: string;
  settings: Record<string, unknown>;
}

/**
 * Shows the widget exactly as the frontend renders it, using live site data
 * (products, menus, posts). The markup comes from the plugin's own renderer,
 * which escapes every value, so it is safe to inject.
 */
export function ServerPreview({ type, settings }: ServerPreviewProps): JSX.Element {
  const key = useMemo(() => `${type}:${JSON.stringify(settings)}`, [type, settings]);
  const [html, setHtml] = useState<string>(cache.get(key) ?? LOADING);

  useEffect(() => {
    const cached = cache.get(key);

    if (cached !== undefined) {
      setHtml(cached);

      return undefined;
    }

    const boot = window.breeferEditor;
    let cancelled = false;

    if (!boot) {
      setHtml(FAILED);

      return undefined;
    }

    // Debounce so typing in a control does not fire a request per keystroke.
    const timer = window.setTimeout(() => {
      renderWidget(boot, type, settings)
        .then((markup) => {
          cache.set(key, markup);

          if (!cancelled) {
            setHtml(markup);
          }
        })
        .catch(() => {
          if (!cancelled) {
            setHtml(FAILED);
          }
        });
    }, 350);

    return () => {
      cancelled = true;
      window.clearTimeout(timer);
    };
  }, [key, type, settings]);

  return <div className="br-server-preview" dangerouslySetInnerHTML={{ __html: html }} />;
}
