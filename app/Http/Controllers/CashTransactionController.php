<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CashTransactionController extends Controller
{
    public function index(Request $request)
    {
        $filter = $request->string('period')->value();
        $query = CashTransaction::with(['member', 'user'])->orderByDesc('transaction_date')->orderByDesc('id');
        if ($filter === 'week') {
            $query->whereBetween('transaction_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($filter === 'month') {
            $query->whereBetween('transaction_date', [now()->startOfMonth(), now()->endOfMonth()]);
        }
        if ($request->filled('member_id')) {
            $query->where('member_id', $request->integer('member_id'));
        }

        return view('cash.index', [
            'transactions' => $query->paginate(15)->withQueryString(),
            'members' => Member::orderBy('full_name')->get(),
            'balance' => $this->balance(),
            'income' => (int) CashTransaction::where('type', 'income')->sum('amount'),
            'expense' => (int) CashTransaction::where('type', 'expense')->sum('amount'),
            'filter' => $filter,
            'memberId' => $request->integer('member_id'),
        ]);
    }

    public function create()
    {
        return view('cash.form', ['transaction' => new CashTransaction(), 'members' => Member::orderBy('full_name')->get()]);
    }

    public function store(Request $request)
    {
        CashTransaction::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('cash.index')->with('success', 'Transaksi kas berhasil ditambahkan.');
    }

    public function edit(CashTransaction $cash)
    {
        return view('cash.form', ['transaction' => $cash, 'members' => Member::orderBy('full_name')->get()]);
    }

    public function update(Request $request, CashTransaction $cash)
    {
        $cash->update($this->validated($request) + ['user_id' => $cash->user_id ?? $request->user()->id]);

        return redirect()->route('cash.index')->with('success', 'Transaksi kas berhasil diperbarui.');
    }

    public function destroy(CashTransaction $cash)
    {
        $cash->delete();

        return redirect()->route('cash.index')->with('success', 'Transaksi kas berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'transaction_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in(['iuran_mingguan', 'sewa_lapangan', 'beli_bola', 'minum', 'lainnya'])],
            'amount' => ['required', 'integer', 'min:1', 'max:1000000000'],
            'description' => ['nullable', 'string', 'max:1000'],
            'member_id' => ['nullable', 'required_if:category,iuran_mingguan', 'exists:members,id'],
        ]);
    }

    private function balance(): int
    {
        return (int) CashTransaction::selectRaw("COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE -amount END), 0) as balance")->value('balance');
    }
}
