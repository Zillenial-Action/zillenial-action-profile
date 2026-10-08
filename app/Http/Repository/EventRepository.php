<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    public function findById(int $id)
    {
        return Event::lockForUpdate()->find($id);
    }

    public function decreaseTicket(Event $event, int $qty)
    {
        $event->decrement('jumlah_tiket', $qty);
    }
}
