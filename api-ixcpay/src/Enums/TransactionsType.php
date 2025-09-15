<?php

namespace App\Enums;

enum TransactionsType: string {

    case TRANSFER = 'transfer';
    case DEPOSIT = 'deposit';
    case TAKEOUTAMOUNT = 'withdraw';
    case REVERSAL = 'reversal';
}
