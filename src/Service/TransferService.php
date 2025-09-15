<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Entity\UserAccounts;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enums\TransactionsType;
use App\Service\Notification\NotificationServiceInterface;
class TransferService {

    private EntityManagerInterface $em;
    private UserAccountsRepository $userAccountsRepository;
    private ExternalValidationService $externalValidation;
    private ReversalService $reversalService;
    private NotificationServiceInterface $notifier;

    public function __construct(EntityManagerInterface $em, UserAccountsRepository $userAccountsRepository, ExternalValidationService $externalValidation, ReversalService $reversalService, NotificationServiceInterface $notifier) {
        $this->em = $em;
        $this->userAccountsRepository = $userAccountsRepository;
        $this->externalValidation = $externalValidation;
        $this->reversalService = $reversalService;
        $this->notifier = $notifier;
    }

    /**
     * @param UserAccounts $fromUserAccounts usuário remetente
     * @param string $toDocument CPF ou CNPJ do destinatário
     * @param float $amount valor a transferir
     * @throws \Exception
    */

    public function transfer(UserAccounts $fromUserAccounts, string $toDocument, float $amount): void{

        $toUser = $this->userAccountsRepository->findByDocument($toDocument);

        if (!$toUser) {
            throw new \Exception("Usuário destinatário não encontrado");
        }

        $fromIsCnpj = strlen($fromUserAccounts->getDocument()) === 14;

        if ($fromIsCnpj) {
            throw new \Exception("Este usuário não pode realizar transações desta natureza.");
        }

        if ($amount <= 0) {
            throw new \Exception("O valor da transferência deve ser maior do que 0");
        }

        if ($fromUserAccounts->getBalance() < $amount) {
            throw new \Exception("Saldo insuficiente.");
        }

        // validação externa
        $approved = $this->externalValidation->validateTransaction($amount, TransactionsType::TRANSFER->value);
        if (!$approved) {
            throw new \Exception("Transação reprovada pelo validador externo");
        }

        $this->em->beginTransaction();

        try {
            $fromUserAccounts->debit($amount);
            $toUser->credit($amount);

            $transaction = new Transactions();
            $transaction->setType(TransactionsType::TRANSFER);
            $transaction->setFromUser($fromUserAccounts);
            $transaction->setToUser($toUser);
            $transaction->setAmount($amount);

            $this->em->persist($fromUserAccounts);
            $this->em->persist($toUser);
            $this->em->persist($transaction);
            $this->em->flush();

            $this->em->commit();

            // notifica
            try {
                $this->notifier->notifyTransfer(
                    $fromUserAccounts->getEmail(),
                    $toUser->getEmail(),
                    $amount,
                    $fromUserAccounts->getDocument(),
                    $toUser->getDocument()
                );
            } catch (\Throwable) {
                // ignorar
            }

        } catch (\Throwable $e) {
            $this->em->rollback();
            throw new \Exception("Erro ao processar tranferência: " . $e->getMessage());
        }

    }
}
