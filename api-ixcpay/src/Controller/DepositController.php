<?php

namespace App\Controller;

use App\Service\DepositService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class DepositController extends AbstractController {

    #[Route('/api/deposit', name: 'deposit', methods: ['POST'])]
    public function deposit(Request $request, DepositService $depositService): JsonResponse{

        $data = json_decode($request->getContent(), true);

        $accountId = $data['accountId'] ?? null;
        $amount = $data['amount'] ?? null;

        if (!$accountId || !$amount) {
            return new JsonResponse([
                'Error' => 'Parâmetros inválidos!'
            ], 400);
        }

        try {
            $transaction = $depositService->deposit((int)$accountId, (float)$amount);

            return new JsonResponse([
                'message' => 'Depósito realizado com sucesso!',
                'transaction' => [
                    'id' => $transaction->getId(),
                    'account' => $transaction->getToUser()->getDocument(),
                    'amount' => $transaction->getAmount(),
                    'createdAt' => $transaction->getCreatedAt()->format('Y-m-d H:i:s')
                ]
            ], 201);
        } catch (\Exception $exception) {
            return new JsonResponse([
                'Error' => $exception->getMessage()
            ], 400);
        }
    }
}
