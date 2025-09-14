<?php

namespace App\Controller;

use App\Entity\UserAccounts;
use App\Repository\UserAccountsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;

class UserAccountController extends AbstractController {

    #[Route('api/useraccounts', name: '', methods:'POST')]
    public function createUserAccount(
        Request $request,
        EntityManagerInterface $em): JsonResponse{

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse([
                'Error' => 'JSON inválido'
            ], 400);
        }

        if (!isset($data['user_type'], $data['full_name'], $data['document'], $data['email'], $data['password'], )) {
            return new JsonResponse([
                'Error' => 'Campos obrigatórios faltando'
            ], 400);
        }

        $user = new UserAccounts();
        $user->setUserType($data['user_type']);
        $user->setFullName($data['full_name']);
        $user->setDocument($data['document']);
        $user->setEmail($data['email']);
        $user->setBalance(0.0);
        $user->setPassword($data['password']); // implementar hash para senha depois, se der tempo

        $existingUserByDoc = $em->getRepository(UserAccounts::class)
            ->findOneBy([
                'document' => $data['document']
            ]);

        if ($existingUserByDoc) {
            return new JsonResponse([
                'Error' => 'Já existe um usuário com este CPF/CNPJ!'
            ], 400);
        }

        $existingUserByEmail = $em->getRepository(UserAccounts::class)
            ->findOneBy([
                'email' => $data['email']
            ]);

        if ($existingUserByEmail) {
            return new JsonResponse([
                'Error' => 'Já existe um usuário com este e-mail!'
            ], 400);
        }

        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'Conta criada com sucesso!',
            'id' => $user->getId(),
            'document' => $user->getDocument(),
            'email' => $user->getEmail()
        ], 201);
    }
}
