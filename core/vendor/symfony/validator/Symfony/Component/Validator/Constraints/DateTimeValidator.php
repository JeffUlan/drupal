<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @api
 */
class DateTimeValidator extends DateValidator
{
    const PATTERN = '/^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/';

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof DateTime) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\DateTime');
        }

        if (null === $value || '' === $value || $value instanceof \DateTime) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        if (!preg_match(static::PATTERN, $value, $matches)) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();

            return;
        }

        if (!DateValidator::checkDate($matches[1], $matches[2], $matches[3])) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }

        if (!TimeValidator::checkTime($matches[4], $matches[5], $matches[6])) {
            $this->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->addViolation();
        }
    }
}
