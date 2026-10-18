<?php
// public/antrean/display.php - Halaman Display Antrean (layar tunggu pelanggan)
// Diakses di: localhost/BARDIR/public/antrean/display.php
date_default_timezone_set('Asia/Jakarta');
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$appRoot = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'], 3)), '/');
$apiBase = $baseUrl . $appRoot;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BARDIR — Layar Antrean</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@700&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0a0e1a;
  --bg2: #0f1628;
  --card: #131d35;
  --border: rgba(255,255,255,0.07);
  --gold: #f5c842;
  --gold2: #ffd900;
  --green: #22c55e;
  --blue: #3b82f6;
  --slate: #94a3b8;
  --white: #f1f5f9;
}
* { margin:0; padding:0; box-sizing:border-box; }
body {
  font-family:'Inter',sans-serif;
  background:var(--bg);
  color:var(--white);
  min-height:100vh;
  overflow-x:hidden;
}

/* ── HEADER ── */
.header {
  background: linear-gradient(135deg, #0f1628 0%, #1a2540 100%);
  border-bottom: 1px solid var(--border);
  padding: 14px 28px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.header-logo { display:flex; align-items:center; gap:14px; }
.logo-icon {
  width:48px; height:48px; border-radius:14px;
  background: linear-gradient(135deg,#f5c842,#ff9800);
  display:flex; align-items:center; justify-content:center;
  font-size:22px; font-weight:900; color:#000;
}
.logo-text { font-size:22px; font-weight:900; letter-spacing:-0.5px; }
.logo-sub { font-size:11px; color:var(--slate); font-weight:500; letter-spacing:1.5px; }
.header-clock { text-align:right; }
.clock-time { font-family:'JetBrains Mono',monospace; font-size:32px; font-weight:700; color:var(--gold); line-height:1; }
.clock-date { font-size:11px; color:var(--slate); text-transform:uppercase; letter-spacing:1px; margin-top:2px; }

/* ── LIVE INDICATOR ── */
.live-badge {
  display:flex; align-items:center; gap:7px;
  background:rgba(34,197,94,0.1); border:1px solid rgba(34,197,94,0.25);
  border-radius:20px; padding:5px 14px;
  font-size:11px; font-weight:700; color:var(--green); letter-spacing:0.5px;
}
.live-dot { width:8px; height:8px; border-radius:50%; background:var(--green); animation:pulse-dot 1.4s infinite; }
@keyframes pulse-dot { 0%,100%{ opacity:1; transform:scale(1); } 50%{ opacity:.5; transform:scale(.8); } }

/* ── MAIN LAYOUT ── */
.main { display:flex; gap:0; min-height:calc(100vh - 77px); }
.chairs-section { flex:1; padding:24px 24px 24px 28px; }
.waiting-section { width:340px; background:var(--bg2); border-left:1px solid var(--border); padding:20px; display:flex; flex-direction:column; }

/* ── SECTION HEADER ── */
.section-header {
  display:flex; align-items:center; justify-content:space-between;
  margin-bottom:16px;
}
.section-title {
  display:flex; align-items:center; gap:9px;
  font-size:13px; font-weight:700; letter-spacing:0.5px; text-transform:uppercase; color:var(--slate);
}
.section-title::before { content:''; width:4px; height:16px; border-radius:2px; background:var(--gold); }
.section-count { font-size:11px; font-weight:700; color:var(--gold); background:rgba(245,200,66,.12); border:1px solid rgba(245,200,66,.2); border-radius:20px; padding:3px 10px; }

/* ── CHAIRS GRID ── */
.chairs-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:16px; }

/* ── CHAIR CARD ── */
.chair-card {
  background:var(--card);
  border:1px solid var(--border);
  border-radius:20px;
  padding:20px;
  transition: all .35s cubic-bezier(.34,1.56,.64,1);
  position:relative; overflow:hidden;
}
.chair-card::before {
  content:''; position:absolute; top:0; left:0; right:0; height:3px;
  border-radius:20px 20px 0 0;
  background: linear-gradient(90deg,transparent,rgba(255,255,255,0.05),transparent);
  transition: background .3s;
}
.chair-card.active::before { background: linear-gradient(90deg,var(--gold),#ff9800); }
.chair-card.called { animation: chairPulse 0.8s ease 3; border-color:rgba(245,200,66,0.5); }
@keyframes chairPulse { 0%,100%{ box-shadow:none; } 50%{ box-shadow:0 0 30px rgba(245,200,66,0.4); } }

.chair-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; }
.chair-label { font-size:11px; font-weight:700; color:var(--slate); letter-spacing:1.5px; text-transform:uppercase; }
.chair-status {
  font-size:10px; font-weight:700; letter-spacing:0.5px; padding:3px 10px;
  border-radius:20px; text-transform:uppercase;
}
.chair-status.serving { background:rgba(245,200,66,0.15); color:var(--gold); border:1px solid rgba(245,200,66,0.3); animation:status-pulse 2s infinite; }
.chair-status.idle    { background:rgba(148,163,184,0.1); color:var(--slate); border:1px solid rgba(148,163,184,0.15); }
@keyframes status-pulse { 0%,100%{ opacity:1; } 50%{ opacity:.6; } }

.chair-number {
  font-family:'JetBrains Mono',monospace;
  font-size:58px; font-weight:700;
  color: var(--gold); line-height:1; text-align:center; margin:8px 0;
  letter-spacing:-3px;
  text-shadow: 0 0 40px rgba(245,200,66,0.3);
}
.chair-number.empty { color:rgba(148,163,184,0.2); font-size:36px; letter-spacing:0; }
.chair-customer { font-size:15px; font-weight:700; text-align:center; margin-bottom:6px; color:var(--white); }
.chair-service  { font-size:11px; color:var(--slate); text-align:center; margin-bottom:12px; }
.chair-barber   { display:flex; align-items:center; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:10px; }
.chair-barber-label { font-size:10px; color:var(--slate); font-weight:500; }
.chair-barber-name  { font-size:12px; font-weight:700; color:var(--white); }

/* ── CALL OVERLAY ── */
#callOverlay {
  position:fixed; inset:0; z-index:999;
  background:rgba(0,0,0,0.85);
  backdrop-filter:blur(8px);
  display:none; align-items:center; justify-content:center; flex-direction:column;
}
#callOverlay.show { display:flex; }
.call-box {
  background:linear-gradient(135deg,#131d35,#1a2a50);
  border:2px solid var(--gold);
  border-radius:28px; padding:50px 60px; text-align:center;
  box-shadow:0 0 80px rgba(245,200,66,0.3);
  animation:callZoom .5s cubic-bezier(.34,1.56,.64,1);
}
@keyframes callZoom { from{transform:scale(.5);opacity:0;} to{transform:scale(1);opacity:1;} }
.call-label { font-size:14px; font-weight:700; color:var(--slate); letter-spacing:2px; text-transform:uppercase; margin-bottom:10px; }
.call-number { font-family:'JetBrains Mono',monospace; font-size:120px; font-weight:700; color:var(--gold); line-height:1; text-shadow:0 0 60px rgba(245,200,66,0.5); animation:numberFlash 0.6s ease 3; }
@keyframes numberFlash { 0%,100%{opacity:1;} 50%{opacity:.4;} }
.call-name    { font-size:28px; font-weight:800; color:var(--white); margin-top:10px; }
.call-sub     { font-size:14px; color:var(--slate); margin-top:8px; }

/* ── WAITING PANEL ── */
.waiting-empty { color:var(--slate); font-size:13px; text-align:center; margin-top:20px; opacity:.6; }
.waiting-item {
  background:var(--card); border:1px solid var(--border); border-radius:14px;
  padding:14px 16px; margin-bottom:10px; display:flex; align-items:center; gap:12px;
  transition:all .3s;
}
.waiting-item.new-item { animation:slideIn .4s ease; }
@keyframes slideIn { from{transform:translateX(20px);opacity:0;} to{transform:translateX(0);opacity:1;} }
.waiting-num {
  min-width:40px; height:40px; border-radius:10px;
  background:rgba(245,200,66,0.1); border:1px solid rgba(245,200,66,0.2);
  color:var(--gold); font-family:'JetBrains Mono',monospace; font-size:15px; font-weight:700;
  display:flex; align-items:center; justify-content:center;
}
.waiting-info { flex:1; min-width:0; }
.waiting-name    { font-size:13px; font-weight:700; color:var(--white); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.waiting-service { font-size:11px; color:var(--slate); margin-top:2px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.waiting-barber  { font-size:10px; font-weight:700; color:var(--gold); margin-top:2px; }

/* ── TICKER ── */
.ticker {
  background:linear-gradient(90deg,#0d1225,#131d35,#0d1225);
  border-top:1px solid var(--border);
  padding:10px 0; overflow:hidden;
  font-size:12px; font-weight:600; color:var(--slate); letter-spacing:0.5px;
}
.ticker-inner { display:flex; white-space:nowrap; animation:ticker 30s linear infinite; }
.ticker-inner span { padding:0 40px; }
@keyframes ticker { from{transform:translateX(0);} to{transform:translateX(-50%);} }

/* ── FOOTER ── */
.footer {
  padding:10px 28px; display:flex; align-items:center; justify-content:space-between;
  border-top:1px solid var(--border); background:var(--bg2);
  font-size:11px; color:var(--slate);
}
.conn-status { display:flex; align-items:center; gap:6px; }
.conn-dot { width:7px; height:7px; border-radius:50%; }
.conn-dot.ok   { background:var(--green); }
.conn-dot.err  { background:#ef4444; }
</style>
</head>
<body>

<!-- HEADER -->
<header class="header">
  <div class="header-logo">
    <div class="logo-icon">✂</div>
    <div>
      <div class="logo-text">BARDIR</div>
      <div class="logo-sub">LIVE QUEUE DISPLAY</div>
    </div>
  </div>
  <div class="live-badge"><span class="live-dot"></span> LIVE</div>
  <div class="header-clock">
    <div class="clock-time" id="clockTime">--:--:--</div>
    <div class="clock-date" id="clockDate">Loading...</div>
  </div>
</header>

<!-- CALL OVERLAY -->
<div id="callOverlay">
  <div class="call-box">
    <div class="call-label">📣 Nomor Antrean Dipanggil</div>
    <div class="call-number" id="callNumber">#00</div>
    <div class="call-name" id="callName">—</div>
    <div class="call-sub" id="callSub">Silakan menuju kursi kapster</div>
  </div>
</div>

<!-- MAIN -->
<div class="main">
  <!-- LEFT: Chairs serving -->
  <div class="chairs-section">
    <div class="section-header">
      <div class="section-title">Kursi Kapster Sedang Melayani</div>
      <div id="autoUpdateBadge" style="font-size:11px;color:var(--slate);">—</div>
    </div>
    <div class="chairs-grid" id="chairsGrid">
      <p style="color:var(--slate);font-size:13px;">Memuat data...</p>
    </div>
  </div>

  <!-- RIGHT: Waiting -->
  <div class="waiting-section">
    <div class="section-header">
      <div class="section-title">Antrean Berikutnya</div>
      <div class="section-count" id="waitingCount">0 Menunggu</div>
    </div>
    <div id="waitingList">
      <p class="waiting-empty">Tidak ada antrean menunggu.</p>
    </div>
  </div>
</div>

<!-- TICKER -->
<div class="ticker">
  <div class="ticker-inner" id="tickerInner">
    <span>✂ Selamat datang di BARDIR Barbershop — Dapatkan layanan terbaik dari kapster profesional kami 🔥</span>
    <span>Antrean dikelola secara real-time — Silakan duduk dan tunggu panggilan Anda 🪑</span>
    <span>✂ Selamat datang di BARDIR Barbershop — Dapatkan layanan terbaik dari kapster profesional kami 🔥</span>
    <span>Antrean dikelola secara real-time — Silakan duduk dan tunggu panggilan Anda 🪑</span>
  </div>
</div>

<!-- FOOTER -->
<footer class="footer">
  <span>© 2026 BARDIR Barbershop — Premium Queue System</span>
  <div class="conn-status">
    <div class="conn-dot ok" id="connDot"></div>
    <span id="connLabel">Terhubung ke Server</span>
  </div>
</footer>

<script>
// ── CONFIG ──────────────────────────────────────────────
const API_BASE   = '<?= $apiBase ?>';
const SSE_URL    = API_BASE + '/api/queue-events.php';
const QUEUE_URL  = API_BASE + '/api/queue-public.php';

// ── CLOCK ────────────────────────────────────────────────
const DAYS_ID = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
const MONTHS_ID = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
function updateClock() {
  const now = new Date();
  const h = String(now.getHours()).padStart(2,'0');
  const m = String(now.getMinutes()).padStart(2,'0');
  const s = String(now.getSeconds()).padStart(2,'0');
  document.getElementById('clockTime').textContent = `${h}:${m}:${s}`;
  const day   = DAYS_ID[now.getDay()];
  const date  = now.getDate();
  const month = MONTHS_ID[now.getMonth()];
  const year  = now.getFullYear();
  document.getElementById('clockDate').textContent = `${day}, ${date} ${month} ${year}`;
}
setInterval(updateClock, 1000);
updateClock();

// ── AUDIO (Web Audio API – no file needed) ──────────────
const AudioCtx = window.AudioContext || window.webkitAudioContext;
let audioCtx = null;

function ensureAudio() {
  if (!audioCtx) audioCtx = new AudioCtx();
  if (audioCtx.state === 'suspended') audioCtx.resume();
}

function playCallSound() {
  ensureAudio();
  // Ding-dong chime: 3 notes
  const notes = [
    { freq: 880,  start: 0,    dur: 0.3 },
    { freq: 1100, start: 0.25, dur: 0.3 },
    { freq: 880,  start: 0.5,  dur: 0.5 },
    { freq: 660,  start: 0.9,  dur: 0.6 },
  ];
  notes.forEach(n => {
    const osc  = audioCtx.createOscillator();
    const gain = audioCtx.createGain();
    osc.connect(gain);
    gain.connect(audioCtx.destination);
    osc.type = 'sine';
    osc.frequency.setValueAtTime(n.freq, audioCtx.currentTime + n.start);
    gain.gain.setValueAtTime(0.5, audioCtx.currentTime + n.start);
    gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + n.start + n.dur);
    osc.start(audioCtx.currentTime + n.start);
    osc.stop(audioCtx.currentTime + n.start + n.dur + 0.05);
  });
}

function playAddSound() {
  ensureAudio();
  const osc  = audioCtx.createOscillator();
  const gain = audioCtx.createGain();
  osc.connect(gain);
  gain.connect(audioCtx.destination);
  osc.type = 'sine';
  osc.frequency.setValueAtTime(440, audioCtx.currentTime);
  osc.frequency.linearRampToValueAtTime(660, audioCtx.currentTime + 0.15);
  gain.gain.setValueAtTime(0.3, audioCtx.currentTime);
  gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.4);
  osc.start(audioCtx.currentTime);
  osc.stop(audioCtx.currentTime + 0.45);
}

// ── CALL OVERLAY ─────────────────────────────────────────
let overlayTimer = null;
function showCallOverlay(data) {
  document.getElementById('callNumber').textContent = '#' + String(data.queue_number).padStart(2,'0');
  document.getElementById('callName').textContent   = data.customer_name;
  const sub = data.service_name
    ? `Layanan: ${data.service_name} — Kapster: ${data.barber_name}`
    : `Kapster: ${data.barber_name}`;
  document.getElementById('callSub').textContent = sub;

  const overlay = document.getElementById('callOverlay');
  overlay.classList.add('show');
  playCallSound();

  // Play sound again after 1.5s
  setTimeout(playCallSound, 1500);

  clearTimeout(overlayTimer);
  overlayTimer = setTimeout(() => overlay.classList.remove('show'), 7000);
}

// Click overlay to dismiss
document.getElementById('callOverlay').addEventListener('click', () => {
  document.getElementById('callOverlay').classList.remove('show');
  clearTimeout(overlayTimer);
});

// ── RENDER ───────────────────────────────────────────────
let knownWaitingIds = new Set();

function renderQueue(data) {
  const queue = data.queue || [];
  const stats = data.stats || {};

  // Chairs (serving)
  const serving = queue.filter(q => q.status === 'serving');
  const grid    = document.getElementById('chairsGrid');

  if (serving.length === 0) {
    grid.innerHTML = `<p style="color:var(--slate);font-size:13px;grid-column:1/-1;padding:20px 0;">Belum ada yang sedang dilayani.</p>`;
  } else {
    grid.innerHTML = serving.map((q, i) => `
      <div class="chair-card active" id="chair-${q.id}">
        <div class="chair-header">
          <span class="chair-label">Kursi ${i + 1}</span>
          <span class="chair-status serving">✂ Mencukur</span>
        </div>
        <div class="chair-number">#${String(q.queue_number).padStart(2,'0')}</div>
        <div class="chair-customer">${escapeHtml(q.customer_name)}</div>
        <div class="chair-service">${escapeHtml(q.service_name || 'Layanan Umum')}</div>
        <div class="chair-barber">
          <span class="chair-barber-label">Kapster:</span>
          <span class="chair-barber-name">${escapeHtml(q.barber_name || 'Siapa Saja')}</span>
        </div>
      </div>
    `).join('');
  }

  // Waiting panel
  const waiting = queue.filter(q => q.status === 'waiting');
  document.getElementById('waitingCount').textContent = waiting.length + ' Menunggu';

  const wList = document.getElementById('waitingList');
  if (waiting.length === 0) {
    wList.innerHTML = '<p class="waiting-empty">Tidak ada antrean menunggu.</p>';
    knownWaitingIds = new Set();
  } else {
    const newIds = new Set(waiting.map(q => q.id));
    wList.innerHTML = waiting.map(q => {
      const isNew = !knownWaitingIds.has(q.id);
      return `
        <div class="waiting-item${isNew ? ' new-item' : ''}">
          <div class="waiting-num">#${String(q.queue_number).padStart(2,'0')}</div>
          <div class="waiting-info">
            <div class="waiting-name">${escapeHtml(q.customer_name)}</div>
            <div class="waiting-service">${escapeHtml(q.service_name || 'Layanan Umum')}</div>
            <div class="waiting-barber">${escapeHtml(q.barber_name || 'Bebas / Siapa Saja')}</div>
          </div>
        </div>
      `;
    }).join('');
    if ([...newIds].some(id => !knownWaitingIds.has(id))) playAddSound();
    knownWaitingIds = newIds;
  }

  // Update badge
  document.getElementById('autoUpdateBadge').textContent =
    `Update: ${new Date().toLocaleTimeString('id-ID')}`;
}

function escapeHtml(str) {
  if (!str) return '';
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── SSE CONNECTION ────────────────────────────────────────
let evtSource = null;
let reconnectTimer = null;
let isFirstLoad = true;

function setConnStatus(ok, label) {
  document.getElementById('connDot').className = 'conn-dot ' + (ok ? 'ok' : 'err');
  document.getElementById('connLabel').textContent = label;
}

function connectSSE() {
  if (evtSource) { evtSource.close(); evtSource = null; }
  clearTimeout(reconnectTimer);

  setConnStatus(true, 'Menghubungkan...');
  evtSource = new EventSource(SSE_URL);

  evtSource.addEventListener('connected', () => {
    setConnStatus(true, 'Live — Terhubung ke Server');
    isFirstLoad = true;
  });

  evtSource.addEventListener('queue_update', e => {
    try {
      const data = JSON.parse(e.data);
      if (isFirstLoad) {
        // First load: render without sound
        knownWaitingIds = new Set((data.queue || []).filter(q => q.status === 'waiting').map(q => q.id));
        isFirstLoad = false;
      }
      renderQueue(data);
    } catch(err) { console.error('SSE parse error', err); }
  });

  evtSource.addEventListener('queue_called', e => {
    try {
      const data = JSON.parse(e.data);
      showCallOverlay(data);
      // Highlight the called chair
      setTimeout(() => {
        document.querySelectorAll('.chair-card').forEach(el => el.classList.remove('called'));
        const chairs = document.querySelectorAll('.chair-card');
        if (chairs.length > 0) chairs[0].classList.add('called');
      }, 300);
    } catch(err) { console.error('SSE call error', err); }
  });

  evtSource.addEventListener('close', () => {
    setConnStatus(false, 'Koneksi terputus — Menghubungkan ulang...');
    evtSource.close();
    reconnectTimer = setTimeout(connectSSE, 3000);
  });

  evtSource.addEventListener('error', () => {
    setConnStatus(false, 'Gagal — Menghubungkan ulang...');
    evtSource.close();
    evtSource = null;
    reconnectTimer = setTimeout(connectSSE, 5000);
  });
}

// ── TEST BUTTON (klik mana saja untuk unlock audio) ──────
let audioUnlocked = false;
document.body.addEventListener('click', () => {
  if (!audioUnlocked) {
    ensureAudio();
    audioUnlocked = true;
  }
}, { once: false });

// ── INIT ─────────────────────────────────────────────────
// Initial load via REST then switch to SSE
async function initialLoad() {
  try {
    const res = await fetch(QUEUE_URL);
    const result = await res.json();
    if (result.success) {
      knownWaitingIds = new Set((result.data.queue || []).filter(q => q.status === 'waiting').map(q => q.id));
      renderQueue(result.data);
    }
  } catch(e) { console.warn('Initial load failed:', e); }
}

initialLoad().then(() => {
  isFirstLoad = false;
  connectSSE();
});
</script>
</body>
</html>
