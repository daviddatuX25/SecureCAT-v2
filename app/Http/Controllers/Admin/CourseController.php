<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Course;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(): Response
    {
        $courses = Course::query()
            ->with('department:id,name,code')
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Courses/Index', [
            'courses' => $courses,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Courses/Create', [
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Course::create([
            'department_id' => $validated['department_id'],
            'name' => $validated['name'],
            'code' => $validated['code'],
            'quota' => $validated['quota'] ?? null,
            'score_cutoff' => $validated['score_cutoff'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('admin.courses.index')->with('success', 'Course created.');
    }

    public function edit(Course $course): Response
    {
        return Inertia::render('Admin/Courses/Edit', [
            'course' => $course->load('department:id,name,code'),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name', 'code']),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('admin.courses.index')->with('success', 'Course updated.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->update(['is_active' => false]);

        return redirect()->route('admin.courses.index')->with('success', 'Course deactivated.');
    }

    public function activate(Course $course): RedirectResponse
    {
        $course->update(['is_active' => true]);

        return redirect()->route('admin.courses.index')->with('success', 'Course activated.');
    }
}

