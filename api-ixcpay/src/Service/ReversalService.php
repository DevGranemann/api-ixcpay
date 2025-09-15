<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Enums\TransactionsType;
use App\Repository\TransactionsRepository;
use Doctrine\ORM\EntityManagerInterface;

class ReversalService
{
    private EntityManagerInterface $em;
    private TransactionsRepository $transactionsRepository;

    public function __construct(
        EntityManagerInterface $em,
        TransactionsRepository $transactionsRepository
    ) {
        $this->em = $em;
        $this->transactionsRepository = $transactionsRepository;
    }

    /**
     * Estorna uma transação existente
     *
     * @param Transactions $transaction
     * @return Transactions
     * @throws \Exception
     */
    public function reverse(Transactions $transaction): Transactions
    {
        $fromUser = $transaction->getFromUser();
        $toUser = $transaction->getToUser();
        $amount = $transaction->getAmount();

        if (!$fromUser || !$toUser) {
            throw new \Exception("Transação inválida para estorno.");
        }

        $reversal = new Transactions();
        $reversal->setType(TransactionsType::REVERSAL);

        if ($transaction->getType() === TransactionsType::TRANSFER) {
            $fromUser->setBalance($fromUser->getBalance() + $amount);
            $toUser->setBalance($toUser->getBalance() - $amount);

            $reversal->setFromUser($toUser);
            $reversal->setToUser($fromUser);
            $reversal->setAmount($amount);
        } elseif ($transaction->getType() === TransactionsType::DEPOSIT) {
            // Estorno de depósito: debita o valor depositado
            $account = $fromUser; // mesmo usuário em from/to
            $account->setBalance($account->getBalance() - $amount);

            $reversal->setFromUser($account);
            $reversal->setToUser($account);
            $reversal->setAmount($amount);
        } else { // TAKEOUTAMOUNT
            // Estorno de saque: credita de volta o valor sacado
            $account = $fromUser; // mesmo usuário em from/to
            $account->setBalance($account->getBalance() + $amount);

            $reversal->setFromUser($account);
            $reversal->setToUser($account);
            $reversal->setAmount($amount);
        }

        $this->em->persist($fromUser);
        $this->em->persist($toUser);
        $this->em->persist($reversal);
        $this->em->flush();

        return $reversal;
    }
}
