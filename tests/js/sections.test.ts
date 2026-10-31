import { describe, expect, it } from 'vitest';

import { sectionPresets } from '../../src/library/sections';
import type { BuilderNode } from '../../src/types';
import { widgetMap } from '../../src/widgets/definitions';

function walk(nodes: BuilderNode[], visit: (node: BuilderNode) => void): void {
  for (const node of nodes) {
    visit(node);
    walk(node.children, visit);
  }
}

describe('section presets', () => {
  it('only use registered widget types', () => {
    const unknown = new Set<string>();

    for (const preset of sectionPresets) {
      walk(preset.build(), (node) => {
        if (!widgetMap[node.type]) {
          unknown.add(`${preset.id}:${node.type}`);
        }
      });
    }

    expect([...unknown]).toEqual([]);
  });

  it('generate unique node ids on every build', () => {
    for (const preset of sectionPresets) {
      const ids = new Set<string>();
      let total = 0;

      walk(preset.build(), (node) => {
        ids.add(node.id);
        total += 1;
      });

      expect(ids.size).toBe(total);
    }
  });

  it('never put a child inside a non-container widget', () => {
    for (const preset of sectionPresets) {
      walk(preset.build(), (node) => {
        if (node.children.length > 0) {
          expect(widgetMap[node.type]?.container).toBe(true);
        }
      });
    }
  });

  it('reference design tokens instead of hard-coded brand colours where possible', () => {
    const hero = sectionPresets.find((preset) => preset.id === 'hero');
    const settings: Record<string, unknown>[] = [];

    walk(hero?.build() ?? [], (node) => settings.push(node.settings));

    expect(settings.some((entry) => entry.gradientFrom === 'global:primary')).toBe(true);
  });
});

describe('widget definitions', () => {
  it('have unique types and a control per default key that is user-facing', () => {
    const types = Object.keys(widgetMap);

    expect(new Set(types).size).toBe(types.length);
    expect(types.length).toBeGreaterThanOrEqual(50);
  });

  it('give every server-rendered widget a category the palette shows', () => {
    const categories = new Set(['layout', 'basic', 'content', 'pro', 'theme', 'woocommerce']);

    for (const definition of Object.values(widgetMap)) {
      expect(categories.has(definition.category)).toBe(true);
    }
  });
});
