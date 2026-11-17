/* WP WAF Manager: Page Rules screen.
 * Values from PHP arrive in window.wpwafPage, printed by WPWAF_Admin::page_script()
 * in includes/views/page-page-rules.php. */
'use strict';
(function(){
const NONCE     = wpwafPage.nonce;
const AJAX_URL  = wpwafPage.ajax_url;
const GUIDE_URL = wpwafPage.guide_url;
const CONCURRENCY = 3;

const esc = s => String(s == null ? '' : s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
const qs  = (s, c) => (c || document).querySelector(s);

let zones   = [];          // from wpwaf_list_zones
const state = {};          // zone id => { status:'pending'|'loading'|'done'|'error', data, error }
let scanning = false;

function toast(msg, ok = true, icon){
  const el = document.createElement('div');
  el.className = 'wpwaf-pr-toast ' + (ok ? 'ok' : 'err');
  if (window.wpwafToastBody) el.innerHTML = wpwafToastBody(msg, ok, icon); else el.textContent = msg;
  document.body.appendChild(el);
  setTimeout(() => { el.style.transition = 'opacity .3s'; el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, ok ? 3000 : 6000);
}

function ajax(action, data){
  const fd = new FormData();
  fd.append('action', action);
  fd.append('nonce', NONCE);
  Object.entries(data || {}).forEach(([k, v]) => fd.append(k, v));
  return fetch(AJAX_URL, { method:'POST', body:fd, credentials:'same-origin' })
    .then(r => r.json())
    .catch(e => ({ success:false, data:{ message:e.message } }));
}

function planKey(name){
  const p = String(name || '').toLowerCase();
  if (p.includes('enterprise')) return 'enterprise';
  if (p.includes('business'))   return 'business';
  if (p.includes('pro'))        return 'pro';
  return 'free';
}

// ── Scan ─────────────────────────────────────────────────────────────────────
qs('#wpwaf-pr-scan').addEventListener('click', scanAll);
qs('#wpwaf-pr-search').addEventListener('input', render);
qs('#wpwaf-pr-only').addEventListener('change', render);

async function scanAll(){
  if (scanning) return;
  scanning = true;
  const btn = qs('#wpwaf-pr-scan');
  btn.disabled = true;
  btn.innerHTML = '<span class="wpwaf-pr-spinner"></span> Scanning&hellip;';
  setProgress('Loading zones&hellip;');

  const zr = await ajax('wpwaf_list_zones');
  if (!zr.success){
    qs('#wpwaf-pr-zones').innerHTML = '<div class="wpwaf-pr-notice wpwaf-pr-notice--error">Could not load zones: ' + esc(zr.data && zr.data.message || 'error') + '</div>';
    finishScan();
    return;
  }
  zones = (zr.data.zones || []).slice().sort((a, b) => a.name.localeCompare(b.name));
  zones.forEach(z => { state[z.id] = { status:'pending' }; });
  render();

  let next = 0, done = 0;
  const worker = async () => {
    while (next < zones.length){
      const z = zones[next++];
      await scanZone(z);
      done++;
      setProgress('Scanned ' + done + ' of ' + zones.length + ' zones');
    }
  };
  await Promise.all(Array.from({ length: Math.min(CONCURRENCY, zones.length) }, worker));
  finishScan();
}

function finishScan(){
  scanning = false;
  const btn = qs('#wpwaf-pr-scan');
  btn.disabled = false;
  btn.innerHTML = wpwafIconText('refresh', 'Scan again');
}

function setProgress(html){ qs('#wpwaf-pr-progress').innerHTML = html; }

async function scanZone(z){
  state[z.id] = { status:'loading' };
  renderZone(z);
  const res = await ajax('wpwaf_pr_scan', { zone_id:z.id, plan:z.plan || '' });
  if (res.success){
    state[z.id] = { status:'done', data:res.data };
  } else {
    state[z.id] = { status:'error', error:(res.data && res.data.message) || 'Scan failed' };
  }
  render();
}

// ── Render ───────────────────────────────────────────────────────────────────
function visibleZones(){
  const q    = qs('#wpwaf-pr-search').value.trim().toLowerCase();
  const only = qs('#wpwaf-pr-only').checked;
  return zones.filter(z => {
    if (q && !z.name.toLowerCase().includes(q)) return false;
    if (!only) return true;
    const s = state[z.id];
    if (!s || s.status === 'pending' || s.status === 'loading' || s.status === 'error') return true;
    return (s.data.rules || []).length > 0;
  });
}

function render(){
  if (!zones.length) return;
  updateSummary();
  const list = visibleZones();
  const wrap = qs('#wpwaf-pr-zones');
  if (!list.length){
    const allDone = zones.every(z => state[z.id] && state[z.id].status !== 'pending' && state[z.id].status !== 'loading');
    wrap.innerHTML = '<div class="wpwaf-pr-empty">' + (allDone && qs('#wpwaf-pr-only').checked && !qs('#wpwaf-pr-search').value
      ? wpwafIconText('circle-check', 'No Page Rules found in any zone. Nothing to migrate.')
      : 'No zones match your filter.') + '</div>';
    return;
  }
  wrap.innerHTML = list.map(zoneHTML).join('');
}

function renderZone(z){
  const el = qs('#wpwaf-pr-zone-' + z.id);
  if (el) el.outerHTML = zoneHTML(z);
  else render();
}

function updateSummary(){
  let zc = 0, rc = 0, ac = 0, dc = 0, any = false;
  zones.forEach(z => {
    const s = state[z.id];
    if (!s || s.status !== 'done') return;
    any = true;
    const rules = s.data.rules || [];
    const mig = s.data.migrated || {};
    if (rules.length) zc++;
    rc += rules.length;
    rules.forEach(r => {
      if (mig[r.id]) dc++;
      else if (r.auto) ac++;
    });
  });
  qs('#wpwaf-pr-summary').style.display = any ? '' : 'none';
  qs('#wpwaf-pr-s-zones').textContent = zc;
  qs('#wpwaf-pr-s-rules').textContent = rc;
  qs('#wpwaf-pr-s-auto').textContent  = ac;
  qs('#wpwaf-pr-s-done').textContent  = dc;
}

function planPill(plan){
  const k = planKey(plan);
  return '<span class="wpwaf-pr-plan' + (k !== 'free' ? ' wpwaf-pr-plan--' + k : '') + '">' + esc(plan || 'Free') + '</span>';
}

function zoneHTML(z){
  const s = state[z.id] || { status:'pending' };
  let meta = '', body = '', actions = '';

  if (s.status === 'pending'){
    body = '<div class="wpwaf-pr-zone-empty">Waiting&hellip;</div>';
  } else if (s.status === 'loading'){
    body = '<div class="wpwaf-pr-zone-empty"><span class="wpwaf-pr-spinner"></span> Reading Page Rules&hellip;</div>';
  } else if (s.status === 'error'){
    body = '<div class="wpwaf-pr-zone-warn">' + esc(s.error) + '</div><div class="wpwaf-pr-zone-empty"></div>';
    actions = '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-rescan="' + esc(z.id) + '">' + wpwafIconText('refresh', 'Retry') + '</button>';
  } else {
    const d = s.data, rules = d.rules || [], mig = d.migrated || {}, lim = d.limits || {};
    const counts = d.counts || {};
    meta = '<span class="wpwaf-pr-zone-meta">' + rules.length + ' of ' + esc(lim.page_rules) + ' Page Rules'
      + (counts.redirect != null ? ' &middot; Single Redirects ' + esc(counts.redirect) + '/' + esc(lim.redirect) : '')
      + (counts.cache != null ? ' &middot; Cache Rules ' + esc(counts.cache) + '/' + esc(lim.cache) : '')
      + '</span>';
    const eligible = rules.filter(r => r.auto && !mig[r.id]);
    if (eligible.length > 1){
      actions = '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-all="' + esc(z.id) + '">Migrate all eligible (' + eligible.length + ')</button>';
    }
    actions += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-rescan="' + esc(z.id) + '">' + wpwafIconText('refresh', 'Rescan') + '</button>';
    body = (d.warning ? '<div class="wpwaf-pr-zone-warn">' + esc(d.warning) + '</div>' : '')
      + (rules.length ? rules.map(r => ruleHTML(z, r, mig[r.id])).join('') : '<div class="wpwaf-pr-zone-empty">No Page Rules in this zone.</div>');
  }

  return '<div class="wpwaf-pr-zone" id="wpwaf-pr-zone-' + esc(z.id) + '">'
    + '<div class="wpwaf-pr-zone-head"><span class="wpwaf-pr-zone-name">' + esc(z.name) + '</span>' + planPill(z.plan) + meta
    + (actions ? '<div class="wpwaf-pr-zone-actions">' + actions + '</div>' : '') + '</div>'
    + '<div class="wpwaf-pr-zone-body">' + body + '</div></div>';
}

function ruleHTML(z, r, migrated){
  const on = r.status === 'active';
  const kindLabel = r.kind === 'redirect' ? 'Single Redirect' : 'Cache Rule';
  let badges = '<span class="wpwaf-pr-badge">Priority ' + esc(r.priority) + '</span>'
    + '<span class="wpwaf-pr-badge ' + (on ? 'wpwaf-pr-badge--on' : 'wpwaf-pr-badge--off') + '">' + (on ? 'On' : 'Off') + '</span>';
  if (migrated) badges += '<span class="wpwaf-pr-badge wpwaf-pr-badge--done">' + wpwafIconText('check', 'Migrated') + '</span>';
  else if (r.auto) badges += '<span class="wpwaf-pr-badge wpwaf-pr-badge--auto">Auto migrate to ' + kindLabel + '</span>';

  const acts = (r.actions || []).map(a =>
    '<div class="wpwaf-pr-action"><strong>' + esc(a.label) + '</strong>'
    + (a.value ? ' <span class="wpwaf-pr-action-val">(' + esc(a.value) + ')</span>' : '')
    + '<span class="wpwaf-pr-arrow">&rarr;</span>' + esc(a.replacement) + '</div>').join('');

  let reason = '';
  if (migrated){
    reason = '<div class="wpwaf-pr-reason">A matching ' + (migrated.phase === 'http_request_dynamic_redirect' ? 'Single Redirect' : 'Cache Rule')
      + ' created by this plugin is in place. Test the site, then turn this Page Rule off.</div>';
  } else if (!r.auto){
    reason = '<div class="wpwaf-pr-reason"><strong>Migrate manually:</strong> ' + esc(r.reason)
      + ' <a href="' + esc(GUIDE_URL) + '" target="_blank" rel="noopener">How to migrate</a></div>';
  }

  let btns = '';
  if (!migrated && r.auto){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--primary wpwaf-pr-btn--sm" data-pr-preview="' + esc(z.id) + '|' + esc(r.id) + '">Preview &amp; migrate</button>';
  }
  if (migrated && on){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-status="' + esc(z.id) + '|' + esc(r.id) + '|disabled">Turn Page Rule off</button>';
  }
  if (!on && migrated){
    btns += '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--sm" data-pr-status="' + esc(z.id) + '|' + esc(r.id) + '|active">Re-enable Page Rule</button>';
  }

  return '<div class="wpwaf-pr-rule">'
    + '<div><div class="wpwaf-pr-rule-top"><span class="wpwaf-pr-pattern">' + esc(r.pattern) + '</span>' + badges + '</div>'
    + '<div class="wpwaf-pr-actions">' + acts + '</div>' + reason + '</div>'
    + '<div class="wpwaf-pr-rule-btns">' + btns + '</div></div>';
}

// ── Actions ──────────────────────────────────────────────────────────────────
qs('#wpwaf-pr-zones').addEventListener('click', e => {
  const t = e.target.closest('button');
  if (!t) return;
  if (t.dataset.prRescan){
    const z = zones.find(x => x.id === t.dataset.prRescan);
    if (z) scanZone(z);
  } else if (t.dataset.prPreview){
    const [zid, rid] = t.dataset.prPreview.split('|');
    openPreview(zid, rid);
  } else if (t.dataset.prStatus){
    const [zid, rid, status] = t.dataset.prStatus.split('|');
    setStatus(zid, rid, status, t);
  } else if (t.dataset.prAll){
    migrateAll(t.dataset.prAll, t);
  }
});

function findRule(zid, rid){
  const s = state[zid];
  return s && s.data ? (s.data.rules || []).find(r => r.id === rid) : null;
}

function openPreview(zid, rid){
  const z = zones.find(x => x.id === zid), r = findRule(zid, rid);
  if (!z || !r || !r.preview) return;
  const p = r.preview, isRedirect = r.kind === 'redirect';
  const lim = state[zid].data.limits || {}, counts = state[zid].data.counts || {};
  const used = isRedirect ? counts.redirect : counts.cache;
  const max  = isRedirect ? lim.redirect : lim.cache;

  let fields = '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">When incoming requests match</div><code class="wpwaf-pr-code">' + esc(p.expression) + '</code></div>';
  if (isRedirect){
    fields += '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">Redirect to (' + esc(p.status_code) + ')</div><code class="wpwaf-pr-code">' + esc(p.target) + '</code></div>'
      + '<div class="wpwaf-pr-field"><label class="wpwaf-pr-check wpwaf-pr-check--wrap"><input type="checkbox" id="wpwaf-pr-keepqs" checked> Preserve query string (Cloudflare recommends this when migrating)</label></div>';
  } else {
    fields += '<div class="wpwaf-pr-field"><div class="wpwaf-pr-field-label">Then</div><ul>' + (p.settings || []).map(x => '<li>' + esc(x) + '</li>').join('') + '</ul></div>';
  }

  const notes = isRedirect
    ? '<p>The new Single Redirect is added after any redirects already in the zone. When two redirects match, Cloudflare uses the first one.</p>'
      + '<p>Single Redirects only work on hostnames that are proxied through Cloudflare (orange cloud).</p>'
    : '<p>Modern Rules take priority over Page Rules, so the new Cache Rule applies right away. Cache Rules that match the same request combine, and later rules win when settings conflict.</p>'
      + (r.preview.settings.join(' ').indexOf('Eligible') !== -1 ? '<p>Eligible for cache caches every response that matches, including HTML. Make sure the matched URLs never show logged-in or personal content.</p>' : '');

  const modal = document.createElement('div');
  modal.className = 'wpwaf-pr-modal-bg';
  modal.innerHTML = '<div class="wpwaf-pr-modal" role="dialog" aria-modal="true" aria-labelledby="wpwaf-pr-modal-title">'
    + '<h2 id="wpwaf-pr-modal-title">Migrate to a ' + (isRedirect ? 'Single Redirect' : 'Cache Rule') + '</h2>'
    + '<div class="wpwaf-pr-modal-sub">' + esc(z.name) + ' &middot; Page Rule <strong>' + esc(r.pattern) + '</strong>'
    + (used != null ? ' &middot; ' + esc(used) + ' of ' + esc(max) + ' ' + (isRedirect ? 'Single Redirects' : 'Cache Rules') + ' used' : '') + '</div>'
    + fields
    + '<div class="wpwaf-pr-modal-notes">' + notes + '<p>The Page Rule stays on until you turn it off. Nothing is deleted.</p></div>'
    + '<div class="wpwaf-pr-modal-foot"><button type="button" class="wpwaf-pr-btn" data-close>Cancel</button>'
    + '<button type="button" class="wpwaf-pr-btn wpwaf-pr-btn--primary" data-go>Create ' + (isRedirect ? 'Single Redirect' : 'Cache Rule') + '</button></div></div>';
  document.body.appendChild(modal);

  const close = () => { modal.remove(); document.removeEventListener('keydown', onKey); };
  const onKey = e => { if (e.key === 'Escape') close(); };
  document.addEventListener('keydown', onKey);
  modal.addEventListener('click', e => { if (e.target === modal || e.target.hasAttribute('data-close')) close(); });
  const go = qs('[data-go]', modal);
  go.focus();
  go.addEventListener('click', async () => {
    go.disabled = true;
    go.innerHTML = '<span class="wpwaf-pr-spinner"></span> Creating&hellip;';
    const keep = qs('#wpwaf-pr-keepqs', modal);
    const ok = await migrate(z, r, keep ? keep.checked : true);
    if (ok) close();
    else { go.disabled = false; go.textContent = 'Try again'; }
  });
}

async function migrate(z, r, keepQs){
  const res = await ajax('wpwaf_pr_migrate', { zone_id:z.id, rule_id:r.id, preserve_query_string:keepQs ? '1' : '0' });
  if (res.success || (res.data && res.data.already)){
    const d = state[z.id].data;
    d.migrated = d.migrated || {};
    d.migrated[r.id] = { phase:(res.data && res.data.phase) || r.phase, rule_id:(res.data && res.data.rule_id) || '' };
    if (res.success){
      if (r.kind === 'redirect' && d.counts && d.counts.redirect != null) d.counts.redirect++;
      if (r.kind === 'cache' && d.counts && d.counts.cache != null) d.counts.cache++;
    }
    render();
    toast(res.success ? (r.kind === 'redirect' ? 'Single Redirect' : 'Cache Rule') + ' created for ' + z.name : res.data.message);
    return true;
  }
  toast('Migration failed: ' + ((res.data && res.data.message) || 'error'), false);
  return false;
}

async function migrateAll(zid, btn){
  const z = zones.find(x => x.id === zid);
  const d = state[zid] && state[zid].data;
  if (!z || !d) return;
  const mig = d.migrated || {};
  const eligible = (d.rules || []).filter(r => r.auto && !mig[r.id]);
  if (!eligible.length) return;
  if (!confirm('Create ' + eligible.length + ' new rules in ' + z.name + '? Your Page Rules stay on until you turn them off.')) return;

  // Redirects: first match wins, so the highest priority Page Rule goes first.
  // Cache Rules: later rules win, so the highest priority Page Rule goes last.
  const redirects = eligible.filter(r => r.kind === 'redirect').sort((a, b) => b.priority - a.priority);
  const caches    = eligible.filter(r => r.kind === 'cache').sort((a, b) => a.priority - b.priority);
  btn.disabled = true;
  btn.innerHTML = '<span class="wpwaf-pr-spinner"></span> Migrating&hellip;';
  let ok = 0;
  for (const r of redirects.concat(caches)){
    if (await migrate(z, r, true)) ok++;
    else break;
  }
  toast(ok + ' of ' + eligible.length + ' rules migrated in ' + z.name, ok === eligible.length);
  render();
}

async function setStatus(zid, rid, status, btn){
  const z = zones.find(x => x.id === zid), r = findRule(zid, rid);
  if (!z || !r) return;
  if (status === 'disabled' && !confirm('Turn off the Page Rule ' + r.pattern + '? You can turn it back on here at any time.')) return;
  btn.disabled = true;
  const res = await ajax('wpwaf_pr_set_status', { zone_id:zid, rule_id:rid, status });
  if (res.success){
    r.status = status;
    render();
    toast(status === 'disabled' ? 'Page Rule turned off' : 'Page Rule turned back on');
  } else {
    btn.disabled = false;
    toast('Failed: ' + ((res.data && res.data.message) || 'error'), false);
  }
}
})();
