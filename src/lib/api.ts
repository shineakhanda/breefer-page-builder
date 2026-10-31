import type { Bootstrap, BuilderNode } from '../types';

interface ApiError {
  message?: string;
}

interface SaveResponse {
  id: number;
  title: string;
  status: string;
  modified: string;
  data: { version: number; root: BuilderNode };
  wooType?: string;
  condition?: string;
  layout?: string;
}

export async function saveDocument(
  boot: Bootstrap,
  title: string,
  root: BuilderNode,
  wooType: string,
  condition: string,
  layout: string,
  status: string,
): Promise<SaveResponse> {
  const response = await fetch(`${boot.restUrl}documents/${boot.postId}`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': boot.nonce,
    },
    body: JSON.stringify({
      title,
      wooType,
      condition,
      layout,
      status,
      data: { version: 1, root },
    }),
  });

  if (!response.ok) {
    const payload: unknown = await response.json().catch(() => null);
    const message =
      payload && typeof payload === 'object' && 'message' in payload
        ? (payload as ApiError).message
        : null;

    throw new Error(message || `Save failed (${response.status})`);
  }

  return (await response.json()) as SaveResponse;
}

/**
 * Render one widget on the server so the canvas can show live data.
 */
export async function renderWidget(
  boot: Bootstrap,
  type: string,
  settings: Record<string, unknown>,
): Promise<string> {
  const response = await fetch(`${boot.restUrl}render-widget`, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': boot.nonce,
    },
    body: JSON.stringify({ type, settings, postId: boot.postId }),
  });

  if (!response.ok) {
    throw new Error(`Preview failed (${response.status})`);
  }

  const payload = (await response.json()) as { html?: string };

  return typeof payload.html === 'string' ? payload.html : '';
}
