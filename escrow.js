const API = "https://dev.api.trustlesswork.com";
const API_KEY =
  "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF4dWJmYmVtemtldGh0eHhxeXBuIiwicm9sZSI6ImFub24iLCJpYXQiOjE3MzA0MDc4MDUsImV4cCI6MjA0NTk4MzgwNX0.ummT4NCZFHRYpMbKMxb34rXYXDQfcWMZpAuEPLyF_4M";

// Hardcoded dispute resolver — your own Freighter wallet address
const DISPUTE_RESOLVER =
  "GBVDCDOKM6B33HFWXCIW3HX5KNXGBBP6DPWPX7AMKZDRWJKDG2VIM3BR";

async function signAndSubmit(xdr) {
  try {
    const signed = await window.freighterSDK.signTransaction(xdr, {
      network: "TESTNET",
    });

    const response = await fetch(`${API}/helper/send-transaction`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify({
        signed_tx_xdr: signed.signedTxXdr,
      }),
    });

    return response.json();
  } catch (err) {
    console.error("Sign and submit failed:", err);
    throw err;
  }
}

async function createEscrow(data) {
  try {
    const response = await fetch(`${API}/deployer/multi-release`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify(data),
    });

    const result = await response.json();
    console.log("Escrow deploy result:", result);

    if (!result.unsigned_tx_xdr) {
      throw new Error(result.message || "Failed to get unsigned transaction");
    }

    const signed = await signAndSubmit(result.unsigned_tx_xdr);

    return {
      escrowId: result.escrow_id,
      tx: signed,
    };
  } catch (err) {
    console.error("createEscrow failed:", err);
    throw err;
  }
}

async function approveMilestone(escrowId, milestoneIndex, approverWallet) {
  const response = await fetch(
    `${API}/escrow/multi-release/approve-milestone`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify({
        escrow_id: escrowId,
        milestone_index: milestoneIndex,
        approver: approverWallet,
      }),
    }
  );

  const result = await response.json();
  if (!result.unsigned_tx_xdr)
    throw new Error(result.message || "Approve failed");
  return await signAndSubmit(result.unsigned_tx_xdr);
}

async function releaseFunds(escrowId, milestoneIndex, callerWallet) {
  const response = await fetch(
    `${API}/escrow/multi-release/release-milestone-funds`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify({
        escrow_id: escrowId,
        milestone_index: milestoneIndex,
        caller: callerWallet,
      }),
    }
  );

  const result = await response.json();
  if (!result.unsigned_tx_xdr)
    throw new Error(result.message || "Release failed");
  return await signAndSubmit(result.unsigned_tx_xdr);
}

async function raiseDispute(escrowId, milestoneIndex, callerWallet) {
  const response = await fetch(
    `${API}/escrow/multi-release/dispute-milestone`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify({
        escrow_id: escrowId,
        milestone_index: milestoneIndex,
        caller: callerWallet,
      }),
    }
  );

  const result = await response.json();
  if (!result.unsigned_tx_xdr)
    throw new Error(result.message || "Dispute failed");
  return await signAndSubmit(result.unsigned_tx_xdr);
}

async function resolveDispute(escrowId, milestoneIndex, winner) {
  const response = await fetch(
    `${API}/escrow/multi-release/resolve-milestone-dispute`,
    {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${API_KEY}`,
      },
      body: JSON.stringify({
        escrow_id: escrowId,
        milestone_index: milestoneIndex,
        resolver: DISPUTE_RESOLVER,
        winner: winner, // "client" or "serviceProvider"
      }),
    }
  );

  const result = await response.json();
  if (!result.unsigned_tx_xdr)
    throw new Error(result.message || "Resolve failed");
  return await signAndSubmit(result.unsigned_tx_xdr);
}
