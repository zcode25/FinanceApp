<?php

use App\Services\AnalysisService;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new AnalysisService();

// Test Jan 2026
$startDate = Carbon::createFromFormat('Y-m', '2026-01')->startOfMonth();
$endDate = Carbon::createFromFormat('Y-m', '2026-01')->endOfMonth();

echo "Testing getCashFlowTrend...\n";
try {
    $trend = $service->getCashFlowTrend($startDate, $endDate);
    echo "Trend Count: " . count($trend['labels']) . "\n";
    echo "First Label: " . ($trend['labels'][0] ?? 'None') . "\n";
} catch (\Exception $e) {
    echo "Error in getCashFlowTrend: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\nTesting getSmartInsights...\n";
try {
    $insights = $service->getSmartInsights($startDate, $endDate);
    echo "Insights Count: " . count($insights) . "\n";
    print_r($insights);
} catch (\Exception $e) {
    echo "Error in getSmartInsights: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
