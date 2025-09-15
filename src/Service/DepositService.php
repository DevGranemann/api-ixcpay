<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Entity\UserAccounts;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enums\TransactionsType;

class DepositService
{
    private EntityManagerInterface $em;
    private UserAccountsRepository $accountsRepository;

    public function __construct(EntityManagerInterface $em, UserAccountsRepository $accountsRepository)
    {
        $this->em = $em;
        $this->accountsRepository = $accountsRepository;
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

        return $transaction;
    }
}
