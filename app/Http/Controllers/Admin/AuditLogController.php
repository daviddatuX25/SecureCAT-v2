<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('actor')->orderBy('created_at', 'desc');

        if ($request->filled('event')) {
            $query->where('event', $request->get('event'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $logs = $query->paginate(25)->withQueryString();

        $events = AuditLog::query()->select('event')->distinct()->orderBy('event')->pluck('event')->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->toArray();
        $categories = AuditLog::query()->select('category')->distinct()->whereNotNull('category')->orderBy('category')->pluck('category')->map(fn ($v) => ['value' => $v, 'label' => $v])->values()->toArray();

        return Inertia::render('Admin/Logs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'category', 'actor_id', 'date_from', 'date_to']),
            'events' => $events,
            'categories' => $categories,
            'scopeLabel' => 'Activity log',
            'showActorFilter' => true,
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', AuditLog::class);

        $query = AuditLog::query()->with('actor')->orderBy('created_at', 'desc');

        if ($request->filled('event')) {
            $query->where('event', $request->get('event'));
        }
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->integer('actor_id'));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="audit-log-'.now()->format('Y-m-d').'.csv"',
        ];

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['id', 'created_at', 'event', 'category', 'actor_id', 'actor_type', 'summary']);

            $query->cursor()->each(function (AuditLog $log) use ($handle) {
                $actorName = $log->actor?->name ?? $log->actor?->email ?? null;
                fputcsv($handle, [
                    $log->id,
                    $log->created_at?->toIso8601String(),
                    $log->event,
                    $log->category,
                    $log->actor_id,
                    $log->actor_type,
                    $log->summary,
                ]);
            });
            fclose($handle);
        }, 'audit-log-'.now()->format('Y-m-d').'.csv', $headers);
    }
}
