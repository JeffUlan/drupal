<?php
/**
 * PHPUnit
 *
 * Copyright (c) 2010-2014, Sebastian Bergmann <sebastian@phpunit.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    PHPUnit_MockObject
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2010-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @link       http://github.com/sebastianbergmann/phpunit-mock-objects
 * @since      File available since Release 1.0.0
 */

/**
 * Invocation matcher which looks for specific parameters in the invocations.
 *
 * Checks the parameters of all incoming invocations, the parameter list is
 * checked against the defined constraints in $parameters. If the constraint
 * is met it will return true in matches().
 *
 * @package    PHPUnit_MockObject
 * @author     Sebastian Bergmann <sebastian@phpunit.de>
 * @copyright  2010-2014 Sebastian Bergmann <sebastian@phpunit.de>
 * @license    http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
 * @version    Release: @package_version@
 * @link       http://github.com/sebastianbergmann/phpunit-mock-objects
 * @since      Class available since Release 1.0.0
 */
class PHPUnit_Framework_MockObject_Matcher_Parameters extends PHPUnit_Framework_MockObject_Matcher_StatelessInvocation
{
    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var PHPUnit_Framework_MockObject_Invocation
     */
    protected $invocation;

    /**
     * @param array $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $parameter) {
            if (!($parameter instanceof PHPUnit_Framework_Constraint)) {
                $parameter = new PHPUnit_Framework_Constraint_IsEqual(
                  $parameter
                );
            }

            $this->parameters[] = $parameter;
        }
    }

    /**
     * @return string
     */
    public function toString()
    {
        $text = 'with parameter';

        foreach ($this->parameters as $index => $parameter) {
            if ($index > 0) {
                $text .= ' and';
            }

            $text .= ' ' . $index . ' ' . $parameter->toString();
        }

        return $text;
    }

    /**
     * @param  PHPUnit_Framework_MockObject_Invocation $invocation
     * @return boolean
     */
    public function matches(PHPUnit_Framework_MockObject_Invocation $invocation)
    {
        $this->invocation = $invocation;
        return $this->verify();
    }

    /**
     * Checks if the invocation $invocation matches the current rules. If it
     * does the matcher will get the invoked() method called which should check
     * if an expectation is met.
     *
     * @param  PHPUnit_Framework_MockObject_Invocation      $invocation
     *                                                                  Object containing information on a mocked or stubbed method which
     *                                                                  was invoked.
     * @return bool
     * @throws PHPUnit_Framework_ExpectationFailedException
     */
    public function verify()
    {
        if ($this->invocation === NULL) {
            throw new PHPUnit_Framework_ExpectationFailedException(
              'Mocked method does not exist.'
            );
        }

        if (count($this->invocation->parameters) < count($this->parameters)) {
            $message = 'Parameter count for invocation %s is too low.';

            // The user called `->with($this->anything())`, but may have meant
            // `->withAnyParameters()`.
            //
            // @see https://github.com/sebastianbergmann/phpunit-mock-objects/issues/199
            if (count($this->parameters) === 1 &&
                get_class($this->parameters[0]) === 'PHPUnit_Framework_Constraint_IsAnything') {
                $message .= "\nTo allow 0 or more parameters with any value, omit ->with() or use ->withAnyParameters() instead.";
            }

            throw new PHPUnit_Framework_ExpectationFailedException(
              sprintf($message, $this->invocation->toString())
            );
        }

        foreach ($this->parameters as $i => $parameter) {
            $parameter->evaluate(
              $this->invocation->parameters[$i],
              sprintf(
                'Parameter %s for invocation %s does not match expected ' .
                'value.',

                $i,
                $this->invocation->toString()
              )
            );
        }

        return true;
    }
}
