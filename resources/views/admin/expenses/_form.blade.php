<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="expense_date" class="block text-sm font-medium text-slate-700">Date</label>
        <input id="expense_date" name="expense_date" type="date" required max="{{ today()->toDateString() }}" value="{{ old('expense_date', $expense->expense_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('expense_date') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="category" class="block text-sm font-medium text-slate-700">Category</label>
        <select id="category" name="category" required class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
            @foreach ($categories as $value => $label)
                <option value="{{ $value }}" @selected(old('category', $expense->category) === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('category') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="md:col-span-2">
        <label for="description" class="block text-sm font-medium text-slate-700">Description</label>
        <input id="description" name="description" required maxlength="255" value="{{ old('description', $expense->description) }}" placeholder="e.g. Meralco bill for common areas" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('description') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label for="amount" class="block text-sm font-medium text-slate-700">Amount (PHP)</label>
        <input id="amount" name="amount" type="number" step="0.01" min="0.01" inputmode="decimal" required value="{{ old('amount', $expense->amount) }}" class="mt-1 w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        @error('amount') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>
</div>

<div class="mt-6 flex gap-2">
    <button class="rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">Save Expense</button>
    <a href="{{ route('admin.expenses.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</a>
</div>
