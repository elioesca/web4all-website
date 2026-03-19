<?php

namespace App\Controllers;

use App\Models\Paginator;
use App\Models\StudentModel;
use App\Models\UserModel;

class StudentController extends Controller
{
    public function index(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $search = trim($_GET['search'] ?? '');
        $studentModel = new StudentModel();

        $totalStudents = $studentModel->countStudents($search);
        $paginator = new Paginator($totalStudents, 8);

        $students = $studentModel->getStudents(
            $search,
            $paginator->getPerPage(),
            $paginator->getOffset()
        );

        if ($_SESSION['user']['role'] === 'pilot') {
            $pilotUserId = (int) $_SESSION['user']['id'];
            $pilotStudentIds = $studentModel->getPilotStudentIds($pilotUserId);

            foreach ($students as &$student) {
                $student['can_view_applications'] = in_array((int) $student['user_id'], $pilotStudentIds, true);
            }
            unset($student);
        } else {
            foreach ($students as &$student) {
                $student['can_view_applications'] = false;
            }
            unset($student);
        }

        $this->render('student/index.html.twig', [
            'pageTitle' => 'GESTION DES ETUDIANTS',
            'students' => $students,
            'search' => $search,
            'currentPage' => $paginator->getCurrentPage(),
            'totalPages' => $paginator->getTotalPages(),
            'basePath' => '/students'
        ]);
    }

    public function create(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $studentModel = new StudentModel();

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

    public function store(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        $searchStatusId = (int) ($_POST['search_status_id'] ?? 0);

        $studentModel = new StudentModel();
        $userModel = new UserModel();

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

        $created = $studentModel->createStudent(
            $lastName,
            $firstName,
            $email,
            $phoneNumber,
            $password,
            $promotionId,
            $searchStatusId
        );

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

    public function edit(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_GET['id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/students');
        }

        $studentModel = new StudentModel();
        $student = $studentModel->findStudentById($userId);

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

    public function update(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);
        $lastName = trim($_POST['last_name'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phoneNumber = trim($_POST['phone_number'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $promotionId = (int) ($_POST['promotion_id'] ?? 0);
        $searchStatusId = (int) ($_POST['search_status_id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/students');
        }

        $studentModel = new StudentModel();
        $userModel = new UserModel();

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

    public function deactivate(): void
    {
        $this->requireLogin();

        if (!in_array($_SESSION['user']['role'], ['admin', 'pilot'])) {
            $this->redirect('/dashboard');
        }

        $userId = (int) ($_POST['user_id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/students');
        }

        $studentModel = new StudentModel();
        $studentModel->deactivateStudent($userId);

        $this->redirect('/students');
    }

    public function pilotStudentApplications(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'pilot') {
            $this->redirect('/dashboard');
        }

        $studentUserId = (int) ($_GET['id'] ?? 0);
        $pilotUserId = (int) $_SESSION['user']['id'];

        if ($studentUserId <= 0) {
            $this->redirect('/students');
        }

        $studentModel = new StudentModel();

        if (!$studentModel->pilotCanAccessStudent($pilotUserId, $studentUserId)) {
            $this->redirect('/students');
        }

        $totalApplications = $studentModel->countApplicationsForPilotStudent($pilotUserId, $studentUserId);
        $paginator = new Paginator($totalApplications, 8);

        $student = $studentModel->findStudentById($studentUserId);

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
}