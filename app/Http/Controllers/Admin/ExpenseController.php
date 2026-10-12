<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Support\Search;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', now()->format('Y-m'));
        $start = Carbon::createFromFormat('Y-m', preg_match('/^\d{4}-\d{2}$/', $month) ? $month : now()->format('Y-m'))->startOfMonth();
        $end = (clone $start)->endOfMonth();

        $expenses = Expense::whereBetween('expense_date', [$start, $end])
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->category))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['description']))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $totalExpenses = (float) Expense::whereBetween('expense_date', [$start, $end])->sum('amount');
        $income = (float) Payment::whereBetween('payment_date', [$start, $end])->sum('amount');

        return view('admin.expenses.index', [
            'expenses' => $expenses,
            'month' => $start,
            'totalExpenses' => $totalExpenses,
            'income' => $income,
            'categories' => Expense::CATEGORIES,
            'byCategory' => Expense::whereBetween('expense_date', [$start, $end])
                ->selectRaw('category, SUM(amount) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->pluck('total', 'category'),
        ]);
    }

    public function create()
    {
        return view('admin.expenses.create', [
            'expense' => new Expense(['expense_date' => today()]),
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        Expense::create($this->validated($request));

        return redirect()->route('admin.expenses.index')->with('success', 'Expense recorded.');
    }

    public function edit(Expense $expense)
    {
        return view('admin.expenses.edit', [
            'expense' => $expense,
            'categories' => Expense::CATEGORIES,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));

        return redirect()->route('admin.expenses.index', ['month' => $expense->expense_date->format('Y-m')])->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $month = $expense->expense_date->format('Y-m');
        $expense->delete();

        return redirect()->route('admin.expenses.index', ['month' => $month])->with('success', 'Expense deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'category' => ['required', Rule::in(array_keys(Expense::CATEGORIES))],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:10000000'],
        ]);
    }
}
