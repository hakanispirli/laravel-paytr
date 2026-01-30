<?php

namespace Hakanispirli\Paytr\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaytrPaymentSuccess
{
    use Dispatchable, SerializesModels;

    public $merchantOid;
    public $amount;
    public $data;

    public function __construct($merchantOid, $amount, $data)
    {
        $this->merchantOid = $merchantOid;
        $this->amount = $amount;
        $this->data = $data;
    }
}
