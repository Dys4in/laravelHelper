const IGNORED = ['_token', '_method', '_modal'];

function fieldsOf(form) {
  return Array.from(form.querySelectorAll('input[name], select[name], textarea[name]')).filter(
    (field) => !IGNORED.includes(field.name) && !['button', 'submit', 'reset'].includes(field.type)
  );
}

function valueOf(field) {
  if (field instanceof HTMLInputElement && (field.type === 'checkbox' || field.type === 'radio')) {
    return field.checked ? '1' : '0';
  }
  if (field instanceof HTMLInputElement && field.type === 'file') {
    return field.files && field.files.length ? 'file' : '';
  }
  if (field instanceof HTMLSelectElement && field.multiple) {
    return Array.from(field.selectedOptions).map((option) => option.value).sort().join('|');
  }
  return field.value;
}

function snapshot(form) {
  return fieldsOf(form).map(valueOf).join('\u0000');
}

function setup(form) {
  const buttons = Array.from(form.querySelectorAll('button[type="submit"], input[type="submit"]'));
  if (!buttons.length || form.hasAttribute('data-confirm') || !fieldsOf(form).length) return;

  const initial = snapshot(form);
  const hasErrors = Boolean(form.querySelector('[aria-invalid="true"], .hp-error'));

  const sync = () => {
    const changed = snapshot(form) !== initial;
    buttons.forEach((button) => {
      button.disabled = !changed && !hasErrors;
      button.setAttribute('aria-disabled', String(button.disabled));
      if (button.disabled) button.title = 'No hay cambios para guardar';
      else button.removeAttribute('title');
    });
  };

  form.addEventListener('input', sync);
  form.addEventListener('change', sync);
  form.addEventListener('reset', () => requestAnimationFrame(sync));

  const modal = form.closest('[data-app-modal], dialog');
  if (modal) {
    modal.addEventListener('modal:close', () => requestAnimationFrame(sync));
    modal.addEventListener('close', () => requestAnimationFrame(sync));
  }

  sync();
}

function init() {
  document.querySelectorAll('form').forEach((form) => {
    const method = form.querySelector('input[name="_method"]');
    const isEdit = Boolean(method && ['PUT', 'PATCH'].includes(String(method.value).toUpperCase()));
    if (isEdit || form.hasAttribute('data-no-changes-guard')) setup(form);
  });
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
else init();
