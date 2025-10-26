<?php

namespace App\Controller;

use App\Entity\Game;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/games')]
class GameController extends AbstractController
{
    #[Route('', name: 'api_games_list', methods: ['GET'])]
    public function list(Request $request, GameRepository $repo): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $limit = min(100, max(1, (int) $request->query->get('limit', 20)));
        $offset = max(0, (int) $request->query->get('offset', 0));

        $qb = $repo->createQueryBuilder('g')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->orderBy('g.createdAt', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(g.title) LIKE :q')->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $items = $qb->getQuery()->getArrayResult();

        return $this->json([
            'items' => $items,
            'limit' => $limit,
            'offset' => $offset,
            'query' => $q,
        ]);
    }

    #[Route('', name: 'api_games_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($data, new Assert\Collection([
            'title'        => [new Assert\NotBlank(), new Assert\Length(max: 255)],
            'platform'     => [new Assert\Optional([new Assert\Length(max: 100)])],
            'releaseYear'  => [new Assert\Optional([new Assert\Range(min: 1970, max: 2100)])],
            'genre'        => [new Assert\Optional([new Assert\Length(max: 100)])],
            'developer'    => [new Assert\Optional([new Assert\Length(max: 150)])],
            'coverUrl'     => [new Assert\Optional([new Assert\Url(), new Assert\Length(max: 500)])],
        ]));

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) { $errors[] = $v->getPropertyPath().': '.$v->getMessage(); }
            return $this->json(['errors' => $errors], 400);
        }

        $game = (new Game())
            ->setTitle($data['title'])
            ->setPlatform($data['platform'] ?? null)
            ->setReleaseYear($data['releaseYear'] ?? null)
            ->setGenre($data['genre'] ?? null)
            ->setDeveloper($data['developer'] ?? null)
            ->setCoverUrl($data['coverUrl'] ?? null);

        $em->persist($game);
        $em->flush();

        return $this->json($this->serializeGame($game), 201);
    }

    #[Route('/{id}', name: 'api_games_get', methods: ['GET'])]
    public function getOne(int $id, GameRepository $repo): JsonResponse
    {
        $game = $repo->find($id);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }
        return $this->json($this->serializeGame($game));
    }

    #[Route('/{id}', name: 'api_games_update', methods: ['PUT', 'PATCH'])]
    public function update(
        int $id,
        Request $request,
        GameRepository $repo,
        EntityManagerInterface $em,
    ): JsonResponse {
        $game = $repo->find($id);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }
        $data = json_decode($request->getContent(), true) ?? [];

        if (array_key_exists('title', $data))        { $game->setTitle((string)$data['title']); }
        if (array_key_exists('platform', $data))     { $game->setPlatform($data['platform'] ?? null); }
        if (array_key_exists('releaseYear', $data))  { $game->setReleaseYear($data['releaseYear'] ?? null); }
        if (array_key_exists('genre', $data))        { $game->setGenre($data['genre'] ?? null); }
        if (array_key_exists('developer', $data))    { $game->setDeveloper($data['developer'] ?? null); }
        if (array_key_exists('coverUrl', $data))     { $game->setCoverUrl($data['coverUrl'] ?? null); }

        $game->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        return $this->json($this->serializeGame($game));
    }

    #[Route('/{id}', name: 'api_games_delete', methods: ['DELETE'])]
    public function delete(int $id, GameRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $game = $repo->find($id);
        if (!$game) {
            return $this->json(['error' => 'Game not found'], 404);
        }
        $em->remove($game);
        $em->flush();

        return $this->json(['deleted' => true]);
    }

    private function serializeGame(Game $g): array
    {
        return [
            'id' => $g->getId(),
            'title' => $g->getTitle(),
            'platform' => $g->getPlatform(),
            'releaseYear' => $g->getReleaseYear(),
            'genre' => $g->getGenre(),
            'developer' => $g->getDeveloper(),
            'coverUrl' => $g->getCoverUrl(),
            'createdAt' => $g->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $g->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
