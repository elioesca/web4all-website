<?php

namespace App\Controllers;

use App\Models\Paginator;
use App\Models\StudentModel;
use App\Models\UserModel;

/**
 * StudentController
 * 
 * Contrôleur gérant l'affichage, la création, la modification et la suppression des étudiants.
 * Permet également aux pilots et administrateurs de gérer les profils étudiants.
 */
class StudentController extends Controller
{
    /**
     * index()
     * 
     * Affiche la liste paginée de tous les étudiants avec une fonction de recherche.
     * Accessible uniquement aux administrateurs et aux pilots.
     * 
     * Pour les pilots: affiche un indicateur pour voir si l'étudiant est suivi par le pilot
     * Pour les administrateurs: affiche tous les étudiants sans restriction
     */
    public function index(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        // Sinon, le redirige vers le tableau de bord
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère le paramètre de recherche depuis l'URL
        $search = trim($_GET['search'] ?? '');
        $studentModel = new StudentModel();

        // Compte le nombre total d'étudiants correspondant à la recherche
        $totalStudents = $studentModel->countStudents($search);
        // Crée un objet Paginator pour gérer la pagination (8 étudiants par page)
        $paginator = new Paginator($totalStudents, 8);

        // Récupère les étudiants pour la page actuelle
        $students = $studentModel->getStudents(
            $search,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        // Ajoute un drapeau pour indiquer si le pilot peut voir les applications
        if ($_SESSION['user']['role'] === 'pilot') {
            // Pour les pilots: récupère leurs étudiants assignés
            $pilotUserId = (int) $_SESSION['user']['id'];
            $pilotStudentIds = $studentModel->getPilotStudentIds($pilotUserId);

            // Ajoute le drapeau 'can_view_applications' à chaque étudiant
            // true si l'étudiant est assigné au pilot, false sinon
            foreach ($students as &$student) {
                $student['can_view_applications'] = in_array((int) $student['user_id'], $pilotStudentIds, true);
            }
            unset($student);
        } else {
            // Pour les administrateurs: désactive l'accès aux applications (non utilisé ici)
            foreach ($students as &$student) {
                $student['can_view_applications'] = false;
            }
            unset($student);
        }

        // Affiche la vue avec les données paginées
        $this->render('student/index.html.twig', [
            'pageTitle' => 'GESTION DES ETUDIANTS',
            'students' => $students,
            'search' => $search,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/students'
        ]);
    }

    /**
     * create()
     * 
     * Affiche le formulaire de création d'un nouvel étudiant.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function create(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Crée une instance du modèle StudentModel
        $studentModel = new StudentModel();

        // Affiche le formulaire vierge avec les données nécessaires
        $this->render('student/form.html.twig', [
            'pageTitle' => 'CREATION COMPTE ETUDIANT',
            'formAction' => '/students/create',
            'submitLabel' => 'CREER',
            'student' => null,
            'promotions' => $studentModel->getPromotions(),
            'searchStatuses' => $studentModel->getSearchStatuses(),
            'showDeactivate' => false
        ]);
    }

    /**
     * store()
     * 
     * Traite la soumission du formulaire de création d'un nouvel étudiant.
     * Valide les données, vérifie que l'email est unique, puis crée le compte.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function store(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère les données du formulaire en utilisant trim pour nettoyer les espaces
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        $searchStatusId = (int) ($_POST['search_status_id'] ?? 0);

        // Crée les instances des modèles nécessaires
        $studentModel = new StudentModel();
        $userModel = new UserModel();

        // VALIDATION 1: Vérifie que tous les champs requis sont remplis
        if (
            $lastName === '' || $firstName === '' || $email === '' || $password === ''
            || $promotionId <= 0 || $searchStatusId <= 0
        ) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE ETUDIANT',
                'formAction' => '/students/create',
                'submitLabel' => 'CREER',
                'error' => 'Veuillez remplir tous les champs obligatoires.',
                'student' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => false
            ]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE ETUDIANT',
                'formAction' => '/students/create',
                'submitLabel' => 'CREER',
                'error' => 'Adresse email invalide.',
                'student' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => false
            ]);
            return;
        }

        // VALIDATION 3: Vérifie que cet email n'existe pas déjà dans la base de données
        if ($userModel->findByEmail($email)) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE ETUDIANT',
                'formAction' => '/students/create',
                'submitLabel' => 'CREER',
                'error' => 'Cet email est déjà utilisé.',
                'student' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => false
            ]);
            return;
        }

        // Crée le compte étudiant avec les données validées
        $created = $studentModel->createStudent(
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionId,
            $searchStatusId
        );

        // Si la création a échoué, réaffiche le formulaire avec un message d'erreur
        if (!$created) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'CREATION COMPTE ETUDIANT',
                'formAction' => '/students/create',
                'submitLabel' => 'CREER',
                'error' => 'Une erreur est survenue lors de la création.',
                'student' => [
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => false
            ]);
            return;
        }

        $this->redirect('/students');
    }

    /**
     * edit()
     * 
     * Affiche le formulaire de modification du profil d'un étudiant.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function edit(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant depuis l'URL (paramètre 'id')
        $userId = (int) ($_GET['id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/students');
        }

        // Crée une instance du modèle et récupère les données de l'étudiant
        $studentModel = new StudentModel();
        $student = $studentModel->findStudentById($userId);

        // Si l'étudiant n'existe pas, redirige vers la liste
        if (!$student) {
            $this->redirect('/students');
        }

        $this->render('student/form.html.twig', [
            'pageTitle' => 'MODIFICATION PROFIL DE L’ETUDIANT',
            'formAction' => '/students/edit',
            'submitLabel' => 'MODIFIER',
            'student' => $student,
            'promotions' => $studentModel->getPromotions(),
            'searchStatuses' => $studentModel->getSearchStatuses(),
            'showDeactivate' => true
        ]);
    }

    /**
     * update()
     * 
     * Traite la soumission du formulaire de modification du profil d'un étudiant.
     * Valide les données et met à jour le compte étudiant.
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function update(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère les données du formulaire
        $userId = (int) ($_POST['user_id'] ?? 0);
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        $searchStatusId = (int) ($_POST['search_status_id'] ?? 0);

        // Vérifie que l'ID de l'utilisateur est valide
        if ($userId <= 0) {
            $this->redirect('/students');
        }

        // Crée les instances des modèles nécessaires
        $studentModel = new StudentModel();
        $userModel = new UserModel();

        // VALIDATION 1: Vérifie que tous les champs requis sont remplis
        if (
            $lastName === '' || $firstName === '' || $email === ''
            || $promotionId <= 0 || $searchStatusId <= 0
        ) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'MODIFICATION PROFIL DE L’ETUDIANT',
                'formAction' => '/students/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Veuillez remplir tous les champs obligatoires.',
                'student' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => true
            ]);
            return;
        }

        // VALIDATION 2: Vérifie que l'adresse email est au bon format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'MODIFICATION PROFIL DE L’ETUDIANT',
                'formAction' => '/students/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Adresse email invalide.',
                'student' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => true
            ]);
            return;
        }

        // VALIDATION 3: Vérifie que cet email n'est pas utilisé par un autre utilisateur
        if ($userModel->emailExistsForAnotherUser($email, $userId)) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'MODIFICATION PROFIL DE L’ETUDIANT',
                'formAction' => '/students/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Cet email est déjà utilisé.',
                'student' => [
                    'user_id' => $userId,
                    'last_name' => $lastName,
                    'first_name' => $firstName,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                    'promotion_id' => $promotionId,
                    'search_status_id' => $searchStatusId
                ],
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => true
            ]);
            return;
        }

        // Met à jour le compte étudiant avec les données validées
        $updated = $studentModel->updateStudent(
            $userId,
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionId,
            $searchStatusId
        );

        // Si la mise à jour a échoué, réaffiche le formulaire avec les données actuelles
        if (!$updated) {
            $this->render('student/form.html.twig', [
                'pageTitle' => 'MODIFICATION PROFIL DE L’ETUDIANT',
                'formAction' => '/students/edit',
                'submitLabel' => 'MODIFIER',
                'error' => 'Une erreur est survenue lors de la modification.',
                'student' => $studentModel->findStudentById($userId),
                'promotions' => $studentModel->getPromotions(),
                'searchStatuses' => $studentModel->getSearchStatuses(),
                'showDeactivate' => true
            ]);
            return;
        }

        $this->redirect('/students');
    }

    /**
     * deactivate()
     * 
     * Désactive le compte d'un étudiant (l'étudiant ne peut plus se connecter).
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function deactivate(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant à désactiver
        $userId = (int) ($_POST['user_id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/students');
        }

        // Désactive l'étudiant
        $studentModel = new StudentModel();
        $studentModel->deactivateStudent($userId);

        // Redirige vers la liste des étudiants
        $this->redirect('/students');
    }

    /**
     * pilotStudentApplications()
     * 
     * Affiche les candidatures d'un étudiant assigné au pilot.
     * Accessible uniquement aux pilots.
     */
    public function pilotStudentApplications(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'pilot'
        if ($_SESSION['user']['role'] !== 'pilot') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant et l'ID du pilot (depuis la session)
        $studentUserId = (int) ($_GET['id'] ?? 0);
        $pilotUserId = (int) $_SESSION['user']['id'];

        // Vérifie que l'ID de l'étudiant est valide
        if ($studentUserId <= 0) {
            $this->redirect('/students');
        }

        $studentModel = new StudentModel();

        // Vérifie que le pilot a accès à cet étudiant (l'étudiant lui est assigné)
        if (!$studentModel->pilotCanAccessStudent($pilotUserId, $studentUserId)) {
            $this->redirect('/students');
        }

        // Compte le nombre total de candidatures de cet étudiant
        $totalApplications = $studentModel->countApplicationsForPilotStudent($pilotUserId, $studentUserId);
        // Crée un objet Paginator pour gérer la pagination (8 candidatures par page)
        $paginator = new Paginator($totalApplications, 8);

        // Récupère les informations de l'étudiant
        $student = $studentModel->findStudentById($studentUserId);

        // Récupère les candidatures de l'étudiant pour la page actuelle
        $applications = $studentModel->getApplicationsForPilotStudent(
            $pilotUserId,
            $studentUserId,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        $this->render('student/applications.html.twig', [
            'student' => $student,
            'applications' => $applications,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/pilot/student-applications',
            'studentId' => $studentUserId
        ]);
    }

    /**
     * reactivate()
     * 
     * Réactive le compte d'un étudiant (l'étudiant peut à nouveau se connecter).
     * Accessible uniquement aux administrateurs et pilots.
     */
    public function reactivate(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'admin' ou 'pilot'
        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant à réactiver
        $userId = (int) ($_POST['user_id'] ?? 0);

        // Vérifie que l'ID est valide
        if ($userId <= 0) {
            $this->redirect('/students');
        }

        // Réactive l'étudiant
        $studentModel = new StudentModel();
        $studentModel->reactivateStudent($userId);

        // Redirige vers la page de modification de l'étudiant
        $this->redirect('/students/edit?id=' . $userId);
    }

    /**
     * applications()
     * 
     * Affiche les candidatures personnelles de l'étudiant connecté (ses propres candidatures).
     * Accessible uniquement aux étudiants.
     */
    public function applications(): void
    {
        // Vérifie que l'utilisateur est connecté
        $this->requireLogin();

        // Vérifie que l'utilisateur a le rôle 'student'
        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        // Récupère l'ID de l'étudiant connecté depuis la session
        $studentUserId = (int) $_SESSION['user']['id'];

        $studentModel = new StudentModel();

        // Compte le nombre total de candidatures de cet étudiant
        $totalApplications = $studentModel->countApplicationsForStudent($studentUserId);
        // Crée un objet Paginator pour gérer la pagination (8 candidatures par page)
        $paginator = new Paginator($totalApplications, 8);

        // Récupère les candidatures de l'étudiant pour la page actuelle
        $applications = $studentModel->getApplicationsForStudent(
            $studentUserId,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        // Affiche la vue avec les candidatures paginées
        $this->render('student/my_applications.html.twig', [
            'pageTitle' => 'MES CANDIDATURES',
            'applications' => $applications,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/applications'
        ]);
    }
}