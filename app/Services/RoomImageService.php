<?php

namespace App\Services;

use App\Models\Room;
use App\Models\RoomImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class RoomImageService
{
    protected string $disk = 'public';
    protected string $basePath = 'rooms';

    /**
     * Upload multiple images for a room
     *
     * @param Room $room
     * @param array<UploadedFile> $files
     * @return array<RoomImage>
     */
    public function uploadImages(Room $room, array $files): array
    {
        $uploadedImages = [];
        $currentCount = $room->images()->count();
        $maxImages = 5;

        // Check if adding these images would exceed the limit
        $availableSlots = $maxImages - $currentCount;
        if ($availableSlots <= 0) {
            return [];
        }

        // Only process up to available slots
        $filesToProcess = array_slice($files, 0, $availableSlots);
        $sortOrder = $currentCount;

        foreach ($filesToProcess as $file) {
            $path = $file->store("{$this->basePath}/{$room->id}", $this->disk);

            $image = RoomImage::create([
                'room_id' => $room->id,
                'path' => $path,
                'is_primary' => $currentCount === 0 && $sortOrder === 0, // First image is primary
                'sort_order' => $sortOrder++,
            ]);

            $uploadedImages[] = $image;
        }

        return $uploadedImages;
    }

    /**
     * Delete a room image
     *
     * @param RoomImage $image
     * @return bool
     */
    public function deleteImage(RoomImage $image): bool
    {
        // Delete file from storage
        if (Storage::disk($this->disk)->exists($image->path)) {
            Storage::disk($this->disk)->delete($image->path);
        }

        $wasPrimary = $image->is_primary;
        $roomId = $image->room_id;

        // Delete the database record
        $image->delete();

        // If deleted image was primary, set another as primary
        if ($wasPrimary) {
            $newPrimary = RoomImage::where('room_id', $roomId)
                ->orderBy('sort_order')
                ->first();

            if ($newPrimary) {
                $newPrimary->update(['is_primary' => true]);
            }
        }

        return true;
    }

    /**
     * Set an image as the primary image
     *
     * @param RoomImage $image
     * @return bool
     */
    public function setPrimaryImage(RoomImage $image): bool
    {
        // Unset all other images as primary for this room
        RoomImage::where('room_id', $image->room_id)
            ->where('id', '!=', $image->id)
            ->update(['is_primary' => false]);

        // Set this image as primary
        return $image->update(['is_primary' => true]);
    }

    /**
     * Reorder images for a room
     *
     * @param Room $room
     * @param array<int> $order Array of image IDs in desired order
     * @return bool
     */
    public function reorderImages(Room $room, array $order): bool
    {
        foreach ($order as $index => $imageId) {
            RoomImage::where('room_id', $room->id)
                ->where('id', $imageId)
                ->update(['sort_order' => $index]);
        }

        return true;
    }

    /**
     * Delete all images for a room
     *
     * @param Room $room
     * @return bool
     */
    public function deleteAllImages(Room $room): bool
    {
        foreach ($room->images as $image) {
            $this->deleteImage($image);
        }

        // Also delete the room's folder if empty
        $folderPath = "{$this->basePath}/{$room->id}";
        if (Storage::disk($this->disk)->exists($folderPath)) {
            $files = Storage::disk($this->disk)->files($folderPath);
            if (empty($files)) {
                Storage::disk($this->disk)->deleteDirectory($folderPath);
            }
        }

        return true;
    }
}
