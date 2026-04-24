<?php

namespace App\Http\Controllers;

use App\Models\JobOrders;
use App\Models\AutomotiveParts;
use App\Models\PartVariation;
use App\Models\JobOrdersParts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class JobOrdersController extends Controller
{
    public function index(Request $request)
    {
        // Start query and include the relationships
        $query = JobOrders::with([
            'handler',
            'jobOrderParts.automotivePart',
            'jobOrderParts.variation'
        ])->latest('job_orders_id');

        // Handle Search (checks name, phone, or plate)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('vehicle_plate', 'like', "%{$search}%")
                  ->orWhere('customer_phone_num', 'like', "%{$search}%");
            });
        }

        // Handle Status Filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Paginate results (10 per page) and keep query strings for pagination links
        $jobOrders = $query->paginate(10)->withQueryString();

        return Inertia::render('JobOrders/Index', [
            'jobOrders' => $jobOrders,
            'filters' => $request->only(['search', 'status'])
        ]);
    }

    public function create()
    {
        // Get parts that are in stock (including their variations)
        $parts = AutomotiveParts::with(['variations' => function($query) {
                        $query->where('stock_quantity', '>', 0);
                    }])
                    ->select('automotive_parts_id', 'name', 'price', 'stock_quantity', 'part_serial_number')
                    ->where(function($query) {
                        // Include parts that either have base stock OR have variations with stock
                        $query->where('stock_quantity', '>', 0)
                              ->orWhereHas('variations', function($q) {
                                  $q->where('stock_quantity', '>', 0);
                              });
                    })
                    ->where('is_visible_to_public', 1) 
                    ->get();

        return Inertia::render('JobOrders/Create', [
            'parts' => $parts,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone_num' => 'required|string|max:20',
            'vehicle_plate' => 'required|string|max:20',
            'vehicle_brand' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5060',
            'reported_issue' => 'required|string',
            'status' => 'required|string',
            'total_cost' => 'required|numeric|min:0',
            'parts_used' => 'nullable|array',
            'parts_used.*.automotive_parts_id' => 'required|exists:automotive_parts,automotive_parts_id',
            'parts_used.*.variation_id' => 'nullable|exists:part_variations,variation_id',
            'parts_used.*.quantity' => 'required|integer|min:1',
            'parts_used.*.unit_price' => 'required|numeric|min:0',
            'parts_used.*.subtotal' => 'required|numeric|min:0',
        ]);

        // Handle vehicle picture upload
        $vehiclePicturePath = null;
        if ($request->hasFile('vehicle_picture')) {
            $vehiclePicturePath = $request->file('vehicle_picture')->store('vehicle_pictures', 'public');
        }

        // Create the main Job Order
        $jobOrder = JobOrders::create([
            'customer_name' => strtoupper($validated['customer_name']), 
            'customer_phone_num' => $validated['customer_phone_num'],
            'vehicle_plate' => strtoupper($validated['vehicle_plate']),
            'vehicle_brand' => strtoupper($validated['vehicle_brand']),
            'vehicle_model' => strtoupper($validated['vehicle_model']),
            'vehicle_picture' => $vehiclePicturePath,
            'reported_issue' => $validated['reported_issue'],
            'status' => $validated['status'],
            'handled_by' => Auth::id(), 
            'total_cost' => $validated['total_cost'],
        ]);

        // Attach parts to the Pivot Table and deduct stock
        if (!empty($validated['parts_used'])) {
            foreach ($validated['parts_used'] as $partData) {
                // Create the job order part entry
                JobOrdersParts::create([
                    'job_orders_id' => $jobOrder->job_orders_id,
                    'automotive_parts_id' => $partData['automotive_parts_id'],
                    'variation_id' => $partData['variation_id'] ?? null,
                    'quantity_used' => $partData['quantity'],
                    'unit_price' => $partData['unit_price'],
                    'subtotal' => $partData['subtotal'],
                ]);

                // Deduct stock from the appropriate location
                if (!empty($partData['variation_id'])) {
                    // Deduct from variation stock
                    $variation = PartVariation::find($partData['variation_id']);
                    if ($variation && $variation->stock_quantity >= $partData['quantity']) {
                        $variation->decrement('stock_quantity', $partData['quantity']);
                    } else {
                        return back()->withErrors(['error' => 'Insufficient stock for variation: ' . $variation->name]);
                    }
                } else {
                    // Deduct from base part stock
                    $part = AutomotiveParts::find($partData['automotive_parts_id']);
                    if ($part && $part->stock_quantity >= $partData['quantity']) {
                        $part->decrement('stock_quantity', $partData['quantity']);
                    } else {
                        return back()->withErrors(['error' => 'Insufficient stock for part: ' . $part->name]);
                    }
                }
            }
        }

        return redirect()->route('displayJobOrders')->with('success', 'Job Order created successfully!');
    }

    public function edit($id)
    {
        // Get the job order with all relationships
        $jobOrder = JobOrders::with([
            'handler',
            'jobOrderParts.automotivePart.variations',
            'jobOrderParts.variation'
        ])->findOrFail($id);

        // Get all available parts (including their variations)
        $parts = AutomotiveParts::with(['variations'])
                    ->select('automotive_parts_id', 'name', 'price', 'stock_quantity', 'part_serial_number')
                    ->where('is_visible_to_public', 1) 
                    ->get();

        // Transform job order parts for the form
        $partsUsed = $jobOrder->jobOrderParts->map(function ($jobOrderPart) {
            return [
                'job_order_parts_id' => $jobOrderPart->job_order_parts_id,
                'automotive_parts_id' => $jobOrderPart->automotive_parts_id,
                'variation_id' => $jobOrderPart->variation_id,
                'quantity' => $jobOrderPart->quantity_used,
                'unit_price' => $jobOrderPart->unit_price,
                'subtotal' => $jobOrderPart->subtotal,
                'original_quantity' => $jobOrderPart->quantity_used, // Track original quantity for stock adjustment
            ];
        });

        return Inertia::render('JobOrders/Edit', [
            'jobOrder' => $jobOrder,
            'parts' => $parts,
            'partsUsed' => $partsUsed,
        ]);
    }

    public function update(Request $request, $id)
    {
        $jobOrder = JobOrders::findOrFail($id);

        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone_num' => 'required|string|max:20',
            'vehicle_plate' => 'required|string|max:20',
            'vehicle_brand' => 'required|string|max:100',
            'vehicle_model' => 'required|string|max:100',
            'vehicle_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5060',
            'reported_issue' => 'required|string',
            'status' => 'required|string',
            'total_cost' => 'required|numeric|min:0',
            'parts_used' => 'nullable|array',
            'parts_used.*.job_order_parts_id' => 'nullable|exists:job_orders_parts,job_order_parts_id',
            'parts_used.*.automotive_parts_id' => 'required|exists:automotive_parts,automotive_parts_id',
            'parts_used.*.variation_id' => 'nullable|exists:part_variations,variation_id',
            'parts_used.*.quantity' => 'required|integer|min:1',
            'parts_used.*.unit_price' => 'required|numeric|min:0',
            'parts_used.*.subtotal' => 'required|numeric|min:0',
            'parts_used.*.original_quantity' => 'nullable|integer',
        ]);

        // Handle vehicle picture upload
        $vehiclePicturePath = $jobOrder->vehicle_picture;
        if ($request->hasFile('vehicle_picture')) {
            // Delete old picture if exists
            if ($vehiclePicturePath && \Storage::disk('public')->exists($vehiclePicturePath)) {
                \Storage::disk('public')->delete($vehiclePicturePath);
            }
            $vehiclePicturePath = $request->file('vehicle_picture')->store('vehicle_pictures', 'public');
        }

        // Update the main Job Order
        $jobOrder->update([
            'customer_name' => strtoupper($validated['customer_name']), 
            'customer_phone_num' => $validated['customer_phone_num'],
            'vehicle_plate' => strtoupper($validated['vehicle_plate']),
            'vehicle_brand' => strtoupper($validated['vehicle_brand']),
            'vehicle_model' => strtoupper($validated['vehicle_model']),
            'vehicle_picture' => $vehiclePicturePath,
            'reported_issue' => $validated['reported_issue'],
            'status' => $validated['status'],
            'total_cost' => $validated['total_cost'],
        ]);

        // Get existing part IDs to track deletions
        $existingPartIds = $jobOrder->jobOrderParts->pluck('job_order_parts_id')->toArray();
        $updatedPartIds = [];

        // Update or create parts
        if (!empty($validated['parts_used'])) {
            foreach ($validated['parts_used'] as $partData) {
                $jobOrderPartId = $partData['job_order_parts_id'] ?? null;
                $originalQuantity = $partData['original_quantity'] ?? 0;
                $newQuantity = $partData['quantity'];
                $quantityDifference = $newQuantity - $originalQuantity;

                if ($jobOrderPartId) {
                    // Update existing part
                    $updatedPartIds[] = $jobOrderPartId;
                    
                    $jobOrderPart = JobOrdersParts::find($jobOrderPartId);
                    $jobOrderPart->update([
                        'automotive_parts_id' => $partData['automotive_parts_id'],
                        'variation_id' => $partData['variation_id'] ?? null,
                        'quantity_used' => $newQuantity,
                        'unit_price' => $partData['unit_price'],
                        'subtotal' => $partData['subtotal'],
                    ]);

                    // Adjust stock based on quantity change
                    if ($quantityDifference != 0) {
                        if ($partData['variation_id']) {
                            $variation = PartVariation::find($partData['variation_id']);
                            if ($variation) {
                                if ($quantityDifference > 0) {
                                    // Increased quantity - deduct more stock
                                    $variation->decrement('stock_quantity', $quantityDifference);
                                } else {
                                    // Decreased quantity - return stock
                                    $variation->increment('stock_quantity', abs($quantityDifference));
                                }
                            }
                        } else {
                            $part = AutomotiveParts::find($partData['automotive_parts_id']);
                            if ($part) {
                                if ($quantityDifference > 0) {
                                    // Increased quantity - deduct more stock
                                    $part->decrement('stock_quantity', $quantityDifference);
                                } else {
                                    // Decreased quantity - return stock
                                    $part->increment('stock_quantity', abs($quantityDifference));
                                }
                            }
                        }
                    }
                } else {
                    // Create new part entry
                    $newJobOrderPart = JobOrdersParts::create([
                        'job_orders_id' => $jobOrder->job_orders_id,
                        'automotive_parts_id' => $partData['automotive_parts_id'],
                        'variation_id' => $partData['variation_id'] ?? null,
                        'quantity_used' => $newQuantity,
                        'unit_price' => $partData['unit_price'],
                        'subtotal' => $partData['subtotal'],
                    ]);

                    $updatedPartIds[] = $newJobOrderPart->job_order_parts_id;

                    // Deduct stock for new part
                    if ($partData['variation_id']) {
                        $variation = PartVariation::find($partData['variation_id']);
                        if ($variation) {
                            $variation->decrement('stock_quantity', $newQuantity);
                        }
                    } else {
                        $part = AutomotiveParts::find($partData['automotive_parts_id']);
                        if ($part) {
                            $part->decrement('stock_quantity', $newQuantity);
                        }
                    }
                }
            }
        }

        // Delete removed parts and return their stock
        $deletedPartIds = array_diff($existingPartIds, $updatedPartIds);
        foreach ($deletedPartIds as $deletedId) {
            $deletedPart = JobOrdersParts::find($deletedId);
            if ($deletedPart) {
                // Return stock
                if ($deletedPart->variation_id) {
                    $variation = PartVariation::find($deletedPart->variation_id);
                    if ($variation) {
                        $variation->increment('stock_quantity', $deletedPart->quantity_used);
                    }
                } else {
                    $part = AutomotiveParts::find($deletedPart->automotive_parts_id);
                    if ($part) {
                        $part->increment('stock_quantity', $deletedPart->quantity_used);
                    }
                }
                $deletedPart->delete();
            }
        }

        return redirect()->route('displayJobOrders')->with('success', 'Job Order updated successfully!');
    }
}