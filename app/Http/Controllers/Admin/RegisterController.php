<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Web\UI\AttendanceSheetController;

/**
 * Registers (service attendance sheets) in the admin console: list, create,
 * edit, delete, and marking/unmarking attendance. Same data and rules as the
 * site's front-of-house attendance sheets; only the pages and redirects differ.
 */
class RegisterController extends AttendanceSheetController
{
    protected string $indexPage = 'Admin/Registers/Index';
    protected string $showPage = 'Admin/Registers/Show';
    protected string $indexRoute = 'admin.registers.index';
    protected string $showRoute = 'admin.registers.show';
}
