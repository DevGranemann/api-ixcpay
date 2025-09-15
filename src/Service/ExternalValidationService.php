<?php

namespace App\Service;

class ExternalValidationService {

    /**
     *
     * Aleatório
     */
    public function validateTransaction(float $amount, string $type): bool
    {
        // acima de 5000 reprova
        if ($amount > 5000) {
            return false;
        }

        // 90% de chance de aprovar
        return rand(1, 100) <= 90;
    }
}
