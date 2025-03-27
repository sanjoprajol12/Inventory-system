<?php

$buying_rate = $_POST['buying_rate'];

$sql = "UPDATE products SET name = ?, quantity = ?, price = ?, buying_rate = ?, selling_rate = ? WHERE id = ?"; 