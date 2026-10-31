import { useEditorStore } from '../store/editorStore';
import type { Viewport } from '../types';
import {
  boxInParent,
  clamp,
  isAbsolute,
  nodeElement,
  offsetWithin,
  round,
  snapAxis,
  snapTargets,
  type Box,
  type SnapTargets,
} from './geometry';
import type { DropMode } from './tree';
import { findNode } from './tree';

/**
 * Canvas gestures: move, resize, rotate, reorder, align and nudge.
 *
 * During a gesture the element's inline style is changed directly so dragging
 * stays smooth, and nothing touches the store. On release the inline overrides
 * are removed and the result is committed as ONE undoable settings patch. All
 * values are stored under the active device's key suffix, so a layout can be
 * placed differently on tablet and mobile.
 */

export type HandleDirection = 'n' | 'ne' | 'e' | 'se' | 's' | 'sw' | 'w' | 'nw';

export interface Guide {
  axis: 'x' | 'y';
  /** Position along the axis, in canvas wrapper coordinates. */
  pos: number;
  from: number;
  to: number;
}

export interface DropMark {
  left: number;
  top: number;
  width: number;
  height: number;
  mode: DropMode;
}

export interface Marks {
  guides: Guide[];
  drop: DropMark | null;
}

const NO_MARKS: Marks = { guides: [], drop: null };

let sink: ((marks: Marks) => void) | null = null;

export function setMarkSink(next: ((marks: Marks) => void) | null): void {
  sink = next;
}

function publish(marks: Marks): void {
  sink?.(marks);
}

function wrapperElement(): HTMLElement | null {
  return document.querySelector<HTMLElement>('.breefer-canvas-wrap');
}

/** Ask the selection overlay to re-measure (the DOM changed without a store update). */
function remeasure(): void {
  window.dispatchEvent(new Event('breefer:measure'));
}

function suffix(viewport: Viewport): string {
  return viewport === 'desktop' ? '' : `_${viewport}`;
}

interface Subject {
  id: string;
  element: HTMLElement;
  parent: HTMLElement;
  parentId: string;
  wrapper: HTMLElement;
  viewport: Viewport;
  key: (name: string) => string;
  start: Box;
  floating: boolean;
  locked: boolean;
  /** Inline style before the gesture, restored on release so React stays in charge. */
  savedStyle: string;
}

function subject(id: string, viewport: Viewport): Subject | null {
  const element = nodeElement(id);
  const parent = element?.parentElement;
  const wrapper = wrapperElement();
  const node = findNode(useEditorStore.getState().root, id);

  if (!element || !parent || !wrapper || !node || !parent.dataset.nodeId) {
    return null;
  }

  const sfx = suffix(viewport);

  return {
    id,
    element,
    parent,
    parentId: parent.dataset.nodeId,
    wrapper,
    viewport,
    key: (name) => `${name}${sfx}`,
    start: boxInParent(element),
    floating: isAbsolute(element),
    locked: Boolean(node.settings.locked),
    savedStyle: element.style.cssText,
  };
}

/** Width available to a percentage width: padding box when floating, content box in flow. */
function percentBase(parent: HTMLElement, floating: boolean): number {
  if (floating) {
    return Math.max(1, parent.clientWidth);
  }

  const style = window.getComputedStyle(parent);

  return Math.max(
    1,
    parent.clientWidth -
      (parseFloat(style.paddingLeft) || 0) -
      (parseFloat(style.paddingRight) || 0),
  );
}

type Patch = { id: string; patch: Record<string, unknown> };

/** Settings that take a flow element out of the flow without moving it. */
function floatPatches(subj: Subject, box: Box = subj.start): Patch[] {
  const patches: Patch[] = [
    {
      id: subj.id,
      patch: {
        [subj.key('position')]: 'absolute',
        [subj.key('offsetLeft')]: round(box.left),
        [subj.key('offsetTop')]: round(box.top),
        [subj.key('boxWidth')]: round((box.width / percentBase(subj.parent, true)) * 100, 1),
      },
    },
  ];

  // Without in-flow children the parent would collapse, so pin its height.
  const parentNode = findNode(useEditorStore.getState().root, subj.parentId);

  if (
    parentNode &&
    parentNode.settings[subj.key('minHeight')] === undefined &&
    parentNode.settings[subj.key('boxHeight')] === undefined
  ) {
    patches.push({
      id: subj.parentId,
      patch: { [subj.key('minHeight')]: Math.round(subj.parent.offsetHeight) },
    });
  }

  return patches;
}

function commit(patches: Patch[]): void {
  useEditorStore.getState().patchSettings(patches);
}

function beginGesture(): () => void {
  document.body.classList.add('breefer-is-gesturing');

  return () => {
    document.body.classList.remove('breefer-is-gesturing');
    publish(NO_MARKS);
  };
}

function guidesFor(subj: Subject, xGuide: number | null, yGuide: number | null): Guide[] {
  const origin = offsetWithin(subj.parent, subj.wrapper);
  const guides: Guide[] = [];

  if (xGuide !== null) {
    guides.push({
      axis: 'x',
      pos: origin.x + xGuide,
      from: origin.y,
      to: origin.y + subj.parent.clientHeight,
    });
  }

  if (yGuide !== null) {
    guides.push({
      axis: 'y',
      pos: origin.y + yGuide,
      from: origin.x,
      to: origin.x + subj.parent.clientWidth,
    });
  }

  return guides;
}

/** Drag a widget anywhere inside its parent. A widget in the flow floats on first move. */
export function startMove(
  event: { button: number; clientX: number; clientY: number },
  id: string,
  viewport: Viewport,
  scale: number,
): void {
  if (event.button !== 0) {
    return;
  }

  const subj = subject(id, viewport);

  if (!subj || subj.locked) {
    return;
  }

  const state = useEditorStore.getState();
  const targets: SnapTargets = snapTargets(subj.element, subj.parent);
  const threshold = 6 / scale;
  const startX = event.clientX;
  const startY = event.clientY;
  let moved = false;
  let end: (() => void) | null = null;
  let left = subj.start.left;
  let top = subj.start.top;

  const onMove = (move: PointerEvent): void => {
    let dx = (move.clientX - startX) / scale;
    let dy = (move.clientY - startY) / scale;

    if (!moved) {
      if (Math.hypot(dx, dy) * scale < 4) {
        return;
      }

      moved = true;
      end = beginGesture();

      if (!subj.floating) {
        subj.element.style.position = 'absolute';
        subj.element.style.width = `${subj.start.width}px`;
      }
    }

    if (move.shiftKey) {
      if (Math.abs(dx) > Math.abs(dy)) {
        dy = 0;
      } else {
        dx = 0;
      }
    }

    left = subj.start.left + dx;
    top = subj.start.top + dy;

    let xGuide: number | null = null;
    let yGuide: number | null = null;

    if (state.snap && !move.altKey) {
      const { width, height } = subj.start;
      const sx = snapAxis([left, left + width / 2, left + width], targets.x, threshold);
      const sy = snapAxis([top, top + height / 2, top + height], targets.y, threshold);

      left += sx.delta;
      top += sy.delta;
      xGuide = sx.guide;
      yGuide = sy.guide;
    }

    subj.element.style.left = `${left}px`;
    subj.element.style.top = `${top}px`;

    publish({ guides: guidesFor(subj, xGuide, yGuide), drop: null });
    remeasure();
  };

  const onUp = (): void => {
    window.removeEventListener('pointermove', onMove);
    window.removeEventListener('pointerup', onUp);
    end?.();

    if (!moved) {
      return;
    }

    subj.element.style.cssText = subj.savedStyle;

    const patches = subj.floating ? [] : floatPatches(subj);
    const main = patches[0] ?? { id: subj.id, patch: {} };

    main.patch[subj.key('offsetLeft')] = round(left);
    main.patch[subj.key('offsetTop')] = round(top);

    commit(patches.length ? patches : [main]);
  };

  window.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);
}

/** Resize from any of the eight handles. Shift keeps the aspect ratio on corners. */
export function startResize(
  event: { clientX: number; clientY: number },
  id: string,
  direction: HandleDirection,
  viewport: Viewport,
  scale: number,
): void {
  const subj = subject(id, viewport);

  if (!subj || subj.locked) {
    return;
  }

  const state = useEditorStore.getState();
  const targets = snapTargets(subj.element, subj.parent);
  const threshold = 6 / scale;
  const startX = event.clientX;
  const startY = event.clientY;
  const west = direction.includes('w');
  const east = direction.includes('e');
  const north = direction.includes('n');
  const south = direction.includes('s');
  const corner = direction.length === 2;
  const needsFloat = !subj.floating && (west || north);
  const MIN = 20;
  const end = beginGesture();
  let box: Box = { ...subj.start };

  if (needsFloat) {
    subj.element.style.position = 'absolute';
  }

  const onMove = (move: PointerEvent): void => {
    const dx = (move.clientX - startX) / scale;
    const dy = (move.clientY - startY) / scale;
    let { left, top, width, height } = subj.start;

    if (east) {
      width = subj.start.width + dx;
    }

    if (west) {
      width = subj.start.width - dx;
    }

    if (south) {
      height = subj.start.height + dy;
    }

    if (north) {
      height = subj.start.height - dy;
    }

    if (move.shiftKey && corner) {
      const fw = width / subj.start.width;
      const fh = height / subj.start.height;
      const factor = Math.abs(fw - 1) > Math.abs(fh - 1) ? fw : fh;

      width = subj.start.width * factor;
      height = subj.start.height * factor;
    }

    width = Math.max(MIN, width);
    height = Math.max(MIN, height);

    if (west) {
      left = subj.start.left + subj.start.width - width;
    }

    if (north) {
      top = subj.start.top + subj.start.height - height;
    }

    let xGuide: number | null = null;
    let yGuide: number | null = null;

    if (state.snap && !move.altKey && !(move.shiftKey && corner)) {
      if (east) {
        const s = snapAxis([left + width], targets.x, threshold);
        width += s.delta;
        xGuide = s.guide;
      }

      if (west) {
        const s = snapAxis([left], targets.x, threshold);
        left += s.delta;
        width -= s.delta;
        xGuide = s.guide;
      }

      if (south) {
        const s = snapAxis([top + height], targets.y, threshold);
        height += s.delta;
        yGuide = s.guide;
      }

      if (north) {
        const s = snapAxis([top], targets.y, threshold);
        top += s.delta;
        height -= s.delta;
        yGuide = s.guide;
      }
    }

    box = { left, top, width: Math.max(MIN, width), height: Math.max(MIN, height) };

    if (east || west) {
      subj.element.style.width = `${box.width}px`;
    }

    if (north || south) {
      subj.element.style.height = `${box.height}px`;
    }

    if (subj.floating || needsFloat) {
      subj.element.style.left = `${box.left}px`;
      subj.element.style.top = `${box.top}px`;
    }

    publish({ guides: guidesFor(subj, xGuide, yGuide), drop: null });
    remeasure();
  };

  const onUp = (): void => {
    window.removeEventListener('pointermove', onMove);
    window.removeEventListener('pointerup', onUp);
    end();
    subj.element.style.cssText = subj.savedStyle;

    const floating = subj.floating || needsFloat;
    const patches: Patch[] = needsFloat ? floatPatches(subj, box) : [{ id: subj.id, patch: {} }];
    const main = patches[0] ?? { id: subj.id, patch: {} };

    if (east || west) {
      main.patch[subj.key('boxWidth')] = round(
        (box.width / percentBase(subj.parent, floating)) * 100,
        1,
      );
    }

    if (north || south) {
      main.patch[subj.key('boxHeight')] = Math.round(box.height);
    }

    if (floating) {
      main.patch[subj.key('offsetLeft')] = round(box.left);
      main.patch[subj.key('offsetTop')] = round(box.top);
    }

    commit(patches);
  };

  window.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);
}

/** Rotate around the centre. Shift snaps to 15 degree steps. */
export function startRotate(
  event: { clientX: number; clientY: number },
  id: string,
  viewport: Viewport,
): void {
  const subj = subject(id, viewport);

  if (!subj || subj.locked) {
    return;
  }

  const node = findNode(useEditorStore.getState().root, id);
  const startRotation =
    Number(node?.settings[subj.key('rotate')] ?? node?.settings.rotate ?? 0) || 0;
  const rect = subj.element.getBoundingClientRect();
  const cx = rect.left + rect.width / 2;
  const cy = rect.top + rect.height / 2;
  const startAngle = Math.atan2(event.clientY - cy, event.clientX - cx);
  const end = beginGesture();
  let rotation = startRotation;

  const onMove = (move: PointerEvent): void => {
    const angle = Math.atan2(move.clientY - cy, move.clientX - cx);
    let next = startRotation + ((angle - startAngle) * 180) / Math.PI;

    next = ((((next + 180) % 360) + 360) % 360) - 180;

    if (move.shiftKey) {
      next = Math.round(next / 15) * 15;
    }

    rotation = round(next, 1);
    subj.element.style.transform = `rotate(${rotation}deg)`;
    remeasure();
  };

  const onUp = (): void => {
    window.removeEventListener('pointermove', onMove);
    window.removeEventListener('pointerup', onUp);
    end();
    subj.element.style.cssText = subj.savedStyle;
    commit([{ id: subj.id, patch: { [subj.key('rotate')]: rotation } }]);
  };

  window.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);
}

/** Drag the handle to move a widget in the flow: before, after or inside another widget. */
export function startReorder(
  event: { clientX: number; clientY: number },
  id: string,
  viewport: Viewport,
): void {
  const active = nodeElement(id);
  const wrapper = wrapperElement();

  if (!active || !wrapper) {
    return;
  }

  void viewport;

  const end = beginGesture();
  let target: { id: string; mode: DropMode } | null = null;

  const onMove = (move: PointerEvent): void => {
    const hit = document
      .elementsFromPoint(move.clientX, move.clientY)
      .find(
        (element): element is HTMLElement =>
          element instanceof HTMLElement &&
          Boolean(element.dataset.nodeId) &&
          element !== active &&
          !active.contains(element),
      );

    if (!hit || !hit.dataset.nodeId) {
      target = null;
      publish(NO_MARKS);

      return;
    }

    const rect = hit.getBoundingClientRect();
    const ratio = (move.clientY - rect.top) / Math.max(1, rect.height);
    const isContainer = hit.classList.contains('breefer-node-container');
    const isRoot = hit.classList.contains('breefer-node-root');
    let mode: DropMode = ratio < 0.5 ? 'before' : 'after';

    if (isRoot || (isContainer && ratio > 0.25 && ratio < 0.75)) {
      mode = 'inside';
    }

    target = { id: hit.dataset.nodeId, mode };

    const origin = offsetWithin(hit, wrapper);
    const mark: DropMark =
      mode === 'inside'
        ? { left: origin.x, top: origin.y, width: hit.offsetWidth, height: hit.offsetHeight, mode }
        : {
            left: origin.x,
            top: origin.y + (mode === 'after' ? hit.offsetHeight : 0) - 2,
            width: hit.offsetWidth,
            height: 4,
            mode,
          };

    publish({ guides: [], drop: mark });
  };

  const onUp = (): void => {
    window.removeEventListener('pointermove', onMove);
    window.removeEventListener('pointerup', onUp);
    end();

    if (target) {
      useEditorStore.getState().moveTo(id, target.id, target.mode);
    }
  };

  void event;
  window.addEventListener('pointermove', onMove);
  window.addEventListener('pointerup', onUp);
}

export type AlignHow = 'left' | 'hcenter' | 'right' | 'top' | 'vmiddle' | 'bottom';

/** Align to the parent, floating the widget first if it is in the flow. */
export function alignNode(id: string, how: AlignHow, viewport: Viewport): void {
  const subj = subject(id, viewport);

  if (!subj || subj.locked) {
    return;
  }

  const { width, height } = subj.start;
  const pw = subj.parent.clientWidth;
  const ph = subj.parent.clientHeight;
  let { left, top } = subj.start;

  if (how === 'left') left = 0;
  if (how === 'hcenter') left = (pw - width) / 2;
  if (how === 'right') left = pw - width;
  if (how === 'top') top = 0;
  if (how === 'vmiddle') top = (ph - height) / 2;
  if (how === 'bottom') top = ph - height;

  const patches = subj.floating
    ? [{ id, patch: {} as Record<string, unknown> }]
    : floatPatches(subj);
  const main = patches[0] ?? { id, patch: {} };

  main.patch[subj.key('offsetLeft')] = round(left);
  main.patch[subj.key('offsetTop')] = round(top);

  commit(patches);
}

/** Toggle between "floating" (free position) and "in flow". */
export function toggleFloat(id: string, viewport: Viewport): void {
  const subj = subject(id, viewport);

  if (!subj || subj.locked) {
    return;
  }

  if (subj.floating) {
    commit([
      {
        id,
        patch: {
          [subj.key('position')]: undefined,
          [subj.key('offsetLeft')]: undefined,
          [subj.key('offsetTop')]: undefined,
          [subj.key('offsetRight')]: undefined,
          [subj.key('offsetBottom')]: undefined,
        },
      },
    ]);

    return;
  }

  commit(floatPatches(subj));
}

/** Arrow-key nudge. Returns false when the widget is not floating, so the key can do something else. */
export function nudge(id: string, dx: number, dy: number, viewport: Viewport): boolean {
  const subj = subject(id, viewport);

  if (!subj || !subj.floating) {
    return false;
  }

  if (!subj.locked) {
    // Read from the store, not the DOM, so held-down arrow keys never use stale positions.
    const node = findNode(useEditorStore.getState().root, id);
    const left = Number(node?.settings[subj.key('offsetLeft')] ?? node?.settings.offsetLeft);
    const top = Number(node?.settings[subj.key('offsetTop')] ?? node?.settings.offsetTop);

    commit([
      {
        id,
        patch: {
          [subj.key('offsetLeft')]: round((Number.isFinite(left) ? left : subj.start.left) + dx),
          [subj.key('offsetTop')]: round((Number.isFinite(top) ? top : subj.start.top) + dy),
        },
      },
    ]);
  }

  return true;
}

export function clampScale(value: number): number {
  return clamp(value, 0.25, 2);
}
