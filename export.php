<?php
$date = date('Y-m-d_H-i');
require '/var/www/html/amazon-cat-scraper/Model/Init.php';
require '/var/www/html/amazon-cat-scraper/Model/Scraper.php';
//require 'Model/Init.php';
//require 'Model/Scraper.php';
$scraper = new Scraper();
$products = $scraper->getProducts();

$csv = 'reports/amazon_products_'.$date.'.csv';
$data[] = implode('","', array(
    'ASIN',
    'Sales Rank',
    'Price',
    'Third Seller Price'
));
foreach($products as $row){
    $data[] = implode('","', array(
            $row['asin'],
            $row['rank'],
            $row['price'],
            $row['ts_seller']
        )
    );
}

$file = fopen($csv,"w");
foreach ($data as $line){
    fputcsv($file, explode('","',$line));
}
fclose($file);

