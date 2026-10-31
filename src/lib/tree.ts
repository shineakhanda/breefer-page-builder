import type { BuilderNode } from '../types';
import { createId } from './ids';

export function findNode(root: BuilderNode, id: string): BuilderNode | null {
  if (root.id === id) {
    return root;
  }

  for (const child of root.children) {
    const found = findNode(child, id);

    if (found) {
      return found;
    }
  }

  return null;
}

export function findParent(
  root: BuilderNode,
  id: string,
): { parent: BuilderNode; index: number } | null {
  const index = root.children.findIndex((child) => child.id === id);

  if (index >= 0) {
    return { parent: root, index };
  }

  for (const child of root.children) {
    const found = findParent(child, id);

    if (found) {
      return found;
    }
  }

  return null;
}

export function containsNode(root: BuilderNode, id: string): boolean {
  return findNode(root, id) !== null;
}

export function updateNode(
  root: BuilderNode,
  id: string,
  patch: Partial<BuilderNode>,
): BuilderNode {
  if (root.id === id) {
    return { ...root, ...patch };
  }

  let changed = false;
  const children = root.children.map((child) => {
    const next = updateNode(child, id, patch);

    if (next !== child) {
      changed = true;
    }

    return next;
  });

  return changed ? { ...root, children } : root;
}

export function removeNode(root: BuilderNode, id: string): BuilderNode {
  const children = root.children
    .filter((child) => child.id !== id)
    .map((child) => removeNode(child, id));

  return { ...root, children };
}

export function insertNode(
  root: BuilderNode,
  parentId: string,
  node: BuilderNode,
  index?: number,
): BuilderNode {
  if (root.id === parentId) {
    const children = [...root.children];
    const at =
      index === undefined ? children.length : Math.max(0, Math.min(children.length, index));
    children.splice(at, 0, node);

    return { ...root, children };
  }

  let changed = false;
  const children = root.children.map((child) => {
    const next = insertNode(child, parentId, node, index);

    if (next !== child) {
      changed = true;
    }

    return next;
  });

  return changed ? { ...root, children } : root;
}

export function cloneNode(node: BuilderNode): BuilderNode {
  return {
    ...node,
    id: createId(),
    settings: { ...node.settings },
    children: node.children.map(cloneNode),
  };
}

export function countNodes(node: BuilderNode): number {
  return 1 + node.children.reduce((total, child) => total + countNodes(child), 0);
}

export function moveNode(root: BuilderNode, activeId: string, overId: string): BuilderNode {
  if (activeId === overId) {
    return root;
  }

  const active = findNode(root, activeId);
  const over = findNode(root, overId);

  if (!active || !over || containsNode(active, overId)) {
    return root;
  }

  const activeParent = findParent(root, activeId);
  const overParent = findParent(root, overId);

  if (!activeParent || !overParent) {
    return root;
  }

  if (activeParent.parent.id === overParent.parent.id) {
    const children = [...activeParent.parent.children];
    const from = children.findIndex((child) => child.id === activeId);
    const to = children.findIndex((child) => child.id === overId);
    const moving = children[from];

    if (from < 0 || to < 0 || !moving) {
      return root;
    }

    children.splice(from, 1);
    children.splice(to, 0, moving);

    return updateNode(root, activeParent.parent.id, { children });
  }

  // Dropping onto a container nests the node instead of reordering it.
  if (over.type === 'container') {
    return insertNode(removeNode(root, activeId), over.id, active);
  }

  const without = removeNode(root, activeId);

  return insertNode(without, overParent.parent.id, active, overParent.index);
}

export type DropMode = 'before' | 'after' | 'inside';

/**
 * Move a node next to (or into) another node. Refuses to drop a node into
 * itself or one of its own descendants.
 */
export function moveNodeTo(
  root: BuilderNode,
  activeId: string,
  overId: string,
  mode: DropMode,
): BuilderNode {
  if (activeId === overId || activeId === root.id) {
    return root;
  }

  const active = findNode(root, activeId);

  if (!active || containsNode(active, overId)) {
    return root;
  }

  const without = removeNode(root, activeId);

  if (mode === 'inside') {
    const target = findNode(without, overId);

    return target && target.type === 'container' ? insertNode(without, overId, active) : root;
  }

  const parent = findParent(without, overId);

  if (!parent) {
    return root;
  }

  return insertNode(
    without,
    parent.parent.id,
    active,
    mode === 'before' ? parent.index : parent.index + 1,
  );
}
