/* ============================================================
   SOPHIATE CRÉATION — gozem.js
   Intégration API Gozem — PRÊTE, à activer quand tu as les clés
   ============================================================ */

/* ── CONFIG ── (remplis ces valeurs quand tu as ton compte Gozem Business)
   Demande ton API key sur : https://business.gozem.co
   ─────────────────────────────────────────────────────────── */
const GOZEM_CONFIG = {
  apiKey:      'METS_TON_API_KEY_ICI',           // Ex: "gz_live_xxxxxxxxxxxx"
  apiUrl:      'https://api.gozem.co/v1',        // URL de base de l'API Gozem
  merchantId:  'METS_TON_MERCHANT_ID_ICI',       // Ton ID marchand Gozem
  currency:    'XOF',                             // Franc CFA
  city:        'Lome',
  enabled:     false,   // ← Passe à true quand tu as tes clés
};

/* ── CALCULER LE PRIX DE LIVRAISON ──
   Appelle l'API Gozem pour estimer le coût selon la distance
   ─────────────────────────────────────────────────────────── */
async function getDeliveryQuote(customerAddress) {
  if (!GOZEM_CONFIG.enabled) {
    // Mode simulation — retourne un prix fixe
    return { price: 1500, currency: 'FCFA', duration: '30-60 min', simulated: true };
  }

  try {
    const response = await fetch(`${GOZEM_CONFIG.apiUrl}/delivery/quote`, {
      method: 'POST',
      headers: {
        'Content-Type':  'application/json',
        'Authorization': `Bearer ${GOZEM_CONFIG.apiKey}`,
        'X-Merchant-Id': GOZEM_CONFIG.merchantId,
      },
      body: JSON.stringify({
        pickup: {
          address: 'Luxolin-Baguida, Lomé',     // ← Adresse de Sophiate Création
          city:    GOZEM_CONFIG.city,
        },
        delivery: {
          address: customerAddress,
          city:    GOZEM_CONFIG.city,
        },
        currency: GOZEM_CONFIG.currency,
      }),
    });

    if (!response.ok) throw new Error('Erreur API Gozem: ' + response.status);
    const data = await response.json();
    return {
      price:    data.price || 0,
      currency: data.currency || 'FCFA',
      duration: data.estimated_duration || '24h',
    };
  } catch (err) {
    console.error('Gozem API error:', err);
    return { price: 1500, currency: 'FCFA', duration: '24h', error: true };
  }
}

/* ── CRÉER UNE COMMANDE DE LIVRAISON ──
   Appelle l'API pour créer et tracker une livraison
   ─────────────────────────────────────────────────────────── */
async function createDelivery(orderData) {
  if (!GOZEM_CONFIG.enabled) {
    showToast('Livraison Gozem', 'Intégration en cours de finalisation — contactez-nous par téléphone');
    return null;
  }

  const { customerName, customerPhone, customerAddress, items, totalAmount } = orderData;

  try {
    const response = await fetch(`${GOZEM_CONFIG.apiUrl}/delivery/create`, {
      method: 'POST',
      headers: {
        'Content-Type':  'application/json',
        'Authorization': `Bearer ${GOZEM_CONFIG.apiKey}`,
        'X-Merchant-Id': GOZEM_CONFIG.merchantId,
      },
      body: JSON.stringify({
        merchant_id: GOZEM_CONFIG.merchantId,
        pickup: {
          name:    'Sophiate Création',
          address: 'Luxolin-Baguida, Lomé',
          phone:   '+22896475974',
          city:    GOZEM_CONFIG.city,
        },
        delivery: {
          name:    customerName,
          address: customerAddress,
          phone:   customerPhone,
          city:    GOZEM_CONFIG.city,
        },
        order: {
          reference:    'SC-' + Date.now(),
          items:        items.map(i => ({ name: i.name, quantity: i.qty })),
          total_amount: totalAmount,
          currency:     GOZEM_CONFIG.currency,
          payment_mode: 'CASH_ON_DELIVERY', // ou 'MOBILE_MONEY', 'CARD'
        },
        notes: 'Vêtements — à manipuler avec soin',
      }),
    });

    if (!response.ok) throw new Error('Erreur création livraison: ' + response.status);
    const data = await response.json();

    showToast('Commande confirmée !', 'Votre livreur Gozem arrive bientôt');
    return data;
  } catch (err) {
    console.error('Gozem create delivery error:', err);
    showToast('Erreur livraison', 'Veuillez réessayer ou appeler le +228 96 47 59 74');
    return null;
  }
}

/* ── TRACKER UNE LIVRAISON ──
   Affiche le statut en temps réel
   ─────────────────────────────────────────────────────────── */
async function trackDelivery(deliveryId) {
  if (!GOZEM_CONFIG.enabled) return null;

  try {
    const response = await fetch(`${GOZEM_CONFIG.apiUrl}/delivery/${deliveryId}/track`, {
      headers: {
        'Authorization': `Bearer ${GOZEM_CONFIG.apiKey}`,
        'X-Merchant-Id': GOZEM_CONFIG.merchantId,
      },
    });
    if (!response.ok) throw new Error('Track error');
    return await response.json();
  } catch (err) {
    console.error('Track error:', err);
    return null;
  }
}

/* ── BOUTON COMMANDER VIA GOZEM ──
   Appelé depuis le bouton "Commander avec livraison Gozem"
   ─────────────────────────────────────────────────────────── */
function orderWithGozem() {
  const cartItems = Cart.items || [];

  if (!GOZEM_CONFIG.enabled) {
    // Tant que l'API n'est pas activée → rediriger vers WhatsApp ou appel
    const msg = encodeURIComponent(
      'Bonjour Sophiate Création, je voudrais commander avec livraison Gozem.\n\n' +
      (cartItems.length
        ? 'Mon panier : ' + cartItems.map(i => `${i.name} x${i.qty}`).join(', ')
        : 'Je suis intéressé par vos créations.')
    );
    // Option 1 : WhatsApp
    window.open(`https://wa.me/22896475974?text=${msg}`, '_blank');
    // Option 2 : Appel direct (décommente si tu préfères)
    // window.location.href = 'tel:+22896475974';
    return;
  }

  // Quand l'API est activée : ouvrir le formulaire de commande
  document.getElementById('orderModal')?.classList.add('open');
}

/* Exposer pour usage dans les pages */
window.Gozem = { getDeliveryQuote, createDelivery, trackDelivery, orderWithGozem, CONFIG: GOZEM_CONFIG };
