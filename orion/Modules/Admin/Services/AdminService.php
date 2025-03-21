<?php

namespace Orion\Modules\Admin\Services;

use Orion\Modules\Admin\Repositories\AdminRepository;

readonly class AdminService
{

    public function __construct(private AdminRepository $adminRepository)
    {
    }

    // Add service logic here
}