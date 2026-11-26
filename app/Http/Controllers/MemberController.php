<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MemberController extends Controller
{
    public function index(Request $request)
    {
        $members = Member::query()
            ->when($request->filled('search'), fn ($query) => $query->where('full_name', 'like', '%'.$request->string('search').'%'))
            ->orderBy('full_name')
            ->paginate(12)
            ->withQueryString();

        return view('members.index', compact('members'));
    }

    public function create()
    {
        return view('members.form', ['member' => new Member()]);
    }

    public function store(Request $request)
    {
        Member::create($this->validated($request));

        return redirect()->route('members.index')->with('success', 'Anggota berhasil ditambahkan.');
    }

    public function edit(Member $member)
    {
        return view('members.form', compact('member'));
    }

    public function update(Request $request, Member $member)
    {
        $data = $this->validated($request);
        if (isset($data['photo_path']) && $member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        $member->update($data);

        return redirect()->route('members.index')->with('success', 'Data anggota berhasil diperbarui.');
    }

    public function destroy(Member $member)
    {
        if ($member->photo_path) {
            Storage::disk('public')->delete($member->photo_path);
        }
        $member->delete();

        return redirect()->route('members.index')->with('success', 'Anggota berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'position' => ['required', Rule::in(['kiper', 'bek', 'gelandang', 'penyerang'])],
            'is_active' => ['required', 'boolean'],
            'joined_at' => ['required', 'date'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);
        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('members', 'public');
        }
        unset($data['photo']);

        return $data;
    }
}
