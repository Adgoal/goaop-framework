<?php
declare(strict_types = 1);
/*
 * Go! AOP framework
 *
 * @copyright Copyright 2011, Lisachenko Alexander <lisachenko.it@gmail.com>
 *
 * This source file is subject to the license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Go\Aop\Framework;

use Closure;
use Go\Core\AspectKernel;
use ReflectionFunction;
use ReflectionMethod;
use Serializable;
use Go\Aop\Intercept\Interceptor;

/**
 * Base class for all framework interceptor implementations
 *
 * This class describe an action taken by the interceptor at a particular joinpoint.
 * Different types of interceptors include "around", "before" and "after" advices.
 *
 * Around interceptor is an advice that surrounds a joinpoint such as a method invocation. This is the most powerful
 * kind of advice. Around advices will perform custom behavior before and after the method invocation. They are
 * responsible for choosing whether to proceed to the joinpoint or to shortcut executing by returning their own return
 * value or throwing an exception.
 *
 * After and before interceptors are simple closures that will be invoked after and before main invocation.
 *
 * Framework models an interceptor as an PHP-closure, maintaining a chain of interceptors "around" the joinpoint:
 *   public function (Joinpoint $joinPoint)
 *   {
 *      echo 'Before action';
 *      // call chain here with Joinpoint->proceed() method
 *      $result = $joinPoint->proceed();
 *      echo 'After action';
 *
 *      return $result;
 *   }
 */
abstract class AbstractInterceptor implements Interceptor, OrderedAdvice, Serializable
{
    /**
     * Local cache of advices for faster unserialization on big projects
     *
     * @var Closure[]
     */
    protected static $localAdvicesCache = [];

    /**
     * Pointcut expression string which was used for this interceptor
     */
    protected $pointcutExpression;

    /**
     * Closure to call
     */
    protected $adviceMethod;

    /**
     * Advice order
     */
    private $adviceOrder;

    /**
     * Default constructor for interceptor
     */
    public function __construct(Closure $adviceMethod, int $adviceOrder = 0, string $pointcutExpression = '')
    {
        $this->adviceMethod       = $adviceMethod;
        $this->adviceOrder        = $adviceOrder;
        $this->pointcutExpression = $pointcutExpression;
    }

    /**
     * Serialize advice method into array
     */
    public static function serializeAdvice(Closure $adviceMethod): array
    {
        $refAdvice = new ReflectionFunction($adviceMethod);

        return [
            'method' => $refAdvice->name,
            'aspect' => get_class($refAdvice->getClosureThis())
        ];
    }

    /**
     * Unserialize an advice
     *
     * @param array $adviceData Information about advice
     */
    public static function unserializeAdvice(array $adviceData): Closure
    {
        $aspectName = $adviceData['aspect'];
        $methodName = $adviceData['method'];

        if (!isset(static::$localAdvicesCache["$aspectName->$methodName"])) {
            $aspect    = AspectKernel::getInstance()->getContainer()->getAspect($aspectName);
            $refMethod = new ReflectionMethod($aspectName, $methodName);
            $advice    = $refMethod->getClosure($aspect);

            static::$localAdvicesCache["$aspectName->$methodName"] = $advice;
        }

        return static::$localAdvicesCache["$aspectName->$methodName"];
    }

    /**
     * Returns the advice order
     */
    public function getAdviceOrder(): int
    {
        return $this->adviceOrder;
    }

    /**
     * Getter for extracting the advice closure from Interceptor
     */
    public function getRawAdvice(): Closure
    {
        return $this->adviceMethod;
    }

    /**
     * Serializes an interceptor into string representation
     *
     * @return string the string representation of the object or null
     */
    public function serialize()
    {
        $vars = array_filter(get_object_vars($this));
        $vars['adviceMethod'] = static::serializeAdvice($this->adviceMethod);

        return serialize($vars);
    }

    /**
     * Unserialize an interceptor from the string
     *
     * @param string $serialized The string representation of the object.
     * @return void
     */
    public function unserialize($serialized)
    {
        $vars = unserialize($serialized);
        $vars['adviceMethod'] = static::unserializeAdvice($vars['adviceMethod']);
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }
    }
}
