{{--
  Cierre de sesión por inactividad (en el navegador). Incluir UNA vez por layout autenticado: <x-idle-timeout />

  - Cuenta el tiempo sin que la persona mueva el mouse, escriba, haga scroll o toque la pantalla.
  - Comparte la última actividad entre pestañas (localStorage): usar otra pestaña también cuenta.
  - Mientras hay actividad, avisa al servidor cada ~60 s (GET /session/ping) para que su reloj coincida.
  - 1 minuto antes de cerrar muestra un aviso con cuenta regresiva; cualquier actividad lo cancela.
  - Al llegar al límite cierra la sesión y lleva al login.
  - El servidor aplica el mismo límite (EnforceIdleTimeout): esto solo evita que la pantalla se quede abierta.
  - En el editor de OnlyOffice (un iframe) el navegador no ve el teclado: la página del editor llama
    HelperIdle.mark() cuando el documento cambia.
--}}
@php($idleMinutes = \App\Http\Middleware\EnforceIdleTimeout::limitMinutes())

<div id="idle-warning" hidden role="alertdialog" aria-live="assertive" aria-label="Sesión por caducar"
     style="position:fixed;right:1rem;bottom:1rem;z-index:110;max-width:22rem;border-radius:.75rem;border:1px solid #fcd34d;background:#fffbeb;padding:1rem 1.25rem;box-shadow:0 10px 30px -10px rgba(0,0,0,.35);font-size:.8125rem;color:#78350f">
  <p style="margin:0;font-weight:600">Tu sesión está por cerrarse</p>
  <p style="margin:.25rem 0 .75rem">Por inactividad se cerrará en <strong id="idle-count">60</strong> s.</p>
  <button type="button" id="idle-stay" data-click-guard-ignore
          style="cursor:pointer;border:0;border-radius:.5rem;background:#6d3ce8;padding:.45rem .9rem;font-size:.8125rem;font-weight:600;color:#fff">
    Seguir conectado
  </button>
</div>

<script>
(function () {
  if (window.HelperIdle) return;

  var LIMIT = {{ $idleMinutes }} * 60 * 1000;   // cierre
  var WARN = Math.min(60 * 1000, LIMIT / 2);    // aviso antes del cierre
  var PING_EVERY = 60 * 1000;                   // aviso al servidor mientras hay actividad
  var KEY = 'helper:last-activity';
  var PING_URL = @json(route('session.ping'));
  var LOGOUT_URL = @json(route('logout'));
  var LOGIN_URL = @json(route('login'));
  var TOKEN = @json(csrf_token());

  var warning = document.getElementById('idle-warning');
  var counter = document.getElementById('idle-count');
  var lastMark = 0;
  var lastPing = Date.now();
  var closing = false;

  function readLast() {
    try { return parseInt(localStorage.getItem(KEY), 10) || 0; } catch (e) { return 0; }
  }

  function writeLast(t) {
    try { localStorage.setItem(KEY, String(t)); } catch (e) { /* modo privado: solo cuenta esta pestaña */ }
    memory = t;
  }

  var memory = Date.now();
  writeLast(Date.now());

  function lastActivity() { return Math.max(readLast(), memory); }

  function ping() {
    lastPing = Date.now();
    fetch(PING_URL, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(function (r) {
      if (r.status === 401 || r.status === 419) expire();
    }).catch(function () { /* sin red: el servidor decide al volver */ });
  }

  // Marca actividad (limitado a 1 vez por segundo para no escribir en cada movimiento del mouse).
  function mark() {
    var now = Date.now();
    if (closing || now - lastMark < 1000) return;
    lastMark = now;
    writeLast(now);
    hideWarning();
    if (now - lastPing >= PING_EVERY) ping();
  }

  function showWarning(seconds) {
    counter.textContent = seconds;
    if (warning.hidden) warning.hidden = false;
  }

  function hideWarning() {
    if (!warning.hidden) warning.hidden = true;
  }

  function expire() {
    if (closing) return;
    closing = true;
    hideWarning();

    var body = new FormData();
    body.append('_token', TOKEN);
    body.append('reason', 'idle');

    var go = function () { window.location.replace(LOGIN_URL + '?expired=1'); };

    fetch(LOGOUT_URL, {
      method: 'POST',
      body: body,
      credentials: 'same-origin',
      keepalive: true,
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(go, go);
  }

  function tick() {
    if (closing) return;

    var idle = Date.now() - lastActivity();

    if (idle >= LIMIT) { expire(); return; }

    if (idle >= LIMIT - WARN) showWarning(Math.max(1, Math.ceil((LIMIT - idle) / 1000)));
    else hideWarning();
  }

  ['mousemove', 'mousedown', 'keydown', 'wheel', 'scroll', 'touchstart', 'pointerdown'].forEach(function (name) {
    window.addEventListener(name, mark, { passive: true, capture: true });
  });

  // Entrar al editor de OnlyOffice (iframe): el foco pasa al iframe y la ventana pierde el foco.
  window.addEventListener('blur', function () {
    setTimeout(function () {
      if (document.activeElement && document.activeElement.tagName === 'IFRAME') mark();
    }, 0);
  });

  document.getElementById('idle-stay').addEventListener('click', function () { lastMark = 0; mark(); ping(); });

  // Al volver a la pestaña se revisa al momento (los temporizadores de pestañas ocultas se frenan).
  document.addEventListener('visibilitychange', function () { if (!document.hidden) tick(); });

  setInterval(tick, 1000);

  window.HelperIdle = { mark: mark };
})();
</script>
