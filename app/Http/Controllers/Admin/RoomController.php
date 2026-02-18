<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $rooms = Room::query()
            ->orderBy('building')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Rooms/Create');
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Room::create([
            'name' => $validated['name'],
            'building' => $validated['building'],
            'floor' => $validated['floor'] ?? null,
            'capacity' => $validated['capacity'],
            'facilities' => $validated['facilities'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room created.');
    }

    public function edit(Room $room): Response
    {
        return Inertia::render('Admin/Rooms/Edit', [
            'room' => $room,
        ]);
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        $room->update($request->validated());

        return redirect()->route('admin.rooms.index')->with('success', 'Room updated.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        // Per API spec: Deactivate room. Future: cannot deactivate if assigned to future exam_sessions
        $room->update(['is_active' => false]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room deactivated.');
    }
}
