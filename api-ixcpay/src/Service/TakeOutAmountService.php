<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserAccounts;
use App\Enums\TransactionsType;
use App\Service\ExternalValidationMock;
use App\Service\ReversalService;

class TakeOutAmountService {

    private EntityManagerInterface $em;
    private UserAccountsRepository $accountsRepository;
    private ExternalValidationMock $validationService;
    private ReversalService $reversalService;

    public function __construct(
        EntityManagerInterface $em,
        UserAccountsRepository $accountsRepository,
        ExternalValidationMock $validationService,
        ReversalService $reversalService)
    {
        $this->em = $em;
        $this->accountsRepository = $accountsRepository;
        $this->validationService = $validationService;
        $this->reversalService = $reversalService;
    }

    /**
     * Saque em uma conta existente
     *
     * @param int $accountId
     * @param float $amount
     * @return Transactions
     * @throws \Exception
     */
    public function takeOutValue(int $accountId, float $amount): Transactions{

        if ($amount <= 0) {
            throw new \Exception("Valor inválido para saque");
        }

        /** @var UserAccounts|null $account */
        $account = $this->accountsRepository->find($accountId);

        if (!$account) {
            throw new \Exception("Conta não encontrada");
        }

        if ($account->getBalance() < $amount) {
            throw new \Exception("Saldo insuficiente para realizar o saque");
        }

        $this->em->getConnection()->beginTransaction();

        try {

            $account->debit($amount);

            $transaction = new Transactions();
            $transaction->setType(TransactionsType::TAKEOUTAMOUNT);
            $transaction->setFromUser($account);
            $transaction->setToUser($account);
            $transaction->setAmount($amount);

            $this->em->persist($account);
            $this->em->persist($transaction);
            $this->em->flush();

            if (!$this->validationService->validateTransaction($transaction)) {
                $reversal = $this->reversalService->reverse($transaction);
                $this->em->getConnection()->commit();
                return $reversal;
            }

            $this->em->getConnection()->commit();
            return $transaction;
        } catch (\Throwable $e) {
            $this->em->getConnection()->rollBack();
            throw new \Exception("Erro ao processar saque: " . $e->getMessage());
        }
    }

}
