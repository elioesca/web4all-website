<?php

namespace App\Controllers;

use App\Models\ApplicationModel;
use App\Models\OfferModel;

/**
 * ApplicationController
 * 
 * Contrôleur gérant les candidatures des étudiants pour les offres de stage.
 * Permet de créer une candidature, uploader des fichiers et vérifier l'état.
 */
class ApplicationController extends Controller
{
    /**
     * create()
     *
     * Affiche le formulaire de candidature pour une offre donnée.
     * Vérifie la connexion, le rôle étudiant et l'existence de l'offre.
     */
    public function create(): void
    {
        // Vérifie la connexion
        $this->requireLogin();

        // Seuls les étudiants peuvent postuler
        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        // Récupère et valide l'ID de l'offre
        $offerId = (int) ($_GET['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        // Crée les instances des modèles
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

    /**
     * store()
     *
     * Traite la soumission du formulaire de candidature d'un étudiant.
     * Vérifie la connexion, l'offre, la candidature précédente, puis gère l'upload des fichiers PDF.
     */
    public function store(): void
    {
        // Vérifie la connexion
        $this->requireLogin();

        // Seuls les étudiants peuvent soumettre une candidature
        if ($_SESSION['user']['role'] !== 'student') {
            $this->redirect('/dashboard');
        }

        // Récupère et valide l'ID de l'offre
        $offerId = (int) ($_POST['offer_id'] ?? 0);

        if ($offerId <= 0) {
            $this->redirect('/offers');
        }

        // Charge les modèles nécessaires
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

    /**
     * handlePdfUpload()
     *
     * Valide et enregistre un fichier PDF dans un sous-dossier uploads.
     * Retourne le chemin du fichier enregistré ou false si échec.
     *
     * @param array $file Fichier reçu via $_FILES (cv ou lettre)
     * @param string $folder Dossier cible (cv ou letters)
     * @return string|false Chemin relatif du fichier ou false
     */
    private function handlePdfUpload(array $file, string $folder): string|false
    {
        // Vérifie le code d'erreur initial du transfert
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }

        // Limite de taille 5 Mo
        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return false;
        }

        // Vérifie l'extension de fichier
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($extension !== 'pdf') {
            return false;
        }

        // Vérifie le type MIME réel du fichier
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if ($mimeType !== 'application/pdf') {
            return false;
        }

        // Prépare le dossier de destination (uploads/cv ou uploads/letters)
        $targetDir = __DIR__ . '/../../public/uploads/' . $folder;

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Génère un nom de fichier unique
        $fileName = uniqid($folder . '_', true) . '.pdf';
        $targetPath = $targetDir . '/' . $fileName;

        // Déplace le fichier téléchargé vers le dossier cible
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return false;
        }

        // Retourne le chemin relatif utilisable dans l'application
        return 'uploads/' . $folder . '/' . $fileName;
    }
}