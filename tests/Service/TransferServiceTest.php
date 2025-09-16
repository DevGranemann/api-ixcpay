<?php

namespace App\Tests\Service;

use App\Entity\UserAccounts;
use App\Entity\Transactions;
use App\Enums\TransactionsType;
use App\Repository\UserAccountsRepository;
use App\Service\TransferService;
use App\Service\ExternalValidationService;
use App\Service\ReversalService;
use App\Service\Notification\NotificationServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class TransferServiceTest extends TestCase
{
    private $em;
    private $userRepo;
    private $externalValidation;
    private $reversalService;
    private $notifier;
    private $transferService;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->userRepo = $this->createMock(UserAccountsRepository::class);
        $this->externalValidation = $this->createMock(ExternalValidationService::class);
        $this->reversalService = $this->createMock(ReversalService::class);
        $this->notifier = $this->createMock(NotificationServiceInterface::class);

        $this->transferService = new TransferService(
            $this->em,
            $this->userRepo,
            $this->externalValidation,
            $this->reversalService,
            $this->notifier
        );
    }

    public function testTransferFailsWhenRecipientNotFound(): void
    {
        $fromUser = new UserAccounts();
        $fromUser->setDocument("12345678901"); // CPF

        $this->userRepo->method('findByDocument')->willReturn(null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Usuário destinatário não encontrado");

        $this->transferService->transfer($fromUser, "98765432100", 100);
    }

    public function testTransferFailsWhenFromIsCnpj(): void
    {
        $fromUser = new UserAccounts();
        $fromUser->setDocument("12345678000199"); // CNPJ

        $toUser = new UserAccounts();
        $toUser->setDocument("98765432100");

        $this->userRepo->method('findByDocument')->willReturn($toUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Este usuário não pode realizar transações desta natureza.");

        $this->transferService->transfer($fromUser, $toUser->getDocument(), 100);
    }

    public function testTransferFailsWhenInsufficientBalance(): void
    {
        $fromUser = new UserAccounts();
        $fromUser->setDocument("12345678901");
        $fromUser->setBalance(50);

        $toUser = new UserAccounts();
        $toUser->setDocument("98765432100");

        $this->userRepo->method('findByDocument')->willReturn($toUser);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Saldo insuficiente.");

        $this->transferService->transfer($fromUser, $toUser->getDocument(), 100);
    }

    public function testSuccessfulTransfer(): void
    {
        $fromUser = new UserAccounts();
        $fromUser->setDocument("12345678901");
        $fromUser->setBalance(200);
        $fromUser->setEmail("from@test.com");

        $toUser = new UserAccounts();
        $toUser->setDocument("98765432100");
        $toUser->setBalance(50);
        $toUser->setEmail("to@test.com");

        $this->userRepo->method('findByDocument')->willReturn($toUser);
        $this->externalValidation->method('validateTransaction')->willReturn(true);

        // Mockando EntityManager (simular persistência e transação)
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->em->expects($this->exactly(3))
            ->method('persist')
            ->withConsecutive(
                [$this->isInstanceOf(UserAccounts::class)],
                [$this->isInstanceOf(UserAccounts::class)],
                [$this->isInstanceOf(Transactions::class)]
            );


        $this->transferService->transfer($fromUser, $toUser->getDocument(), 100);

        $this->assertEquals(100, $fromUser->getBalance());
        $this->assertEquals(150, $toUser->getBalance());
    }
}
