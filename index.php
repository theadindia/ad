<?php
// ============ बॉट ब्लॉकर - सिंपल वर्जन ============

// फाइल का नाम जहां ब्लॉक IP स्टोर होंगे
$block_file = "blocked_ips.txt";

// आपका IP पता पता करें
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

$user_ip = get_client_ip();
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// बॉट पैटर्न - जिन्हें ब्लॉक करना है
$bots = array('bot', 'crawler', 'spider', 'scraper', 'wget', 'curl', 'python', 'java', 'nikto', 'sqlmap');

// चेक करो कि यूजर एजेंट में बॉट तो नहीं
$is_bot = false;
foreach ($bots as $bot) {
    if (stripos($user_agent, $bot) !== false) {
        $is_bot = true;
        break;
    }
}

// अगर यूजर एजेंट खाली है तो भी बॉट मानो
if (empty($user_agent)) {
    $is_bot = true;
}

// ब्लॉक IP को स्टोर करने का फंक्शन
function block_ip($ip, $reason) {
    $file = "blocked_ips.txt";
    $current = file_exists($file) ? file_get_contents($file) : "";
    $time = date('Y-m-d H:i:s');
    $new_entry = "$ip | $time | $reason\n";
    file_put_contents($file, $new_entry . $current);
}

// चेक करो कि IP पहले से ब्लॉक तो नहीं
function is_ip_blocked($ip) {
    $file = "blocked_ips.txt";
    if (!file_exists($file)) {
        return false;
    }
    $content = file_get_contents($file);
    if (strpos($content, $ip) !== false) {
        return true;
    }
    return false;
}

// अगर बॉट है तो ब्लॉक करो
if ($is_bot && !is_ip_blocked($user_ip)) {
    block_ip($user_ip, "Bot detected: " . $user_agent);
    die("<h1>Access Denied</h1><p>Your IP has been blocked.</p>");
}

// अगर पहले से ब्लॉक है तो एक्सेस ना दो
if (is_ip_blocked($user_ip)) {
    die("<h1>Access Denied</h1><p>Your IP is blocked. Contact administrator.</p>");
}

// ============ यहां से नॉर्मल वेबसाइट शुरू ============
?>
<!DOCTYPE html>
<html>
<head>
    <title>Website Protected</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f0f2f5;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .blocked-list {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .ip-item {
            background: white;
            padding: 10px;
            margin: 5px 0;
            border-left: 3px solid #f44336;
            font-family: monospace;
        }
        .badge {
            background: #4CAF50;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
        button {
            background: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🛡️ Bot Protection Active</h1>
        
        <div class="info">
            <strong>Your IP:</strong> <?php echo $user_ip; ?><br>
            <strong>Status:</strong> <span class="badge">✅ Safe</span><br>
            <strong>User Agent:</strong> <?php echo htmlspecialchars($user_agent); ?>
        </div>
        
        <div class="blocked-list">
            <h3>🚫 Blocked IPs</h3>
            <?php
            if (file_exists("blocked_ips.txt")) {
                $blocked = file_get_contents("blocked_ips.txt");
                if (empty($blocked)) {
                    echo "<p>No IPs blocked yet.</p>";
                } else {
                    $lines = explode("\n", trim($blocked));
                    foreach ($lines as $line) {
                        if (!empty($line)) {
                            echo "<div class='ip-item'>$line</div>";
                        }
                    }
                }
            } else {
                echo "<p>No IPs blocked yet.</p>";
            }
            ?>
            <button onclick="location.reload()">🔄 Refresh</button>
        </div>
        
        <div style="margin-top: 20px; text-align: center; color: #666; font-size: 12px;">
            <p>This website automatically blocks bots and malicious visitors.</p>
        </div>
    </div>
</body>
</html>