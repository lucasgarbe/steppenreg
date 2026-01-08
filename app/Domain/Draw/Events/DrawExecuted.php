<?php

namespace App\Domain\Draw\Events;

use App\Domain\Draw\Models\Draw;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DrawExecuted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Draw $draw
    ) {}
}
