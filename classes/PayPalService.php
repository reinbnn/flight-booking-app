<?php
/**
 * PayPal Payment Service
 */

class PayPalService {
    private $clientId;
    private $secret;
    private $apiBase;
    private $accessToken;

    public function __construct() {
        $this->clientId = PAYPAL_CLIENT_ID;
        $this->secret = PAYPAL_SECRET;
        $this->apiBase = PAYPAL_API_BASE;
        $this->getAccessToken();
    }

    /**
     * Get Access Token
     */
    private function getAccessToken() {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiBase . '/v1/oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_USERPWD => $this->clientId . ':' . $this->secret,
            CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        $this->accessToken = $data['access_token'] ?? null;

        return $this->accessToken;
    }

    /**
     * Create Order
     */
    public function createOrder($bookingId, $amount, $email, $description) {
        $data = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => $bookingId,
                    'description' => $description,
                    'amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($amount, 2, '.', '')
                    ]
                ]
            ],
            'payer' => [
                'email_address' => $email
            ],
            'application_context' => [
                'brand_name' => 'SKYJET',
                'locale' => 'en-US',
                'return_url' => PAYPAL_RETURN_URL,
                'cancel_url' => PAYPAL_CANCEL_URL,
                'user_action' => 'PAY_NOW'
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiBase . '/v2/checkout/orders',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 201 && isset($result['id'])) {
            return [
                'success' => true,
                'order_id' => $result['id'],
                'approve_link' => $result['links'][0]['href'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Failed to create PayPal order'
        ];
    }

    /**
     * Capture Order
     */
    public function captureOrder($orderId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiBase . '/v2/checkout/orders/' . $orderId . '/capture',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_POSTFIELDS => '{}',
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 201 && $result['status'] === 'COMPLETED') {
            return [
                'success' => true,
                'transaction_id' => $result['purchase_units'][0]['payments']['captures'][0]['id'],
                'status' => $result['status'],
                'payer_email' => $result['payer']['email_address'],
                'amount' => $result['purchase_units'][0]['amount']['value']
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Failed to capture payment'
        ];
    }

    /**
     * Get Order Details
     */
    public function getOrderDetails($orderId) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiBase . '/v2/checkout/orders/' . $orderId,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }

    /**
     * Refund Payment
     */
    public function refundPayment($captureId, $amount = null) {
        $data = ['amount' => $amount ? number_format($amount, 2, '.', '') : null];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiBase . '/v2/payments/captures/' . $captureId . '/refund',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->accessToken
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $result = json_decode($response, true);

        if ($httpCode === 201) {
            return [
                'success' => true,
                'refund_id' => $result['id'],
                'status' => $result['status']
            ];
        }

        return [
            'success' => false,
            'error' => $result['message'] ?? 'Refund failed'
        ];
    }
}

?>
