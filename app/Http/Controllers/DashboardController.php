<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\CashTransaction;
use App\Models\Member;
use App\Models\Schedule;
use App\Services\CommunityReport;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __invoke(CommunityReport $report)
    {
        $start = now()->startOfWeek()->toDateString();
        $end = now()->endOfWeek()->toDateString();
        $activeMemberCount = Member::where('is_active', true)->count();

        $attendanceChart = collect(range(3, 0))->map(function (int $weeksAgo) {
            $from = now()->startOfWeek()->subWeeks($weeksAgo);
            $to = $from->copy()->endOfWeek();

            return [
                'label' => $from->translatedFormat('d M'),
                'total' => Attendance::whereIn('status', ['hadir', 'terlambat'])
                    ->whereBetween('attendance_date', [$from->toDateString(), $to->toDateString()])
                    ->distinct('member_id')
                    ->count('member_id'),
            ];
        });

        $cashChart = collect(range(3, 0))->map(function (int $weeksAgo) {
            $from = now()->startOfWeek()->subWeeks($weeksAgo);
            $to = $from->copy()->endOfWeek();
            $transactions = CashTransaction::whereBetween('transaction_date', [$from->toDateString(), $to->toDateString()])
                ->selectRaw("SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income, SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense")
                ->first();

            return [
                'label' => $from->translatedFormat('d M'),
                'income' => (int) $transactions->income,
                'expense' => (int) $transactions->expense,
            ];
        });

        $paidIds = CashTransaction::where('type', 'income')
            ->where('category', 'iuran_mingguan')
            ->whereBetween('transaction_date', [$start, $end])
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->unique();

        return view('dashboard', [
            'activeMemberCount' => $activeMemberCount,
            'weeklyAttendanceCount' => Attendance::whereBetween('attendance_date', [$start, $end])
                ->whereIn('status', ['hadir', 'terlambat'])->count(),
            'balance' => (int) CashTransaction::selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as balance")->value('balance'),
            'monthlySchedules' => Schedule::whereBetween('game_date', [now()->startOfMonth(), now()->endOfMonth()])->count(),
            'attendanceChart' => $attendanceChart,
            'cashChart' => $cashChart,
            'unpaidMembers' => Member::where('is_active', true)->whereNotIn('id', $paidIds)->orderBy('full_name')->limit(5)->get(),
            'recentAttendances' => Attendance::with(['member', 'schedule'])->latest('attendance_date')->latest('id')->limit(5)->get(),
            'report' => $report->data(),
        ]);
    }
}
