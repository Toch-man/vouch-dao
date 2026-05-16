<?php
session_start();
include 'db.php';

if (empty($_SESSION['wallet_address'])) {
    header('Location: index.php');
    exit;
}

$wallet_address = $_SESSION['wallet_address'];
$wallet_name    = $_SESSION['wallet_name'] ?? 'Wallet';
$xlm_balance    = (float)($_SESSION['xlm_balance'] ?? 0);
$usd_balance    = (float)($_SESSION['usd_balance'] ?? 0);
$pending_funds  = $usd_balance * 0.336;
$released_funds = $usd_balance * 0.647;
$short_address  = substr($wallet_address, 0, 6) . '...' . substr($wallet_address, -4);
?>
<!DOCTYPE html>
<html>
<head>
<title>Dashboard — Vouch</title>
<link rel="stylesheet" href="dashboard.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/stellar-sdk/13.3.0/stellar-sdk.min.js"></script>
<script type="module">
  import { isConnected, requestAccess, getAddress, signTransaction }
    from 'https://esm.sh/@stellar/freighter-api';
  window.freighterSDK = { isConnected, requestAccess, getAddress, signTransaction };
</script>
</head>
<body>
<div class="app">

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
  <a href="index.php" class="sidebar-logo">
    <div class="logo-mark">V</div>Vouch
  </a>
  <div class="sidebar-section">Menu</div>
  <a class="nav-link active" href="dashboard.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <rect x="3" y="3" width="7" height="7" rx="1"/>
      <rect x="14" y="3" width="7" height="7" rx="1"/>
      <rect x="3" y="14" width="7" height="7" rx="1"/>
      <rect x="14" y="14" width="7" height="7" rx="1"/>
    </svg>Dashboard
  </a>
  <a class="nav-link" href="proposal.php">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
      <rect x="9" y="3" width="6" height="4" rx="1"/>
    </svg>Proposals
  </a>
  <div class="sidebar-section">Community</div>
  <a class="nav-link" href="#">Contributors</a>
  <a class="nav-link" href="#">Reports</a>
  <a class="nav-link" href="#">Settings</a>
  <div class="sidebar-spacer"></div>
  <div class="sidebar-wallet">
    <div class="sw-label">Connected Wallet</div>
    <div class="sw-address"><?= htmlspecialchars($short_address) ?></div>
    <div class="sw-chain"><?= htmlspecialchars($wallet_name) ?></div>
    <div class="sw-eth" id="sidebarEth"><?= number_format($xlm_balance, 4) ?> XLM</div>
    <a href="disconnect.php" class="btn-disconnect">Disconnect</a>
  </div>
</aside>

<!-- MAIN -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <button class="hamburger" onclick="document.getElementById('sidebar').classList.add('open')">☰</button>
      <div class="topbar-title">Dashboard</div>
    </div>
    <div class="topbar-right">
      <div class="chain-badge">
        <span class="chain-dot"></span>
        <span class="chain-name">Stellar Testnet</span>
      </div>
      <button class="btn-proposal" id="newProposalBtn">+ New Proposal</button>
    </div>
  </header>

  <div class="content">
    <div class="welcome">
      Welcome back, <strong><?= htmlspecialchars($wallet_name) ?></strong>!
    </div>

    <!-- STAT CARDS -->
    <div class="stat-grid">
      <div class="stat-card" style="--card-accent:var(--accent)">
        <div class="sc-label">Total Funds <span class="live-badge"><span class="live-dot"></span>Live</span></div>
        <div class="sc-value" id="totalFunds">$<?= number_format($usd_balance, 2) ?></div>
        <div class="sc-sub"><span id="ethDisplay"><?= number_format($xlm_balance, 4) ?> XLM</span></div>
        <div class="sc-icon">💎</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--warning)">
        <div class="sc-label">Pending Funds</div>
        <div class="sc-value">$<?= number_format($pending_funds, 2) ?></div>
        <div class="sc-sub"><span class="sc-badge warn">Awaiting approval</span></div>
        <div class="sc-icon">⏳</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--success)">
        <div class="sc-label">Released Funds</div>
        <div class="sc-value">$<?= number_format($released_funds, 2) ?></div>
        <div class="sc-sub"><span class="sc-badge up">✓ Released</span></div>
        <div class="sc-icon">✅</div>
      </div>
      <div class="stat-card" style="--card-accent:var(--accent2)">
        <div class="sc-label">Active Milestones</div>
        <div class="sc-value">
          <?php
            $mstmt = $conn->prepare("SELECT COUNT(*) as cnt FROM proposal WHERE wallet_address = ? AND status = 'active'");
            $mstmt->bind_param("s", $wallet_address);
            $mstmt->execute();
            echo $mstmt->get_result()->fetch_assoc()['cnt'];
            $mstmt->close();
          ?>
        </div>
        <div class="sc-icon">🚩</div>
      </div>
    </div>

    <!-- MILESTONE PROGRESS -->
    <div class="panel">
      <div class="panel-header">
        <div class="panel-title">Milestone Progress</div>
        <a href="proposal.php" class="panel-action">View all</a>
      </div>
      <?php
        $stmt = $conn->prepare("SELECT * FROM proposal WHERE wallet_address = ? ORDER BY created_at DESC LIMIT 5");
        $stmt->bind_param("s", $wallet_address);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = 1;
        while ($row = $result->fetch_assoc()):
          $progress = $row['status'] === 'active' ? 75 : ($row['status'] === 'dispute' ? 40 : 20);
          $due = date('M d, Y', strtotime($row['created_at'] . ' +' . $row['duration'] . ' weeks'));
      ?>
        <div class="milestone-item">
          <div class="mi-top">
            <div class="mi-num"><?= $count ?></div>
            <div class="mi-info">
              <div class="mi-name"><?= htmlspecialchars($row['project_name']) ?></div>
              <div class="mi-desc"><?= $row['duration'] ?> week(s)</div>
            </div>
            <div class="mi-right">
              <div class="mi-due"><?= $due ?></div>
              <span class="status-badge <?= $row['status'] === 'active' ? 'status-on-track' : 'status-pending' ?>">
                <?= ucfirst($row['status']) ?>
              </span>
            </div>
          </div>
          <div class="progress-bar">
            <div class="progress-fill" style="width:<?= $progress ?>%"></div>
          </div>
          <div class="mi-budget"><?= $progress ?>% · Budget: $<?= number_format((float)$row['budget'], 2) ?></div>

          <!-- ESCROW ACTIONS -->
          <?php if (!empty($row['escrow_id'])): ?>
          <div style="display:flex;gap:8px;margin-top:10px;flex-wrap:wrap;">
            <button
              onclick="handleApprove('<?= $row['escrow_id'] ?>', 0)"
              style="background:#22d3a0;color:#000;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600">
              ✅ Approve M1
            </button>
            <button
              onclick="handleRelease('<?= $row['escrow_id'] ?>', 0)"
              style="background:#6378ff;color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600">
              💸 Release M1
            </button>
            <button
              onclick="handleDispute('<?= $row['escrow_id'] ?>', 0)"
              style="background:#ff5c5c;color:#fff;border:none;padding:6px 14px;border-radius:8px;cursor:pointer;font-size:12px;font-weight:600">
              ⚠️ Dispute
            </button>
            <a href="https://viewer.trustlesswork.com/<?= $row['escrow_id'] ?>"
               target="_blank"
               style="background:#111829;color:#6378ff;border:1px solid #6378ff;padding:6px 14px;border-radius:8px;font-size:12px;text-decoration:none;">
              🔗 View on Chain
            </a>
          </div>
          <?php else: ?>
            <div style="margin-top:8px;font-size:11px;color:#555;">No escrow deployed yet</div>
          <?php endif; ?>
        </div>
      <?php $count++; endwhile; $stmt->close(); ?>
    </div>

    <!-- RECENT TRANSACTIONS -->
    <div class="panel" style="margin-top:20px">
      <div class="panel-header">
        <div class="panel-title">Recent Transactions</div>
      </div>
      <?php
        $tstmt = $conn->prepare("SELECT * FROM transactions WHERE wallet_address = ? ORDER BY created_at DESC LIMIT 5");
        $tstmt->bind_param("s", $wallet_address);
        $tstmt->execute();
        $tres = $tstmt->get_result();
        if ($tres->num_rows === 0):
      ?>
        <div style="padding:20px;color:#555;font-size:14px;">No transactions yet.</div>
      <?php else: while ($tx = $tres->fetch_assoc()): ?>
        <div class="tx-item">
          <div class="tx-icon">💰</div>
          <div class="tx-info">
            <div class="tx-name"><?= htmlspecialchars($tx['type']) ?></div>
            <div class="tx-desc"><?= date("M d, Y", strtotime($tx['created_at'])) ?></div>
          </div>
          <div class="tx-right">
            <div class="tx-amount" style="color:var(--success)">$<?= number_format($tx['amount'], 2) ?></div>
          </div>
        </div>
      <?php endwhile; endif; $tstmt->close(); ?>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="panel" style="margin-top:20px">
      <div class="panel-header"><div class="panel-title">Quick Actions</div></div>
      <div class="qa-grid">
        <div class="qa-btn" onclick="document.getElementById('proposalModal').style.display='flex'">
          <div class="qa-icon">📋</div>
          <div class="qa-label">Create Proposal</div>
        </div>
        <div class="qa-btn" onclick="alert('Go to a proposal and click Approve')">
          <div class="qa-icon">✅</div>
          <div class="qa-label">Approve Funds</div>
        </div>
        <div class="qa-btn" onclick="alert('Go to a proposal and click Dispute')">
          <div class="qa-icon">⚠️</div>
          <div class="qa-label">Raise Dispute</div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- PROPOSAL MODAL -->
<div class="modal" id="proposalModal" style="display:none">
  <div class="modal-content">
    <div class="modal-header">
      <div class="modal-title">Create New Proposal</div>
      <button class="close-modal" onclick="document.getElementById('proposalModal').style.display='none'">×</button>
    </div>
    <div class="modal-body">
      <div id="modalError" style="display:none;background:rgba(255,92,92,0.1);color:#ff5c5c;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px;"></div>
      <div id="modalSuccess" style="display:none;background:rgba(34,211,160,0.1);color:#22d3a0;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px;"></div>

      <div class="form-group">
        <label>Wallet Name</label>
        <input type="text" id="walletName" value="<?= htmlspecialchars($wallet_name) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Your Wallet Address (DAO Admin)</label>
        <input type="text" id="walletAddress" value="<?= htmlspecialchars($wallet_address) ?>" readonly>
      </div>
      <div class="form-group">
        <label>Contributor Wallet Address (Stellar G...)</label>
        <input type="text" id="contributorWallet" placeholder="G... contributor Stellar address" required>
      </div>
      <div class="form-group">
        <label>Project Name</label>
        <input type="text" id="projectName" placeholder="e.g. Website Redesign v2" required>
      </div>
      <div class="form-group">
        <label>Duration (weeks)</label>
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
      <button id="submitProposalBtn" class="btn-submit" style="width:100%;padding:14px;background:linear-gradient(135deg,#6378ff,#a78bfa);color:#fff;border:none;border-radius:12px;font-size:15px;font-weight:600;cursor:pointer;margin-top:8px;">
        🚀 Deploy Escrow & Submit
      </button>
    </div>
  </div>
</div>

<script src="escrow.js"></script>
<script>
// Balance refresh
async function refreshBalance() {
  try {
    const server = new StellarSdk.Horizon.Server("https://horizon-testnet.stellar.org");
    const account = await server.loadAccount("<?= $wallet_address ?>");
    const xlm = account.balances.find(b => b.asset_type === "native");
    const balance = xlm ? parseFloat(xlm.balance) : 0;
    document.getElementById("ethDisplay").textContent = balance.toFixed(4) + " XLM";
    document.getElementById("sidebarEth").textContent = balance.toFixed(4) + " XLM";
  } catch(e) { console.log("Balance fetch failed", e); }
}
refreshBalance();
setInterval(refreshBalance, 60000);

// Milestone auto-calculate
document.getElementById('budget').addEventListener('input', function() {
  const b = parseFloat(this.value);
  if (!isNaN(b) && b > 0) {
    document.getElementById('milestone1').value = '$' + (b * 0.35).toFixed(2);
    document.getElementById('milestone2').value = '$' + (b * 0.35).toFixed(2);
    document.getElementById('milestone3').value = '$' + (b * 0.30).toFixed(2);
  }
});

// Modal open
document.getElementById('newProposalBtn').onclick = () => {
  document.getElementById('proposalModal').style.display = 'flex';
};

// Submit proposal — deploy escrow first then save to DB
document.getElementById('submitProposalBtn').onclick = async () => {
  const btn             = document.getElementById('submitProposalBtn');
  const errorDiv        = document.getElementById('modalError');
  const successDiv      = document.getElementById('modalSuccess');
  const daoWallet       = document.getElementById('walletAddress').value;
  const contributorWallet = document.getElementById('contributorWallet').value.trim();
  const projectName     = document.getElementById('projectName').value.trim();
  const duration        = document.getElementById('duration').value;
  const budget          = parseFloat(document.getElementById('budget').value);
  const milestone1      = document.getElementById('milestone1').value;
  const milestone2      = document.getElementById('milestone2').value;
  const milestone3      = document.getElementById('milestone3').value;

  errorDiv.style.display   = 'none';
  successDiv.style.display = 'none';

  // Validate
  if (!contributorWallet.startsWith('G') || contributorWallet.length !== 56) {
    errorDiv.textContent = 'Please enter a valid Stellar contributor wallet (starts with G, 56 chars)';
    errorDiv.style.display = 'block';
    return;
  }
  if (!projectName || !budget || !duration) {
    errorDiv.textContent = 'Please fill all fields';
    errorDiv.style.display = 'block';
    return;
  }

  btn.textContent = '⏳ Deploying escrow on Stellar...';
  btn.disabled = true;

  try {
    // 1. Deploy escrow on Stellar via Trustless Work
    const escrowResult = await createEscrow({
      client: daoWallet,
      serviceProvider: contributorWallet,
      disputeResolver: DISPUTE_RESOLVER,
      milestones: [
        { description: projectName + " - Milestone 1", amount: String(budget * 0.35) },
        { description: projectName + " - Milestone 2", amount: String(budget * 0.35) },
        { description: projectName + " - Milestone 3", amount: String(budget * 0.30) },
      ],
      asset: "USDC",
      platformAddress: daoWallet,
      platformFee: "0"
    });

    btn.textContent = '💾 Saving to database...';

    // 2. Save to PHP/MySQL
    const formData = new FormData();
    formData.append('submit_proposal', '1');
    formData.append('wallet_name', '<?= htmlspecialchars($wallet_name) ?>');
    formData.append('wallet_address', daoWallet);
    formData.append('contributor_wallet', contributorWallet);
    formData.append('resolver_wallet', DISPUTE_RESOLVER);
    formData.append('escrow_id', escrowResult.escrowId);
    formData.append('project_name', projectName);
    formData.append('duration', duration);
    formData.append('budget', budget);
    formData.append('milestone1', milestone1);
    formData.append('milestone2', milestone2);
    formData.append('milestone3', milestone3);

    const res = await fetch('submit_proposal.php', {
      method: 'POST',
      body: formData
    });

    successDiv.textContent = '✅ Escrow deployed on Stellar! ID: ' + escrowResult.escrowId;
    successDiv.style.display = 'block';

    setTimeout(() => {
      document.getElementById('proposalModal').style.display = 'none';
      window.location.reload();
    }, 2000);

  } catch (err) {
    errorDiv.textContent = 'Error: ' + err.message;
    errorDiv.style.display = 'block';
    btn.textContent = '🚀 Deploy Escrow & Submit';
    btn.disabled = false;
  }
};

// Escrow action handlers
async function handleApprove(escrowId, milestoneIndex) {
  if (!confirm('Approve milestone ' + (milestoneIndex + 1) + '?')) return;
  try {
    await approveMilestone(escrowId, milestoneIndex, "<?= $wallet_address ?>");
    alert('✅ Milestone approved!');
    window.location.reload();
  } catch(e) { alert('Error: ' + e.message); }
}

async function handleRelease(escrowId, milestoneIndex) {
  if (!confirm('Release funds for milestone ' + (milestoneIndex + 1) + '?')) return;
  try {
    await releaseFunds(escrowId, milestoneIndex, "<?= $wallet_address ?>");
    alert('💸 Funds released!');
    window.location.reload();
  } catch(e) { alert('Error: ' + e.message); }
}

async function handleDispute(escrowId, milestoneIndex) {
  if (!confirm('Raise dispute for milestone ' + (milestoneIndex + 1) + '?')) return;
  try {
    await raiseDispute(escrowId, milestoneIndex, "<?= $wallet_address ?>");
    alert('⚠️ Dispute raised!');
    window.location.reload();
  } catch(e) { alert('Error: ' + e.message); }
}
</script>
</body>
</html>