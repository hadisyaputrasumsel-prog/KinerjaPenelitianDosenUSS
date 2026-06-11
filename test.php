<?php
$url = "https://sinta.kemdiktisaintek.go.id/authors/profile/6784225/?view=googlescholar";
$options = [
    "http" => [
        "method" => "GET",
        "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n" .
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
    ],
    "ssl" => [
        "verify_peer" => false,
        "verify_peer_name" => false,
    ]
];
$context = stream_context_create($options);
$html = file_get_contents($url, false, $context);
file_put_contents('profile_scholar.html', $html);
