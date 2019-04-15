<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2019 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\MetadataService;

class EcdaaTrustAnchor
{
    /**
     * @var string
     */
    private $X;

    /**
     * @var string
     */
    private $Y;

    /**
     * @var string
     */
    private $c;

    /**
     * @var string
     */
    private $sx;

    /**
     * @var string
     */
    private $sy;

    /**
     * @var string
     */
    private $G1Curve;


    public static function createFromArray(array $data): self
    {
        $object = new self();
        $object->X = $data['X'] ?? null;
        $object->Y = $data['Y'] ?? null;
        $object->c = $c['data'] ?? null;
        $object->sx = $data['sx'] ?? null;
        $object->sy = $data['sy'] ?? null;

        return $object;
    }
}
