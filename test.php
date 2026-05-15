<?php
session_start();

// ── Auth guard ────────────────────────────────────────────────
if (empty($_SESSION['wallet_address'])) {
    header('Location: index.php');
    exit;
}

$wallet_name    = $_SESSION['wallet_name']    ?? 'Unknown';
$wallet_address = $_SESSION['wallet_address'] ?? '';
$usd_balance    = (float)($_SESSION['usd_balance']  ?? 0);
$eth_balance    = (float)($_SESSION['eth_balance']  ?? 0);
$chain_id       = (int)($_SESSION['chain_id']       ?? 1);

// Derive dashboard figures from real balance
$pending_funds  = $usd_balance * 0.336;
$released_funds = $usd_balance * 0.647;

// Short address display
$short_address = strlen($wallet_address) > 10
    ? substr($wallet_address, 0, 6) . '…' . substr($wallet_address, -4)
    : $wallet_address;

$chain_name = match($chain_id) {
    1     => 'Ethereum Mainnet',
    5     => 'Goerli Testnet',
    11155111 => 'Sepolia Testnet',
    137   => 'Polygon',
    42161 => 'Arbitrum',
    10    => 'Optimism',
    default => 'Chain ' . $chain_id,
};
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vouch — Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<!-- Ethers v6 for live balance refresh -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>
<style>

</style>
</head>
<body>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="app">
<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="sidebar-logo">
    <div class="logo-mark">V</div>Vouch
  </a>
  <div class="sidebar-section">Menu</div>
  <a class="nav-link active" href="dashboard.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard
  </a>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>Proposals
  </a>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 8v4l3 3"/></svg>Milestone
  </a>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>Funds
  </a>
  <div class="sidebar-section">Community</div>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>Contributors
  </a>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>Reports
  </a>
  <a class="nav-link" href="#">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93l-1.41 1.41M5.34 18.66l-1.41 1.41M19.07 19.07l-1.41-1.41M5.34 5.34L3.93 3.93M21 12h-3M6 12H3M12 3V0M12 24v-3"/></svg>Settings
  </a>
  <div class="sidebar-spacer"></div>
  <div class="sidebar-wallet">
    <div class="sw-label">Connected Wallet</div>
    <div class="sw-address"><?= htmlspecialchars($short_address) ?></div>
    <div class="sw-chain"><?= htmlspecialchars($wallet_name) ?></div>
    <div class="sw-eth" id="sidebarEth"><?= number_format($eth_balance, 4) ?> ETH</div>
    <a href="disconnect.php" class="btn-disconnect">Disconnect</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="openSidebar()">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
      <div class="topbar-title">Dashboard</div>
    </div>
    <div class="topbar-right">
      <div class="chain-badge">
        <span class="chain-dot"></span>
        <span class="chain-name"><?= htmlspecialchars($chain_name) ?></span>
      </div>
      <button class="btn-proposal">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        <span>New Proposal</span>
      </button>
    </div>
  </header>

  <div class="content">
    <div class="welcome">
      Welcome back, <strong><?= htmlspecialchars($wallet_name) ?></strong>!
      Here's what's happening with Moonlight DAO.
    </div>

    <!-- STAT CARDS -->
    <div class="stat-grid">
      <div class="stat-card" style="--card-accent:var(--accent)">
        <div class="sc-label">
          Total Funds
          <span class="live-badge"><span class="live-dot"></span>Live</span>
        </div>
        <div class="sc-value" id="totalFunds">$<?= number_format($usd_balance, 2) ?></div>
        <div class="sc-sub">
          <span class="sc-badge up">↑ ETH wallet</span>
          <span id="ethDisplay"><?= number_format($eth_balance, 4) ?> ETH</span>
        </div>
        <div class="sc-icon" style="background:rgba(99,120,255,0.12)">💎</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--warning)">
        <div class="sc-label">Pending Funds</div>
        <div class="sc-value">$<?= number_format($pending_funds, 2) ?></div>
        <div class="sc-sub"><span class="sc-badge warn">Awaiting</span> Awaiting approval</div>
        <div class="sc-icon" style="background:rgba(245,158,11,0.12)">⏳</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--success)">
        <div class="sc-label">Released Funds</div>
        <div class="sc-value">$<?= number_format($released_funds, 2) ?></div>
        <div class="sc-sub"><span class="sc-badge up">✓</span> Successfully released</div>
        <div class="sc-icon" style="background:rgba(34,211,160,0.12)">✅</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--accent2)">
        <div class="sc-label">Active Milestones</div>
        <div class="sc-value">8</div>
        <div class="sc-sub"><span style="color:var(--accent2)">Across 4 projects</span></div>
        <div class="sc-icon" style="background:rgba(167,139,250,0.12)">🚩</div>
      </div>
    </div>

    <!-- TWO-COL -->
    <div class="dash-cols">
      <!-- MILESTONE PROGRESS -->
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Milestone Progress</div>
          <button class="panel-action">View all Milestones</button>
        </div>
        <div class="milestone-item">
          <div class="mi-top">
            <div class="mi-num" style="background:rgba(99,120,255,0.15);color:var(--accent)">1</div>
            <div class="mi-info"><div class="mi-name">Website Redesign</div><div class="mi-desc">Redesign and develop the new marketing website</div></div>
            <div class="mi-right">
              <div class="mi-due"><span style="color:var(--warning)">May 26, 2026</span>Due Date</div>
              <span class="status-badge status-on-track">On Track</span>
            </div>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:75%;background:linear-gradient(90deg,var(--accent),var(--accent2))"></div></div>
          <div class="mi-budget">75% · Budget: $15,000</div>
        </div>
        <div class="milestone-item">
          <div class="mi-top">
            <div class="mi-num" style="background:rgba(34,211,160,0.15);color:var(--success)">2</div>
            <div class="mi-info"><div class="mi-name">Smart Contract Audit</div><div class="mi-desc">Security audit of core smart contracts</div></div>
            <div class="mi-right">
              <div class="mi-due"><span style="color:var(--warning)">June 15, 2026</span>Due Date</div>
              <span class="status-badge status-on-track">On Track</span>
            </div>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:50%;background:linear-gradient(90deg,var(--success),#34d399)"></div></div>
          <div class="mi-budget">50% · Budget: $8,500</div>
        </div>
        <div class="milestone-item">
          <div class="mi-top">
            <div class="mi-num" style="background:rgba(255,92,92,0.15);color:var(--danger)">3</div>
            <div class="mi-info"><div class="mi-name">Mobile App Development</div><div class="mi-desc">iOS and Android app development</div></div>
            <div class="mi-right">
              <div class="mi-due"><span style="color:var(--danger)">July 9, 2026</span>Due Date</div>
              <span class="status-badge status-at-risk">At Risk</span>
            </div>
          </div>
          <div class="progress-bar"><div class="progress-fill" style="width:25%;background:linear-gradient(90deg,var(--danger),#f87171)"></div></div>
          <div class="mi-budget">25% · Budget: $25,000</div>
        </div>
        <div style="padding:12px 20px;display:flex;gap:20px;font-size:11px;color:var(--muted);flex-wrap:wrap;">
          <span style="display:flex;align-items:center;gap:5px"><span style="width:9px;height:9px;border-radius:50%;background:#333;display:inline-block"></span>Not started</span>
          <span style="display:flex;align-items:center;gap:5px"><span style="width:9px;height:9px;border-radius:50%;background:var(--accent);display:inline-block"></span>In Progress</span>
          <span style="display:flex;align-items:center;gap:5px"><span style="width:9px;height:9px;border-radius:50%;background:var(--warning);display:inline-block"></span>On Review</span>
          <span style="display:flex;align-items:center;gap:5px"><span style="width:9px;height:9px;border-radius:50%;background:var(--success);display:inline-block"></span>Completed</span>
        </div>
      </div>

      <!-- RIGHT: FUNDS OVERVIEW -->
      <div style="display:flex;flex-direction:column;gap:20px;">
        <div class="panel">
          <div class="panel-header"><div class="panel-title">Funds Overview</div></div>
          <div class="funds-overview">
            <?php
              $pend_pct = $usd_balance > 0 ? round($pending_funds / $usd_balance * 100) : 33;
              $rel_pct  = $usd_balance > 0 ? round($released_funds / $usd_balance * 100) : 65;
              $r = 70; $cx = 90; $cy = 90;
              $circ = 2 * pi() * $r;
              $rel_dash  = ($rel_pct  / 100) * $circ;
              $pend_dash = ($pend_pct / 100) * $circ;
              $gap = 5;
            ?>
            <div style="display:flex;justify-content:center;margin:12px 0;">
              <svg width="180" height="180" viewBox="0 0 180 180" style="overflow:visible;">
                <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="rgba(255,255,255,0.04)" stroke-width="18"/>
                <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="#6378ff" stroke-width="18"
                  stroke-dasharray="<?=$rel_dash?> <?=($circ-$rel_dash)?>"
                  stroke-dashoffset="<?=($circ*0.25)?>" stroke-linecap="round"/>
                <circle cx="<?=$cx?>" cy="<?=$cy?>" r="<?=$r?>" fill="none" stroke="#f4c430" stroke-width="18"
                  stroke-dasharray="<?=$pend_dash?> <?=($circ-$pend_dash)?>"
                  stroke-dashoffset="<?=($circ*0.25 - $rel_dash - $gap)?>" stroke-linecap="round"/>
                <text x="<?=$cx?>" y="<?=$cy-6?>" text-anchor="middle" fill="#e8eaf6" font-family="Syne,sans-serif" font-size="15" font-weight="700">
                  <?= $eth_balance > 0 ? number_format($eth_balance, 3) . ' ETH' : '$' . round($usd_balance/1000) . 'K' ?>
                </text>
                <text x="<?=$cx?>" y="<?=$cy+14?>" text-anchor="middle" fill="#6b7a9e" font-family="DM Sans,sans-serif" font-size="11">Treasury</text>
              </svg>
            </div>
            <div class="donut-legend">
              <div class="legend-item">
                <span class="legend-dot" style="background:var(--gold)"></span>
                <span class="legend-label">Pending Funds</span>
                <span class="legend-val">$<?=number_format($pending_funds,0)?></span>
                <span class="legend-pct"><?=$pend_pct?>%</span>
              </div>
              <div class="legend-item">
                <span class="legend-dot" style="background:var(--accent)"></span>
                <span class="legend-label">Released Funds</span>
                <span class="legend-val">$<?=number_format($released_funds,0)?></span>
                <span class="legend-pct"><?=$rel_pct?>%</span>
              </div>
            </div>
            <div class="donut-total">
              <div class="lbl">Total Treasury</div>
              <div class="val">$<?=number_format($usd_balance,2)?></div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TRANSACTIONS + QUICK ACTIONS -->
    <div class="dash-cols" style="margin-bottom:24px">
      <div class="panel">
        <div class="panel-header">
          <div class="panel-title">Recent Transactions</div>
          <button class="panel-action">View all</button>
        </div>
        <div class="tx-item">
          <div class="tx-icon" style="background:rgba(245,158,11,0.12)">⏳</div>
          <div class="tx-info"><div class="tx-name">Milestone 1 Payment</div><div class="tx-desc">Website Redesign</div></div>
          <div class="tx-right"><div class="tx-amount" style="color:var(--warning)">$7,500</div><div class="tx-time"><span class="status-badge status-review" style="font-size:10px">Pending</span> 2h ago</div></div>
        </div>
        <div class="tx-item">
          <div class="tx-icon" style="background:rgba(34,211,160,0.12)">✅</div>
          <div class="tx-info"><div class="tx-name">Milestone 2 Payment</div><div class="tx-desc">Smart Contract Audit</div></div>
          <div class="tx-right"><div class="tx-amount" style="color:var(--success)">$4,250</div><div class="tx-time"><span class="status-badge status-on-track" style="font-size:10px">Released</span> 1d ago</div></div>
        </div>
        <div class="tx-item">
          <div class="tx-icon" style="background:rgba(34,211,160,0.12)">💰</div>
          <div class="tx-info"><div class="tx-name">DAO Treasury Top-up</div><div class="tx-desc">From <?= htmlspecialchars($short_address) ?></div></div>
          <div class="tx-right"><div class="tx-amount" style="color:var(--success)">$20,000</div><div class="tx-time"><span class="status-badge status-on-track" style="font-size:10px">Released</span> 3d ago</div></div>
        </div>
      </div>

      <div class="panel" style="display:flex;flex-direction:column;">
        <div class="panel-header"><div class="panel-title">Quick Action</div></div>
        <div class="qa-grid">
          <div class="qa-btn"><div class="qa-icon" style="background:rgba(34,211,160,0.12)">✅</div><div class="qa-label">Approve Funds</div></div>
          <div class="qa-btn"><div class="qa-icon" style="background:rgba(99,120,255,0.12)">💬</div><div class="qa-label">Provide Feedback</div></div>
          <div class="qa-btn"><div class="qa-icon" style="background:rgba(167,139,250,0.12)">📋</div><div class="qa-label">Create Proposal</div></div>
        </div>
      </div>
    </div>

    <!-- MILESTONE DETAILS TABLE -->
    <div class="panel" style="margin-bottom:24px">
      <div class="panel-header"><div class="panel-title">Milestone Details</div></div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Milestone</th><th>Progress</th><th>Status</th>
              <th>Deadline</th><th>Approval</th><th></th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="mi-num" style="width:28px;height:28px;border-radius:8px;background:rgba(99,120,255,0.15);color:var(--accent);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">1</div><div><div class="td-name">Website Redesign</div><div class="td-sub">Design and Development</div></div></div></td>
              <td><div style="display:flex;align-items:center;gap:8px"><div class="progress-bar" style="flex:1;min-width:60px"><div class="progress-fill" style="width:75%;background:var(--accent)"></div></div><span style="font-size:12px;color:var(--muted)">75%</span></div></td>
              <td><span class="status-badge" style="background:rgba(99,120,255,0.1);color:var(--accent)">In Progress</span></td>
              <td><div style="font-size:12px">May 25, 2026</div><div class="td-sub" style="color:var(--success)">5 days left</div></td>
              <td><div class="approval-dots"><div class="apdot" style="background:var(--success)"></div><div class="apdot" style="background:var(--success)"></div><div class="apdot" style="background:var(--border)"></div><span style="font-size:11px;color:var(--muted);margin-left:4px">2/3</span></div></td>
              <td><button class="btn-review">Review</button></td>
            </tr>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="mi-num" style="width:28px;height:28px;border-radius:8px;background:rgba(245,158,11,0.15);color:var(--warning);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">2</div><div><div class="td-name">Smart Contract Audit</div><div class="td-sub">Security Audit</div></div></div></td>
              <td><div style="display:flex;align-items:center;gap:8px"><div class="progress-bar" style="flex:1;min-width:60px"><div class="progress-fill" style="width:50%;background:var(--warning)"></div></div><span style="font-size:12px;color:var(--muted)">50%</span></div></td>
              <td><span class="status-badge status-review">On Review</span></td>
              <td><div style="font-size:12px">Jun 10, 2026</div><div class="td-sub" style="color:var(--muted)">21 days left</div></td>
              <td><div class="approval-dots"><div class="apdot" style="background:var(--success)"></div><div class="apdot" style="background:var(--border)"></div><div class="apdot" style="background:var(--border)"></div><span style="font-size:11px;color:var(--muted);margin-left:4px">1/3</span></div></td>
              <td><button class="btn-review">Review</button></td>
            </tr>
            <tr>
              <td><div style="display:flex;align-items:center;gap:10px"><div class="mi-num" style="width:28px;height:28px;border-radius:8px;background:rgba(255,92,92,0.15);color:var(--danger);font-size:12px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0">3</div><div><div class="td-name">Mobile App Development</div><div class="td-sub">iOS &amp; Android App</div></div></div></td>
              <td><div style="display:flex;align-items:center;gap:8px"><div class="progress-bar" style="flex:1;min-width:60px"><div class="progress-fill" style="width:25%;background:var(--danger)"></div></div><span style="font-size:12px;color:var(--muted)">25%</span></div></td>
              <td><span class="status-badge" style="background:rgba(99,120,255,0.1);color:var(--accent)">In Progress</span></td>
              <td><div style="font-size:12px">Jul 5, 2026</div><div class="td-sub" style="color:var(--danger)">At risk</div></td>
              <td><div class="approval-dots"><div class="apdot" style="background:var(--border)"></div><div class="apdot" style="background:var(--border)"></div><div class="apdot" style="background:var(--border)"></div><span style="font-size:11px;color:var(--muted);margin-left:4px">0/3</span></div></td>
              <td><button class="btn-review">Review</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<script>
// ── Sidebar toggle ──────────────────────────────────────
function openSidebar() {
  document.getElementById('sidebar').classList.add('open');
  document.getElementById('sidebarOverlay').classList.add('open');
}
function closeSidebar() {
  document.getElementById('sidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('open');
}

// ── Animate progress bars ───────────────────────────────
window.addEventListener('load', () => {
  document.querySelectorAll('.progress-fill').forEach(el => {
    const w = el.style.width; el.style.width = '0';
    setTimeout(() => { el.style.width = w; }, 300);
  });

  // ── Live balance refresh via Ethers.js ──────────────────
  // Uses the connected wallet provider to re-fetch balance
  refreshBalance();
  setInterval(refreshBalance, 60000); // refresh every 60s
});

async function refreshBalance() {
  try {
    if (typeof window.ethereum === 'undefined') return;
    const provider = new ethers.BrowserProvider(window.ethereum);
    const accounts = await provider.listAccounts();
    if (!accounts.length) return;

    const address = accounts[0].address || accounts[0];
    const raw     = await provider.getBalance(address);
    const eth     = parseFloat(ethers.formatEther(raw));

    // Fetch live ETH price
    const r   = await fetch('https://api.coingecko.com/api/v3/simple/price?ids=ethereum&vs_currencies=usd');
    const d   = await r.json();
    const px  = d.ethereum?.usd ?? 3200;
    const usd = eth * px;

    document.getElementById('totalFunds').textContent = '$' + usd.toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('ethDisplay').textContent = eth.toFixed(4) + ' ETH';
    if (document.getElementById('sidebarEth'))
      document.getElementById('sidebarEth').textContent = eth.toFixed(4) + ' ETH';
  } catch(e) {
    // silent fail — initial PHP values remain
  }
}
</script>
</body>
</html>