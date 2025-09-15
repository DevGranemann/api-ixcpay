<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\UserAccounts;
use App\Enums\TransactionsType;

class TakeOutAmountService {

    private EntityManagerInterface $em;
    private UserAccountsRepository $accountsRepository;
    private ExternalValidationService $externalValidation;
    private ReversalService $reversalService;

    public function __construct(EntityManagerInterface $em, UserAccountsRepository $accountsRepository, ExternalValidationService $externalValidation, ReversalService $reversalService)
    {
        $this->em = $em;
        $this->accountsRepository = $accountsRepository;
        $this->externalValidation = $externalValidation;
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

        $approved = $this->externalValidation->validateTransaction($amount, TransactionsType::TAKEOUTAMOUNT->value);
        if (!$approved) {
            throw new \Exception("Transação reprovada pelo validador externo");
        }

        $this->em->beginTransaction();
        try {
            $account->setBalance($account->getBalance() - $amount);

            $transaction = new Transactions();
            $transaction->setType(TransactionsType::TAKEOUTAMOUNT);
            $transaction->setFromUser($account);
            $transaction->setToUser($account);
            $transaction->setAmount($amount);

            $this->em->persist($account);
            $this->em->persist($transaction);
            $this->em->flush();

            $this->em->commit();
            return $transaction;
            
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw new \Exception("Falha ao processar saque: " . $e->getMessage());
        }
    }

}
