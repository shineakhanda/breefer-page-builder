import { findNode } from '../lib/tree';
import { resolveSetting } from '../lib/styles';
import { useEditorStore } from '../store/editorStore';
import type { ControlDef, Viewport } from '../types';
import { widgetMap } from '../widgets/definitions';
import { Control } from './Control';

const deviceLabels: Record<Viewport, string> = {
  desktop: 'Desktop',
  tablet: 'Tablet',
  mobile: 'Mobile',
};

const visibilityControls: ControlDef[] = [
  { key: 'hideDesktop', label: 'Hide on desktop', type: 'toggle' },
  { key: 'hideTablet', label: 'Hide on tablet', type: 'toggle' },
  { key: 'hideMobile', label: 'Hide on mobile', type: 'toggle' },
];

const motionControls: ControlDef[] = [
  {
    key: 'animation',
    label: 'Entrance animation',
    type: 'select',
    options: [
      { label: 'None', value: '' },
      { label: 'Fade in', value: 'fade' },
      { label: 'Fade up', value: 'fadeup' },
      { label: 'Fade down', value: 'fadedown' },
      { label: 'Fade from left', value: 'fadeleft' },
      { label: 'Fade from right', value: 'faderight' },
      { label: 'Zoom in', value: 'zoom' },
      { label: 'Slide up', value: 'slideup' },
    ],
  },
  {
    key: 'animationDelay',
    label: 'Animation delay',
    type: 'number',
    min: 0,
    max: 3000,
    unit: 'ms',
  },
  {
    key: 'hover',
    label: 'Hover effect',
    type: 'select',
    options: [
      { label: 'None', value: '' },
      { label: 'Lift', value: 'lift' },
      { label: 'Grow', value: 'grow' },
      { label: 'Glow ring', value: 'glow' },
    ],
  },
  { key: 'opacity', label: 'Opacity', type: 'number', min: 0, max: 1, step: 0.05 },
  { key: 'overflowHidden', label: 'Clip overflow', type: 'toggle' },
];

const advancedControls: ControlDef[] = [
  {
    key: 'visibility',
    label: 'Show to',
    type: 'select',
    options: [
      { label: 'Everyone', value: 'all' },
      { label: 'Logged-in visitors', value: 'loggedin' },
      { label: 'Logged-out visitors', value: 'loggedout' },
    ],
  },
  { key: 'anchorId', label: 'Anchor ID (for #links)', type: 'text', placeholder: 'pricing' },
  { key: 'cssClass', label: 'CSS classes', type: 'text', placeholder: 'my-class another' },
  {
    key: 'customCss',
    label: 'Custom CSS',
    type: 'textarea',
    rows: 6,
    placeholder: 'selector { box-shadow: 0 0 0 2px red; } selector:hover { opacity: .8; }',
  },
];

const layoutControls: ControlDef[] = [
  { key: 'boxWidth', label: 'Width', type: 'number', group: 'layout', min: 1, max: 200, unit: '%' },
  {
    key: 'boxHeight',
    label: 'Height',
    type: 'number',
    group: 'layout',
    min: 0,
    max: 4000,
    unit: 'px',
  },
  {
    key: 'position',
    label: 'Position',
    type: 'select',
    group: 'layout',
    options: [
      { label: 'Default', value: '' },
      { label: 'Relative', value: 'relative' },
      { label: 'Absolute (overlap)', value: 'absolute' },
      { label: 'Sticky (stays in view)', value: 'sticky' },
    ],
  },
  {
    key: 'offsetTop',
    label: 'Top offset',
    type: 'number',
    group: 'layout',
    min: -3000,
    max: 3000,
    unit: 'px',
  },
  {
    key: 'offsetLeft',
    label: 'Left offset',
    type: 'number',
    group: 'layout',
    min: -3000,
    max: 3000,
    unit: 'px',
  },
  {
    key: 'offsetRight',
    label: 'Right offset',
    type: 'number',
    group: 'layout',
    min: -3000,
    max: 3000,
    unit: 'px',
  },
  {
    key: 'offsetBottom',
    label: 'Bottom offset',
    type: 'number',
    group: 'layout',
    min: -3000,
    max: 3000,
    unit: 'px',
  },
  { key: 'zIndex', label: 'Z-index', type: 'number', group: 'layout', min: 0, max: 9999 },
  {
    key: 'rotate',
    label: 'Rotation',
    type: 'number',
    group: 'layout',
    min: -360,
    max: 360,
    unit: 'deg',
  },
  { key: 'locked', label: 'Lock position & size (editor)', type: 'toggle', group: 'layout' },
];

export function Inspector(): JSX.Element {
  const root = useEditorStore((state) => state.root);
  const selectedId = useEditorStore((state) => state.selectedId);
  const viewport = useEditorStore((state) => state.viewport);
  const wooType = useEditorStore((state) => state.wooType);
  const setWooType = useEditorStore((state) => state.setWooType);
  const condition = useEditorStore((state) => state.condition);
  const setCondition = useEditorStore((state) => state.setCondition);
  const layout = useEditorStore((state) => state.layout);
  const setLayout = useEditorStore((state) => state.setLayout);
  const updateSettings = useEditorStore((state) => state.updateSettings);
  const duplicate = useEditorStore((state) => state.duplicateSelected);
  const remove = useEditorStore((state) => state.removeSelected);
  const boot = window.breeferEditor;

  const node = selectedId ? findNode(root, selectedId) : root;

  if (!node) {
    return <aside className="breefer-inspector" />;
  }

  const definition = widgetMap[node.type] ?? null;
  const isRoot = node.id === root.id;
  const contentControls =
    definition?.controls.filter((control) => (control.group ?? 'content') === 'content') ?? [];
  const styleControls = definition?.controls.filter((control) => control.group === 'style') ?? [];
  const storageKey = (key: string) => (viewport === 'desktop' ? key : `${key}_${viewport}`);
  const isOverride = (key: string) =>
    viewport !== 'desktop' && node.settings[storageKey(key)] !== undefined;
  const change = (key: string, value: unknown) =>
    updateSettings(node.id, { [storageKey(key)]: value });
  const reset = (key: string) => updateSettings(node.id, { [storageKey(key)]: undefined });

  const renderControl = (control: ControlDef) => (
    <Control
      key={control.key}
      control={control}
      value={resolveSetting(node.settings, control.key, viewport)}
      isOverride={isOverride(control.key)}
      onReset={() => reset(control.key)}
      onChange={(value) => change(control.key, value)}
    />
  );

  return (
    <aside className="breefer-inspector" aria-label="Widget settings">
      <header className="breefer-panel-head">
        <div>
          <span className="breefer-panel-eyebrow">
            {isRoot ? 'Page' : (definition?.title ?? node.type)}
          </span>
          <h2>{isRoot ? 'Root container' : 'Settings'}</h2>
        </div>
        <code className="breefer-node-id">{node.id}</code>
      </header>

      <div className="breefer-inspector-body">
        <p className="breefer-device-note">
          <strong>{deviceLabels[viewport]}</strong>
          {viewport === 'desktop'
            ? ' styles are the base for every device.'
            : ` overrides inherit from ${viewport === 'mobile' ? 'tablet' : 'desktop'}.`}
        </p>

        {isRoot ? (
          <p className="breefer-hint">
            The root container wraps the page. Select any widget to edit it.
          </p>
        ) : null}

        {isRoot && boot?.postType === 'breefer_template' ? (
          <section className="breefer-controls-group">
            <h3>Template type</h3>
            <select
              className="breefer-input"
              value={wooType || 'none'}
              onChange={(event) => setWooType(event.target.value)}
            >
              <option value="none">Reusable block (shortcode only)</option>
              <optgroup label="Site templates">
                {(boot.templateTypes ?? [])
                  .filter((type) => type.group === 'theme')
                  .map((type) => (
                    <option key={type.slug} value={type.slug}>
                      {type.label}
                    </option>
                  ))}
              </optgroup>
              {boot.wooAvailable ? (
                <optgroup label="WooCommerce">
                  {(boot.templateTypes ?? [])
                    .filter((type) => type.group === 'woocommerce')
                    .map((type) => (
                      <option key={type.slug} value={type.slug}>
                        {type.label}
                      </option>
                    ))}
                </optgroup>
              ) : null}
            </select>
            <p className="breefer-hint">
              {(boot.templateTypes ?? []).find((type) => type.slug === wooType)?.hint ??
                'Insert it anywhere with the [breefer id] shortcode or the Breefer block.'}
            </p>
            {(boot.templateTypes ?? []).find((type) => type.slug === wooType)?.conditional ? (
              <>
                <select
                  className="breefer-input"
                  value={condition || 'all'}
                  onChange={(event) => setCondition(event.target.value)}
                >
                  {Object.entries(boot.conditions ?? {}).map(([slug, label]) => (
                    <option key={slug} value={slug}>
                      {label}
                    </option>
                  ))}
                </select>
                <p className="breefer-hint">
                  Where this {wooType} appears. The most specific, most recently edited match wins.
                </p>
              </>
            ) : null}
          </section>
        ) : null}

        {isRoot && boot?.postType !== 'breefer_template' ? (
          <section className="breefer-controls-group">
            <h3>Page layout</h3>
            <select
              className="breefer-input"
              value={layout || 'theme'}
              onChange={(event) => setLayout(event.target.value)}
            >
              <option value="theme">Theme</option>
              <option value="full">Full width (theme header/footer)</option>
              <option value="canvas">Canvas (no header/footer)</option>
            </select>
            <p className="breefer-hint">
              Canvas removes the theme header and footer, useful for landing pages. Saving applies
              the layout on the frontend.
            </p>
          </section>
        ) : null}

        {contentControls.length > 0 ? (
          <section className="breefer-controls-group">
            <h3>Content</h3>
            {contentControls.map(renderControl)}
          </section>
        ) : null}

        <section className="breefer-controls-group">
          <h3>Layout &amp; overlap</h3>
          {layoutControls.map(renderControl)}
        </section>

        {styleControls.length > 0 ? (
          <section className="breefer-controls-group">
            <h3>Style</h3>
            {styleControls.map(renderControl)}
          </section>
        ) : null}

        {!isRoot ? (
          <section className="breefer-controls-group">
            <h3>Motion &amp; effects</h3>
            {motionControls.map(renderControl)}
          </section>
        ) : null}

        <section className="breefer-controls-group">
          <h3>Visibility</h3>
          {visibilityControls.map((control) => (
            <Control
              key={control.key}
              control={control}
              value={node.settings[control.key]}
              onChange={(value) => updateSettings(node.id, { [control.key]: value })}
            />
          ))}
          {!isRoot
            ? advancedControls.map((control) => (
                <Control
                  key={control.key}
                  control={control}
                  value={node.settings[control.key]}
                  onChange={(value) => updateSettings(node.id, { [control.key]: value })}
                />
              ))
            : null}
        </section>
      </div>

      {!isRoot ? (
        <footer className="breefer-inspector-foot">
          <button type="button" className="breefer-button" onClick={duplicate}>
            Duplicate
          </button>
          <button type="button" className="breefer-button is-danger" onClick={remove}>
            Delete
          </button>
        </footer>
      ) : null}
    </aside>
  );
}
