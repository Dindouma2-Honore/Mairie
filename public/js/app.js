/* Mairie360 — comportement du navigateur : menu, mode hors ligne, écran d'encaissement. */
(() => {
  'use strict';
  const body = document.body;
  const D = body.dataset;
  const USER = D.user || '';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fcfa = (n) => new Intl.NumberFormat('fr-FR').format(Math.round(n || 0)).replace(/[  ]/g, ' ') + ' FCFA';
  const plural = (n, one, many) => (n > 1 ? many : one);
  const uuid = () => (crypto.randomUUID ? crypto.randomUUID()
    : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 3 | 8)).toString(16); }));

  function toast(msg, kind = 'ok', ms = 5500) {
    const box = $('#toasts'); if (!box) return;
    const t = document.createElement('div'); t.className = 'toast ' + kind; t.textContent = msg;
    box.appendChild(t); setTimeout(() => t.remove(), ms);
  }

  /* ---------- Menu : un seul accordéon ouvert, menu mobile ---------- */
  const nav = $('#nav');
  if (nav) nav.addEventListener('toggle', (e) => {
    if (e.target.matches('details') && e.target.open) $$('details[open]', nav).forEach((d) => { if (d !== e.target) d.open = false; });
  }, true);
  const burger = $('#burger');
  const setNav = (open) => { body.classList.toggle('nav-open', open); if (burger) burger.setAttribute('aria-expanded', String(open)); };
  if (burger) burger.addEventListener('click', () => setNav(!body.classList.contains('nav-open')));
  document.addEventListener('click', (e) => {
    if (body.classList.contains('nav-open') && !e.target.closest('#sidebar') && !e.target.closest('#burger')) setNav(false);
    const u = $('.user'); if (u && u.open && !u.contains(e.target)) u.open = false;
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { setNav(false); const u = $('.user'); if (u) u.open = false; } });

  /* ---------- Stockage local (IndexedDB) ---------- */
  let dbp;
  const idb = () => (dbp ||= new Promise((res, rej) => {
    const r = indexedDB.open('mairie360', 1);
    r.onupgradeneeded = () => {
      r.result.createObjectStore('queue', { keyPath: 'uuid' });
      r.result.createObjectStore('blocks', { keyPath: 'id', autoIncrement: true });
    };
    r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error);
  }));
  const req = (r) => new Promise((res, rej) => { r.onsuccess = () => res(r.result); r.onerror = () => rej(r.error); });
  const store = async (name, mode, fn) => {
    const db = await idb();
    return new Promise((res, rej) => {
      const t = db.transaction(name, mode); let out;
      Promise.resolve(fn(t.objectStore(name))).then((v) => { out = v; });
      t.oncomplete = () => res(out); t.onerror = () => rej(t.error); t.onabort = () => rej(t.error);
    });
  };
  const queue = {
    all: () => store('queue', 'readonly', (s) => req(s.getAll())),
    put: (i) => store('queue', 'readwrite', (s) => req(s.put(i))),
    del: (id) => store('queue', 'readwrite', (s) => req(s.delete(id))),
  };
  const blocks = {
    all: () => store('blocks', 'readonly', (s) => req(s.getAll())),
    put: (b) => store('blocks', 'readwrite', (s) => req(s.put(b))),
  };

  /** Prend le prochain numéro de reçu du lot local, de façon atomique (une seule transaction). */
  async function takeReceiptNumber() {
    const db = await idb();
    return new Promise((res, rej) => {
      const t = db.transaction('blocks', 'readwrite'); const s = t.objectStore('blocks'); let out = null;
      s.getAll().onsuccess = (e) => {
        const b = e.target.result.filter((x) => x.uid === USER && x.next <= x.end).sort((a, c) => a.id - c.id)[0];
        if (!b) return;
        out = { year: b.year, seq: b.next }; b.next += 1; s.put(b);
      };
      t.oncomplete = () => res(out); t.onerror = () => rej(t.error);
    });
  }
  async function remainingNumbers() {
    return (await blocks.all()).filter((b) => b.uid === USER).reduce((n, b) => n + Math.max(0, b.end - b.next + 1), 0);
  }
  const receiptNumber = (year, seq) => `REC-${year}-${String(seq).padStart(6, '0')}`;

  /* ---------- Réseau ---------- */
  const csrf = () => ($('meta[name=csrf-token]') || {}).content || '';
  async function refreshCsrf() {
    try {
      const r = await fetch(D.urlCsrf, { credentials: 'same-origin', headers: { Accept: 'application/json' } });
      if (r.ok) { const j = await r.json(); const m = $('meta[name=csrf-token]'); if (m) m.content = j.token; }
    } catch (_) { /* hors ligne */ }
  }
  async function api(method, url, data, retry = true) {
    const ctl = new AbortController(); const timer = setTimeout(() => ctl.abort(), 12000); let res;
    try {
      res = await fetch(url, {
        method, credentials: 'same-origin', signal: ctl.signal,
        headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        body: data === undefined ? undefined : JSON.stringify(data),
      });
    } catch (_) { throw { network: true }; } finally { clearTimeout(timer); }
    if (res.status === 419 && retry) { await refreshCsrf(); return api(method, url, data, false); }
    if (res.status === 401 || (res.redirected && /connexion/.test(res.url))) throw { auth: true };
    if (!res.ok) { let b = null; try { b = await res.json(); } catch (_) { /* vide */ } throw { http: res.status, body: b }; }
    return res.json();
  }

  async function refillBlocks() {
    if (!USER || D.collector !== '1' || !navigator.onLine) return;
    if ((await remainingNumbers()) > Number(D.blockLow || 15)) return;
    try { const b = await api('POST', D.urlBlocks, {}); await blocks.put({ uid: USER, year: b.year, next: b.start, end: b.end }); } catch (_) { /* réessai plus tard */ }
  }

  /* ---------- Synchronisation ---------- */
  let syncing = false;
  async function syncNow(quiet = false) {
    if (syncing || !USER || !navigator.onLine) return { skipped: true };
    syncing = true;
    try {
      const todo = (await queue.all()).filter((i) => i.uid === USER && i.status !== 'rejected')
        .sort((a, b) => a.created_at.localeCompare(b.created_at));
      let ok = 0, rejected = 0;
      for (let i = 0; i < todo.length; i += 50) {
        const chunk = todo.slice(i, i + 50);
        const out = await api('POST', D.urlSync, { items: chunk.map(({ uuid: id, type, payload, created_at }) => ({ uuid: id, type, payload, created_at })) });
        for (const r of out.results) {
          const item = chunk.find((c) => c.uuid === r.uuid); if (!item) continue;
          if (r.status === 'ok' || r.status === 'duplicate') { await queue.del(r.uuid); ok++; }
          else if (r.status === 'rejected') { item.status = 'rejected'; item.message = r.message; await queue.put(item); rejected++; }
          else { item.message = r.message; await queue.put(item); }
        }
      }
      if (!quiet) {
        if (ok) toast(`${ok} ${plural(ok, 'saisie envoyée', 'saisies envoyées')} au serveur.`, 'ok');
        if (rejected) toast(`${rejected} ${plural(rejected, 'saisie refusée', 'saisies refusées')} : à corriger dans « Synchronisation ».`, 'error', 9000);
      }
      return { ok, rejected };
    } catch (e) {
      if (e.auth || e.http === 419) toast('Session expirée : reconnectez-vous pour envoyer vos saisies en attente.', 'warn', 9000);
      return { error: e };
    } finally {
      syncing = false; await refreshBadge(); document.dispatchEvent(new CustomEvent('m360:queue'));
    }
  }

  /* ---------- Indicateur de connexion ---------- */
  async function refreshBadge() {
    const el = $('#conn'); if (!el) return;
    const waiting = USER ? (await queue.all()).filter((i) => i.uid === USER).length : 0;
    const on = navigator.onLine;
    el.classList.toggle('is-offline', !on);
    $('.conn-text', el).textContent = on ? 'En ligne' : 'Hors ligne';
    const c = $('#conn-count'); c.hidden = !waiting; c.textContent = waiting;
    el.title = waiting ? `${waiting} ${plural(waiting, 'saisie en attente', 'saisies en attente')} d’envoi` : (on ? 'Connecté' : 'Sans connexion : vos saisies sont gardées sur cet appareil');
  }
  function offlineBanner() {
    let b = $('#offline-banner');
    if (!navigator.onLine && USER) {
      if (!b) {
        b = document.createElement('div'); b.id = 'offline-banner'; b.className = 'alert warning'; b.setAttribute('role', 'status');
        b.innerHTML = '<div><b>Vous êtes hors ligne</b>Vous pouvez continuer à encaisser et à déclarer des naissances : les saisies sont gardées sur cet appareil et envoyées au retour de la connexion. Les listes affichées peuvent ne pas être à jour.</div>';
        const m = $('#main'); if (m) m.prepend(b);
      }
    } else if (b) b.remove();
  }

  /* ---------- Saisie : en ligne si possible, sinon sur l'appareil ---------- */
  const formValues = (f) => { const o = {}; new FormData(f).forEach((v, k) => { if (k !== '_token' && k !== '_method') o[k] = v; }); return o; };
  function formError(f, msg) {
    let a = $('.js-form-error', f);
    if (!a) { a = document.createElement('div'); a.className = 'alert error js-form-error'; a.setAttribute('role', 'alert'); f.prepend(a); }
    a.innerHTML = '<div></div>'; a.firstChild.textContent = msg; a.scrollIntoView({ block: 'center', behavior: 'smooth' });
  }
  let till = null;
  function describe(f, p) {
    if (f.dataset.offline === 'receipt.create' && till) return till.meta(p);
    if (f.dataset.offline === 'birth_act.draft') return { title: `Naissance : ${(p.child_last_name || '').toUpperCase()} ${p.child_first_names || ''}`.trim() };
    return { title: f.dataset.offline };
  }
  async function saveLocally(item, f) {
    if (item.type === 'receipt.create') {
      const n = await takeReceiptNumber();
      if (!n) { formError(f, 'Plus de numéro de reçu disponible hors ligne. Reconnectez-vous une fois à internet pour recevoir un nouveau lot.'); return false; }
      item.payload.number_year = String(n.year); item.payload.number_seq = String(n.seq);
      item.meta.number = receiptNumber(n.year, n.seq);
    }
    if (item.type === 'birth_act.draft') item.payload.offline = '1';
    await queue.put(item);
    return true;
  }
  document.addEventListener('submit', async (e) => {
    const f = e.target; if (!(f instanceof HTMLFormElement)) return;
    const type = f.dataset.offline;
    if (!type) {
      if (f.hasAttribute('data-logout') && navigator.serviceWorker && navigator.serviceWorker.controller) navigator.serviceWorker.controller.postMessage({ type: 'clear' });
      else if ((f.method || 'get').toLowerCase() !== 'get' && !navigator.onLine) { e.preventDefault(); toast('Cette action demande une connexion internet.', 'warn'); }
      return;
    }
    e.preventDefault();
    if (!f.reportValidity()) return;
    const btn = $('[type=submit]', f); if (btn) btn.disabled = true;
    const payload = formValues(f);
    const item = { uuid: (f.dataset.uuid ||= uuid()), uid: USER, type, payload, meta: describe(f, payload), created_at: new Date().toISOString(), status: 'pending' };
    try {
      if (navigator.onLine) {
        try {
          const out = await api('POST', D.urlSync, { items: [{ uuid: item.uuid, type, payload, created_at: item.created_at }] });
          const r = out.results[0];
          if (r.status === 'ok' || r.status === 'duplicate') { toast(r.message || 'Enregistré.', 'ok'); location.assign(r.url || D.urlQueue); return; }
          if (r.status === 'rejected') { formError(f, r.message); return; }
        } catch (err) {
          if (err.http && err.http !== 419) { formError(f, 'Le serveur a refusé la demande (code ' + err.http + ').'); return; }
          if (err.auth || err.http === 419) toast('Session expirée : la saisie est gardée, reconnectez-vous pour l’envoyer.', 'warn', 8000);
        }
      }
      if (await saveLocally(item, f)) {
        toast('Enregistré sur cet appareil. Envoi automatique au retour de la connexion.', 'warn', 7000);
        location.assign(D.urlQueue + '?saved=' + item.uuid);
      }
    } catch (err) {
      formError(f, 'Impossible d’enregistrer sur cet appareil : ' + (err && err.message ? err.message : 'stockage indisponible') + '.');
    } finally { if (btn) btn.disabled = false; }
  });

  /* ---------- Écran « Encaisser » ---------- */
  function initTill() {
    const f = $('#till'); if (!f) return;
    const data = JSON.parse($('#till-data').textContent);
    const el = (n) => $('#f-' + n);
    const cat = el('category_id'), tar = el('tariff_id'), qty = el('quantity'), amt = el('amount'), pm = el('payment_method'), ref = el('transaction_ref'), tp = el('taxpayer_id');
    const methods = data.methods;
    const cur = () => data.categories.find((c) => String(c.id) === cat.value);
    const tariff = () => { const c = cur(); return c && c.tariffs.find((t) => String(t.id) === tar.value); };
    const unit = () => { const t = tariff(); const c = cur(); return t ? t.amount : (c && c.custom ? Number(amt.value || 0) : 0); };
    const total = () => unit() * Math.max(1, Number(qty.value || 1));

    function buildTariffs() {
      const c = cur(); const keep = tar.value;
      tar.innerHTML = '<option value="">' + (c && c.tariffs.length ? 'Choisir un tarif…' : (c ? 'Aucun tarif en vigueur' : 'Choisissez d’abord une catégorie')) + '</option>'
        + (c ? c.tariffs.map((t) => `<option value="${t.id}">${esc(t.label)} — ${fcfa(t.amount)}</option>`).join('') : '');
      if ([...tar.options].some((o) => o.value === keep)) tar.value = keep;
      tar.required = !!c && !c.custom;
      $('#amount-wrap').hidden = !(c && c.custom);
      amt.required = !!c && c.custom && !tar.value;
    }
    function paint() {
      const c = cur(), t = tariff();
      $('#t-total').textContent = new Intl.NumberFormat('fr-FR').format(total()).replace(/[  ]/g, ' ');
      $('#t-cat').textContent = c ? c.name : '—';
      $('#t-tariff').textContent = t ? t.label : (c && c.custom && Number(amt.value) ? 'Montant libre' : '—');
      $('#t-qty').textContent = `${Math.max(1, Number(qty.value || 1))} × ${new Intl.NumberFormat('fr-FR').format(unit()).replace(/[  ]/g, ' ')}`;
      $('#t-pay').textContent = methods[pm.value] || '—';
      const needRef = pm.value && pm.value !== 'espece'; $('#ref-wrap').hidden = !needRef; ref.required = !!needRef;
      const m = $('#t-mode'); const on = navigator.onLine;
      m.className = 'mode ' + (on ? 'is-online' : 'is-offline');
      m.textContent = on ? 'Le reçu sera numéroté par le serveur.' : 'Hors ligne : le reçu prendra un numéro de votre lot local et sera confirmé à la reconnexion.';
    }
    cat.addEventListener('change', () => { tar.value = ''; buildTariffs(); paint(); });
    [tar, qty, amt, pm].forEach((x) => x.addEventListener('input', () => { if (x === tar) amt.required = !!cur() && cur().custom && !tar.value; paint(); }));
    tp.addEventListener('change', () => { const p = $('#f-payer_name'); p.disabled = !!tp.value; if (tp.value) p.value = ''; });
    window.addEventListener('online', paint); window.addEventListener('offline', paint);
    till = {
      meta(p) {
        const c = cur(), t = tariff(), who = tp.value ? (tp.options[tp.selectedIndex].text) : (p.payer_name || '');
        return { title: 'Reçu : ' + (c ? c.name : ''), category: c ? c.name : '', tariff: t ? t.label : 'Montant libre', qty: Number(p.quantity || 1), unit: unit(), total: total(), method: methods[p.payment_method] || '', payer: who };
      },
    };
    buildTariffs(); paint();
  }

  /* ---------- Page « Synchronisation » ---------- */
  const TYPES = { 'receipt.create': 'Reçu', 'birth_act.draft': 'Déclaration de naissance' };
  function printProvisional(item) {
    const m = item.meta || {}; const w = window.open('', '_blank'); if (!w) { toast('Autorisez les fenêtres pour imprimer.', 'warn'); return; }
    w.document.write(`<!doctype html><meta charset="utf-8"><title>${esc(m.number)}</title><body style="font:15px system-ui;max-width:420px;margin:24px auto">
      <h2 style="margin:0">${esc(D.mairie)}</h2><p style="margin:2px 0 14px"><b>REÇU PROVISOIRE</b> n° ${esc(m.number)}<br>
      <small>Saisi hors ligne le ${esc(new Date(item.created_at).toLocaleString('fr-FR'))}. Sera confirmé à la synchronisation.</small></p><hr>
      <p>${esc(m.category)}<br>${esc(m.tariff)} — ${esc(m.qty)} × ${esc(fcfa(m.unit))}</p>
      <p style="font-size:26px;margin:8px 0"><b>${esc(fcfa(m.total))}</b></p><p>Payeur : ${esc(m.payer || 'Non renseigné')}<br>Mode : ${esc(m.method)}</p><hr>
      <script>window.onload=()=>window.print()<\/script>`);
    w.document.close();
  }
  async function renderQueue() {
    const root = $('#queue-root'); if (!root) return;
    const all = (await queue.all()).sort((a, b) => a.created_at.localeCompare(b.created_at));
    const mine = all.filter((i) => i.uid === USER), other = all.length - mine.length;
    const rem = await remainingNumbers();
    const rows = mine.map((i) => {
      const rejected = i.status === 'rejected';
      return `<div class="q-item"><div class="info"><b>${esc((i.meta && i.meta.title) || TYPES[i.type] || i.type)}</b>
        <span class="row-sub">${esc(TYPES[i.type] || i.type)}${i.meta && i.meta.number ? ', ' + esc(i.meta.number) : ''}, saisi le ${esc(new Date(i.created_at).toLocaleString('fr-FR'))}</span>
        ${i.message ? `<span class="row-sub" style="color:var(--${rejected ? 'danger' : 'warn'})">${esc(i.message)}</span>` : ''}</div>
        <span class="badge ${rejected ? 'danger' : 'info'}">${rejected ? 'Refusée' : 'En attente d’envoi'}</span>
        ${i.type === 'receipt.create' && i.meta && i.meta.number ? `<button class="btn btn-sm" data-act="print" data-id="${esc(i.uuid)}">Imprimer</button>` : ''}
        ${rejected ? `<button class="btn btn-sm btn-danger" data-act="del" data-id="${esc(i.uuid)}">Supprimer</button>` : ''}</div>`;
    }).join('');
    root.innerHTML = `
      <div class="panel"><div class="panel-head"><h2 style="flex:1">Saisies sur cet appareil</h2>
        <button class="btn btn-primary btn-sm" data-act="sync" ${navigator.onLine ? '' : 'disabled'}>Envoyer maintenant</button></div>
        ${rows || '<div class="empty"><b>Rien en attente</b>Toutes vos saisies ont été envoyées au serveur.</div>'}</div>
      ${other ? `<div class="alert info" style="margin-top:16px"><div><b>${other} ${plural(other, 'saisie appartient', 'saisies appartiennent')} à un autre compte</b>Elles seront envoyées quand ce compte se reconnectera sur cet appareil.</div></div>` : ''}
      ${D.collector === '1' ? `<div class="panel" style="margin-top:16px"><div class="panel-body"><h3>Numéros de reçu disponibles hors ligne</h3>
        <p class="muted" style="margin:6px 0 0"><span class="num"><b>${rem}</b></span> ${plural(rem, 'numéro réservé', 'numéros réservés')} pour ce compte.
        ${rem <= Number(D.blockLow || 15) ? 'Connectez-vous à internet pour en recevoir un nouveau lot avant de partir sur le terrain.' : 'Suffisant pour travailler sans connexion.'}</p></div></div>` : ''}`;
  }
  document.addEventListener('click', async (e) => {
    const b = e.target.closest('[data-act]'); if (!b || !$('#queue-root')) return;
    const act = b.dataset.act;
    if (act === 'sync') { b.disabled = true; await syncNow(); await renderQueue(); }
    if (act === 'print') { const i = (await queue.all()).find((x) => x.uuid === b.dataset.id); if (i) printProvisional(i); }
    if (act === 'del' && confirm('Supprimer définitivement cette saisie refusée ? Elle ne pourra pas être récupérée.')) { await queue.del(b.dataset.id); await refreshBadge(); renderQueue(); }
  });
  document.addEventListener('m360:queue', renderQueue);

  /* ---------- Service worker et pages disponibles hors ligne ---------- */
  async function prime() {
    if (!USER || !navigator.onLine || !('serviceWorker' in navigator)) return;
    const key = `m360:primed:${USER}:${new Date().toISOString().slice(0, 10)}`;
    try { if (localStorage.getItem(key)) return; } catch (_) { /* stockage bloqué */ }
    try {
      const j = await api('GET', D.urlPages);
      const reg = await navigator.serviceWorker.ready;
      if (reg.active) reg.active.postMessage({ type: 'prime', urls: j.urls });
      try { localStorage.setItem(key, '1'); } catch (_) { /* ignoré */ }
    } catch (_) { /* réessai au prochain chargement */ }
  }
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' }).catch(() => {});

  window.addEventListener('online', () => { toast('Connexion rétablie.', 'ok'); refreshBadge(); offlineBanner(); syncNow(); refillBlocks(); prime(); });
  window.addEventListener('offline', () => { toast('Connexion perdue : vous pouvez continuer à travailler.', 'warn', 7000); refreshBadge(); offlineBanner(); });
  setInterval(() => { if (USER && navigator.onLine) syncNow(true); }, 45000);

  initTill(); refreshBadge(); offlineBanner(); renderQueue();
  if (USER) { setTimeout(() => { syncNow(true); refillBlocks(); prime(); }, 800); }
  window.M360 = { queue, blocks, syncNow, remainingNumbers, takeReceiptNumber };
})();
