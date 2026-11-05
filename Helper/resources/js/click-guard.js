const CLICK_LOCK_MS = 700;
const CLICKABLE = 'button, a[href], input[type="submit"], input[type="button"], input[type="reset"], [role="button"]';
const SUBMIT_BUTTONS = 'button[type="submit"], input[type="submit"], button:not([type])';

const closestClickable = (target) => (target instanceof Element ? target.closest(CLICKABLE) : null);

document.addEventListener('click', (event) => {
  const el = closestClickable(event.target);
  if (!el || el.hasAttribute('data-click-guard-ignore')) return;
  if (el.disabled || el.getAttribute('aria-disabled') === 'true') return;

  const now = Date.now();
  const last = Number(el.dataset.clickGuardAt || 0);

  if (now - last < CLICK_LOCK_MS) {
    event.preventDefault();
    event.stopImmediatePropagation();
    return;
  }

  el.dataset.clickGuardAt = String(now);
}, true);

const lockForm = (form) => {
  if (!(form instanceof HTMLFormElement) || form.dataset.clickGuardSubmitting === '1') return;

  form.dataset.clickGuardSubmitting = '1';
  form.setAttribute('aria-busy', 'true');

  form.querySelectorAll(SUBMIT_BUTTONS).forEach((button) => {
    button.disabled = true;
    button.dataset.clickGuardDisabled = '1';
  });
};

const unlockForm = (form) => {
  if (!(form instanceof HTMLFormElement)) return;

  delete form.dataset.clickGuardSubmitting;
  form.removeAttribute('aria-busy');

  form.querySelectorAll('[data-click-guard-disabled="1"]').forEach((button) => {
    button.disabled = false;
    delete button.dataset.clickGuardDisabled;
  });
};

document.addEventListener('submit', (event) => {
  const form = event.target;
  if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-confirm')) return;

  if (form.dataset.clickGuardSubmitting === '1') {
    event.preventDefault();
    event.stopImmediatePropagation();
    return;
  }

  lockForm(form);
}, true);

window.addEventListener('pageshow', () => {
  document.querySelectorAll('form[data-click-guard-submitting="1"]').forEach(unlockForm);
  document.querySelectorAll('[data-click-guard-at]').forEach((el) => delete el.dataset.clickGuardAt);
});

window.HelperClickGuard = { lockForm, unlockForm };
