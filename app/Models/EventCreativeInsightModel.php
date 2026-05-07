<?php

namespace App\Models;

use CodeIgniter\Model;

class EventCreativeInsightModel extends Model
{
    protected $table         = 'event_creative_insight';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'event_id', 'creative_item_id', 'tanggal', 'platform',
        'reach', 'impressions', 'views', 'likes', 'comments',
        'shares', 'saves', 'followers_gained',
        'file_name', 'original_name', 'catatan', 'created_by',
    ];

    public function getGroupedByItems(array $itemIds): array
    {
        if (empty($itemIds)) return [];
        $rows = $this->whereIn('creative_item_id', $itemIds)
                     ->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['creative_item_id']]['entries'][] = $row;
        }
        // Aggregate latest/best values per item
        foreach ($grouped as $iid => &$data) {
            $data['total_reach']     = max(array_column($data['entries'], 'reach'));
            $data['total_impressions']= max(array_column($data['entries'], 'impressions'));
            $data['total_views']     = max(array_column($data['entries'], 'views'));
            $data['total_likes']     = max(array_column($data['entries'], 'likes'));
            $data['total_comments']  = max(array_column($data['entries'], 'comments'));
            $data['total_shares']    = max(array_column($data['entries'], 'shares'));
            $data['total_saves']     = max(array_column($data['entries'], 'saves'));
            $data['total_followers'] = array_sum(array_column($data['entries'], 'followers_gained'));
        }
        unset($data);
        return $grouped;
    }

    public function getMonthlyGrouped(string $bulan, array $itemIds): array
    {
        if (empty($itemIds)) return [];
        [$year, $month] = explode('-', $bulan);
        $rows = $this->whereIn('creative_item_id', $itemIds)
                     ->where('YEAR(tanggal)', (int)$year)
                     ->where('MONTH(tanggal)', (int)$month)
                     ->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['creative_item_id']]['entries'][] = $row;
        }
        foreach ($grouped as $iid => &$data) {
            $entries = $data['entries'];
            $data['max_reach']             = max(array_column($entries, 'reach'));
            $data['max_impressions']       = max(array_column($entries, 'impressions'));
            $data['max_views']             = max(array_column($entries, 'views'));
            $data['max_likes']             = max(array_column($entries, 'likes'));
            $data['max_comments']          = max(array_column($entries, 'comments'));
            $data['max_shares']            = max(array_column($entries, 'shares'));
            $data['max_saves']             = max(array_column($entries, 'saves'));
            $data['total_followers_gained']= array_sum(array_column($entries, 'followers_gained'));
        }
        unset($data);
        return $grouped;
    }
}
