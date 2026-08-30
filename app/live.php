<?php
// Modified for Direct Streaming (No Server Proxy)
// This fetches the Jio stream and directs the client straight to Jio's CDN.

error_reporting(0);
include "functions.php";

// Response headers for video player
header("Content-Type: application/vnd.apple.mpegurl");
header("Access-Control-Allow-Origin: *");

// 1. Get the Channel ID
$id = htmlspecialchars($_REQUEST['id'] ?? '');
$haystack = getJioTvData($id);

// 2. Check if token needs to be refreshed
if (empty($haystack->code) || $haystack->code !== 200) {
    refresh_token();
    header("Location: {$_SERVER['REQUEST_URI']}");
    exit;
}

// 3. Extract the direct Jio CDN URL from the fetched data
$jio_url = $haystack->result;

// 4. Fetch the raw .m3u8 playlist from Jio
$headers_1 = ["User-Agent: plaYtv/7.1.3 (Linux;Android 14) ExoPlayerLib/2.11.7"];
$playlist = cUrlGetData($jio_url, $headers_1);

// 5. Parse the Jio URL to get the base path and authentication tokens
$parsed_url = parse_url($jio_url);
$base_dir = $parsed_url['scheme'] . '://' . $parsed_url['host'] . substr($parsed_url['path'], 0, strrpos($parsed_url['path'], '/') + 1);
$query_string = isset($parsed_url['query']) ? $parsed_url['query'] : '';

// 6. Fix internal URI tags (like multi-audio tracks) to point to Jio directly
$playlist = preg_replace_callback('/URI="([^"]+)"/', function($matches) use ($base_dir, $query_string) {
    $uri = $matches[1];
    if (!str_starts_with($uri, 'http')) {
        $uri = $base_dir . $uri; // Make it an absolute Jio URL
    }
    // Append the Jio token to the URI
    if (!empty($query_string)) {
        $separator = str_contains($uri, '?') ? '&' : '?';
        $uri .= $separator . $query_string;
    }
    return 'URI="' . $uri . '"';
}, $playlist);

// 7. Process the playlist line by line to fix video chunks (.ts files)
$lines = explode("\n", $playlist);
$final_playlist = "";

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    if (str_starts_with($line, '#')) {
        // Keep HLS configuration tags exactly as they are
        $final_playlist .= $line . "\n";
    } else {
        // It's a video file path. Make it point directly to Jio instead of proxy.
        if (!str_starts_with($line, 'http')) {
            $line = $base_dir . $line;
        }
        // Append the Jio authentication token
        if (!empty($query_string)) {
            $separator = str_contains($line, '?') ? '&' : '?';
            $line .= $separator . $query_string;
        }
        $final_playlist .= $line . "\n";
    }
}

// 8. Output the final direct playlist to the video player
echo $final_playlist;
exit;
?>
