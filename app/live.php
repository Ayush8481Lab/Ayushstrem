<?php
// API Mode: Returns the fetched Jio URL as JSON data instead of streaming.

error_reporting(0);
include "functions.php";

// Set headers to return JSON text instead of a video playlist
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// 1. Get the Channel ID
$id = htmlspecialchars($_REQUEST['id'] ?? '');

if (empty($id)) {
    echo json_encode(["status" => "error", "message" => "No channel ID provided"]);
    exit;
}

// 2. Fetch data from Jio
$haystack = getJioTvData($id);

// 3. Check if token needs to be refreshed
if (empty($haystack->code) || $haystack->code !== 200) {
    refresh_token();
    
    // Fetch again immediately after refreshing the token
    $haystack = getJioTvData($id);
    
    // If it STILL fails after refresh, output an error
    if (empty($haystack->code) || $haystack->code !== 200) {
        echo json_encode(["status" => "error", "message" => "Failed to authenticate with JioTV"]);
        exit;
    }
}

// 4. Output the raw data as JSON
$jio_url = $haystack->result;

echo json_encode([
    "status" => "success",
    "channel_id" => $id,
    "stream_url" => $jio_url
], JSON_UNESCAPED_SLASHES);

exit;
?>
