<?php
    header('Content-Type: text/css; charset=UTF-8');
    $cols = key_exists('n', $_REQUEST) ? $_REQUEST['n'] : 0;
?>
.mce-grid {
    border-spacing: 0 !important;
}
.mce-grid td {
    padding: 0;
}
.mce-grid td:last-child {
    padding-left: 3px;
}
.mce-grid td div {
    border-style: solid none none solid !important;
}
<?php
    if ($cols > 0) {
        ?>
.mce-grid td:nth-child(<?php echo $cols; ?>) {
    padding-right: 3px;
}
.mce-grid td:nth-child(<?php echo $cols; ?>) div {
    border-right-style: solid !important;
}
<?php
    }
?>
.mce-grid td:nth-child(<?php echo 18 + $cols ?>) div,
.mce-grid td:last-child div {
    border-right-style: solid !important;
}
.mce-grid tr:nth-child(13) td div,
.mce-grid tr:last-child td div {
    border-bottom-style: solid !important;
}
.mce-grid tr:nth-child(14) td {
    padding-top: 4px;
}