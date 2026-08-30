<?php
// Copyright 2021-2025 SnehTV, Inc.
// Licensed under MIT (https://github.com/mitthu786/TS-JioTV/blob/main/LICENSE)
// Created By: TechieSneh

error_reporting(0);
include "functions.php";

// Set headers for JSON response
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// Get and sanitize parameters
$id = htmlspecialchars($_REQUEST['id'] ?? '');
$cid = htmlspecialchars($_REQUEST['cid'] ?? '');
$cooks = htmlspecialchars($_REQUEST['ck'] ?? '');

if (empty($cid) || empty($cooks) || empty($id)) {
    http_response_code(400);
    echo json_encode(["error" => "Missing required parameters"]);
    exit;
}

// Process request
$chs = explode('-', $id);
$cookie = hex2bin($cooks);

// Prepare headers
$user_agent = 'plaYtv/7.1.3 (Linux;Android 14) ExoPlayerLib/2.11.7';
$headers = [
    'Cookie: ' . $cookie,
    'Content-Type: application/x-www-form-urlencoded',
    'User-Agent: ' . $user_agent
];

// Fetch data
$url = sprintf("https://jiotvmblive.cdn.jio.com/bpk-tv/%s/Fallback/%s", $chs[0], $id);
$hs = cUrlGetData($url, $headers);

// Get refreshed cookie if needed by the stream
$cuk = get_and_refresh_cookie($url, $headers);
$decoded_cuk = hex2bin($cuk);

// Generate direct playlist 
// (Replaces local .ts segments with direct absolute URLs pointing to Jio CDN instead of proxy)
$search = [
    $chs[0] . '-', 
    '.ts'
];
$replace = [
    "https://jiotvmblive.cdn.jio.com/bpk-tv/{$chs[0]}/Fallback/{$chs[0]}-", 
    ".ts?" . $decoded_cuk
];
$direct_playlist = str_replace($search, $replace, $hs);

// Build JSON response structure
$response = [
    "cid" => $cid,
    "id" => $id,
    "playlist_url" => $url,
    "headers" => [
        "User-Agent" => $user_agent,
        "Cookie" => $cookie
    ],
    "refreshed_cookie" => $decoded_cuk,
    "refreshed_cookie_hex" => $cuk,
    "raw_playlist" => $hs,
    "direct_playlist" => $direct_playlist
];

// Output structured JSON response
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
exit;
