<?php
$url = "https://scholar.google.com/citations?user=hnqwGugAAAAJ&hl=en&cstart=0&pagesize=5";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64)");
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$html = curl_exec($ch);
curl_close($ch);
$dom = new \DOMDocument();
@$dom->loadHTML($html);
$xpath = new \DOMXPath($dom);
$rows = $xpath->query("//tr[@class=\"gsc_a_tr\"]");
foreach ($rows as $row) {
    $titleNode = $xpath->query(".//a[@class=\"gsc_a_at\"]", $row)->item(0);
    if ($titleNode) echo $titleNode->textContent . "\n";
}
