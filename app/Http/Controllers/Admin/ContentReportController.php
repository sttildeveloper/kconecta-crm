<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentReport;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContentReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->ensureAdmin($request->user());
        $status = (string) $request->query('status', 'pending');
        $reports = ContentReport::query()->with(['reporter', 'reportedUser', 'moderator'])
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()->paginate(30)->withQueryString();

        return view('admin.content_reports.index', compact('reports', 'status') + ['activeNav' => 'content-reports']);
    }

    public function update(Request $request, ContentReport $report): RedirectResponse
    {
        $this->ensureAdmin($request->user());
        $validated = $request->validate([
            'status' => ['required', Rule::in((array) config('compliance.content_safety.report_statuses', []))],
            'resolution_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $report->update($validated + [
            'moderator_user_id' => (int) $request->user()->id,
            'reviewed_at' => now(),
            'active_fingerprint' => in_array($validated['status'], ['resolved', 'rejected'], true) ? null : $report->active_fingerprint,
        ]);

        return back()->with('status', 'Denuncia actualizada.');
    }

    private function ensureAdmin(?User $user): void
    {
        abort_unless($user?->isAdmin(), 403);
    }
}
