<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocketeer\Interfaces;

interface IdentifierInterface
{
    /**
     * Get a global identifier for this entity.
     *
     * @return string
     */
    public function getIdentifier();
}
