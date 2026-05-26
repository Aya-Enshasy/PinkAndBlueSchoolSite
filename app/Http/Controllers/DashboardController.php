<?php

namespace App\Http\Controllers;

use App\Models\PlannerItem;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $selectedDate = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : now();

        $monthStart = $selectedDate->copy()->startOfMonth();
        $monthEnd = $selectedDate->copy()->endOfMonth();

        $items = PlannerItem::query()
            ->whereBetween('scheduled_for', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->orderBy('scheduled_for')
            ->get();

        $itemsByDate = $items->groupBy(fn ($item) => $item->scheduled_for->format('Y-m-d'));

        $selectedItems = PlannerItem::query()
            ->whereDate('scheduled_for', $selectedDate->toDateString())
            ->orderBy('created_at')
            ->get();

        return view('dashboard', [
            'totalStudents' => Student::count(),
            'recentStudents' => Student::latest()->take(5)->get(),
            'totalFiles' => Student::query()
                ->selectRaw('SUM(
                    (CASE WHEN student_id_image IS NULL OR student_id_image = \'\' THEN 0 ELSE 1 END) +
                    (CASE WHEN father_id_image IS NULL OR father_id_image = \'\' THEN 0 ELSE 1 END) +
                    (CASE WHEN birth_certificate_image IS NULL OR birth_certificate_image = \'\' THEN 0 ELSE 1 END)
                ) as total')
                ->value('total') ?? 0,
            'selectedDate' => $selectedDate,
            'monthStart' => $monthStart,
            'monthEnd' => $monthEnd,
            'itemsByDate' => $itemsByDate,
            'selectedItems' => $selectedItems,
        ]);
    }
}
