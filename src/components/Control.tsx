import type { ControlDef, SelectOption } from '../types';
import { IconPicker } from './Icons';

interface ControlProps {
  control: ControlDef;
  value: unknown;
  onChange: (value: unknown) => void;
  isOverride?: boolean;
  onReset?: () => void;
}

function stringValue(value: unknown): string {
  if (typeof value === 'string') {
    return value;
  }

  if (typeof value === 'number') {
    return String(value);
  }

  return '';
}

function numberValue(value: unknown): number {
  if (typeof value === 'number' && Number.isFinite(value)) {
    return value;
  }

  if (typeof value === 'string' && value.trim() !== '') {
    const parsed = Number(value);

    return Number.isFinite(parsed) ? parsed : 0;
  }

  return 0;
}

function colorValue(value: unknown): string {
  if (typeof value === 'string') {
    const token = /^global:([a-z]+)$/.exec(value);

    if (token) {
      const match = window.breeferEditor?.designTokens?.find((item) => item.slug === token[1]);

      return match && /^#[0-9a-f]{6}$/i.test(match.value) ? match.value : '#ffffff';
    }
  }

  return typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value) ? value : '#ffffff';
}

function selectOptions(control: ControlDef): SelectOption[] {
  if (control.optionsSource) {
    const source = window.breeferEditor?.[control.optionsSource] ?? [];

    return [{ label: '— Select —', value: '' }, ...source];
  }

  return control.options ?? [];
}

export function Control({
  control,
  value,
  onChange,
  isOverride,
  onReset,
}: ControlProps): JSX.Element {
  const id = `breefer-control-${control.key}`;
  const label = (
    <span className="breefer-control-label">
      {control.label}
      {control.unit ? <em>{control.unit}</em> : null}
    </span>
  );
  const reset =
    isOverride && onReset ? (
      <button
        type="button"
        className="breefer-reset"
        title="Reset this device value"
        onClick={onReset}
      >
        Reset
      </button>
    ) : null;

  if (control.type === 'toggle') {
    return (
      <div className="breefer-toggle-row">
        <label htmlFor={id}>{label}</label>
        <span className="breefer-toggle-side">
          {reset}
          <label className="breefer-switch" htmlFor={id}>
            <input
              id={id}
              type="checkbox"
              checked={Boolean(value)}
              onChange={(event) => onChange(event.target.checked)}
            />
            <span className="breefer-switch-track" aria-hidden="true">
              <span className="breefer-switch-thumb" />
            </span>
          </label>
        </span>
      </div>
    );
  }

  return (
    <div className="breefer-control">
      <div className="breefer-control-head">
        <label htmlFor={id}>{label}</label>
        {reset}
      </div>

      {control.type === 'textarea' ? (
        <textarea
          id={id}
          className="breefer-input breefer-textarea"
          rows={control.rows ?? 4}
          placeholder={control.placeholder}
          value={stringValue(value)}
          onChange={(event) => onChange(event.target.value)}
        />
      ) : null}

      {control.type === 'select' ? (
        <select
          id={id}
          className="breefer-input"
          value={stringValue(value)}
          onChange={(event) => onChange(event.target.value)}
        >
          {selectOptions(control).map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ) : null}

      {control.type === 'number' ? (
        <input
          id={id}
          className="breefer-input"
          type="number"
          min={control.min}
          max={control.max}
          step={control.step ?? 1}
          value={value === undefined || value === null ? '' : numberValue(value)}
          onChange={(event) =>
            onChange(event.target.value === '' ? undefined : Number(event.target.value))
          }
        />
      ) : null}

      {control.type === 'color' ? (
        <div className="breefer-color-row">
          <input
            id={id}
            className="breefer-color"
            type="color"
            value={colorValue(value)}
            onChange={(event) => onChange(event.target.value)}
          />
          <input
            className="breefer-input breefer-color-hex"
            type="text"
            value={stringValue(value)}
            spellCheck={false}
            onChange={(event) => onChange(event.target.value)}
          />
        </div>
      ) : null}

      {control.type === 'color' ? (
        <div className="breefer-swatches" role="group" aria-label="Global colours">
          {(window.breeferEditor?.designTokens ?? []).map((token) => (
            <button
              key={token.slug}
              type="button"
              className={`breefer-swatch${value === `global:${token.slug}` ? ' is-active' : ''}`}
              style={{ backgroundColor: token.value }}
              title={`Global: ${token.label}`}
              aria-label={`Use global colour ${token.label}`}
              onClick={() => onChange(`global:${token.slug}`)}
            />
          ))}
          {value ? (
            <button type="button" className="breefer-swatch-clear" onClick={() => onChange('')}>
              Clear
            </button>
          ) : null}
        </div>
      ) : null}

      {control.type === 'text' ? (
        <input
          id={id}
          className="breefer-input"
          type="text"
          placeholder={control.placeholder}
          value={stringValue(value)}
          onChange={(event) => onChange(event.target.value)}
        />
      ) : null}

      {control.type === 'icon' ? (
        <IconPicker value={value} onChange={(next) => onChange(next)} />
      ) : null}
    </div>
  );
}
