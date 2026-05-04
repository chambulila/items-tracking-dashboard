<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\ItemMatch;
use App\Models\LostItem;

class MatchingService
{
    public function __construct(private NotificationService $notifications) {}

    public function matchLostItem(LostItem $lostItem): int
    {
        $created = 0;

        FoundItem::query()
            ->where('status', 'unclaimed')
            ->where('item_category_id', $lostItem->item_category_id)
            ->chunkById(100, function ($foundItems) use ($lostItem, &$created): void {
                foreach ($foundItems as $foundItem) {
                    $result = $this->score($lostItem, $foundItem);

                    if ($result['score'] < 55) {
                        continue;
                    }

                    $match = ItemMatch::query()->updateOrCreate(
                        ['lost_item_id' => $lostItem->id, 'found_item_id' => $foundItem->id],
                        ['score' => $result['score'], 'reasons' => $result['reasons'], 'status' => 'possible']
                    );

                    if ($match->wasRecentlyCreated) {
                        $created++;
                        $this->notifyUsers($match);
                    }
                }
            });

        return $created;
    }

    public function matchFoundItem(FoundItem $foundItem): int
    {
        $created = 0;

        LostItem::query()
            ->where('status', 'open')
            ->where('item_category_id', $foundItem->item_category_id)
            ->chunkById(100, function ($lostItems) use ($foundItem, &$created): void {
                foreach ($lostItems as $lostItem) {
                    $result = $this->score($lostItem, $foundItem);

                    if ($result['score'] < 55) {
                        continue;
                    }

                    $match = ItemMatch::query()->updateOrCreate(
                        ['lost_item_id' => $lostItem->id, 'found_item_id' => $foundItem->id],
                        ['score' => $result['score'], 'reasons' => $result['reasons'], 'status' => 'possible']
                    );

                    if ($match->wasRecentlyCreated) {
                        $created++;
                        $this->notifyUsers($match);
                    }
                }
            });

        return $created;
    }

    /**
     * @return array{score:int,reasons:array<int, string>}
     */
    private function score(LostItem $lostItem, FoundItem $foundItem): array
    {
        $score = 25;
        $reasons = ['same category'];

        foreach (['name' => 20, 'color' => 15, 'brand_model' => 15, 'serial_imei' => 25] as $field => $points) {
            if ($lostItem->{$field} && $foundItem->{$field} && mb_strtolower((string) $lostItem->{$field}) === mb_strtolower((string) $foundItem->{$field})) {
                $score += $points;
                $reasons[] = str_replace('_', ' ', $field).' matches';
            }
        }

        if (str_contains(mb_strtolower($foundItem->description), mb_strtolower($lostItem->name))) {
            $score += 10;
            $reasons[] = 'description references item name';
        }

        if (abs($lostItem->lost_date->diffInDays($foundItem->found_date, false)) <= 14) {
            $score += 10;
            $reasons[] = 'dates are close';
        }

        if ($this->distanceKm((float) $lostItem->latitude, (float) $lostItem->longitude, (float) $foundItem->latitude, (float) $foundItem->longitude) <= 1.5) {
            $score += 15;
            $reasons[] = 'locations are close';
        }

        return ['score' => min($score, 100), 'reasons' => $reasons];
    }

    private function notifyUsers(ItemMatch $match): void
    {
        $match->loadMissing('lostItem.user', 'foundItem.finder');

        $this->notifications->send(
            $match->lostItem->user,
            'item_match',
            'Possible item match found',
            "A found item may match your lost {$match->lostItem->name}.",
            ['match_id' => $match->id]
        );

        $this->notifications->send(
            $match->foundItem->finder,
            'item_match',
            'Found item may have an owner',
            "Your found {$match->foundItem->name} matches a lost item report.",
            ['match_id' => $match->id]
        );
    }

    private function distanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
