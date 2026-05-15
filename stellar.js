const API = "https://dev.api.trustlesswork.com";

const API_KEY = "YOUR_API_KEY";

async function signAndSubmit(xdr) {
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
}

async function createEscrow(data) {
  const response = await fetch(`${API}/deployer/multi-release`, {
    method: "POST",

    headers: {
      "Content-Type": "application/json",
      Authorization: `Bearer ${API_KEY}`,
    },

    body: JSON.stringify(data),
  });

  const result = await response.json();

  const signed = await signAndSubmit(result.unsigned_tx_xdr);

  return {
    escrowId: result.escrow_id,
    tx: signed,
  };
}
