<?php

namespace App\Http\Controllers;

use App\Models\JobOrders;
use App\Models\AutomotiveParts;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf; // Requires: composer require barryvdh/laravel-dompdf

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Top Level Stats
        $totalParts = AutomotiveParts::count();
        $totalJobs = JobOrders::count();
        $lowStock = AutomotiveParts::where('stock_quantity', '<', 5)->count();

        // 2. Recent Job Orders
        $recentOrders = JobOrders::with('handler')->latest('job_orders_id')->take(5)->get();

        // 3. Chart Data: Job Orders by Status
        $jobOrdersByStatus = JobOrders::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get(); // Get the actual statuses that exist in the DB

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

        // 4. Chart Data: Inventory by Category (Top 5)
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
        
        // dd($inventoryByCategory);
            
        $maxInventoryCount = $inventoryByCategory->max('value') ?: 1;

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalParts' => $totalParts,
                'totalJobs' => $totalJobs,
                'lowStock' => $lowStock
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
        // Gather data for the report
        $data = [
            'totalParts' => AutomotiveParts::count(),
            'totalJobs' => JobOrders::count(),
            'completedOrders' => JobOrders::where('status', 'Completed')->count(),
            'lowStockParts' => AutomotiveParts::where('stock_quantity', '<', 5)->get(),
            'date' => now()->format('d M Y')
        ];

        // Ensure you create a simple blade file at resources/views/reports/dashboard.blade.php
        $pdf = Pdf::loadView('reports.dashboard', $data);
        
        return $pdf->download('workstock-inventory-report.pdf');
    }
}