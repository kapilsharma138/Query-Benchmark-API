<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use Illuminate\Support\Facades\Cache;
use Illuminate\Http\JsonResponse;

class ReportsController extends Controller
{
    public function slow()
    {
        $slowstart = microtime(true);
        $slowquery = DB::table('reports')->get()->toArray();
        $slowelapsed = (microtime(true) - $slowstart) * 1000; // ms

        $slowresult = [
            "time_taken" => $slowelapsed,
            "row_count" => $slowquery,
        ];

        return $slowresult;
    }

    public function optimized()
    {
        $faststart = microtime(true);
        $optimizedquery = DB::table('reports')->select('id', 'name')->get()->toArray();
        $fastelapsed = (microtime(true) - $faststart) * 1000; // ms

        $fastresult = [
            "time_taken" => $fastelapsed,
            "row_count" => $optimizedquery,
        ];

        return $fastresult;
    }
    
    public function compare()
    {     
        $slowResult  = $this->slow();
        $fastResult  = $this->optimized();

        $compare = [
            'slow_ms' => $slowResult['time_taken'], 
            'fast_ms' => $fastResult['time_taken'],
            'improvement_pct' => (($slowResult['time_taken'] - $fastResult['time_taken']) / $slowResult['time_taken']) * 100
        ];

        return $compare;
    }


    public function slowwithexplain()
    {
        $start = microtime(true);

        $query = DB::table('reports')->where('name', 'Becker-Braun')->get()->toArray();

        $elapsed = (microtime(true) - $start) * 1000;

        // Ask MySQL what it just did
        $explain = DB::select("EXPLAIN SELECT * FROM reports WHERE name = 'Becker-Braun'");

        return [
            "time_ms"      => $elapsed,
            "rows_scanned" => $explain[0]->rows,
            "index_used"   => $explain[0]->key ?? 'none',
            "row_count"    => count($query),
        ];
    }

    public function optimizedwithexplain()
    {
        $start = microtime(true);

        $query = DB::table('reports')
                    ->select('id', 'name')
                    ->where('name', 'Becker-Braun')
                    ->get()
                    ->toArray();

        $elapsed = (microtime(true) - $start) * 1000;

        $explain = DB::select("EXPLAIN SELECT id, name FROM reports WHERE name = 'Becker-Braun'");

        return [
            "time_ms"      => $elapsed,
            "rows_scanned" => $explain[0]->rows,
            "index_used"   => $explain[0]->key ?? 'none',
            "row_count"    => count($query),
        ];
    }
    
    public function comparewithexplain()
    {
        $slowResult = $this->slowwithexplain();
        $fastResult = $this->optimizedwithexplain();

        $slowMs = $slowResult['time_ms'];
        $fastMs = $fastResult['time_ms'];

        $improvement = $slowMs > 0
            ? (($slowMs - $fastMs) / $slowMs) * 100
            : 0;

        return response()->json([
            'slow' => $slowResult,
            'fast' => $fastResult,
            'compare' => [
                'slow_ms' => round($slowMs, 3),
                'fast_ms' => round($fastMs, 3),
                'improvement_pct' => round($improvement, 2),
                'slow_rows_scanned' => $slowResult['rows_scanned'],
                'fast_rows_scanned' => $fastResult['rows_scanned'],
                'slow_index_used' => $slowResult['index_used'],
                'fast_index_used' => $fastResult['index_used'],
            ],
        ]);
    }

    public function cachedWithExplain()
    {
        $cacheKey = 'benchmark:reports:becker-braun';

        // ── FIRST CALL SIMULATION (fresh — no cache) ──────────────────
        Cache::forget($cacheKey); // clear so we can measure DB hit fresh

        $dbStart = microtime(true);
        $dbResult = Cache::remember($cacheKey, 300, function () {
            return DB::table('reports')
                ->select('id', 'name')
                ->where('name', 'Becker-Braun')
                ->get()
                ->toArray();
        });
        $dbTime = round((microtime(true) - $dbStart) * 1000, 3);

        $explain = DB::select("EXPLAIN SELECT id, name FROM reports WHERE name = 'Becker-Braun'");

        // ── SECOND CALL SIMULATION (cache hit) ────────────────────────
        $cacheStart = microtime(true);
        $cachedResult = Cache::remember($cacheKey, 300, function () {
            return DB::table('reports')
                ->select('id', 'name')
                ->where('name', 'Becker-Braun')
                ->get()
                ->toArray();
        });
        $cacheTime = round((microtime(true) - $cacheStart) * 1000, 3);

        // ── IMPROVEMENT CALCULATIONS ──────────────────────────────────
        $slowMs     = 17.331; // your real slow baseline from earlier
        $vsSlowPct  = round((($slowMs - $cacheTime) / $slowMs) * 100, 2);
        $vsFastPct  = round((($dbTime - $cacheTime) / $dbTime) * 100, 2);

        return response()->json([
            'first_call' => [
                'served_from'  => 'database',
                'time_ms'      => $dbTime,
                'rows'         => count($dbResult),
                'index_used'   => $explain[0]->key ?? 'none',
                'rows_scanned' => $explain[0]->rows,
            ],
            'second_call' => [
                'served_from'      => 'redis_cache',
                'time_ms'          => $cacheTime,
                'rows'             => count($cachedResult),
                'cache_ttl_seconds'=> 300,
            ],
            'compare' => [
                'slow_baseline_ms'        => $slowMs,
                'db_optimised_ms'         => $dbTime,
                'redis_cached_ms'         => $cacheTime,
                'cache_vs_db_improvement' => $vsFastPct . '%',
                'cache_vs_slow_improvement' => $vsSlowPct . '%',
                'summary' => "Redis cache is {$vsFastPct}% faster than optimised DB, and {$vsSlowPct}% faster than the slow baseline",
            ],
        ]);
    }

}