<?php

namespace App\Controllers;

class StudentController extends Controller
{
    public function dashboard(): void
    {
        echo 'Dashboard étudiant';
    }

    public function wishlist(): void
    {
        echo 'Wishlist étudiant';
    }

    public function applications(): void
    {
        echo 'Candidatures étudiant';
    }
}