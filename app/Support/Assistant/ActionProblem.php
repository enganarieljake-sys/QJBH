<?php

namespace App\Support\Assistant;

use RuntimeException;

/**
 * A proposal that can't be prepared, e.g. no tenant by that name. The message
 * goes back to the AI so it can tell the admin or ask a follow-up question.
 */
class ActionProblem extends RuntimeException
{
}
