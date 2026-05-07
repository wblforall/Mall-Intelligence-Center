<?php

namespace App\Models;

use CodeIgniter\Model;

class CreativeInsightModel extends Model
{
    protected $table         = 'creative_insight';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'creative_item_id', 'tanggal', 'platform',
        'reach', 'impressions', 'views', 'likes', 'comments',
        'shares', 'saves', 'followers_gained',
        'file_name', 'original_name', 'catatan', 'created_by',
    ];

    public function getGroupedByItems(array $ids): array
    {
        if (empty($ids)) return [];

        $rows = $this->whereIn('creative_item_id', $ids)
                     ->orderBy('tanggal', 'ASC')
                     ->orderBy('id', 'ASC')
                     ->findAll();

        $grouped = [];
        foreach ($rows as $row) {
            $cid = $row['creative_item_id'];
            $grouped[$cid]['entries'][] = $row;
        }

        foreach ($grouped as $cid => &$data) {
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

    public function getMonthlyGrouped(string $bulan, array $ids): array
    {
        if (empty($ids)) return [];
        [$year, $month] = explode('-', $bulan);
        $rows = $this->whereIn('creative_item_id', $ids)
                     ->where('YEAR(tanggal)', (int)$year)
                     ->where('MONTH(tanggal)', (int)$month)
                     ->orderBy('tanggal', 'ASC')->orderBy('id', 'ASC')
                     ->findAll();
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['creative_item_id']]['entries'][] = $row;
        }
        foreach ($grouped as $cid => &$data) {
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
