<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Models\ExamSession;
use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Room::query()->orderBy('building')->orderBy('name');

        if ($request->filled('search')) {
            $term = '%'.$request->get('search').'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('building', 'like', $term);
            });
        }

        $rooms = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Rooms/Index', [
            'rooms' => $rooms,
            'filters' => $request->only(['search']),
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

    /**
     * Deactivate room. Per E-024: Block if room has future exam sessions.
     */
    public function destroy(Room $room): RedirectResponse
    {
        $hasFutureSessions = ExamSession::query()
            ->where('room_id', $room->id)
            ->whereDate('date', '>=', now()->toDateString())
            ->exists();

        if ($hasFutureSessions) {
            return redirect()->route('admin.rooms.index')
                ->with('error', 'Cannot deactivate: this room has exam sessions scheduled in the future.');
        }

        $room->update(['is_active' => false]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room deactivated.');
    }

    public function activate(Room $room): RedirectResponse
    {
        $room->update(['is_active' => true]);

        return redirect()->route('admin.rooms.index')->with('success', 'Room activated.');
    }
}
