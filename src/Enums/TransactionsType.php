<?php

namespace App\Enum;

enum TransactionsType: string {

    case TRANSFER = 'transfer';
    case DEPOSIT = 'deposit';
    case TAKEOUTAMOUNT = 'withdraw';
}
