<?php

namespace Hakanispirli\Paytr\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaytrPaymentFailed
{
    use Dispatchable, SerializesModels;

    public $merchantOid;
    public $reason;
    public $data;

    public function __construct($merchantOid, $reason, $data)
    {
        $this->merchantOid = $merchantOid;
        $this->reason = $reason;
        $this->data = $data;
    }
}
