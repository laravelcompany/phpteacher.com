<?php

$need = <<<EOM
FWC 2, 3, 7 9, 13, qat 5, qat 6 10 qat 11, 13, 14, qat 15,
qat 18, 19, ECU 1 2 3, 5, 6, 10, 13 19, SEN 7, 8, sen 11,
sen 19, NED 2, 3, 13, ENG 7, ENG 10, ENG 19,
IRN 2, 3, 5, 7, 10 12 17 19, USA 2, USA 12, USA 14 15 16,
USA 17, USA 19, WAL 2, WAL 5, WAL 16, ARG 4 8 9 10
Arg 11, Arg 14, Arg 18 , ECU 14 15, ECU 18 Ned 17
EOM;

$dupes = <<<EOM
00, FWC 1,4, FWC 12, FWC 17, QAT 1, QAT 3,4 ECU 18 19
GER 2,8, 10, 13,14, 15,  JPN 3, JPN 10, JPN 14 15 19,
JPN 20, BEL 3 9 12 14 15 16 19, Can 5,7,17,19, USA 4,
MAR 4, MAR 14, MAR 16, SEN 11, SEN 19, NED 2,3,13,ger 20,
CRO 6, CRO 18, BRA 1, BRA 8, BRA 13, SRB 5,10 14, SRB 16,
SRB 18, 20, SUI 4, SUI 6, SUI 9, SUI 11, SUI 15, SUI 20,
CMR 7 9,11,13,15,16, POR 3, 5, 8, POR 10,11,12, WAL 16
por 15,16, 18, Wal 2,5, USA 15,16,17
EOM;

$need = toStickerList($need);
$dupes = toStickerList($dupes);

$swap = array_intersect($need, $dupes);
echo "You can swap the following: " . implode(', ', $swap);