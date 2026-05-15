<?php
session_start();
include 'db.php';

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
<title>Vouch — Proposals</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="dashboard.css">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<!-- Ethers v6 for live balance refresh -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ethers/6.7.0/ethers.umd.min.js"></script>
<style>
.proposal-table-card{
    background:#081226;
    border:1px solid rgba(99,120,255,0.15);
    border-radius:18px;
    padding:20px;
    margin-top:20px;
    box-shadow:0 0 20px rgba(0,0,0,0.25);
}

.table-header h3{
    color:#fff;
    font-size:20px;
    margin-bottom:20px;
}

.table-wrapper{
    overflow-x:auto;
}

.proposal-table{
    width:100%;
    border-collapse:collapse;
    color:#cfd8ff;
}

.proposal-table thead{
    background:#0c1832;
}

.proposal-table th,
.proposal-table td{
    padding:14px 16px;
    text-align:left;
    border-bottom:1px solid rgba(255,255,255,0.05);
    font-size:14px;
}

.proposal-table tr:hover{
    background:rgba(99,120,255,0.05);
}

.status-pill{
    padding:6px 12px;
    border-radius:20px;
    font-size:12px;
    font-weight:600;
}

.status-pill.active{
    background:rgba(16,185,129,0.15);
    color:#10b981;
}

.status-pill.pending{
    background:rgba(245,158,11,0.15);
    color:#f59e0b;
}

.status-pill.dispute{
    background:rgba(239,68,68,0.15);
    color:#ef4444;
}

.menu-btn{
    background:none;
    border:none;
    color:#fff;
    font-size:22px;
    cursor:pointer;
}

.dropdown{
    position:relative;
}

.dropdown-content{
    display:none;
    position:absolute;
    right:0;
    top:30px;
    background:#101c3a;
    min-width:140px;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,0.35);
    z-index:999;
}

.dropdown-content a{
    display:block;
    padding:10px 14px;
    color:#cfd8ff;
    text-decoration:none;
    font-size:14px;
}

.dropdown-content a:hover{
    background:#18284d;
}
.dropdown {
    position: relative;
    display: inline-block;
}

.dropdown-content {
    display: none;
    position: absolute;
    right: 0;
    top: 32px;
    background: #0f172a;
    min-width: 150px;
    border-radius: 10px;
    border: 1px solid rgba(99,120,255,0.15);
    box-shadow: 0 10px 25px rgba(0,0,0,0.4);
    z-index: 9999;
    overflow: hidden;
}

.dropdown-content a {
    display: block;
    padding: 12px 15px;
    color: #cfd8ff;
    text-decoration: none;
    font-size: 14px;
}

.dropdown-content a:hover {
    background: rgba(99,120,255,0.08);
}
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
  <a class="nav-link" href="dashboard.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>Dashboard
  </a>
  <a class="nav-link active" href="proposal.php">
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
      <div class="topbar-title">Proposals</div>
    </div>
    <div class="topbar-right">
      <div class="chain-badge">
        <span class="chain-dot"></span>
        <span class="chain-name"><?= htmlspecialchars($chain_name) ?></span>
      </div>
      <button class="btn-proposal" id="newProposalBtn">
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

<!-- ==================== PROPOSAL MODAL ==================== -->
<div class="modal" id="proposalModal">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Create New Proposal</div>
      <button class="close-modal" id="closeModal">×</button>
    </div>
    <div class="modal-body">
      <form id="proposalForm" method="POST" action="submit_proposal.php">
        <input type="hidden" name="submit_proposal" value="1">

        <div class="form-group">
          <label>Wallet Name</label>
          <input type="text" id="walletName" name="wallet_name" value="<?= htmlspecialchars($wallet_name) ?>" readonly>
        </div>

        <div class="form-group">
          <label>Wallet Address</label>
          <input type="text" id="walletAddress" name="wallet_address" value="<?= htmlspecialchars($wallet_address) ?>" readonly>
        </div>
      
        <div class="form-group">
          <label>Project Name</label>
          <input type="text" name="project_name" id="projectName" placeholder="e.g. Website Redesign v2" required>
        </div>

        <div class="form-group">
          <label>Duration (in weeks)</label>
          <input type="number" name="duration" id="duration" placeholder="12" min="1" required>
        </div>

        <div class="form-group">
          <label>Budget (USD)</label>
          <input type="number" name="budget" id="budget" placeholder="15000" min="100" step="100" required>
        </div>

        <div class="form-group">
          <label>Milestone 1 (35%)</label>
          <input type="text" id="milestone1" readonly>
          <input type="hidden" name="milestone1" id="hidden_m1">
        </div>

        <div class="form-group">
          <label>Milestone 2 (35%)</label>
          <input type="text" id="milestone2" readonly>
          <input type="hidden" name="milestone2" id="hidden_m2">
        </div>

        <div class="form-group">
          <label>Milestone 3 (30%)</label>
          <input type="text" id="milestone3" readonly>
          <input type="hidden" name="milestone3" id="hidden_m3">
        </div>

        <button type="submit" class="btn-submit">Submit Proposal</button>
      </form>
    </div>
  </div>
</div>

<div class="proposal-table-card">
    <div class="table-header">
        <h3>All Proposals</h3>
    </div>

    <div class="table-wrapper">
        <table class="proposal-table">
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Duration</th>
                    <th>Budget</th>
                    <th>Milestone 1</th>
                    <th>Milestone 2</th>
                    <th>Milestone 3</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>

                <?php
                $stmt = $conn->prepare("SELECT * FROM proposal WHERE wallet_address = ? ORDER BY created_at DESC");
                $stmt->bind_param("s", $wallet_address);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $id = (int)$row['proposal_id'];
                     $budget     = floatval(str_replace('$', '', $row['budget']));
                    $milestone1 = floatval(str_replace('$', '', $row['milestone1']));
                    $milestone2 = floatval(str_replace('$', '', $row['milestone2']));
                    $milestone3 = floatval(str_replace('$', '', $row['milestone3']));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['project_name']) ?></td>
                        <td><?= $row['duration'] ?> week(s)</td>
                        <td>$<?= number_format($budget, 2) ?></td>
                        <td>$<?= number_format($milestone1, 2) ?></td>
                        <td>$<?= number_format($milestone2, 2) ?></td>
                        <td>$<?= number_format($milestone3, 2) ?></td>

                        <td>
                            <span class="status-pill <?= strtolower($row['status'] ?? 'pending') ?>">
                                <?= ucfirst($row['status'] ?? 'pending') ?>
                            </span>
                        </td>

                        <td><?= date("M d, Y", strtotime($row['created_at'])) ?></td>

<td>
<?php
    $id = (int)$row['proposal_id'];

    $active_url  = "update_status.php?id=" . $id . "&status=active";
    $pending_url = "update_status.php?id=" . $id . "&status=pending";
    $dispute_url = "update_status.php?id=" . $id . "&status=dispute";
    $delete_url  = "delete_proposal.php?id=" . $id;
?>
    <div class="dropdown">
        <button type="button" class="menu-btn" onclick="toggleMenu(this)">⋮</button>

        <div class="dropdown-content">
            <a href="<?php echo $active_url; ?>">Active</a>
            <a href="<?php echo $pending_url; ?>">Pending</a>
            <a href="<?php echo $dispute_url; ?>">Dispute</a>
            <a href="<?php echo $delete_url; ?>" onclick="return confirm('Delete this proposal?')">Delete</a>
        </div>
    </div>
</td>
                    </tr>
                    <?php
                }

                $stmt->close();
                ?>

            </tbody>
        </table>
    </div>
</div>

    <!-- MILESTONE DETAILS TABLE -->
    <div class="panel" style="margin-bottom:24px">
      <div class="panel-header"><div class="panel-title">Milestone Details</div></div>
      <div class="table-wrap">

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


document.getElementById('budget').addEventListener('input', function() {
    const budget = parseFloat(this.value);
    if (!isNaN(budget) && budget > 0) {
        const milestone1Amount = (budget * 0.35).toFixed(2);
        const milestone2Amount = (budget * 0.35).toFixed(2);
        const milestone3Amount = (budget * 0.30).toFixed(2);
        
        // Format milestone text
        const milestone1Text = `$${milestone1Amount}`;
        const milestone2Text = `$${milestone2Amount}`;
        const milestone3Text = `$${milestone3Amount}`;
        
        // Set display values
        document.getElementById('milestone1').value = milestone1Text;
        document.getElementById('milestone2').value = milestone2Text;
        document.getElementById('milestone3').value = milestone3Text;
        
        // Set hidden values for submission
        document.getElementById('hidden_m1').value = milestone1Text;
        document.getElementById('hidden_m2').value = milestone2Text;
        document.getElementById('hidden_m3').value = milestone3Text;
    }
});

// Trigger calculation on page load if budget is pre-filled
document.addEventListener('DOMContentLoaded', function() {
    const budgetInput = document.getElementById('budget');
    if (budgetInput.value) {
        const event = new Event('input');
        budgetInput.dispatchEvent(event);
    }
});

function toggleMenu(button) {
    let menu = button.nextElementSibling;

    document.querySelectorAll('.dropdown-content').forEach(drop => {
        if (drop !== menu) {
            drop.style.display = 'none';
        }
    });

    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

window.onclick = function(e) {
    if (!e.target.matches('.menu-btn')) {
        document.querySelectorAll('.dropdown-content').forEach(drop => {
            drop.style.display = 'none';
        });
    }
}
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