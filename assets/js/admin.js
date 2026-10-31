/**
 * Breefer admin shell behaviour.
 *
 * Vanilla only: no jQuery, no framework, no build step.
 */
(function () {
  'use strict';

  var root = document.querySelector('.breefer-admin');

  if (!root) {
    return;
  }

  var labels = window.breeferAdmin || {};

  function copyText(text) {
    if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
      return navigator.clipboard.writeText(text);
    }

    return new Promise(function (resolve, reject) {
      var field = document.createElement('textarea');

      field.value = text;
      field.setAttribute('readonly', '');
      field.style.position = 'fixed';
      field.style.top = '-1000px';
      field.style.opacity = '0';
      document.body.appendChild(field);
      field.select();

      try {
        var copied = document.execCommand('copy');

        if (!copied) {
          reject(new Error('copy failed'));
          return;
        }

        resolve();
      } catch (error) {
        reject(error);
      } finally {
        field.remove();
      }
    });
  }

  root.querySelectorAll('[data-breefer-copy]').forEach(function (button) {
    button.addEventListener('click', function () {
      var selector = button.getAttribute('data-breefer-copy');
      var target = selector ? document.querySelector(selector) : null;
      var content = target ? (target.textContent || '').trim() : '';

      if (!content) {
        return;
      }

      var original = button.getAttribute('data-breefer-label') || button.textContent || '';

      copyText(content)
        .then(function () {
          button.textContent = labels.copied || 'Copied';

          window.setTimeout(function () {
            button.textContent = original;
          }, 1600);
        })
        .catch(function () {
          button.textContent = original;
        });
    });
  });

  var paletteInputs = root.querySelectorAll('input[name="breefer_settings[appearance][palette]"]');

  function syncPalettes() {
    root.querySelectorAll('.breefer-palette').forEach(function (card) {
      var input = card.querySelector('input[type="radio"]');
      card.classList.toggle('is-selected', Boolean(input && input.checked));
    });
  }

  paletteInputs.forEach(function (input) {
    input.addEventListener('change', syncPalettes);
  });

  syncPalettes();

  var nav = root.querySelector('[data-breefer-settings-nav]');

  if (!nav || !('IntersectionObserver' in window)) {
    return;
  }

  var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]'));
  var sections = links
    .map(function (link) {
      var href = link.getAttribute('href');

      return href ? document.querySelector(href) : null;
    })
    .filter(Boolean);

  if (!sections.length) {
    return;
  }

  var setActive = function (id) {
    links.forEach(function (link) {
      var active = link.getAttribute('href') === '#' + id;

      link.classList.toggle('is-active', active);

      if (active) {
        link.setAttribute('aria-current', 'true');
      } else {
        link.removeAttribute('aria-current');
      }
    });
  };

  var observer = new IntersectionObserver(
    function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          setActive(entry.target.id);
        }
      });
    },
    { rootMargin: '-20% 0px -70% 0px' },
  );

  sections.forEach(function (section) {
    observer.observe(section);
  });

  links.forEach(function (link) {
    link.addEventListener('click', function () {
      var href = link.getAttribute('href');

      if (href && href.length > 1) {
        setActive(href.slice(1));
      }
    });
  });
})();
