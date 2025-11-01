<?php
// Notification utilities (SMS via Twilio or simulation)

function env_or_default($key, $default = null) {
    $v = getenv($key);
    return ($v !== false && $v !== null && $v !== '') ? $v : $default;
}

function send_sms_notification($to, $message) {
    $sid = env_or_default('TWILIO_SID');
    $token = env_or_default('TWILIO_AUTH_TOKEN');
    $from = env_or_default('TWILIO_FROM_NUMBER');

    if (!$to || !$message) {
        return [ 'success' => false, 'message' => 'Missing phone or message' ];
    }

    // If Twilio config not set, simulate success to avoid breaking the flow
    if (!$sid || !$token || !$from) {
        return [ 'success' => true, 'simulated' => true, 'message' => 'SMS simulated (no provider configured)' ];
    }

    $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

    $data = http_build_query([
        'From' => $from,
        'To' => $to,
        'Body' => $message
    ]);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return [ 'success' => false, 'message' => $err ];
    }

    $ok = $httpCode >= 200 && $httpCode < 300;
    return [ 'success' => $ok, 'provider_response' => $response, 'status' => $httpCode ];
}
