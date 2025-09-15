<?php

namespace App\Controller;

use App\Service\TakeOutAmountService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class TakeOutAmountController extends AbstractController {

    #[Route('/api/takeoutvalue', name: 'take_out_value', methods: ['POST'])]
    public function takeOutValue(Request $request, TakeOutAmountService $takeOutAmount): JsonResponse{

        $data = json_decode($request->getContent(), true);

        $accountId = $data['accountId'] ?? null;
        $amount = $data['amount'] ?? null;

        if (!$accountId|| !$amount) {
            return new JsonResponse([
                'Error' => 'Parâmetros inválidos'
            ], 400);
        }

        try {
            $transaction = $takeOutAmount->takeOutValue((int)$accountId, (float)$amount);

            return new JsonResponse([
                'message' => 'Saque realizado com sucesso',
                'transaction' => [
                    'id' => $transaction->getId(),
                    'account' => $transaction->getFromUser()->getDocument(),
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
