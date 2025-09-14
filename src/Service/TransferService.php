<?php

namespace App\Service;

use App\Entity\Transactions;
use App\Entity\UserAccounts;
use App\Repository\UserAccountsRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransferService {

    private EntityManagerInterface $em;
    private UserAccountsRepository $userAccountsRepository;

    public function __construct(EntityManagerInterface $em, UserAccountsRepository $userAccountsRepository) {
        $this->em = $em;
        $this->userAccountsRepository = $userAccountsRepository;
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

        $this->em->beginTransaction();

        try {
            $fromUserAccounts->debit($amount);
            $toUser->credit($amount);

            // para armazenar no banco
            $transaction = new Transactions();
            $transaction->setFromUser($fromUserAccounts);
            $transaction->setToUser($toUser);
            $transaction->setAmount($amount);

            $this->em->persist($fromUserAccounts);
            $this->em->persist($toUser);
            $this->em->flush();

            $this->em->commit();

        } catch (\Throwable $e) {
            $this->em->rollback();
            throw new \Exception("Erro ao processar tranferência: " . $e->getMessage());
        }

    }
}
