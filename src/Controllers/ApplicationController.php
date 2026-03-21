<?php

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Models\OfferModel;

class ApplicationController extends Controller
{
    public function create(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        $offerId = (int) ($_GET['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();
        $applicationModel = new ApplicationModel();

        $offer = $offerModel->findById($offerId);

        if (!$offer || !(bool) $offer['is_valid']) {
            $this->redirect('/offers');
        }

        $studentUserId = (int) $_SESSION['user']['id'];

        if ($applicationModel->hasAlreadyApplied($studentUserId, $offerId)) {
            $this->render('application/form.html.twig', [
                'pageTitle' => 'POSTULER À UNE OFFRE',
                'offer' => $offer,
                'error' => 'Vous avez déjà postulé à cette offre.',
                'alreadyApplied' => true
            ]);
            return;
        }

        $this->render('application/form.html.twig', [
            'pageTitle' => 'POSTULER À UNE OFFRE',
            'offer' => $offer,
            'alreadyApplied' => false
        ]);
    }

    public function store(): void
    {
        $this->requireLogin();

        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        $offerId = (int) ($_POST['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        $offerModel = new OfferModel();
        $applicationModel = new ApplicationModel();

        $offer = $offerModel->findById($offerId);

        if (!$offer || !(bool) $offer['is_valid']) {
            $this->redirect('/offers');
        }

        $studentUserId = (int) $_SESSION['user']['id'];

        if ($applicationModel->hasAlreadyApplied($studentUserId, $offerId)) {
            $this->render('application/form.html.twig', [
                'pageTitle' => 'POSTULER À UNE OFFRE',
                'offer' => $offer,
                'error' => 'Vous avez déjà postulé à cette offre.',
                'alreadyApplied' => true
            ]);
            return;
        }

        if (
            !isset($_FILES['cv']) || !isset($_FILES['cover_letter']) ||
            $_FILES['cv']['error'] !== UPLOAD_ERR_OK ||
            $_FILES['cover_letter']['error'] !== UPLOAD_ERR_OK
        ) {
            $this->render('application/form.html.twig', [
                'pageTitle' => 'POSTULER À UNE OFFRE',
                'offer' => $offer,
                'error' => 'Veuillez envoyer un CV et une lettre de motivation au format PDF.',
                'alreadyApplied' => false
            ]);
            return;
        }

        $cvPath = $this->handlePdfUpload($_FILES['cv'], 'cv');
        $coverLetterPath = $this->handlePdfUpload($_FILES['cover_letter'], 'letters');

        if ($cvPath === false || $coverLetterPath === false) {
            $this->render('application/form.html.twig', [
                'pageTitle' => 'POSTULER À UNE OFFRE',
                'offer' => $offer,
                'error' => 'Les fichiers doivent être des PDF valides.',
                'alreadyApplied' => false
            ]);
            return;
        }

        $created = $applicationModel->createApplication(
            $studentUserId,
            $offerId,
            $cvPath,
            $coverLetterPath
        );

        if (!$created) {
            if (file_exists(__DIR__ . '/../../public/' . $cvPath)) {
                unlink(__DIR__ . '/../../public/' . $cvPath);
            }

            if (file_exists(__DIR__ . '/../../public/' . $coverLetterPath)) {
                unlink(__DIR__ . '/../../public/' . $coverLetterPath);
            }

            $this->render('application/form.html.twig', [
                'pageTitle' => 'POSTULER À UNE OFFRE',
                'offer' => $offer,
                'error' => 'Une erreur est survenue lors de la candidature.',
                'alreadyApplied' => false
            ]);
            return;
        }

        $this->redirect('/applications');
    }

    private function handlePdfUpload(array $file, string $folder): string|false
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return false;
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return false;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return false;
        }

        $targetDir = __DIR__ . '/../../public/uploads/' . $folder;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        $fileName = uniqid($folder . '_', true) . '.pdf';
        $targetPath = $targetDir . '/' . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        return 'uploads/' . $folder . '/' . $fileName;
    }
}