<?php

namespace App\Services\Frontend;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use App\Services\Frontend\CustomerBaseService;


class CustomerBaseService extends CustomerBaseService
{
    protected $user;
    protected $customerId;
   
    public function __construct()
    {
        $this->user = $this->getUser();
        $this->customerId = $this->customer->id;
    }

    protected function getUser():Customer
    {
        return Auth::guard('customer')->customer() ?? Auth::customer();
    }
   
}
