// site.js — unified for Online/UPI + Wallet
// mode: production (Cashfree SDK)
const cashfree = Cashfree({ mode: "production" });

async function createOrder({ amount, customer_id, purpose, order_note, return_to }) {
  const res = await fetch('cashfree/createorder.php', {
    method: 'POST',
    headers: { 'Content-Type':'application/json' },
    body: JSON.stringify({ amount, customer_id, purpose, order_note, return_to })
  });
  return res.json();
}

export async function payNow({ amount, customer_id, purpose='order' }) {
  // purpose: 'order' | 'wallet'
  const resp = await createOrder({ amount, customer_id, purpose });

  if (!resp.success) {
    alert(resp.message || 'Failed to create order'); return;
  }
  // Hosted link available → go
  if (resp.payment_link) {
    window.location.href = resp.payment_link;
    return;
  }
  // Otherwise use JS SDK with payment_session_id
  if (resp.payment_session_id) {
    await cashfree.checkout({
      paymentSessionId: resp.payment_session_id,
      redirectTarget: "_self" // or "_blank" / "_modal"
    });
    return;
  }
  alert('Unexpected response');
}
