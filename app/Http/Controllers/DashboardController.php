<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\BirthAct;
use App\Models\LeaveRequest;
use App\Models\Receipt;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $data = ['user' => $user];

        if ($user->hasPermission('revenue.view')) {
            $paid = Receipt::where('status', 'paid');

            $data['revenue'] = [
                'today' => (clone $paid)->whereDate('collected_at', today())->sum('amount'),
                'today_count' => (clone $paid)->whereDate('collected_at', today())->count(),
                'month' => (clone $paid)->whereBetween('collected_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount'),
                'cancelled' => Receipt::where('status', 'cancelled')->whereBetween('collected_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'pending_offline' => Receipt::where('created_offline', true)->whereDate('created_at', today())->count(),
            ];

            // Série des 14 derniers jours pour le graphique.
            $from = today()->subDays(13);
            $byDay = (clone $paid)->where('collected_at', '>=', $from)
                ->selectRaw('DATE(collected_at) as d, SUM(amount) as total')
                ->groupBy('d')->pluck('total', 'd');

            $data['series'] = collect(range(0, 13))->map(function ($i) use ($from, $byDay) {
                $day = $from->copy()->addDays($i);

                return ['label' => $day->format('d/m'), 'value' => (int) ($byDay[$day->toDateString()] ?? 0)];
            });

            $data['by_category'] = Receipt::where('status', 'paid')
                ->whereBetween('collected_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->join('revenue_categories', 'revenue_categories.id', '=', 'receipts.category_id')
                ->selectRaw('revenue_categories.name as name, SUM(receipts.amount) as total')
                ->groupBy('revenue_categories.name')->orderByDesc('total')->limit(5)->get();
        }

        if ($user->hasPermission('civil.birth.view')) {
            $data['civil'] = [
                'month' => BirthAct::whereBetween('declaration_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'drafts' => BirthAct::where('status', 'draft')->count(),
            ];
        }

        if ($user->hasPermission('hr.view')) {
            $data['hr'] = [
                'agents' => Agent::where('is_active', true)->count(),
            ];
        }

        if ($user->hasPermission('hr.leave.decide')) {
            $data['pending_leaves'] = LeaveRequest::with('agent')->where('status', 'pending')->latest()->limit(5)->get();
        }

        if ($user->hasPermission('audit.view')) {
            $data['activity'] = AuditLog::with('user')->latest('id')->limit(8)->get();
        }

        return view('dashboard.index', $data);
    }
}
