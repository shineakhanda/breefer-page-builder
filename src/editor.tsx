/**
 * Editor entry point.
 *
 * PHP mounts this into the builder screen and passes a bootstrap config on
 * window.breeferEditor. The frontend never loads this bundle.
 */
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';

import { EditorApp } from './EditorApp';
import './styles/editor.css';

const mount = document.getElementById('breefer-editor-root');

if (!mount) {
  throw new Error('Breefer: editor root element was not found.');
}

createRoot(mount).render(
  <StrictMode>
    <EditorApp />
  </StrictMode>,
);
