<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;

$affId = 8263;
$page = 1;
$lecturers = [];

while(true) {
    echo "Scraping page $page...\n";
    $url = "https://sinta.kemdiktisaintek.go.id/affiliations/authors/$affId?page=$page";
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
    $html = @file_get_contents($url, false, $context);

    if (empty($html)) {
        echo "Gagal mengambil data dari halaman $page (Mungkin diblokir oleh SINTA/Cloudflare atau halaman kosong). Berhenti.\n";
        break;
    }

    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);

    $items = $xpath->query('//div[contains(@class, "au-item")]');

    if ($items->length == 0) {
        echo "No authors found on this page. Stopping.\n";
        break;
    }

    foreach ($items as $item) {
        // Name & Sinta ID
        $nameNode = $xpath->query('.//div[@class="profile-name"]/a', $item)->item(0);
        if (!$nameNode) continue;
        $name = trim($nameNode->textContent);
        $href = $nameNode->getAttribute('href');
        $sintaId = 0;
        if (preg_match('/profile\/(\d+)/', $href, $m)) {
            $sintaId = $m[1];
        }

        // Prodi
        $prodiNode = $xpath->query('.//div[@class="profile-dept"]/a', $item)->item(0);
        $prodi = $prodiNode ? trim(preg_replace('/<i[^>]*><\/i>/', '', $prodiNode->nodeValue)) : 'Belum Diketahui';
        $prodi = trim($prodi);

        // Gunakan konfigurasi override prodi jika ada
        $prodiConfig = config('prodi', []);
        if (array_key_exists($sintaId, $prodiConfig)) {
            $prodi = $prodiConfig[$sintaId];
        } elseif (empty($prodi) || $prodi === 'Belum Diketahui') {
            $prodi = 'Unknown';
        }

        // Stats
        $statNodes = $xpath->query('.//div[contains(@class, "stat-num")]', $item);
        $sinta3Yr = 0;
        $sintaOverall = 0;
        if ($statNodes->length >= 2) {
            $sinta3Yr = (int) str_replace(',', '', trim($statNodes->item(0)->textContent));
            $sintaOverall = (int) str_replace(',', '', trim($statNodes->item(1)->textContent));
        }

        // Image URL
        $imgNode = $xpath->query('.//img[contains(@class, "avatar")]', $item)->item(0);
        $imageUrl = $imgNode ? $imgNode->getAttribute('src') : null;

        $scopusDocs = 0;
        $scholarCitations = 0;
        $hIndex = 0;
        $scopusHIndex = 0;
        $scholarId = null;

        if ($sintaId > 0) {
            $profileUrl = "https://sinta.kemdiktisaintek.go.id/authors/profile/$sintaId";
            $htmlProfile = @file_get_contents($profileUrl, false, $context);
            if ($htmlProfile) {
                // Parse Scholar ID
                if (preg_match('/scholar\.google\.[^\/]+\/citations\?user=([^&"\' ]+)/', $htmlProfile, $m)) {
                    $scholarId = $m[1];
                }

                if ($scholarId && (!$imageUrl || str_contains(strtolower($imageUrl), 'default') || str_contains(strtolower($imageUrl), 'avatar'))) {
                    $imageUrl = "https://scholar.googleusercontent.com/citations?view_op=view_photo&user=" . $scholarId . "&citpid=1";
                }

                $domP = new DOMDocument();
                @$domP->loadHTML($htmlProfile);
                $xpathP = new DOMXPath($domP);
                
                $trs = $xpathP->query('//table//tr');
                foreach ($trs as $tr) {
                    $tds = $xpathP->query('.//td', $tr);
                    if ($tds->length >= 4) {
                        $label = trim($tds->item(0)->textContent);
                        $scopusVal = (int) str_replace(',', '', trim($tds->item(1)->textContent));
                        $gscholarVal = (int) str_replace(',', '', trim($tds->item(2)->textContent));
                        
                        if ($label == 'Article') {
                            $scopusDocs = $scopusVal;
                        } elseif ($label == 'Citation') {
                            $scholarCitations = $gscholarVal;
                        } elseif ($label == 'H-Index') {
                            $scopusHIndex = $scopusVal;
                            $hIndex = $gscholarVal;
                        }
                    }
                }
            }
            sleep(1); // Avoid too many rapid requests
        }

        $lecturers[] = [
            'name' => $name,
            'sintaId' => $sintaId,
            'scholarId' => $scholarId,
            'prodi' => $prodi,
            'image_url' => $imageUrl,
            'sintaOverall' => $sintaOverall,
            'sinta3Yr' => $sinta3Yr,
            'scholar' => $scholarCitations,
            'scopus' => $scopusDocs,
            'scopusHIndex' => $scopusHIndex,
            'hIndex' => $hIndex
        ];
    }
    
    // Check if next page exists. The pagination usually has a link with ?page=2
    if (!strpos($html, "?page=" . ($page + 1))) {
        break;
    }
    $page++;
    sleep(1);
}

// Save to JSON as backup (Abaikan error permission jika direktori database tidak writable di production)
@file_put_contents(base_path("database/data/lecturers.json"), json_encode($lecturers, JSON_PRETTY_PRINT));
echo "Saved " . count($lecturers) . " lecturers to lecturers.json\n";

if (count($lecturers) > 0) {
    // Upsert to Database
    echo "Menyimpan ke Database...\n";
    foreach ($lecturers as $l) {
        DB::table('lecturers')->updateOrInsert(
            ['sintaId' => $l['sintaId']],
            [
                'name' => $l['name'],
                'scholarId' => $l['scholarId'],
                'prodi' => $l['prodi'],
                'image_url' => $l['image_url'],
                'sintaOverall' => $l['sintaOverall'],
                'sinta3Yr' => $l['sinta3Yr'],
                'scholar' => $l['scholar'],
                'scopus' => $l['scopus'],
                'scopusHIndex' => $l['scopusHIndex'],
                'hIndex' => $l['hIndex']
            ]
        );
    }
    echo "Selesai memperbarui database!\n";
} else {
    echo "PERINGATAN: Tidak ada data dosen yang berhasil ditarik karena diblokir oleh sistem keamanan SINTA (Cloudflare).\nSaran: Jalankan proses Crawl SINTA ini melalui komputer lokal (Localhost/Laragon) Anda, lalu ekspor databasenya ke production.\n";
}
