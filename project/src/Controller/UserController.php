<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\ClientRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route(
        path: '/api/users',
        name: 'api_users',
        methods: ['GET']
    )]
    public function getAllUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
    {
        $userList = $userRepository->findBy(['client' => $this->getUser()]);
        $jsonUserList = $serializer->serialize($userList, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    #[Route(
        path: '/api/users',
        name: 'api_create_user',
        methods: ['POST']
    )]
    public function createUser(
        Request $request,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        UrlGeneratorInterface $urlGenerator,
        ClientRepository $clientRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        //On vérifie les erreurs
        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }
        $user->setClient($clientRepository->find($this->getUser()));

        $manager->persist($user);
        $manager->flush();

        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);

        $location = $urlGenerator->generate('api_detailUser', ['id' => $user->getId()], UrlGeneratorInterface::ABSOLUTE_URL);

        return new JsonResponse($jsonUser, Response::HTTP_CREATED, ["Location" => $location], true);
    }

    #[Route(
        path: '/api/users/{id}',
        name: 'api_detailUser',
        methods: ['GET']
    )]
    public function getDetailUser(User $user, SerializerInterface $serializer): JsonResponse
    {
        $jsonUser = $serializer->serialize($user, 'json', ['groups' => 'getUsers']);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    #[Route(
        path: '/api/users/{id}',
        name: 'api_updateUser',
        methods: ['PUT']
    )]
    public function updateUser(
        Request $request,
        User $currentUser,
        SerializerInterface $serializer,
        EntityManagerInterface $manager,
        ClientRepository $clientRepository,
        ValidatorInterface $validator
    ): JsonResponse
    {
        if ($currentUser->getClient() === $this->getUser()) {
            $updatedUser = $serializer->deserialize($request->getContent(), User::class, 'json', [AbstractNormalizer::OBJECT_TO_POPULATE => $currentUser]);

            $content = $request->toArray();
            $idClient = $content['idClient'] ?? $this->getUser();

            $updatedUser->setClient($clientRepository->find($idClient));
            //On vérifie les erreurs
            $errors = $validator->validate($updatedUser);
            if ($errors->count() > 0) {
                return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
            }
            $manager->persist($updatedUser);
            $manager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);

        } else {
            //renvoyer une erreur car l'utilisateur n'appartient pas au client ou n'existe pas
            $message = "Cet utilisateur n'existe pas.";
            return new JsonResponse($message, Response::HTTP_NON_AUTHORITATIVE_INFORMATION);

        }
    }

    #[Route(
        path: '/api/users/{id}',
        name: 'api_deleteUser',
        methods: ['DELETE']
    )]
    public function deleteUser(
        User $user,
        EntityManagerInterface $manager
    ): JsonResponse
    {
        if ($user->getClient() === $this->getUser()) {
            $manager->remove($user);
            $manager->flush();
            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        } else {
            //renvoyer une erreur car l'utilisateur n'appartient pas au client ou n'existe pas
            $message = "Cet utilisateur n'existe pas.";
            return new JsonResponse($message, Response::HTTP_NON_AUTHORITATIVE_INFORMATION);

        }
    }

}
