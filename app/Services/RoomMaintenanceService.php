<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomMaintenanceSchedule;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class RoomMaintenanceService
{
    /**
     * Schedule a new maintenance period for a room
     *
     * @param Room $room
     * @param Carbon $startDatetime
     * @param Carbon $endDatetime
     * @param string|null $reason
     * @param User|null $createdBy
     * @return RoomMaintenanceSchedule
     * @throws InvalidArgumentException
     */
    public function scheduleMaintenance(
        Room $room,
        Carbon $startDatetime,
        Carbon $endDatetime,
        ?string $reason = null,
        ?User $createdBy = null
    ): RoomMaintenanceSchedule {
        // Validate end date is after start date
        if ($endDatetime <= $startDatetime) {
            throw new InvalidArgumentException('End datetime must be after start datetime.');
        }

        // Check for conflicts
        if ($this->hasConflict($room, $startDatetime, $endDatetime)) {
            throw new InvalidArgumentException('This maintenance schedule conflicts with an existing schedule.');
        }

        return RoomMaintenanceSchedule::create([
            'room_id' => $room->id,
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'reason' => $reason,
            'created_by' => $createdBy?->id,
        ]);
    }

    /**
     * Cancel (delete) a maintenance schedule
     *
     * @param RoomMaintenanceSchedule $schedule
     * @return bool
     */
    public function cancelMaintenance(RoomMaintenanceSchedule $schedule): bool
    {
        // If maintenance is currently active, revert room status
        if ($schedule->is_active) {
            // Check if there are other active schedules
            $otherActiveSchedules = RoomMaintenanceSchedule::where('room_id', $schedule->room_id)
                ->where('id', '!=', $schedule->id)
                ->active()
                ->exists();

            if (!$otherActiveSchedules) {
                $schedule->room->update(['status' => 'active']);
            }
        }

        return $schedule->delete();
    }

    /**
     * Update an existing maintenance schedule
     *
     * @param RoomMaintenanceSchedule $schedule
     * @param Carbon $startDatetime
     * @param Carbon $endDatetime
     * @param string|null $reason
     * @return RoomMaintenanceSchedule
     * @throws InvalidArgumentException
     */
    public function updateMaintenance(
        RoomMaintenanceSchedule $schedule,
        Carbon $startDatetime,
        Carbon $endDatetime,
        ?string $reason = null
    ): RoomMaintenanceSchedule {
        // Validate end date is after start date
        if ($endDatetime <= $startDatetime) {
            throw new InvalidArgumentException('End datetime must be after start datetime.');
        }

        // Check for conflicts (excluding this schedule)
        if ($this->hasConflict($schedule->room, $startDatetime, $endDatetime, $schedule->id)) {
            throw new InvalidArgumentException('This maintenance schedule conflicts with an existing schedule.');
        }

        $schedule->update([
            'start_datetime' => $startDatetime,
            'end_datetime' => $endDatetime,
            'reason' => $reason,
        ]);

        return $schedule->fresh();
    }

    /**
     * Check if a maintenance schedule would conflict with existing schedules
     *
     * @param Room $room
     * @param Carbon $startDatetime
     * @param Carbon $endDatetime
     * @param int|null $excludeId Schedule ID to exclude from conflict check
     * @return bool
     */
    public function hasConflict(
        Room $room,
        Carbon $startDatetime,
        Carbon $endDatetime,
        ?int $excludeId = null
    ): bool {
        $query = RoomMaintenanceSchedule::where('room_id', $room->id)
            ->where(function ($q) use ($startDatetime, $endDatetime) {
                // Check for overlap
                $q->where('start_datetime', '<', $endDatetime)
                    ->where('end_datetime', '>', $startDatetime);
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Get all upcoming maintenance schedules for a room
     *
     * @param Room $room
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUpcomingForRoom(Room $room)
    {
        return $room->maintenanceSchedules()
            ->upcoming()
            ->orderBy('start_datetime')
            ->get();
    }

    /**
     * Get currently active maintenance for a room
     *
     * @param Room $room
     * @return RoomMaintenanceSchedule|null
     */
    public function getActiveForRoom(Room $room): ?RoomMaintenanceSchedule
    {
        return $room->maintenanceSchedules()
            ->active()
            ->first();
    }
}
