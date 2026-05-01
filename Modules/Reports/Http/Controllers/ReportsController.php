<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

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

}