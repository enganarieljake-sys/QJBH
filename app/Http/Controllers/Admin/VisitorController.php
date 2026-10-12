<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Support\Search;
use Illuminate\Http\Request;

class VisitorController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date('date') ?? today();
        // A search looks through every day, so a past visitor can be found by name.
        $searching = filled($request->input('q'));

        $visitors = Visitor::with('tenant.room')
            ->unless($searching, fn ($query) => $query->whereDate('visit_date', $date))
            ->tap(fn ($query) => Search::apply($query, $request->input('q'), ['visitor_name', 'purpose', 'tenant.full_name']))
            ->when($searching, fn ($query) => $query->latest('visit_date'))
            ->when($request->boolean('inside'), fn ($query) => $query->whereNull('time_out'))
            ->orderBy('time_in')
            ->paginate(20)
            ->withQueryString();

        return view('admin.visitors.index', [
            'visitors' => $visitors,
            'date' => $date,
            'insideNow' => Visitor::whereDate('visit_date', today())->whereNull('time_out')->count(),
        ]);
    }

    public function checkout(Visitor $visitor)
    {
        if ($visitor->isInside()) {
            $visitor->update(['time_out' => now()->format('H:i')]);
        }

        return back()->with('success', $visitor->visitor_name.' marked as left.');
    }
}
