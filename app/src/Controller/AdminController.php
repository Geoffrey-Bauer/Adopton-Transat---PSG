<?php

namespace App\Controller;

use App\Document\Reservation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;

class AdminController extends AbstractController
{
  private $logger;

  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  #[Route('/admin/login', name: 'admin_login')]
  public function login(AuthenticationUtils $authenticationUtils): Response
  {
    $error = $authenticationUtils->getLastAuthenticationError();
    $lastUsername = $authenticationUtils->getLastUsername();

    return $this->render('admin/login.html.twig', [
      'last_username' => $lastUsername,
      'error' => $error,
    ]);
  }

  #[Route('/admin/dashboard', name: 'admin_dashboard')]
  public function dashboard(DocumentManager $dm): Response
  {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $reservations = $dm->getRepository(Reservation::class)->findAll();

    $this->logger->info('Nombre de réservations trouvées: ' . count($reservations));

    return $this->render('admin/dashboard.html.twig', [
      'reservations' => $reservations,
    ]);
  }

  #[Route('/admin/cancel-reservation/{id}', name: 'admin_cancel_reservation', methods: ['POST'])]
  public function cancelReservation(string $id, DocumentManager $dm): JsonResponse
  {
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $reservation = $dm->getRepository(Reservation::class)->find($id);

    if (!$reservation) {
      return new JsonResponse(['success' => false, 'message' => 'Réservation non trouvée'], 404);
    }

    try {
      $dm->remove($reservation);
      $dm->flush();
      return new JsonResponse(['success' => true, 'message' => 'Réservation annulée avec succès']);
    } catch (\Exception $e) {
      $this->logger->error('Erreur lors de l\'annulation de la réservation: ' . $e->getMessage());
      return new JsonResponse(['success' => false, 'message' => 'Erreur lors de l\'annulation de la réservation'], 500);
    }
  }

  #[Route('/admin/logout', name: 'admin_logout', methods: ['GET'])]
  public function logout(): void
  {
    throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
  }
}