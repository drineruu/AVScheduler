<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class BrotherController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Brothers/Index');
    }
}
