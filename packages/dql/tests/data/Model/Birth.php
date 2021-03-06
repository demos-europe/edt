<?php

declare(strict_types=1);

namespace Tests\data\Model;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class Birth
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $country;
    /**
     * @var string|null
     */
    private $region;
    /**
     * @var string
     */
    private $locality;
    /**
     * @var int
     */
    private $day;
    /**
     * @var int
     */
    private $month;
    /**
     * @var int
     */
    private $year;
}
