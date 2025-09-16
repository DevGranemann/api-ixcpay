<?php

namespace App\Controller;

use App\Service\DepositService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use OpenApi\Attributes as OA;

class DepositController extends AbstractController {

    #[Route('/api/deposit', name: 'deposit', methods: ['POST'])]
    #[OA\Tag(name: 'Deposits')]
    #[OA\Post(
        summary: 'Realiza um depósito em uma conta',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: 'application/json',
                schema: new OA\Schema(
                    type: 'object',
                    required: ['accountId','amount'],
                    properties: [
                        new OA\Property(property: 'accountId', type: 'integer', example: 1),
                        new OA\Property(property: 'amount', type: 'number', format: 'float', example: 100)
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Depósito realizado com sucesso'),
            new OA\Response(response: 400, description: 'Parâmetros inválidos ou erro de negócio')
        ]
    )]
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
