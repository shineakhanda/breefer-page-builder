import { useEffect, useLayoutEffect, useState } from 'react';

import {
  alignNode,
  setMarkSink,
  startReorder,
  startResize,
  startRotate,
  toggleFloat,
  type AlignHow,
  type HandleDirection,
  type Marks,
} from '../lib/gestures';
import { isAbsolute, nodeElement, offsetWithin } from '../lib/geometry';
import { resolveSetting } from '../lib/styles';
import { findNode } from '../lib/tree';
import { useEditorStore } from '../store/editorStore';
import { widgetMap } from '../widgets/definitions';

interface Measured {
  left: number;
  top: number;
  width: number;
  height: number;
  rotate: number;
  floating: boolean;
}

const HANDLES: Array<{ dir: HandleDirection; x: number; y: number }> = [
  { dir: 'nw', x: 0, y: 0 },
  { dir: 'n', x: 50, y: 0 },
  { dir: 'ne', x: 100, y: 0 },
  { dir: 'e', x: 100, y: 50 },
  { dir: 'se', x: 100, y: 100 },
  { dir: 's', x: 50, y: 100 },
  { dir: 'sw', x: 0, y: 100 },
  { dir: 'w', x: 0, y: 50 },
];

const CURSORS: Record<HandleDirection, string> = {
  n: 'ns-resize',
  s: 'ns-resize',
  e: 'ew-resize',
  w: 'ew-resize',
  ne: 'nesw-resize',
  sw: 'nesw-resize',
  nw: 'nwse-resize',
  se: 'nwse-resize',
};

const ALIGNMENTS: Array<{ how: AlignHow; glyph: string; label: string }> = [
  { how: 'left', glyph: '⇤', label: 'Align left' },
  { how: 'hcenter', glyph: '↔', label: 'Center horizontally' },
  { how: 'right', glyph: '⇥', label: 'Align right' },
  { how: 'top', glyph: '⤒', label: 'Align top' },
  { how: 'vmiddle', glyph: '↕', label: 'Center vertically' },
  { how: 'bottom', glyph: '⤓', label: 'Align bottom' },
];

function same(a: Measured | null, b: Measured | null): boolean {
  return (
    a === b ||
    (a !== null &&
      b !== null &&
      a.left === b.left &&
      a.top === b.top &&
      a.width === b.width &&
      a.height === b.height &&
      a.rotate === b.rotate &&
      a.floating === b.floating)
  );
}

/**
 * Selection box, resize/rotate handles, floating toolbar and snap guides.
 * It lives next to the page (not inside a widget), so nothing is clipped and
 * tiny widgets still get usable handles.
 */
export function SelectionOverlay({ scale }: { scale: number }): JSX.Element {
  const selectedId = useEditorStore((state) => state.selectedId);
  const root = useEditorStore((state) => state.root);
  const viewport = useEditorStore((state) => state.viewport);
  const duplicate = useEditorStore((state) => state.duplicateSelected);
  const remove = useEditorStore((state) => state.removeSelected);
  const arrange = useEditorStore((state) => state.arrange);
  const updateSettings = useEditorStore((state) => state.updateSettings);
  const [marks, setMarks] = useState<Marks>({ guides: [], drop: null });
  const [measured, setMeasured] = useState<Measured | null>(null);
  const [tick, setTick] = useState(0);

  useEffect(() => {
    setMarkSink(setMarks);

    return () => setMarkSink(null);
  }, []);

  useEffect(() => {
    const refresh = (): void => setTick((value) => value + 1);

    window.addEventListener('breefer:measure', refresh);
    window.addEventListener('resize', refresh);

    const wrapper = document.querySelector('.breefer-canvas-wrap');
    const observer = wrapper && 'ResizeObserver' in window ? new ResizeObserver(refresh) : null;

    if (wrapper) {
      observer?.observe(wrapper);
    }

    return () => {
      window.removeEventListener('breefer:measure', refresh);
      window.removeEventListener('resize', refresh);
      observer?.disconnect();
    };
  }, []);

  const node = selectedId ? findNode(root, selectedId) : null;
  const isRoot = node !== null && node.id === root.id;

  useLayoutEffect(() => {
    const wrapper = document.querySelector<HTMLElement>('.breefer-canvas-wrap');
    const element = selectedId && !isRoot ? nodeElement(selectedId) : null;

    if (!wrapper || !element || !node) {
      setMeasured((previous) => (previous === null ? previous : null));

      return;
    }

    const origin = offsetWithin(element, wrapper);
    const next: Measured = {
      left: origin.x,
      top: origin.y,
      width: element.offsetWidth,
      height: element.offsetHeight,
      rotate: Number(resolveSetting(node.settings, 'rotate', viewport)) || 0,
      floating: isAbsolute(element),
    };

    setMeasured((previous) => (same(previous, next) ? previous : next));
  }, [selectedId, isRoot, node, viewport, tick, scale, root]);

  void tick;

  const locked = Boolean(node?.settings.locked);
  const title = node ? (widgetMap[node.type]?.title ?? node.type) : '';
  const ui = 1 / scale;
  const above = measured ? measured.top - 46 * ui >= 0 : true;

  return (
    <div className="breefer-overlay" style={{ ['--ui' as string]: ui }}>
      {marks.guides.map((guide, index) => (
        <span
          key={`${guide.axis}-${index}`}
          className={`breefer-guide is-${guide.axis}`}
          style={
            guide.axis === 'x'
              ? { left: guide.pos, top: guide.from, height: guide.to - guide.from }
              : { top: guide.pos, left: guide.from, width: guide.to - guide.from }
          }
        />
      ))}

      {marks.drop ? (
        <span
          className={`breefer-drop is-${marks.drop.mode}`}
          style={{
            left: marks.drop.left,
            top: marks.drop.top,
            width: marks.drop.width,
            height: marks.drop.height,
          }}
        />
      ) : null}

      {measured && node && selectedId ? (
        <>
          <div
            className={`breefer-selection${locked ? ' is-locked' : ''}`}
            style={{
              left: measured.left,
              top: measured.top,
              width: measured.width,
              height: measured.height,
              transform: measured.rotate ? `rotate(${measured.rotate}deg)` : undefined,
            }}
          >
            {!locked ? (
              <>
                {HANDLES.map((handle) => (
                  <span
                    key={handle.dir}
                    className={`breefer-handle is-${handle.dir.length === 2 ? 'corner' : 'edge'}`}
                    style={{
                      left: `${handle.x}%`,
                      top: `${handle.y}%`,
                      cursor: CURSORS[handle.dir],
                    }}
                    role="presentation"
                    onPointerDown={(event) => {
                      event.preventDefault();
                      event.stopPropagation();
                      startResize(event, selectedId, handle.dir, viewport, scale);
                    }}
                  />
                ))}
                <span
                  className="breefer-rotate"
                  title="Rotate (Shift snaps to 15°)"
                  role="presentation"
                  onPointerDown={(event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    startRotate(event, selectedId, viewport);
                  }}
                >
                  ⟳
                </span>
              </>
            ) : null}
            <span className="breefer-size-tag">
              {Math.round(measured.width)} × {Math.round(measured.height)}
              {measured.rotate ? ` · ${Math.round(measured.rotate)}°` : ''}
            </span>
          </div>

          <div
            className="breefer-toolbar"
            style={{
              left: measured.left,
              top: above ? measured.top - 8 * ui : measured.top + measured.height + 8 * ui,
              transform: `scale(${ui}) translateY(${above ? '-100%' : '0'})`,
            }}
            onPointerDown={(event) => event.stopPropagation()}
            onClick={(event) => event.stopPropagation()}
          >
            <button
              type="button"
              className="breefer-tool is-handle"
              title="Drag to reorder in the flow"
              aria-label="Reorder"
              onPointerDown={(event) => {
                event.preventDefault();
                event.stopPropagation();
                startReorder(event, selectedId, viewport);
              }}
            >
              ⠿
            </button>
            <span className="breefer-tool-title">{title}</span>

            <button
              type="button"
              className={`breefer-tool${measured.floating ? ' is-on' : ''}`}
              title={
                measured.floating
                  ? 'Return to the layout flow'
                  : 'Free position: place anywhere, overlap allowed'
              }
              onClick={() => toggleFloat(selectedId, viewport)}
            >
              {measured.floating ? 'Free' : 'Flow'}
            </button>

            <span className="breefer-tool-sep" aria-hidden="true" />
            {ALIGNMENTS.map((item) => (
              <button
                key={item.how}
                type="button"
                className="breefer-tool"
                title={item.label}
                aria-label={item.label}
                onClick={() => alignNode(selectedId, item.how, viewport)}
              >
                {item.glyph}
              </button>
            ))}

            {measured.floating ? (
              <>
                <span className="breefer-tool-sep" aria-hidden="true" />
                <button
                  type="button"
                  className="breefer-tool"
                  title="Bring to front"
                  aria-label="Bring to front"
                  onClick={() => arrange(selectedId, 'front')}
                >
                  ⤊
                </button>
                <button
                  type="button"
                  className="breefer-tool"
                  title="Bring forward"
                  aria-label="Bring forward"
                  onClick={() => arrange(selectedId, 'forward')}
                >
                  ↑
                </button>
                <button
                  type="button"
                  className="breefer-tool"
                  title="Send backward"
                  aria-label="Send backward"
                  onClick={() => arrange(selectedId, 'backward')}
                >
                  ↓
                </button>
                <button
                  type="button"
                  className="breefer-tool"
                  title="Send to back"
                  aria-label="Send to back"
                  onClick={() => arrange(selectedId, 'back')}
                >
                  ⤋
                </button>
              </>
            ) : null}

            <span className="breefer-tool-sep" aria-hidden="true" />
            <button
              type="button"
              className={`breefer-tool${locked ? ' is-on' : ''}`}
              title={locked ? 'Unlock' : 'Lock position and size'}
              onClick={() => updateSettings(selectedId, { locked: !locked })}
            >
              {locked ? 'Unlock' : 'Lock'}
            </button>
            <button
              type="button"
              className="breefer-tool"
              title="Duplicate (Ctrl+D)"
              onClick={duplicate}
            >
              Copy
            </button>
            <button
              type="button"
              className="breefer-tool is-danger"
              title="Delete"
              onClick={remove}
            >
              Delete
            </button>
          </div>
        </>
      ) : null}
    </div>
  );
}
