import type { Bootstrap, Viewport } from '../types';
import { useEditorStore } from '../store/editorStore';

interface TopBarProps {
  boot: Bootstrap;
}

const viewports: Array<{ id: Viewport; label: string; icon: string }> = [
  { id: 'desktop', label: 'Desktop', icon: 'dashicons-desktop' },
  { id: 'tablet', label: 'Tablet', icon: 'dashicons-tablet' },
  { id: 'mobile', label: 'Mobile', icon: 'dashicons-smartphone' },
];

export function TopBar({ boot }: TopBarProps): JSX.Element {
  const title = useEditorStore((state) => state.title);
  const dirty = useEditorStore((state) => state.dirty);
  const saving = useEditorStore((state) => state.saving);
  const savedAt = useEditorStore((state) => state.savedAt);
  const postStatus = useEditorStore((state) => state.status);
  const viewport = useEditorStore((state) => state.viewport);
  const history = useEditorStore((state) => state.history);
  const future = useEditorStore((state) => state.future);
  const setTitle = useEditorStore((state) => state.setTitle);
  const setViewport = useEditorStore((state) => state.setViewport);
  const undo = useEditorStore((state) => state.undo);
  const redo = useEditorStore((state) => state.redo);
  const save = useEditorStore((state) => state.save);
  const publish = useEditorStore((state) => state.publish);
  const isTemplate = boot.postType === 'breefer_template';

  const status = saving ? 'Saving…' : dirty ? 'Unsaved changes' : savedAt ? 'Saved' : 'Ready';

  return (
    <header className="breefer-topbar">
      <div className="breefer-topbar-left">
        <a className="breefer-icon-button" href={boot.templatesUrl} title="Back to templates">
          <span className="dashicons dashicons-arrow-left-alt2" aria-hidden="true" />
        </a>

        <span className="breefer-brand-mark" aria-hidden="true">
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none">
            <path d="M12 3 21 8l-9 5-9-5 9-5Z" fill="currentColor" />
            <path
              d="m3 12 9 5 9-5"
              stroke="currentColor"
              strokeWidth="1.6"
              strokeLinecap="round"
              strokeLinejoin="round"
            />
            <path
              d="m3 16 9 5 9-5"
              stroke="currentColor"
              strokeWidth="1.6"
              strokeLinecap="round"
              strokeLinejoin="round"
              opacity="0.55"
            />
          </svg>
        </span>

        <div className="breefer-doc">
          <input
            className="breefer-title-input"
            type="text"
            value={title}
            aria-label="Document title"
            onChange={(event) => setTitle(event.target.value)}
          />
          <span className={`breefer-save-state${dirty ? ' is-dirty' : ''}`}>{status}</span>
        </div>
      </div>

      <div className="breefer-topbar-center">
        <div className="breefer-viewports" role="group" aria-label="Canvas width">
          {viewports.map((item) => (
            <button
              key={item.id}
              type="button"
              className={`breefer-viewport${viewport === item.id ? ' is-active' : ''}`}
              aria-pressed={viewport === item.id}
              title={item.label}
              onClick={() => setViewport(item.id)}
            >
              <span className={`dashicons ${item.icon}`} aria-hidden="true" />
              <span className="breefer-viewport-label">{item.label}</span>
            </button>
          ))}
        </div>
      </div>

      <div className="breefer-topbar-right">
        <button
          type="button"
          className="breefer-icon-button"
          title="Undo (Ctrl+Z)"
          disabled={history.length === 0}
          onClick={undo}
        >
          <span className="dashicons dashicons-undo" aria-hidden="true" />
        </button>
        <button
          type="button"
          className="breefer-icon-button"
          title="Redo (Ctrl+Shift+Z)"
          disabled={future.length === 0}
          onClick={redo}
        >
          <span className="dashicons dashicons-redo" aria-hidden="true" />
        </button>

        <a
          className="breefer-button is-quiet"
          href={boot.previewUrl}
          target="_blank"
          rel="noreferrer"
        >
          Preview
        </a>

        <button
          type="button"
          className="breefer-button is-primary"
          onClick={() => void (isTemplate || postStatus === 'publish' ? save() : publish())}
          disabled={saving}
        >
          {saving
            ? 'Saving…'
            : isTemplate
              ? 'Save'
              : postStatus === 'publish'
                ? 'Update'
                : 'Publish'}
        </button>
      </div>
    </header>
  );
}
