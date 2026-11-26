<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CashTransaction;
use App\Models\Member;
use App\Models\Schedule;

class CommunityReport
{
    public function data(): array
    {
        $now = now();
        $weekStart = $now->copy()->startOfWeek();
        $weekEnd = $now->copy()->endOfWeek();
        $activeMembers = Member::where('is_active', true)->count();

        $weeklyAttendances = Attendance::with('member')
            ->whereBetween('attendance_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->get();
        $presentCount = $weeklyAttendances
            ->whereIn('status', ['hadir', 'terlambat'])
            ->pluck('member_id')
            ->unique()
            ->count();

        $weeklyIncome = CashTransaction::where('type', 'income')
            ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('amount');
        $weeklyExpense = CashTransaction::where('type', 'expense')
            ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->sum('amount');

        $paidMemberIds = CashTransaction::where('type', 'income')
            ->where('category', 'iuran_mingguan')
            ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->unique();
        $unpaidMembers = Member::where('is_active', true)
            ->whereNotIn('id', $paidMemberIds)
            ->orderBy('full_name')
            ->get();

        $largestExpense = CashTransaction::query()
            ->selectRaw('category, SUM(amount) as total')
            ->where('type', 'expense')
            ->whereBetween('transaction_date', [$weekStart->toDateString(), $weekEnd->toDateString()])
            ->groupBy('category')
            ->orderByDesc('total')
            ->first();

        $weeks = collect(range(3, 0))->map(function (int $weeksAgo) use ($now, $activeMembers) {
            $start = $now->copy()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();
            $present = Attendance::whereIn('status', ['hadir', 'terlambat'])
                ->whereBetween('attendance_date', [$start->toDateString(), $end->toDateString()])
                ->distinct('member_id')
                ->count('member_id');

            return [
                'label' => $start->translatedFormat('d M'),
                'present' => $present,
                'rate' => $activeMembers > 0 ? round($present / $activeMembers * 100) : 0,
            ];
        });

        $weeklyNets = collect(range(1, 4))->map(function (int $weeksAgo) use ($now) {
            $start = $now->copy()->startOfWeek()->subWeeks($weeksAgo);
            $end = $start->copy()->endOfWeek();

            return (int) CashTransaction::whereBetween('transaction_date', [$start->toDateString(), $end->toDateString()])
                ->selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as net")
                ->value('net');
        });

        $balance = (int) CashTransaction::selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as balance")
            ->value('balance');
        $expectedBalance = (int) round($balance + $weeklyNets->avg());
        $rateAverage = (int) round($weeks->avg('rate'));

        $recommendations = $this->attendanceRecommendations();
        $recommendations[] = 'Dengan tren 4 minggu terakhir, saldo kas minggu depan diperkirakan sekitar Rp '.number_format($expectedBalance, 0, ',', '.').'.';

        $summary = 'Minggu ini '.$presentCount.' dari '.$activeMembers.' anggota aktif hadir ('.($activeMembers > 0 ? round($presentCount / $activeMembers * 100) : 0).'%). Kas bertambah Rp '.number_format($weeklyIncome - $weeklyExpense, 0, ',', '.').'. ';
        $summary .= $unpaidMembers->isEmpty()
            ? 'Semua anggota aktif sudah membayar iuran mingguan. '
            : 'Anggota yang belum bayar: '.$unpaidMembers->take(5)->pluck('full_name')->implode(', ').($unpaidMembers->count() > 5 ? ', dan lainnya' : '').'. ';
        $summary .= $largestExpense
            ? 'Pengeluaran terbesar minggu ini adalah '.$this->categoryLabel($largestExpense->category).' Rp '.number_format((int) $largestExpense->total, 0, ',', '.').'.'
            : 'Belum ada pengeluaran minggu ini.';

        return [
            'summary' => $summary,
            'activeMembers' => $activeMembers,
            'presentCount' => $presentCount,
            'attendanceRate' => $activeMembers > 0 ? round($presentCount / $activeMembers * 100) : 0,
            'weeklyNet' => $weeklyIncome - $weeklyExpense,
            'unpaidMembers' => $unpaidMembers,
            'largestExpense' => $largestExpense,
            'weeks' => $weeks,
            'attendancePrediction' => $rateAverage,
            'expectedBalance' => $expectedBalance,
            'recommendations' => $recommendations,
        ];
    }

    public function categoryLabel(string $category): string
    {
        return [
            'iuran_mingguan' => 'Iuran Mingguan',
            'sewa_lapangan' => 'Sewa Lapangan',
            'beli_bola' => 'Beli Bola',
            'minum' => 'Minum',
            'lainnya' => 'Lainnya',
        ][$category] ?? ucfirst(str_replace('_', ' ', $category));
    }

    private function attendanceRecommendations(): array
    {
        $recommendations = [];
        $members = Member::where('is_active', true)->with(['attendances' => function ($query) {
            $query->orderByDesc('attendance_date')->limit(8);
        }])->get();

        foreach ($members as $member) {
            $missedInARow = 0;
            foreach ($member->attendances as $attendance) {
                if ($attendance->status !== 'alpa') {
                    break;
                }
                $missedInARow++;
            }

            if ($missedInARow >= 3) {
                $recommendations[] = 'Sarankan ingatkan '.$member->full_name.' karena sudah '.$missedInARow.' kali berturut-turut tidak hadir.';
            }
        }

        if ($recommendations === []) {
            $recommendations[] = 'Belum ada anggota dengan 3 kali alpa berturut-turut.';
        }

        return $recommendations;
    }
}
