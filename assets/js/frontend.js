/**
 * Breefer frontend islands.
 *
 * Everything here is progressive enhancement. Without it, the first tab is
 * visible, accordions use the native <details> element, counters show their
 * final value and animated elements stay visible.
 */
(function () {
  'use strict';

  var config = window.breeferFront || {};
  var text = config.i18n || {};
  var reduceMotion =
    window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var hasObserver = 'IntersectionObserver' in window;

  function each(selector, callback) {
    Array.prototype.forEach.call(document.querySelectorAll(selector), callback);
  }

  /* ------------------------------------------------------------- Tabs */

  function activate(root, index) {
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));
    var panels = Array.prototype.slice.call(root.querySelectorAll('[role="tabpanel"]'));

    tabs.forEach(function (tab, tabIndex) {
      var selected = tabIndex === index;
      tab.setAttribute('aria-selected', selected ? 'true' : 'false');
      tab.setAttribute('tabindex', selected ? '0' : '-1');
      tab.classList.toggle('is-active', selected);
    });

    panels.forEach(function (panel, panelIndex) {
      var selected = panelIndex === index;
      panel.hidden = !selected;
      panel.classList.toggle('is-active', selected);
    });
  }

  each('[data-breefer-tabs]', function (root) {
    var tabs = Array.prototype.slice.call(root.querySelectorAll('[role="tab"]'));

    if (!tabs.length) {
      return;
    }

    tabs.forEach(function (tab, index) {
      tab.addEventListener('click', function (event) {
        event.preventDefault();
        activate(root, index);
      });

      tab.addEventListener('keydown', function (event) {
        var next = null;

        if (event.key === 'ArrowRight') {
          next = (index + 1) % tabs.length;
        } else if (event.key === 'ArrowLeft') {
          next = (index - 1 + tabs.length) % tabs.length;
        } else if (event.key === 'Home') {
          next = 0;
        } else if (event.key === 'End') {
          next = tabs.length - 1;
        }

        if (next === null) {
          return;
        }

        event.preventDefault();
        activate(root, next);
        tabs[next].focus();
      });
    });
  });

  /* ---------------------------------------------------- Reveal on scroll */

  function observe(elements, callback, options) {
    if (!elements.length) {
      return;
    }

    if (!hasObserver) {
      elements.forEach(callback);
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          observer.unobserve(entry.target);
          callback(entry.target);
        }
      });
    }, options || { threshold: 0.15 });

    elements.forEach(function (element) {
      observer.observe(element);
    });
  }

  observe(Array.prototype.slice.call(document.querySelectorAll('.br-anim')), function (element) {
    element.classList.add('is-visible');
  });

  /* ---------------------------------------------------------- Counters */

  function formatNumber(value, decimals, separator) {
    var fixed = value.toFixed(decimals);

    if (!separator) {
      return fixed;
    }

    var parts = fixed.split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');

    return parts.join('.');
  }

  observe(
    Array.prototype.slice.call(document.querySelectorAll('[data-breefer-counter]')),
    function (root) {
      var target = root.querySelector('.br-counter-value');
      var start = parseFloat(root.getAttribute('data-start')) || 0;
      var end = parseFloat(root.getAttribute('data-end')) || 0;
      var duration = parseInt(root.getAttribute('data-duration'), 10) || 2000;
      var decimals = parseInt(root.getAttribute('data-decimals'), 10) || 0;
      var separator = root.getAttribute('data-separator') === '1';
      var began = null;

      if (!target || reduceMotion) {
        return;
      }

      function frame(now) {
        if (began === null) {
          began = now;
        }

        var progress = Math.min(1, (now - began) / duration);
        var eased = 1 - Math.pow(1 - progress, 3);

        target.textContent = formatNumber(start + (end - start) * eased, decimals, separator);

        if (progress < 1) {
          window.requestAnimationFrame(frame);
        }
      }

      target.textContent = formatNumber(start, decimals, separator);
      window.requestAnimationFrame(frame);
    },
  );

  /* --------------------------------------------------------- Countdown */

  each('[data-breefer-countdown]', function (root) {
    var due = parseInt(root.getAttribute('data-due'), 10) * 1000;
    var expired = root.getAttribute('data-expired') || '';
    var timer = null;

    function pad(value) {
      return value < 10 ? '0' + value : String(value);
    }

    function set(unit, value) {
      var node = root.querySelector('[data-unit="' + unit + '"] .br-countdown-number');

      if (node) {
        node.textContent = pad(value);
      }
    }

    function tick() {
      if (Date.now() >= due) {
        root.classList.add('is-expired');
        root.textContent = expired;
        window.clearInterval(timer);

        return;
      }

      var remaining = Math.floor((due - Date.now()) / 1000);

      set('days', Math.floor(remaining / 86400));
      set('hours', Math.floor((remaining % 86400) / 3600));
      set('minutes', Math.floor((remaining % 3600) / 60));
      set('seconds', remaining % 60);
    }

    timer = window.setInterval(tick, 1000);
    tick();
  });

  /* ------------------------------------------------- Dismissible alerts */

  document.addEventListener('click', function (event) {
    var button = event.target.closest ? event.target.closest('[data-breefer-dismiss]') : null;
    var alert = button ? button.parentNode : null;

    if (alert && alert.parentNode) {
      alert.parentNode.removeChild(alert);
    }
  });

  /* ----------------------------------------------------- Menu toggle */

  each('[data-breefer-nav]', function (nav) {
    var toggle = nav.querySelector('.br-nav-toggle');

    if (!toggle) {
      return;
    }

    toggle.addEventListener('click', function () {
      var open = nav.classList.toggle('is-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });

  /* ------------------------------------------------------ Contact forms */

  each('[data-breefer-form]', function (form) {
    var status = form.querySelector('.br-form-status');

    function show(message, kind) {
      if (!status) {
        return;
      }

      status.textContent = message;
      status.className = 'br-form-status ' + (kind ? 'is-' + kind : '');
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();

      if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
        return;
      }

      if (!config.restUrl) {
        show(text.error || 'Error', 'error');
        return;
      }

      var data = {};

      new FormData(form).forEach(function (value, key) {
        data[key] = value;
      });

      form.classList.add('is-sending');
      show(text.sending || '', '');

      window
        .fetch(config.restUrl + 'forms/submit', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data),
        })
        .then(function (response) {
          return response.json().then(function (body) {
            return { ok: response.ok, body: body };
          });
        })
        .then(function (result) {
          form.classList.remove('is-sending');

          if (result.ok) {
            form.reset();
            show((result.body && result.body.message) || '', 'success');
          } else {
            show((result.body && result.body.message) || text.error || 'Error', 'error');
          }
        })
        .catch(function () {
          form.classList.remove('is-sending');
          show(text.error || 'Error', 'error');
        });
    });
  });

  /* ------------------------------------------- Sticky add-to-cart bar */

  each('[data-breefer-sticky]', function (bar) {
    var anchor = document.querySelector('form.cart');
    var button = bar.querySelector('.br-sticky-cart-button');
    var simple = bar.getAttribute('data-simple') === '1';

    if (!anchor) {
      return;
    }

    bar.hidden = false;

    if (hasObserver) {
      new IntersectionObserver(function (entries) {
        var entry = entries[0];

        // Show once the real button has scrolled above the viewport.
        bar.classList.toggle('is-visible', !entry.isIntersecting && entry.boundingClientRect.top < 0);
      }).observe(anchor);
    }

    if (button) {
      button.addEventListener('click', function () {
        var submit = anchor.querySelector('button[type="submit"], .single_add_to_cart_button');

        if (simple && submit) {
          submit.click();
        } else {
          anchor.scrollIntoView({ behavior: reduceMotion ? 'auto' : 'smooth', block: 'center' });
        }
      });
    }
  });
})();
