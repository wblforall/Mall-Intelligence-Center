<?php

namespace App\Controllers;

use App\Models\EventCompletionModel;
use App\Libraries\ActivityLog;

class EventCompletion extends BaseController
{
    public function mark(int $eventId, string $module)
    {
        if (! array_key_exists($module, EventCompletionModel::REQUIRED_MODULES)) {
            return redirect()->back()->with('error', 'Modul tidak valid.');
        }

        (new EventCompletionModel())->mark($eventId, $module, $this->currentUser()['id']);
        ActivityLog::write('update', 'completion', (string)$eventId, "Mark selesai: {$module}", ['event_id' => $eventId, 'module' => $module]);

        return redirect()->to("/events/{$eventId}/{$module}")->with('success', 'Data berhasil ditandai selesai.');
    }

    public function unmark(int $eventId, string $module)
    {
        if (! $this->isAdmin()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        (new EventCompletionModel())->unmark($eventId, $module);
        ActivityLog::write('delete', 'completion', (string)$eventId, "Unmark selesai: {$module}", ['event_id' => $eventId, 'module' => $module]);

        return redirect()->to("/events/{$eventId}/{$module}")->with('success', 'Tanda selesai dibatalkan.');
    }
}
