<?php

namespace App\Controllers;

class LegalController extends Controller
{
    public function index(): void
    {
        $this->render('mentions-legales.html.twig');
    }
}