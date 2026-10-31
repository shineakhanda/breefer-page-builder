import type { BuilderNode } from '../types';

export interface Box {
  left: number;
  top: number;
  width: number;
  height: number;
}

export function nodeElement(id: string): HTMLElement | null {
  return document.querySelector<HTMLElement>(`[data-node-id="${id}"]`);
}

/**
 * Box of a node inside its parent's padding box, which is the coordinate space
 * of CSS `left`/`top` on an absolutely positioned child. Margins are removed
 * because `left` positions the margin edge.
 */
export function boxInParent(element: HTMLElement): Box {
  const style = window.getComputedStyle(element);

  return {
    left: element.offsetLeft - (parseFloat(style.marginLeft) || 0),
    top: element.offsetTop - (parseFloat(style.marginTop) || 0),
    width: element.offsetWidth,
    height: element.offsetHeight,
  };
}

/** Top-left of an element in the coordinates of an ancestor (layout pixels, unscaled). */
export function offsetWithin(
  element: HTMLElement,
  ancestor: HTMLElement,
): { x: number; y: number } {
  let x = 0;
  let y = 0;
  let current: HTMLElement | null = element;

  while (current && current !== ancestor) {
    x += current.offsetLeft;
    y += current.offsetTop;

    const next = current.offsetParent as HTMLElement | null;

    if (next && next !== ancestor) {
      x += next.clientLeft;
      y += next.clientTop;
    }

    current = next;
  }

  return { x, y };
}

export function isAbsolute(element: HTMLElement): boolean {
  return window.getComputedStyle(element).position === 'absolute';
}

export function round(value: number, places = 0): number {
  const factor = 10 ** places;

  return Math.round(value * factor) / factor;
}

export function clamp(value: number, min: number, max: number): number {
  return Math.max(min, Math.min(max, value));
}

export interface SnapTargets {
  x: number[];
  y: number[];
}

/** Edges and centres of the parent and every visible sibling, in parent coordinates. */
export function snapTargets(element: HTMLElement, parent: HTMLElement): SnapTargets {
  const x = [0, parent.clientWidth / 2, parent.clientWidth];
  const y = [0, parent.clientHeight / 2, parent.clientHeight];

  Array.from(parent.children).forEach((child) => {
    if (!(child instanceof HTMLElement) || child === element || !child.dataset.nodeId) {
      return;
    }

    if (child.offsetWidth === 0 && child.offsetHeight === 0) {
      return;
    }

    const box = boxInParent(child);

    x.push(box.left, box.left + box.width / 2, box.left + box.width);
    y.push(box.top, box.top + box.height / 2, box.top + box.height);
  });

  return { x, y };
}

export interface SnapResult {
  delta: number;
  guide: number | null;
}

/** Find the smallest adjustment that aligns any of `points` to any of `targets`. */
export function snapAxis(points: number[], targets: number[], threshold: number): SnapResult {
  let best: SnapResult = { delta: 0, guide: null };
  let bestDistance = threshold + 1;

  points.forEach((point) => {
    targets.forEach((target) => {
      const distance = Math.abs(target - point);

      if (distance <= threshold && distance < bestDistance) {
        bestDistance = distance;
        best = { delta: target - point, guide: target };
      }
    });
  });

  return best;
}

export function nodeIsFloating(node: BuilderNode): boolean {
  return node.settings.position === 'absolute';
}
