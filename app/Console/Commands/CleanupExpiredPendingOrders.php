<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PendingOrder;

class CleanupExpiredPendingOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'orders:cleanup-expired 
                            {--dry-run : Show what would be cleaned up without making changes}
                            {--older-than=24 : Clean up orders older than X hours (default: 24)}
                            {--delete-completed=30 : Delete completed orders older than X days (0 = don\'t delete)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired pending orders and optionally delete old completed ones';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $olderThanHours = (int) $this->option('older-than');

        $this->info("Looking for pending orders expired more than {$olderThanHours} hours ago...");

        // Find expired pending orders
        $expiredOrders = PendingOrder::where('expires_at', '<', now()->subHours($olderThanHours))
            ->whereIn('status', ['pending_payment', 'payment_verified'])
            ->get();

        if ($expiredOrders->isEmpty()) {
            $this->info('No expired pending orders found.');
            return 0;
        }

        $this->info("Found {$expiredOrders->count()} expired pending orders:");

        // Show details
        $this->table(
            ['ID', 'Reference', 'User ID', 'Amount', 'Status', 'Expired At'],
            $expiredOrders->map(function ($order) {
                return [
                    $order->id,
                    $order->payment_reference,
                    $order->user_id,
                    '$' . number_format($order->total_amount, 2),
                    $order->status,
                    $order->expires_at->diffForHumans()
                ];
            })->toArray()
        );

        if ($dryRun) {
            $this->warn('DRY RUN: No changes made. Remove --dry-run to actually clean up.');
            return 0;
        }

        if (!$this->confirm('Do you want to mark these orders as expired?')) {
            $this->info('Cleanup cancelled.');
            return 0;
        }

        // Mark as expired
        $cleaned = PendingOrder::cleanupExpired();

        $this->info("Successfully marked {$cleaned} pending orders as expired.");

        return 0;
    }
}
