<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Entity\UserAccounts;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enums\TransactionsType;
use App\Service\Notification\NotificationServiceInterface;

class DepositService
{
    private EntityManagerInterface $em;
    private UserAccountsRepository $accountsRepository;
    private ExternalValidationService $externalValidation;
    private ReversalService $reversalService;
    private NotificationServiceInterface $notifier;

    public function __construct(EntityManagerInterface $em, UserAccountsRepository $accountsRepository, ExternalValidationService $externalValidation, ReversalService $reversalService, NotificationServiceInterface $notifier)
    {
        $this->em = $em;
        $this->accountsRepository = $accountsRepository;
        $this->externalValidation = $externalValidation;
        $this->reversalService = $reversalService;
        $this->notifier = $notifier;
    }

    /**
     * Realiza depósito em uma conta existente
     *
     * @param int $accountId
     * @param float $amount
     * @return Transactions
     * @throws \Exception
    */

    public function deposit(int $accountId, float $amount): Transactions
    {
        if ($amount <= 0) {
            throw new \Exception("Valor inválido para depósito");
        }

        /** @var UserAccounts|null $account */
        $account = $this->accountsRepository->find($accountId);

        if (!$account) {
            throw new \Exception("Conta não encontrada");
        }

        // Validação externa
        $approved = $this->externalValidation->validateTransaction($amount, TransactionsType::DEPOSIT->value);
        if (!$approved) {
            throw new \Exception("Transação reprovada pelo validador externo");
        }

        $this->em->beginTransaction();
        try {
            $account->setBalance($account->getBalance() + $amount);

            // Registro
            $transaction = new Transactions();
            $transaction->setFromUser($account);
            $transaction->setToUser($account);
            $transaction->setAmount($amount);
            $transaction->setType(TransactionsType::DEPOSIT);

            $this->em->persist($account);
            $this->em->persist($transaction);
            $this->em->flush();

            $this->em->commit();

            // notificação
            try {
                $this->notifier->notifyAccountOperation(
                    $account->getEmail(),
                    'deposit',
                    $amount,
                    $account->getDocument()
                );
            } catch (\Throwable) {
                // mock: ignora
            }

            return $transaction;
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}
