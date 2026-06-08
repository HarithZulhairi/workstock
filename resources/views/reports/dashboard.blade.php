<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Workspace Report</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        
        /* Header Section */
        .header-table { width: 100%; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 20px; }
        .header-logo { width: 70px; height: auto; }
        .header-title { text-align: right; }
        .header-title h1 { margin: 0; color: #0f172a; font-size: 24px; text-transform: uppercase; }
        .header-title p { margin: 5px 0 0 0; color: #64748b; font-size: 12px; }

        .section-title {
            font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 10px;
            text-transform: uppercase; border-left: 4px solid #3b82f6; padding-left: 10px;
            page-break-after: avoid;
        }

        /* Stats Section */
        .stats-table { width: 100%; margin-bottom: 30px; border-collapse: separate; border-spacing: 10px; }
        .stat-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 8px; text-align: center; width: 33.33%; }
        .stat-label { font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: bold; margin-bottom: 5px; }
        .stat-value { font-size: 24px; font-weight: bold; color: #0f172a; margin: 0; }

        /* Charts Section */
        .charts-container { width: 100%; margin-bottom: 30px; page-break-inside: avoid; }
        .chart-box { border: 1px solid #e2e8f0; border-radius: 8px; padding: 15px; vertical-align: top; }
        
        /* Horizontal Bar Chart (Safe for DOMPDF) */
        .h-chart-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .h-chart-table td { padding: 4px 0; }
        .h-bar-bg { width: 100%; background-color: #f1f5f9; height: 12px; border-radius: 6px; margin-bottom: 8px; }
        .h-bar-fill { height: 12px; border-radius: 6px; }
        .h-label { font-size: 11px; color: #475569; font-weight: bold;}
        .h-value { font-size: 11px; font-weight: bold; color: #0f172a; text-align: right; }

        /* Data Table Section */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; page-break-inside: auto; }
        .data-table tr { page-break-inside: avoid; page-break-after: auto; }
        .data-table th { background-color: #f1f5f9; color: #475569; font-size: 11px; text-transform: uppercase; padding: 10px; text-align: left; border-bottom: 2px solid #cbd5e1; }
        .data-table td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        .badge-danger { background-color: #fee2e2; color: #991b1b; padding: 3px 8px; border-radius: 12px; font-size: 10px; font-weight: bold; }

        /* Footer */
        .footer { position: fixed; bottom: -20px; left: 0; right: 0; height: 40px; border-top: 1px solid #e2e8f0; text-align: center; line-height: 40px; font-size: 10px; color: #94a3b8; }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            @php
                $imagePath = public_path('images/workstock-icon-sidebar.png');
                $imageData = file_exists($imagePath) ? base64_encode(file_get_contents($imagePath)) : '';
            @endphp
            <td width="20%">
                @if($imageData)
                    <img src="data:image/png;base64,{{ $imageData }}" class="header-logo" alt="WorkStock Logo">
                @else
                    <h2>WORKSTOCK</h2>
                @endif
            </td>
            <td class="header-title">
                <h1>Workspace Report</h1>
                <p>Generated on: {{ $date }}</p>
            </td>
        </tr>
    </table>

    <div class="section-title">Executive Summary</div>
    <table class="stats-table">
        <tr>
            <td class="stat-box">
                <div class="stat-label">Total Inventory Parts</div>
                <div class="stat-value">{{ $totalParts }}</div>
            </td>
            <td class="stat-box">
                <div class="stat-label">Total Job Orders</div>
                <div class="stat-value">{{ $totalJobs }}</div>
            </td>
            <td class="stat-box">
                <div class="stat-label">Low Stock Alerts</div>
                <div class="stat-value" style="color: #dc2626;">{{ count($lowStockParts) }}</div>
            </td>
        </tr>
    </table>

    <table class="charts-container">
        <tr>
            <td class="chart-box" width="48%" style="margin-right: 2%;">
                <div class="section-title" style="border-left-color: #10b981;">Job Orders Status Pipeline</div>
                <table class="h-chart-table">
                    @forelse($jobOrdersChart as $item)
                        @php 
                            $width = $maxJobOrders > 0 ? ($item['value'] / $maxJobOrders) * 100 : 0; 
                            // Determine bar color based on status
                            $color = '#3b82f6'; // default blue
                            if(strtolower($item['label']) == 'arrived') $color = '#10b981'; // emerald
                            if(strtolower($item['label']) == 'pending') $color = '#f59e0b'; // amber
                        @endphp
                        <tr>
                            <td class="h-label">{{ $item['label'] }}</td>
                            <td class="h-value">{{ $item['value'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="h-bar-bg">
                                    <div class="h-bar-fill" style="width: {{ $width }}%; background-color: {{ $color }};"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" align="center" style="padding: 30px 0; color: #94a3b8; font-size: 12px;">No data available</td></tr>
                    @endforelse
                </table>
            </td>
            
            <td width="4%"></td> <td class="chart-box" width="48%">
                <div class="section-title" style="border-left-color: #0f172a;">Top Inventory Categories</div>
                <table class="h-chart-table">
                    @forelse($inventoryChart as $item)
                        @php $width = $maxInventory > 0 ? ($item['value'] / $maxInventory) * 100 : 0; @endphp
                        <tr>
                            <td class="h-label">{{ $item['label'] }}</td>
                            <td class="h-value">{{ $item['value'] }}</td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="h-bar-bg">
                                    <div class="h-bar-fill" style="width: {{ $width }}%; background-color: #0f172a;"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="2" align="center" style="padding: 30px 0; color: #94a3b8; font-size: 12px;">No data available</td></tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    <div class="section-title" style="border-left-color: #ef4444;">Critical Low Stock Alerts</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="10%">#</th>
                <th width="40%">Part Name</th>
                <th width="20%">Serial Number</th>
                <th width="15%">Price (RM)</th>
                <th width="15%" style="text-align: center;">Stock Qty</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lowStockParts as $index => $part)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td><strong>{{ $part->name }}</strong></td>
                    <td>{{ $part->part_serial_number }}</td>
                    <td>{{ number_format($part->price, 2) }}</td>
                    <td style="text-align: center;">
                        <span class="badge-danger">{{ $part->stock_quantity }} units</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #64748b;">
                        <em>No low stock items detected. Inventory is healthy.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated by WorkStock Management System &copy; {{ date('Y') }} | Confidential
    </div>

</body>
</html>