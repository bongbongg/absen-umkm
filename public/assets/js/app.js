/* =============================================================
   Monitoring Absensi & Tugas Karyawan UMKM
   UI helpers — vanilla JavaScript, aman jika elemen tidak ada
   ============================================================= */

(function () {
  'use strict';

  /* ----------------------------- helpers ---------------------------- */

  function each(selector, callback, root) {
    var nodes = (root || document).querySelectorAll(selector);
    Array.prototype.forEach.call(nodes, callback);
  }

  function byId(id) {
    return id ? document.getElementById(id) : null;
  }

  function safe(name, fn) {
    try {
      fn();
    } catch (err) {
      if (window.console && typeof window.console.warn === 'function') {
        window.console.warn('[app:' + name + ']', err);
      }
    }
  }

  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  }

  ready(function () {
    safe('sidebar', initSidebar);
    safe('confirm', initConfirm);
    safe('autodismiss', initAutoDismiss);
    safe('range', initRangeSync);
    safe('file', initFileLabels);
    safe('selectAll', initSelectAll);
    safe('autoSubmit', initAutoSubmit);
  });

  /* --------------------------- 1. sidebar --------------------------- */

  function initSidebar() {
    var body = document.body;
    var menu = document.querySelector('.topbar__menu');
    var overlay = document.querySelector('.sidebar-overlay');

    function syncAria() {
      if (menu && menu.hasAttribute('aria-expanded')) {
        menu.setAttribute('aria-expanded', body.classList.contains('nav-open') ? 'true' : 'false');
      }
    }

    function closeNav() {
      body.classList.remove('nav-open');
      syncAria();
    }

    function openNav() {
      body.classList.add('nav-open');
      syncAria();
    }

    if (menu) {
      menu.addEventListener('click', function (event) {
        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }
        if (body.classList.contains('nav-open')) {
          closeNav();
        } else {
          openNav();
        }
      });
      syncAria();
    }

    if (overlay) {
      overlay.addEventListener('click', closeNav);
    }

    each('[data-nav-close]', function (element) {
      element.addEventListener('click', closeNav);
    });

    document.addEventListener('keydown', function (event) {
      var key = event && (event.key || event.keyCode);
      if (key === 'Escape' || key === 'Esc' || key === 27) {
        closeNav();
      }
    });
  }

  /* ------------------------- 2. data-confirm ------------------------ */

  /* Popup konfirmasi custom menggantikan window.confirm bawaan browser
     supaya tampilannya konsisten dengan aplikasi (dan rapi di wireframe). */
  function buildConfirmModal() {
    if (!document.createElement || !document.body || !document.body.appendChild) {
      return null;
    }

    var root = document.createElement('div');
    root.className = 'app-modal';
    root.id = 'appConfirmModal';
    root.hidden = true;
    root.innerHTML = ''
      + '<div class="app-modal__backdrop" data-app-modal-close></div>'
      + '<div class="app-modal__card" role="dialog" aria-modal="true" aria-labelledby="appConfirmTitle">'
      +   '<div class="app-modal__icon" aria-hidden="true">?</div>'
      +   '<h2 class="app-modal__title" id="appConfirmTitle">Konfirmasi</h2>'
      +   '<p class="app-modal__text"></p>'
      +   '<div class="app-modal__actions">'
      +     '<button type="button" class="btn btn--secondary" data-app-modal-close>Batal</button>'
      +     '<button type="button" class="btn btn--primary app-modal__yes">Ya, lanjutkan</button>'
      +   '</div>'
      + '</div>';

    document.body.appendChild(root);

    return {
      root: root,
      text: root.querySelector('.app-modal__text'),
      yes: root.querySelector('.app-modal__yes'),
      closers: root.querySelectorAll('[data-app-modal-close]')
    };
  }

  function initConfirm() {
    var triggers = document.querySelectorAll('[data-confirm]');
    if (!triggers.length) {
      return;
    }

    var modal = buildConfirmModal();
    var pendingAction = null;

    function closeConfirm() {
      pendingAction = null;
      if (modal && modal.root) {
        modal.root.hidden = true;
      }
      document.body.classList.remove('modal-open');
    }

    function openConfirm(message, onYes) {
      /* Fallback bila DOM tidak mendukung modal custom. */
      if (!modal || !modal.text || !modal.yes) {
        if (window.confirm(message)) {
          onYes();
        }
        return;
      }

      pendingAction = onYes;
      modal.text.textContent = message;
      modal.root.hidden = false;
      document.body.classList.add('modal-open');
      if (modal.yes.focus) {
        modal.yes.focus();
      }
    }

    if (modal) {
      if (modal.yes) {
        modal.yes.addEventListener('click', function () {
          var action = pendingAction;
          closeConfirm();
          if (typeof action === 'function') {
            action();
          }
        });
      }
      Array.prototype.forEach.call(modal.closers, function (closer) {
        closer.addEventListener('click', closeConfirm);
      });
      document.addEventListener('keydown', function (event) {
        var key = event && (event.key || event.keyCode);
        if ((key === 'Escape' || key === 'Esc' || key === 27)
            && modal.root && !modal.root.hidden) {
          closeConfirm();
        }
      });
    }

    var forms = [];

    Array.prototype.forEach.call(triggers, function (trigger) {
      var form = trigger.closest ? trigger.closest('form') : null;
      if (form && forms.indexOf(form) === -1) {
        forms.push(form);
      }
    });

    /* Satu listener per form → tidak ada konfirmasi dobel. */
    forms.forEach(function (form) {
      if (form.__appConfirmBound) {
        return;
      }
      form.__appConfirmBound = true;

      form.addEventListener('submit', function (event) {
        if (form.__appConfirmApproved) {
          form.__appConfirmApproved = false;
          return;
        }

        var message = form.getAttribute('data-confirm') || '';
        var child = form.querySelector('[data-confirm]');
        if (!message && child) {
          message = child.getAttribute('data-confirm') || '';
        }
        if (!message) {
          return;
        }

        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }
        if (event && typeof event.stopImmediatePropagation === 'function') {
          event.stopImmediatePropagation();
        }

        openConfirm(message, function () {
          form.__appConfirmApproved = true;
          if (typeof form.requestSubmit === 'function') {
            try {
              form.requestSubmit();
              return;
            } catch (err) {
              /* Fallback ke submit() di bawah. */
            }
          }
          if (typeof form.submit === 'function') {
            form.submit();
          }
        });
      });
    });

    /* Trigger di luar form (link / tombol non-submit) tetap dikonfirmasi. */
    Array.prototype.forEach.call(triggers, function (trigger) {
      if (trigger.closest && trigger.closest('form')) {
        return;
      }
      if (trigger.__appConfirmBound) {
        return;
      }
      if (trigger.tagName !== 'A' && trigger.tagName !== 'BUTTON') {
        return;
      }
      trigger.__appConfirmBound = true;

      trigger.addEventListener('click', function (event) {
        if (trigger.__appConfirmApproved) {
          trigger.__appConfirmApproved = false;
          return;
        }

        var message = trigger.getAttribute('data-confirm') || '';
        if (!message) {
          return;
        }
        if (event && typeof event.preventDefault === 'function') {
          event.preventDefault();
        }

        openConfirm(message, function () {
          trigger.__appConfirmApproved = true;
          if (trigger.tagName === 'A' && trigger.href) {
            window.location.href = trigger.href;
            return;
          }
          if (typeof trigger.click === 'function') {
            trigger.click();
          }
        });
      });
    });
  }

  /* ---------------------- 3. auto-dismiss alert --------------------- */

  function initAutoDismiss() {
    each('.alert[data-autodismiss]', function (alert) {
      if (alert.__appAutoDismissBound) {
        return;
      }
      alert.__appAutoDismissBound = true;

      var raw = parseInt(alert.getAttribute('data-autodismiss'), 10);
      var delay = isFinite(raw) && raw > 0 ? raw : 5000;

      window.setTimeout(function () {
        if (!alert.parentNode) {
          return;
        }
        alert.classList.add('is-hiding');

        window.setTimeout(function () {
          if (alert.parentNode) {
            alert.parentNode.removeChild(alert);
          }
        }, 300);
      }, delay);
    });
  }

  /* -------------------------- 4. range sync ------------------------- */

  function initRangeSync() {
    function clamp(value) {
      var number = parseFloat(value);
      if (!isFinite(number)) {
        number = 0;
      }
      if (number < 0) {
        number = 0;
      }
      if (number > 100) {
        number = 100;
      }
      return number;
    }

    function setValue(element, value) {
      var text = String(value);
      if (element.tagName === 'INPUT' || element.tagName === 'SELECT' || element.tagName === 'TEXTAREA') {
        element.value = text;
      } else {
        element.textContent = text;
      }
    }

    function nearest(scope, selector, skip) {
      if (!scope || !scope.querySelector) {
        return null;
      }
      var found = scope.querySelector(selector);
      return found && found !== skip ? found : null;
    }

    function bind(range, target) {
      if (!range || !target || range.__appRangeTarget === target) {
        return;
      }
      range.__appRangeTarget = target;
      target.__appRangeSource = range;

      function rangeToTarget() {
        setValue(target, clamp(range.value));
      }

      function targetToRange() {
        setValue(range, clamp(target.value));
        range.value = String(clamp(range.value));
      }

      range.addEventListener('input', rangeToTarget);
      range.addEventListener('change', rangeToTarget);
      target.addEventListener('input', targetToRange);
      target.addEventListener('change', targetToRange);

      /* Nilai awal mengikuti range. */
      rangeToTarget();
    }

    /* Bentuk A: range memuat id target pada data-range-sync. */
    each('[data-range-sync]', function (range) {
      var target = byId(range.getAttribute('data-range-sync'));
      if (!target) {
        var scope = range.closest ? (range.closest('form') || range.parentNode) : range.parentNode;
        target = nearest(scope, '[data-range-target]', range);
      }
      bind(range, target);
    });

    /* Bentuk B: input angka memuat id range pada data-range-target. */
    each('[data-range-target]', function (target) {
      var range = byId(target.getAttribute('data-range-target'));
      if (!range) {
        var scope = target.closest ? (target.closest('form') || target.parentNode) : target.parentNode;
        range = nearest(scope, '[data-range-sync]', target);
      }
      bind(range, target);
    });
  }

  /* -------------------------- 5. file label ------------------------- */

  function initFileLabels() {
    each('input[type="file"][data-file-label]', function (input) {
      var label = byId(input.getAttribute('data-file-label'));
      if (!label) {
        return;
      }

      var placeholder = label.textContent;

      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        label.textContent = file ? file.name : placeholder;
      });
    });
  }

  /* ------------------------- 6. select all -------------------------- */

  function initSelectAll() {
    each('[data-select-all]', function (master) {
      var scope = master.closest
        ? (master.closest('form') || master.closest('table') || master.closest('.table-wrap') || document)
        : document;

      var boxes = Array.prototype.filter.call(
        scope.querySelectorAll('.js-check'),
        function (box) {
          return box !== master && box.type === 'checkbox';
        }
      );

      if (!boxes.length) {
        return;
      }

      function refreshMaster() {
        var checkedCount = 0;
        boxes.forEach(function (box) {
          if (box.checked) {
            checkedCount += 1;
          }
        });
        var allChecked = checkedCount === boxes.length;
        master.checked = allChecked;
        if (typeof master.indeterminate === 'boolean') {
          master.indeterminate = !allChecked && checkedCount > 0;
        }
      }

      master.addEventListener('change', function () {
        boxes.forEach(function (box) {
          box.checked = master.checked;
          if (typeof window.Event === 'function') {
            box.dispatchEvent(new window.Event('change', { bubbles: true }));
          }
        });
        refreshMaster();
      });

      boxes.forEach(function (box) {
        box.addEventListener('change', refreshMaster);
      });

      refreshMaster();
    });
  }

  /* ----------------------- 7. auto-submit filter -------------------- */

  function initAutoSubmit() {
    each('.filters select[data-auto-submit]', function (select) {
      if (select.__appAutoSubmitBound) {
        return;
      }
      select.__appAutoSubmitBound = true;

      select.addEventListener('change', function () {
        var form = select.closest ? select.closest('form') : null;
        if (!form) {
          return;
        }

        if (typeof form.requestSubmit === 'function') {
          try {
            form.requestSubmit();
            return;
          } catch (err) {
            /* Fallback ke submit() di bawah. */
          }
        }

        if (typeof form.submit === 'function') {
          form.submit();
        }
      });
    });
  }
})();
