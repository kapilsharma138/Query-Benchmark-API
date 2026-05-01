# Query Benchmark API

> **89.43% query performance improvement** — demonstrated with live MySQL EXPLAIN analysis on real data.

Built by a Backend Engineer who reduced dashboard query times by 50% on government portals serving 10,000+ stakeholders (DRDO, NCDC). This Laravel API reproduces those exact optimisation techniques — measurable, reproducible, and fully explained.

---

## Results

Tested on a `reports` table with realistic dataset. Filter: `WHERE name = 'Becker-Braun'`.

| Metric | Slow Query | Optimised Query | Improvement |
|---|---|---|---|
| **Execution time** | 17.331ms | 1.832ms | **89.43% faster** |
| **Rows scanned** | 10 | 10 | — |
| **Index used** | `idx_name` | `idx_name` | — |
| **Columns fetched** | ALL (`SELECT *`) | `id, name` only | Reduced payload |

> The difference isn't the index — both queries use the same index. The improvement comes entirely from **selective column fetching** (`SELECT id, name` vs `SELECT *`). Fewer bytes transferred from database to application = faster response. This is the real-world lesson.

---

## What this demonstrates

**1. MySQL EXPLAIN in practice**
Every endpoint runs `EXPLAIN` on its own query and returns the execution plan alongside results — rows scanned, index used, query type. No guessing what MySQL is doing internally.

**2. SELECT * vs selective columns**
Both queries scan the same 10 rows using the same index. The only difference is what gets transferred. At 50,000+ rows in production, this gap compounds significantly.

**3. Measurable before/after**
The `/compare-with-explain` endpoint runs both queries in a single request and returns a structured diff — timing, index usage, rows scanned, and improvement percentage. The number is the proof.

---

## Endpoints

```
GET /reports/slow                  — Full table fetch, SELECT *, no filters
GET /reports/optimized             — SELECT id, name only
GET /reports/compare               — Side-by-side timing diff

GET /reports/slow-with-explain     — Slow query + MySQL EXPLAIN output
GET /reports/optimized-with-explain — Optimised query + MySQL EXPLAIN output
GET /reports/compare-with-explain  — Full comparison: timing + EXPLAIN + improvement %
```

---

## Sample response — `/comparewithexplain`

```json
{
    "slow": {
        "time_ms": 17.331,
        "rows_scanned": 10,
        "index_used": "idx_name",
        "row_count": 10
    },
    "fast": {
        "time_ms": 1.832,
        "rows_scanned": 10,
        "index_used": "idx_name",
        "row_count": 10
    },
    "compare": {
        "slow_ms": 17.331,
        "fast_ms": 1.832,
        "improvement_pct": 89.43,
        "slow_rows_scanned": 10,
        "fast_rows_scanned": 10,
        "slow_index_used": "idx_name",
        "fast_index_used": "idx_name"
    }
}
```

---

## How it works

```php
// SLOW — fetches every column, transfers full row data
DB::table('reports')
    ->where('name', 'Becker-Braun')
    ->get();  // SELECT * FROM reports WHERE name = ?

// OPTIMISED — fetches only what's needed
DB::table('reports')
    ->select('id', 'name')
    ->where('name', 'Becker-Braun')
    ->get();  // SELECT id, name FROM reports WHERE name = ?

// EXPLAIN — what MySQL actually did
DB::select("EXPLAIN SELECT id, name FROM reports WHERE name = ?", ['Becker-Braun']);
// returns: rows scanned, index used, query type, key length
```

---

## Key insight

Both queries use the `idx_name` index. The row count scanned is identical. **The 89% improvement is purely from column reduction** — transferring 2 columns instead of all columns across every matched row.

In production systems with wide tables (20+ columns), high-concurrency dashboards, or large result sets, this difference scales dramatically. The technique is the same one used to reduce load times by 50% on cooperative sector dashboards at Silver Touch Technologies.

---

## Stack

- **Framework:** Laravel (Modular — `Modules\Reports`)
- **Database:** MySQL with `idx_name` index on `reports.name`
- **Query profiling:** `microtime()` + MySQL `EXPLAIN`
- **Architecture:** RESTful API · Controller-based · Modular Laravel structure

---

## Run locally

```bash
git clone https://github.com/kapilsharma138/query-benchmark-api
cd query-benchmark-api
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* in .env, then:
php artisan migrate
php artisan db:seed --class=ReportsSeeder
php artisan serve

# Test
curl http://localhost:8000/reports/comparewithexplain
```

---

## Next steps in this project

- [ ] Add Redis caching layer — show cache hit vs database hit timing
- [ ] Add composite index demo `(name, status)` vs single column index
- [ ] Add `EXPLAIN ANALYZE` (MySQL 8.0+) for deeper execution stats
- [ ] Deploy to AWS EC2 + RDS — live URL

---

*Part of a series of public backend engineering demos. See also: Job Alert Bot (Laravel + AWS Lambda + Telegram) · CV–JD Matcher (Laravel CLI + scoring engine).*