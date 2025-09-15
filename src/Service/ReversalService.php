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

        if (!$fromUser || !$toUser) {
            throw new \Exception("Transação inválida para estorno.");
        }

        $fromUser->setBalance($fromUser->getBalance() + $transaction->getAmount());
        $toUser->setBalance($toUser->getBalance() - $transaction->getAmount());

        $reversal = new Transactions();
        $reversal->setFromUser($toUser);
        $reversal->setToUser($fromUser);
        $reversal->setAmount($transaction->getAmount());
        $reversal->setType(TransactionsType::REVERSAL);

        $this->em->persist($fromUser);
        $this->em->persist($toUser);
        $this->em->persist($reversal);
        $this->em->flush();

        return $reversal;
    }
}
