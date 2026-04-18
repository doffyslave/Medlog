<?php
session_start();
require '../Database/connection.php';

$client_id = "4ae15b4b-380f-4061-9627-378e758586f3";
$client_secret = "stv8Q~WkbNVJWYhPPj8Pa3yJzq_jVP6XqisraaJX";
$tenant_id = "3409bac1-15a4-4498-8f0f-ef60b6d7f220";
$redirect_uri = "http://localhost/MedLog/auth/callback.php"; // 🔥 FIXED CASE

if (!isset($_GET['code'])) {
    die("No code received.");
}

$code = $_GET['code'];

# STEP 1: GET ACCESS TOKEN
$token_url = "https://login.microsoftonline.com/common/oauth2/v2.0/token";
//$token_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";
$data = [
    'client_id' => $client_id,
    //'scope' => 'User.Read',
    'scope' => 'User.Read openid profile email',
    'code' => $code,
    'redirect_uri' => $redirect_uri,
    'grant_type' => 'authorization_code',
    'client_secret' => $client_secret
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$response = file_get_contents($token_url, false, $context);

if ($response === FALSE) {
    die("Token request failed");
}

$token = json_decode($response, true);

if (!isset($token['access_token'])) {
    echo "<pre>";
    print_r($token);
    die("NO ACCESS TOKEN");
}

$access_token = $token['access_token'];

# STEP 2: GET USER INFO
$user_info = file_get_contents(
    "https://graph.microsoft.com/v1.0/me",
    false,
    stream_context_create([
        'http' => [
            'header' => "Authorization: Bearer $access_token"
        ]
    ])
);

$user = json_decode($user_info, true);

$user_id = $user['id'] ?? null; 
// ✅ FIX: Use Microsoft unique ID instead of auto-increment

$email = $user['mail'] ?? $user['userPrincipalName'] ?? null;
$name = $user['displayName'] ?? 'Unknown User';

if (!$user_id || !$email) {
    // ✅ FIX: Also validate user_id (was missing before)
    die("Failed to retrieve user info from Microsoft");
}

# STEP 3: CHECK DATABASE
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$db_user = $stmt->fetch();

if (!$db_user) {
    //$stmt = $conn->prepare("INSERT INTO users (name, email, role) VALUES (?, ?, 'student')");
    //$stmt->execute([$name, $email]);
    //$user_id = $conn->lastInsertId();

    // ✅ FIX: Explicitly insert user_id (prevents empty '' primary key)
    $stmt = $conn->prepare("INSERT INTO users (user_id, name, email, role) VALUES (?, ?, ?, 'student')");
    $stmt->execute([$user_id, $name, $email]);
    
} else {
    $user_id = $db_user['user_id'];
}

# STEP 4: SESSION
$_SESSION['user'] = [
    'user_id' => $user_id,
    'name' => $name,
    'email' => $email,
    'role' => $db_user['role'] ?? 'student'
];

header("Location: ../dashboard.php");
exit();