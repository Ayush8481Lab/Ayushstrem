<?php
// DIRECT CDN REWRITER - Forces the player to fetch .ts chunks directly from Jio
error_reporting(0);
include "functions.php";

header("Content-Type: application/vnd.apple.mpegurl");
header("Access-Control-Allow-Origin: *");

// 1. Get Channel ID
$id = htmlspecialchars($_REQUEST['id'] ?? '');
if (empty($id)) exit;

// 2. Fetch Master Menu from Jio
$haystack = getJioTvData($id);
if (empty($haystack->code) || $haystack->code !== 200) {
    refresh_token();
    header("Location: {$_SERVER['REQUEST_URI']}");
    exit;
}

$jio_url = $haystack->result;

// 3. Fetch the content of the Master Menu (index.m3u8)
$headers_1 = ["User-Agent: plaYtv/7.1.3 (Linux;Android 14) ExoPlayerLib/2.11.7"];
$playlist = cUrlGetData($jio_url, $headers_1);

// 4. Extract Jio's Base Server URL and Authentication Token
$parsed_url = parse_url($jio_url);
$base_dir = $parsed_url['scheme'] . '://' . $parsed_url['host'] . substr($parsed_url['path'], 0, strrpos($parsed_url['path'], '/') + 1);
$query_string = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';

// 5. Rewrite the internal playlists to point directly to Jio's CDN
$lines = explode("\n", $playlist);
$final_playlist = "";

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    if (str_starts_with($line, '#')) {
        // Fix internal Audio/Video URI tracks
        if (preg_match('/URI="([^"]+)"/', $line, $matches)) {
            $uri = $matches[1];
            if (!str_starts_with($uri, 'http')) {
                $absolute_uri = $base_dir . $uri . $query_string;
                $line = str_replace('URI="' . $uri . '"', 'URI="' . $absolute_uri . '"', $line);
            }
        }
        $final_playlist .= $line . "\n";
    } else {
        // Convert relative playlist links to absolute Jio links + Append Token
        if (!str_starts_with($line, 'http')) {
            $line = $base_dir . $line . $query_string;
        }
        $final_playlist .= $line . "\n";
    }
}

// 6. Output the rewritten playlist to your video player
echo $final_playlist;
exit;
?>
