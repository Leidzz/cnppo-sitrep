<?php

namespace App\Exceptions;

use RuntimeException;

class InvalidStatusTransition extends RuntimeException
{
    // Dedicated exception so tests (and the panel) can distinguish
    // "illegal workflow move" from generic crashes.
}
