<?php
session_start();
if (!empty($_SESSION['wallet_address'])) {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vouch — DAO Governance Platform</title>

<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>
:root{
  --bg:#050810;--surface:#0c1022;--surface2:#111829;
  --border:rgba(99,120,255,0.15);--accent:#6378ff;--accent2:#a78bfa;
  --gold:#f4c430;--text:#e8eaf6;--muted:#6b7a9e;
  --danger:#ff5c5c;--success:#22d3a0;
}
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'DM Sans',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;overflow-x:hidden;}
.bg-canvas{position:fixed;inset:0;z-index:0;
  background:radial-gradient(ellipse 80% 60% at 20% 10%,rgba(99,120,255,0.13) 0%,transparent 60%),
             radial-gradient(ellipse 60% 50% at 80% 80%,rgba(167,139,250,0.10) 0%,transparent 55%),
             var(--bg);}
.grid-overlay{position:fixed;inset:0;z-index:0;
  background-image:linear-gradient(rgba(99,120,255,0.04) 1px,transparent 1px),
                   linear-gradient(90deg,rgba(99,120,255,0.04) 1px,transparent 1px);
  background-size:60px 60px;}
.orb{position:fixed;border-radius:50%;filter:blur(80px);z-index:0;animation:floatOrb 12s ease-in-out infinite;}
.orb-1{width:400px;height:400px;top:-100px;left:-100px;background:rgba(99,120,255,0.08);}
.orb-2{width:300px;height:300px;bottom:-80px;right:-80px;background:rgba(167,139,250,0.07);animation-delay:-4s;}
@keyframes floatOrb{0%,100%{transform:translate(0,0) scale(1);}50%{transform:translate(20px,-15px) scale(1.04);}}

nav{position:relative;z-index:10;display:flex;align-items:center;justify-content:space-between;padding:24px 40px;}
.nav-logo{display:flex;align-items:center;gap:10px;font-family:'Syne',sans-serif;font-size:22px;font-weight:800;color:var(--text);text-decoration:none;}
.logo-mark{width:36px;height:36px;border-radius:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:#fff;box-shadow:0 0 20px rgba(99,120,255,0.4);}
.btn-connect{display:flex;align-items:center;gap:8px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:12px;padding:11px 22px;font-family:'DM Sans',sans-serif;font-size:14px;font-weight:500;cursor:pointer;box-shadow:0 0 24px rgba(99,120,255,0.35);transition:all 0.25s ease;}
.btn-connect:hover{transform:translateY(-1px);box-shadow:0 0 36px rgba(99,120,255,0.5);}

.hero{position:relative;z-index:5;min-height:calc(100vh - 84px);display:flex;align-items:center;justify-content:center;padding:40px 20px;text-align:center;}
.hero-inner{max-width:720px;}
.hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(99,120,255,0.1);border:1px solid var(--border);border-radius:100px;padding:6px 16px;margin-bottom:28px;font-size:12px;font-weight:500;color:var(--accent2);letter-spacing:0.5px;animation:fadeUp 0.6s ease both;}
.hero-badge .dot{width:6px;height:6px;border-radius:50%;background:var(--success);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:0.6;transform:scale(1.3)}}
.hero-title{font-family:'Syne',sans-serif;font-size:clamp(42px,7vw,76px);font-weight:800;line-height:1.05;letter-spacing:-2px;margin-bottom:20px;animation:fadeUp 0.6s ease 0.1s both;}
.hero-title .line-accent{background:linear-gradient(90deg,var(--accent),var(--accent2),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
.hero-sub{font-size:17px;color:var(--muted);line-height:1.7;max-width:500px;margin:0 auto 40px;animation:fadeUp 0.6s ease 0.2s both;}
.hero-cta{display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap;animation:fadeUp 0.6s ease 0.3s both;}
.btn-primary{display:flex;align-items:center;gap:10px;background:linear-gradient(135deg,var(--accent),var(--accent2));color:#fff;border:none;border-radius:14px;padding:16px 32px;font-family:'DM Sans',sans-serif;font-size:16px;font-weight:600;cursor:pointer;box-shadow:0 0 40px rgba(99,120,255,0.4);transition:all 0.3s ease;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 0 60px rgba(99,120,255,0.55);}
.btn-ghost{display:flex;align-items:center;gap:8px;background:transparent;color:var(--muted);border:1px solid var(--border);border-radius:14px;padding:15px 28px;font-family:'DM Sans',sans-serif;font-size:15px;cursor:pointer;transition:all 0.25s ease;}
.btn-ghost:hover{border-color:var(--accent);color:var(--text);background:rgba(99,120,255,0.06);}
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}

/* floating stat cards */
.float-card{position:absolute;background:rgba(12,16,34,0.85);border:1px solid var(--border);border-radius:16px;padding:14px 18px;backdrop-filter:blur(12px);animation:floatCard 8s ease-in-out infinite;display:flex;align-items:center;gap:10px;z-index:3;}
.float-card .fc-icon{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0;}
.float-card .fc-label{font-size:11px;color:var(--muted);}
.float-card .fc-val{font-family:'Syne',sans-serif;font-size:15px;font-weight:700;}
.float-card-1{top:18%;left:4%;animation-delay:0s;}
.float-card-2{top:18%;right:3%;animation-delay:-3s;}
.float-card-3{bottom:22%;left:5%;animation-delay:-6s;}
.float-card-4{bottom:22%;right:4%;animation-delay:-1.5s;}
@keyframes floatCard{0%,100%{transform:translateY(0);}50%{transform:translateY(-12px);}}

/* ── WALLET MODAL ── */
.modal-overlay{position:fixed;inset:0;z-index:100;background:rgba(5,8,16,0.88);backdrop-filter:blur(8px);display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity 0.3s ease;}
.modal-overlay.active{opacity:1;pointer-events:all;}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:24px;padding:32px;width:100%;max-width:440px;transform:translateY(20px) scale(0.97);transition:transform 0.3s ease;box-shadow:0 40px 100px rgba(0,0,0,0.6);}
.modal-overlay.active .modal{transform:translateY(0) scale(1);}
.modal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;}
.modal-title{font-family:'Syne',sans-serif;font-size:22px;font-weight:700;}
.modal-close{width:36px;height:36px;border-radius:10px;background:var(--surface2);border:none;cursor:pointer;color:var(--muted);font-size:18px;display:flex;align-items:center;justify-content:center;transition:all 0.2s;}
.modal-close:hover{background:rgba(99,120,255,0.1);color:var(--text);}
.modal-sub{font-size:14px;color:var(--muted);margin-bottom:24px;}
.wallet-list{display:flex;flex-direction:column;gap:10px;}
.wallet-item{display:flex;align-items:center;gap:14px;padding:14px 16px;border-radius:14px;background:var(--surface2);border:1px solid transparent;cursor:pointer;transition:all 0.2s ease;}
.wallet-item:hover{border-color:var(--accent);background:rgba(99,120,255,0.07);transform:translateX(3px);}
.wallet-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;}
.wallet-info{flex:1;}
.wallet-name{font-family:'Syne',sans-serif;font-size:15px;font-weight:600;}
.wallet-tag{font-size:11px;color:var(--muted);margin-top:1px;}
.wallet-arrow{color:var(--muted);font-size:18px;transition:transform 0.2s;}
.wallet-item:hover .wallet-arrow{transform:translateX(4px);color:var(--accent);}
.wallet-popular{font-size:10px;background:rgba(99,120,255,0.15);color:var(--accent);border-radius:6px;padding:2px 7px;margin-left:8px;}
.divider{display:flex;align-items:center;gap:12px;margin:16px 0;}
.divider span{font-size:12px;color:var(--muted);white-space:nowrap;}
.divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}
.modal-footer{text-align:center;font-size:12px;color:var(--muted);}
.modal-footer a{color:var(--accent);text-decoration:none;}

/* ── CONNECTING SCREEN ── */
.connecting-screen{position:fixed;inset:0;z-index:200;background:var(--bg);display:none;flex-direction:column;align-items:center;justify-content:center;gap:20px;}
.connecting-screen.show{display:flex;}
.spinner{width:56px;height:56px;border-radius:50%;border:3px solid var(--border);border-top-color:var(--accent);animation:spin 0.9s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
.connecting-label{font-family:'Syne',sans-serif;font-size:18px;font-weight:600;}
.connecting-sub{font-size:14px;color:var(--muted);}
.connecting-addr{font-size:12px;color:var(--accent);font-family:monospace;margin-top:4px;}

/* toast */
.toast{position:fixed;bottom:30px;left:50%;transform:translateX(-50%) translateY(20px);background:#160808;border:1px solid rgba(255,92,92,0.3);color:var(--danger);border-radius:12px;padding:12px 24px;font-size:14px;z-index:300;opacity:0;transition:all 0.3s ease;pointer-events:none;max-width:90vw;text-align:center;}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
.toast.success-toast{background:#061610;border-color:rgba(34,211,160,0.3);color:var(--success);}

@media(max-width:768px){
  nav{padding:18px 20px;}
  .float-card-1,.float-card-3{display:none;}
  .hero-title{letter-spacing:-1px;}
}
@media(max-width:480px){
  .btn-primary{width:100%;justify-content:center;}
  .hero-cta{flex-direction:column;}
  .float-card-2,.float-card-4{display:none;}
  nav .nav-logo span{display:none;}
}
</style>

<<script type="module">
  import {
    isConnected,
    requestAccess,
    getAddress,
    signTransaction
  } from 'https://esm.sh/@stellar/freighter-api@2.1.1';

  // Wait for full load before attaching to window
  window.addEventListener('load', () => {
    window.freighterSDK = { 
      isConnected, 
      requestAccess, 
      getAddress, 
      signTransaction 
    };
    console.log("Freighter SDK loaded ✅");
  });
</script>
</head>

<body>

<nav>
  <a class="nav-logo"><div class="logo-mark">V</div>Vouch</a>
  <button class="btn-connect" onclick="openModal()">Connect Wallet</button>
</nav>

<!-- WALLET MODAL -->
<div class="modal-overlay" id="walletModal">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title">Connect Wallet</div>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <p class="modal-sub">Connect Freighter wallet to continue</p>

    <div class="wallet-list">
      <div class="wallet-item" onclick="connectFreighter()">
        <div class="wallet-icon">⭐</div>
        <div class="wallet-info">
          <div class="wallet-name">Freighter Wallet</div>
          <div class="wallet-tag">Stellar wallet</div>
        </div>
        <div class="wallet-arrow">›</div>
      </div>
    </div>
  </div>
</div>

<!-- CONNECTING SCREEN -->
<div class="connecting-screen" id="connectingScreen">
  <div class="spinner"></div>
  <div class="connecting-label">Connecting...</div>
</div>

<!-- FORM -->
<form id="sessionForm" method="POST" action="connect.php" style="display:none">
  <input name="wallet_address" id="f_address">
  <input name="wallet_name" id="f_name">
  <input name="chain_id" id="f_chain">
</form>

<script>
function openModal(){
  document.getElementById('walletModal').classList.add('active');
}
function closeModal(){
  document.getElementById('walletModal').classList.remove('active');
}

function showConnecting(){
  document.getElementById('connectingScreen').classList.add('show');
}
function hideConnecting(){
  document.getElementById('connectingScreen').classList.remove('show');
}

async function connectFreighter() {
  try {
    // Wait for SDK to load
    if (!window.freighterSDK) {
      alert("Still loading, please try again in 2 seconds");
      return;
    }

    showConnecting();

    const connected = await window.freighterSDK.isConnected();
    
    if (!connected.isConnected) {
      window.open('https://www.freighter.app/', '_blank');
      hideConnecting();
      alert("Please install Freighter wallet extension first");
      return;
    }

    await window.freighterSDK.requestAccess();

    const addressResult = await window.freighterSDK.getAddress();
    const address = addressResult.address || addressResult;

    if (!address) {
      hideConnecting();
      alert("Could not get wallet address. Make sure Freighter is unlocked.");
      return;
    }

    document.getElementById('f_address').value = address;
    document.getElementById('f_name').value = "Freighter";
    document.getElementById('f_chain').value = "stellar-testnet";

    document.getElementById('sessionForm').submit();

  } catch (e) {
    hideConnecting();
    console.error(e);
    alert("Error: " + e.message);
  }
}
</script>

</body>
</html>