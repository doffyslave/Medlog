<?php
$client_id = "4ae15b4b-380f-4061-9627-378e758586f3";
$tenant_id = "3409bac1-15a4-4498-8f0f-ef60b6d7f220";
$redirect_uri = "http://localhost/MedLog/auth/callback.php";

/*$auth_url = "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/authorize?" . http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'response_mode' => 'query',
    'scope' => 'User.Read',
    'state' => '12345'
]);*/

//Workaround
$auth_url = "https://login.microsoftonline.com/common/oauth2/v2.0/authorize?" . http_build_query([
    'client_id' => $client_id,
    'response_type' => 'code',
    'redirect_uri' => $redirect_uri,
    'response_mode' => 'query',
    //'scope' => 'User.Read',
    'scope' => 'User.Read openid profile email',
    'state' => '12345'
]);

header("Location: $auth_url");  
exit();