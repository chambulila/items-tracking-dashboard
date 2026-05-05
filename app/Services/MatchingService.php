<?php

namespace App\Services;

use App\Models\FoundItem;
use App\Models\ItemMatch;
use App\Models\LostItem;

class MatchingService
{
    public function __construct(private NotificationService $notifications) {}

    public function matchLostItem(LostItem $lostItem, bool $notify = true): int
    {
        $created = 0;

        FoundItem::query()
            ->where('status', 'unclaimed')
            ->chunkById(100, function ($foundItems) use ($lostItem, $notify, &$created): void {
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

                        if ($notify) {
                            $this->notifyUsers($match);
                        }
                    }
                }
            });

        return $created;
    }

    public function matchFoundItem(FoundItem $foundItem, bool $notify = true): int
    {
        $created = 0;

        LostItem::query()
            ->where('status', 'open')
            ->chunkById(100, function ($lostItems) use ($foundItem, $notify, &$created): void {
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

                        if ($notify) {
                            $this->notifyUsers($match);
                        }
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
        $score = 0;
        $reasons = [];

        if ($lostItem->item_category_id === $foundItem->item_category_id) {
            $score += 30;
            $reasons[] = 'same category';
        }

        if ($this->sameText($lostItem->serial_imei, $foundItem->serial_imei, true)) {
            $score += 60;
            $reasons[] = 'serial or IMEI matches';
        }

        if ($this->sameText($lostItem->name, $foundItem->name)) {
            $score += 25;
            $reasons[] = 'name matches';
        } elseif ($this->containsEither($lostItem->name, $foundItem->name)) {
            $score += 15;
            $reasons[] = 'name is similar';
        }

        foreach (['color' => 10, 'brand_model' => 15] as $field => $points) {
            if ($this->sameText($lostItem->{$field}, $foundItem->{$field})) {
                $score += $points;
                $reasons[] = str_replace('_', ' ', $field).' matches';
            }
        }

        if ($this->containsEither($lostItem->description, $foundItem->name) || $this->containsEither($foundItem->description, $lostItem->name)) {
            $score += 10;
            $reasons[] = 'description references item details';
        }

        if (abs($lostItem->lost_date->diffInDays($foundItem->found_date, false)) <= 14) {
            $score += 10;
            $reasons[] = 'dates are close';
        }

        if ($lostItem->campus_id === $foundItem->campus_id) {
            $score += 8;
            $reasons[] = 'same campus';
        }

        if ($lostItem->building_id && $lostItem->building_id === $foundItem->building_id) {
            $score += 7;
            $reasons[] = 'same building';
        }

        $distanceKm = $this->distanceKm($lostItem->latitude, $lostItem->longitude, $foundItem->latitude, $foundItem->longitude);

        if ($distanceKm !== null && $distanceKm <= 2) {
            $score += 15;
            $reasons[] = 'locations are close';
        }

        return ['score' => min($score, 100), 'reasons' => $reasons ?: ['weak similarity']];
    }

    private function sameText(?string $left, ?string $right, bool $alphanumericOnly = false): bool
    {
        if (blank($left) || blank($right)) {
            return false;
        }

        $left = $this->normalize($left, $alphanumericOnly);
        $right = $this->normalize($right, $alphanumericOnly);

        return $left !== '' && $left === $right;
    }

    private function containsEither(?string $left, ?string $right): bool
    {
        if (blank($left) || blank($right)) {
            return false;
        }

        $left = $this->normalize($left);
        $right = $this->normalize($right);

        return mb_strlen($left) >= 3
            && mb_strlen($right) >= 3
            && (str_contains($left, $right) || str_contains($right, $left));
    }

    private function normalize(string $value, bool $alphanumericOnly = false): string
    {
        $value = mb_strtolower(trim($value));

        if ($alphanumericOnly) {
            return (string) preg_replace('/[^a-z0-9]/', '', $value);
        }

        return preg_replace('/\s+/', ' ', $value) ?: '';
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

    private function distanceKm(mixed $lat1, mixed $lon1, mixed $lat2, mixed $lon2): ?float
    {
        if ($lat1 === null || $lon1 === null || $lat2 === null || $lon2 === null) {
            return null;
        }

        $lat1 = (float) $lat1;
        $lon1 = (float) $lon1;
        $lat2 = (float) $lat2;
        $lon2 = (float) $lon2;

        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lonDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
