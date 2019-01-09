<?php
require '/var/www/html/amazon-cat-scraper/Model/Init.php';
require '/var/www/html/amazon-cat-scraper/Model/Scraper.php';
require '/var/www/html/amazon-cat-scraper/simple_html_dom.php';
//require 'Model/Init.php';
//require 'Model/Scraper.php';
//require 'simple_html_dom.php';

$scraper = new Scraper();
$products = $scraper->getProductLink();

foreach ($products as $row){
    $id = $row['id'];
    $url = 'https://www.amazon.com/gp/offer-listing/'.$row['asin'].'/ref=dp_olp_all_mbc?ie=UTF8&condition=all';

    $htmlData = $scraper->curlTo($url);
    if ($htmlData['html']) {
        $html = str_get_html($htmlData['html']);
        $price = $html->find('.olpOfferPrice', 2);
        if($price){
            $sellerPrice = trim(str_replace($letters, '', $price->plaintext));
        }else{
            $price = $html->find('.olpOfferPrice', 1);
            if($price) {
                $sellerPrice = trim(str_replace($letters, '', $price->plaintext));
            }else{
                $price = $html->find('.olpOfferPrice', 0);
                if($price) {
                    $sellerPrice = trim(str_replace($letters, '', $price->plaintext));
                }else{
                    $sellerPrice = 0;
                }
            }
        }

        echo $sellerPrice . '<br>';

        $scraper->updateProduct($id, $sellerPrice);

    }

}