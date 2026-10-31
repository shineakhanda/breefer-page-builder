import { create } from 'zustand';

import { saveDocument } from '../lib/api';
import { createId } from '../lib/ids';
import {
  type DropMode,
  cloneNode,
  countNodes,
  findNode,
  findParent,
  insertNode,
  moveNode,
  moveNodeTo,
  removeNode,
  updateNode,
} from '../lib/tree';
import type { Bootstrap, BuilderNode, Viewport } from '../types';
import { widgetMap } from '../widgets/definitions';

const boot: Bootstrap | undefined = window.breeferEditor;

const fallbackRoot: BuilderNode = {
  id: 'root',
  type: 'container',
  settings: {},
  children: [],
};

interface EditorState {
  postId: number;
  title: string;
  status: string;
  root: BuilderNode;
  selectedId: string | null;
  dirty: boolean;
  saving: boolean;
  savedAt: string | null;
  error: string | null;
  history: BuilderNode[];
  future: BuilderNode[];
  viewport: Viewport;
  historyLimit: number;
  wooType: string;
  condition: string;
  layout: string;
  /** 0 means fit to the available width. */
  zoom: number;
  snap: boolean;
  select: (id: string | null) => void;
  setTitle: (title: string) => void;
  setViewport: (viewport: Viewport) => void;
  setWooType: (wooType: string) => void;
  setCondition: (condition: string) => void;
  setLayout: (layout: string) => void;
  addWidget: (type: string, parentId?: string) => void;
  insertSection: (nodes: BuilderNode[]) => void;
  setZoom: (zoom: number) => void;
  toggleSnap: () => void;
  /** Apply several settings patches as one undoable step. */
  patchSettings: (patches: Array<{ id: string; patch: Record<string, unknown> }>) => void;
  moveTo: (activeId: string, overId: string, mode: DropMode) => void;
  arrange: (id: string, how: 'front' | 'back' | 'forward' | 'backward') => void;
  updateSettings: (id: string, patch: Record<string, unknown>) => void;
  removeSelected: () => void;
  duplicateSelected: () => void;
  move: (activeId: string, overId: string) => void;
  undo: () => void;
  redo: () => void;
  save: () => Promise<void>;
  publish: () => Promise<void>;
  clearError: () => void;
}

interface CommitState {
  root: BuilderNode;
  history: BuilderNode[];
  future: BuilderNode[];
  dirty: boolean;
  selectedId?: string | null;
}

function withHistory(
  state: EditorState,
  root: BuilderNode,
  selectedId?: string | null,
): CommitState {
  const committed: CommitState = {
    root,
    history: [...state.history, state.root].slice(-state.historyLimit),
    future: [],
    dirty: true,
  };

  if (selectedId !== undefined) {
    committed.selectedId = selectedId;
  }

  return committed;
}

export const useEditorStore = create<EditorState>((set, get) => ({
  postId: boot?.postId ?? 0,
  title: boot?.title ?? '',
  status: boot?.status || 'draft',
  root: boot?.data?.root ?? fallbackRoot,
  selectedId: null,
  dirty: false,
  saving: false,
  savedAt: null,
  error: null,
  history: [],
  future: [],
  viewport: 'desktop',
  historyLimit: boot?.historyLimit ?? 50,
  wooType: boot?.wooType || 'none',
  condition: boot?.condition || 'all',
  layout: boot?.layout || 'theme',
  zoom: 0,
  snap: true,

  select: (id) => set({ selectedId: id }),

  setTitle: (title) => set({ title, dirty: true }),

  setViewport: (viewport) => set({ viewport }),

  setWooType: (wooType) => set({ wooType, dirty: true }),

  setCondition: (condition) => set({ condition, dirty: true }),

  setZoom: (zoom) => set({ zoom: zoom <= 0 ? 0 : Math.max(0.25, Math.min(2, zoom)) }),

  toggleSnap: () => set((state) => ({ snap: !state.snap })),

  patchSettings: (patches) =>
    set((state) => {
      let root = state.root;

      for (const { id, patch } of patches) {
        const node = findNode(root, id);

        if (node) {
          root = updateNode(root, id, { settings: { ...node.settings, ...patch } });
        }
      }

      return root === state.root ? {} : withHistory(state, root);
    }),

  moveTo: (activeId, overId, mode) =>
    set((state) => {
      const next = moveNodeTo(state.root, activeId, overId, mode);

      return next === state.root ? {} : withHistory(state, next, activeId);
    }),

  arrange: (id, how) =>
    set((state) => {
      const found = findParent(state.root, id);

      if (!found) {
        return {};
      }

      // Only positioned siblings stack by z-index, so number them in their current order first.
      const ordered = found.parent.children
        .map((child, index) => ({ child, index }))
        .filter(({ child }) => child.settings.position === 'absolute' || child.id === id)
        .sort((a, b) => {
          const za = typeof a.child.settings.zIndex === 'number' ? a.child.settings.zIndex : 0;
          const zb = typeof b.child.settings.zIndex === 'number' ? b.child.settings.zIndex : 0;

          return za - zb || a.index - b.index;
        })
        .map(({ child }) => child.id);

      const at = ordered.indexOf(id);
      ordered.splice(at, 1);

      const target =
        how === 'front'
          ? ordered.length
          : how === 'back'
            ? 0
            : how === 'forward'
              ? Math.min(ordered.length, at + 1)
              : Math.max(0, at - 1);

      ordered.splice(target, 0, id);

      let root = state.root;

      ordered.forEach((nodeId, rank) => {
        const node = findNode(root, nodeId);

        if (node) {
          root = updateNode(root, nodeId, { settings: { ...node.settings, zIndex: rank + 1 } });
        }
      });

      return withHistory(state, root);
    }),

  insertSection: (nodes) =>
    set((state) => {
      let root = state.root;
      let selectedId: string | undefined;

      for (const node of nodes) {
        root = insertNode(root, state.root.id, node);
        selectedId = node.id;
      }

      return nodes.length ? withHistory(state, root, selectedId) : {};
    }),

  setLayout: (layout) => set({ layout, dirty: true }),

  addWidget: (type, parentId) =>
    set((state) => {
      const definition = widgetMap[type];

      if (!definition) {
        return {};
      }

      const selected = state.selectedId ? findNode(state.root, state.selectedId) : null;
      let target = parentId ?? '';

      if (!target) {
        if (selected?.type === 'container') {
          target = selected.id;
        } else if (selected) {
          const parent = findParent(state.root, selected.id);
          target = parent?.parent.id ?? state.root.id;
        } else {
          target = state.root.id;
        }
      }

      const node: BuilderNode = {
        id: createId(),
        type,
        settings: { ...definition.defaults },
        children: [],
      };

      const host = findNode(state.root, target);

      if (host && host.settings.layout === 'free') {
        const step = (host.children.length % 8) * 28;

        node.settings = {
          boxWidth: 30,
          ...node.settings,
          position: 'absolute',
          offsetLeft: 40 + step,
          offsetTop: 40 + step,
          zIndex: host.children.length + 1,
        };
      }

      return withHistory(state, insertNode(state.root, target, node), node.id);
    }),

  updateSettings: (id, patch) =>
    set((state) => {
      const node = findNode(state.root, id);

      if (!node) {
        return {};
      }

      const next = updateNode(state.root, id, {
        settings: { ...node.settings, ...patch },
      });

      return withHistory(state, next);
    }),

  removeSelected: () =>
    set((state) => {
      if (!state.selectedId || state.selectedId === state.root.id) {
        return {};
      }

      const parent = findParent(state.root, state.selectedId);
      const next = removeNode(state.root, state.selectedId);

      return withHistory(state, next, parent?.parent.id ?? state.root.id);
    }),

  duplicateSelected: () =>
    set((state) => {
      if (!state.selectedId || state.selectedId === state.root.id) {
        return {};
      }

      const parent = findParent(state.root, state.selectedId);
      const node = findNode(state.root, state.selectedId);

      if (!parent || !node) {
        return {};
      }

      const copy = cloneNode(node);

      // A floating copy would sit exactly on its original, so nudge it.
      if (copy.settings.position === 'absolute') {
        copy.settings = {
          ...copy.settings,
          offsetLeft: (Number(copy.settings.offsetLeft) || 0) + 24,
          offsetTop: (Number(copy.settings.offsetTop) || 0) + 24,
        };
      }

      const next = insertNode(state.root, parent.parent.id, copy, parent.index + 1);

      return withHistory(state, next, copy.id);
    }),

  move: (activeId, overId) =>
    set((state) => {
      const next = moveNode(state.root, activeId, overId);

      if (next === state.root) {
        return {};
      }

      return withHistory(state, next, activeId);
    }),

  undo: () =>
    set((state) => {
      const previous = state.history[state.history.length - 1];

      if (!previous) {
        return {};
      }

      return {
        root: previous,
        history: state.history.slice(0, -1),
        future: [state.root, ...state.future].slice(0, state.historyLimit),
        dirty: true,
        selectedId: null,
      };
    }),

  redo: () =>
    set((state) => {
      const next = state.future[0];

      if (!next) {
        return {};
      }

      return {
        root: next,
        history: [...state.history, state.root].slice(-state.historyLimit),
        future: state.future.slice(1),
        dirty: true,
        selectedId: null,
      };
    }),

  save: async () => {
    const state = get();

    if (!boot || state.saving) {
      return;
    }

    set({ saving: true, error: null });

    try {
      const response = await saveDocument(
        boot,
        state.title,
        state.root,
        state.wooType,
        state.condition,
        state.layout,
        state.status,
      );

      set({
        saving: false,
        dirty: false,
        savedAt: response.modified,
        title: response.title || state.title,
        wooType: response.wooType || state.wooType,
        condition: response.condition || state.condition,
        layout: response.layout || state.layout,
        status: response.status || state.status,
      });
    } catch (error) {
      set({
        saving: false,
        error: error instanceof Error ? error.message : 'Save failed',
      });
    }
  },

  publish: async () => {
    const previous = get().status;

    set({ status: 'publish' });
    await get().save();

    if (get().error) {
      set({ status: previous });
    }
  },

  clearError: () => set({ error: null }),
}));

export function visibleNodeCount(): number {
  return countNodes(useEditorStore.getState().root);
}
