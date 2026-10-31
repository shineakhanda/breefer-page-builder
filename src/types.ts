export interface BuilderNode {
  id: string;
  type: string;
  settings: Record<string, unknown>;
  children: BuilderNode[];
}

export interface BuilderDocument {
  version: number;
  root: BuilderNode;
}

export type Viewport = 'desktop' | 'tablet' | 'mobile';

export interface IconDef {
  slug: string;
  label: string;
  paths: string[];
  fill?: boolean;
}

export interface Bootstrap {
  postId: number;
  title: string;
  status: string;
  postType: string;
  restUrl: string;
  nonce: string;
  data: BuilderDocument;
  previewUrl: string;
  templatesUrl: string;
  dashboardUrl: string;
  autosave: number;
  historyLimit: number;
  canvasWidth: number;
  icons: Record<string, { label: string; paths: string[]; fill?: boolean }>;
  wooType: string;
  wooAvailable: boolean;
  layout: string;
  templateTypes: TemplateTypeInfo[];
  conditions: Record<string, string>;
  condition: string;
  designTokens: DesignToken[];
  menus: SelectOption[];
  postTypes: SelectOption[];
}

export interface TemplateTypeInfo {
  slug: string;
  label: string;
  group: 'theme' | 'woocommerce';
  conditional: boolean;
  hint: string;
}

export interface DesignToken {
  slug: string;
  label: string;
  value: string;
}

export type ControlType = 'text' | 'textarea' | 'number' | 'select' | 'color' | 'toggle' | 'icon';

export type OptionsSource = 'menus' | 'postTypes';

export interface SelectOption {
  label: string;
  value: string;
}

export interface ControlDef {
  key: string;
  label: string;
  type: ControlType;
  group?: 'content' | 'style' | 'layout';
  options?: SelectOption[];
  optionsSource?: OptionsSource;
  rows?: number;
  min?: number;
  max?: number;
  step?: number;
  placeholder?: string;
  unit?: string;
}

export interface WidgetDef {
  type: string;
  title: string;
  description: string;
  category: 'layout' | 'basic' | 'content' | 'pro' | 'theme' | 'woocommerce';
  icon: string;
  container?: boolean;
  /** Rendered by the server so the canvas shows live data. */
  server?: boolean;
  defaults: Record<string, unknown>;
  controls: ControlDef[];
}

declare global {
  interface Window {
    breeferEditor?: Bootstrap;
  }
}
