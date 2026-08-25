<?php
/**
 * Accept Hosted: the lowest-effort integration.
 *
 * Flow:
 *   1. Your server asks Authorize.Net for a one-time form token
 *   2. You POST that token to the hosted payment page
 *   3. The customer enters their card on Authorize.Net's domain
 *   4. They come back to your return URL
 *
 * Card data never touches your server. That keeps the client in the
 * simplest PCI tier (SAQ A), which is the single biggest reason to
 * use this over a custom form.
 */

declare(strict_types=1);

$env            = getenv('ANET_ENV') ?: 'sandbox';
$apiLoginId     = getenv('ANET_API_LOGIN_ID');
$transactionKey = getenv('ANET_TRANSACTION_KEY');

$apiUrl = $env === 'production'
    ? 'https://api.authorize.net/xml/v1/request.api'
    : 'https://apitest.authorize.net/xml/v1/request.api';

$payUrl = $env === 'production'
    ? 'https://accept.authorize.net/payment/payment'
    : 'https://test.authorize.net/payment/payment';

// Replace with your real cart total. Never trust an amount sent from the browser.
$amount    = '19.99';

// These must be real, reachable-looking URLs on a normal domain. Authorize.Net
// rejects reserved placeholder TLDs such as .example, and the error it returns
// is "must begin with http:// or https://" even when the URL plainly does,
// which sends you looking in the wrong place entirely.
$returnUrl = 'https://example.com/receipt';
$cancelUrl = 'https://example.com/cart';

/**
 * NOTE: the API is XML behind a JSON translation layer, so element order
 * is enforced. Do not alphabetise these keys or reshuffle them.
 */
$request = [
    'getHostedPaymentPageRequest' => [
        'merchantAuthentication' => [
            'name'           => $apiLoginId,
            'transactionKey' => $transactionKey,
        ],
        'transactionRequest' => [
            'transactionType' => 'authCaptureTransaction',
            'amount'          => $amount,
        ],
        'hostedPaymentSettings' => [
            'setting' => [
                [
                    'settingName'  => 'hostedPaymentReturnOptions',
                    'settingValue' => json_encode([
                        'showReceipt'   => true,
                        'url'           => $returnUrl,
                        'urlText'       => 'Continue',
                        'cancelUrl'     => $cancelUrl,
                        'cancelUrlText' => 'Cancel',
                    ]),
                ],
                [
                    'settingName'  => 'hostedPaymentButtonOptions',
                    'settingValue' => json_encode(['text' => 'Pay']),
                ],
                [
                    'settingName'  => 'hostedPaymentPaymentOptions',
                    'settingValue' => json_encode([
                        'cardCodeRequired' => true,
                        'showCreditCard'   => true,
                    ]),
                ],
                [
                    'settingName'  => 'hostedPaymentBillingAddressOptions',
                    'settingValue' => json_encode([
                        'show'     => true,
                        'required' => false,
                    ]),
                ],
            ],
        ],
    ],
];

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_POSTFIELDS     => json_encode($request),
    CURLOPT_TIMEOUT        => 30,
]);
$raw = curl_exec($ch);
if ($raw === false) {
    http_response_code(502);
    exit('Gateway unreachable: ' . curl_error($ch));
}
curl_close($ch);

// The response is prefixed with a UTF-8 BOM. json_decode chokes on it.
$response = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $raw), true);

$token = $response['token'] ?? null;
if (!$token) {
    http_response_code(502);
    $message = $response['messages']['message'][0]['text'] ?? 'Unknown error';
    exit('Could not get a payment token: ' . htmlspecialchars($message));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Redirecting to secure payment…</title>
</head>
<body>
  <p>Taking you to our secure payment page…</p>

  <!-- Redirect approach. For an iframe/lightbox instead, see
       hostedPaymentIFrameCommunicatorUrl in the official docs. -->
  <form id="pay" method="post" action="<?= htmlspecialchars($payUrl) ?>">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <noscript><button type="submit">Continue to payment</button></noscript>
  </form>

  <script>document.getElementById('pay').submit();</script>
</body>
</html>
