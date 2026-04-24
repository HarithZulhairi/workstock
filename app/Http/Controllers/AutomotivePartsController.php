<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\AutomotiveParts;
use App\Models\Categories;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AutomotivePartsController extends Controller
{
    public function index(Request $request)
    {
        $query = AutomotiveParts::with(['category', 'variations'])->latest();

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

    public function addStock(Request $request, $id)
    {
        $part = AutomotiveParts::findOrFail($id);

        // Removed the 'min:0' constraints to allow negative numbers
        $request->validate([
            'base_stock_add' => ['nullable', 'integer'],
            'variations' => ['nullable', 'array'],
            'variations.*.variation_id' => ['required', 'exists:part_variations,variation_id'],
            'variations.*.add_quantity' => ['nullable', 'integer'],
        ]);

        // Validate that base stock subtraction doesn't go below 0
        if ($request->filled('base_stock_add')) {
            $newBaseStock = $part->stock_quantity + $request->base_stock_add;
            if ($newBaseStock < 0) {
                return back()->withErrors(['base_stock_add' => 'Stock cannot drop below 0.']);
            }
        }

        // Validate that variation stock subtraction doesn't go below 0
        if ($request->has('variations')) {
            foreach ($request->variations as $varData) {
                if (isset($varData['add_quantity'])) {
                    $variation = $part->variations()->where('variation_id', $varData['variation_id'])->first();
                    if ($variation && ($variation->stock_quantity + $varData['add_quantity'] < 0)) {
                        return back()->withErrors(['variations' => 'Variation stock cannot drop below 0.']);
                    }
                }
            }
        }

        // Update base stock if provided (handles both positive and negative values)
        if ($request->filled('base_stock_add') && $request->base_stock_add != 0) {
            $part->increment('stock_quantity', $request->base_stock_add);
        }

        // Update variation stocks if provided
        if ($request->has('variations')) {
            foreach ($request->variations as $varData) {
                if (isset($varData['add_quantity']) && $varData['add_quantity'] != 0) {
                    $part->variations()
                        ->where('variation_id', $varData['variation_id'])
                        ->increment('stock_quantity', $varData['add_quantity']);
                }
            }
        }

        return redirect()->back()->with('success', 'Stock adjusted successfully!');
    }

    public function show($id)
    {
        $part = AutomotiveParts::with(['category', 'variations'])->findOrFail($id);
        return Inertia::render('Stocks/View', ['part' => $part]);
    }

    public function store(Request $request)
    {
        // 1. Validate both the main part AND the variations array
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'part_serial_number' => ['required', 'string', 'max:255', 'unique:automotive_parts,part_serial_number'],
            'category_id' => ['required', 'exists:categories,category_id'], 
            'price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'part_description' => ['nullable', 'string'],
            'is_visible_to_public' => ['nullable', 'boolean'],
            'brand' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'integer', 'min:1', 'max:10'],
            'part_images' => ['nullable', 'array', 'max:3'], 
            'part_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], 
            
            // --- NEW: Validation for Variations ---
            'variations' => ['nullable', 'array'],
            'variations.*.name' => ['required_with:variations', 'string', 'max:255'],
            'variations.*.price' => ['required_with:variations', 'numeric', 'min:0'],
            'variations.*.stock_quantity' => ['required_with:variations', 'integer', 'min:0'],
            'variations.*.picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        // Ensure is_visible_to_public is properly set as boolean
        // When checkbox is unchecked, the value might be false, null, or not present
        $validated['is_visible_to_public'] = filter_var($request->input('is_visible_to_public', false), FILTER_VALIDATE_BOOLEAN);

        // Handle Main Part Multiple Image Uploads
        if ($request->hasFile('part_images')) {
            $imagePaths = []; 
            foreach ($request->file('part_images') as $image) {
                $path = $image->store('parts', 'public');
                $imagePaths[] = $path;
            }
            $validated['part_images'] = $imagePaths;
        }

        // Separate the base part data and Create the Main Record
        // We use collect()->except() so we don't accidentally try to save the raw variations array into the main table
        $basePartData = collect($validated)->except('variations')->toArray();
        
        $part = AutomotiveParts::create($basePartData);

        // Handle Variations (If the user added any)
        if ($request->has('variations') && is_array($request->variations)) {
            
            foreach ($request->variations as $index => $variationData) {
                $picturePath = null;

                // Check if this specific variation has a picture uploaded
                // Because it's an array of objects, we target it using the index
                if ($request->hasFile("variations.{$index}.picture")) {
                    $picture = $request->file("variations.{$index}.picture");
                    $picturePath = $picture->store('variations', 'public'); // Save to 'storage/app/public/variations'
                }

                // Create the variation using the relationship (this automatically links the automotive_parts_id!)
                $part->variations()->create([
                    'name' => $variationData['name'],
                    'price' => $variationData['price'],
                    'stock_quantity' => $variationData['stock_quantity'],
                    'picture' => $picturePath,
                ]);
            }
        }

        return redirect()->route('displayStock')->with('success', 'Part added successfully!');
    }

    public function destroy($id)
    {
        $part = AutomotiveParts::with('variations')->findOrFail($id);

        if (!empty($part->part_images)) {
            foreach ($part->part_images as $imagePath) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        if ($part->variations) {
            foreach ($part->variations as $variation) {
                if ($variation->picture) {
                    Storage::disk('public')->delete($variation->picture);
                }
            }
        }

        $part->delete();

        return redirect()->back()->with('success', 'Part deleted successfully!');
    }

    public function edit($id)
    {
        $part = AutomotiveParts::with('variations')->findOrFail($id);
        $categories = Categories::all();

        return Inertia::render('Stocks/Edit', [
            'part' => $part,
            'categories' => $categories
        ]);
    }

    public function update(Request $request, $id)
    {
        $part = AutomotiveParts::with('variations')->findOrFail($id);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // Ignore the current part's ID for the unique check
            'part_serial_number' => ['required', 'string', 'max:255', 'unique:automotive_parts,part_serial_number,' . $id . ',automotive_parts_id'],
            'category_id' => ['required', 'exists:categories,category_id'], 
            'price' => ['required', 'numeric', 'min:0'],
            // Notice: stock_quantity is removed from validation here!
            'part_description' => ['nullable', 'string'],
            'is_visible_to_public' => ['nullable', 'boolean'],
            'brand' => ['nullable', 'string', 'max:255'],
            'warranty' => ['nullable', 'string', 'max:255'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'condition' => ['nullable', 'integer', 'min:1', 'max:10'],
            'part_images' => ['nullable', 'array', 'max:3'], 
            'part_images.*' => ['image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], 
            
            'variations' => ['nullable', 'array'],
            'variations.*.variation_id' => ['nullable', 'exists:part_variations,variation_id'], // Need this to know if we are updating or creating
            'variations.*.name' => ['required_with:variations', 'string', 'max:255'],
            'variations.*.price' => ['required_with:variations', 'numeric', 'min:0'],
            'variations.*.picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
        ]);

        // Ensure is_visible_to_public is properly set as boolean
        // When checkbox is unchecked, the value might be false, null, or not present
        $validated['is_visible_to_public'] = filter_var($request->input('is_visible_to_public', false), FILTER_VALIDATE_BOOLEAN);

        // Handle Main Part Images (Only update if new files were uploaded)
        if ($request->hasFile('part_images')) {
            // Optional: Delete old images from Storage here if you want
            $imagePaths = []; 
            foreach ($request->file('part_images') as $image) {
                $imagePaths[] = $image->store('parts', 'public');
            }
            $validated['part_images'] = $imagePaths;
        } else {
            // If no new images uploaded, prevent it from overwriting the existing ones with null
            unset($validated['part_images']);
        }

        $basePartData = collect($validated)->except('variations')->toArray();
        $part->update($basePartData);

        // Handle Variations
        if ($request->has('variations')) {
            $existingVarIds = [];

            foreach ($request->variations as $index => $varData) {
                $picturePath = null;

                // Handle variation image upload
                if ($request->hasFile("variations.{$index}.picture")) {
                    $picturePath = $request->file("variations.{$index}.picture")->store('variations', 'public');
                }

                if (isset($varData['variation_id'])) {
                    // UPDATE EXISTING VARIATION
                    $variation = $part->variations()->find($varData['variation_id']);
                    $variation->name = $varData['name'];
                    $variation->price = $varData['price'];
                    if ($picturePath) {
                        $variation->picture = $picturePath; // Only overwrite if a new picture was uploaded
                    }
                    $variation->save();
                    $existingVarIds[] = $variation->variation_id;
                } else {
                    // CREATE NEW VARIATION (Defaults stock to 0)
                    $newVar = $part->variations()->create([
                        'name' => $varData['name'],
                        'price' => $varData['price'],
                        'stock_quantity' => 0, // Fresh variations start at 0 stock
                        'picture' => $picturePath,
                    ]);
                    $existingVarIds[] = $newVar->variation_id;
                }
            }

            // Delete any variations that the user removed from the form
            $part->variations()->whereNotIn('variation_id', $existingVarIds)->delete();
        } else {
            // If they deleted all variations, wipe them out
            $part->variations()->delete();
        }

        // Redirect back with success message
        return redirect()->route('displayStock')->with('success', 'Part updated successfully!');
    }
}
