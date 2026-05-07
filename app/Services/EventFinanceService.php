<?php

namespace App\Services;

use CodeIgniter\Database\BaseConnection;

/**
 * Aggregates budget, revenue, and traffic figures for one or many events.
 * Bulk methods replace per-event loops that cause N+1 queries.
 */
class EventFinanceService
{
    // -------------------------------------------------------------------------
    // Single-event helpers (used in per-event summary pages)
    // -------------------------------------------------------------------------

    public static function getBudgetTotal(int $eventId): int
    {
        $totals = static::getBulkBudgetTotals([$eventId]);
        return $totals[$eventId] ?? 0;
    }

    public static function getRevenueTotal(int $eventId): int
    {
        $totals = static::getBulkRevenueTotals([$eventId]);
        return $totals[$eventId] ?? 0;
    }

    // -------------------------------------------------------------------------
    // Bulk helpers (used in monthly summary to avoid N+1)
    // -------------------------------------------------------------------------

    /**
     * Returns [event_id => total_budget] for all given event IDs.
     * Runs 5 queries total regardless of event count.
     */
    public static function getBulkBudgetTotals(array $eventIds): array
    {
        if (empty($eventIds)) return [];

        $db     = db_connect();
        $totals = array_fill_keys($eventIds, 0);

        $tables = [
            ['event_budgets',       'jumlah'],
            ['event_loyalty_programs', 'budget'],
            ['event_vm_items',      'budget'],
            ['event_content_items', 'budget'],
            ['event_creative_items','budget'],
        ];

        foreach ($tables as [$table, $col]) {
            $rows = $db->table($table)
                ->select("event_id, SUM({$col}) AS total")
                ->whereIn('event_id', $eventIds)
                ->groupBy('event_id')
                ->get()->getResultArray();

            foreach ($rows as $row) {
                $totals[(int)$row['event_id']] += (int)$row['total'];
            }
        }

        return $totals;
    }

    /**
     * Returns [event_id => total_revenue] for all given event IDs.
     * Runs 2 queries total regardless of event count.
     */
    public static function getBulkRevenueTotals(array $eventIds): array
    {
        if (empty($eventIds)) return [];

        $db     = db_connect();
        $totals = array_fill_keys($eventIds, 0);

        // Exhibition dealing
        $rows = $db->table('event_exhibitors')
            ->select('event_id, SUM(nilai_dealing) AS total')
            ->whereIn('event_id', $eventIds)
            ->groupBy('event_id')
            ->get()->getResultArray();
        foreach ($rows as $row) {
            $totals[(int)$row['event_id']] += (int)$row['total'];
        }

        // Sponsor cash
        $rows = $db->table('event_sponsors')
            ->select('event_id, SUM(nilai) AS total')
            ->whereIn('event_id', $eventIds)
            ->where('jenis', 'cash')
            ->groupBy('event_id')
            ->get()->getResultArray();
        foreach ($rows as $row) {
            $totals[(int)$row['event_id']] += (int)$row['total'];
        }

        return $totals;
    }

    /**
     * Returns [event_id => total_traffic] for all given events.
     * Fetches all traffic in the combined date range in ONE query,
     * then distributes to each event in PHP.
     *
     * @param array $events  Array of event rows, each with: id, start_date, event_days
     */
    public static function getBulkTrafficTotals(array $events): array
    {
        if (empty($events)) return [];

        $db      = db_connect();
        $totals  = array_fill_keys(array_column($events, 'id'), 0);
        $dateMap = static::buildEventDateMap($events);

        [$minDate, $maxDate] = static::getDateRange($events);

        $rows = $db->table('daily_traffic')
            ->select('tanggal, SUM(jumlah_pengunjung) AS total')
            ->where('tanggal >=', $minDate)
            ->where('tanggal <=', $maxDate)
            ->groupBy('tanggal')
            ->get()->getResultArray();

        $trafficByDate = [];
        foreach ($rows as $row) {
            $trafficByDate[$row['tanggal']] = (int)$row['total'];
        }

        foreach ($events as $ev) {
            $start = $ev['start_date'];
            $days  = (int)$ev['event_days'];
            for ($i = 0; $i < $days; $i++) {
                $date = date('Y-m-d', strtotime("{$start} +{$i} days"));
                $totals[(int)$ev['id']] += $trafficByDate[$date] ?? 0;
            }
        }

        return $totals;
    }

    /**
     * Returns [event_id => ['mobil' => x, 'motor' => y]] for all given events.
     * ONE query for all vehicle data.
     *
     * @param array $events  Array of event rows, each with: id, start_date, event_days
     */
    public static function getBulkVehicleTotals(array $events): array
    {
        if (empty($events)) return [];

        $db     = db_connect();
        $totals = array_fill_keys(array_column($events, 'id'), ['mobil' => 0, 'motor' => 0]);

        [$minDate, $maxDate] = static::getDateRange($events);

        $rows = $db->table('daily_vehicles')
            ->select('tanggal, SUM(total_mobil) AS mobil, SUM(total_motor) AS motor')
            ->where('tanggal >=', $minDate)
            ->where('tanggal <=', $maxDate)
            ->groupBy('tanggal')
            ->get()->getResultArray();

        $vehicleByDate = [];
        foreach ($rows as $row) {
            $vehicleByDate[$row['tanggal']] = ['mobil' => (int)$row['mobil'], 'motor' => (int)$row['motor']];
        }

        foreach ($events as $ev) {
            $start = $ev['start_date'];
            $days  = (int)$ev['event_days'];
            for ($i = 0; $i < $days; $i++) {
                $date = date('Y-m-d', strtotime("{$start} +{$i} days"));
                $totals[(int)$ev['id']]['mobil'] += $vehicleByDate[$date]['mobil'] ?? 0;
                $totals[(int)$ev['id']]['motor'] += $vehicleByDate[$date]['motor'] ?? 0;
            }
        }

        return $totals;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function getDateRange(array $events): array
    {
        $minDate = null;
        $maxDate = null;

        foreach ($events as $ev) {
            $start = $ev['start_date'];
            $end   = date('Y-m-d', strtotime($start . ' +' . ($ev['event_days'] - 1) . ' days'));
            if ($minDate === null || $start < $minDate) $minDate = $start;
            if ($maxDate === null || $end   > $maxDate) $maxDate = $end;
        }

        return [$minDate, $maxDate];
    }

    private static function buildEventDateMap(array $events): array
    {
        $map = [];
        foreach ($events as $ev) {
            $start = $ev['start_date'];
            $days  = (int)$ev['event_days'];
            for ($i = 0; $i < $days; $i++) {
                $map[date('Y-m-d', strtotime("{$start} +{$i} days"))][] = (int)$ev['id'];
            }
        }
        return $map;
    }
}
