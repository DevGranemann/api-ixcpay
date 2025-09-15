<?php

namespace App\Service;

class ExternalValidationMock {

    /**
     *
     * Pode aprovar ou reprovar de forma aleatória
     */
    public function validateTransaction($transaction): bool
    {
        $amount = $transaction->getAmount();
        $type = $transaction->getType();

        // Exemplo simples: reprova valores acima de 5000
        if ($amount > 5000) {
            return false;
        }

        // 90% de chance de aprovar
        return rand(1, 100) <= 90;
    }
}
