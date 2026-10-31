import { useMemo, useState } from 'react';

import { useEditorStore } from '../store/editorStore';
import type { BuilderNode } from '../types';
import { sectionGroups, sectionPresets } from '../library/sections';
import { widgetCategories, widgetDefinitions, widgetMap } from '../widgets/definitions';

function NavigatorBranch({ node, depth }: { node: BuilderNode; depth: number }): JSX.Element {
  const selectedId = useEditorStore((state) => state.selectedId);
  const select = useEditorStore((state) => state.select);
  const definition = widgetMap[node.type];
  const isRoot = 'root' === node.id;

  return (
    <div>
      <button
        type="button"
        className={`breefer-nav-row${selectedId === node.id ? ' is-active' : ''}`}
        style={{ paddingLeft: `${8 + depth * 12}px` }}
        onClick={() => select(node.id)}
      >
        <span
          className={`dashicons ${isRoot ? 'dashicons-layout' : (definition?.icon ?? 'dashicons-marker')}`}
          aria-hidden="true"
        />
        <span className="breefer-nav-label">
          {isRoot ? 'Page' : (definition?.title ?? node.type)}
        </span>
      </button>
      {node.children.map((child) => (
        <NavigatorBranch key={child.id} node={child} depth={depth + 1} />
      ))}
    </div>
  );
}

export function Palette(): JSX.Element {
  const [query, setQuery] = useState('');
  const [mode, setMode] = useState<'widgets' | 'sections' | 'navigator'>('widgets');
  const root = useEditorStore((state) => state.root);
  const addWidget = useEditorStore((state) => state.addWidget);
  const insertSection = useEditorStore((state) => state.insertSection);

  const groups = useMemo(() => {
    const needle = query.trim().toLowerCase();

    return widgetCategories.map((category) => ({
      ...category,
      widgets: widgetDefinitions.filter((widget) => {
        if (widget.category !== category.id) {
          return false;
        }

        if (!needle) {
          return true;
        }

        return (
          widget.title.toLowerCase().includes(needle) ||
          widget.description.toLowerCase().includes(needle)
        );
      }),
    }));
  }, [query]);

  const total = groups.reduce((sum, group) => sum + group.widgets.length, 0);

  return (
    <aside className="breefer-palette" aria-label="Widget library and navigator">
      <header className="breefer-panel-head">
        <div>
          <span className="breefer-panel-eyebrow">Library</span>
          <h2>{mode === 'widgets' ? 'Widgets' : mode === 'sections' ? 'Sections' : 'Navigator'}</h2>
        </div>
        {mode === 'widgets' ? (
          <span className="breefer-count-badge">{widgetDefinitions.length}</span>
        ) : null}
      </header>

      <div className="breefer-panel-tabs" role="tablist" aria-label="Left panel">
        <button
          type="button"
          role="tab"
          aria-selected={mode === 'widgets'}
          className={mode === 'widgets' ? 'is-active' : ''}
          onClick={() => setMode('widgets')}
        >
          Widgets
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={mode === 'sections'}
          className={mode === 'sections' ? 'is-active' : ''}
          onClick={() => setMode('sections')}
        >
          Sections
        </button>
        <button
          type="button"
          role="tab"
          aria-selected={mode === 'navigator'}
          className={mode === 'navigator' ? 'is-active' : ''}
          onClick={() => setMode('navigator')}
        >
          Navigator
        </button>
      </div>

      {mode === 'widgets' ? (
        <>
          <input
            className="breefer-input breefer-search"
            type="search"
            placeholder="Search widgets"
            aria-label="Search widgets"
            value={query}
            onChange={(event) => setQuery(event.target.value)}
          />

          <div className="breefer-palette-body">
            {groups.map((group) =>
              group.widgets.length > 0 ? (
                <section key={group.id} className="breefer-palette-group">
                  <h3>{group.label}</h3>
                  <div className="breefer-palette-grid">
                    {group.widgets.map((widget) => (
                      <button
                        key={widget.type}
                        type="button"
                        className="breefer-widget"
                        title={widget.description}
                        onClick={() => addWidget(widget.type)}
                      >
                        <span className={`dashicons ${widget.icon}`} aria-hidden="true" />
                        <span>{widget.title}</span>
                      </button>
                    ))}
                  </div>
                </section>
              ) : null,
            )}

            {total === 0 ? <p className="breefer-hint">No widgets match that search.</p> : null}
          </div>

          <p className="breefer-palette-foot">
            Click a widget to add it inside the selected container.
          </p>
        </>
      ) : mode === 'sections' ? (
        <>
          <div className="breefer-palette-body">
            {sectionGroups.map((group) => (
              <section key={group.id} className="breefer-palette-group">
                <h3>{group.label}</h3>
                <div className="breefer-section-list">
                  {sectionPresets
                    .filter((preset) => preset.group === group.id)
                    .map((preset) => (
                      <button
                        key={preset.id}
                        type="button"
                        className="breefer-section-card"
                        onClick={() => insertSection(preset.build())}
                      >
                        <strong>{preset.title}</strong>
                        <span>{preset.description}</span>
                      </button>
                    ))}
                </div>
              </section>
            ))}
          </div>
          <p className="breefer-palette-foot">
            Click a preset to append it to the page. Colours follow your design system.
          </p>
        </>
      ) : (
        <div className="breefer-palette-body breefer-navigator">
          <NavigatorBranch node={root} depth={0} />
        </div>
      )}
    </aside>
  );
}
