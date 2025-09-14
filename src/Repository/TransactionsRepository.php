<?php

namespace App\Repository;

use App\Entity\Transactions;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transactions::class);
    }

    /**
     * Retorna todas as transações de um usuário (como remetente ou destinatário)
    */
    public function findByUserDocument(string $document): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.fromUser', 'fu')
            ->join('t.toUser', 'tu')
            ->where('fu.document = :document OR tu.document = :document')
            ->setParameter('document', $document)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca transações de um usuário (como remetente ou destinatário)
    */
    public function findTransactionsByUser(string $document, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        $queryBuilder = $this->createQueryBuilder('t')
            ->join('t.fromUser', 'fu')
            ->join('t.toUser', 'tu')
            ->where('fu.document = :doc OR tu.document = :doc')
            ->setParameter('doc', $document)
            ->orderBy('t.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * Conta o total de transações de um usuário (para paginação)
    */
    public function countTransactionsByUser(string $document): int
    {
        $queryBuilder = $this->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->join('t.fromUser', 'fu')
            ->join('t.toUser', 'tu')
            ->where('fu.document = :doc OR tu.document = :doc')
            ->setParameter('doc', $document);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}

