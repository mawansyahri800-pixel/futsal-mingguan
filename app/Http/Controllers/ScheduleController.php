<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::withCount('attendances')->orderByDesc('game_date')->paginate(12);

        return view('schedules.index', compact('schedules'));
    }

    public function create()
    {
        return view('schedules.form', ['schedule' => new Schedule()]);
    }

    public function store(Request $request)
    {
        Schedule::create($this->validated($request));

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil ditambahkan.');
    }

    public function edit(Schedule $schedule)
    {
        return view('schedules.form', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule)
    {
        $schedule->update($this->validated($request));

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();

        return redirect()->route('schedules.index')->with('success', 'Jadwal berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'game_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:150'],
            'rental_cost' => ['required', 'integer', 'min:0'],
            'status' => ['required', Rule::in(['upcoming', 'completed', 'cancelled'])],
        ]);
        $data['day'] = \Illuminate\Support\Carbon::parse($data['game_date'])->locale('id')->translatedFormat('l');

        return $data;
    }
}
