<?php

namespace App\Controller\Admin\Alert;

use App\Controller\Admin\AtCrudController;
use App\Entity\Alert\Alert;

class AlertCrudController extends AtCrudController
{
    public static function getEntityFqcn(): string
    {
        return Alert::class;
    }
}
