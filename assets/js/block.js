/**
 * Breefer block for the WordPress block editor.
 *
 * Registered with the wp.* globals so it needs no build step.
 */
(function (blocks, element, components, serverSideRender, i18n) {
  'use strict';

  var el = element.createElement;
  var config = window.breeferBlock || { templates: [], editorUrl: '', label: 'Breefer' };

  function edit(props) {
    var value = props.attributes.id || 0;
    var options = config.templates.length
      ? config.templates
      : [{ label: i18n.__('No templates yet', 'breefer'), value: 0 }];

    return el(
      'div',
      { className: 'breefer-block-editor' },
      el(
        components.Placeholder,
        {
          icon: 'layout',
          label: config.label,
          instructions: i18n.__('Choose a saved Breefer template to render here.', 'breefer'),
        },
        el(components.SelectControl, {
          value: value,
          options: options,
          onChange: function (next) {
            props.setAttributes({ id: parseInt(next, 10) || 0 });
          },
        }),
        el(
          'a',
          {
            href: config.editorUrl,
            target: '_blank',
            rel: 'noreferrer',
            style: { marginTop: '12px', display: 'inline-block' },
          },
          i18n.__('Create a template', 'breefer'),
        ),
      ),
      value
        ? el(serverSideRender, {
            block: 'breefer/template',
            attributes: props.attributes,
          })
        : null,
    );
  }

  blocks.registerBlockType('breefer/template', {
    apiVersion: 2,
    title: config.label,
    description: i18n.__('Render a Breefer template as static HTML.', 'breefer'),
    icon: 'layout',
    category: 'design',
    attributes: {
      id: {
        type: 'number',
        default: 0,
      },
    },
    edit: edit,
    save: function () {
      return null;
    },
  });

  /* Sidebar entry so the builder is discoverable in the block editor. */
  var pluginApi = window.wp.plugins;
  var editPost = window.wp.editPost;
  var data = window.wp.data;

  if (pluginApi && editPost && data && editPost.PluginDocumentSettingPanel) {
    var Panel = editPost.PluginDocumentSettingPanel;

    var BreeferPanel = function () {
      var postId = data.select('core/editor').getCurrentPostId();
      var base = (window.breeferBlock && window.breeferBlock.editorUrlBase) || '';

      return el(
        Panel,
        {
          name: 'breefer-page-panel',
          title: i18n.__('Breefer', 'breefer'),
          className: 'breefer-page-panel',
        },
        el(
          'p',
          { style: { marginTop: 0 } },
          i18n.__(
            'Design this page on the Breefer canvas. Saving there enables Breefer output automatically.',
            'breefer',
          ),
        ),
        el(
          'a',
          {
            className: 'components-button is-primary',
            href: base + postId,
            target: '_blank',
            rel: 'noreferrer',
          },
          i18n.__('Open Breefer editor', 'breefer'),
        ),
      );
    };

    pluginApi.registerPlugin('breefer-page-panel', { render: BreeferPanel });
  }
})(
  window.wp.blocks,
  window.wp.element,
  window.wp.components,
  window.wp.serverSideRender,
  window.wp.i18n,
);
