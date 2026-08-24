<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class MeetingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Meetings/Index');
    }
}
