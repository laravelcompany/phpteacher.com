<?php

declare(strict_types=1);

interface SQLKml
{
    public const SQL_INSERT_PLACEMARKS = <<< SIP
insert ignore into `placemarks` (`league_id`, `doc_name`, 
`point_name`, `coord_x`, `coord_y`, `coord_z`)
VALUES (?, ?, ?, ?, ?, ?)
SIP;

}