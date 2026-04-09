<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAptitudeAreaRequest;
use App\Http\Requests\UpdateAptitudeAreaRequest;
use App\Models\AptitudeArea;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AptitudeAreaController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', AptitudeArea::class);

        $aptitudeAreas = AptitudeArea::query()
            ->orderBy('display_order')
            ->orderBy('id')
            ->get()
            ->map(fn (AptitudeArea $a) => [
                'id'            => $a->id,
                'name'          => $a->name,
                'code'          => $a->code,
                'description'   => $a->description,
                'max_items'     => $a->max_items,
                'display_order' => $a->display_order,
                'is_active'     => $a->is_active,
            ]);

        return Inertia::render('Admin/AptitudeAreas/Index', [
            'aptitude_areas' => $aptitudeAreas,
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', AptitudeArea::class);

        return Inertia::render('Admin/AptitudeAreas/Create');
    }

    public function store(StoreAptitudeAreaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        AptitudeArea::create([
            'name'          => $data['name'],
            'code'          => $data['code'],
            'description'   => $data['description'] ?? null,
            'max_items'     => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.aptitude-areas.index')
            ->with('success', 'Aptitude area created.');
    }

    public function edit(AptitudeArea $aptitudeArea): Response
    {
        $this->authorize('update', $aptitudeArea);

        return Inertia::render('Admin/AptitudeAreas/Edit', [
            'aptitude_area' => [
                'id'            => $aptitudeArea->id,
                'name'          => $aptitudeArea->name,
                'code'          => $aptitudeArea->code,
                'description'   => $aptitudeArea->description ?? '',
                'max_items'     => $aptitudeArea->max_items,
                'display_order' => $aptitudeArea->display_order,
                'is_active'     => $aptitudeArea->is_active,
            ],
        ]);
    }

    public function update(UpdateAptitudeAreaRequest $request, AptitudeArea $aptitudeArea): RedirectResponse
    {
        $data = $request->validated();

        $aptitudeArea->update([
            'name'          => $data['name'],
            'code'          => $data['code'],
            'description'   => $data['description'] ?? null,
            'max_items'     => (int) $data['max_items'],
            'display_order' => isset($data['display_order']) ? (int) $data['display_order'] : 0,
            'is_active'     => $data['is_active'] ?? true,
        ]);

        return redirect()->route('admin.aptitude-areas.index')
            ->with('success', 'Aptitude area updated.');
    }
}
