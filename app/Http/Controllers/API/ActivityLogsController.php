<?php
// app/Http/Controllers/API/ActivityLogsController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class ActivityLogsController extends Controller
{
    /**
     * GET /api/activity-logs
     * Query (all optional):
     * - page, limit (default 50, max 500), sort=asc|desc (default desc)
     * - q (free text)
     * - module, activity (exact match)
     * - actor  -> matches performed_by exactly; also tries performed_by_name LIKE
     * - from,to (YYYY-MM-DD) on created_at|occurred_at|when
     */
public function index(Request $r)
{
    try {
        $table = 'user_data_activity_log';

        $page  = max((int) $r->query('page', 1), 1);
        $limit = min(max((int) $r->query('limit', 50), 1), 500);
        $sort  = strtolower((string) $r->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        $q         = trim((string) $r->query('q', ''));
        $module    = trim((string) $r->query('module', ''));
        $activity  = trim((string) $r->query('activity', ''));
        $actor     = trim((string) $r->query('actor', ''));
        $from      = trim((string) $r->query('from', ''));
        $to        = trim((string) $r->query('to', ''));

        $studentId = trim((string) (
            $r->query('student_id') ?? $r->query('user_id') ?? ''
        ));

        $columns = $this->getTableColumns($table);
        $builder = DB::table($table);

        /*
        |--------------------------------------------------------------------------
        | Student/User filter
        |--------------------------------------------------------------------------
        | Support both schemas:
        | 1) CRUD logs      -> performed_by / target / record_id
        | 2) Auth logs      -> target_id / target_type / performed_by
        */
        if ($studentId !== '') {
            if (!is_numeric($studentId)) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'student_id / user_id must be numeric'
                ], 422);
            }

            $studentIdInt = (int) $studentId;

            $builder->where(function ($w) use ($columns, $studentIdInt) {
                $hasAny = false;

                if (in_array('performed_by', $columns, true)) {
                    $w->orWhere('performed_by', $studentIdInt);
                    $hasAny = true;
                }

                if (in_array('target_id', $columns, true)) {
                    if (in_array('target_type', $columns, true)) {
                        $w->orWhere(function ($x) use ($studentIdInt) {
                            $x->where('target_id', $studentIdInt)
                              ->whereIn('target_type', ['user', 'student']);
                        });
                    } else {
                        $w->orWhere('target_id', $studentIdInt);
                    }
                    $hasAny = true;
                }

                if (in_array('target', $columns, true)) {
                    $w->orWhere('target', (string) $studentIdInt);
                    $w->orWhere('target', $studentIdInt);
                    $hasAny = true;
                }

                if (in_array('record_id', $columns, true)) {
                    $w->orWhere('record_id', (string) $studentIdInt);
                    $w->orWhere('record_id', $studentIdInt);
                    $hasAny = true;
                }

                if (!$hasAny) {
                    $w->whereRaw('1 = 0');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Exact filters
        |--------------------------------------------------------------------------
        */
        if ($module !== '' && in_array('module', $columns, true)) {
            $builder->where('module', $module);
        }

        if ($activity !== '' && in_array('activity', $columns, true)) {
            $builder->where('activity', $activity);
        }

        /*
        |--------------------------------------------------------------------------
        | Actor search
        |--------------------------------------------------------------------------
        */
        if ($actor !== '' && $studentId === '') {
            $builder->where(function ($w) use ($actor, $columns) {
                if (in_array('performed_by', $columns, true) && is_numeric($actor)) {
                    $w->orWhere('performed_by', (int) $actor);
                }
                if (in_array('performed_by_name', $columns, true)) {
                    $w->orWhere('performed_by_name', 'like', "%{$actor}%");
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Date column detection
        |--------------------------------------------------------------------------
        */
        $dateCol = in_array('created_at', $columns, true)
            ? 'created_at'
            : (in_array('occurred_at', $columns, true)
                ? 'occurred_at'
                : (in_array('when', $columns, true) ? 'when' : null));

        if ($dateCol) {
            if ($from !== '') {
                $builder->where($dateCol, '>=', "{$from} 00:00:00");
            }
            if ($to !== '') {
                $builder->where($dateCol, '<=', "{$to} 23:59:59");
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Free text search
        |--------------------------------------------------------------------------
        */
        if ($q !== '') {
            $searchable = array_values(array_intersect($columns, [
                'module',
                'activity',
                'performed_by',
                'performed_by_name',
                'title',
                'log_note',
                'details',
                'description',
                'message',
                'properties',
                'record_table',
                'record_id',
                'target',
                'target_id',
                'target_type',
                'ip',
                'ip_address',
                'user_agent',
            ]));

            if (!empty($searchable)) {
                $builder->where(function ($w) use ($searchable, $q) {
                    foreach ($searchable as $col) {
                        $w->orWhere($col, 'like', "%{$q}%");
                    }
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        */
        $orderCol = $dateCol
            ?: (in_array('id', $columns, true) ? 'id' : ($columns[0] ?? null));

        if ($orderCol) {
            $builder->orderBy($orderCol, $sort);
        }

        $total = (clone $builder)->count();
        $rows  = $builder->forPage($page, $limit)->get();

        /*
        |--------------------------------------------------------------------------
        | Student info
        |--------------------------------------------------------------------------
        */
        $student = null;
        if ($studentId !== '') {
            $student = DB::table('users')
                ->where('id', (int) $studentId)
                ->whereNull('deleted_at')
                ->select(['id', 'name', 'email'])
                ->first();
        }

        /*
        |--------------------------------------------------------------------------
        | Normalize output for frontend
        |--------------------------------------------------------------------------
        */
        $data = $rows->map(function ($row) use ($columns, $dateCol) {
            $properties = null;

            if (in_array('properties', $columns, true) && !empty($row->properties)) {
                try {
                    $properties = json_decode($row->properties, true);
                } catch (\Throwable $e) {
                    $properties = null;
                }
            }

            return [
                'id'                => $row->id ?? null,
                'module'            => $row->module ?? null,
                'activity'          => $row->activity ?? null,
                'performed_by'      => $row->performed_by ?? null,
                'performed_by_name' => $row->performed_by_name ?? null,

                // unified title/note/message
                'title'             => $row->title ?? null,
                'log_note'          => $row->log_note ?? null,
                'description'       => $row->description
                                        ?? $row->message
                                        ?? $row->log_note
                                        ?? $row->title
                                        ?? null,
                'message'           => $row->message ?? null,
                'details'           => $row->details ?? null,
                'properties'        => $properties,

                // unified target
                'target'            => $row->target
                                        ?? $row->target_id
                                        ?? $row->record_id
                                        ?? null,
                'target_id'         => $row->target_id ?? null,
                'target_type'       => $row->target_type ?? null,
                'record_table'      => $row->record_table ?? null,
                'record_id'         => $row->record_id ?? null,

                // unified IP
                'ip'                => $row->ip ?? $row->ip_address ?? null,
                'ip_address'        => $row->ip_address ?? $row->ip ?? null,
                'user_agent'        => $row->user_agent ?? null,

                // unified time
                'created_at'        => $row->created_at
                                        ?? $row->occurred_at
                                        ?? $row->when
                                        ?? null,
                'occurred_at'       => $row->occurred_at ?? null,
                'updated_at'        => $row->updated_at ?? null,
            ];
        })->values();

        return response()->json([
            'ok'         => true,
            'data'       => $data,
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'student_id' => $studentId !== '' ? (int) $studentId : null,
            'student'    => $student ? [
                'id'    => (int) $student->id,
                'name'  => (string) $student->name,
                'email' => (string) $student->email,
            ] : null,
        ]);
    } catch (Throwable $e) {
        Log::error('ActivityLogsController@index failed', [
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'ok'    => false,
            'error' => 'Failed to fetch activity logs.'
        ], 500);
    }
}
    /**
     * POST /api/activity-logs
     * Store an activity row (including deletes).
     * Accepts: module, activity, performed_by, performed_by_name, log_note, details (array|json|string),
     *          record_table, record_id, target, occurred_at (ISO or Y-m-d H:i:s)
     */
    public function store(Request $r)
    {
        try {
            $table   = 'user_data_activity_log';
            $columns = $this->getTableColumns($table);

            // Basic validation (lightweight, to work without a FormRequest)
            $module   = trim((string)$r->input('module', ''));
            $activity = trim((string)$r->input('activity', ''));
            if ($module === '') {
                return response()->json(['ok'=>false, 'error'=>'module is required'], 422);
            }

            // normalize activity to our set; allow custom if your DB expects free text
            $allowed = ['store','update','delete','default','toggled on','toggled off'];
            if ($activity === '') $activity = 'default';
            // keep casing as-is; only guard if you want strict
            if (!in_array($activity, $allowed, true)) {
                // still allow but tag it
                $activity = $activity; // or: return 422 if you want hard validation
            }

            // Coerce details to JSON if array/object
            $details = $r->input('details', null);
            if (is_array($details) || is_object($details)) {
                $details = json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } elseif (is_string($details)) {
                // keep as-is; could be JSON or plain text
            } elseif ($details !== null) {
                $details = (string)$details;
            }

            // Prepare base payload
            $payload = [
                'module'            => $module,
                'activity'          => $activity,
                'performed_by'      => $r->input('performed_by', null),
                'performed_by_name' => $r->input('performed_by_name', null),
                'log_note'          => $r->input('log_note', null),
                'details'           => $details,
                'record_table'      => $r->input('record_table', null),
                'record_id'         => $r->input('record_id', null),
                'target'            => $r->input('target', null),
                'ip'                => $r->ip(),
                'user_agent'        => substr((string)$r->userAgent(), 0, 500),
            ];

            // Occurred at / timestamps
            $now = now();
            $occurred = $r->input('occurred_at');
            if ($occurred) {
                // trust input if looks like datetime; else fallback to now()
                $payload['occurred_at'] = date('Y-m-d H:i:s', strtotime($occurred)) ?: $now->toDateTimeString();
            } elseif (in_array('occurred_at', $columns, true)) {
                $payload['occurred_at'] = $now->toDateTimeString();
            }

            // If table has created_at/updated_at columns, fill them
            if (in_array('created_at', $columns, true)) $payload['created_at'] = $now->toDateTimeString();
            if (in_array('updated_at', $columns, true)) $payload['updated_at'] = $now->toDateTimeString();

            // Insert only known columns
            $payload = Arr::only($payload, $columns);

            DB::table($table)->insert($payload);

            return response()->json(['ok'=>true]);
        } catch (Throwable $e) {
            Log::error('ActivityLogsController@store failed', ['error'=>$e->getMessage()]);
            return response()->json(['ok'=>false, 'error'=>'Failed to store activity log.'], 500);
        }
    }

    /**
     * OPTIONAL helper for dropdowns:
     * GET /api/activity-logs/meta
     * Returns distinct modules, activities, and actors (performed_by + name).
     */
    public function meta()
    {
        try {
            $table = 'user_data_activity_log';

            $modules = DB::table($table)
                ->when(DB::getDriverName()==='pgsql', fn($q)=>$q->selectRaw('DISTINCT module'), fn($q)=>$q->distinct()->select('module'))
                ->whereNotNull('module')
                ->pluck('module')
                ->filter(fn($v)=>$v !== '')
                ->values();

            $activities = DB::table($table)
                ->when(DB::getDriverName()==='pgsql', fn($q)=>$q->selectRaw('DISTINCT activity'), fn($q)=>$q->distinct()->select('activity'))
                ->whereNotNull('activity')
                ->pluck('activity')
                ->filter(fn($v)=>$v !== '')
                ->values();

            $actors = DB::table($table)
                ->select(['performed_by', 'performed_by_name'])
                ->whereNotNull('performed_by')
                ->groupBy('performed_by', 'performed_by_name')
                ->get()
                ->map(fn($r) => [
                    'id'   => $r->performed_by,
                    'name' => $r->performed_by_name ?? $r->performed_by,
                ])
                ->values();

            return response()->json([
                'modules'    => $modules,
                'activities' => $activities,
                'actors'     => $actors,
            ]);
        } catch (Throwable $e) {
            Log::warning('ActivityLogsController@meta failed', ['error'=>$e->getMessage()]);
            return response()->json([
                'modules'=>[], 'activities'=>[], 'actors'=>[]
            ]);
        }
    }

    /** Utility: get columns for a table */
    private function getTableColumns(string $table): array
    {
        try {
            $conn = DB::connection()->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);
            if ($conn === 'mysql') {
                $result = DB::select("SHOW COLUMNS FROM `{$table}`");
                return array_map(fn($r) => $r->Field, $result);
            }
            if ($conn === 'pgsql') {
                $result = DB::select("
                    SELECT column_name
                    FROM information_schema.columns
                    WHERE table_schema = 'public' AND table_name = ?
                    ORDER BY ordinal_position
                ", [$table]);
                return array_map(fn($r) => $r->column_name, $result);
            }
            return DB::getSchemaBuilder()->getColumnListing($table);
        } catch (Throwable $e) {
            try { return DB::getSchemaBuilder()->getColumnListing($table); }
            catch (Throwable) { return []; }
        }
    }

    /* ===========================================================
       Convenience: log a delete from any controller/service
       Usage (inside your resource controller just before/after delete):
         ActivityLogsController::logDelete(
             module: 'documents',
             recordTable: 'documents',
             recordId: $doc->id,
             snapshot: $doc->toArray(),
             performedBy: (string)auth()->id(),
             performedByName: optional(auth()->user())->name,
             note: 'Hard delete' // or 'Soft delete'
         );
       =========================================================== */
    public static function logDelete(
        string $module,
        string $recordTable,
        $recordId,
        ?array $snapshot = null,
        ?string $performedBy = null,
        ?string $performedByName = null,
        ?string $note = null
    ): void {
        try {
            $table   = 'user_data_activity_log';
            $columns = DB::getSchemaBuilder()->getColumnListing($table);

            $payload = [
                'module'            => $module,
                'activity'          => 'delete',
                'performed_by'      => $performedBy,
                'performed_by_name' => $performedByName,
                'log_note'          => $note,
                'details'           => $snapshot ? json_encode(['before'=>$snapshot], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null,
                'record_table'      => $recordTable,
                'record_id'         => $recordId,
                'target'            => $recordId,
                'occurred_at'       => now()->toDateTimeString(),
                'created_at'        => now()->toDateTimeString(),
                'updated_at'        => now()->toDateTimeString(),
                // ip / user_agent omitted here (no Request). If you want those, call POST /api/activity-logs from HTTP context.
            ];

            $payload = Arr::only($payload, $columns);
            DB::table($table)->insert($payload);
        } catch (Throwable $e) {
            Log::warning('ActivityLogsController::logDelete failed', ['error'=>$e->getMessage()]);
        }
    }
}
