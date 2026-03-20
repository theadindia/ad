<?php
/**
 * बॉट ब्लॉकर सिस्टम - एक ही फाइल में पूरा सिस्टम
 * इस एक फाइल को अपनी वेबसाइट पर अपलोड करें और भूल जाएं
 */

// ============ कॉन्फ़िगरेशन ============
define('BLOCK_TIME', 86400); // 24 घंटे ब्लॉक रहेगा
define('MAX_REQUESTS', 30); // 1 मिनट में 30 से ज्यादा रिक्वेस्ट पर ब्लॉक

// बॉट पैटर्न - जिन्हें ब्लॉक करना है
$bot_patterns = ['bot', 'crawler', 'spider', 'scraper', 'scanner', 'wget', 'curl', 
                 'python', 'java', 'perl', 'ruby', 'nikto', 'sqlmap', 'nmap'];

// ============ फंक्शन्स ============

// आईपी पता पाने के लिए
function getIP() {
    if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    if (isset($_SERVER['HTTP_X_REAL_IP'])) return $_SERVER['HTTP_X_REAL_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// बॉट चेक करने के लिए
function isBot($ua, $patterns) {
    if (empty($ua)) return true; // खाली यूजर एजेंट = बॉट
    $ua = strtolower($ua);
    foreach ($patterns as $pattern) {
        if (strpos($ua, strtolower($pattern)) !== false) return true;
    }
    return false;
}

// आईपी ब्लॉक है?
function isBlocked($ip) {
    $file = 'blocks.json';
    if (!file_exists($file)) return false;
    
    $data = json_decode(file_get_contents($file), true);
    if (!isset($data[$ip])) return false;
    
    // टाइम चेक करो
    if ($data[$ip]['expires'] < time()) {
        unset($data[$ip]);
        file_put_contents($file, json_encode($data));
        return false;
    }
    return true;
}

// आईपी ब्लॉक करो
function blockIP($ip, $reason) {
    $file = 'blocks.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    $data[$ip] = [
        'ip' => $ip,
        'time' => time(),
        'expires' => time() + BLOCK_TIME,
        'reason' => $reason
    ];
    
    file_put_contents($file, json_encode($data));
    
    // लॉग में सेव करो
    $log = date('Y-m-d H:i:s') . " - BLOCKED: $ip - $reason\n";
    file_put_contents('block_log.txt', $log, FILE_APPEND);
}

// रिक्वेस्ट काउंट चेक करो
function checkRate($ip) {
    $file = 'requests.json';
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    $now = time();
    
    // पुरानी रिक्वेस्ट हटाओ
    if (!isset($data[$ip])) $data[$ip] = [];
    $data[$ip] = array_filter($data[$ip], function($t) use ($now) {
        return ($now - $t) < 60; // सिर्फ 1 मिनट की रिक्वेस्ट रखो
    });
    
    // नई रिक्वेस्ट जोड़ो
    $data[$ip][] = $now;
    file_put_contents($file, json_encode($data));
    
    return count($data[$ip]);
}

// ============ मेन लॉजिक ============

$ip = getIP();
$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$is_bot = isBot($ua, $bot_patterns);
$request_count = checkRate($ip);

// ब्लॉक करने की शर्तें
if (isBlocked($ip)) {
    http_response_code(403);
    die('<!DOCTYPE html>
    <html>
    <head><title>403 Access Denied</title>
    <style>
        body{font-family:Arial;text-align:center;padding:50px;background:#f5f5f5}
        .box{background:white;padding:30px;border-radius:10px;max-width:500px;margin:auto;box-shadow:0 2px 10px rgba(0,0,0,0.1)}
        h1{color:#d32f2f}
    </style>
    </head>
    <body>
    <div class="box">
        <h1>🚫 Access Denied</h1>
        <p>Your IP has been blocked due to suspicious activity.</p>
        <p>Block will expire in 24 hours.</p>
        <hr>
        <p><small>IP: ' . htmlspecialchars($ip) . '</small></p>
    </div>
    </body>
    </html>');
}

if ($is_bot || $request_count > MAX_REQUESTS) {
    $reason = $is_bot ? "Bot detected in User Agent" : "Too many requests ($request_count in 1 minute)";
    blockIP($ip, $reason);
    http_response_code(403);
    die('<h1>403 Forbidden</h1><p>Your IP has been blocked.</p>');
}

// ============ DASHBOARD HTML ============
// अगर नॉर्मल यूजर है तो डैशबोर्ड दिखाओ

$blocked_ips = [];
if (file_exists('blocks.json')) {
    $data = json_decode(file_get_contents('blocks.json'), true);
    $now = time();
    foreach ($data as $ip => $info) {
        if ($info['expires'] > $now) {
            $blocked_ips[] = $info;
        }
    }
}

$total_blocks = count($blocked_ips);
?>
<!DOCTYPE html>
<html lang="hi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>बॉट प्रोटेक्शन सिस्टम</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: auto;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .header h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card .number {
            font-size: 3em;
            font-weight: bold;
            color: #667eea;
            margin: 15px 0;
        }
        
        .card .label {
            color: #666;
            font-size: 0.9em;
        }
        
        .blocked-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .blocked-section h2 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .ip-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .ip-item {
            background: #f8f9fa;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 4px solid #dc3545;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .ip-details {
            flex: 1;
        }
        
        .ip-address {
            font-family: monospace;
            font-size: 1.1em;
            font-weight: bold;
            color: #333;
        }
        
        .ip-reason {
            font-size: 0.85em;
            color: #dc3545;
            margin-top: 5px;
        }
        
        .ip-time {
            font-size: 0.8em;
            color: #666;
            margin-top: 3px;
        }
        
        .badge {
            background: #dc3545;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            color: white;
            padding: 20px;
        }
        
        .refresh-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-size: 1em;
            margin-top: 15px;
            transition: background 0.3s;
        }
        
        .refresh-btn:hover {
            background: #5a67d8;
        }
        
        @media (max-width: 768px) {
            .ip-item {
                flex-direction: column;
                text-align: center;
            }
            .badge {
                margin-top: 10px;
            }
            .header h1 {
                font-size: 1.8em;
            }
        }
        
        .alert {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert.show {
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🛡️ बॉट प्रोटेक्शन सिस्टम</h1>
            <p>आपकी वेबसाइट बॉट्स से सुरक्षित है</p>
        </div>
        
        <div class="stats">
            <div class="card">
                <div class="label">आपका IP Address</div>
                <div class="number" style="font-size: 1.5em;"><?php echo htmlspecialchars($ip); ?></div>
                <div class="label">✅ सुरक्षित स्थिति</div>
            </div>
            
            <div class="card">
                <div class="label">कुल ब्लॉक IP</div>
                <div class="number"><?php echo $total_blocks; ?></div>
                <div class="label">अब तक ब्लॉक किए गए</div>
            </div>
            
            <div class="card">
                <div class="label">सिस्टम स्टेटस</div>
                <div class="number" style="font-size: 1.5em;">🟢 ACTIVE</div>
                <div class="label">24 घंटे बाद ब्लॉक हटेगा</div>
            </div>
        </div>
        
        <div class="blocked-section">
            <h2>🚫 ब्लॉक किए गए IP पते</h2>
            <div id="alert" class="alert"></div>
            
            <div class="ip-list" id="ipList">
                <?php if (empty($blocked_ips)): ?>
                    <p style="text-align: center; color: #999; padding: 40px;">
                        ✨ कोई IP ब्लॉक नहीं है<br>
                        <small>सभी विज़िटर सुरक्षित हैं</small>
                    </p>
                <?php else: ?>
                    <?php foreach ($blocked_ips as $block): ?>
                        <div class="ip-item">
                            <div class="ip-details">
                                <div class="ip-address"><?php echo htmlspecialchars($block['ip']); ?></div>
                                <div class="ip-reason">🚫 कारण: <?php echo htmlspecialchars($block['reason']); ?></div>
                                <div class="ip-time">
                                    ⏰ ब्लॉक किया: <?php echo date('d/m/Y H:i:s', $block['time']); ?><br>
                                    ⏱️ खुलेगा: <?php echo date('d/m/Y H:i:s', $block['expires']); ?>
                                </div>
                            </div>
                            <div class="badge">BLOCKED</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 20px;">
                <button class="refresh-btn" onclick="location.reload()">🔄 रिफ्रेश करें</button>
            </div>
        </div>
        
        <div class="footer">
            <p>🔒 यह सिस्टम ऑटोमैटिक बॉट्स को ब्लॉक करता है</p>
            <p>⚡ बॉट डिटेक्ट होते ही तुरंत ब्लॉक</p>
            <p>⏰ हर 24 घंटे में ब्लॉक हट जाता है</p>
        </div>
    </div>
    
    <script>
        // ऑटो रिफ्रेश - हर 30 सेकंड में
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // एलर्ट दिखाने के लिए
        function showAlert(message) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.classList.add('show');
            setTimeout(() => {
                alert.classList.remove('show');
            }, 3000);
        }
        
        // अगर पेज रिफ्रेश हुआ तो मैसेज दिखाओ
        <?php if (isset($_GET['refreshed'])): ?>
        showAlert('✅ डेटा अपडेट हो गया');
        <?php endif; ?>
    </script>
</body>
</html>
<?php
// यहां PHP खत्म होता है
?>