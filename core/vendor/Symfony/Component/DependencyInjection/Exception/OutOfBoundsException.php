<?php

/*
 * This file is part of the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Symfony\Component\DependencyInjection\Exception;

/**
 * Base OutOfBoundsException for Dependency Injection component.
 */
class OutOfBoundsException extends \OutOfBoundsException implements ExceptionInterface
{
}
