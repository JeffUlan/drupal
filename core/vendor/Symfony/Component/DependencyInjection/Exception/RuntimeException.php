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
 * Base RuntimeException for Dependency Injection component.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class RuntimeException extends \RuntimeException implements ExceptionInterface
{
}
