<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlannerItemRequest;
use App\Http\Requests\UpdatePlannerItemRequest;
use App\Models\PlannerItem;
use Illuminate\Http\RedirectResponse;

class PlannerItemController extends Controller
{
    public function store(StorePlannerItemRequest $request): RedirectResponse
    {
        PlannerItem::create($request->validated());

        return back()->with('success', 'تمت إضافة النشاط بنجاح.');
    }

    public function update(UpdatePlannerItemRequest $request, PlannerItem $plannerItem): RedirectResponse
    {
        $plannerItem->update($request->validated());

        return back()->with('success', 'تم تعديل النشاط بنجاح.');
    }

    public function destroy(PlannerItem $plannerItem): RedirectResponse
    {
        $plannerItem->delete();

        return back()->with('success', 'تم حذف النشاط.');
    }
}
