<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Library;
use App\Entity\LibraryGame;
use App\Enum\PlayStatus;
use App\Repository\LibraryGameRepository;
use App\Repository\LibraryRepository;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/libraries')]
class LibraryItemController extends AbstractController
{
    #[Route('/{libraryId}/items', name: 'api_library_items_list', methods: ['GET'])]
    public function list(int $libraryId, LibraryRepository $libRepo, LibraryGameRepository $lgRepo): JsonResponse
    {
        $lib = $libRepo->find($libraryId);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $items = $lgRepo->createQueryBuilder('lg')
            ->join('lg.game', 'g')->addSelect('g')
            ->andWhere('lg.library = :lib')->setParameter('lib', $lib)
            ->orderBy('lg.addedAt', 'DESC')
            ->getQuery()->getResult();

        return $this->json([
            'items' => array_map(fn(LibraryGame $lg) => $this->serializeItem($lg), $items)
        ]);
    }

    #[Route('/{libraryId}/items', name: 'api_library_items_add', methods: ['POST'])]
    public function add(
        int $libraryId,
        Request $request,
        LibraryRepository $libRepo,
        GameRepository $gameRepo,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $lib = $libRepo->find($libraryId);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($data, new Assert\Collection([
            'gameId'          => [new Assert\NotBlank(), new Assert\Type('integer')],
            'status'          => [new Assert\Optional([new Assert\Choice(array_map(fn($c)=>$c->value, PlayStatus::cases()))])],
            'rating'          => [new Assert\Optional([new Assert\Range(min: 0, max: 100)])],
            'progressPercent' => [new Assert\Optional([new Assert\Range(min: 0, max: 100)])],
            'hoursPlayed'     => [new Assert\Optional([new Assert\PositiveOrZero()])],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) { $errors[] = $v->getPropertyPath().': '.$v->getMessage(); }
            return $this->json(['errors' => $errors], 400);
        }

        $game = $gameRepo->find((int) $data['gameId']);
        if (!$game) return $this->json(['error' => 'Game not found'], 404);

        // Unicidad library+game
        $exists = $em->getRepository(LibraryGame::class)->findOneBy(['library' => $lib, 'game' => $game]);
        if ($exists) {
            return $this->json(['error' => 'Game already added to this library'], 409);
        }

        $lg = (new LibraryGame())
            ->setLibrary($lib)
            ->setGame($game);

        if (!empty($data['status'])) {
            $lg->setStatus(PlayStatus::from($data['status']));
        }
        if (array_key_exists('rating', $data)) $lg->setRating($data['rating']);
        if (array_key_exists('progressPercent', $data)) $lg->setProgressPercent($data['progressPercent']);
        if (array_key_exists('hoursPlayed', $data)) $lg->setHoursPlayed($data['hoursPlayed'] !== null ? (string)$data['hoursPlayed'] : null);

        $em->persist($lg);
        $em->flush();

        return $this->json($this->serializeItem($lg), 201);
    }

    #[Route('/{libraryId}/items/{itemId}', name: 'api_library_items_update', methods: ['PATCH', 'PUT'])]
    public function update(
        int $libraryId,
        int $itemId,
        Request $request,
        LibraryRepository $libRepo,
        LibraryGameRepository $lgRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $lib = $libRepo->find($libraryId);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $lg = $lgRepo->find($itemId);
        if (!$lg || $lg->getLibrary()->getId() !== $lib->getId()) {
            return $this->json(['error' => 'Item not found in this library'], 404);
        }

        $data = json_decode($request->getContent(), true) ?? [];
        if (array_key_exists('status', $data) && $data['status'] !== null) {
            $lg->setStatus(PlayStatus::from($data['status']));
        }
        if (array_key_exists('rating', $data))          $lg->setRating($data['rating']);
        if (array_key_exists('progressPercent', $data)) $lg->setProgressPercent($data['progressPercent']);
        if (array_key_exists('hoursPlayed', $data))     $lg->setHoursPlayed($data['hoursPlayed'] !== null ? (string)$data['hoursPlayed'] : null);

        $em->flush();

        return $this->json($this->serializeItem($lg));
    }

    #[Route('/{libraryId}/items/{itemId}', name: 'api_library_items_delete', methods: ['DELETE'])]
    public function remove(
        int $libraryId,
        int $itemId,
        LibraryRepository $libRepo,
        LibraryGameRepository $lgRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        $lib = $libRepo->find($libraryId);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $lg = $lgRepo->find($itemId);
        if (!$lg || $lg->getLibrary()->getId() !== $lib->getId()) {
            return $this->json(['error' => 'Item not found in this library'], 404);
        }

        $em->remove($lg);
        $em->flush();

        return $this->json(['deleted' => true]);
    }

    private function assertOwner(Library $l): void
    {
        $me = $this->getUser();
        if (!$me || $l->getOwner()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException('Not your library');
        }
    }

    private function serializeItem(LibraryGame $lg): array
    {
        return [
            'id' => $lg->getId(),
            'libraryId' => $lg->getLibrary()->getId(),
            'game' => [
                'id' => $lg->getGame()->getId(),
                'title' => $lg->getGame()->getTitle(),
                'platform' => $lg->getGame()->getPlatform(),
            ],
            'status' => $lg->getStatus()->value,
            'rating' => $lg->getRating(),
            'progressPercent' => $lg->getProgressPercent(),
            'hoursPlayed' => $lg->getHoursPlayed(),
            'addedAt' => $lg->getAddedAt()->format(DATE_ATOM),
        ];
    }
}
