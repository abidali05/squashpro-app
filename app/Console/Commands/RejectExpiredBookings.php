<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\CourtTimeSlot;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RejectExpiredBookings extends Command
{
    protected $signature   = 'bookings:reject-expired';
    protected $description = 'Reject pending bookings whose booking date and start time have passed.';

    public function handle(): int
    {
        $now   = Carbon::now('Asia/Karachi');
        $today = $now->toDateString();
        $time  = $now->format('H:i:s');

        // Fetch all pending bookings where date+time has already passed
        $expired = Booking::query()
            ->where('booking_status', 'pending')
            ->where(function ($query) use ($today, $time): void {
                // booking_date is strictly in the past
                $query->whereDate('booking_date', '<', $today)
                    // OR booking_date is today but start_time has passed
                    ->orWhere(function ($query) use ($today, $time): void {
                        $query->whereDate('booking_date', $today)
                              ->whereTime('start_time', '<=', $time);
                    });
            })
            ->get();

        if ($expired->isEmpty()) {
            $this->info('No expired pending bookings found.');
            return self::SUCCESS;
        }

        $rejected = 0;

        foreach ($expired as $booking) {
            DB::transaction(function () use ($booking): void {
                $booking->booking_status   = 'cancelled';
                $booking->rejection_reason = 'Rejected by system: booking time has passed.';
                $booking->save();

                // Free the slot back to available
                CourtTimeSlot::query()
                    ->where('booking_id', $booking->id)
                    ->update([
                        'status'     => 'available',
                        'booking_id' => null,
                    ]);
            });

            $rejected++;
        }

        Log::channel('daily')->info('bookings:reject-expired completed', [
            'rejected_count' => $rejected,
            'ran_at'         => $now->toDateTimeString(),
        ]);

        $this->info("Rejected {$rejected} expired pending booking(s).");

        return self::SUCCESS;
    }
}
