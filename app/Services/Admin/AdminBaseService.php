<?php

namespace App\Services\Admin;

use App\Models\Admin;
use App\Services\BaseService;
use Illuminate\Support\Facades\Auth;

class AdminBaseService extends BaseService
{
    protected $admin;
    protected $adminId;

    public function __construct()
    {
        $this->admin = $this->getAdmin();
        $this->adminId = $this->admin->id;
    }

    protected function getAdmin():Admin
    {
        return Auth::guard('admin')->customer()??Auth::customer();
    }

}
