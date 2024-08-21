<?php

namespace App\Controller;

use App\Document\Transat;
use App\Document\Reservation;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class TransatController extends AbstractController
{
  private $logger;

  public function __construct(LoggerInterface $logger)
  {
    $this->logger = $logger;
  }

  #[Route('/', name: 'transat_list')]
  public function index(Request $request, DocumentManager $dm): Response
  {
    $selectedDate = $request->query->get('date', date('Y-m-d'));

    // Vérifier si la date sélectionnée est valide (entre aujourd'hui et un an à partir d'aujourd'hui)
    $today = new \DateTime();
    $oneYearLater = (new \DateTime())->modify('+1 year');
    $selectedDateTime = new \DateTime($selectedDate);

    if ($selectedDateTime < $today || $selectedDateTime > $oneYearLater) {
      $selectedDate = date('Y-m-d'); // Utiliser la date d'aujourd'hui si la date sélectionnée n'est pas valide
    }

    $transats = $dm->getRepository(Transat::class)->findAll();
    $timeSlots = $this->generateTimeSlots();

    return $this->render('transat/index.html.twig', [
      'transats' => $transats,
      'timeSlots' => $timeSlots,
      'selectedDate' => $selectedDate,
    ]);
  }

  #[Route('/transat/{id}/reserve', name: 'transat_reserve', methods: ['POST'])]
  public function reserve(Request $request, string $id, DocumentManager $dm): JsonResponse
  {
    try {
      $transat = $dm->getRepository(Transat::class)->find($id);
      if (!$transat) {
        return new JsonResponse(['success' => false, 'message' => 'Transat non trouvé'], 404);
      }

      $startTime = new \DateTime($request->request->get('startTime'));
      $endTime = (clone $startTime)->modify('+3 hours');
      $password = $request->request->get('password');

      if (!$transat->isAvailable($startTime, $endTime)) {
        return new JsonResponse(['success' => false, 'message' => 'Ce créneau est déjà réservé']);
      }

      $reservation = new Reservation();
      $reservation->setStartTime($startTime);
      $reservation->setEndTime($endTime);
      $reservation->setPassword($password);
      $reservation->setTransat($transat);

      $dm->persist($reservation);
      $dm->flush();

      return new JsonResponse(['success' => true, 'message' => 'Réservation réussie']);
    } catch (\Exception $e) {
      $this->logger->error('Erreur lors de la réservation: ' . $e->getMessage());
      return new JsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de la réservation'], 500);
    }
  }

  #[Route('/transat/{id}/cancel', name: 'transat_cancel', methods: ['POST'])]
  public function cancel(Request $request, string $id, DocumentManager $dm): JsonResponse
  {
    try {
      $transat = $dm->getRepository(Transat::class)->find($id);
      if (!$transat) {
        return new JsonResponse(['success' => false, 'message' => 'Transat non trouvé'], 404);
      }

      $startTime = new \DateTime($request->request->get('startTime'));
      $password = $request->request->get('password');

      $reservation = $dm->getRepository(Reservation::class)->findOneBy([
        'transat' => $transat,
        'startTime' => $startTime
      ]);

      if (!$reservation) {
        return new JsonResponse(['success' => false, 'message' => 'Réservation non trouvée']);
      }

      if (!$reservation->verifyPassword($password)) {
        return new JsonResponse(['success' => false, 'message' => 'Mot de passe incorrect']);
      }

      $dm->remove($reservation);
      $dm->flush();

      return new JsonResponse(['success' => true, 'message' => 'Annulation réussie']);
    } catch (\Exception $e) {
      $this->logger->error('Erreur lors de l\'annulation: ' . $e->getMessage());
      return new JsonResponse(['success' => false, 'message' => 'Une erreur est survenue lors de l\'annulation'], 500);
    }
  }

  private function generateTimeSlots(): array
  {
    $slots = [];
    $start = new \DateTime('today 08:00');
    $end = new \DateTime('today 23:00');
    $interval = new \DateInterval('PT3H');

    $current = clone $start;
    while ($current < $end) {
      $slots[] = $current->format('H:i');
      $current->add($interval);
    }

    return $slots;
  }
}