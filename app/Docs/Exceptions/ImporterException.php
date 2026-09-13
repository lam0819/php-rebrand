<?php

declare(strict_types=1);

namespace App\Docs\Exceptions;

use RuntimeException;

/**
 * Base type for every exception thrown by the documentation pipeline.
 *
 * Catching this single type lets a caller handle any pipeline failure without
 * coupling to the specific failure modes below.
 */
class ImporterException extends RuntimeException {}
