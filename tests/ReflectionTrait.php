<?php

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2022, Ferry Cools (DigiLive)
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

declare(strict_types=1);

namespace DigiLive\GitChangelog\Tests;

use ReflectionClass;

/**
 * Introspect classes, interfaces, functions, methods and extensions.
 *
 * Wrapper around the reflection API of php.
 */
trait ReflectionTrait
{
    /**
     * Set a non-static private or protected property on an object via reflection.
     *
     * @param   object  $object    The object to reflect.
     * @param   string  $property  The property name.
     * @param   mixed   $value     The new value.
     *
     * @return void
     * @throws \ReflectionException If no property exists by that name.
     */
    private function setPrivateProperty(object $object, string $property, $value): void
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * Get a non-static private or protected property on an object via reflection.
     *
     * @param   object  $object    The object to reflect.
     * @param   string  $property  The property name.
     *
     * @return mixed The property value.
     * @throws \ReflectionException If no property exists by that name.
     */
    private function getPrivateProperty(object $object, string $property)
    {
        $reflection         = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }

    /**
     * Get a private or protected method on an object via reflection.
     *
     * @param   object  $object  The object to reflect.
     * @param   string  $method  The method name.
     *
     * @return \ReflectionMethod The ReflectionMethod for a class method.
     * @throws \ReflectionException If the method does not exist.
     */
    private function getPrivateMethod(object $object, string $method): object
    {
        $reflection       = new ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);

        $reflectionMethod->setAccessible(true);

        return $reflectionMethod;
    }
}
