/**
 * Accept.js backend.
 *
 * The browser tokenises the card and sends you an opaqueData blob.
 * You charge the token. Raw card numbers never reach this process,
 * which is what keeps the PCI scope small.
 *
 *   npm install express
 *   node server/server.js
 */

const express = require('express');
const path = require('path');

const app = express();
app.use(express.json());
app.use(express.static(path.join(__dirname, '..', 'public')));

const ENV = process.env.ANET_ENV || 'sandbox';
const API_URL = ENV === 'production'
  ? 'https://api.authorize.net/xml/v1/request.api'
  : 'https://apitest.authorize.net/xml/v1/request.api';

const AUTH = {
  name: process.env.ANET_API_LOGIN_ID,
  transactionKey: process.env.ANET_TRANSACTION_KEY,
};

// Your real prices live server-side. Never accept an amount from the client.
const PRICES = { 'demo-1001': '19.99' };

async function anet(payload) {
  const res = await fetch(API_URL, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload),
  });

  // Response carries a UTF-8 BOM that breaks JSON.parse.
  const text = (await res.text()).replace(/^\uFEFF/, '');
  return JSON.parse(text);
}

app.post('/api/charge', async (req, res) => {
  const { opaqueData, orderId } = req.body || {};

  if (!opaqueData || !opaqueData.dataValue) {
    return res.status(400).json({ approved: false, error: 'Missing payment token' });
  }

  const amount = PRICES[orderId];
  if (!amount) {
    return res.status(400).json({ approved: false, error: 'Unknown order' });
  }

  // Element order matters. The JSON is translated to XML server-side.
  const payload = {
    createTransactionRequest: {
      merchantAuthentication: AUTH,
      // Your own idempotency handle. Shows up in the merchant's reports,
      // and makes reconciliation with your database possible.
      refId: String(orderId).slice(0, 20),
      transactionRequest: {
        transactionType: 'authCaptureTransaction',
        amount,
        payment: {
          opaqueData: {
            dataDescriptor: opaqueData.dataDescriptor,
            dataValue: opaqueData.dataValue,
          },
        },
      },
    },
  };

  try {
    const result = await anet(payload);
    const tx = result.transactionResponse || {};

    // responseCode: 1 approved, 2 declined, 3 error, 4 held for review.
    // Treating 4 as approved is a classic and expensive mistake.
    if (tx.responseCode === '1') {
      return res.json({
        approved: true,
        transactionId: tx.transId,
        authCode: tx.authCode,
      });
    }

    if (tx.responseCode === '4') {
      return res.json({
        approved: false,
        held: true,
        transactionId: tx.transId,
        error: 'Payment is under review. Do not fulfil this order yet.',
      });
    }

    const reason =
      (tx.errors && tx.errors[0] && tx.errors[0].errorText) ||
      (result.messages && result.messages.message[0].text) ||
      'Declined';

    return res.json({ approved: false, error: reason });
  } catch (err) {
    console.error('Gateway error', err);
    return res.status(502).json({ approved: false, error: 'Gateway unavailable' });
  }
});

app.listen(3000, () => {
  console.log(`Accept.js demo on http://localhost:3000  (env: ${ENV})`);
});
