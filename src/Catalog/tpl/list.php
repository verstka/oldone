<?php

foreach ($materials as $material) {
    echo static::render('item', ['item' => $material]);
}

?>