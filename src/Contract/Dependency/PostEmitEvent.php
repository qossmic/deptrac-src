<?php

declare(strict_types=1);

namespace Qossmic\Deptrac\Contract\Dependency;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event triggered after all the dependencies have been resolved.
 */
final class PostEmitEvent extends Event {}
