<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function __construct()
    {
        // Gunakan konfigurasi default dari .env
    }

    public function index()
    {
        $lecturers = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')
            ->get()->map(function($item) {
                return (array) $item;
            })->toArray();

        // Calculate Stats
        $totalLecturers = count($lecturers);
        $totalResearch = array_sum(array_column($lecturers, 'scholar')) + array_sum(array_column($lecturers, 'scopus'));
        $avgSinta = $totalLecturers > 0 ? round(array_sum(array_column($lecturers, 'sintaOverall')) / $totalLecturers) : 0;
        $avgSinta3Yr = $totalLecturers > 0 ? round(array_sum(array_column($lecturers, 'sinta3Yr')) / $totalLecturers) : 0;
        
        $stats = [
            'totalLecturers' => $totalLecturers,
            'totalResearch' => $totalResearch,
            'totalPengabdian' => array_sum(array_column($lecturers, 'pengabdian')),
            'avgSinta' => $avgSinta,
            'avgSinta3Yr' => $avgSinta3Yr,
            'productivityRatio' => $totalLecturers > 0 ? round((count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] >= 50)) / $totalLecturers) * 100) : 0,
            'productiveCount' => count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] >= 50)),
            'lessActiveCount' => count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] < 50)),
            'unggulCount' => count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] > $avgSinta3Yr)),
            'baikCount' => count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] > 0 && $l['sinta3Yr'] <= $avgSinta3Yr)),
            'perluCount' => count(array_filter($lecturers, fn($l) => $l['sinta3Yr'] == 0)),
        ];

        return view('dashboard', compact('lecturers', 'stats'));
    }

    public function lecturers()
    {
        $lecturers = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')->get()->map(function($item) {
            return (array) $item;
        })->toArray();
        
        // Stats for filtering
        $totalLecturers = count($lecturers);
        $avgSinta3Yr = $totalLecturers > 0 ? round(array_sum(array_column($lecturers, 'sinta3Yr')) / $totalLecturers) : 0;

        return view('lecturers', compact('lecturers', 'avgSinta3Yr'));
    }

    public function crawl()
    {
        return view('crawl');
    }

    public function analytics()
    {
        $lecturers = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')
            ->get()->map(function($item) {
                return (array) $item;
            })->toArray();
            
        return view('analytics', compact('lecturers'));
    }

    public function sintaProxy(\Illuminate\Http\Request $request)
    {
        $name = $request->query('name');
        if (!$name) {
            return response()->json(["error" => "No name provided"]);
        }

        $affId = env('SINTA_AFFILIATION_ID', '8263');
        $url = "https://sinta.kemdiktisaintek.go.id/authors?aff=" . $affId . "&q=" . urlencode($name);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $headers = [
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
            'Accept-Language: en-US,en;q=0.9,id;q=0.8',
            'Connection: keep-alive',
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $html = curl_exec($ch);
        curl_close($ch);

        if (preg_match_all('/profile\/(\d+)/', $html, $matches)) {
            $id = $matches[1][0];
            return response()->json(["success" => true, "id" => $id]);
        } else {
            return response()->json(["success" => false, "message" => "ID not found or blocked by Cloudflare"]);
        }
    }

    public function accreditation(\Illuminate\Http\Request $request)
    {
        $selectedProdi = $request->query('prodi');
        
        $prodis = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')
            ->select('prodi')->whereNotNull('prodi')->where('prodi', '!=', '')
            ->distinct()->pluck('prodi')->toArray();

        $researchQuery = \Illuminate\Support\Facades\DB::connection('mysql')->table('research')
            ->join('lecturers', 'research.lecturerId', '=', 'lecturers.id')
            ->select('research.*', 'lecturers.prodi');
            
        $pubQuery = \Illuminate\Support\Facades\DB::connection('mysql')->table('publications')
            ->join('lecturers', 'publications.lecturerId', '=', 'lecturers.id')
            ->select('publications.*', 'lecturers.prodi');

        if ($selectedProdi) {
            $researchQuery->where('lecturers.prodi', $selectedProdi);
            $pubQuery->where('lecturers.prodi', $selectedProdi);
        }

        $research = $researchQuery->get()->map(function($item) { return (array) $item; })->toArray();
        $publications = $pubQuery->get()->map(function($item) { return (array) $item; })->toArray();

        // Define years
        $ts = 2026;
        $ts1 = 2025;
        $ts2 = 2024;

        // Table 3.b.2 Logic
        $t3b2_data = [
            'pt_mandiri' => ['ts2' => [], 'ts1' => [], 'ts' => []],
            'nasional' => ['ts2' => [], 'ts1' => [], 'ts' => []],
            'internasional' => ['ts2' => [], 'ts1' => [], 'ts' => []],
        ];

        foreach ($research as $r) {
            $yearKey = $r['year'] == $ts ? 'ts' : ($r['year'] == $ts1 ? 'ts1' : ($r['year'] == $ts2 ? 'ts2' : null));
            if (!$yearKey) continue;

            if ($r['source'] == 'Perguruan Tinggi' || $r['source'] == 'Mandiri') {
                $t3b2_data['pt_mandiri'][$yearKey][] = $r;
            } elseif ($r['source'] == 'Lembaga Dalam Negeri') {
                $t3b2_data['nasional'][$yearKey][] = $r;
            } elseif ($r['source'] == 'Lembaga Luar Negeri') {
                $t3b2_data['internasional'][$yearKey][] = $r;
            }
        }

        $table_3b2 = [
            [
                'no' => 1,
                'sumber' => "a) Perguruan tinggi\nb) Mandiri",
                'ts2' => count($t3b2_data['pt_mandiri']['ts2']),
                'ts1' => count($t3b2_data['pt_mandiri']['ts1']),
                'ts' => count($t3b2_data['pt_mandiri']['ts']),
                'ts2_items' => $t3b2_data['pt_mandiri']['ts2'],
                'ts1_items' => $t3b2_data['pt_mandiri']['ts1'],
                'ts_items' => $t3b2_data['pt_mandiri']['ts'],
                'jumlah' => count($t3b2_data['pt_mandiri']['ts2']) + count($t3b2_data['pt_mandiri']['ts1']) + count($t3b2_data['pt_mandiri']['ts']),
                'jumlah_items' => array_merge($t3b2_data['pt_mandiri']['ts2'], $t3b2_data['pt_mandiri']['ts1'], $t3b2_data['pt_mandiri']['ts'])
            ],
            [
                'no' => 2,
                'sumber' => 'Lembaga dalam negeri (diluar PT)',
                'ts2' => count($t3b2_data['nasional']['ts2']),
                'ts1' => count($t3b2_data['nasional']['ts1']),
                'ts' => count($t3b2_data['nasional']['ts']),
                'ts2_items' => $t3b2_data['nasional']['ts2'],
                'ts1_items' => $t3b2_data['nasional']['ts1'],
                'ts_items' => $t3b2_data['nasional']['ts'],
                'jumlah' => count($t3b2_data['nasional']['ts2']) + count($t3b2_data['nasional']['ts1']) + count($t3b2_data['nasional']['ts']),
                'jumlah_items' => array_merge($t3b2_data['nasional']['ts2'], $t3b2_data['nasional']['ts1'], $t3b2_data['nasional']['ts'])
            ],
            [
                'no' => 3,
                'sumber' => 'Lembaga luar negeri',
                'ts2' => count($t3b2_data['internasional']['ts2']),
                'ts1' => count($t3b2_data['internasional']['ts1']),
                'ts' => count($t3b2_data['internasional']['ts']),
                'ts2_items' => $t3b2_data['internasional']['ts2'],
                'ts1_items' => $t3b2_data['internasional']['ts1'],
                'ts_items' => $t3b2_data['internasional']['ts'],
                'jumlah' => count($t3b2_data['internasional']['ts2']) + count($t3b2_data['internasional']['ts1']) + count($t3b2_data['internasional']['ts']),
                'jumlah_items' => array_merge($t3b2_data['internasional']['ts2'], $t3b2_data['internasional']['ts1'], $t3b2_data['internasional']['ts'])
            ]
        ];

        // Table 3.b.4 Logic
        $t3b4_data = array_fill(1, 10, ['ts2' => [], 'ts1' => [], 'ts' => []]);

        foreach ($publications as $p) {
            $yearKey = $p['year'] == $ts ? 'ts' : ($p['year'] == $ts1 ? 'ts1' : ($p['year'] == $ts2 ? 'ts2' : null));
            if (!$yearKey) continue;

            $source = $p['source'];
            if ($source == 'Scholar') {
                $t3b4_data[1][$yearKey][] = $p;
            } elseif (str_contains($source, 'SINTA')) {
                $t3b4_data[2][$yearKey][] = $p;
            } elseif ($source == 'Scopus Q3' || $source == 'Scopus Q4') {
                $t3b4_data[3][$yearKey][] = $p;
            } elseif ($source == 'Scopus Q1' || $source == 'Scopus Q2') {
                $t3b4_data[4][$yearKey][] = $p;
            } else {
                // If it's Pengabdian or something else, default to category 5 for now
                $t3b4_data[5][$yearKey][] = $p;
            }
        }

        $table_3b4_names = [
            1 => 'Jurnal penelitian tidak terakreditasi',
            2 => 'Jurnal penelitian nasional terakreditasi',
            3 => 'Jurnal penelitian internasional',
            4 => 'Jurnal penelitian internasional bereputasi',
            5 => 'Seminar wilayah/lokal/perguruan tinggi',
            6 => 'Seminar nasional',
            7 => 'Seminar internasional',
            8 => 'Pagelaran/pameran/presentasi dalam forum di tingkat wilayah',
            9 => 'Pagelaran/pameran/presentasi dalam forum di tingkat nasional',
            10 => 'Pagelaran/pameran/presentasi dalam forum di tingkat internasional',
        ];

        $table_3b4 = [];
        foreach ($table_3b4_names as $no => $name) {
            $table_3b4[] = [
                'no' => $no,
                'jenis' => $name,
                'ts2' => count($t3b4_data[$no]['ts2']),
                'ts1' => count($t3b4_data[$no]['ts1']),
                'ts' => count($t3b4_data[$no]['ts']),
                'ts2_items' => $t3b4_data[$no]['ts2'],
                'ts1_items' => $t3b4_data[$no]['ts1'],
                'ts_items' => $t3b4_data[$no]['ts'],
                'jumlah' => count($t3b4_data[$no]['ts2']) + count($t3b4_data[$no]['ts1']) + count($t3b4_data[$no]['ts']),
                'jumlah_items' => array_merge($t3b4_data[$no]['ts2'], $t3b4_data[$no]['ts1'], $t3b4_data[$no]['ts'])
            ];
        }

        return view('accreditation', compact('table_3b2', 'table_3b4', 'prodis', 'selectedProdi'));
    }

    public function crawlScholar(\Illuminate\Http\Request $request)
    {
        $name = $request->input('name');
        if (!$name) {
            return response()->json(["success" => false, "message" => "Name is required"]);
        }

        $url = "https://scholar.google.com/citations?view_op=search_authors&mauthors=" . urlencode($name);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $html = curl_exec($ch);
        curl_close($ch);

        // Find Scholar ID
        if (preg_match('/user=([^&"]+)/', $html, $matches)) {
            $scholarId = $matches[1];
            
            // Now fetch profile
            $profileUrl = "https://scholar.google.com/citations?user=" . $scholarId;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $profileUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            $profileHtml = curl_exec($ch);
            curl_close($ch);

            // Parse publications (simple regex for titles)
            if (preg_match_all('/class="gsc_a_at">([^<]+)<\/a>/', $profileHtml, $titleMatches)) {
                $titles = $titleMatches[1];
                return response()->json([
                    "success" => true, 
                    "scholarId" => $scholarId,
                    "publications_count" => count($titles),
                    "titles" => array_slice($titles, 0, 5) // Return top 5
                ]);
            }

            return response()->json(["success" => true, "scholarId" => $scholarId, "message" => "Found profile but failed to parse publications"]);
        }

        return response()->json(["success" => false, "message" => "Profile not found on Google Scholar"]);
    }

    public function crawlSinta(\Illuminate\Http\Request $request)
    {
        // Execute the standalone scrape_uss.php script
        $scriptPath = base_path('scrape_uss.php');
        if (!file_exists($scriptPath)) {
            return response()->json(["success" => false, "message" => "Scraper script not found."]);
        }

        // We run it via shell_exec
        $output = shell_exec('php ' . escapeshellarg($scriptPath) . ' 2>&1');
        
        return response()->json([
            "success" => true, 
            "message" => "Proses sinkronisasi SINTA berhasil dijalankan.",
            "output" => $output
        ]);
    }

    public function updateLecturer(\Illuminate\Http\Request $request)
    {
        $id = $request->input('id');
        $status = $request->input('status');
        $sintaId = $request->input('sintaId');

        if ($status === 'Berhenti') {
            \Illuminate\Support\Facades\DB::connection('mysql')->table('research')->where('lecturerId', $id)->delete();
            \Illuminate\Support\Facades\DB::connection('mysql')->table('publications')->where('lecturerId', $id)->delete();
            \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')->where('id', $id)->delete();
            
            return response()->json(["success" => true, "message" => "Data dosen berhasil dihapus"]);
        }

        $data = [];
        if ($status) $data['status'] = $status;
        if ($sintaId) $data['sintaId'] = $sintaId;
        if ($request->has('scopusId')) $data['scopusId'] = $request->input('scopusId');
        if ($request->has('garudaId')) $data['garudaId'] = $request->input('garudaId');
        if ($request->has('scholarId')) $data['scholarId'] = $request->input('scholarId');

        if (empty($data)) {
            return response()->json(["success" => false, "message" => "No data to update"]);
        }

        $updated = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')
            ->where('id', $id)
            ->update($data);

        return response()->json(["success" => true]);
    }
    public function syncLecturerSinta(\Illuminate\Http\Request $request)
    {
        $id = $request->input('id');
        $lecturer = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')->where('id', $id)->first();

        if (!$lecturer || !$lecturer->sintaId) {
            return response()->json(["success" => false, "message" => "Dosen tidak ditemukan atau belum memiliki SINTA ID."]);
        }

        $sintaId = $lecturer->sintaId;
        $profileUrl = "https://sinta.kemdiktisaintek.go.id/authors/profile/$sintaId";
        
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
        $htmlProfile = @file_get_contents($profileUrl, false, $context);

        if (!$htmlProfile) {
            return response()->json(["success" => false, "message" => "Gagal mengambil profil dari SINTA."]);
        }

        $domP = new \DOMDocument();
        @$domP->loadHTML($htmlProfile);
        $xpathP = new \DOMXPath($domP);
        
        $scopusDocs = 0;
        $scholarCitations = 0;
        $hIndex = 0;
        $scopusHIndex = 0;

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

        \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')
            ->where('id', $id)
            ->update([
                'scholar' => $scholarCitations,
                'scopus' => $scopusDocs,
                'scopusHIndex' => $scopusHIndex,
                'hIndex' => $hIndex
            ]);

        return response()->json([
            "success" => true, 
            "message" => "Data berhasil disinkronisasi",
            "data" => [
                'scholar' => $scholarCitations,
                'scopus' => $scopusDocs,
                'scopusHIndex' => $scopusHIndex,
                'hIndex' => $hIndex
            ]
        ]);
    }

    public function getPublications($id)
    {
        $publications = \Illuminate\Support\Facades\DB::connection('mysql')->table('publications')
            ->where('lecturerId', $id)
            ->orderBy('year', 'desc')
            ->get();
        return response()->json(["success" => true, "data" => $publications]);
    }

    public function syncPublications(\Illuminate\Http\Request $request)
    {
        $id = $request->id;
        $lecturer = \Illuminate\Support\Facades\DB::connection('mysql')->table('lecturers')->where('id', $id)->first();
        
        if (!$lecturer) {
            return response()->json(["success" => false, "message" => "Dosen tidak ditemukan"]);
        }

        $addedCount = 0;

        // 1. Scraping Google Scholar (Jika ada Scholar ID)
        if (!empty($lecturer->scholarId)) {
            $url = "https://scholar.google.com/citations?user=" . $lecturer->scholarId . "&hl=en&cstart=0&pagesize=100";
            
            try {
                $response = \Illuminate\Support\Facades\Http::withOptions(['verify' => false])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36'])
                    ->get($url);

                if ($response->successful()) {
                    $html = $response->body();
                    $dom = new \DOMDocument();
                    @$dom->loadHTML($html);
                    $xpath = new \DOMXPath($dom);
                    
                    $rows = $xpath->query('//tr[@class="gsc_a_tr"]');
                    if ($rows) {
                        foreach ($rows as $row) {
                            $titleNode = $xpath->query('.//a[@class="gsc_a_at"]', $row)->item(0);
                            $sourceNode = $xpath->query('.//div[@class="gs_gray"]', $row)->item(1); // venue
                            $yearNode = $xpath->query('.//span[@class="gsc_a_h gsc_a_hc gs_ibl"]', $row)->item(0);
                            $citeNode = $xpath->query('.//a[@class="gsc_a_ac gs_ibl"]', $row)->item(0);

                            if ($titleNode) {
                                $title = trim($titleNode->textContent);
                                $link = "https://scholar.google.com" . $titleNode->getAttribute('href');
                                $source = $sourceNode ? trim($sourceNode->textContent) : 'Google Scholar';
                                $year = $yearNode && trim($yearNode->textContent) !== '' ? (int) trim($yearNode->textContent) : date('Y');
                                $citations = $citeNode && trim($citeNode->textContent) !== '' ? (int) trim($citeNode->textContent) : 0;

                                // Prevent duplicates
                                $exists = \Illuminate\Support\Facades\DB::connection('mysql')->table('publications')
                                    ->where('lecturerId', $id)
                                    ->where('title', $title)
                                    ->exists();
                                
                                if (!$exists) {
                                    \Illuminate\Support\Facades\DB::connection('mysql')->table('publications')->insert([
                                        'lecturerId' => $id,
                                        'title' => $title,
                                        'source' => substr($source, 0, 255),
                                        'year' => $year,
                                        'citations' => $citations,
                                        'url' => $link,
                                        'created_at' => now(),
                                        'updated_at' => now()
                                    ]);
                                    $addedCount++;
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Silently ignore Google Scholar blocking to allow Garuda to process if exists
            }
        }

        // Return result
        if ($addedCount > 0) {
            return response()->json([
                "success" => true, 
                "message" => "Berhasil menarik $addedCount publikasi baru dari profil eksternal (Google Scholar)."
            ]);
        } else {
            return response()->json([
                "success" => true, 
                "message" => "Sinkronisasi selesai. Tidak ada publikasi baru yang ditemukan."
            ]);
        }
    }
}
