<?php

namespace App\Controller;

use App\Entity\Transactions;
use App\Repository\UserAccountsRepository;
use App\Service\TransferService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\TransactionsRepository;

class TransferController extends AbstractController {

    private TransferService $transferService;
    private UserAccountsRepository $userAccountsRepository;
    private TransactionsRepository $transactionsRepository;

    public function __construct(
        TransferService $transferService,
        UserAccountsRepository $userAccountsRepository,
        TransactionsRepository $transactionsRepository) {

        $this->transactionsRepository = $transactionsRepository;
        $this->transferService = $transferService;
        $this->userAccountsRepository = $userAccountsRepository;
    }

    #[Route('api/transfers', name: 'make_transfer', methods: 'POST')]
    public function transfer(Request $request, EntityManagerInterface $em): JsonResponse{

        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['from_document'], $data['to_document'], $data['amount'])) {
            return new JsonResponse([
                'error' => 'Parâmetros obrigatórios: from_document, to_document, amount'
            ], 400);
        }

        $fromDocument = $data['from_document'];
        $toDocument = $data['to_document'];
        $amount = (float) $data['amount'];

        try {
            $fromUser = $this->userAccountsRepository->findByDocument($fromDocument);

            if (!$fromUser) {
                return new JsonResponse([
                    'error' => 'Usuário remetente não encontrado'
                ], 404);
            }

            $this->transferService->transfer($fromUser, $toDocument, $amount);

            return new JsonResponse([
                'message' => 'Transferência realizada com sucesso',
                'from' => $fromUser->getDocument(),
                'to' => $toDocument,
                'amount' => $amount,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ], 200);

        } catch (\Exception $e) {
            return new JsonResponse([
                'error' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/api/transfers/{document}', name: 'user_transactions', methods: ['GET'])]
    public function listUserTransactions(string $document, Request $request): JsonResponse
    {
        // Pega paginação da query string (default: page=1, limit=10)
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(50, (int) $request->query->get('limit', 10)); // limite de segurança

        $transactions = $this->transactionsRepository->findTransactionsByUser($document, $page, $limit);
        $total = $this->transactionsRepository->countTransactionsByUser($document);

        $results = array_map(function ($t) use ($document) {
            return [
                'id' => $t->getId(),
                'from' => $t->getFromUser()->getDocument(),
                'to' => $t->getToUser()->getDocument(),
                'amount' => $t->getAmount(),
                'createdAt' => $t->getCreatedAt()->format('Y-m-d H:i:s'),
                'direction' => $t->getFromUser()->getDocument() === $document ? 'outgoing' : 'incoming'
            ];
        }, $transactions);

        return new JsonResponse([
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'transactions' => $results
        ], 200);
    }
}

