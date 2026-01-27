<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::forUser(Auth::id())
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return Inertia::render('Categories/Index', [
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense',
            'color' => 'required|string|max:50',
        ]);

        Category::create([
            'name' => $request->name,
            'type' => $request->type,
            'color' => $request->color,
            'user_id' => Auth::id(),
            'is_active' => true
        ]);

        return redirect()->back();
    }

    public function update(Request $request, Category $category)
    {
        // Ensure user owns this category or it's a system category?
        // Proposal says: Only allow updates for categories owned by user.

        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
        ]);

        $category->update($request->only('name', 'color'));

        return redirect()->back();
    }

    public function destroy(Category $category)
    {
        if ($category->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        if ($category->is_system) {
            return redirect()->back()->withErrors(['error' => 'System categories cannot be deleted.']);
        }

        // Soft delete or Hard delete?
        // Model has 'is_active'. Let's soft delete (set is_active = false)
        // Or if we want to delete permanently if no transactions, we'd need a check.
        // For now, let's implement soft delete (hide) via is_active = false.

        $category->update(['is_active' => false]);

        return redirect()->back();
    }
}
