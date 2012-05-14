<?php
/**
 * Go! OOP&AOP PHP framework
 *
 * @copyright     Copyright 2012, Lissachenko Alexander <lisachenko.it@gmail.com>
 * @license       http://www.opensource.org/licenses/mit-license.php The MIT License
 */

namespace Go\Aop\Support;

use Reflector;
use ReflectionClass;

use Go\Aop\ClassFilter;

/**
 * Simple ClassFilter implementation that passes classes (and optionally subclasses)
 */
class RootClassFilter implements ClassFilter
{

    /**
     * Class name
     *
     * @var string
     */
    protected $className = null;

    /**
     * Class constructor
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;
    }

    /**
     * Performs matching of class
     *
     * @param Reflector|ReflectionClass $class Class instance
     *
     * @return bool
     */
    public function matches(Reflector $class)
    {
        if (!$class instanceof ReflectionClass) {
            return false;
        }
        $isCurrentClass = $class->getName() == $this->className;
        return $isCurrentClass || $class->isSubclassOf($this->className);
    }
}
