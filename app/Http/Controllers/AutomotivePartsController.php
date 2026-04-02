<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AutomotiveParts;
use App\Models\Categories;
use App\Http\Controllers\Controller;
use Inertia\Inertia;


class AutomotivePartsController extends Controller
{
    public function index(Request $request)
    {
        $query = AutomotiveParts::with('category')->latest();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id') && $request->category_id !== 'all') {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->filled('min_stock')) {
            $query->where('stock_quantity', '>=', $request->min_stock);
        }
        if ($request->filled('max_stock')) {
            $query->where('stock_quantity', '<=', $request->max_stock);
        }

        if ($request->filled('visibility') && $request->visibility !== 'all') {
            $query->where('is_visible_to_public', $request->visibility === 'public' ? 1 : 0);
        }

        $parts = $query->paginate(10)->withQueryString();

        $categories = Categories::all();

        return Inertia::render('Stocks/Index', [
            'parts' => $parts,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'min_price', 'max_price', 'min_stock', 'max_stock', 'visibility'])
        ]);
    }

    public function show($id)
    {
        $part = AutomotiveParts::with('category')->findOrFail($id);
        return Inertia::render('Stocks/View', ['part' => $part]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'part_serial_number' => ['required', 'string', 'max:255', 'unique:automotive_parts,part_serial_number'],
            'category_id' => ['required', 'exists:categories,category_id'], 
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'part_description' => ['nullable', 'string'],
            'is_visible_to_public' => ['boolean'],
            'brand' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'integer', 'min:1', 'max:10'],
            'part_images' => ['nullable', 'array', 'max:3'], 
            'part_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], 
        ]);

        // Handle Multiple Image Uploads
        if ($request->hasFile('part_images')) {
            $imagePaths = []; 
            
            foreach ($request->file('part_images') as $image) {
                $path = $image->store('parts', 'public');
                $imagePaths[] = $path;
            }
        
            $validated['part_images'] = $imagePaths;
        }

        // Create the record in the database
        AutomotiveParts::create($validated);

        return redirect()->route('displayStock');
    }
}
