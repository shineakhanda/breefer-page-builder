import { useEffect } from 'react';

import { Canvas } from './components/Canvas';
import { Inspector } from './components/Inspector';
import { Palette } from './components/Palette';
import { TopBar } from './components/TopBar';
import { nudge } from './lib/gestures';
import { countNodes, findNode } from './lib/tree';
import { useEditorStore } from './store/editorStore';
import type { Bootstrap } from './types';
import { widgetMap } from './widgets/definitions';

interface ShellProps {
  boot: Bootstrap;
}

function EditorShell({ boot }: ShellProps): JSX.Element {
  const root = useEditorStore((state) => state.root);
  const selectedId = useEditorStore((state) => state.selectedId);
  const viewport = useEditorStore((state) => state.viewport);
  const dirty = useEditorStore((state) => state.dirty);
  const saving = useEditorStore((state) => state.saving);
  const error = useEditorStore((state) => state.error);
  const save = useEditorStore((state) => state.save);
  const undo = useEditorStore((state) => state.undo);
  const redo = useEditorStore((state) => state.redo);
  const remove = useEditorStore((state) => state.removeSelected);
  const select = useEditorStore((state) => state.select);
  const clearError = useEditorStore((state) => state.clearError);

  const canvasWidth = viewport === 'desktop' ? boot.canvasWidth : viewport === 'tablet' ? 768 : 390;

  const selectedNode = selectedId ? findNode(root, selectedId) : null;
  const selectedLabel = selectedNode
    ? (widgetMap[selectedNode.type]?.title ?? selectedNode.type)
    : 'Nothing selected';

  useEffect(() => {
    function onKeyDown(event: KeyboardEvent): void {
      const target = event.target as HTMLElement | null;
      const typing = Boolean(
        target &&
        target.closest('input, textarea, select, [contenteditable="true"], [role="textbox"]'),
      );
      const modifier = event.ctrlKey || event.metaKey;
      const key = event.key.toLowerCase();

      if (modifier && key === 's') {
        event.preventDefault();
        void save();
        return;
      }

      // Let inputs handle their own native text undo/redo.
      if (typing && (key === 'z' || key === 'y')) {
        return;
      }

      if (modifier && key === 'z') {
        event.preventDefault();

        if (event.shiftKey) {
          redo();
        } else {
          undo();
        }

        return;
      }

      if (modifier && key === 'y') {
        event.preventDefault();
        redo();
        return;
      }

      if (modifier && key === 'd') {
        event.preventDefault();
        useEditorStore.getState().duplicateSelected();
        return;
      }

      const state = useEditorStore.getState();
      const activeId = state.selectedId;
      const arrows: Record<string, [number, number]> = {
        arrowleft: [-1, 0],
        arrowright: [1, 0],
        arrowup: [0, -1],
        arrowdown: [0, 1],
      };

      // Arrow keys nudge a floating widget by 1px, or 10px with Shift.
      if (!typing && activeId && arrows[key]) {
        const step = event.shiftKey ? 10 : 1;
        const [dx, dy] = arrows[key];

        if (nudge(activeId, dx * step, dy * step, state.viewport)) {
          event.preventDefault();
        }

        return;
      }

      // Ctrl+] / Ctrl+[ change the stacking order; add Shift for front / back.
      if (!typing && activeId && modifier && (event.key === ']' || event.key === '[')) {
        event.preventDefault();

        const forward = event.key === ']';
        state.arrange(
          activeId,
          event.shiftKey ? (forward ? 'front' : 'back') : forward ? 'forward' : 'backward',
        );

        return;
      }

      if (modifier && (key === '=' || key === '+' || key === '-' || key === '0')) {
        event.preventDefault();

        const current = state.zoom === 0 ? 1 : state.zoom;

        state.setZoom(key === '0' ? 0 : key === '-' ? current - 0.1 : current + 0.1);

        return;
      }

      if (!typing && (key === 'delete' || key === 'backspace')) {
        event.preventDefault();
        remove();
        return;
      }

      if (!typing && key === 'escape') {
        select(null);
      }
    }

    window.addEventListener('keydown', onKeyDown);

    return () => window.removeEventListener('keydown', onKeyDown);
  }, [save, undo, redo, remove, select]);

  useEffect(() => {
    const interval = window.setInterval(
      () => {
        const state = useEditorStore.getState();

        if (state.dirty && !state.saving) {
          void state.save();
        }
      },
      Math.max(5, boot.autosave) * 1000,
    );

    return () => window.clearInterval(interval);
  }, [boot.autosave]);

  useEffect(() => {
    function onBeforeUnload(event: BeforeUnloadEvent): void {
      if (useEditorStore.getState().dirty) {
        event.preventDefault();
        event.returnValue = '';
      }
    }

    window.addEventListener('beforeunload', onBeforeUnload);

    return () => window.removeEventListener('beforeunload', onBeforeUnload);
  }, []);

  return (
    <div className="breefer-app">
      <TopBar boot={boot} />

      <div className="breefer-workspace">
        <Palette />
        <Canvas width={canvasWidth} />
        <Inspector />
      </div>

      <footer className="breefer-statusbar">
        <div className="breefer-status-left">
          <span className={`breefer-status-dot${dirty ? ' is-dirty' : ''}`} aria-hidden="true" />
          <span>{saving ? 'Saving' : dirty ? 'Unsaved' : 'Synced'}</span>
          <span className="breefer-status-sep" aria-hidden="true" />
          <span>{countNodes(root) - 1} widgets</span>
          <span className="breefer-status-sep" aria-hidden="true" />
          <span>{selectedLabel}</span>
        </div>
        <div className="breefer-status-right">
          <span>Drag to move · handles resize · ⟳ rotates</span>
          <span>Arrows nudge · Alt bypasses snap</span>
          <span>Ctrl+S save · Ctrl+Z undo · Del remove</span>
          <span className="breefer-status-version">v{boot.postId}</span>
        </div>
      </footer>

      {error ? (
        <div className="breefer-toast is-error" role="alert">
          <span>{error}</span>
          <button type="button" onClick={clearError} aria-label="Dismiss">
            ×
          </button>
        </div>
      ) : null}
    </div>
  );
}

export function EditorApp(): JSX.Element {
  const boot = window.breeferEditor;

  if (!boot) {
    return (
      <div className="breefer-fatal">
        <h1>Breefer could not start</h1>
        <p>The editor configuration was not provided by WordPress. Reload the builder screen.</p>
      </div>
    );
  }

  return <EditorShell boot={boot} />;
}
