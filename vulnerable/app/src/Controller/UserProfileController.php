<?php

namespace App\Controller;

use App\Repository\AtelierRepository;
use App\Repository\InscriptionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ImageType;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserProfileController extends AbstractController
{
    #[Route('/student/user-profile/{id}', name: 'user-profile')]

    public function index(
        InscriptionRepository $inscriptionRepository,
        $id,
        Request $request,
        #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory,
        EntityManagerInterface $em
    ): Response {
        $inscription = $inscriptionRepository->find($id);

        if (!$inscription) {
            throw $this->createNotFoundException("L'inscription n'a pas été trouvé.");
        }

        // Vérification d'autorisation : IDOR fix
        // L'utilisateur ne peut accéder qu'à son propre profil, sauf s'il est admin
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException('Vous devez être connecté pour accéder à cette page.');
        }

        // Les admins peuvent accéder à tous les profils
        if (!in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            // Vérifier que l'inscription appartient à l'utilisateur connecté
            $userInscription = $currentUser->getInscription();
            if (!$userInscription || $userInscription->getId() !== $inscription->getId()) {
                throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à accéder à ce profil.');
            }
        }

        $form = $this->createForm(ImageType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                // Validation du type MIME réel (pas seulement l'extension)
                $allowedMimeTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'image/webp'
                ];

                $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

                // Vérifier le MIME type réel du fichier
                $mimeType = $imageFile->getMimeType();
                $originalExtension = strtolower($imageFile->getClientOriginalExtension());

                // Validation stricte : MIME type ET extension doivent être valides
                if (
                    !in_array($mimeType, $allowedMimeTypes, true) ||
                    !in_array($originalExtension, $allowedExtensions, true)
                ) {
                    $this->addFlash('error', 'Seuls les fichiers image (JPG, PNG, WEBP) sont autorisés.');
                    return $this->redirectToRoute('user-profile', ['id' => $inscription->getId()]);
                }

                // Vérifier la taille (max 5MB)
                $maxSize = 5 * 1024 * 1024; // 5MB
                if ($imageFile->getSize() > $maxSize) {
                    $this->addFlash('error', 'Le fichier est trop volumineux. Taille maximum : 5MB.');
                    return $this->redirectToRoute('user-profile', ['id' => $inscription->getId()]);
                }

                // Générer un nom unique et non prédictible (UUID v4)
                $newFilename = bin2hex(random_bytes(16)) . '.' . $originalExtension;

                // Déterminer l'extension finale basée sur le MIME type réel pour plus de sécurité
                $finalExtension = match ($mimeType) {
                    'image/jpeg', 'image/jpg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => $originalExtension
                };

                // Si l'extension ne correspond pas au MIME, utiliser celle du MIME
                if ($finalExtension !== $originalExtension) {
                    $newFilename = preg_replace('/\.[^.]+$/', '.' . $finalExtension, $newFilename);
                }

                try {
                    $imageFile->move($imagesDirectory, $newFilename);

                    // Supprimer l'ancien fichier s'il existe
                    $oldFilename = $this->getUser()->getImageFilename();
                    if ($oldFilename && file_exists($imagesDirectory . '/' . $oldFilename)) {
                        unlink($imagesDirectory . '/' . $oldFilename);
                    }

                    $this->getUser()->setImageFilename($newFilename);
                    $em->flush();

                    $this->addFlash('success', 'Image uploadée avec succès.');
                } catch (FileException $e) {
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload du fichier.');
                }
            }

            return $this->redirectToRoute('user-profile', ['id' => $inscription->getId()]);
        }

        return $this->render('user-profile/index.html.twig', [
            'inscription' => $inscription,
            'form' => $form,
        ]);
    }
}
