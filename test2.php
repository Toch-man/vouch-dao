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
<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>

<style>
:root{
  --bg:#060A14;--sidebar-bg:#080D1A;--surface:#0E1528;--surface2:#131d35;
  --border:rgba(99,120,255,0.14);--accent:#6378ff;--accent2:#a78bfa;
  --gold:#f4c430;--text:#e8eaf6;--muted:#6b7a9e;
  --danger:#ff5c5c;--success:#22d3a0;--warning:#f59e0b;
  --sidebar-w:240px;
}
*{margin:0;padding:0;box-sizing:border-box;}
html,body{height:100%;font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);}

/* ... [All your existing styles remain unchanged] ... */

/* ── NEW PROPOSAL MODAL ── */
.modal {
  display: none;
  position: fixed;
  top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(6,10,20,0.85);
  backdrop-filter: blur(8px);
  z-index: 1000;
  align-items: center;
  justify-content: center;
}
.modal-content {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: 16px;
  width: 90%;
  max-width: 520px;
  max-height: 92vh;
  overflow-y: auto;
  box-shadow: 0 20px 60px rgba(0,0,0,0.6);
}
.modal-header {
  padding: 20px 24px;
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.modal-title {
  font-family: 'Syne', sans-serif;
  font-size: 18px;
  font-weight: 700;
}
.close-modal {
  background: none;
  border: none;
  color: var(--muted);
  font-size: 24px;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.close-modal:hover { color: var(--text); }

.modal-body {
  padding: 24px;
}
.form-group {
  margin-bottom: 20px;
}
.form-group label {
  display: block;
  font-size: 13px;
  color: var(--muted);
  margin-bottom: 8px;
  font-weight: 500;
}
.form-group input {
  width: 100%;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 10px;
  padding: 12px 16px;
  color: var(--text);
  font-size: 15px;
}
.form-group input:focus {
  outline: none;
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(99,120,255,0.15);
}
.form-group input[readonly] {
  background: rgba(99,120,255,0.08);
  color: var(--accent2);
  cursor: not-allowed;
}

.btn-submit {
  width: 100%;
  background: linear-gradient(135deg, var(--accent), var(--accent2));
  color: white;
  border: none;
  border-radius: 10px;
  padding: 14px;
  font-size: 15px;
  font-weight: 600;
  cursor: pointer;
  margin-top: 10px;
}
.btn-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 0 25px rgba(99,120,255,0.4);
}
</style>
</head>
<body>

<!-- [All your existing HTML content remains the same until the New Proposal button] -->

    <div class="topbar-right">
      <div class="chain-badge">
        <span class="chain-dot"></span>
        <span class="chain-name"><?= htmlspecialchars($chain_name) ?></span>
      </div>
      <button class="btn-proposal" id="newProposalBtn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="12" y1="5" x2="12" y2="19"/>
          <line x1="5" y1="12" x2="19" y2="12"/>
        </svg>
        <span>New Proposal</span>
      </button>
    </div>

<!-- NEW PROPOSAL MODAL -->
<div class="modal" id="proposalModal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Create New Proposal</div>
      <button class="close-modal" id="closeModal">×</button>
    </div>
    <div class="modal-body">
      <form id="proposalForm">
        <div class="form-group">
          <label>Project Name</label>
          <input type="text" id="projectName" placeholder="e.g. Website Redesign v2" required>
        </div>

        <div class="form-group">
          <label>Duration (in weeks)</label>
          <input type="number" id="duration" placeholder="12" min="1" required>
        </div>

        <div class="form-group">
          <label>Budget (USD)</label>
          <input type="number" id="budget" placeholder="15000" min="100" step="100" required>
        </div>

        <div class="form-group">
          <label>Milestone 1 (35%)</label>
          <input type="text" id="milestone1" readonly>
        </div>

        <div class="form-group">
          <label>Milestone 2 (35%)</label>
          <input type="text" id="milestone2" readonly>
        </div>

        <div class="form-group">
          <label>Milestone 3 (30%)</label>
          <input type="text" id="milestone3" readonly>
        </div>

        <button type="submit" class="btn-submit">Submit Proposal</button>
      </form>
    </div>
  </div>
</div>

<!-- [Rest of your existing content remains unchanged] -->

<script>
// ── Existing scripts... ──────────────────────────────────────

// New Proposal Modal
const modal = document.getElementById('proposalModal');
const openBtn = document.getElementById('newProposalBtn');
const closeBtn = document.getElementById('closeModal');

openBtn.addEventListener('click', () => {
  modal.style.display = 'flex';
  document.getElementById('projectName').focus();
});

closeBtn.addEventListener('click', () => {
  modal.style.display = 'none';
});

// Close modal when clicking outside
modal.addEventListener('click', (e) => {
  if (e.target === modal) modal.style.display = 'none';
});

// Auto-calculate milestones
const budgetInput = document.getElementById('budget');
const ms1 = document.getElementById('milestone1');
const ms2 = document.getElementById('milestone2');
const ms3 = document.getElementById('milestone3');

budgetInput.addEventListener('input', () => {
  const budget = parseFloat(budgetInput.value);
  
  if (!isNaN(budget) && budget > 0) {
    const m1 = (budget * 0.35).toFixed(2);
    const m2 = (budget * 0.35).toFixed(2);
    const m3 = (budget * 0.30).toFixed(2);
    
    ms1.value = '$' + m1;
    ms2.value = '$' + m2;
    ms3.value = '$' + m3;
  } else {
    ms1.value = '';
    ms2.value = '';
    ms3.value = '';
  }
});

// Form submission (you can expand this later)
document.getElementById('proposalForm').addEventListener('submit', (e) => {
  e.preventDefault();
  alert('✅ Proposal submitted successfully!\n\n(This is a demo. Connect backend as needed.)');
  modal.style.display = 'none';
  document.getElementById('proposalForm').reset();
});

// ── Your existing scripts (sidebar, balance refresh, etc.) ─────
window.addEventListener('load', () => {
  document.querySelectorAll('.progress-fill').forEach(el => {
    const w = el.style.width; el.style.width = '0';
    setTimeout(() => { el.style.width = w; }, 300);
  });
  refreshBalance();
  setInterval(refreshBalance, 60000);
});

async function refreshBalance() {
  // ... your existing refreshBalance function ...
}
</script>
</body>
</html>