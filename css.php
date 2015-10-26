<?php

header('Content-Type: text/css; charset=UTF-8');
$cols = key_exists('n', $_GET) ? max(0, intval($_GET['n'])) : 0;
echo '.mce-grid{border-spacing:0!important}.mce-grid td{padding:0}.mce-grid td:last-child{padding-left:3px}.mce-grid td div{border-style:solid none none solid!important}' . ($cols ? ".mce-grid td:nth-child($cols){padding-right:3px}.mce-grid td:nth-child($cols) div{border-right-style:solid!important}" : '') . '.mce-grid td:nth-child(' . (18 + $cols) . ') div,.mce-grid td:last-child div{border-right-style:solid!important}.mce-grid tr:nth-child(13) td div,.mce-grid tr:last-child td div{border-bottom-style:solid!important}.mce-grid tr:nth-child(14) td{padding-top:4px}';
