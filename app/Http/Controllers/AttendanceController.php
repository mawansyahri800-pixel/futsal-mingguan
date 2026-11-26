<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $selectedDate = $request->date('date')?->toDateString() ?? now()->toDateString();
        $scheduleId = $request->integer('schedule_id');
        $schedules = Schedule::orderByDesc('game_date')->get();
        $members = Member::where('is_active', true)->orderBy('full_name')->get();
        $attendances = $scheduleId
            ? Attendance::where('schedule_id', $scheduleId)->get()->keyBy('member_id')
            : collect();
        $recent = Attendance::with(['member', 'schedule'])->latest('attendance_date')->latest('id')->paginate(15);
        $stats = Attendance::query()
            ->select('member_id')
            ->selectRaw("SUM(status IN ('hadir', 'terlambat')) as present_total")
            ->selectRaw("SUM(status = 'alpa') as absent_total")
            ->selectRaw('COUNT(*) as total')
            ->groupBy('member_id')
            ->with('member')
            ->get();

        return view('attendances.index', compact('selectedDate', 'scheduleId', 'schedules', 'members', 'attendances', 'recent', 'stats'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'attendance_date' => ['required', 'date'],
            'attendances' => ['required', 'array'],
            'attendances.*' => ['required', Rule::in(['hadir', 'izin', 'alpa', 'terlambat'])],
        ]);
        $schedule = Schedule::findOrFail($data['schedule_id']);
        if ($schedule->game_date->toDateString() !== $data['attendance_date']) {
            return back()->withErrors(['attendance_date' => 'Tanggal absensi harus sama dengan tanggal jadwal.'])->withInput();
        }
        $memberIds = Member::where('is_active', true)->whereIn('id', array_keys($data['attendances']))->pluck('id')->all();
        if (count($memberIds) !== count($data['attendances'])) {
            return back()->withErrors(['attendances' => 'Daftar anggota tidak valid.'])->withInput();
        }

        DB::transaction(function () use ($data) {
            foreach ($data['attendances'] as $memberId => $status) {
                Attendance::updateOrCreate(
                    ['schedule_id' => $data['schedule_id'], 'member_id' => $memberId],
                    ['attendance_date' => $data['attendance_date'], 'status' => $status],
                );
            }
        });

        return redirect()->route('attendances.index', ['date' => $data['attendance_date'], 'schedule_id' => $data['schedule_id']])
            ->with('success', 'Absensi berhasil disimpan.');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()->route('attendances.index')->with('success', 'Data kehadiran berhasil dihapus.');
    }
}
