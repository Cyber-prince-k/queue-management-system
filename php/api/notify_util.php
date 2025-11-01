<?php
// Notification utilities (SMS via Twilio or simulation)

function env_or_default($key, $default = null) {
    $v = getenv($key);
    return ($v !== false && $v !== null && $v !== '') ? $v : $default;
}

// Low-dependency SMTP sender (TLS) suitable for Gmail/Office365
function smtp_send($host, $port, $username, $password, $fromEmail, $fromName, $toEmail, $subject, $body, $secure = 'tls', $timeout = 15) {
    $crlf = "\r\n";
    $debug = (bool)env_or_default('SMTP_DEBUG', false);
    $relax = (bool)env_or_default('SMTP_RELAX_SSL', false);
    $log = function($msg) use ($debug) {
        if (!$debug) return;
        $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'logs';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        @file_put_contents($dir . DIRECTORY_SEPARATOR . 'mail.log', '['.date('Y-m-d H:i:s')."] ".$msg."\n", FILE_APPEND);
    };
    $sslOpts = [ 'verify_peer' => !$relax, 'verify_peer_name' => !$relax, 'allow_self_signed' => $relax ];
    $context = stream_context_create(['ssl' => $sslOpts]);
    $scheme = ($secure === 'ssl') ? 'ssl' : 'tcp';
    $fp = @stream_socket_client("{$scheme}://{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) { $log("connect failed: $errstr ($errno)"); return ['success' => false, 'message' => "connect failed: $errstr ($errno)"]; }
    stream_set_timeout($fp, $timeout);

    $read = function() use ($fp){ $resp=''; while (($line = fgets($fp, 515)) !== false) { $resp .= $line; if (isset($line[3]) && $line[3] === ' ') break; } return $resp; };
    $write = function($cmd) use ($fp, $debug, $log){ if ($debug) { $log('C: '.trim($cmd)); } fwrite($fp, $cmd); };

    $greet = $read();
    if ($debug) { $log('S: '.trim($greet)); }
    if (substr($greet,0,3) !== '220') { fclose($fp); $log('bad greeting: '.$greet); return ['success'=>false,'message'=>'bad greeting: '.$greet]; }
    $write('EHLO qech.local'.$crlf); $ehlo = $read(); if ($debug) { $log('S: '.trim($ehlo)); }
    if (substr($ehlo,0,3) !== '250') { fclose($fp); $log('EHLO failed: '.$ehlo); return ['success'=>false,'message'=>'EHLO failed: '.$ehlo]; }

    if ($secure === 'tls') {
        $write('STARTTLS'.$crlf); $starttls = $read(); if ($debug) { $log('S: '.trim($starttls)); }
        if (substr($starttls,0,3) !== '220') { fclose($fp); $log('STARTTLS failed: '.$starttls); return ['success'=>false,'message'=>'STARTTLS failed: '.$starttls]; }
        $tlsMethod = defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : STREAM_CRYPTO_METHOD_TLS_CLIENT;
        if (!stream_socket_enable_crypto($fp, true, $tlsMethod)) { fclose($fp); $log('TLS negotiation failed'); return ['success'=>false,'message'=>'TLS negotiation failed']; }
        // EHLO again after TLS
        $write('EHLO qech.local'.$crlf); $ehlo2 = $read(); if ($debug) { $log('S: '.trim($ehlo2)); }
        if (substr($ehlo2,0,3) !== '250') { fclose($fp); $log('EHLO after TLS failed: '.$ehlo2); return ['success'=>false,'message'=>'EHLO after TLS failed: '.$ehlo2]; }
    } elseif ($secure === 'ssl') {
        // Already inside implicit TLS tunnel; proceed
    }

    // AUTH LOGIN
    $write('AUTH LOGIN'.$crlf); $auth = $read(); if ($debug) { $log('S: '.trim($auth)); }
    if (substr($auth,0,3) !== '334') { fclose($fp); $log('AUTH not accepted: '.$auth); return ['success'=>false,'message'=>'AUTH not accepted: '.$auth]; }
    $write(base64_encode($username).$crlf); $usr = $read(); if ($debug) { $log('S: '.trim($usr)); }
    if (substr($usr,0,3) !== '334') { fclose($fp); $log('Username rejected: '.$usr); return ['success'=>false,'message'=>'Username rejected: '.$usr]; }
    $write(base64_encode($password).$crlf); $pwd = $read(); if ($debug) { $log('S: '.trim($pwd)); }
    if (substr($pwd,0,3) !== '235') { fclose($fp); $log('Password rejected: '.$pwd); return ['success'=>false,'message'=>'Password rejected: '.$pwd]; }

    // MAIL FROM / RCPT TO
    $write('MAIL FROM:<'.$fromEmail.'>'.$crlf); $mfrom = $read(); if ($debug) { $log('S: '.trim($mfrom)); }
    if (substr($mfrom,0,3) !== '250') { fclose($fp); $log('MAIL FROM failed: '.$mfrom); return ['success'=>false,'message'=>'MAIL FROM failed: '.$mfrom]; }
    $write('RCPT TO:<'.$toEmail.'>'.$crlf); $rcpt = $read(); if ($debug) { $log('S: '.trim($rcpt)); }
    if (substr($rcpt,0,3) !== '250' && substr($rcpt,0,3) !== '251') { fclose($fp); $log('RCPT TO failed: '.$rcpt); return ['success'=>false,'message'=>'RCPT TO failed: '.$rcpt]; }

    // DATA
    $write('DATA'.$crlf); $dataResp = $read(); if ($debug) { $log('S: '.trim($dataResp)); }
    if (substr($dataResp,0,3) !== '354') { fclose($fp); $log('DATA rejected: '.$dataResp); return ['success'=>false,'message'=>'DATA rejected: '.$dataResp]; }
    $headers = [];
    $headers[] = 'From: '.sprintf('%s <%s>', $fromName, $fromEmail);
    $headers[] = 'To: <'.$toEmail.'>';
    $headers[] = 'Subject: '.$subject;
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $msg = implode($crlf, $headers).$crlf.$crlf.$body.$crlf.
           '.'; // end of DATA marker is a single dot on its own line
    // Ensure CRLF and end marker
    $msg = str_replace(["\r\n","\r","\n"], $crlf, $msg);
    if (substr($msg,-1) !== "\n") $msg .= $crlf;
    $write($msg.$crlf);
    $dataOk = $read(); if ($debug) { $log('S: '.trim($dataOk)); }
    if (substr($dataOk,0,3) !== '250') { fclose($fp); $log('Message not accepted: '.$dataOk); return ['success'=>false,'message'=>'Message not accepted: '.$dataOk]; }

    // QUIT
    $write('QUIT'.$crlf); $quit = $read(); if ($debug) { $log('S: '.trim($quit)); } fclose($fp);
    return ['success'=>true];
}

function send_email_notification($to, $subject, $message) {
    if (!$to || !$subject || !$message) {
        return [ 'success' => false, 'message' => 'Missing to/subject/message' ];
    }
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return [ 'success' => false, 'message' => 'Invalid email address' ];
    }

    $host = env_or_default('SMTP_HOST');
    $port = (int)env_or_default('SMTP_PORT', 587);
    // Read from env; fall back to provided values ONLY if env not set
    $user = env_or_default('SMTP_USER', 'princekamnga1@gmail.com');
    $pass = env_or_default('SMTP_PASS', 'cescerhdeckfssqf');
    $secure = env_or_default('SMTP_SECURE', 'tls');
    if ($port === 465) { $secure = 'ssl'; }
    $from = env_or_default('SMTP_FROM_EMAIL', $user ?: 'no-reply@qech.local');
    $fromName = env_or_default('SMTP_FROM_NAME', 'QECH Queue System');

    // Ensure From equals authenticated user for Gmail deliverability
    if ($user && strpos($from, '@') !== false && strtolower($from) !== strtolower($user)) {
        $from = $user;
    }

    if ($host && $user && $pass) {
        try {
            // Dot-stuff lines starting with '.' in body
            $safeBody = preg_replace('/\n\./', "\n..", $message);
            $res = smtp_send($host, $port, $user, $pass, $from, $fromName, $to, $subject, $safeBody, $secure, 25);
            if (!$res['success']) return $res;
            return ['success' => true];
        } catch (Throwable $e) {
            // Fall back to mail()
        }
    }

    // Fallback to mail()
    $headers = [];
    $headers[] = 'From: ' . sprintf('%s <%s>', $fromName, $from);
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $ok = @mail($to, $subject, $message, implode("\r\n", $headers));
    if (!$ok) return [ 'success' => false, 'message' => 'mail() returned false (configure SMTP env vars)' ];
    return [ 'success' => true ];
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
