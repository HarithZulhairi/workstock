<?php

namespace App\Http\Controllers;

use App\Models\JobOrders;
use App\Models\AutomotiveParts;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\PartVariation;
use Barryvdh\DomPDF\Facade\Pdf; // Requires: composer require barryvdh/laravel-dompdf
use Illuminate\Support\Collection;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Top Level Stats
        $totalParts = AutomotiveParts::count();
        $totalJobs = JobOrders::count();
        
        $lowStockBase = AutomotiveParts::where('stock_quantity', '<', 5)->get();
        $lowStockVariations = PartVariation::with('part.category')->where('stock_quantity', '<', 5)->get();
        
        $lowStockCount = $lowStockBase->count() + $lowStockVariations->count();

        // Format Base Parts for the modal
        $formattedLowStockItems = collect();

        foreach ($lowStockBase as $part) {
            $formattedLowStockItems->push([
                'id' => 'part_' . $part->automotive_parts_id,
                'name' => $part->name,
                'var_name' => $part->base_var_name ?? 'Default',
                'type' => 'Base Part',
                'category' => $part->category ? $part->category->name : 'Uncategorized',
                'stock_quantity' => $part->stock_quantity,
                'image' => !empty($part->part_images) ? '/storage/' . $part->part_images[0] : null,
            ]);
        }

        // Format Variations for the modal
        foreach ($lowStockVariations as $variation) {
            $parent = $variation->part;
            $formattedLowStockItems->push([
                'id' => 'var_' . $variation->variation_id,
                'name' => ($parent ? $parent->name : 'Unknown Part'),
                'var_name' => $variation->name,
                'type' => 'Variation',
                'category' => ($parent && $parent->category) ? $parent->category->name : 'Uncategorized',
                'stock_quantity' => $variation->stock_quantity,
                // Fallback to parent image if variation has no picture
                'image' => $variation->picture 
                            ? '/storage/' . $variation->picture 
                            : ($parent && !empty($parent->part_images) ? '/storage/' . $parent->part_images[0] : null),
            ]);
        }

        // Sort the combined list by stock quantity (lowest first)
        $formattedLowStockItems = $formattedLowStockItems->sortBy('stock_quantity')->values()->toArray();

        // 2. Recent Job Orders
        $recentOrders = JobOrders::with('handler')->latest('job_orders_id')->take(5)->get();

        // 3. Chart Data: Job Orders by Status
        $jobOrdersByStatus = JobOrders::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $chartJobOrders = [];
        $maxJobOrderCount = 1; 
        
        foreach ($jobOrdersByStatus as $row) {
            $chartJobOrders[] = [
                'label' => $row->status, 
                'value' => $row->count
            ];
            if ($row->count > $maxJobOrderCount) {
                $maxJobOrderCount = $row->count;
            }
        }

        // 4. Chart Data: Inventory by Category
        $inventoryByCategory = AutomotiveParts::with('category')
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function($part) {
                return [
                    'label' => $part->category ? $part->category->name : 'Uncategorized',
                    'value' => $part->count
                ];
            });
            
        $maxInventoryCount = $inventoryByCategory->max('value') ?: 1;

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalParts' => $totalParts,
                'totalJobs' => $totalJobs,
                'lowStock' => $lowStockCount,
                'lowStockItems' => $formattedLowStockItems, // Pass the newly formatted array
            ],
            'recentOrders' => $recentOrders,
            'charts' => [
                'jobOrders' => $chartJobOrders,
                'maxJobOrders' => $maxJobOrderCount,
                'inventory' => $inventoryByCategory,
                'maxInventory' => $maxInventoryCount
            ]
        ]);
    }

    public function downloadReport()
    {
        // 1. Job Orders Chart Data
        $jobOrdersByStatus = JobOrders::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        $chartJobOrders = [];
        $maxJobOrderCount = 1; 
        foreach ($jobOrdersByStatus as $row) {
            $chartJobOrders[] = [
                'label' => $row->status, 
                'value' => $row->count
            ];
            if ($row->count > $maxJobOrderCount) {
                $maxJobOrderCount = $row->count;
            }
        }

        // 2. Inventory Category Chart Data
        $inventoryByCategory = AutomotiveParts::with('category')
            ->selectRaw('category_id, count(*) as count')
            ->groupBy('category_id')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function($part) {
                return [
                    'label' => $part->category ? $part->category->name : 'Uncategorized',
                    'value' => $part->count
                ];
            });
            
        $maxInventoryCount = $inventoryByCategory->max('value') ?: 1;

        // Gather all data for the report
        $data = [
            'totalParts' => AutomotiveParts::count(),
            'totalJobs' => JobOrders::count(),
            'completedOrders' => JobOrders::where('status', 'Completed')->count(),
            'lowStockParts' => AutomotiveParts::where('stock_quantity', '<', 5)->get(),
            'lowStockVariations' => PartVariation::where('stock_quantity', '<', 5)->get(),
            'date' => now()->format('d M Y'),
            'jobOrdersChart' => $chartJobOrders,
            'maxJobOrders' => $maxJobOrderCount,
            'inventoryChart' => $inventoryByCategory,
            'maxInventory' => $maxInventoryCount,
        ];

        $pdf = Pdf::loadView('reports.dashboard', $data);
        
        return $pdf->download('workstock-inventory-report.pdf');
    }
}