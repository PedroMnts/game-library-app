<?php

namespace App\Controller;

use App\Entity\Library;
use App\Entity\User;
use App\Repository\LibraryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[Route('/api/libraries')]
class LibraryController extends AbstractController
{
    #[Route('', name: 'api_libraries_list', methods: ['GET'])]
    public function list(LibraryRepository $repo): JsonResponse
    {
        /** @var User $me */
        $me = $this->getUser();
        $items = $repo->createQueryBuilder('l')
            ->andWhere('l.owner = :me')->setParameter('me', $me)
            ->orderBy('l.createdAt', 'DESC')
            ->getQuery()->getArrayResult();

        return $this->json(['items' => $items]);
    }

    #[Route('', name: 'api_libraries_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?? [];

        $violations = $validator->validate($data, new Assert\Collection([
            'name' => [new Assert\NotBlank(), new Assert\Length(max: 120)],
        ]));
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) { $errors[] = $v->getPropertyPath().': '.$v->getMessage(); }
            return $this->json(['errors' => $errors], 400);
        }

        /** @var User $me */
        $me = $this->getUser();

        // Unicidad (owner,name)
        $exists = $em->getRepository(Library::class)->findOneBy([
            'owner' => $me, 'name' => $data['name']
        ]);
        if ($exists) {
            return $this->json(['error' => 'You already have a library with this name'], 409);
        }

        $lib = (new Library())
            ->setName($data['name'])
            ->setOwner($me);

        $em->persist($lib);
        $em->flush();

        return $this->json($this->serializeLibrary($lib), 201);
    }

    #[Route('/{id}', name: 'api_libraries_get', methods: ['GET'])]
    public function getOne(int $id, LibraryRepository $repo): JsonResponse
    {
        $lib = $repo->find($id);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);
        return $this->json($this->serializeLibrary($lib));
    }

    #[Route('/{id}', name: 'api_libraries_update', methods: ['PUT', 'PATCH'])]
    public function update(int $id, Request $request, LibraryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $lib = $repo->find($id);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $data = json_decode($request->getContent(), true) ?? [];
        if (array_key_exists('name', $data)) {
            $lib->setName((string) $data['name']);
            $lib->setUpdatedAt(new \DateTimeImmutable());
        }
        $em->flush();
        return $this->json($this->serializeLibrary($lib));
    }

    #[Route('/{id}', name: 'api_libraries_delete', methods: ['DELETE'])]
    public function delete(int $id, LibraryRepository $repo, EntityManagerInterface $em): JsonResponse
    {
        $lib = $repo->find($id);
        if (!$lib) return $this->json(['error' => 'Library not found'], 404);
        $this->assertOwner($lib);

        $em->remove($lib);
        $em->flush();
        return $this->json(['deleted' => true]);
    }

    private function assertOwner(Library $l): void
    {
        /** @var User $me */
        $me = $this->getUser();
        if ($l->getOwner()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException('Not your library');
        }
    }

    private function serializeLibrary(Library $l): array
    {
        return [
            'id' => $l->getId(),
            'name' => $l->getName(),
            'ownerId' => $l->getOwner()->getId(),
            'createdAt' => $l->getCreatedAt()->format(DATE_ATOM),
            'updatedAt' => $l->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }
}
